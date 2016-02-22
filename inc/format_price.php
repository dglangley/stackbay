<?php
	function format_price($price,$ext=true,$sep='') {
		$fprice = '';
		if ($price>0) {
			if ($ext===false AND $price==round($price)) {//show truncated format without decimals, if none
				$fprice = '$'.$sep.number_format($price,0,'.','');
			} else {
				$fprice = '$'.$sep.number_format($price,2);
			}
		}
		return ($fprice);
	}
?>
