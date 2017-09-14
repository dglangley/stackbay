<?php
	include_once $_SERVER["ROOT_DIR"]."/inc/dbconnect.php";
//	include_once $_SERVER["ROOT_DIR"]."/inc/pipe.php";

	//Declare the company array which will be used to globally communicate the found companies
	$COMPANIES = array();
	
	//Function designed by Aaron to act as a direct conversion from the value in the old database
	//to the new one via the curated table called company_aliases.

	$COMPANY_MAPS = array();
	function dbTranslate($companyid, $oldToNew = true){
		global $COMPANY_MAPS;

		if (isset($COMPANY_MAPS[$companyid][$oldToNew])) { return ($COMPANY_MAPS[$companyid][$oldToNew]); }

		if ($oldToNew){
			$query = "SELECT name FROM inventory_company WHERE id = '".$companyid."'; ";
			$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
			if (mysqli_num_rows($result)==0) { return 0; }
			$r = mysqli_fetch_assoc($result);

			$query = "SELECT c.id `c` FROM companies c LEFT JOIN company_aliases a ON c.id = a.companyid ";
			$query .= "WHERE c.name = '".$r['name']."' OR a.name = '".$r['name']."' ";
			$query .= "GROUP BY c.id ORDER BY c.id ASC LIMIT 1; ";

//			$query = "Select companyid `c` From company_maps where inventory_companyid = '$companyid';";
		} else {
			$query = "Select inventory_companyid `c` From company_maps where companyid = '$companyid';";
		}

		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if(mysqli_num_rows($result)){
			foreach($result as $row){
//				echo "Found the translation: ".$row['c'];
				$COMPANY_MAPS[$companyid][$oldToNew] = $row['c'];

				return $row['c'];
			}
		}
		else{
			$COMPANY_MAPS[$companyid][$oldToNew] = $companyid;

			return $companyid;
		}
	}

	
	//The main function of this file. It takes inputs of the search parameter 
	//and its type, as well as the output one wishes to grab from the field. If there is no existing company
	//then we pass in the boolean which indicates this. However, there is already an "addCompany" function,
	//So I would have to assume there is redundancy in the system somewhere.

	function getCompany($search_field,$input_field='id',$output_field='name',$add_new=false) {
		//Make the company array global
		global $COMPANIES;

		//Strip any white space from the search field
		$search_field = trim($search_field);
		
		//If there is no search field, exit the code and return null
		if (! $search_field) { return (''); }
		
		//Translate any old code referring to companyid into regular id.
		if ($output_field=='companyid') { $output_field = 'id'; }

		if (isset($COMPANIES[$search_field]) AND isset($COMPANIES[$search_field][$input_field])) {
			return ($COMPANIES[$search_field][$input_field][$output_field]);
		}

		$num_results = 0;

		if ($add_new) {
			$search_field = trim($search_field);

			// check for existing
			$query = "SELECT id FROM companies WHERE name = '".res($search_field)."'; ";
			$result = qdb($query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				return ($r['id']);
			}

			$query = "INSERT INTO companies (name) VALUES ('".res($search_field)."'); ";
			$result = qdb($query);
			return (qid());
		}

		$query_field = $input_field;
		$key_field = $search_field;

		if ($input_field=='contactid') {
			$query = "SELECT companyid FROM contacts WHERE id = '".res($search_field)."'; ";
			$result = qdb($query);
			$num_results = mysqli_num_rows($result);
			if ($num_results>0) {
				$r = mysqli_fetch_assoc($result);
				$query_field = 'id';
				$search_field = $r['companyid'];
			}
		}
		if ($output_field == 'oldid'){}
		$query = "SELECT * FROM companies WHERE $query_field = '".res($search_field)."'; ";
		//echo $query; exit;
		$result = qdb($query);
		
		$num_results = mysqli_num_rows($result);
		if ($num_results==0) {
			if ($input_field=='name') {
				$find_companyid = abbrevName($search_field);
				if ($find_companyid>0) {
					$query = "SELECT * FROM companies WHERE id = '$find_companyid'; ";
					$result = qdb($query);
					$num_results = mysqli_num_rows($result);
				}
			}
			if ($num_results==0) {
				$query2 = "SELECT * FROM company_aliases WHERE name = '".res($search_field)."'; ";
				$result2 = qdb($query2);
				if (mysqli_num_rows($result2)==1) {
					$r2 = mysqli_fetch_assoc($result2);

					$query = "SELECT * FROM companies WHERE id = '".res($r2['companyid'])."'; ";
					$result = qdb($query);
					$num_results = mysqli_num_rows($result);
				}
			}

			if ($num_results==0) { return (0); }
		}
		$r = mysqli_fetch_assoc($result);
		$r['oldid'] = 0;

/*
		$query2 = "SELECT inventory_companyid id FROM company_maps WHERE companyid = '".$r['id']."'; ";
		$result2 = qdb($query2);
		if (mysqli_num_rows($result2)>0) {
			$r2 = mysqli_fetch_assoc($result2);
			$r['oldid'] = $r2['id'];
		}
*/

		if (! $r['default_email']) {
			$query2 = "SELECT email FROM contacts, emails WHERE companyid = '".res($r['id'])."' AND emails.contactid = contacts.id; ";
//			$query2 .= "AND login_email IS NOT NULL AND login_email <> '' AND rfq = 'T'; ";
			$result2 = qdb($query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$r['default_email'] = $r2['email'];
			}
		}

		// add all aliases for this company
		$r['aliases'] = array();
		$query2 = "SELECT * FROM company_aliases WHERE companyid = '".res($r['id'])."'; ";
		$result2 = qdb($query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$r['aliases'][$r2['id']] = $r2['name'];
		}

		$COMPANIES[$key_field] = array($input_field=>$r);

		return ($r[$output_field]);
	}

	function addCompany(&$company) {
		$company = trim($company);
		if (! $company) { return 7; }

		$companyid = abbrevName($company);
		if ($companyid) { return ($companyid); }

		$query = "INSERT INTO companies (name) VALUES ('".res($company)."') ";
		if ($GLOBALS['test']) { echo "$query<BR>"; }
		else { $result = qdb($query); }
		return (qid());
	}

	function abbrevName(&$company) {
		$try_company = trim(preg_replace('/[,]?[[:space:]]?(inc|ab|bv|ltd|us|ag|limited|l[.]?l[.]?c|plc|technologies|corp|corporation|telecom|e\\.K|\\([A-Z]+\\))?[.]*[[:space:]]*$/i','',$company));

		if (strtolower($company)=='commsource telecommunication supply') {
			$company = trim(str_replace('Telecommunication Supply','',$company));
		} else if (strtolower($company)=='e & m communications') {
			$company = 'E&M Communications';
		}

		if ($try_company<> '' AND $try_company<>$company) {
			$company = $try_company;

			$query = "SELECT * FROM companies WHERE name = '".res($try_company)."' ";
			$result = qdb($query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				return ($r['id']);
			}

//			$company = $try_company;
		}
		return false;//($company);
	}

	function setCompany($input_name='companyid') {
		$companyid = 0;
		if (isset($_REQUEST[$input_name]) AND trim($_REQUEST[$input_name])) {
			$new_company = false;
			// check that this is a legitimate company by passing in id and asking for it back; this way, even an
			// all-numeric company NAME can be created here...
			if (is_numeric($_REQUEST[$input_name])) {
				$companyid = getCompany($_REQUEST[$input_name],'id','id');
				if (! $companyid OR $companyid<>$_REQUEST[$input_name]) { $new_company = true; }
			} else {
				$new_company = true;
			}
			if ($new_company) {
				$companyid = getCompany(trim($_REQUEST[$input_name]),'name','id',true);
			}
		}

		return ($companyid);
	}
?>
