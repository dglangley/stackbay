<?php
	$rootdir = $_SERVER["ROOT_DIR"];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getCondition.php';
	include_once $rootdir.'/inc/getFreight.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/getPartId.php';
	include_once $rootdir.'/inc/setPart.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/getPipeIds.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/filter.php';
    include_once $rootdir.'/inc/import_aid.php';
    include_once $rootdir.'/inc/getUser.php';
    include_once $rootdir.'/inc/setRMA.php';
    include_once $rootdir.'/inc/importActivities.php';
	
    // qdb("TRUNCATE repair_orders;");
    // qdb("TRUNCATE repair_items;");
    // qdb("TRUNCATE TABLE `repair_activities`;");

	$debug = 0;

	$STATUSES = array(
		3 => 'In Queue',
		6 => 'Inbound',
		11 => 'In Shipping',
		12 => 'Closed',
		13 => 'Out For Repair',
	);

	$locationid = 67;//id of REPAIR location
	$INSERTED = 0;

	$meta = array('ro_number'=>'','private_notes'=>'');
//	$query .= "/*AND created_at > '2014-12-31'*/ ";
//	$query .= "/*AND serials NOT IN ('02CYHL6Q','NNTM40108508','MC00AU006P','ALCLAAU37346', 'XTV2.44','X501','17-H60542', '92MV07272904', 'FE4107002009', 'be0905011264', 'BE4904001250', 'FE4606004036','g29444','i21775','05VR17000001','G51565','AJP1600')*/ ";
//	$query .= "/*AND company_id NOT IN ('1275')*/ ";
	/*
	AND ticket_number not in (
	  SELECT `ticket_number`
	  FROM `inventory_repair` r, `inventory_rmaticket` rt, inventory_solditem isi
	  WHERE r.ticket_number = rt.repair_id
	  and rt.item_id = isi.id 
	  AND created_at > '2014-11-10 12:58:46'
	  AND serials NOT IN ('02CYHL6Q','NNTM40108508','MC00AU006P','ALCLAAU37346', 'XTV2.44','X501','17-H60542')
	)
	*/
	$query = "SELECT * FROM `inventory_repair` ";
	$query .= "WHERE inventory_id is not null/*test record*/ ";
