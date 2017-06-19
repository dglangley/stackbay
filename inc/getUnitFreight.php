<?php
	function getUnitFreight($inventoryid,$order_number,$order_type) {
		$freight_cost = 0;

		$query2 = "SELECT freight_amount, packageid FROM package_contents c, packages p ";
		$query2 .= "WHERE serialid = '".$inventoryid."' AND packageid = p.id ";
		$query2 .= "AND p.order_number = '".$order_number."' AND p.order_type = '".$order_type."'; ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		if (mysqli_fetch_assoc($result2)>0) {
			$r2 = mysqli_fetch_assoc($result2);
			$freight_amount = $r2['freight_amount'];

			// divide the freight amount on the package by the total number of pieces in the box
			$query2 = "SELECT id FROM package_contents c WHERE packageid = '".$r2['packageid']."'; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			$pcs = mysqli_num_rows($result2);
			if ($pcs>0) {//should always be >0; um, hello, we're already inside the conditional statement above!
				$freight_cost = ($freight_amount/$pcs);
			}
		}
		return ($freight_cost);
	}
?>
