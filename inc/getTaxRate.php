<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	function getTaxRate($cid){
		$tax = (double)"7.75";//default for all companies unless something is entered below

		if (! $cid) { return ($tax); }

		$query = "SELECT default_tax_rate FROM companies WHERE id = ".res($cid).";";
		$result = qedb($query);

		if(qnum($result)) {
			$r = qrow($result);

			if ($r['default_tax_rate']) {
				$tax = $r['default_tax_rate'];
			}
		}

		return $tax;
	}
