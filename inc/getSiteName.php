<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	function getSiteName($companyid, $addressid) {
		$sitename = '';

		$query = "SELECT * FROM company_addresses WHERE companyid = ".fres($companyid)." AND addressid = ".fres($addressid).";";
		$result = qedb($query);

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);

			if($r['nickname'])
				$sitename = $r['nickname'];
		}

		return $sitename;
	}