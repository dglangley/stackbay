<?php
	function format_price($price,$ext=true,$sep='',$dbformat=false) {
		$price = trim($price);
		$price = str_replace(array('$',','),'',$price);

		$fprice = $price;//'';
		if ($price>0 AND preg_match('/^[0-9]+([.][0-9]+)?$/',$price)) {
			if ($dbformat) {
				$fprice = $sep.number_format($price,2,'.','');
			} else if ($ext===false AND $price==round($price)) {//show truncated format without decimals, if none
				$fprice = '$'.$sep.number_format($price,0,'.','');
			} else {
				$fprice = '$'.$sep.number_format($price,2);
			}
		}
		return ($fprice);
	}
?>
