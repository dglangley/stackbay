<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/svcs_pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/imports.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPartId.php';

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

	$query = "SELECT i.component_id, u.quantity, i.received_quantity qty, u.price, i.job_id, i.sale_price, i.po_id, i.ur_id, c.part_number, u.date ";
	$query .= "FROM services_userrequest u, services_jobbulkinventory i, services_component c ";
	$query .= "WHERE u.id = i.ur_id AND po_id IS NOT NULL AND u.date >= '2016-01-01' AND c.id = i.component_id; ";
	$result = qedb($query,'SVCS_PIPE');
	while ($r = mysqli_fetch_assoc($result)) {
		$po_number = mapPO($r['po_id']);
		if (! $po_number) {
			echo "Missing PO# for ".$r['po_id']."!<BR><BR>";
			continue;
		}
		echo 'po id '.$r['po_id'].'<BR>';

		$query2 = "SELECT created FROM purchase_orders WHERE po_number = '".res($po_number)."' AND created = '".$r['date']." 12:00:00'; ";
		$result2 = qedb($query2);
		if (mysqli_num_rows($result2)==0) {
			$query2 = "UPDATE purchase_orders SET created = '".$r['date']." 12:00:00' WHERE po_number = '".res($po_number)."'; ";
			$result2 = qedb($query2);
		}

		$part = utf8_encode(trim($r['part_number']));

		$partid = mapComponent($r['component_id']);
		if (! $partid) {
			$partid = mapComponent($part,'part');

			if (! $partid) {
				echo "Missing partid for ".$r['component_id']." ".$r['part_number']."!<BR><BR>";
				continue;
			}
		}
		echo 'component '.$r['part_number'].'<BR>';
		echo 'component id '.$r['component_id'].'<BR>';

		// already correct?
		$query2 = "SELECT * FROM purchase_items ";
		$query2 .= "WHERE po_number = '".$po_number."' AND partid = '".$partid."' AND price = '".$r['price']."' ";
		$query2 .= "ORDER BY IF(qty='".$r['qty']."',0,1); ";
		$result2 = qedb($query2);
		if (mysqli_num_rows($result2)>0) {
			echo '<BR><BR>';
			continue;
		}

		$alt_partid = 0;
		$query2 = "SELECT * FROM purchase_items ";
		$query2 .= "WHERE po_number = '".$po_number."' AND partid = '".$partid."' AND price = '".$r['sale_price']."' ";
		$query2 .= "ORDER BY IF(qty='".$r['qty']."',0,1); ";
		$result2 = qedb($query2);
		if (mysqli_num_rows($result2)==0) {
			$partids = getPartId($part,'',0,true,true);
			print "<pre>".print_r($partids,true)."</pre>";

			$matches = 0;
			foreach ($partids as $altid) {
				if ($altid==$partid) { continue; }

				$query2 = "SELECT * FROM purchase_items ";
				$query2 .= "WHERE po_number = '".$po_number."' AND partid = '".$altid."' AND price = '".$r['sale_price']."' ";
				$query2 .= "ORDER BY IF(qty='".$r['qty']."',0,1); ";
				$result2 = qedb($query2);
				$n = mysqli_num_rows($result2);
				if ($n==0) {
					echo $query2.'<BR>';
					continue;
				}

				$alt_partid = $altid;
				break;
			}
			if (! $alt_partid) {
//				echo $query2.'<BR>';
				exit;
			}

			$query3 = "UPDATE maps_component SET partid = '".$partid."' WHERE partid = '".$alt_partid."' AND BDB_cid = '".$r['component_id']."'; ";
			$result3 = qedb($query3);
		}

		while ($PI = mysqli_fetch_assoc($result2)) {
			$query2 = "UPDATE purchase_items SET price = '".$r['price']."' ";
			if ($alt_partid) { $query2 .= ", partid = '".$partid."' "; }
			$query2 .= "WHERE id = '".$PI['id']."' ";
//			$query2 .= "LIMIT 1 ";
			$query2 .= "; ";
			$result2 = qedb($query2);
		}

		echo '<BR><BR>';
	}

	echo '<BR><BR>UH.... YOU\'RE DONE, MISTER!';
?>
