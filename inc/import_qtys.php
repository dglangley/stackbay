<?php
    include_once 'dbconnect.php';
    include_once 'simplexlsx.class.php';//awesome class used for xlsx-only
    include_once 'getPartId.php';

	$filename = "/var/tmp/invexport.xlsx";
	$file = file_get_contents($filename);

	// create temp file name in temp directory
	$tempfile = tempnam(false,'inv'.date("ymdHis"));
//	echo $tempfile.'<BR>';
	// add contents from file
	file_put_contents($tempfile,$file);

	$xlsx = new SimpleXLSX($tempfile);
	$lines = $xlsx->rows();

	foreach ($lines as $line) {
		$part = $line[0];
		$heci = $line[1];
		$qty = $line[2];

		$partid = getPartId($part,$heci);
		if (! $partid) { continue; }

		print "<pre>".print_r($line,true)."</pre>";
		$query = "REPLACE qtys (partid, qty) VALUES ('".res($partid)."','".res($qty)."'); ";
		$result = qdb($query);
	}

?>
