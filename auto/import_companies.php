<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_phone.php';
	//include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';

	//Temp array to hold Brian's data
	$companyHolder = array();
	
	//For testing purposes and compile time adding LIMIT 30
	$query = "SELECT * FROM inventory_company ORDER BY id ASC; ";
	$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$companyHolder[] = $r;
	}
	
	foreach ($companyHolder as $key => $value) {
		//Reset this to hold the current comapny aliases
		$companyaliases = array();
		$aliasSwap = false;
		
		//This holds the current root company in 'our' database
		$rootCompanyID = 0;
		
		//$value contains the root company information on Brian's Database
		$value = array_map('trim', $value);
		
		//Query to get Alias
		$query = "SELECT * FROM inventory_companyalias WHERE company_id = ".$value['id']." ORDER BY id ASC; ";
		$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
		
		while ($r = mysqli_fetch_assoc($result)) {
			//Filter out duplicate company names in Brian's system
			if(strtolower(trim($r['alias'])) != strtolower($value['name'])) {
				$companyaliases[] = $r;
			}
		}
		
		echo $value['name'] . '<br>';
		if($companyaliases) {
			echo '<b>Company has Aliases</b><br>';
		}
		
		//Check if the company exists already in the database
		$query2 = "SELECT * FROM companies WHERE LOWER(name) = '".res(strtolower($value['name']))."'; ";
		$result2 = qdb($query2) OR die(qe().' '.$query2);
		if (mysqli_num_rows($result2)>0) {
			$r = mysqli_fetch_assoc($result2);
			$rootCompanyID = $r['id'];
			echo '<b>Company ID:</b> '.$rootCompanyID.'<br>';
		} else if($companyaliases) {
			echo '<b>DOES NOT EXIST SEARCHING ALIAS</b><br>';
			//First level does not exists time to check Alias level if they exist or not
			foreach($companyaliases as $holder => $alias ) {
				$query2 = "SELECT * FROM companies WHERE LOWER(name) = '".res(strtolower($alias['alias']))."'; ";
				$result2 = qdb($query2) OR die(qe().' '.$query2);
				//Check if the alias name exists in the company database
				if (mysqli_num_rows($result2)>0) {
					$r = mysqli_fetch_assoc($result2);
					$rootCompanyID = $r['id'];
					
					$aliasSwap = true;
					
					//Shift the values around making the alias the root company name and pushing the root down to alias name
					$tempHolder = $value['name'];
					$value['name'] = $alias['alias'];
					$companyaliases[$holder]['alias'] = $tempHolder;
					
					echo '<b>ALIAS: </b>"'.$alias['alias'] . '" <b>EXISTS IN DB AS Company ID:</b>'.$rootCompanyID.'<br>';
					break;
				} else {
					echo '<b>ALIAS:</b> "'.$alias['alias'].'" <b>DOES NOT EXIST IN DB</b><br>';
				}
			}
		} else {
			echo '<b>New Company Found and being added to Database</b><br>';
		}
		
		if($aliasSwap) {
			echo '<b>Swap Occured: New Data Below</b><br>';
			print_r($value);
			echo '<br>';
		}
		if($companyaliases) {
			print_r($companyaliases);
			echo '<br>';
		}
		
		if(strtolower($value['website']) == 'none' || strtolower($value['website']) == 'n/a' || strtolower($value['website']) == 'mailbot generated') {
			$value['website'] = '';
		}
		
		//Phone format here (If phone >14 then do not run the format... else run David's regex format)
		if(strlen($value['phone']) <= 14) {
			$value['phone'] = format_phone($value['phone']);
		}
		
		$companyID = setCompany($value['name'], $value['website'], $value['phone'], $coporateid = null, $value['email'], $value['notes'], $rootCompanyID);
		$aliasID = 0;
		
		//Just in case we will check if companyid exists over a 0
		if($companyID) {
			foreach($companyaliases as $alias) {
				 //Check for Contactid
				$query2 = "SELECT * FROM company_aliases WHERE LOWER(name) = '".res(strtolower($alias['alias']))."' AND companyid = ".res($companyID)."; ";
				$result2 = qdb($query2) OR die(qe().' '.$query2);
				if (mysqli_num_rows($result2)>0) {
					$r2 = mysqli_fetch_assoc($result2);
					$aliasID = $r2['id'];
					echo $alias['alias'] . " <b>Exists in company_aliases so we are going to update the record instead.</b><br>";
				} else {
					echo $alias['alias'] . " <b>New Alias being added for company ".$companyID."</b><br>";
				}
				
				setCompanyAlias($alias['alias'], $companyID, $aliasID);
			}
		}
		
		echo '<br><br>';
	}
	
	//Creating another setCompany so we can update data to the latest
	function setCompany($name, $website, $phone, $coporateid = null, $default_email, $notes, $id = 0) {
		$name = (string)$name;
		$name = trim($name);
		
		$website = (string)$website;
		$website = trim($website);
		
		$phone = (string)$phone;
		$phone = trim($phone);
		
		$default_email = (string)$default_email;
		$default_email = trim($default_email);
		
		$notes = (string)$notes;
		$notes = trim($notes);

		$query = "REPLACE companies (name, website, phone, corporateid, default_email, notes";
		if ($id) { $query .= ", id"; }
		$query .= ") VALUES ('".res($name)."',";
		$query .= " '".res($website)."'";
		$query .= ", '".res($phone)."'";
		$query .= ", NULL";
		$query .= ", '".res($default_email)."'";
		$query .= ", '".res($notes)."'";
		if ($id) { $query .= ",'".res($id)."'"; }
		$query .= "); ";
		$result = qdb($query) OR die(qe().' '.$query);
		if (!$id) {$id = qid();}

		return ($id);
	}
	
	function setCompanyAlias($name, $companyid, $id = 0) {
		//echo $companyid . 'HI<br>';
		$name = (string)$name;
		$name = trim($name);
		
		$companyid = (int)$companyid;

		$query = "REPLACE company_aliases (name, companyid";
		if ($id) { $query .= ", id"; }
		$query .= ") VALUES ('".res($name)."',";
		$query .= res($companyid);
		if ($id) { $query .= ",'".res($id)."'"; }
		$query .= "); ";
		$result = qdb($query) OR die(qe().' '.$query);
		if (!$id) {$id = qid();}
		
		//echo $query . "<br>";

		return ($id);
	}
?>
