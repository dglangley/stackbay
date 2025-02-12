<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_phone.php';

	$DEBUG = 0;

	function updateContact($fieldname,$fieldvalue,$contactid,$id=0) {
		if ($fieldname=='phone') { $fieldvalue = format_phone($fieldvalue); }

		$type = '';//for now
		if(!empty($fieldvalue)) {
			$query = "REPLACE ".$fieldname."s (".$fieldname.", type, contactid";
			if ($id) { $query .= ", id"; }
			$query .= ") VALUES ('".$fieldvalue."',";
			if ($type) { $query .= "'".$type."',"; } else { $query .= "NULL,"; }
			$query .= "'".$contactid."'";
			if ($id) { $query .= ",'".$id."'"; }
			$query .= "); ";
			$result = qedb($query);

			if (! $id) { $id = qid(); }
		//If empty remove the contact email or phone...
		} else {
			$query = "DELETE FROM ".$fieldname."s ";
			$query .= "WHERE id = '".$id."'";
			$query .= "; ";
			$result = qedb($query);
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
			$result = qedb($query);
			if (! $id) { $id = qid(); }
			
			//Add the link from addressid to companyid
			$query = "REPLACE company_addresses (companyid, addressid, nickname, alias, contactid, code, notes) ";
			$query .= "VALUES ($companyid,$id, ".fres($nickname).", ".fres($alias).", ".fres($contactid).", ".fres($code).", ".fres($notes).");";
			$result = qedb($query);
		//If empty remove the address
		} else if ($id) {
			$query = "DELETE FROM addresses ";
			$query .= "WHERE id = '".$id."'";
			$query .= "; ";
			$result = qedb($query);
			
			//Delete it from Company Addresses too
			$query = "DELETE FROM company_addresses ";
			$query .= "WHERE addressid = '".$id."'";
			$query .= "; ";
			$result = qedb($query);
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
			$result = qedb($query);
			if (! $id) { $id = qid(); }
		//If empty remove the address
		} else {
			$query = "DELETE FROM freight_accounts ";
			$query .= "WHERE id = '".$id."'";
			$query .= "; ";
			$result = qedb($query);
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
	$default_email = false;
	$ebay = array();
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
	//Default RFQ email
	if (isset($_REQUEST['default_email'])) { $default_email = $_REQUEST['default_email']; }
	
	
	$msg = 'Success';
	if (!$companyid OR (!$name AND !$address_name)) {// AND !$accountids)) {
		$msg = 'Missing valid input data';
	//We are saving contacts here
	} else if($submit_type == 'contact') {
		//This section is for contacts saving
		//if (isset($_REQUEST['title'])) { $title = trim($_REQUEST['title']); }
		if (isset($_REQUEST['ebay']) AND is_array($_REQUEST['ebay'])) { $ebay = $_REQUEST['ebay']; }
		if (isset($_REQUEST['notes']) AND is_array($_REQUEST['notes'])) { $notes = $_REQUEST['notes']; }
		if (isset($_REQUEST['emails']) AND is_array($_REQUEST['emails'])) { $emails = $_REQUEST['emails']; }
		if (isset($_REQUEST['phones']) AND is_array($_REQUEST['phones'])) { $phones = $_REQUEST['phones']; }
		
		// is this valid input?
		$query = "SELECT id FROM companies WHERE id = '".$companyid."'; ";
		$result = qedb($query);
		if (mysqli_num_rows($result)==0) {
			$msg = 'Contact does not exist';
		}
		
		foreach($name as $contactid => $cname) {
			if(!empty($cname)) {
				$pointer;
				//An id of 0 signifies a new contact being created so create the user using $contactid and create the userid
				if($contactid == 0) {
					$contactid = setContact($cname,$companyid,$title,$notes[$contactid],$ebay[$contactid]);
				 	$pointer = 0;
				} else {
					updateContactName($cname,$companyid,$title,$notes[$contactid],$ebay[$contactid], $contactid);
					$pointer = $contactid;
				}

				foreach ($emails[$pointer] as $id => $email) {
					if($id != 0 || ($id == 0 && !empty($email)))
						$id = updateContact('email',$email,$contactid,$id);
				}

				foreach ($phones[$pointer] as $id => $phone) {
					if($id != 0 || ($id == 0 && !empty($phone)))
						$id = updateContact('phone',$phone,$contactid,$id);
				}
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
		$result = qedb($query);
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
		$result = qedb($query);
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

	$default_warrantyid = 0;
	if (isset($_REQUEST['default_warrantyid'])) { $default_warrantyid = $_REQUEST['default_warrantyid']; }

	$default_tax_rate = 0;
	if (isset($_REQUEST['default_tax_rate'])) { $default_tax_rate = $_REQUEST['default_tax_rate']; }

	//Checks if the company exists
	$query = "SELECT id FROM companies WHERE id = '".$companyid."'; ";
	$result = qedb($query);
	if (mysqli_num_rows($result)==0) {
		$msg = 'Company does not exist';
	} else {

		if($default_warrantyid OR $default_tax_rate OR $default_email!==false) {
			// Update the company default warranty
			$query2 = "UPDATE companies SET ";
			$inner_query = "";
			if($default_warrantyid) {
				$inner_query .= "default_warrantyid = '".res($default_warrantyid)."' ";
			}
			if($default_tax_rate) {
				if ($inner_query) { $inner_query .= ", "; }
				$inner_query .= "default_tax_rate = '".res($default_tax_rate)."' ";
			}
			if ($default_email!==false) {
				if ($inner_query) { $inner_query .= ", "; }
				$inner_query .= "default_email = ".fres($default_email)." ";
			}
			$query2 .= $inner_query."WHERE id=".res($companyid).";";
			qedb($query2);
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

	if ($DEBUG) { exit; }

	$tab = '';
	if (isset($_REQUEST['tab'])) { $tab = $_REQUEST['tab']; }
	header('Location: /company.php?companyid='.$companyid.'&tab='.$tab);
	exit;
