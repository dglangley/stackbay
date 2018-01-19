<?php
exit;
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';

	//Temp array to hold Brian's data
	$creditHolder = array();
	$creditInfo = array();
	
	//For testing purposes and compile time adding LIMIT 30
	$query = "SELECT * FROM inventory_creditmemo ORDER BY id ASC;";
	$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$creditHolder[] = $r;
	}
	
	foreach ($creditHolder as $key => $value) {
		$so_id = 0;
		$companyid = 0;
		$oqid = 0;
		$inventoryid = 0;
		$date_created;
		$amount = 0;
		$qty = 0;
		$repid = 0;
		$contactid = 0;
		$id = 0;
		
		$creditParts = array();
		$creditSerials = array();
		
		if($value['ship_to'] == '') {
			continue;
		}
		
		echo '<b>Customer\'s PO #</b>' . $value['po_number'] . ' <b>CMID</b> = ' . $value['id'];
		
		echo '<br> <b>SO ID:</b> ';
		
		$query = "SELECT quote_ptr_id FROM inventory_salesorder WHERE po_number = '".res($value['po_number'])."';";
		
		$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$so_id = $r['quote_ptr_id'];
		}
		echo $so_id;
		
		echo '<br> <b>Company ID:</b> ';
		
		$query = "SELECT company_id, date, contact_id FROM inventory_quote WHERE id = ".res($so_id).";";
		
		$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$companyid = dbTranslate($r['company_id']);
			$date_created = format_date($r['date'], 'Y-m-d');
			$contactid = $r['contact_id'];
		}
		echo $companyid . ' <br><b>Date:</b> ' . $date_created;
		
		echo '<br> <b>Items tied to this credit memo:</b><br>';
		
		//(LATER) Create a query to log all items on Brian's database that does not have a valid serial
		$query = "SELECT * FROM inventory_creditmemoli WHERE cm_id = ".res($value['id'])." AND item IS NOT NULL;";
		
		$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$creditInfo[] = $r;
			$creditParts[] = trim(splitDesc($r['desc'], 'Part Number:', 'Serial:'));
			$creditSerials[] = trim(substr($r['desc'], strpos($r['desc'], 'Serial:') + 7));
			
			$qty = $r['quantity'];
			$amount = $r['amount'];
		}
		print_r($creditInfo);
		
		if($creditParts) {
			echo '<br> <b>Part Numbers to this credit memo:</b><br>';
			print_r($creditParts);
		}
		
		if($creditSerials) {
			echo '<br> <b>Serial Numbers to this credit memo:</b><br>';
			print_r($creditSerials);
			
			echo '<b><br>OQID:</b>';
			
			foreach($creditSerials as $serial) {
				$oqid = 0;
				
				if($serial != '000') {
					$query = "SELECT oq_id, inventory_id, rep_id FROM inventory_solditem WHERE serial = '".res($serial)."' AND so_id = ".res($so_id).";";
				
					$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
					if (mysqli_num_rows($result)>0) {
						$r = mysqli_fetch_assoc($result);
						$oqid = $r['oq_id'];
						$inventoryid = $r['inventory_id'];
						$repid = $r['rep_id'];
					}
					
					//If this fails try this bypass method
					if(!$oqid) {
						$query = "SELECT oq_id, inventory_id, rep_id FROM inventory_solditem WHERE serial = '".res($serial)."' LIMIT 1;";
				
						$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
						if (mysqli_num_rows($result)>0) {
							$r = mysqli_fetch_assoc($result);
							$oqid = $r['oq_id'];
							$inventoryid = $r['inventory_id'];
							$repid = $r['rep_id'];
						}	
					}
					
					echo $oqid . ' <br><b>Inventory ID:</b>' . $inventoryid;
				}
			}
		}
		
		$order_type;
		
		if($value['rma_id'] != '') {
			$order_type = 'Repair';
		} else {
			$order_type = 'Sales';
		}

		echo "<br><b>Other Needed Items:</b> " . $order_type . ' <b>RMA:</b>' . $value['rma_id']. ' <b>RepID:</b>' . $repid.' <b>Contact ID:</b>' . $contactid;
		
		echo '<br><br>';
		
		//Check for Sales Credit
		$query2 = "SELECT * FROM sales_credits WHERE order_num = '".res($so_id)."' AND rma = ".res($value['rma_id'])." AND companyid = ".res($companyid)."; ";
		$result2 = qdb($query2) OR die(qe().' '.$query2);
		if (mysqli_num_rows($result2)>0) {
			$r2 = mysqli_fetch_assoc($result2);
			$id = $r2['id'];
			echo '<br>ID: '.$id . '<br><br>';
		}
		
		$cid = setCredits($companyid, $date_created, $so_id, $order_type, $value['rma_id'], mapContactID($repid), mapContactID($contactid), $id);
		
		$creditid = 0;
		
		//Check for Sales Credit Lines
		$query = "SELECT * FROM sales_credit_items WHERE cid = '".res($cid)."';";
		$result = qdb($query) OR die(qe().' '.$query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$creditid = $r['id'];
		}
		
		setCreditItems($cid, $so_id, $value['rma_id'], $qty, $amount, $creditid);
		
		unset($creditParts); 
		unset($creditSerials); 
		unset($creditInfo); 
		
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
	
	
	function setCredits($companyid, $date_created, $order_num, $order_type, $rma, $repid, $contactid, $id = 0) {
		$companyid = (int)$companyid;
		$date_created = $date_created;
		$order_num = (int)$order_num;
		
		$order_type = (string)$order_type;
		$order_type = trim($order_type);
		
		$rma = (int)$rma;
		$repid = (int)$repid;
		$contactid = (int)$contactid;

		$query = "REPLACE sales_credits (companyid, date_created, order_num, order_type, rma, repid, contactid";
		if ($id) { $query .= ", id"; }
		$query .= ") VALUES ('".res($companyid)."',";
		$query .= " '".res($date_created)."'";
		$query .= ", ".res($order_num)."";
		$query .= ", '".res($order_type)."'";
		$query .= ", ".res($rma)."";
		$query .= ", ".res($repid)."";
		$query .= ", ".res($contactid)."";
		if ($id) { $query .= ",'".res($id)."'"; }
		$query .= "); ";
		$result = qdb($query) OR die(qe().' '.$query);
		if (!$id) {$id = qid();}
		

		return ($id);
	}
	
	function setCreditItems($cid, $sales_item_id, $return_item_id, $qty, $amount, $id = 0) {
		$cid = (int)$cid;
		$sales_item_id = (int)$sales_item_id;
		$return_item_id = (int)$return_item_id;
		$qty = (int)$qty;
		$amount = (float)$amount;

		$query = "REPLACE sales_credit_items (cid, sales_item_id, return_item_id, qty, amount";
		if ($id) { $query .= ", id"; }
		$query .= ") VALUES (".res($cid).",";
		$query .= " ".res($sales_item_id)."";
		$query .= ", ".res($return_item_id)."";
		$query .= ", ".res($qty)."";
		$query .= ", '".res($amount)."'";
		if ($id) { $query .= ",'".res($id)."'"; }
		$query .= "); ";
		$result = qdb($query) OR die(qe().' '.$query);
		if (!$id) {$id = qid();}
		

		return ($id);
	}
?>
