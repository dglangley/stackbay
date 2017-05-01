<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/import_aid.php';

	//Temp array to hold Brian's data
	$items = array();
	
	//For testing purposes and compile time adding LIMIT 30
	$query = "SELECT * FROM inventory_solditem WHERE ci_id IS NOT NULL ORDER BY id ASC; ";
	$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$items[] = $r;
	}

	foreach ($items as $key => $value) {
		$inventoryid = 0;

		// if($serial = '000') {
		$serial =  $value['serial'];
		echo $serial . '<br>';

		//Check to see if the serial already exists
		$query = "SELECT id FROM inventory WHERE serial_no = ".prep($serial).";";
		$result = qdb($query) OR die(qe().' '.$query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$inventoryid = $r['id'];
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

			$query = "SELECT id FROM consignment WHERE inventoryid = ".prep($inventoryid)." AND rep_id = ".prep($rep_id)." AND order_id = ".prep($consignment['order_id']).";";
			$result = qdb($query) OR die(qe().' '.$query);
			if (mysqli_num_rows($result)>0) {
				echo 'Consignment Item Already Exists!';
			} else {
				//insertTableConsignment($inventoryid, $repid, $price, $companyid, $order_id, $date, $pct, $exp_date, $memo)
				if(insertTableConsignment($inventoryid, $rep_id, $consignment['price'], dbTranslate($consignment['company_id']), $consignment['order_id'], $consignment['date'], $consignment['pct'], $consignment['exp_date'], $consignment['memo'])){
					echo 'Consignment Added Successfully<br>';
				} else {
					echo 'Consignment Failed<br>';
				}
			}
			//}
		}

		echo '<br><br>';
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
