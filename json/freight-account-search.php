<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getContact.php';
	include_once $rootdir.'/inc/getContacts.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/form_handle.php';

    $q = grab('q');

    
    //$companyid = (isset($_REQUEST['limit']))? trim($_REQUEST['limit']) : '0'; 
    $companyid = grab('limit');
    $cid = prep($companyid);
    $carrierid = prep(grab('carrierid')); 
    
    $output = array();
    
    
    $query = "SELECT * FROM `freight_accounts` WHERE ";
    $query .= (($q) ? " `account_no` LIKE ".prep($q."%")." OR " : "");
	$query .= "`companyid` = $cid ";
	$query .= "AND `carrierid` = $carrierid ";
	$query .= "GROUP BY `account_no` ";
	$query .= "ORDER BY IF(`companyid`=$cid,0,1), `account_no`;";
    $results = qdb($query) or die(qe()." $query");
    
    if (mysqli_num_rows($results)){
        foreach($results as $row){
			$account = "";
			if ($row['companyid']<>$companyid) {
				$account .= "<b>".getCompany($row['companyid']).":</b> ";
			}
			$account .= $row['account_no'];
            $line = array(
                'id' => $row['id'], 
                'text' => $account,
            );
            $output[] = $line;
        }
    }

	// always add Prepaid option
	$output[] = array(
        'id' => "null",
        'text' => "PREPAID"
    );
    if (strlen($q) > 1){
        $output[] = array(
            'id' => "Add $q",
            'text' => "Add $q"
        );
    } 
    

	echo json_encode($output);
	exit;
?>
