<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPartId.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/setPart.php';

	//Temp array to hold Brian's data
	$vendorCreditHolder = array();
	$creditInfo = array();
	$payment_info = array();
	$payment_details = array();
	
	//For testing purposes and compile time adding LIMIT 30
	$query = "SELECT * FROM inventory_vendorcredit ORDER BY rma_id DESC LIMIT 50;";
	$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$vendorCreditHolder[] = $r;
	}
	//print_r($vendorCreditHolder);
	
	foreach ($vendorCreditHolder as $key => $value) {
		//Declare Variables
		$contact = '';
		$id;
		
		$creditParts = array();
		$creditSerials = array();
		
		//unset unused items
		unset($value['entered_by_id']);
		unset($value['bill_from']);
		unset($value['qbref']);
		unset($value['group_id']);
		unset($value['push_success']);
		unset($value['pushed']);
		unset($value['result']);
		unset($value['validation_errors']);
		unset($value['validated']);
		unset($value['postfix']);
		unset($value['sent']);
		unset($value['ref_no']);
		
		//Translate and set companyid variable
		$value['companyid'] = dbTranslate($value['vendor_id']);
		
		$value['notes'] = $value['memo'];
		
		//Convert voided into the correct statment
		$value['voided'] = ($value['voided'] ? 'Active' : 'Void');
		
		//Get the RMA number
		//$rma_no = trim(substr($value['ref_no'], strpos($value['ref_no'], "#") + 1));
		// $invoice_no = null;
		// //Strip and trim the ref_no of unneeded words in the database 'RMA TICKET #XX'
		// if(is_numeric($rma_no)) {
		// 	//Get the invoice number from the RMA Tickets Table on Brian's DB
		// 	$query = "SELECT * FROM inventory_rmaticket WHERE id = '".res($rma_no)."'; ";
		// 	$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
		// 	if (mysqli_num_rows($result)>0) {
		// 		$r = mysqli_fetch_assoc($result);
		// 		$invoice_no = $r['name'];
		// 	}
		// }
		
		$value['invoice_no'] = '';
		
		//Get the id's name from Brian's People List
		$query = "SELECT * FROM inventory_people WHERE id = '".res($value['voided_by_id'])."'; ";
		$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$contact = $r['name'];
		}
		
		$value['voided_by_id'] = ($value['voided_by_id'] ? $contact : '');
		
		//Create new value for host all of the void information
		
		$value['voided_info'] = '';
		
		if($value['voided_by_id'] || $value['voided_date'] || $value['voided_reason']) {
			$value['voided_info'] .= 'Voided ';
		}
		
		if($value['voided_by_id']) {
			$value['voided_info'] .= 'by '. $value['voided_by_id'] . ' ';
		}
		
		if($value['voided_date']) {
			$value['voided_info'] .= format_date($value['voided_date']) . ' ';
		}
		
		if($value['voided_reason']) {
			$value['voided_info'] .= ' ' . str_replace(' - see memo', '', $value['voided_reason']);
		}
		
		$value['notes'] .= ' ' . $value['voided_info'];
		
		//This piece checks if the vendor credit meta line already exists
		// $query = "SELECT * FROM purchase_credits WHERE purchase_credits_no = '".res($creditid)."';";
		// $result = qdb($query) OR die(qe().' '.$query);
		// if (mysqli_num_rows($result)>0) {
		// 	$r = mysqli_fetch_assoc($result);
		// 	$vcreditid = $r['id'];
		// }
		
		//setVendorCredit($rma_no, $date_created, $invoice_no, $companyid, $notes, $status, $order_num = 0)
		$creditid = setVendorCredit($value['rma_no'], $value['date'], $value['rma_id'], $value['companyid'], $value['notes'], $value['voided'], $id);
		
		$query = "SELECT `desc`, quantity, amount  FROM inventory_vendorcreditli WHERE vc_id = ".res($value['id'])." AND item IS NOT NULL;";
		
		$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$creditInfo[] = $r;
		}
		
		foreach($creditInfo as $item) {
			//Declare used variables
			$itemid = 0;
			$inventoryid = 0;
			$vcreditid = 0;
				
			$memo = $item['desc'];
			$qty = $item['quantity'];
			$amount = $item['amount'];
			
			//RMA ID exists on the meta level so try to find the part ID and data from RMA Ticket table from Brian's DB
			if($value['rma_id']) {
				
				//Get the item_id to be used in solditems table from the RMATICKETS using rma_id
				$query = "SELECT item_id FROM inventory_rmaticket WHERE id = '".res($value['rma_id'])."'; ";
				$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
				if (mysqli_num_rows($result)>0) {
					$r = mysqli_fetch_assoc($result);
					$itemid = $r['item_id'];
				}
				
				echo "<b>RMA Exists</b> Item id: $itemid<br>";
				
				//Get more info from the solditems table using the item_id
				$query = "SELECT inventory_id FROM inventory_solditem WHERE id = '".res($itemid)."'; ";
				$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
				if (mysqli_num_rows($result)>0) {
					$r = mysqli_fetch_assoc($result);
					$inventoryid = $r['inventory_id'];
				}
				
				echo "<b>Inventory ID Exists</b> Inventory id: $inventoryid<br>";
				
				$partid = translateID($inventoryid);
				
				echo "<b>Part ID Exists</b> Part id: $partid<br>";
				echo "<b>Purchase Credit ID</b> id: $creditid<br>";
			}
			
			//Check for Sales Credit Lines
			$query = "SELECT * FROM purchase_credit_items WHERE purchase_credit_no = '".res($creditid)."';";
			$result = qdb($query) OR die(qe().' '.$query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$vcreditid = $r['id'];
			}
			
			//if(!$vcreditid)
			//setCreditItems($purchase_id, $partid, $qty, $amount, $memo, $id = 0)
			setCreditItems($creditid, $partid, $qty, $amount, $memo, $vcreditid);
		}
		
		//Item was paid for so translate this data into the payments / payment_details database
		if($value['paid']) {
			$payment_info['companyid'] = $value['companyid'];
		    $payment_info['date'] = $value['date'];
		    $payment_info['payment_type'] = "Other";
		    $payment_info['number'] = "PO".$creditid;
		    $payment_info['amount'] = $qty * $amount; //This amount will equal the audited amount on the line items, assuming no discrepency
		    $payment_info['notes'] = "Imported";
		    $payment_details['order_number'] = $insert_row['bill_no'];
		    $payment_details['order_type'] = "Bill";
		    $payment_details['ref_number'] = "";
		    $payment_details['ref_type'] = "";
		    $payment_details['amount'] = $qty * $amount;
		}
		
		//garbage collection
		unset($value['voided_by_id']);
		unset($value['memo']);
		unset($value['voided_date']);
		unset($value['voided_info']);
		unset($value['voided_reason']);
		unset($value['vendor_id']);
		unset($value['ref_no']);
		unset($value['paid']);
		
		unset($payment_info);
		unset($payment_details);
		
		print_r($value);
		echo '<br>';
		print_r($creditSerials);
	
		echo "<br><br>";
		
	}
	
	function translateID($inventory_id){
        global $INVENTORY_IDS;
        if (!isset($INVENTORY_IDS[$inventory_id])){
            $query = "SELECT i.heci, i.clei, i.short_description, im.name manf
            FROM inventory_inventory i, inventory_manufacturer im 
            WHERE i.id = ".prep($inventory_id)." AND i.manufacturer_id_id = im.id;";
            $result = qdb($query,"PIPE") or die(qe("PIPE")." ".$query);
            $r = mysqli_fetch_assoc($result);
            $INVENTORY_IDS[$inventory_id] = part_process($r);
        }
        return($INVENTORY_IDS[$inventory_id]);
    }
    
    function part_process($r){
        //Function takes in a row from Brian's inventory table, checks to 
        //see if it exists in our system, and sets part if it doesn't
        //Requires the inclusion of both getPartID.php and setPartId.php
        if ($r['clei']) { $r['heci'] = $r['clei']; }
        else if (strlen($r['heci'])<>7 OR is_numeric($r['heci']) OR preg_match('/[^[:alnum:]]+/',$r['heci'])) { $r['heci'] = ''; }
        else { $r['heci'] .= 'VTL'; }//append fake ending to make the 7-digit a 10-digit string

        $partid = getPartId($r['part_number'],$r['heci']);
        if (! $partid) {
            $partid = setPart(array('part'=>$r['part_number'],'heci'=>$r['heci'],'manf'=>$r['manf'],'descr'=>$r['short_description']));
        }
        return $partid;
    }
	
	function splitDesc($data, $start, $end){
	    $data = ' ' . $data;
	    
	    $initial = strpos($data, $start);
	    
	    if ($initial == 0) 
	    	return '';
	    	
	    $initial += strlen($start);
	    $length = strpos($data, $end, $initial) - $initial;
	    
	    return substr($data, $initial, $length);
	}
	
	function mapContactID($contactid) {
		//Function top map Brian's contactid to ours
		$contactName = '';
		$contactEmail = '';
		$contactCompany = 0;
		
		if($contactid != '') {
			//Check for People
			$query = "SELECT * FROM inventory_people ORDER BY id ASC; ";
			$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$contactName = trim($r['name']);
				$contactEmail = trim($r['email']);
				$contactCompany = dbTranslate($r['company_id']);
			}
			
			//Check our database for ID
			$query = "SELECT * FROM contacts WHERE LOWER(name) = '".res(strtolower($contactName))."' AND companyid = ".res($contactCompany)."; ";
			$result = qdb($query) OR die(qe().' '.$query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$contactid = $r['id'];
			}
		} else {
			$contactid = null;
		}
		
		return $contactid;
	}
	
	
	function setVendorCredit($rma_no, $date_created, $rmaid = null, $companyid, $notes, $status, $id = 0) {
		$companyid = (int)$companyid;
		$order_num = (int)$order_num;
		
		$notes = (string)$notes;
		$notes = trim($notes);
		
		$rmaid = (string)$rmaid;
		$rmaid = trim($rmaid);

		$query = "REPLACE purchase_credits (rma_number, companyid, date_created, rma_no, status, notes";
		if ($id) { $query .= ", id"; }
		$query .= ") VALUES ('".res($rmaid)."',";
		$query .= " ".res($companyid)."";
		$query .= ", '".res($date_created)."'";
		$query .= ", '".res($rma_no)."'";
		$query .= ", '".res($status)."'";
		$query .= ", '".res($notes)."'";
		if ($id) { $query .= ",'".res($id)."'"; }
		$query .= "); ";
		$result = qdb($query) OR die(qe().' '.$query);
		if (!$id) {$id = qid();}
		

		return ($id);
	}
	
	function setCreditItems($purchase_id, $partid, $qty, $amount, $memo, $id = 0) {
		$cid = (int)$cid;
		$sales_item_id = (int)$sales_item_id;
		$return_item_id = (int)$return_item_id;
		$qty = (int)$qty;
		$amount = (float)$amount;

		$query = "REPLACE purchase_credit_items (purchase_credit_no, partid, qty, amount, memo";
		if ($id) { $query .= ", id"; }
		$query .= ") VALUES (".res($purchase_id).",";
		$query .= " ".res($partid)."";
		$query .= ", ".res($qty)."";
		$query .= ", '".res($amount)."'";
		$query .= ", '".res($memo)."'";
		if ($id) { $query .= ",'".res($id)."'"; }
		$query .= "); ";
		$result = qdb($query) OR die(qe().' '.$query);
		if (!$id) {$id = qid();}
		

		return ($id);
	}
?>
