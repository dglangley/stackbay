<?php
	include_once $_SERVER["DOCUMENT_ROOT"].'/inc/dbconnect.php';
	include_once $_SERVER["DOCUMENT_ROOT"].'/inc/format_price.php';

	$PIPE = mysqli_connect('db.ven-tel.com', 'david', '33WbkcY6YBMs5cLWe7sD', 'inventory', '13306');
	if (mysqli_connect_errno($PIPE)) {
		echo "Failed to connect to MySQL: " . mysqli_connect_error();
	}
//	echo '<BR><BR><BR>';

	function pipe($search,$min_date=false) {
		$results = array();

		$query = "SELECT id, manufacturer_id_id manfid, part_number part, short_description description, ";
		$query .= "clei heci, quantity_stock qty, notes ";
		$query .= "FROM inventory_inventory WHERE part_number LIKE '".res($search,'PIPE')."%'; ";
		$result = qdb($query,'PIPE') OR die(qe('PIPE'));
		while ($r = mysqli_fetch_assoc($result)) {
//			print "<pre>".print_r($r,true)."</pre>";
			$invid = $r['id'];
			$key = $r['part'];
			if ($r['heci']) { $key .= '.'.$r['heci']; }

			$query2 = "SELECT date datetime, quantity qty, price, inventory_company.name FROM inventory_outgoing_quote, inventory_company ";
			$query2 .= "WHERE inventory_id = '".$invid."' AND inventory_outgoing_quote.company_id = inventory_company.id ";
			if ($min_date) { $query2 .= "AND date >= '".$min_date."' "; }
			$query2 .= "ORDER BY date ASC, inventory_outgoing_quote.id ASC; ";
//			echo $query2.'<BR>';
			$result2 = qdb($query2,'PIPE') OR die(qe('PIPE'));
			while ($r2 = mysqli_fetch_assoc($result2)) {
//				print "<pre>".print_r($r2,true)."</pre>";

				$r2['price'] = format_price($r2['price'],2);
				$results[] = $r2;
			}
		}
		return ($results);
	}

//	$search = 'NT5C07AC';
//	pipe($search);
?>
