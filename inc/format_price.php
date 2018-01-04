<?php
	function format_price($price,$decimal4=true,$sep='',$dbformat=false) {
		//$decimal4 = show full price extension out to 4 decimal places
		//$sep = separator between "$" and dollar value
		//$dbformat = output in db format

		$price = trim($price);
		$price = str_replace(array('$',','),'',$price);

		$fprice = $price;//'';
		//if ($price>0 AND preg_match('/^[0-9]+([.][0-9]+)?$/',$price)) {
		if (preg_match('/^[-]?[0-9]+([.][0-9]+)?$/',$price)) {
			if ($dbformat) {
				// truncate to 2-decimals so long as it's not using 4-decimals
				if (round($price,2)<>round($price,4)) {
					$fprice = $sep.number_format($price,4,'.','');
				} else {
					$fprice = $sep.number_format($price,2,'.','');
				}
			} else if ($decimal4===false AND $price==round($price)) {//show truncated format without decimals, if none
				$fprice = '$'.$sep.number_format($price,0,'.','');
			} else {
				// truncate to 2-decimals so long as it's not using 4-decimals
				if (round($price,2)<>round($price,4)) {
					$fprice = '$'.$sep.number_format($price,4);
				} else {
					$fprice = '$'.$sep.number_format($price,2);
				}
			}
		}
		return ($fprice);
	}
?>