//	$query .= "AND purchase_order not like 'RMA%' ";
	$query .= "ORDER BY ticket_number ASC; ";// LIMIT 1000,100; ";
	$results = qdb($query, "PIPE") or die(qe("PIPE")." $query");
	echo($query."<br><br>");
	foreach($results as $r){
	    $ro_number = $r['ticket_number'];
		$meta['ro_number'] = $ro_number;
		$companyid = dbTranslate($r['company_id']);
		$creator_id = prep(mapUser($r['tech_id']),16);
    
		$sales_rep_id = mapUser($r['sales_rep_id']);
		$freight_service = 'NULL';
		$freight_carrier = 'NULL';
		if ($r['carrier_id']) {
			$freight_service = prep($SERVICE_MAPS[$r['carrier_id']]);
			$freight_carrier = prep($CARRIER_MAPS[$r['carrier_id']]);
		}

		$meta['public_notes'] = trim(substr($r['ship_to'].' '.$r['ext_notes'],0,255));
		$meta['private_notes'] = trim(substr(str_replace($r['purchase_order'],'',trim($r['notes'])),0,255));
		$partid = translateID($r['inventory_id']);
		$pserial = trim(strtoupper($r['serials']));
		$invid = 0;
		$rma = 0;
		$return_item_id = 0;
    
		$line = array();
		$line['ref_1'] = null;
		$line['ref_1_label'] = null;
		$line['ref_2'] = null;
		$line['ref_2_label'] = null;
		if($r['material_sap']){
			//material_sap -> some verizon label REF_LABEL1 SAP, Re11val
			$line['ref_1'] = $r['material_sap'];
			$line['ref_1_label'] = 'SAP';
		}
		if($r['verizon_ref']){
			$line['ref_2'] = $r['verizon_ref'];
			$line['ref_2_label'] = 'Verizon Ref';
		}

		// separate handling of RMA repairs, including a diff terms id; note that RMA results are
		// known, existing-inventory units, as opposed to billable repairs that should be customer-owned,
		// non-inventory units. so this process will be outlined differently, EXCEPT on warranty claims (RMAs)
		// on originally-billed repairs
		if (substr(trim($r['purchase_order']),0,3)=='RMA') {
			$termsid = 15;// N/A
	        $rma = preg_replace('/^(RMA[[:space:]]*[#][[:space:]]*)([0-9]{4,6})$/','$2',trim($r['purchase_order']));

			/***** TWO EXCEPTIONS NEED PARTIDS UPDATED AS RM'S *****
			328487 -> RMA #14428... 256993 partid, NNTMEN00UZD1 serial: SELECT * FROM inventory WHERE serial_no = 'NNTMEN00UZD1' AND partid = '256993';
			328499 -> RMA #14479... 27574 partid, 90DJ03212596 serial: SELECT * FROM inventory WHERE serial_no = '90DJ03212596' AND partid = '27574'; 
			******                                             *****/

			// unit was NOT in stock previously, even though this is an RMA for a pre-existing SO/RO...
			$query2 = "SELECT id FROM inventory WHERE serial_no = '".res($pserial)."' AND partid = '".res($partid)."'; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			if (mysqli_num_rows($result2)==0) {
				//update exception partids here
				if ($ro_number==328487) {
					$query3 = "UPDATE inventory SET partid = 256993 WHERE serial_no = 'NNTMEN00UZD1' AND partid = 241369; ";
//					$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
					echo $query3.'<BR>';
				} else if ($ro_number==328499) {
					$query3 = "UPDATE inventory SET partid = 27574 WHERE serial_no = '90DJ03212596' AND partid = 61921; ";
//					$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
					echo $query3.'<BR>';
				} else {
					// these last 5 cases (328545,328655,328679,328680 & 328698) are all billable repairs that got returned...no previous stock,
					// so in reality we shouldn't have this case met since previously-iterated repairs should already have entered these serials
//for now in dev:
echo (++$INSERTED).'. '.$ro_number.' intentionally left out<BR><BR>';
continue;
				}

				// for now during dev, use orig partid's record
				$query2 = "SELECT id FROM inventory WHERE serial_no = '".res($pserial)."'; ";
				$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			}
			$r2 = mysqli_fetch_assoc($result2);
			$invid = $r2['id'];

			/***** SCENARIOS *****
				327181, 327797 = Canceled
				327533 = Outsourced 3rd party repair via S&R
				328660 - 328667 = RMA's created in new system, so no RMA records exist in query below
			******           *****/

			$order_number = 0;
			$order_type = '';
			$query3 = "SELECT si.so_id, si.repair_id, rma.reason, rma.action_id, rma.master_id ";
			$query3 .= "FROM inventory_rmaticket rma LEFT JOIN inventory_solditem si ON rma.item_id = si.id ";
			$query3 .= "WHERE rma.repair_id = '".res($ro_number)."'; ";
			$result3 = qdb($query3,'PIPE') OR die(qe('PIPE').'<BR>'.$query3);
			if (mysqli_num_rows($result3)==0) {
//echo $query3.'<BR>';
				if ($r2['status_id']==12 AND $r3['shipped_status_id']==8) {//Closed and "Canceled - Not Received" = "Void"
				} else if ($ro_number==327533) {
				} else {/*328660-328667*/
				}
			}
			$r3 = mysqli_fetch_assoc($result3);
			$master_rma = $r3['master_id'];
			if ($r3['so_id']) {
				$order_number = $r3['so_id'];
				$order_type = 'Sale';
			} else {
				$order_number = $r3['repair_id'];
				$order_type = 'Repair';
			}

			$query4 = "SELECT rma_number FROM returns WHERE rma_number = '".res($master_rma)."'; ";
			$result4 = qdb($query4) OR die(qe().'<BR>'.$query4);
			if (mysqli_num_rows($result4)==0) {
				$rma_status = 'Active';
				$dispositionid = $DISPOSITIONS[$r3['action_id']];

				// Need to create RMA but without overlapping on existing RMA's. Possible? Anything is possible.
				$master_rma = setRMA($master_rma,$r['created_at'],$creator_id,$companyid,$order_number,$order_type,'',$rma_status);
				$return_item_id = setRMAitem($master_rma,$partid,$invid,$dispositionid,substr($r3['reason'],0,255),1);
			} else {
				$query4 = "SELECT id FROM return_items WHERE rma_number = '".$master_rma."' AND inventoryid = '".res($invid)."'; ";
				$result4 = qdb($query4) OR die(qe().'<BR>'.$query4);
				$r4 = mysqli_fetch_assoc($result4);
				$return_item_id = $r4['id'];
			}

			if (! $line['ref_1']) {
				$line['ref_1'] = $return_item_id;
				$line['ref_1_label'] = 'return_item_id';
			} else {
				$line['ref_2'] = $return_item_id;
				$line['ref_2_label'] = 'return_item_id';
			}
		} else {
			$termsid = prep($TERMS_MAPS[$r['terms_id']]);
		}
		$line['warranty'] = $WARRANTY_MAPS[$r['warranty_id']]; //warranty_id

		//status_id - Connects to the 'inventory_status' table (Aaron's prev note was: 'repairshippeddtatus' table, correlates to our condition table. will need to be mapped to conditions, most likely.)
		if($r['status_id'] == "11" || $r['status_id'] == "12"){//In Shipping or Closed
			$status = 'Completed';
			$item_status = "manifest";
			$qty = 0;
			$conditionid = 5;//repaired (untested)
		} else {//3,6,13 are all statuses used for Active
			$status = 'Active';
			$item_status = "in repair";
			$qty = 1;
			$conditionid = -5;//needs repair
		}

		//Note the fact that we don't actually have an inventory record yet
		$query2 = "REPLACE `repair_orders`(`ro_number`, `created`, `created_by`, `sales_rep_id`, `companyid`,
				`cust_ref`, `ship_to_id`, `freight_carrier_id`, `freight_services_id`,
				`termsid`, `public_notes`, `private_notes`, `status`,`repair_code_id`)
				VALUES ($ro_number, ".prep($r['created_at']).", ".$creator_id.", ".prep($sales_rep_id).", ".prep($companyid).",
				".prep(trim($r['purchase_order'])).", ".prep(address_translate($r['ship_to'])).", $freight_carrier, $freight_service,
				$termsid, ".prep($meta['public_notes']).", ".prep($meta['private_notes']).", ".prep($status).", ".prep($r['shipped_status_id']).");
		";
		if (! $debug) { qdb($query2) or die(qe(). " | $query2"); }
		echo (++$INSERTED).'. '.$query2.'<br>';

		// leave invid NULL for now until we get the inventory record below
		$query2 = "REPLACE `repair_items`(`partid`,`ro_number`,`line_number`,`qty`,`price`,
				`due_date`,
				`invid`,`ref_1`,`ref_1_label`,`ref_2`,`ref_2_label`,
				`notes`, `warrantyid`)
				VALUES (
				".prep($partid).",$ro_number,NULL,1,".prep($r['price_per_unit']).",
				".prep(format_date($r['date_due'],"Y-m-d"), "'".format_date($r['created_at'],"Y-m-d",array("d"=>30))."'").",
				NULL,".prep($line['ref_1']).",".prep($line['ref_1_label']).",".prep($line['ref_2']).",".prep($line['ref_2_label']).",
				NULL,".prep($line['warranty']).");
		";
		if (! $debug) { qdb($query2) or die(qe()." | $query2"); }
		echo($query2."<br>");
		if ($debug) { $repair_item_id = 88888; } else { $repair_item_id = qid(); }

		// check for existing inventory record of this item if we didn't already under RMA section above
		if (! $invid) {
			$inv_check = "SELECT * FROM `inventory` where serial_no like '".res($pserial)."' AND partid = '$partid';";
			$result2 = qdb($inv_check) or die(qe()." | $inv_check");
			if(mysqli_num_rows($result2)){
				$r2 =  mysqli_fetch_assoc($result2);
				$invid = $r2['id'];
			}
		}

		if ($invid) {
			// was there a repair item id already against the existing inventory record?
			if ($r['repair_item_id']) { $chg_from = "= '".$r['repair_item_id']."'"; }
			else { $chg_from = 'IS NULL'; }

			// update with new repair item id
			$inv_update = "UPDATE inventory SET `notes` = ".prep("REPAIR IMPORT").", `repair_item_id` = ".prep($repair_item_id)." WHERE id = '$invid';";
			if (! $debug) { qdb($inv_update) or die(qe()." | $inv_update"); }
			echo($inv_update."<br>");

			//Inventory_history Correction
			$inv_history = "UPDATE inventory_history SET date_changed = ".prep($r['created_at'])." WHERE invid = $invid and field_changed = 'repair_item_id' AND changed_from $chg_from;";
			if (! $debug) { qdb($inv_history) or die(qe().'<BR>'.$inv_history); }
			echo($inv_history."<br>");
		} else {
			// Inventory_insert
			$inv_insert = "INSERT INTO `inventory`(`serial_no`,`qty`, `partid`, `conditionid`,`status`,
					`locationid`,`userid`,`date_created`,`notes`)
					VALUES ('".res($pserial)."',$qty,".prep($partid).",".$conditionid.",".prep($item_status)."
					,".$locationid.",".$creator_id.",".prep($r['created_at']).",".prep("REPAIR IMPORT").")";
			if (! $debug) { qdb($inv_insert) or die(qe()." | $inv_insert"); }
			echo($inv_insert."<br>");
			if ($debug) { $invid = 99999; } else { $invid = qid(); }

			//Inventory_history Correction
			$inv_history = "UPDATE inventory_history SET date_changed = ".prep($r['created_at'])." WHERE invid = $invid and (field_changed = 'new' or field_changed = 'repair_item_id');";
			if (! $debug) { qdb($inv_history) or die(qe().'<BR>'.$inv_history);}
			echo($inv_history."<br>");
		}
    

		//Invid addition to the repair items
		$inv_update = "UPDATE `repair_items` SET invid = ".prep($invid)." WHERE id = '$repair_item_id';";
		if (! $debug) { qdb($inv_update) or die(qe()." | $inv_update"); }
		echo($inv_update."<br>");

		//If there is freight
		$query2 = "SELECT id FROM packages WHERE order_number = $ro_number AND order_type = 'Repair' AND package_no = 1; ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		if (mysqli_num_rows($result2)>0) {
			$r2 = mysqli_fetch_assoc($result2);
			$packageid = $r2['id'];
		} else {
			$package_insert = "INSERT INTO `packages`( `order_type`, `order_number`, `package_no`, `tracking_no`, `datetime`, `freight_amount`) 
				VALUES ('Repair', $ro_number, 1, ".prep($r['tracking_no']).", ".prep($r['date_out']).", ".prep($r['freight_cost']).");";
			if (! $debug) { qdb($package_insert) or die(qe()." | $package_insert"); }
			echo($package_insert."<br>");
			if ($debug) { $packageid = 77777; } else { $packageid = qid(); }
		}
  
		$query2 = "SELECT * FROM package_contents WHERE packageid = $packageid AND serialid = $invid; ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		if (mysqli_num_rows($result2)==0) {
			$pc_insert = "INSERT INTO package_contents (`serialid`, `packageid`) values($invid, $packageid);";
			if (! $debug) { qdb($pc_insert) or die(qe()." | $pc_insert"); }
			echo($pc_insert."<br>");
		}

		// update any applicable inventory_costs_log from repair_id to repair_item_id
		$query2 = "SELECT * FROM inventory_costs_log WHERE eventid = $ro_number AND event_type = 'repair_id'; ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		if (mysqli_num_rows($result2)>0) {
			$query2 = "UPDATE inventory_costs_log SET eventid = '".res($repair_item_id)."', event_type = 'repair_item_id' ";
			$query2 .= "WHERE eventid = $ro_number AND event_type = 'repair_id'; ";
			if (! $debug) { $result2 = qdb($query2) OR die(qe().'<BR>'.$query2); }
