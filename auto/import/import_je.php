<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setJournalEntry.php';

	$query = "SELECT * FROM inventory_journalentry WHERE memo NOT LIKE 'ADP Payroll%' ORDER BY id ASC; ";
	$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		setJournalEntry($r['id']."-JE",$r['date']." 06:00:00",$r['debit_acct'],$r['credit_acct'],$r['memo'],$r['amount'],$r['invoice_id'],'invoice',$r['validated']);
	}
?>
