<?php
	$QTY_FILTER = '(need|qty[:]?)([[:space:]]*)?(-[[:space:]]*)?([0-9]+)(x|ea)?[+-]?';

	function format_qty($qty) {
		global $QTY_FILTER;

		$new_qty = preg_replace('/^'.$QTY_FILTER.'$/i','$4',str_replace(chr(0),'',trim($qty)));
//		if (! is_numeric($new_qty)) { $new_qty = false; }
		return ($new_qty);
	}

	function filter_qty($qtystr) {
		global $QTY_FILTER;

		$qty = false;
		// check if the qty for the previous row is set in this row, which is often the case in Frontier emails
//		echo 'qtystr:'.$qtystr.':'.preg_match('/(need|qty)([[:space:]]*)(-[[:space:]]*)?([0-9]+)/i',$qtystr).'<BR>';
		$matches = array();
		if (preg_match('/((need|qty)([[:space:]]*)(-[[:space:]]*)?([0-9]+))/i',$qtystr,$matches)) {
			$qty = preg_replace('/[\s\S]*(need|qty)([[:space:]]*)(-[[:space:]]*)?([0-9]+).*/i','$4',$qtystr);
//			print "<pre>".print_r($matches,true)."</pre>";
//			echo $qty.'<BR>';
//		if (preg_match('/(need|qty)([[:space:]]*)(-[[:space:]]*)?([0-9]*)/i',$qtystr)) {
//			$qty = preg_replace('/[\s\S]*(need|qty)([[:space:]]*)(-[[:space:]]*)?([0-9]*).*/i','$4',$qtystr);
			if (! is_numeric($qty)) { $qty = false; }
		}
		return ($qty);
	}
?>
