<?php
	header("Content-Type: application/json", true);
	include_once '../inc/dbconnect.php';
	include_once '../inc/format_address.php';
	include_once '../inc/jsonDie.php';

	$addressid = 0;
	if (isset($_REQUEST['addressid'])) { $addressid = trim($_REQUEST['addressid']); }
	$companyid = 0;
	if (isset($_REQUEST['companyid'])) { $companyid = trim($_REQUEST['companyid']); }

	$addr3 = '';
	$country = 'US';
	$notes = '';

	// for now while we don't edit these fields, get them for retaining existing values
	if ($addressid) {
		$query = "SELECT addr3, country, notes FROM addresses WHERE id = '".res($addressid)."'; ";
		$result = qdb($query) OR jsonDie(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$addr3 = $r['addr3'];
			if ($r['country']) { $country = $r['country']; }
			$notes = $r['notes'];
		}
	}

	$name = '';
	if (isset($_REQUEST['name'])) { $name = trim($_REQUEST['name']); }
	$street = '';
	if (isset($_REQUEST['street'])) { $street = trim($_REQUEST['street']); }
	$addr2 = '';
	if (isset($_REQUEST['addr2'])) { $addr2 = trim($_REQUEST['addr2']); }
	$city = '';
	if (isset($_REQUEST['city'])) { $city = ucfirst(trim($_REQUEST['city'])); }
	$state = '';
	if (isset($_REQUEST['state'])) { $state = strtoupper(trim($_REQUEST['state'])); }
	$postal_code = '';
	if (isset($_REQUEST['postal_code'])) { $postal_code = trim($_REQUEST['postal_code']); }

	$query = "REPLACE addresses (name, street, addr2, addr3, city, state, postal_code, country, notes";
	if ($addressid) { $query .= ", id"; }
	$query .= ") VALUES (".fres($name).", ".fres($street).", ".fres($addr2).", ".fres($addr3).", ";
	$query .= fres($city).", ".fres($state).", ".fres($postal_code).", ".fres($country).", ".fres($notes);
	if ($addressid) { $query .= ", '".res($addressid)."'"; }
	$query .= "); ";
	$result = qdb($query) OR jsonDie(qe().'<BR>'.$query);
	$addressid = qid();

	$query = "REPLACE company_addresses (companyid, addressid) ";
	$query .= "VALUES ('".res($companyid)."', '".res($addressid)."'); ";
	$result = qdb($query) OR jsonDie(qe().'<BR>'.$query);

	$address = format_address($addressid,', ',false);

	echo json_encode(array('id'=>$addressid,'text'=>$address));
	exit;
?>
