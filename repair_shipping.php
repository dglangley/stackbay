<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setInventory.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';

	function triggerNewSO($ro_number) {
		$repair_info = array();
		$query = "SELECT * FROM repair_orders r, repair_items i WHERE r.ro_number = ".prep($ro_number)." AND r.ro_number = i.ro_number;";
		$result = qdb($query) or die(qe());
		$order_number;

		if(mysqli_num_rows($result)) {
			$result = mysqli_fetch_assoc($result);
			$repair_info[] = $result;
		}

		if($repair_info) {
			foreach ($repair_info as $item) {			
				$query = "INSERT INTO sales_orders (created, created_by, sales_rep_id, companyid, contactid, cust_ref, ref_ln, bill_to_id, ship_to_id, freight_carrier_id, freight_services_id, freight_account_id, termsid, public_notes, private_notes, status) VALUES (
				'".$GLOBALS['now']."',
				'".$GLOBALS['U']['id']."',
				".prep($item['sales_rep_id']).",
				".prep($item['companyid']).",
				".prep($item['contactid']).",
				".prep($item['cust_ref']).",
				".prep($item['ref_ln']).",
				".prep($item['bill_to_id']).",
				".prep($item['ship_to_id']).",
				".prep($item['freight_carrier_id']).",
				".prep($item['freight_services_id']).",
				".prep($item['freight_account_id']).",
				'15',
				".prep($item['public_notes']).",
				".prep($item['private_notes']).",
				'Active'
				);";

				$result = qdb($query) or die(qe() . ' : ' . $query);
				$order_number = qid();

				if($order_number) {
					$query = "INSERT INTO sales_items (partid, so_number, line_number, qty, price, delivery_date, ref_1, ref_1_label, ref_2, ref_2_label, warranty, conditionid) VALUES (
					".prep($item['partid']).",
					".prep($order_number).",
					'1',
					".prep($item['qty']).",
					'0.00',
					".prep($item['due_date']).",
					".prep($item['id']).",
					'repair_item_id',
					NULL,
					NULL,
					'14',
					'5'
					);";

					$result = qdb($query) or die(qe() . ' : ' . $query);
				}
			}
		}

		return $order_number;

	}

	function getLocation($place, $instance) {
		$locationid;
		$query;
		
		//Get the location ID based on the preset ones in the table
		if($instance != '') {
			$query = "SELECT id FROM locations WHERE place = '". res($place) ."' AND instance = '". res($instance) ."';";
		} else {
			$query = "SELECT id FROM locations WHERE place = '". res($place) ."' AND instance is NULL;";
		}
		
		$locationResult = qdb($query);
		
		if (mysqli_num_rows($locationResult)>0) {
			$locationResult = mysqli_fetch_assoc($locationResult);
			$locationid = $locationResult['id'];
		}
		
		return $locationid;
	}

	function updatetoStock($place, $instance, $condition, $inventoryids){
		$locationid = getLocation($place, $instance);
		foreach ($inventoryids as $inventoryid) {
			$I = array('id'=>$inventoryid,'conditionid'=>'5');
			setInventory($I);
		}
	}
	
	//Declare variables
	$ro_number;
	$place;
	$instance;
	$condition;
	$inventoryids = array();

	if (isset($_REQUEST['place'])) { $place = $_REQUEST['place']; }
	if (isset($_REQUEST['instance'])) { $instance = $_REQUEST['instance']; }
	if (isset($_REQUEST['condition'])) { $condition = $_REQUEST['condition']; }
	if (isset($_REQUEST['inventoryids'])) { $inventoryids = $_REQUEST['inventoryids']; }
	//if (isset($_REQUEST['bill_option'])) { $bill_option = $_REQUEST['bill_option']; }
	
	if (isset($_REQUEST['ro_number'])) { 
		$ro_number = $_REQUEST['ro_number']; 
		updatetoStock($place, $instance, $condition, $inventoryids);
		$order_number = triggerNewSO($ro_number);//, $now);
	}
	
	header('Location: /shipping.php?on=' . $order_number);

	exit;