echo $query2.'<BR>';
		}

		importActivities($ro_number,$repair_item_id,$invid);

		//If the item has been re-shipped back out, all of it's line information is the same
		// $sales = $line;
		// $sales['ship_date'] = $r['date_out'];
		//date_out dateshiipped
    
		//QUOTES innfo

		echo("<br><br>");
	}
	// echo("</table>");
exit;


////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
//Repair activities import
    $activities_select = "SELECT `ticket_number`,`date_in`,`date_out`, `assigned_in`, `created_at`, `notes` from inventory_repair WHERE `ticket_number` is not NULL;";
    $results = qdb($activities_select, "PIPE") or die(qe("PIPE").$activities_select);
    foreach($results as $r){
        $ro_number = $r['ticket_number'];
        //Get Line Item Number
        $ln_query = "SELECT `id` FROM `repair_items` WHERE `ro_number` = '$ro_number';";
        $ln = qdb($ln_query) or die(qe()." | $ln_query");
        $ln = mysqli_fetch_assoc($ln);
        $repair_item_id = $ln['id'];
        $tech_id = 16;
        // if ($r['date_in']){
        //     $item_received = "";
        //     if(format_date($r['date_in'],"y-m-d") == format_date($r['created_at'],"y-m-d")){
        //         $item_received = format_date($r['created_at'], "Y-m-d G:i:s", array("h"=>1));
        //     } else {
        //         $item_received = format_date($r['date_in']." 12:00:00", "Y-m-d G:i:s");
        //     }
        //     $insert = "INSERT INTO `repair_activities`(`ro_number`, `repair_item_id`, `datetime`, `techid`, `notes`) 
        //             VALUES (".prep($ro_number).",".prep($repair_item_id).", ".prep($item_received).", $tech_id, 'Checked In');";
        //             qdb($insert) or die(qe()." | $insert");
        //             echo($insert."<br>");
        // }
        if ($r['date_out']){
            $insert = "INSERT INTO `repair_activities`(`ro_number`, `repair_item_id`, `datetime`, `techid`, `notes`) 
                    VALUES (".prep($ro_number).",".prep($repair_item_id).", ".prep(format_date($r['date_out']." 17:00:00","Y-m-d G:i:s")).", $tech_id, 'Ticket Closed');";
                    qdb($insert) or die(qe()." | $insert");
                    echo($insert."<br>");
        }
        
        //THIS IS NOT A STABLE WAY TO BUILD THIS BUT WILL MAKE THE WORLD SLIGHTLY BETTER? TALK TO DAVID;
        if ($r['assigned_in']){
            $insert = "INSERT INTO `repair_activities`(`ro_number`, `repair_item_id`, `datetime`, `techid`, `notes`) 
                    VALUES (".prep($ro_number).",".prep($repair_item_id).", ".prep(format_date($r['assigned_in']." 17:00:00","Y-m-d G:i:s")).", $tech_id, 'Claimed Ticket');";
                    qdb($insert) or die(qe()." | $insert");
                    echo($insert."<br>");
        } else if($r['date_in']) {
            $insert = "INSERT INTO `repair_activities`(`ro_number`, `repair_item_id`, `datetime`, `techid`, `notes`) 
                    VALUES (".prep($ro_number).",".prep($repair_item_id).", ".prep(format_date($r['date_in']." 17:00:00","Y-m-d G:i:s")).", $tech_id, 'Claimed Ticket');";
                    qdb($insert) or die(qe()." | $insert");
                    echo($insert."<br>");
        } else {
            $insert = "INSERT INTO `repair_activities`(`ro_number`, `repair_item_id`, `datetime`, `techid`, `notes`) 
                    VALUES (".prep($ro_number).",".prep($repair_item_id).", ".prep(format_date($r['created'],"Y-m-d G:i:s")).", $tech_id, 'Claimed Ticket');";
                    qdb($insert) or die(qe()." | $insert");
                    echo($insert."<br>");
        }
        $n = $r['notes'];
        $rma = explode(" ",$r['notes']);
        $notes ='';
        if($rma[0] != "RMA" && strlen($n) > 3){
            $notes = $n;
        }

        if ($notes){
            if ($r['assigned_in']){
                $insert = "INSERT INTO `repair_activities`(`ro_number`, `repair_item_id`, `datetime`, `techid`, `notes`) 
                        VALUES (".prep($ro_number).",".prep($repair_item_id).", ".prep(format_date($r['assigned_in']." 17:00:00","Y-m-d G:i:s")).", $tech_id, ".prep(substr($notes,0,255)).");";
                        qdb($insert) or die(qe()." | $insert");
                        echo($insert."<br>");
            } else if($r['date_in']) {
                $insert = "INSERT INTO `repair_activities`(`ro_number`, `repair_item_id`, `datetime`, `techid`, `notes`) 
                        VALUES (".prep($ro_number).",".prep($repair_item_id).", ".prep(format_date($r['date_in']." 17:00:00","Y-m-d G:i:s")).", $tech_id, ".prep(substr($notes,0,255)).");";
                        qdb($insert) or die(qe()." | $insert");
                        echo($insert."<br>");
            } else {
                $insert = "INSERT INTO `repair_activities`(`ro_number`, `repair_item_id`, `datetime`, `techid`, `notes`) 
                        VALUES (".prep($ro_number).",".prep($repair_item_id).", ".prep(format_date($r['created'],"Y-m-d G:i:s")).", $tech_id, ".prep(substr($notes,0,255)).");";
                        qdb($insert) or die(qe()." | $insert");
                        echo($insert."<br>");
            }
        }
        echo("<br><br>");
        
    }


