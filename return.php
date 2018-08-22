<?php
	$rma_number = '';
	if (isset($_REQUEST['order_number'])) { $rma_number = trim($_REQUEST['order_number']); }

	if (! $rma_number AND isset($_REQUEST['taskid'])) {
		include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

		$query = "SELECT rma_number FROM return_items WHERE id = '".res($_REQUEST['taskid'])."'; ";
		$result = qedb($query);
		if (qnum($result)>0) {
			$r = qrow($result);
			$rma_number = $r['rma_number'];
		}
	}

	include 'rma.php';
?>
