<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setJournalEntry.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/import_aid.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';

    
    $b_query = "SELECT * FROM inventory_qblog 
                WHERE error = '0' 
                AND `source` = 'generate_bill' 
                order by date desc
                LIMIT 200;";
    $b_result = qdb($b_query, "PIPE") or die(qe()." | $b_query");
	
	$old = array();
	
    echo("<pre>");

    $db_company_refs = array();
    function db_company_name($list_id){
        global $db_company_refs;
        if(!$db_company_refs[$list_id]){
            $query = "SELECT `name` FROM `inventory_company` WHERE qbref_c LIKE '%$list_id%' OR qbref_v LIKE '%$list_id%';";
            $result = qdb($query,"PIPE") or die(qe()." | $query");
            $result = mysqli_fetch_assoc($result);
            $db_company_refs[$list_id] = trim($result['name']);
        }
        return($db_company_refs[$list_id]);
    }
    function find_line_item_inv($desc,$table = 'invoice'){
        if($table == "invoice"){
            $type_query = "SELECT id, oqi_id, repair_id, it_id FROM inventory_invoiceli where memo = ".prep($desc).";";
            $type_result = qdb($type_query,"PIPE") or die(qe("PIPE")." ".$type_query);
            $type = mysqli_fetch_assoc($type_result);
            $partid = '';
            if($type["oqi_id"]){
                $part_collection = "SELECT i.part_number, i.heci, i.clei, i.short_description, im.name manf, iq.warranty_period_id warr
                FROM inventory_outgoing_quote_invoice ioqi, inventory_outgoing_quote ioq, inventory_inventory i, inventory_manufacturer im, inventory_quote iq
                WHERE ioqi.id = ".prep($type["oqi_id"])." AND ioqi.oq_id = ioq.id AND ioq.inventory_id = i.id AND i.manufacturer_id_id = im.id AND ioq.quote_id = iq.id;";
            }
        } else if ($table == "bill"){
            $type_query = "SELECT id, iqi_id, co_id, repair_id FROM inventory_billli where memo = ".prep($desc)." AND iqi_id > '';";
            $type_result = qdb($type_query,"PIPE") or die(qe("PIPE")." ".$type_query);
            $type = mysqli_fetch_assoc($type_result);
            if($type['iqi_id']){
                $part_collection = "SELECT i.part_number, i.heci, i.clei, i.short_description, im.name manf, ipi.po_id po_number
                FROM inventory_incoming_quote_invoice iiqi, inventory_incoming_quote iiq, inventory_inventory i, inventory_manufacturer im, inventory_purchaseinvoice ipi
                WHERE iiqi.id = ".prep($type['iqi_id'])." AND iiqi.iq_id = iiq.id and iiq.inventory_id = i.id and i.manufacturer_id_id = im.id AND iiqi.purchase_invoice_id = ipi.id;";
            }
        }
        $partid = '';
        $id = $type['id'];
        if($part_collection){
            $parts_results = qdb($part_collection,"PIPE") or die(qe("PIPE")." ".$part_collection);
            $res = mysqli_fetch_assoc($parts_results);
            $partid = part_process($res);
        } else {
            // echo("Described as ".$desc);
        }
        return(array($id => $partid));
    }
    
	foreach($b_result as $brow){
		$assoc = simplexml_load_string($brow['output']);
		$old = array();
		if($brow['source'] == 'generate_invoice'){
			if($assoc->QBXMLMsgsRq->InvoiceAddRq){
				//If the value is not an invoice Additonal Record
				// print_r($assoc->QBXMLMsgsRq->InvoiceAddRq);
			    continue;
                $memo = (explode("#",current($assoc->QBXMLMsgsRq->InvoiceAddRq->InvoiceAdd->Memo)));
				$number = trim($memo[1]);
				$cust_ref = (current($assoc->QBXMLMsgsRq->InvoiceAddRq->InvoiceAdd->CustomerRef));
				$ref = ($assoc->QBXMLMsgsRq->InvoiceAddRq->InvoiceAdd->RefNumber);
				$ref = explode("-",$ref);
				if(trim($memo[0]) == "SO"){
				    $type = 'Sale';
				} else {
				    $type = 'Repair';
				}
				$id = current(current($assoc->QBXMLMsgsRq->InvoiceAddRq->attributes()));
				$old['invoice_no'] = $id;
                $old['company'] = db_company_name($cust_ref);
                if(!$old['company']){
                    if($assoc->QBXMLMsgsRq->InvoiceAddRq->InvoiceAdd->CustomerRef->FullName){
                        $old['company'] = rtrim(current($assoc->QBXMLMsgsRq->InvoiceAddRq->InvoiceAdd->CustomerRef->FullName)," C");
                    }
                }
                $old['date_invoiced'] = current($assoc->QBXMLMsgsRq->InvoiceAddRq->InvoiceAdd->TxnDate);
                $old['order_number'] = $number;
                $old['order_type'] = $type;
                $old['freight'] = 0.00;
                
                //Grab Meta Level Information from the system
                $query = "SELECT `invoice_no`, `companyid` as company, `date_invoiced`, `order_number`, 
                `order_type`, `freight` FROM invoices WHERE `invoice_no` = $id;";
                $result = qdb($query) or die(qe()." | $query");
                $row = mysqli_fetch_assoc($result);
                $row['date_invoiced'] = format_date($row['date_invoiced'],"Y-m-d");
                $row['company'] = trim(getCompany($row['company']));
                $row['freight'] = floor($row['freight']);
                
                //Line Item Parse
                if($assoc->QBXMLMsgsRq->InvoiceAddRq->InvoiceAdd->InvoiceLineAdd){
                    $old_lines = array();
                    foreach($assoc->QBXMLMsgsRq->InvoiceAddRq->InvoiceAdd->InvoiceLineAdd as $item){
                        // print_r($item);
                        if($item->Desc == "Freight Charge" || trim($item->Desc) == "Freight"){
                            //Add freight to old row
                            $old['freight'] += $item->Rate;
                        } else {
                            // print_r(current($item->Desc));
                            // $ds = desc_walk($desc);
                            $desc = current($item->Desc); 
                            $line_info = find_line_item_inv($desc, "invoice");
                            $partid = current($line_info);
                            if(!$partid){
                                // echo("Not an item sale: No Part Id <br>-----------<br>");
                                continue(2);
                            } else {
                    		  //  echo("<br>------------<br>".$brow['source']." - Invoice #$id<br>");
                            }
                            $qty = $item->Quantity;
                            $amount = round($item->Rate,2);
                            $key = "$qty+$partid";
                            $old_lines[$key] = $amount;
                            // echo($amount);
                        }
                    }
                    $old['freight'] = floor($old['freight']);
                    $item_query = "Select `partid`,`qty`,`price` FROM invoice_items WHERE `invoice_no` = $id;";
                    $item_results = qdb($item_query) or die(qe()." | ".$item_query);
                    foreach($item_results as $line){
                        $test = array();
                        $l_amount = trim(floor($line['price']));
                        $l_qty = trim($line['qty']);
                        $key = $l_qty."+".$line['partid'];
                        
                        $test[$key] = $l_amount;
                        if(isset($old_lines[$key])){
                            if($old_lines[$key] == $l_amount){
                                // echo("<br>Line Clear!<br>");
                            }
                            else{
                                echo("--------------<br>Invoice No $id mismatches on lines:<br>");
                                print_r($old_lines);
                                print_r($line);
                                exit;
                            }
                        } else {
                            echo("--------------<br>Invoice No $id mismatches on lines:<br>");
                            print_r($old_lines);
                            print_r($line);
                            exit;
                        }
                    }
                } else {
                    // echo($brow['source']." - Invoice Proper: No Rows<br><br>");
                }
                if($old != $row){
                    print_r($old);
                    echo("-----------<br>");
                    print_r($row);
                    // exit;
                } else {
                    // echo("Invoice No $id Imported Correctly! <br>------------<br><br>");
                }

			} else if ($assoc->QBXMLMsgsRq->VendorCreditAddRq){
			    continue;
			 //   print_r ($assoc->QBXMLMsgsRq->VendorCreditAddRq);
                $memo = (explode("CM",current($assoc->QBXMLMsgsRq->VendorCreditAddRq->VendorCreditAdd->RefNumber)));
				$number = trim($memo[0]);
				
				$listid = (current($assoc->QBXMLMsgsRq->VendorCreditAddRq->VendorCreditAdd->VendorRef->ListID));
				if ($listid){
    				$old['company'] = db_company_name($listid);
				} else if($assoc->QBXMLMsgsRq->VendorCreditAddRq->VendorCreditAdd->VendorRef->FullName){
                    $old['company'] = rtrim(current($assoc->QBXMLMsgsRq->VendorCreditAddRq->VendorCreditAdd->VendorRef->FullName)," V");
                }

				$id = current(current($assoc->QBXMLMsgsRq->VendorCreditAddRq->attributes()));
				$old['id'] = $id;
                $old['date_created'] = current($assoc->QBXMLMsgsRq->VendorCreditAddRq->VendorCreditAdd->TxnDate);
                
                
                //Grab Meta Level Information from the system
                $query = "SELECT `companyid` as company, `date_created` FROM sales_credits WHERE `id` = $id;";
                exit($query);
                $result = qdb($query) or die(qe()." | $query");
                $row = mysqli_fetch_assoc($result);
                $row['date_created'] = format_date($row['date_created'],"Y-m-d");
                $row['company'] = trim(getCompany($row['company']));
                print_r($old);
                continue;
                //Line Item Parse
                if($assoc->QBXMLMsgsRq->VendorCreditAddRq->VendorCreditAdd->InvoiceLineAdd){
                    $old_lines = array();
                    foreach($assoc->QBXMLMsgsRq->VendorCreditAddRq->VendorCreditAdd->InvoiceLineAdd as $item){
                        // print_r($item);
                        if($item->Desc == "Freight Charge" || trim($item->Desc) == "Freight"){
                            //Add freight to old row
                            $old['freight'] += $item->Rate;
                        } else {
                            // print_r(current($item->Desc));
                            // $ds = desc_walk($desc);
                            $desc = current($item->Desc); 
                            $line_info = find_line_item_inv($desc, "invoice");
                            $partid = current($line_info);
                            if(!$partid){
                                // echo("Not an item sale: No Part Id <br>-----------<br>");
                                continue(2);
                            } else {
                    		  //  echo("<br>------------<br>".$brow['source']." - Invoice #$id<br>");
                            }
                            $qty = $item->Quantity;
                            $amount = round($item->Rate,2);
                            $key = "$qty+$partid";
                            $old_lines[$key] = $amount;
                            // echo($amount);
                        }
                    }
                    $item_query = "Select `partid`,`qty`,`price` FROM invoice_items WHERE `invoice_no` = $id;";
                    $item_results = qdb($item_query) or die(qe()." | ".$item_query);
                    foreach($item_results as $line){
                        $test = array();
                        $l_amount = trim(floor($line['price']));
                        $l_qty = trim($line['qty']);
                        $key = $l_qty."+".$line['partid'];
                        
                        $test[$key] = $l_amount;
                        if(isset($old_lines[$key])){
                            if($old_lines[$key] == $l_amount){
                                // echo("<br>Line Clear!<br>");
                            }
                            else{
                                echo("--------------<br>Invoice No $id mismatches on lines:<br>");
                                print_r($old_lines);
                                print_r($line);
                                exit;
                            }
                        } else {
                            echo("--------------<br>Invoice No $id mismatches on lines:<br>");
                            print_r($old_lines);
                            print_r($line);
                            exit;
                        }
                    }
                } else {
                    // echo($brow['source']." - Invoice Proper: No Rows<br><br>");
                }
                if($old != $row){
                    print_r($old);
                    echo("-----------<br>");
                    print_r($row);
                    echo("_____________<br>");
                    // exit;
                } else {
                    // echo("Invoice No $id Imported Correctly! <br>------------<br><br>");
                }
	
			
//==================================================================================================

                
			} else {
				print_r($assoc);
				echo(" - SOMETHING DIFFERENT <br><br>");
			}
		}
		else if($brow['source'] == "generate_bill"){
				$id = current(current($assoc->QBXMLMsgsRq->BillAddRq->attributes()));
				$old['bill_no'] = $id;
				
				// Redundant on ID??
                // $memo = (explode("#",current($assoc->QBXMLMsgsRq->BillAddRq->BillAdd->Memo)));
				// $number = trim($memo[1]);
				$list_id = (current($assoc->QBXMLMsgsRq->BillAddRq->BillAdd->VendorRef->ListID));
                if($list_id){
                    $old['company'] = db_company_name($list_id);
    				
                } else {
                    $old['company'] = rtrim(current($assoc->QBXMLMsgsRq->BillAddRq->BillAdd->VendorRef->FullName)," V");
                }
				
                $old['date_created'] = current($assoc->QBXMLMsgsRq->BillAddRq->BillAdd->TxnDate);
                $old['due_date'] = current($assoc->QBXMLMsgsRq->BillAddRq->BillAdd->DueDate);
                $old['invoice_no'] = current($assoc->QBXMLMsgsRq->BillAddRq->BillAdd->RefNumber);;
                // $old['order_type'] = $type;
                // $old['freight'] = 0.00;
                
                //Grab Meta Level Information from the system
                $query = "SELECT `bill_no`, `companyid` as company, `date_created`, `due_date`, `invoice_no` FROM bills WHERE `bill_no` = $id;";
                $result = qdb($query) or die(qe()." | $query");
                $row = mysqli_fetch_assoc($result);
                $row['date_created'] = format_date($row['date_created'],"Y-m-d");
                $row['company'] = trim(getCompany($row['company']));
                
                //Line Item Parse
                if($assoc->QBXMLMsgsRq->BillAddRq->BillAdd->ItemLineAdd){
                    $old_lines = array();
                    if(current($assoc->QBXMLMsgsRq->BillAddRq->BillAdd->ItemLineAdd->ItemRef->FullName)!="Inventory Purchase"){
                        continue;
                    }
                    foreach($assoc->QBXMLMsgsRq->BillAddRq->BillAdd->ItemLineAdd as $item){
                        // print_r($item);
                        // print_r(current($item->Desc));
                        // $ds = desc_walk($desc);
                        $desc = current($item->Desc);
                        if(!$desc){
                            print_r($line);
                        }
                        $line_info = find_line_item_inv($desc, "bill");
                        $partid = current($line_info);
                        if($part){continue;}
                        if(!$partid){
                            // echo("Not an item sale: No Part Id <br>-----------<br>");
                            // continue(2);
                        } else {
                		    //echo("<br>------------<br>".$brow['source']." - Bill #$id<br>");
                        }
                        $qty = $item->Quantity;
                        $amount = round($item->Cost,2);
                        $key = "$qty+$partid";
                        $old_lines[$key] = $amount;
                        // echo($amount);
                    }
                    
                    $item_query = "Select `partid`,`qty`,`amount` FROM bill_items WHERE `bill_no` = $id;";
                    $item_results = qdb($item_query) or die(qe()." | ".$item_query);
                    if(mysqli_num_rows($item_results)){
                        foreach($item_results as $line){
                            $test = array();
                            $l_amount = trim(floor($line['amount']));
                            $l_qty = trim($line['qty']);
                            $key = $l_qty."+".$line['partid'];
                            
                            $test[$key] = $l_amount;
                            if(isset($old_lines[$key])){
                                if($old_lines[$key] == $l_amount){
                                    echo("<br>Line Clear!<br>");
                                }
                                else{
                                    echo("--------------<br>Bill No $id mismatches on lines:<br>");
                                    print_r($old_lines);
                                    print_r($line);
                                    // exit;
                                }
                            } else {
                                echo("--------------<br>Bill No $id mismatches on lines:<br>");
                                print_r($old_lines);
                                print_r($line);
                                // exit;
                            }
                        }
                    }
                } else {
                    // echo($brow['source']." - Bill Proper: No Rows<br><br>");
                }
                if($old != $row){
                    print_r($old);
                    echo("-----------<br>");
                    print_r($row);
                    // exit;
                } else {
                    // echo("Bill No $id Imported Correctly! <br>------------<br><br>");
                }

		}
	}
    echo("</pre>");
    

?>
