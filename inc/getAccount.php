<?php
    
   	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getContact.php';
	include_once $rootdir.'/inc/getContacts.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/form_handle.php';

    function getDefaultAccount($companyid, $carrier = ''){
        $companyid = prep($companyid);
        $carrier = prep($carrier);
        //Select with order by id desc to get the most recently added freight account
        $select = "Select * FROM freight_accounts 
        where `companyid` = $companyid
        ".($carrier?" AND `carrierid` = $carrier ":"")."
        order by `id` desc 
        LIMIT 1;";
        $result = qdb($select) or die(qe()." $select");
        if(mysqli_num_rows($result)){
            $result = mysqli_fetch_assoc($result);
            return($result['id']);
        } else {
            return 0;
        }
    }
    
?>