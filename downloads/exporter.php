<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getQty.php';

	header("Content-type: text/csv; charset=utf-8");
	header("Cache-Control: no-store, no-cache");
	header('Content-Disposition: attachment; filename="inventory-export-'.$today.'.csv"');

	$partids = array();
	if (isset($_REQUEST['partids'])) { $partids = $_REQUEST['partids']; }

	$outstream = fopen("php://output",'w');

	$header = array('Qty','Part','HECI','Aliases','Manf','System','Description');
	fputcsv($outstream, $header, ',', '"');

	foreach ($partids as $partid) {
		$H = hecidb($partid,'id');
		$part_strs = explode(' ',$H[$partid]['part']);
		$aliases = '';
		foreach ($part_strs as $i => $str) {
			if ($i==0) { continue; }

			if ($aliases) { $aliases .= ' '; }
			$aliases .= $str;
		}

		$row = array(
			getQty($partid),
			$part_strs[0],
			$H[$partid]['heci'],
			$aliases,
			$H[$partid]['manf'],
			$H[$partid]['system'],
			$H[$partid]['description'],
		);
		fputcsv($outstream, $row, ',', '"');
	}

	fclose($outstream);
	exit;
?>
