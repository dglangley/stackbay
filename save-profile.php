<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setContact.php';

	$debug = 0;

	function updateContact($fieldname,$fieldvalue,$contactid,$id=0) {
		$type = '';//for now
		if(!empty($fieldvalue)) {
			$query = "REPLACE ".$fieldname."s (".$fieldname.", type, contactid";
			if ($id) { $query .= ", id"; }
			$query .= ") VALUES ('".$fieldvalue."',";
			if ($type) { $query .= "'".$type."',"; } else { $query .= "NULL,"; }
			$query .= "'".$contactid."'";
			if ($id) { $query .= ",'".$id."'"; }
			$query .= "); ";
			if ($GLOBALS['debug']) {
				echo $query.'<BR>';
			} else {
				$result = qdb($query) OR die(qe().' '.$query);
			}
			if (! $id) { $id = qid(); }
		//If empty remove the contact email or phone...
		} else {
			$query = "DELETE FROM ".$fieldname."s ";
			$query .= "WHERE id = '".$id."'";
			$query .= "; ";
			if ($GLOBALS['debug']) {
				echo $query.'<BR>';
			} else {
				$result = qdb($query) OR die(qe().' '.$query);
			}
		}

		return ($id);
	}
	
	function updateAddress($name, $street, $addr2 = '', $addr3 = '', $city, $state, $postal, $country, $nickname, $alias, $contactid, $code, $notes, $id=0, $companyid) {
		$type = '';//for now
		if(!empty($name)) {
			$query = "REPLACE addresses (name, street, addr2, addr3, city, state, postal_code, country, notes";
			if ($id) { $query .= ", id"; }
			$query .= ") VALUES ('".trim($name)."','".trim($street)."',".fres(trim($addr2)).",".fres(trim($addr3));
			$query .= ",'".trim($city)."','".trim($state)."',".fres(trim($postal)).",".fres(trim($country)).",".fres(trim($notes));
			if ($id) { $query .= ",'".$id."'"; }
			$query .= "); ";
			if ($GLOBALS['debug']) {
				echo $query.'<BR>';
			} else {
				$result = qdb($query) OR die(qe().' '.$query);
			}
			if (! $id) { $id = qid(); }
			
			//Add the link from addressid to companyid
			$query = "REPLACE company_addresses (companyid, addressid, nickname, alias, contactid, code, notes) ";
			$query .= "VALUES ($companyid,$id, ".fres($nickname).", ".fres($alias).", ".fres($contactid).", ".fres($code).", ".fres($notes).");";
			if ($GLOBALS['debug']) {
				echo $query.'<BR>';
			} else {
				$result = qdb($query) OR die(qe().' '.$query);
			}
		//If empty remove the address
		} else if ($id) {
			$query = "DELETE FROM addresses ";
			$query .= "WHERE id = '".$id."'";
			$query .= "; ";
			if ($GLOBALS['debug']) {
				echo $query.'<BR>';
			} else {
				$result = qdb($query) OR die(qe().' '.$query);
			}
			
			//Delete it from Company Addresses too
			$query = "DELETE FROM company_addresses ";
			$query .= "WHERE addressid = '".$id."'";
			$query .= "; ";
			if ($GLOBALS['debug']) {
				echo $query.'<BR>';
			} else {
				$result = qdb($query) OR die(qe().' '.$query);
			}
		}

		return ($id);
	}
	
	function updateFreight($name, $carrierid, $id=0, $companyid) {
		$type = '';//for now
		if(!empty($name)) {
			$query = "REPLACE freight_accounts (account_no, carrierid, companyid";
			if ($id) { $query .= ", id"; }
			$query .= ") VALUES ('".$name."','".$carrierid."','".$companyid."'";
			if ($id) { $query .= ",'".$id."'"; }
			$query .= "); ";
			if ($GLOBALS['debug']) {
				echo $query.'<BR>';
			} else {
				$result = qdb($query) OR die(qe().' '.$query);
			}
			if (! $id) { $id = qid(); }
		//If empty remove the address
		} else {
			$query = "DELETE FROM freight_accounts ";
			$query .= "WHERE id = '".$id."'";
			$query .= "; ";
			if ($GLOBALS['debug']) {
				echo $query.'<BR>';
			} else {
				$result = qdb($query) OR die(qe().' '.$query);
			}
		}
		return ($id);
	}
	
	//Variables used among all saves
	$companyid = setCompany();
	$submit_type = '';
	
	//Variables for contact
	$name = '';
	$title = '';
	$notes = '';
	$im = array();
	$emails = array();
	$phones = array();
	
	//Variables for address
	$address_name = array();
	$address_street = array();
	$address_city = array();
	$address_state = array();
	$address_postal = array();
	$address_notes = array();
	
	//Variables for freight
	$accountnames = array();
	$carrierids = array();
	
	//Get Company id
	if (isset($_REQUEST['companyid']) AND is_numeric($_REQUEST['companyid']) AND $_REQUEST['companyid']>0) {
		$companyid = $_REQUEST['companyid'];
	}
	
	//Get submit type IMPORTANT
	if (isset($_REQUEST['submit'])) { $submit_type = $_REQUEST['submit']; }
	
	//Get base needed items (**this is a hack to make the first if statement work)
	//Name
	if (isset($_REQUEST['name'])) { $name = $_REQUEST['name']; }
	//Contact
	if (isset($_REQUEST['address_name']) AND is_array($_REQUEST['address_name'])) { $address_name = $_REQUEST['address_name']; }
	//Freight
	if (isset($_REQUEST['account_number']) AND is_array($_REQUEST['account_number'])) { $accountnames = $_REQUEST['account_number']; }
	
	
	$msg = 'Success';
	if (!$companyid OR (!$name AND !$address_name)) {// AND !$accountids)) {
		$msg = 'Missing valid input data';
	//We are saving contacts here
	} else if($submit_type == 'contact') {
		//This section is for contacts saving
		//if (isset($_REQUEST['title'])) { $title = trim($_REQUEST['title']); }
		if (isset($_REQUEST['im']) AND is_array($_REQUEST['im'])) { $im = $_REQUEST['im']; }
		if (isset($_REQUEST['notes']) AND is_array($_REQUEST['notes'])) { $notes = $_REQUEST['notes']; }
		if (isset($_REQUEST['emails']) AND is_array($_REQUEST['emails'])) { $emails = $_REQUEST['emails']; }
		if (isset($_REQUEST['phones']) AND is_array($_REQUEST['phones'])) { $phones = $_REQUEST['phones']; }
		
		// is this valid input?
		$query = "SELECT id FROM companies WHERE id = '".$companyid."'; ";
		$result = qdb($query);
		if (mysqli_num_rows($result)==0) {
			$msg = 'Contact does not exist';
		}
		
		foreach($name as $contactid => $cname) {
			if(!empty($cname)) {
				$pointer;
				//An id of 0 signifies a new contact being created so create the user using $contactid and create the userid
				if($contactid == 0) {
					$contactid = setContact($cname,$companyid,$title,$notes[$contactid],$im[$contactid]);
				 	$pointer = 0;
				} else {
					updateContactName($cname,$companyid,$title,$notes[$contactid],$im[$contactid], $contactid);
					$pointer = $contactid;
				}
				
				//echo $contactid . " " . $cname . ' ';
				//print_r($emails[$contactid]);
				//print_r($phones[$contactid]);
				
				foreach ($emails[$pointer] as $id => $email) {
					if($id != 0 || ($id == 0 && !empty($email)))
						$id = updateContact('email',$email,$contactid,$id);
				}
				
				foreach ($phones[$pointer] as $id => $phone) {
					//echo '<br>Number: ' . $id . '  '. $phone . '<br>';
					if($id != 0 || ($id == 0 && !empty($phone)))
						$id = updateContact('phone',$phone,$contactid,$id);
				}
				// "<br><br>";
			}
		}
		//die;
	//We are saving addresses here
	} else if($submit_type == 'address') {
		
		//This section is for Address saving feature
		if (isset($_REQUEST['address_street']) AND is_array($_REQUEST['address_street'])) { $street = $_REQUEST['address_street']; }
		if (isset($_REQUEST['address_addr2']) AND is_array($_REQUEST['address_addr2'])) { $addr2 = $_REQUEST['address_addr2']; }
		if (isset($_REQUEST['address_city']) AND is_array($_REQUEST['address_city'])) { $city = $_REQUEST['address_city']; }
		if (isset($_REQUEST['address_state']) AND is_array($_REQUEST['address_state'])) { $state = $_REQUEST['address_state']; }
		if (isset($_REQUEST['address_postal']) AND is_array($_REQUEST['address_postal'])) { $postal = $_REQUEST['address_postal']; }
		if (isset($_REQUEST['address_country']) AND is_array($_REQUEST['address_country'])) { $country = $_REQUEST['address_country']; }
		if (isset($_REQUEST['address_nickname']) AND is_array($_REQUEST['address_nickname'])) { $nickname = $_REQUEST['address_nickname']; }
		if (isset($_REQUEST['address_alias']) AND is_array($_REQUEST['address_alias'])) { $alias = $_REQUEST['address_alias']; }
		if (isset($_REQUEST['address_contactid']) AND is_array($_REQUEST['address_contactid'])) { $contactid = $_REQUEST['address_contactid']; }
		if (isset($_REQUEST['address_code']) AND is_array($_REQUEST['address_code'])) { $code = $_REQUEST['address_code']; }
		if (isset($_REQUEST['address_notes']) AND is_array($_REQUEST['address_notes'])) { $notes = $_REQUEST['address_notes']; }

		//Checks if the company exists
		$query = "SELECT id FROM companies WHERE id = '".$companyid."'; ";
		$result = qdb($query);
		if (mysqli_num_rows($result)==0) {
			$msg = 'Company does not exist';
		} else {
			foreach($address_name as $id => $company_name) {
				$addr3 = false;
				updateAddress($company_name, $street[$id], $addr2[$id], $addr3, $city[$id], $state[$id], $postal[$id], $country[$id], $nickname[$id], $alias[$id], $contactid[$id], $code[$id], $notes[$id], $id, $companyid);
			}
		}
	//We are saving freight here
	} else if($submit_type == 'freight') {
		
		//Get the carrier ids
		if (isset($_REQUEST['carrier']) AND is_array($_REQUEST['carrier'])) { $carrierids = $_REQUEST['carrier']; }
		//Checks if the company exists
		$query = "SELECT id FROM companies WHERE id = '".$companyid."'; ";
		$result = qdb($query);
		if (mysqli_num_rows($result)==0) {
			$msg = 'Contact does not exist';
		} else {
			foreach($accountnames as $accountid => $name) {
				if(!$accountid == 0) {
					updateFreight($name, $carrierids[$accountid], $accountid, $companyid);
				} else if(!empty($name)) {
					updateFreight($name, $carrierids[$accountid], $accountid, $companyid);
				}
			}
		}
	}

	if (isset($_FILES) AND count($_FILES)>0 AND $_SERVER['REQUEST_METHOD'] == 'POST' AND isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] == UPLOAD_ERR_OK && is_uploaded_file($_FILES['file_upload']['tmp_name'])) {
		$file = $_FILES['file_upload'];
		$file_contents = file_get_contents($file['tmp_name']);

		if ($file['type']=='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {//xlsx
			$file_ending = 'xlsx';
		} else if ($file['type']=='application/vnd.ms-excel') {//xls
			$file_ending = 'xls';
		} else if ($file['type']=='text/csv') {//csv
			$file_ending = 'csv';
		}

		// create temp file name in temp directory
		$tempfile = 'addrfile'.date("ymdHis").'.'.$file_ending;

		// add contents from file
		file_put_contents($TEMP_DIR.$tempfile,$file_contents);

		header('Location: address_uploader.php?companyid='.$companyid.'&filename='.$tempfile);
		exit;
	}

	if ($debug) { exit; }

	$tab = '';
	if (isset($_REQUEST['tab'])) { $tab = $_REQUEST['tab']; }
	header('Location: /profile.php?companyid='.$companyid.'&tab='.$tab);
	exit;
