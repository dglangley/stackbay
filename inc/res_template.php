<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/download_res.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/parse_res.php';

	$API_ERROR = '';
	function api($search) {
		global $API_ERROR;

		// If no search then go through everything category
		if(! $search) {
			// Letters A-Z
			foreach (range('A', 'Z') as $letter) {
			   $res = download_res('',false,'https://www.resion.com/','res', $letter);
				if ($res===false) { return ($API_ERROR); }
				
				$resArray = parse_res($res, 'db');
			}

			// Numbers 0-9
			foreach (range(0, 9) as $number) {
			  	$res = download_res('',false,'https://www.resion.com/','res', $number);
				if ($res===false) { return ($API_ERROR); }
				
				$resArray = parse_res($res, 'db');
			}
		} else {
			$res = download_res('',false,'https://www.resion.com/','res');
			if ($res===false) { return ($API_ERROR); }
			
			$resArray = parse_res($res, 'db');
		}

		// $res = download_res($search,false,'https://www.resion.com/','res');
		// if ($res===false) { return ($API_ERROR); }

		// $resArray = parse_res($res);

		return false;
	}
?>
