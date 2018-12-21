<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setInventory.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	$DEBUG = 0;

	function triggerNewSO($id,$type = 'Repair') {
		$T = order_type($type);

		$order_number = false;

		$query = "SELECT * FROM ".$T['orders']." r, ".$T['items']." i WHERE ";
		// if ($type=='ro_number') { $query .= "r.ro_number "; } else if ($type=='repair_item_id') { $query .= "i.id "; }
		$query .= "i.id ";
		$query .= "IN (".res($id).") AND r.".$T['order']." = i.".$T['order']." ";
		$query .= "; ";// LIMIT 0,1;";
		$result = qedb($query);
		if (qnum($result)==0) { return ($order_number); }
		while ($r = mysqli_fetch_assoc($result)) {
			if (isset($r['item_id']) AND isset($r['item_label'])) {
				if ($r['item_label']=='partid') { $r['partid'] = $r['item_id']; }
				else { $r['partid'] = ''; }
			}

			if (! $r['partid']) {
				continue;
//				return ($order_number);
			}

			$ln = 1;
			if ($r['line_number']) { $ln = $r['line_number']; }

			// check for existing SO and return that, if applicable
			$query2 = "SELECT * FROM sales_items ";
			$query2 .= "WHERE ((ref_1 = '".$r['id']."' AND ref_1_label = '".$T['item_label']."') ";
			$query2 .= "OR (ref_2 = '".$r['id']."' AND ref_2_label = '".$T['item_label']."')); ";
			$result2 = qedb($query2);
			if (qnum($result2)>0) {
				$r2 = qrow($result2);
				return ($r2['so_number']);
			}

			if (! $order_number) {
				$query2 = "INSERT INTO sales_orders (created, created_by, sales_rep_id, companyid, contactid, cust_ref, ref_ln, ";
				$query2 .= "bill_to_id, ship_to_id, freight_carrier_id, freight_services_id, freight_account_id, termsid, ";
				$query2 .= "public_notes, private_notes, status) ";
				$query2 .= "VALUES ('".$GLOBALS['now']."', '".$GLOBALS['U']['id']."',
					".fres($r['sales_rep_id']).",
					".fres($r['companyid']).",
					".fres($r['contactid']).",
					".fres($r['cust_ref']).",
					".fres($r['ref_ln']).",
					".fres($r['bill_to_id']).",
					".fres($r['ship_to_id']).",
					".fres($r['freight_carrier_id']).",
					".fres($r['freight_services_id']).",
					".fres($r['freight_account_id']).",
					'15',
					NULL,
					NULL,
					'Active'); ";
				$result2 = qedb($query2);

				if ($GLOBALS['DEBUG']) { $order_number = 999999; }
				else { $order_number = qid(); }
			}

			if (! $order_number) { return ($order_number); }

			// generate new sales item for SO
			$query2 = "INSERT INTO sales_items (partid, so_number, line_number, qty, price, delivery_date, ";
			$query2 .= "ref_1, ref_1_label, ref_2, ref_2_label, warranty, conditionid) ";
			$query2 .= "VALUES ('".res($r['partid'])."', '".res($order_number)."', '".res($ln)."', '".res($r['qty'])."', ";
			$query2 .= "'0.00', ".fres($r['due_date']).", ";
			$query2 .= "'".$r['id']."', '".$T['item_label']."', NULL, NULL, '14', '5'); ";
			$result2 = qedb($query2);
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
		
		$locationResult = qedb($query);
		
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
	
	if (isset($_REQUEST['ro_number'])) { //legacy
		$ro_number = $_REQUEST['ro_number']; 
		updatetoStock($place, $instance, $condition, $inventoryids);
		$order_number = triggerNewSO($ro_number);//, $now);
	} else if (isset($_REQUEST['task_label']) AND isset($_REQUEST['taskid'])) {
		$order_number = triggerNewSO($_REQUEST['taskid'],($_REQUEST['type'] ? $_REQUEST['type'] : $_REQUEST['task_label']));
	}

	if (! $order_number AND ! $DEBUG) {
		die("There was a problem processing your shipping request for this order. Please see an admin immediately.");
	}

	if ($DEBUG) { exit; }
	
	header('Location: /shipping.php?order_number=' . $order_number);

	exit;
