<?php
	header('Content-Type: application/json');
	$rootdir = $_SERVER['ROOT_DIR'];
			
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/form_handle.php';

	$data = json_decode(grab('submission'),true);

	$bill_no = $data['bill_no'];
	if (! trim($bill_no) OR ! is_numeric($bill_no)) { $bill_no = 0; }
	$invoice_no = '';
	if (trim($data['invoice_no'])) { $invoice_no = trim($data['invoice_no']); }
	$due_date = format_date($data['due_date'], "Y-m-d");

	if ($bill_no) {
		$query = "UPDATE bills SET due_date = '".res($due_date)."', invoice_no = ";
		if ($invoice_no) { $query .= "'".res($invoice_no)."' "; }
		else { $query .= "NULL "; }
		$query .= "WHERE bill_no = '".res($bill_no)."' LIMIT 1; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
	} else { // create new bill if none is passed in
                $companyid = 0;
                
                $po_select = "
                Select `companyid`, `days` FROM purchase_orders, terms
                WHERE terms.id = termsid AND po_number = ".prep($data['po_number'])."
                ";
                
                $po_results = qdb($po_select);
                
                if (mysqli_num_rows($po_results)>0){
                    $po_results = mysqli_fetch_assoc($po_results);
                    $companyid = $po_results['companyid'];
                }

                $bill_insert = "
                INSERT INTO `bills`(
                `invoice_no`,
                `date_created`,
                `due_date`,
                `po_number`,
                `companyid`) VALUES (
                ".prep($invoice_no).",
                '".$GLOBALS['now']."',
                ".prep($due_date,'NULL').",
                ".prep($data['po_number']).",
                ".prep($companyid,"NULL").");
                ";
                qdb($bill_insert) or die(qe().": ".$bill_insert);

                $bill_no = qid();
	}
	foreach ($data['lines'] as $line) {
				$line_id = 0;
				if ($line['bill_item_id']) { $line_id = $line['bill_item_id']; }

				if ($line['qty']==0){
					// delete if already set
					if ($line_id) {
						$query = "DELETE FROM bill_items WHERE id = '".$line_id."'; ";
						$result = qdb($query) OR die(qe().'<BR>'.$query);

						$query = "DELETE FROM bill_shipments WHERE bill_item_id = '".$line_id."'; ";
						$result = qdb($query) OR die(qe().'<BR>'.$query);
					}
                    continue;
                }
				$query = "REPLACE bill_items (bill_no, partid, memo, qty, amount, warranty, line_number";
				if ($line_id) { $query .= ", id"; }
				$query .= ") VALUES ('".$bill_no."', ".prep($line['partid']).", NULL, ".prep($line['qty']).", ";
				$query .= prep($line['price']).", ".prep($line['warranty']).", ".prep($line['ln']);
				if ($line_id) { $query .= ", '".res($line_id)."' "; }
				$query .= "); ";
				$result = qdb($query) or die(qe().": ".$query);
				if (! $line_id) { $line_id = qid(); }

                if(current($line['serials'])){
                    foreach($line['serials'] as $invid){
						$inv = prep($invid);
						$query2 = "SELECT * FROM bill_shipments WHERE inventoryid = $inv AND packageid IS NULL AND bill_item_id = '".res($line_id)."'; ";
						$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
						if (mysqli_num_rows($result2)==0) {
							$query2 = "INSERT INTO bill_shipments (inventoryid, packageid, bill_item_id) ";
							$query2 .= "VALUES ($inv, NULL, $line_id); ";
							$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
						}
                    }
                }
	}

	echo json_encode($bill_no);
	exit;
?>

