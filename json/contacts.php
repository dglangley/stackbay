<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/format_date.php';
	include_once '../inc/keywords.php';
	include_once '../inc/getContact.php';
	include_once '../inc/getContacts.php';
	include_once '../inc/getCompany.php';
	include_once '../inc/form_handle.php';



	$q = grab('q');
    $companyid = grab('limit');
	$cid = prep($companyid);
    
    $output = array();
    
    $query = "SELECT DISTINCT * FROM `contacts` WHERE ";
	if ($companyid AND ! $q) {//showing all company-specific results without user search string
        $query .= "`companyid` = $cid ";
	} else {
		$query .= "`name` LIKE '%".res($q)."%' ";
	}
    $query .= "ORDER BY ";
	if ($q) {
		$query .= "IF(`companyid`='".res($companyid)."',0,1), IF(`name`='".res($q)."',0,1), ";
		$query .= "IF(`name` LIKE '".res($q)."%',0,1), ";
	}
	$query .= "name; ";

    $primary = qdb($query);
        
	// if a company-specific ($companyid) match is found, don't include non-matches
	$company_matches = false;
    if (isset($primary)){
        foreach($primary as $id => $row){
			$name = $row['name'];

			if ($companyid==$row['companyid']) { $company_matches = true; }
			else if ($company_matches) { break; }
			else { $name .= ' ('.getCompany($row['companyid']).')'; }

            $line = array(
                'id' => $row['id'], 
                'text' => $name
            );
            $output[] = $line;
        }
    }   
    
/*
    if ($companyid && strlen($q) > 0){
        //Then append the rest of the contacts ordered by alphabetical
        $secondary = " SELECT DISTINCT * FROM `contacts`
        WHERE `companyid` != $companyid AND `name` LIKE '%$q%' 
        ORDER BY `name`;";
        $second = qdb($secondary);
    
        if (isset($second)){
            foreach($second as $id => $row){
                $line = array(
                    'id' => $row['id'], 
                    'text' => $row['name']
                );
                $output[] = $line;
            }
        }
    }    
*/
    if (strlen($q) > 0){
        $output[] = array(
            'id' => "new $q",
            'text' => "Add $q"
            );
    }
    
	echo json_encode($output);//array('results'=>$companies,'more'=>false));
	exit;
?>
