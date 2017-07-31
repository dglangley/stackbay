<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPartId.php';

	/***** RUN AFTER import_db.php AND import_invoice.php AND import_inventory.php *****/

	$query = "SELECT * FROM inventory_invoiceli li, inventory_solditem si, inventory_inventory i ";
	$query .= "WHERE li.invoice_id = si.invoice_id AND si.inventory_id = i.id; ";;
	$result = qdb($query,'PIPE') OR die(qe('PIPE').' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		if ($r['clei']) { $r['heci'] = $r['clei']; }
		else if (strlen($r['heci'])<>7 OR is_numeric($r['heci']) OR preg_match('/[^[:alnum:]]+/',$r['heci'])) { $r['heci'] = ''; }
		else { $r['heci'] .= 'VTL'; }//append fake ending to make the 7-digit a 10-digit string

		// assume we get a valid partid since we've imported inventory previously
		$partid = getPartId($r['part_number'],$r['heci']);

		$query2 = "SELECT ii.id, i.order_number FROM invoices i, invoice_items ii ";
		$query2 .= "WHERE i.invoice_no = '".$r['invoice_id']."' AND i.invoice_no = ii.invoice_no AND partid = '".$partid."'; ";
		$result2 = qdb($query2) OR die(qe().' '.$query2);
		if (mysqli_num_rows($result2)==0) { echo $query2.'<BR>'; continue; }
		$r2 = mysqli_fetch_assoc($result2);
		$invoice_item_id = $r2['id'];

		$query2 = "SELECT c.packageid FROM inventory i, package_contents c, packages p ";
		$query2 .= "WHERE serial_no = '".$r['serial']."' AND i.id = c.serialid AND p.id = c.packageid ";
		$query2 .= "AND order_number = '".$r2['order_number']."'; ";
		$result2 = qdb($query2) OR die(qe().' '.$query2);
		if (mysqli_num_rows($result2)==0) { echo $query2.'<BR>'; continue; }
		$r2 = mysqli_fetch_assoc($result2);
		$packageid = $r2['packageid'];

		$query2 = "REPLACE invoice_shipments (invoice_item_id, packageid) ";
		$query2 .= "VALUES ('".$invoice_item_id."','".$packageid."'); ";
echo $query2.'<BR>';
		$result2 = qdb($query2) OR die(qe().' '.$query2);
	}
?>
