<?php
	function getMaterialsQuote($quote_item_id) {
		$quote = 0;
		$query = "SELECT * FROM service_quote_materials WHERE quote_item_id = '".res($quote_item_id)."'; ";
		$result = qedb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$quote += $r['quote'];
		}

		return ($quote);
	}
?>
