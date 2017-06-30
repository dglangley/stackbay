<?php
    //================================== GET USER =================================
    //  Get the result from the users table, including contact information.       |
    //=============================================================================

	//Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/form_handle.php';
	
	function getUser($userid, $return = 'name'){
	    $select = "SELECT $return FROM users u, contacts c where u.contactid = c.id AND u.id = ".prep($userid).";";
	    return rsrq($select);
	}
?>