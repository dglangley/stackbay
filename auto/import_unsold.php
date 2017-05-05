<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/import_aid.php';

	//Temp array to hold Brian's data
	$items = array();
	$bogus = array();

	$bogus_serial = 0;
	
	//Find the largest Bogus Serial
	$query = "SELECT MAX(serial_no) sn FROM inventory WHERE serial_no LIKE 'VTL%'; ";// ORDER BY id DESC; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	$r = mysqli_fetch_assoc($result);
	$bogus_serial = (int)str_replace("VTL","",$r['sn']);
/*
		$bogus[] = $r['serial_no'];
	}

	foreach($bogus as $serial) {
		$new_serial = (int)str_replace("VTL", "", $serial);

		if($bogus_serial < $new_serial) {
			$bogus_serial = $new_serial;
			$bogus_serial++;
		}
	}
*/

	function setSerial($serial,$order_type='',$order=0,$id=0) {
		global $bogus_serial,$SERIALS;

		// create new order of serials if '0','000' because Brian re-used this generic serial not just in
		// one or two duplicate times, but EVERY time, resulting in duplicate nonsense garbage that
		// we can't have in the new system
		if ($serial=='0' OR $serial=='000') {
			if ($order_type AND isset($SERIALS[$order_type][$order]) AND $id AND isset($SERIALS[$order_type][$order][$id])) {
				echo 'Re-using serial '.$serial.' for '.$order_type.' order# '.$order.', item id '.$id.' = ';
								$serial = $SERIALS[$order_type][$order][$id];
				echo '"'.$serial.'"<BR>';
			} else {
				$serial = 'VTL'.($bogus_serial++);
				if ($order_type AND $id) {
					if (! isset($SERIALS[$order_type])) { $SERIALS[$order_type] = array(); }
					if (! isset($SERIALS[$order_type][$order])) { $SERIALS[$order_type][$order] = array(); }
					$SERIALS[$order_type][$order][$id] = $serial;
				}
			}
		}
		return ($serial);
	}
	
	//For testing purposes and compile time adding LIMIT 30
//	$query = "SELECT * FROM inventory_itemlocation ORDER BY id ASC; ";
	$query = "SELECT i.id, cost, serial, location_id, freight_cost, orig_cost, part_number,
   short_description, heci, clei, modified_date as date, inventory_id /*,
   count(*) groupcount, sum(quantity_stock) tqs, count(il.id) tcount*/
   FROM inventory_itemlocation il, inventory_inventory i
   where il.inventory_id = i.id and (serial = '000' or serial = '0')
   /*GROUP BY inventory_id*/ order by cost desc;";
