<?php
exit;
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/svcs_pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/imports.php';

	$DEBUG = 0;

	$query = "SELECT commissionid FROM maps_commission; ";
	$result = qedb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		$query2 = "DELETE FROM commissions WHERE id = '".$r['commissionid']."'; ";
		$result2 = qedb($query2);

		$query2 = "DELETE FROM commission_payouts WHERE commissionid = '".$r['commissionid']."'; ";
		$result2 = qedb($query2);
	}

	$query = "TRUNCATE maps_commission; ";
	$result = qedb($query);

	$query = "SELECT id, rep_id, amount, paid, paid_date, approved, job_id FROM services_commission c ";
	$query .= "WHERE canceled = '0' AND (paid_date IS NULL OR paid_date >= '2016-01-01'); ";
	echo $query.'<BR>';
	$result = qedb($query,'SVCS_PIPE');
	while ($r = mysqli_fetch_assoc($result)) {
		$rep_id = mapUser($r['rep_id']);
		if (! $rep_id) { continue; }

		$item_id = mapJob($r['job_id']);
		$item_id_label = 'service_item_id';

		// get invoice info
		$invoice_no = 0;
		$invoice_item_id = 0;
		$query2 = "SELECT invoice_id FROM services_jobpo po, services_jobpoli poli, services_invoiceli i ";
		$query2 .= "WHERE po.job_id = '".$r['job_id']."' AND po.id = poli.po_id AND poli.invoiceli_id = i.id; ";
		$result2 = qedb($query2,'SVCS_PIPE');
		if (mysqli_num_rows($result2)>1) {
			echo 'FOUND MULTIPLE INVOICES: '.$query2.'<BR>';
		}
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$invoice_no = $r2['invoice_id']-10000;

			$query3 = "SELECT id FROM invoice_items ";
			$query3 .= "WHERE invoice_no = '".$invoice_no."' AND ref_1 = '".$item_id."' AND ref_1_label = '".$item_id_label."'; ";
			$result3 = qedb($query3);
			if (mysqli_num_rows($result3)>0) {
				$r3 = mysqli_fetch_assoc($result3);
				$invoice_item_id = $r3['id'];
				break;//found any given invoice item id related to comm
			}
		}

		if (! $r['paid_date']) { $r['paid_date'] = '2018-01-01 00:00:00'; }

		$query2 = "INSERT INTO commissions (invoice_no, invoice_item_id, inventoryid, item_id, item_id_label, ";
		$query2 .= "datetime, cogsid, rep_id, commission_rate, commission_amount) ";
		$query2 .= "VALUES (".fres($invoice_no).", ".fres($invoice_item_id).", NULL, '".$item_id."', '".$item_id_label."', ";
		$query2 .= fres($r['paid_date']).", NULL, '".$rep_id."', NULL, '".$r['amount']."'); ";
		$result2 = qedb($query2);
		if ($DEBUG) { $commid = 999999; }
		else { $commid = qid(); }

		if ($r['paid']) {
			$query2 = "INSERT INTO commission_payouts (commissionid, paid_date, amount, userid) ";
			$query2 .= "VALUES ('".$commid."', ".fres($r['paid_date']).", '".$r['amount']."', NULL); ";
			$result2 = qedb($query2);
		}

		$query2 = "INSERT INTO maps_commission (BDB_commission_id, commissionid) VALUES ('".$r['id']."', '".$commid."'); ";
		$result2 = qedb($query2);
	}
?>
