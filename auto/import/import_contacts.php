<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_phone.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setContact.php';
	
	//Temp array to hold Brian's data
	$contactsDataTemp = array();
	//New data to be imported into the database
	$contactsData = array();
	
	$query = "SELECT * FROM inventory_people ORDER BY id ASC; ";
	$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$contactsDataTemp[] = $r;
	}
	
	foreach($contactsDataTemp as $key => $value) {
		
		//trim the entire array
		$value = array_map('trim', $value);
		
		//If title == any of these then null it out
		if(strtolower($value['title']) == 'none' || strtolower($value['title']) == 'n/a' || strtolower($value['title']) == 'emailbot' || strtolower($value['title']) == 'mailbot') {
			$value['title'] = '';
		}
		
		//If AR is 1 then run this and check if a space is needed in the title
		if($value['ar']) {
			if($value['title'] == '') {
				$value['title'] = 'AR';
			} else {
				$value['title'] .= ' AR';
			}
		}
		unset($value['ar']);
		
		//If AP is 1 then run this and check if a space is needed in the title
		if($value['ap']) {
			if($value['title'] == '') {
				$value['title'] = 'AP';
			} else {
				$value['title'] .= ' AP';
			}
		}
		unset($value['ap']);
		
		//Cleaning value names to match the exact name
		$value['notes'] = $value['note'];
		unset($value['note']);
		
		//Translate the companyid to ours using David's awesome function dbTranslate()
		$value['companyid'] = dbTranslate($value['company_id']);
		//Renaming the column
		unset($value['company_id']);
		
		$value['aim'] = $value['im'];
		//Renaming the column
		unset($value['im']);
		
		//Changing hide to status instead
		if($value['hide'] == '' || $value['hide'] == '0' || $value['hide'] == 0) {
			$value['status'] = 'Active';
		} else {
			$value['status'] = 'Inactive';
		}
		unset($value['hide']);
		
		//Phone format here (If phone >14 then do not run the format... else run David's regex format)
		if(strlen($value['phone']) <= 14) {
			$value['phone'] = format_phone($value['phone']);
		}
		
		//Echo the new values for testing
		print_r($value);
		echo "<br>";
		
		//$contactsData[] = $value;
		
		$type = '';
		
		$contactid = 0;
		$emailid = 0;
		$phoneid = 0;
		
		//Check for Contactid
		$query2 = "SELECT * FROM contacts WHERE name = '".res($value['name'])."' AND companyid = ".res($value['companyid'])."; ";
		$result2 = qdb($query2) OR die(qe().' '.$query2);
		if (mysqli_num_rows($result2)>0) {
			$r2 = mysqli_fetch_assoc($result2);
			$contactid = $r2['id'];
		}
		
		$emailQuery = '';
		$phoneQuery = '';
		//Contactid must exists in order to find the phone
		if($contactid) {
			//Check for Emailid
			$emailQuery = "SELECT * FROM emails WHERE (email = '' OR email = '".res($value['email'])."') AND contactid = ".res($contactid)." LIMIT 1; ";
			$result2 = qdb($emailQuery) OR die(qe().' '.$emailQuery);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$emailid = $r2['id'];
			}
			
			//Check for Phoneid
			$phoneQuery = "SELECT * FROM phones WHERE (phone = '' OR phone = '".res($value['phone'])."') AND contactid = ".res($contactid)." LIMIT 1; ";
			$result2 = qdb($phoneQuery) OR die(qe().' '.$phoneQuery);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$phoneid = $r2['id'];
			}
		}
		
		echo 'Contact ID: ' . $contactid;
		echo "<br>";
		echo 'Email ID: ' . $emailid;
		echo "<br>";
		echo 'Phone ID: ' . $phoneid;
		echo "<br><br>";
		
		//This is a reference to setContact() Function
		//setContact($name,$companyid=0,$title='',$notes='',$ebayid='',$aim='',$status='Active')
		//Set contact gives us the contactid
		$contactid = setContact($value['name'],$value['companyid'],$value['title'],$value['notes'],'',$value['aim'],$value['status'], $contactid);
		
		// //Set the phone with contactid and type
		if($value['phone'] != '') {
			$phoneid = setPhone($value['phone'], $type, $contactid, $phoneid);
		}
		
		// //Set the email with contactid and type
		if($value['email'] != '') {
			$emailid = setEmail($value['email'], $type, $contactid, $emailid);
		}
	}
	
	function setPhone($phone, $type = '', $contactid, $phoneid = 0) {
		$phone = (string)$phone;
		$phone = trim($phone);
		
		$contactid = (int)$contactid;

		$query = "REPLACE phones (phone, type, contactid";
		if ($phoneid) { $query .= ", id"; }
		$query .= ") VALUES ('".res($phone)."',";
		if ($type) { $query .= "'".res($type)."',"; } else { $query .= "NULL,"; }
		$query .= " '".$contactid."'";
		if ($phoneid) { $query .= ",'".res($phoneid)."'"; }
		$query .= "); ";
		$result = qdb($query) OR die(qe().' '.$query);
		$phoneid = qid();

		return ($phoneid);
	}
	
	function setEmail($email, $type = '', $contactid, $emailid = 0) {
		$email = (string)$email;
		$email = trim($email);

		$contactid = (int)$contactid;

		$query = "REPLACE emails (email, type, contactid";
		if ($emailid) { $query .= ", id"; }
		$query .= ") VALUES ('".res($email)."',";
		if ($type) { $query .= "'".res($type)."',"; } else { $query .= "NULL,"; }
		$query .= " '".$contactid."'";
		if ($emailid) { $query .= ",'".res($emailid)."'"; }
		$query .= "); ";
		$result = qdb($query) OR die(qe().' '.$query);
		$emailid = qid();

		return ($emailid);
	}
?>
