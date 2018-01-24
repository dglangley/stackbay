<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/svcs_pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/imports.php';

	$DEBUG = 1;

	function mapPO($po_id) {
		$po_number = 0;

		$query = "SELECT po_number FROM maps_PO m, purchase_items pi ";
		$query .= "WHERE BDB_poid = '".res($po_id)."' AND pi.id = purchase_item_id; ";
		$result = qedb($query);
		if (mysqli_num_rows($result)==0) { return ($po_number); }
		$r = mysqli_fetch_assoc($result);

		return ($r['po_number']);
	}


	function mapComponent($input_field,$input_type='id') {
		$partid = 0;

		if ($input_type=='id') {
			$query = "SELECT partid FROM maps_component WHERE BDB_cid = '".res($input_field)."'; ";
		} else if ($input_type=='part') {
			$query = "SELECT id partid FROM parts WHERE part = '".res($input_field)."'; ";
		}
		$result = qedb($query);
		if (mysqli_num_rows($result)==0) { return ($partid); }
		$r = mysqli_fetch_assoc($result);

		return ($r['partid']);
	}

	$query = "SELECT i.component_id, u.quantity qty, u.price, i.job_id, i.sale_price, i.po_id, i.ur_id, c.part_number, u.date ";
	$query .= "FROM services_userrequest u, services_jobbulkinventory i, services_component c ";
	$query .= "WHERE u.id = i.ur_id AND po_id IS NOT NULL AND u.date >= '2016-01-01' AND c.id = i.component_id; ";
	$result = qedb($query,'SVCS_PIPE');
	while ($r = mysqli_fetch_assoc($result)) {
		$po_number = mapPO($r['po_id']);
		if (! $po_number) {
			echo "Missing PO# for ".$r['po_id']."!<BR><BR>";
			continue;
		}

		$query2 = "UPDATE purchase_orders SET created = '".$r['date']." 12:00:00' WHERE po_number = '".res($po_number)."'; ";
		$result2 = qedb($query2);

		$partid = mapComponent($r['component_id']);
		if (! $partid) {
			$partid = mapComponent($r['part_number'],'part');

			if (! $partid) {
				echo "Missing partid for ".$r['component_id']." ".$r['part_number']."!<BR><BR>";
				continue;
			}
		}

		$query2 = "SELECT * FROM purchase_items ";
		$query2 .= "WHERE po_number = '".$po_number."' AND partid = '".$partid."' AND price = '".$r['sale_price']."' ";
		$query2 .= "ORDER BY IF(qty='".$r['qty']."',0,1); ";
		$result2 = qedb($query2);
		if (mysqli_num_rows($result2)==0) {
			echo 'component id '.$r['component_id'].'<BR>';
			echo 'po id '.$r['po_id'].'<BR>';
			die($query2);
		}

		while ($PI = mysqli_fetch_assoc($result2)) {
			$query2 = "UPDATE purchase_items SET price = '".$r['price']."' ";
			$query2 .= "WHERE id = '".$PI['id']."' ";
//			$query2 .= "LIMIT 1 ";
			$query2 .= "; ";
			$result2 = qedb($query2);
		}

		echo '<BR><BR>';
	}

	echo '<BR><BR>UH.... YOU\'RE DONE, MISTER!';
?>