echo $query.'<BR><BR>';
	$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$items[] = $r;
	}


	echo 'Starting Serial for Bogus: ' . $bogus_serial . '<br><br>';
	//print_r($bogus);

	$i = 0;
	foreach ($items as $key => $value) {
		$inventoryid = 0;
		$poid = '';
		$date_created = '';

		// if($serial = '000') {
		$serial =  setSerial($value['serial']);
		//echo $serial . '<br>';

/* dgl 5-4-17 for 000 fix
		//Check to see if the serial already exists
		$query = "SELECT id FROM inventory WHERE serial_no = ".prep($serial).";";
		$result = qdb($query) OR die(qe().' '.$query);
		if (mysqli_num_rows($result)>0) {
			//$r = mysqli_fetch_assoc($result);
			echo 'Serial above already exists';
			echo '<br><br>';
			continue;
		}
*/

		//Map location id here
		$locationid = $value['location_id'];//mapLocationID($value['location_id']);

		//Map Inventoryid here (serialid)
		//Get the partnumber for use in getPartId
/*
		$query = "SELECT part_number, clei, heci, modified_date FROM inventory_inventory WHERE id = ".prep($value['inventory_id']).";";
		$result = qdb($query, 'PIPE') OR die(qe('PIPE').' '.$query);
		if (mysqli_num_rows($result)==0) {
			die("What happened here?<BR>".$query);
		}
		$r = mysqli_fetch_assoc($result);
		$date_created = $r['modified_date'];
*/
		$date_created = $value['date'];

		if ($value['clei']) { $value['heci'] = $value['clei']; }
		else if (strlen($value['heci'])<>7 OR is_numeric($value['heci']) OR preg_match('/[^[:alnum:]]+/',$value['heci'])) { $value['heci'] = ''; }
		else { $value['heci'] .= 'VTL'; }//append fake ending to make the 7-digit a 10-digit string

		$partid = getPartId($value['part_number']);//,$value['heci']);
		if ($partid) { continue; }

		$partid = getPartId($value['part_number'],$value['heci']);
		if (! $partid) {
			$partid = setPart(array('part'=>$value['part_number'],'heci'=>$value['heci']));//,'manf'=>$value['manf'],'descr'=>$value['description']));
		}
		echo 'Translated Partid: ' .$partid. '<br>';

		//Check for a valid po_id here
		if($value['po_id']) {
			$query2 = "SELECT id FROM purchase_items WHERE partid = '".$partid."' AND po_number = '".$value['po_id']."'; ";
			$result2 = qdb($query2) OR die(qe().' '.$query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$poid = $r2['id'];
			}
		} 

		$status = prep('shelved');
		$qty = prep('1');
		$conditionid = prep('2');

		//$inventoryid = setInventory($serial,$partid,$item_id,$id_field,$status,$stock_date,$qty=false);
		$rmaid = '';

		$i++;
echo 'Brian: '.$value['serial'].' serial, '.$value['inventory_id'].' inventory_id, '.$value['location_id'].' locid, '.$value['po_id'].' po_id, '.$value['date'].'<BR>';
echo 'Stackbay: '.$serial.' serial, '.$partid.' partid, '.$locationid.' locationid, '.$poid.' po id, '.$rmaid.' rmaid, '.$date_created.'<BR><BR>';
continue;
		$inventoryid = insertSerialItem($serial, $partid, $locationid, $poid, $rmaid, $date_created);
		echo 'Added Serial ID: ' . $inventoryid . '<br>';

		//Run stuff for the inventory_cost log
		if(insertTableCost($inventoryid, $date_created, $value['cost'])) {
			echo 'Cost Table Successfully Updated<br>';
		} else {
			echo 'Cost Table Failed to Update<br>';
		}

		//consignment data holder (if exist then go to the next item on the list)
		$consignment = array();

		//Run the code here to insert a row into the consignment table
		if($value['ci_id']) {
			//Get consignment in Brian's system both the order and the item
			$query = "SELECT creator_id as rep_id, price, o.company_id, order_id, o.date, percentage as pct, exp_date, memo FROM inventory_consignmentitem i, inventory_consignmentorder o WHERE i.id = ".prep($value['ci_id'])." AND o.id = i.order_id;";
			$result = qdb($query,'PIPE') OR die(qe('PIPE').' '.$query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$consignment = $r;
			}
			//print_r($consignment);
			//foreach($consignment as $con) {
			$rep_id = mapContactID($consignment['rep_id']);
			echo '<b>Consignment Repid: </b>' .$rep_id. '<br>';

				//insertTableConsignment($inventoryid, $repid, $price, $companyid, $order_id, $date, $pct, $exp_date, $memo)
				if(insertTableConsignment($inventoryid, $rep_id, $consignment['price'], dbTranslate($consignment['company_id']), $consignment['order_id'], $consignment['date'], $consignment['pct'], $consignment['exp_date'], $consignment['memo'])){
					echo 'Consignment Added Successfully<br>';
				} else {
					echo 'Consignment Failed<br>';
				}
			//}

			//print_r($consignment);
		}

		echo '<br><br>';
	}
echo $i.' items imported<BR>';

// 	function setInventory($ser,$partid,$item_id,$id_field,$status,$stock_date,$qty=false) {
// 		$status = prep($status);

// 		// check new inventory system for existing serial, and if already added during PO's above,
// 		// just set `sales_item_id` on the existing record
// 		$query3 = "SELECT id FROM inventory WHERE serial_no = $ser AND partid = $partid; ";
// 		$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
// 		if (mysqli_num_rows($result3)>0) {
// 			$r3 = mysqli_fetch_assoc($result3);
// 			$serialid = $r3['id'];

// 			//$query3 = "UPDATE inventory SET sales_item_id = $so_item_id, qty = 0 WHERE id = $serialid; ";
// 			$query3 = "UPDATE inventory SET $id_field = $item_id, status = $status ";
// 			if ($qty!==false) { $query3 .= ", qty = $qty "; }
// 			$query3 .= "WHERE id = $serialid; ";
// 			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
// echo $query3.'<BR>'.chr(10);