$rtest = "SELECT * FROM `inventory_repairtest`";
$rt_result = qdb($rtest,"PIPE") or die(qe("PIPE")." | $rtest");
foreach($rt_result as $r){
    $ro_number = $r['repair_id'];
    //Get Line Item Number
    $ln_query = "SELECT `id` FROM `repair_items` WHERE `ro_number` = '$ro_number';";
    $ln = qdb($ln_query) or die(qe()." | $ln_query");
    $ln = mysqli_fetch_assoc($ln);
    $repair_item_id = $ln['id'];
    $tech_id = 16;
    if ($r['datetime_in']){
        $tech_id = prep($r['checkin_by_id'],16);
        $insert = "INSERT INTO `repair_activities`(`ro_number`, `repair_item_id`, `datetime`, `techid`, `notes`) 
                VALUES (".prep($ro_number).",".prep($repair_item_id).", ".prep(format_date($r['datetime_in'],"Y-m-d G:i:s")).", $tech_id, 'Testing Complete');";
                qdb($insert) or die(qe()." | $insert");
                echo($insert."<br>");
    }
    if ($r['datetime_out'] && !$r['datetime_in']){
        $tech_id = prep($r['checkout_by_id'],16);
        $insert = "INSERT INTO `repair_activities`(`ro_number`, `repair_item_id`, `datetime`, `techid`, `notes`) 
                VALUES (".prep($ro_number).",".prep($repair_item_id).", ".prep(format_date($r['datetime_out'],"Y-m-d G:i:s")).", $tech_id, ".prep(substr($r['notes'],0,255),"'In Test Lab'").");";
                qdb($insert) or die(qe()." | $insert");
                echo($insert."<br>");
    }
    if($r['notes']){
        $tech_id = prep($r['tech_id'],16);
        $insert = "INSERT INTO `repair_activities`(`ro_number`, `repair_item_id`, `datetime`, `techid`, `notes`) 
                VALUES (".prep($ro_number).",".prep($repair_item_id).", ".prep(format_date($r['datetime_out'],"Y-m-d G:i:s")).", $tech_id, ".prep(substr($r['notes'],0,255)).");";
                qdb($insert) or die(qe()." | $insert");
                echo($insert."<br>");
        $r['timestamp'];
    }
}

