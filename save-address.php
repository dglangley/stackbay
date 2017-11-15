<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getSpreadsheetRows.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_phone.php';

	$debug = 0;

	function setPhone($contactid,$phone) {
		if (! $phone) { return; }

		$query = "SELECT * FROM phones WHERE phone = '".res($phone)."' AND contactid = '".res($contactid)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) {
			$query = "INSERT INTO phones (phone, contactid) VALUES ('".res($phone)."','".res($contactid)."'); ";
			if ($GLOBALS['debug']) { echo $query.'<BR>'; }
			else { $result = qdb($query) OR die(qe().'<BR>'.$query); }
		}
	}

	function setEmail($contactid,$email) {
		if (! $email) { return; }

		$query = "SELECT * FROM emails WHERE email = '".res($email)."' AND contactid = '".res($contactid)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) {
			$query = "INSERT INTO emails (email, contactid) VALUES ('".res(strtolower($email))."','".res($contactid)."'); ";
			if ($GLOBALS['debug']) { echo $query.'<BR>'; }
			else { $result = qdb($query) OR die(qe().'<BR>'.$query); }
		}
	}

//	print "<pre>".print_r($_REQUEST,true)."</pre>";
	$companyid = false;
	if (isset($_REQUEST['companyid'])) { $companyid = $_REQUEST['companyid']; }
	$filename = false;
	if (isset($_REQUEST['filename'])) { $filename = $_REQUEST['filename']; }
	$company_name = false;
	if (isset($_REQUEST['company_name'])) { $company_name = $_REQUEST['company_name']; }
	$street = false;
	if (isset($_REQUEST['street'])) { $street = $_REQUEST['street']; }
	$addr2 = false;
	if (isset($_REQUEST['addr2'])) { $addr2 = $_REQUEST['addr2']; }
	$city = false;
	if (isset($_REQUEST['city'])) { $city = $_REQUEST['city']; }
	$state = false;
	if (isset($_REQUEST['state'])) { $state = $_REQUEST['state']; }
	$postal_code = false;
	if (isset($_REQUEST['postal_code'])) { $postal_code = $_REQUEST['postal_code']; }
	$country = false;
	if (isset($_REQUEST['country'])) { $country = $_REQUEST['country']; }
	$nickname = false;
	if (isset($_REQUEST['nickname'])) { $nickname = $_REQUEST['nickname']; }
	$alias = false;
	if (isset($_REQUEST['alias'])) { $alias = $_REQUEST['alias']; }
	$contactid = false;
	if (isset($_REQUEST['contactid'])) { $contactid = $_REQUEST['contactid']; }
	$code = false;
	if (isset($_REQUEST['code'])) { $code = $_REQUEST['code']; }
	$notes = false;
	if (isset($_REQUEST['notes'])) { $notes = $_REQUEST['notes']; }

	$lines = getSpreadsheetRows($TEMP_DIR.$filename);

	foreach ($lines as $i => $line) {
		if ($i==0) { continue; }//skip header row

		$n = count($line);

		$name = $line[$company_name];
		if (! $name) { $name = getCompany($companyid); }
		$street_str = trim(ucwords(strtolower($line[$street])));
		$addr2_str = trim(ucwords(strtolower($line[$addr2])));
		$city_str = trim(ucwords(strtolower($line[$city])));
		$state_str = trim(strtoupper($line[$state]));
		$postal_str = trim(strtoupper($line[$postal_code]));
		$ctry = $line[$country];
		if (! $ctry) { $ctry = 'US'; }

		if (! $street_str AND ! $city_str AND ! $state_str AND ! $postal_str) { continue; }

		$addressid = 0;
		$query = "SELECT id FROM addresses ";
		$query .= "WHERE name = ".fres($name)." ";
		$query .= "AND street ".($street_str ? '=' : 'IS')." ".fres($street_str)." ";
		$query .= "AND addr2 ".($addr2_str ? '=' : 'IS')." ".fres($addr2_str)." ";
		$query .= "AND city ".($city_str ? '=' : 'IS')." ".fres($city_str)." ";
		$query .= "AND state ".($state_str ? '=' : 'IS')." ".fres($state_str)." ";
		$query .= "AND postal_code ".($postal_str ? '=' : 'IS')." ".fres($postal_str)." ";
		$query .= "AND country ".($ctry ? '=' : 'IS')." ".fres($ctry)." ";
		$query .= "; ";
		if ($debug) { echo $query.'<BR>'; }
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$addressid = $r['id'];
		} else {
			$query = "INSERT INTO addresses (name, street, addr2, city, state, postal_code, country) ";
			$query .= "VALUES (".fres($name).", ".fres($street_str).", ".fres($addr2_str).", ";
			$query .= fres($city_str).", ".fres($state_str).", ".fres($postal_str).", ".fres($ctry)."); ";
			if ($GLOBALS['debug']) { echo $query.'<BR>'; }
			else { $result = qdb($query) OR die(qe().'<BR>'.$query); }
			$addressid = qid();
		}

		$site_nickname = $line[$nickname];//nickname
		$site_alias = $line[$alias];//alias
		$site_contact = $line[$contactid];//contactid
		$site_code = strtoupper($line[$code]);
		$site_notes = trim($line[$notes]);

		$ca_id = 0;
		$query = "SELECT * FROM company_addresses WHERE companyid = '".res($companyid)."' AND addressid = '".$addressid."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$ca_id = $r['id'];
		}

		if ($site_contact) {
			if (strstr($site_contact,',')) {
				$contact_names = explode(',',$site_contact);
				$site_contact = trim($contact_names[1].' '.$contact_names[0]);
			}
			$site_contact = trim($site_contact);

			$site_contactid = 0;
			$query = "SELECT * FROM contacts WHERE name = '".res($site_contact)."' AND companyid = '".res($companyid)."'; ";
			$result = qdb($query) OR die(qe().'<BR>'.$query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$site_contactid = $r['id'];
			}

			$site_contactid = setContact($site_contact,$companyid,'','','','','Active',$site_contactid);

			// follow ensuing fields in spreadsheet table to find phone numbers and emails until we no longer find a match
			for ($j=($contactid+1); $j<$n; $j++) {
				$str = trim($line[$j]);
				// is a phone number field?
				$phone = format_phone($str);
				if ($phone) {
					setPhone($site_contactid,$phone);
				} else if (filter_var($str, FILTER_VALIDATE_EMAIL)) {
					// is an email field?
					$email = $str;
					setEmail($site_contactid,$email);
				} else if ($str) {
					// as soon as we hit a field with content that doesn't conform to phone or email, we're out. PEACE!
					break;
				}
			}
		}

		$query = "REPLACE company_addresses (companyid, addressid, nickname, alias, contactid, code, notes) ";
		$query .= "VALUES ('".res($companyid)."', '".res($addressid)."', ".fres($site_nickname).", ";
		$query .= fres($site_alias).", ".fres($site_contactid).", ".fres($site_code).", ".fres($site_notes)."); ";
		if ($GLOBALS['debug']) { echo $query.'<BR>'; }
		else { $result = qdb($query) OR die(qe().'<BR>'.$query); }
	}

	if ($debug) { exit; }

	header('Location: /profile.php?companyid='.$companyid.'&tab=addresses&update=1');
	exit;
?>
