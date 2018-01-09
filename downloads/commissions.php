<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPart.php';

	$filename = str_replace('/downloads/','',$_SERVER['REQUEST_URI']);
	$filename = 'commissions.csv';
//	if (! $filename OR $filename=='exporter.php') { $filename = 'inventory-export-'.$today.'.csv'; }

	header("Content-type: text/csv; charset=utf-8");
	header("Cache-Control: no-store, no-cache");
	header('Content-Disposition: attachment; filename="'.$filename.'"');

	$comms = array();
	if (isset($_REQUEST['comm'])) { $comms = $_REQUEST['comm']; }

	$outstream = fopen("php://output",'w');

	// Using the sourcing page to populate
	$header = array('Invoice Date','Order No.','Invoice','Comm Amount');
	fputcsv($outstream, $header, ',', '"');

	foreach ($comms as $id => $amt) {
		$query = "SELECT * FROM commissions c, invoices i ";
		$query .= "WHERE c.id = '".res($id)."' AND c.invoice_no = i.invoice_no; ";
		$result = qedb($query);
		if (mysqli_num_rows($result)==0) { continue; }
		$r = mysqli_fetch_assoc($result);

		$item = '';
		if ($r['item_id_label']=='sales_item_id') {
			$query2 = "SELECT partid FROM sales_items WHERE id = '".$r['item_id']."'; ";
		} else {
			$query2 = "SELECT partid FROM repair_items WHERE id = '".$r['item_id']."'; ";
		}
		$result2 = qedb($query2);
		$r2 = mysqli_fetch_assoc($result2);
		getPart($r2['partid']);
		$P = $PARTS[$r2['partid']];

/*
		$cogs = '';
		$query2 = "SELECT cogs_avg FROM sales_cogs WHERE id = '".res($r['cogsid'])."'; ";
		$result2 = qedb($query2);
		if (mysqli_num_rows($result2)>0) {
			$r2 = mysqli_fetch_assoc($result2);
			$cogs = $r2['cogs_avg'];
		}
*/

		$row = array(
			format_date($r['date_invoiced'],'Y-m-d'),
			$r['order_number'],
			$r['invoice_no'],
			$amt,
		);
		fputcsv($outstream, $row, ',', '"');
	}

	fclose($outstream);
	exit;
?>
