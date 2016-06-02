<?php
	$arr1 = array('2016/05/04'=>2,'2016/05/03'=>4,'2016/05/02'=>6);
	$arr2 = array('2016/05/04'=>1,'2016/05/03'=>3,'2016/05/02'=>2);
	$arr3 = array('2016/05/04'=>2,'2016/05/03'=>7,'2016/05/02'=>8);

	function array_sum(&$new_arr,$arr1) {
		foreach ($arr as $date_key => $num_value) {
			// initialize date in new array with 0
			if (! isset($new_arr[$date_key])) { $new_arr[$date_key] = 0; }

			// add number for indexed element of array
			$new_arr[$date_key] += $num_value;
		}
		return ($new_arr);
	}

	$new_arr = array();
	array_sum($new_array,$arr1);
	array_sum($new_array,$arr2);
	array_sum($new_array,$arr3);
?>
