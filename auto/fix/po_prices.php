<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/svcs_pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/imports.php';

	$DEBUG = 3;

	function mapPO($po_id) {
		$po_number = 0;

		$query = "SELECT po_number FROM maps_PO m, purchase_items pi ";
		$query .= "WHERE BDB_poid = '".res($po_id)."' AND pi.id = purchase_item_id; ";
		$result = qedb($query);
		if (mysqli_num_rows($result)==0) { return ($po_number); }
		$r = mysqli_fetch_assoc($result);

		return ($r['po_number']);
	}


	function mapComponent($component_id) {
		$partid = 0;

		$query = "SELECT partid FROM maps_component WHERE BDB_cid = '".res($component_id)."'; ";
		$result = qedb($query);
		if (mysqli_num_rows($result)==0) { return ($partid); }
		$r = mysqli_fetch_assoc($result);

		return ($r['partid']);
	}

	$query = "SELECT i.component_id, u.quantity qty, u.price, i.job_id, i.sale_price, i.po_id, i.ur_id ";
	$query .= "FROM services_userrequest u, services_jobbulkinventory i ";
	$query .= "WHERE u.id = i.ur_id AND po_id IS NOT NULL AND u.date >= '2016-01-01'; ";
	$result = qedb($query,'SVCS_PIPE');
	while ($r = mysqli_fetch_assoc($result)) {
		$po_number = mapPO($r['po_id']);
		$partid = mapComponent($r['component_id']);
		if (! $partid OR ! $po_number) { continue; }

		$query2 = "SELECT * FROM purchase_items ";
		$query2 .= "WHERE po_number = '".$po_number."' AND partid = '".$partid."' AND price = '".$r['sale_price']."'; ";
		$result2 = qedb($query2);
		if (mysqli_num_rows($result2)==0) {
			die($query2);
		}

		echo '<BR><BR>';
	}
?>
