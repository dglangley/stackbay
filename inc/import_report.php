<?php
    include_once 'dbconnect.php';
    include_once 'simplexlsx.class.php';//awesome class used for xlsx-only
    include_once 'getPartId.php';
    include_once 'getCompany.php';
    include_once 'format_date.php';

	$companyid = getCompany("Verizon Telecom","name","id");

	$filename = "/var/tmp/revreport.xlsx";
	$file = file_get_contents($filename);

	// create temp file name in temp directory
	$tempfile = tempnam(false,'inv'.date("ymdHis"));
//	echo $tempfile.'<BR>';
	// add contents from file
	file_put_contents($tempfile,$file);

	$xlsx = new SimpleXLSX($tempfile);
	$lines = $xlsx->rows();

	foreach ($lines as $line) {
		$timestr = strtotime($line[0]);
		$datetime = date("Y-m-d 00:00:00",$timestr);
		$part = $line[1];
		$heci = $line[2];
		$qty = $line[3];
		$price = $line[4];
		$order = $line[5];

		$partid = getPartId($part,$heci);
		if (! $partid) { continue; }

		print "<pre>".print_r($line,true)."</pre>";

		$query = "SELECT id FROM sales_orders WHERE id = '".$order."'; ";
echo $query.'<BR>';
		$result = qdb($query) OR die(qe().' '.$query);
		if (mysqli_num_rows($result)==0) {
			$query = "INSERT INTO sales_orders (companyid, datetime, ref1, ref2, terms, id) ";
			$query .= "VALUES ('".$companyid."','".$datetime."',NULL,NULL,'Net 60','".$order."'); ";
			$result = qdb($query) OR die(qe().' '.$query);
echo $query.'<BR>';
		}

		$query = "INSERT INTO sales_items (sales_orderid, partid, qty, price) ";
		$query .= "VALUES ('".$order."','".$partid."','".$qty."','".$price."'); ";
		$result = qdb($query) OR die(qe().' '.$query);
echo $query.'<BR>';
	}

?>
