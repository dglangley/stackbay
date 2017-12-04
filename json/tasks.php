<?php
	include '../inc/dbconnect.php';
	include '../inc/format_date.php';
	include '../inc/getCompany.php';

	$q = '';
	if (isset($_REQUEST['q'])) { $q = trim($_REQUEST['q']); }
	$order_type = '';
	if (isset($_REQUEST['order_type'])) { $order_type = trim($_REQUEST['order_type']); }
	$noreset = 0;
	if (isset($_REQUEST['noreset'])) { $noreset = trim($_REQUEST['noreset']); }
	$userid = 0;
	if (isset($_REQUEST['userid'])) { $userid = trim($_REQUEST['userid']); }

	$recent_date = format_date($today,'Y-m-d 00:00:00',array('d'=>-90));

	$tasks = array();
	$query = '';

	if($order_type == 'repair') {
		$query = "SELECT ro.*,  ri.id as item_id, ri.line_number FROM repair_orders ro, repair_items ri, service_assignments si ";
		if ($q) {
			$query .= "WHERE ro_number RLIKE '".res($q)."' AND repair_code_id IS NULL AND ri.ro_number = ro.ro_number AND ri.ro_number = ro.ro_number ";
		} else {
			if (! $noreset) {
				$tasks[] = array('id'=>0,'text'=>'- Reset Tasks -');
			}
			$query .= "WHERE repair_code_id IS NULL AND si.item_id = ri.id  AND item_id_label = 'repair_item_id' AND ri.ro_number = ro.ro_number ";
			$query .= "ORDER BY ro.created DESC LIMIT 0,10; ";
		}
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) {
			$tasks[] = array('id'=>'','text'=>'- Select Task -');
		}

		while ($r = mysqli_fetch_assoc($result)) {
			//$task_label = ucwords($order_type);
			if ($r['ro_number']) { $task_label = $r['ro_number']. ($r['line_number'] ? '-' . $r['line_number'] : '-1') . ' '.getCompany($r['companyid']); }
			$tasks[] = array('id'=>$r['item_id'],'text'=>$task_label);
		}
	} else {
		$query = "SELECT so.*,  si.id as item_id, si.line_number, task_name FROM service_orders so, service_items si, service_assignments sa ";
		if (! $noreset) {
			$tasks[] = array('id'=>0,'text'=>'- Reset Tasks -');
		}
		$query .= "WHERE sa.item_id = si.id  AND item_id_label = 'service_item_id' AND si.so_number = so.so_number ";
		$query .= "ORDER BY so.datetime DESC LIMIT 0,10; ";

		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) {
			$tasks[] = array('id'=>'','text'=>'- Select Task -');
		}

		while ($r = mysqli_fetch_assoc($result)) {
			//$task_label = ucwords($order_type);
			if ($r['so_number']) { $task_label = $r['so_number']. ($r['line_number'] ? '-' . $r['line_number'] : '-1') . ' '. $r['task_name']; }
			$tasks[] = array('id'=>$r['item_id'],'text'=>$task_label);
		}
	}

	header("Content-Type: application/json", true);
	echo json_encode($tasks);
	exit;
?>
