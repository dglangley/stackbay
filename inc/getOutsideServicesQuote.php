<?php
	function getOutsideServicesQuote($quote_item_id,$return='quote') {
		$quotes = array();
		$quote = 0;

		$query = "SELECT * FROM service_quote_outsourced WHERE quote_item_id = '".res($quote_item_id)."'; ";
		$result = qedb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$quotes[] = $r;
			$quote += $r['quote'];
		}

		if ($return=='array') { return ($quotes); }
		else { return ($quote); }
	}
?>
