<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/svcs_pipe.php';

	$DEBUG = 3;

	$PARTS = array();

	$BDB = array();
	$query2 = "SELECT * FROM services_billli b; ";
	$result2 = qedb($query2,'SVCS_PIPE');
	while ($r2 = mysqli_fetch_assoc($result2)) {
		$BDB[$r2['id']] = $r2;
	}

	$BULKI = array();
	$query2 = "SELECT * FROM services_jobbulkinventory i; ";
	$result2 = qedb($query2,'SVCS_PIPE');
	while ($r2 = mysqli_fetch_assoc($result2)) {
		$BULKI[$r2['po_number']][$r2['sale_price']] = $r2;
	}

	$query = "SELECT * FROM maps_bill ORDER BY BDB_billli_id ASC; ";
	$result = qedb($query);
	while ($r = mysqli_fetch_assoc($result)) {

		$B = $BDB[$r['BDB_billli_id']];
		if (! $B['jmpo_id']) { continue; }

		// Resolve partids
		$query2 = "SELECT * FROM bill_items WHERE id = '".$r['bill_item_id']."'; ";
		$result2 = qedb($query2);
		$S = mysqli_fetch_assoc($result2);
		$partid = $S['partid'];

		if (! $partid) {
			$part = preg_replace('/^Item:[[:space:]]?([^\;]+)[\;].*/','$1',$S['memo']);

			if (! isset($PARTS[$part])) {
				$PARTS[$part] = 0;

				$query3 = "SELECT id FROM parts WHERE part = '".res($part)."'; ";
				$result3 = qedb($query3);
				if (mysqli_num_rows($result3)==0) {
continue;
print "<pre>".print_r($B,true)."</pre>";
print "<pre>".print_r($S,true)."</pre>";
echo $part.' not found!<BR>';
continue;
				} else {
					$r3 = mysqli_fetch_assoc($result3);
					$PARTS[$part] = $r3['id'];
				}
			}
			$partid = $PARTS[$part];
			$S['partid'] = $partid;

			if ($partid) {
				$query2 = "UPDATE bill_items SET partid = '".$partid."' WHERE id = '".$r['bill_item_id']."'; ";
				$result2 = qedb($query2);
			}
		}

		if (! $partid) { continue; }


		// Resolve PO#s
		$query2 = "SELECT * FROM bills WHERE bill_no = '".$S['bill_no']."'; ";
		$result2 = qedb($query2);
		if (mysqli_num_rows($result2)==0) { die('Zero bills:<BR>'.$query2); }
		$r2 = mysqli_fetch_assoc($result2);
		$po_number = $r2['po_number'];

		if (! $po_number) {
			$query2 = "SELECT po_number FROM maps_PO m, purchase_items pi WHERE BDB_poid = '".$B['jmpo_id']."' AND pi.id = purchase_item_id; ";
			$result2 = qedb($query2);
			if (mysqli_num_rows($result2)==0) { echo ('Zero purchases:<BR>'.$query2.'<BR>'); continue; }
			$r2 = mysqli_fetch_assoc($result2);
			$po_number = $r2['po_number'];

			$query2 = "UPDATE bills SET po_number = '".$po_number."' WHERE bill_no = '".$S['bill_no']."'; ";
			$result2 = qedb($query2);
		}

		$query2 = "SELECT * FROM purchase_items WHERE po_number = '".$po_number."' AND partid = '".$partid."'; ";
		$result2 = qedb($query2);
		if (mysqli_num_rows($result2)<>1) { continue; }

		$r2 = mysqli_fetch_assoc($result2);

		$query2 = "UPDATE bill_items SET item_id = '".$r2['id']."', item_id_label = 'purchase_item_id' WHERE id = '".$r['bill_item_id']."'; ";
		$result2 = qedb($query2);


print "<pre>".print_r($B,true)."</pre>";
print "<pre>".print_r($S,true)."</pre>";
echo $query2.'<BR>';

echo "<BR><BR>";
	}
?>