// 			$query3 = "UPDATE inventory_history SET date_changed = '".$stock_date."' ";
// 			$query3 .= "WHERE invid = $serialid AND field_changed = '".$id_field."' AND value = $item_id; ";
// 			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
// echo $query3.'<BR>'.chr(10);
// 		} else {
// 			$query3 = "REPLACE inventory (serial_no, qty, partid, conditionid, status, locationid, ";
// 			$query3 .= "purchase_item_id, sales_item_id, returns_item_id, userid, date_created, notes) ";
// 			$query3 .= "VALUES ($ser, $qty, $partid, 2, $status, 1, ";
// 			//$query3 .= "NULL, $so_item_id, NULL, 0, '".$stock_date."', NULL); ";
// 			if ($id_field=="purchase_item_id") { $query3 .= "$item_id, "; } else { $query3 .= "NULL, "; }
// 			if ($id_field=="sales_item_id") { $query3 .= "$item_id, "; } else { $query3 .= "NULL, "; }
// 			if ($id_field=="returns_item_id") { $query3 .= "$item_id, "; } else { $query3 .= "NULL, "; }
// 			$query3 .= "0, '".$stock_date."', NULL); ";
// 			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
// echo $query3.'<BR>'.chr(10);
// 			$serialid = qid();

// 			$query3 = "UPDATE inventory_history SET date_changed = '".$stock_date."' ";
// 			$query3 .= "WHERE invid = $serialid; ";
// 			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
// echo $query3.'<BR>'.chr(10);
// 		}
// 		return ($serialid);
// 	}

	function insertSerialItem($serial_no, $partid, $locationid, $purchase_item_id = null, $returns_item_id = null, $date_created) {
		//Assumed values for the inventory table
		$status = prep('shelved');
		$qty = prep('1');
		$conditionid = prep('2');

		$serial_no = prep($serial_no);
		$partid = prep($partid);
		$locationid = prep($locationid);
		$purchase_item_id = prep($purchase_item_id); 
		$returns_item_id = prep($returns_item_id);

		$date_created = prep($date_created);

		//Function to create the insert script
		$query = "INSERT INTO inventory (serial_no, qty, partid, conditionid, status, locationid, purchase_item_id, returns_item_id, date_created) VALUES ($serial_no, $qty, $partid, $conditionid, $status, $locationid, $purchase_item_id, $returns_item_id, $date_created)";
		$result = qdb($query) OR die(qe().' '.$query);
		$id = qid();

		return $id;
	}

	function insertTableCost($inventoryid, $date, $actual) {
		$inventoryid = prep($inventoryid);
		$date = prep($date);
		$actual = prep($actual);
		$average = prep('0.00');
		$notes = prep('imported');

		//$date = prep($date." 23:59:59");

		//Function to create the insert script
		$query = "INSERT INTO inventory_costs (inventoryid, datetime, actual, average, notes) VALUES ($inventoryid, $date, $actual, $average, $notes)";
		$result = qdb($query) OR die(qe().' '.$query);

		return $result;
	}

	function insertTableConsignment($inventoryid, $repid, $price, $companyid, $order_id, $date, $pct, $exp_date, $memo) {
		$inventoryid = prep($inventoryid);
		$repid = prep($repid);
		$price = prep($price);
		$companyid = prep($companyid);
		$order_id = prep($order_id);
		$date = prep($date);
		$pct = prep($pct);
		$exp_date = prep($exp_date);
		$memo = prep($memo);

		$date = prep($date." 10:00:00");

		//Function to create the insert script
		$query = "INSERT INTO consignment (inventoryid, rep_id, price, companyid, order_id, date, pct, exp_date, memo) VALUES ($inventoryid, $repid, $price, $companyid, $order_id, $date, $pct, $exp_date, $memo)";
		$result = qdb($query) OR die(qe().' '.$query);

		return $result;
	}

	function mapLocationID($locationid) {
		//Function top map Brian's contactid to ours
		$locationName = '';
		$location_array = array();
		$locationid = 1;

		$locationid = prep($locationid);
		
		if($locationid != '') {
			//Check for People
			$query = "SELECT * FROM inventory_location WHERE id = $locationid ORDER BY id ASC; ";
			$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$locationName = trim($r['location']);
			}
			

			$location_array = preg_split( "/(-| )/", $locationName, 2);
			$place = trim($location_array[0]);
			$instance = trim($location_array[1]);

			//Check our database for ID
			$query = "SELECT * FROM locations WHERE LOWER(place) = ".prep(strtolower($place))." AND instance = ".prep($instance)."; ";
			$result = qdb($query) OR die(qe().' '.$query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$locationid = $r['id'];
			}
		} else {
			$locationid = 1;
		}

		echo '<b>Translated Location ID: </b>' .$locationid . '<br>';
		
		return $locationid;
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
	

?>
