<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
exit;

	$masterid = 211;
	$slaveid = 1126;

	$query = "SELECT name FROM companies WHERE id = '".$masterid."'; ";
	$result = qdb($query) OR die(qe().' '.$query);
	if (mysqli_num_rows($result)==0) { die("No master"); }
	$r = mysqli_fetch_assoc($result);
	$mastername = $r['name'];

	$query = "SELECT name FROM companies WHERE id = '".$slaveid."'; ";
	$result = qdb($query) OR die(qe().' '.$query);
	if (mysqli_num_rows($result)==0) { die("No slave"); }
	$r = mysqli_fetch_assoc($result);
	$slavename = $r['name'];

	$query = "SELECT * FROM company_aliases WHERE companyid = '".$slaveid."'; ";
	$result = qdb($query) OR die(qe().' '.$query);
	if (mysqli_num_rows($result)>0) {
		$query = "UPDATE company_aliases SET companyid = '".$masterid."' WHERE companyid = '".$slaveid."'; ";
echo $query.'<BR>';
		$result = qdb($query) OR die(qe().' '.$query);
	}

	$query = "SELECT * FROM company_aliases WHERE name = '".res($slavename)."' AND companyid = '".$masterid."'; ";
	$result = qdb($query) OR die(qe().' '.$query);
	// no alias exists, so merge
	if (mysqli_num_rows($result)==0) {
		$query = "INSERT INTO company_aliases (name, companyid, notes) VALUES ('".res($slavename)."','".$masterid."','Added from db 1.0, 7/21/16'); ";
		$result = qdb($query) OR die(qe().' '.$query);
echo $query.'<BR>';
	}

	$query = "UPDATE market SET companyid = '".$masterid."' WHERE companyid = '".$slaveid."'; ";
	$result = qdb($query) OR die(qe().' '.$query);
echo $query.'<BR>';
	$query = "UPDATE search_meta SET companyid = '".$masterid."' WHERE companyid = '".$slaveid."'; ";
	$result = qdb($query) OR die(qe().' '.$query);
echo $query.'<BR>';
	$query = "DELETE FROM companies WHERE id = '".$slaveid."'; ";
	$result = qdb($query) OR die(qe().' '.$query);
echo $query.'<BR>';
	$query = "SELECT inventory_companyid FROM company_maps WHERE companyid = '".$slaveid."'; ";
	$result = qdb($query) OR die(qe().' '.$query);
	if (mysqli_num_rows($result)>0) {
		$query = "UPDATE company_maps SET companyid = '".$masterid."' WHERE companyid = '".$slaveid."'; ";
		$result = qdb($query) OR die(qe().' '.$query);
echo $query.'<BR>';
	}
?>
