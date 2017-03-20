<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setContact.php';

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
			$result = qdb($query) OR die(qe().' '.$query);
			if (! $id) { $id = qid(); }
		//If empty remove the contact email or phone...
		} else {
			$query = "DELETE FROM ".$fieldname."s ";
			$query .= "WHERE id = '".$id."'";
			$query .= "; ";
			$result = qdb($query) OR die(qe().' '.$query);
		}

		return ($id);
	}
	
	function updateAddress($name, $street, $add2 = '', $add3 = '', $city, $state, $postal, $country, $notes, $id=0, $companyid) {
		$type = '';//for now
		if(!empty($name)) {
			$query = "REPLACE addresses (name, street, addr2, addr3, city, state, postal_code, country, notes";
			if ($id) { $query .= ", id"; }
			$query .= ") VALUES ('".$name."','".$street."'";
			if ($add2) { $query .= ",'".$add2."'"; } else { $query .= ", NULL"; }
			if ($add3) { $query .= ",'".$add3."'"; } else { $query .= ", NULL"; }
			$query .= ",'".$city."','".$state."','".$postal."','".$country."','".res($notes)."'";
			if ($id) { $query .= ",'".$id."'"; }
			$query .= "); ";
			$result = qdb($query) OR die(qe().' '.$query);
			if (! $id) { $id = qid(); }
			
			//Add the link from addressid to companyid
			$query = "REPLACE company_addresses (companyid, addressid) VALUES ($companyid,$id);";
			$result = qdb($query) OR die(qe().' '.$query);
		//If empty remove the address
		} else {
			$query = "DELETE FROM addresses ";
			$query .= "WHERE id = '".$id."'";
			$query .= "; ";
			$result = qdb($query) OR die(qe().' '.$query);
			
			//Delete it from Company Addresses too
			$query = "DELETE FROM company_addresses ";
			$query .= "WHERE addressid = '".$id."'";
			$query .= "; ";
			$result = qdb($query) OR die(qe().' '.$query);
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
			$result = qdb($query) OR die(qe().' '.$query);
			if (! $id) { $id = qid(); }
		//If empty remove the address
		} else {
			$query = "DELETE FROM freight_accounts ";
			$query .= "WHERE id = '".$id."'";
			$query .= "; ";
			$result = qdb($query) OR die(qe().' '.$query);
		}
		return ($id);
	}
	
	//Variables used among all saves
	$companyid = 0;
	$submit_type = '';
	
	//Variables for contact
	$name = '';
	$title = '';
	$notes = '';
	$ebayid = '';
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
	if (!$companyid OR (!$name AND !$address_name AND !$accountids)) {
		$msg = 'Missing valid input data';
	//We are saving contacts here
	} else if($submit_type == 'contact') {
		//This section is for contacts saving
		//if (isset($_REQUEST['title'])) { $title = trim($_REQUEST['title']); }
		if (isset($_REQUEST['ebayid']) AND is_array($_REQUEST['ebayid'])) { $ebayid = $_REQUEST['ebayid']; }
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
					$contactid = setContact($cname,$companyid,$title,$notes[$contactid],$ebayid);
				 	$pointer = 0;
				} else {
					updateContactName($cname,$companyid,$title,$notes[$contactid],$ebayid, $contactid);
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
		if (isset($_REQUEST['address_street']) AND is_array($_REQUEST['address_street'])) { $address_street = $_REQUEST['address_street']; }
		if (isset($_REQUEST['address_city']) AND is_array($_REQUEST['address_city'])) { $address_city = $_REQUEST['address_city']; }
		if (isset($_REQUEST['address_state']) AND is_array($_REQUEST['address_state'])) { $address_state = $_REQUEST['address_state']; }
		if (isset($_REQUEST['address_postal']) AND is_array($_REQUEST['address_postal'])) { $address_postal = $_REQUEST['address_postal']; }
		if (isset($_REQUEST['address_notes']) AND is_array($_REQUEST['address_notes'])) { $address_notes = $_REQUEST['address_notes']; }
		
		//Checks if the company exists
		$query = "SELECT id FROM companies WHERE id = '".$companyid."'; ";
		$result = qdb($query);
		if (mysqli_num_rows($result)==0) {
			$msg = 'Contact does not exist';
		} else {
			foreach($address_name as $addressid => $address) {
				//if(!empty($address)) {
					updateAddress($address, $address_street[$addressid], $add2[$addressid], $add3[$addressid], $address_city[$addressid], $address_state[$addressid], $address_postal[$addressid], 'US', $address_notes[$addressid], $addressid, $companyid);
				//}
			}
		}
	//We are saving freight here
	} else if($submit_type == 'freight') {
		
		//Get the carrier ids
		if (isset($_REQUEST['carrier']) AND is_array($_REQUEST['carrier'])) { $carrierids = $_REQUEST['carrier']; }
		print_r($carrierids);
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

	header('Location: /profile.php?companyid='.$companyid);
	exit;
?>
