<?php
	$query = "SELECT sc.* FROM sales_cogs sc, commissions c ";
	$query .= "WHERE c.invoice_item_id IS NOT NULL AND c.cogsid = sc.id AND c.commission_rate IS NOT NULL; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		echo $r;
	}
?>
