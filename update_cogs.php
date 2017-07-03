<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setJournalEntry.php';

	$query = "SELECT sc.*, c.invoice_no FROM sales_cogs sc, commissions c ";
	$query .= "WHERE c.invoice_item_id IS NOT NULL AND c.cogsid = sc.id AND c.commission_rate IS NOT NULL; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$jeid = setJournalEntry(false,$GLOBALS['now'],'Inventory Sale COGS','Inventory Asset','COGS for Invoice '.
	}
?>
