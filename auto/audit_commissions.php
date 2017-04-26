<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getRep.php';

	/*****
		Commissions table fields:
		invoice_no
		rep_id
		amount
		paid
		sold_item_id
		repair_id
		ticket_id (?)
		sinvoice_id
		canceled
		approved
		paid_date
		source_id (see `inventory_commissionsource`)
		orig_amount (used only once for id '83383')
		adjusted (see orig_amount, same here)
		orig_rep_id (see orig_amount, same here)
	*****/

	$RATES = array();
	$query = "SELECT id, commission_rate FROM users WHERE commission_rate > 0; ";
	$result = qdb($query) OR die(qe().' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$RATES[$r['id']] = $r['commission_rate'];
	}

	$query = "SELECT * FROM invoices i, invoice_items ii ";
	$query .= "WHERE i.invoice_no = ii.invoice_no ORDER BY date_invoiced DESC LIMIT 0,100; ";
	$result = qdb($query) OR die(qe().' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
echo $r['invoice_no'].' '.$r['amount'].'<BR>';
foreach ($RATES as $userid => $rate) {
	echo ' &nbsp; '.getRep($userid).' at '.$rate.'% = '.($r['amount']*($rate/100)).'<BR>';
}
	}
?>
