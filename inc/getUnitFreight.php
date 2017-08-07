<?php
	// this function calculates total number of pieces per package associated with item + order, and allocates
	// cost-per-unit in that item's package based on overall costs / number of pieces
	function getUnitFreight($inventoryid,$order_number,$order_type,$return_type='cost') {
		$freight_cost = 0;
		$pkgid = false;

		$query2 = "SELECT freight_amount, packageid FROM package_contents c, packages p ";
		$query2 .= "WHERE serialid = '".$inventoryid."' AND packageid = p.id ";
		$query2 .= "AND p.order_number = '".$order_number."' AND p.order_type = '".$order_type."'; ";
//		if ($GLOBALS['debug']) { echo $query2.'<BR>'; }
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		if (mysqli_num_rows($result2)>0) {
			$r2 = mysqli_fetch_assoc($result2);
			$freight_amount = $r2['freight_amount'];
			$pkgid = $r2['packageid'];

			// divide the freight amount on the package by the total number of pieces in the box
			$query2 = "SELECT id FROM package_contents c WHERE packageid = '".$pkgid."'; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			$pcs = mysqli_num_rows($result2);
			if ($pcs>0) {//should always be >0; um, hello, we're already inside the conditional statement above!
				$freight_cost = ($freight_amount/$pcs);
			}
		}

		if ($return_type=='array') {
			$freight = array('packageid'=>$pkgid,'cost'=>$freight_cost);
			return ($freight);
		} else {
			return ($freight_cost);
		}
	}
?>