$rcheckout = "SELECT * FROM inventory_repaircheckout;";
$rc_result = qdb($rcheckout,"PIPE") or die(qe("PIPE")." | $rcheckout");
foreach ($rc_result as $r) {
    $ro_number = $r['repair_id'];
    //Get Line Item Number
    $ln_query = "SELECT `id` FROM `repair_items` WHERE `ro_number` = '$ro_number';";
    $ln = qdb($ln_query) or die(qe()." | $ln_query");
    $ln = mysqli_fetch_assoc($ln);
    $repair_item_id = $ln['id'];
    $tech_id = prep($r['tech_id'],16);
    if ($r['datetime_in']){
        $insert = "INSERT INTO `repair_activities`(`ro_number`, `repair_item_id`, `datetime`, `techid`, `notes`) 
                VALUES (".prep($ro_number).",".prep($repair_item_id).", ".prep(format_date($r['datetime_in'],"Y-m-d G:i:s")).", $tech_id, ".prep(substr($r['notes'],0,255),"'Ready For Testing'").");";
                qdb($insert) or die(qe()." | $insert");
                echo($insert."<br>");
    }
    if ($r['datetime_out']){
        $insert = "INSERT INTO `repair_activities`(`ro_number`, `repair_item_id`, `datetime`, `techid`, `notes`) 
                VALUES (".prep($ro_number).",".prep($repair_item_id).", ".prep(format_date($r['datetime_out'],"Y-m-d G:i:s")).", $tech_id, ".prep(substr($r['notes'],0,255),"'In Repair Lab'").");";
                qdb($insert) or die(qe()." | $insert");
                echo($insert."<br>");
    }
}

/*
`328667`, `328666`, `328665`, `328664`, `328663`, `328662`, `328661`, `328660`, `328655`, 
`328646`, `328633`, `328624`, `328612`, `328611`, `328610`, `328559`, `328545`, `328544`, 
`328523`, `328490`, `328411`, `328388`, `328387`, `328384`, `328261`, `328246`, `328233`,
`328140`, `328139`, `328138`, `328137`, `328129`, `328061`, `328019`, `327797`, `327576`,
`327575`, `327574`, `327533`, `327513`, `327512`, `327510`, `327509`, `327336`, `327181`,
`327127`, `326977`, `326881`, `326876`, `326785`, `326758`, `326722`, `326719`

Screen          =>  database    =>  Ours
----------------------------------------
Item Received   =>  date_in     =>  



*/
?>

