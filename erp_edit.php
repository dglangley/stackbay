<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/dbsync.php';

	$DEBUG = 0;
	$ALERT = '';

	// Generate a slug for any text aka URL friendlly etc version of text with spaces and special chars
	function slug($text) {
        // replace non letter or digits by _
        $text = preg_replace('~[^\pL\d]+~u', '_', $text);
        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');

        // remove duplicate _
        $text = preg_replace('~-+~', '_', $text);
        // lowercase
        $text = strtolower($text);

        return $text;
    }

	function addDatabase($company, $database, $token = '') {
		global $ALERT;

		// Call upon the class and run the contructor
		$dbSync = new DBSync;

		// Check again right here if the user actually has the correct access to generate new ERPs
		// If not admin MUST have a token in order to proceed that is valid
		if(! $token AND (!$GLOBALS['U']['admin'] AND !$GLOBALS['U']['manager'])) {
			$ALERT = "Access denied. A valid token is required to proceed. Please contact an admin for assistance.";

			return 0;
		}

		if($token) {
			// verify the token here before anything happens going forward.
			$dbSync->authenticateToken($token);

			// the authentication will set an ALERT if something goes wrong in the process
			if($ALERT) {
				return 0;
			}
		}

		$query = "SELECT * FROM erp WHERE company = ".fres($company)." AND namespace = ".fres($database).";";
		$result = qedb($query);

		if(qnum($result) == 0) {
			// $query2 = "INSERT INTO erp (namespace, company) VALUES (".fres($database).", ".fres($company).");";
			// qedb($query2);
			// $databaseid = qid();
		} else {
			$ALERT = 'Database already exists. Please try again or contact an admin for assistance.';
			return 0;
		}

		$namespace = $database;
		$database = 'sb_'.$database;

		// New function that checks and makes sure ALL of the required tables to run this Sync is generate propoerly...
		// Else create them
		$dbSync->generateDB($database, $namespace, $password);
		$dbSync->setCompany($company);

		// Set the DB for what will be used... For this Instance we will use vmmdb or the current one so its more universal
		// Eventually we need to convert it over to the corresponding host that will have the dummy data
		// Host, User, Pass, Name
		$dbSync->setDBOneConnection($GLOBALS['WLI_GLOBALS']['RDS_HOSTNAME'],  $GLOBALS['WLI_GLOBALS']['RDS_USERNAME'], $GLOBALS['WLI_GLOBALS']['RDS_PASSWORD'], $GLOBALS['WLI_GLOBALS']['db']);

		// Host user etc will be the same thanks to David
		// Only change is db name at the end...
		$dbSync->setDBTwoConnection($GLOBALS['WLI_GLOBALS']['RDS_HOSTNAME'],  $GLOBALS['WLI_GLOBALS']['RDS_USERNAME'], $GLOBALS['WLI_GLOBALS']['RDS_PASSWORD'], $database);

		$dbSync->matchTables();

		if($token) {
			// If token exists we already know that it is valid and verified.... The token has already been stored into the object so
			// Token ERP just checks off the access to prevent future access on the same token
			$dbSync->tokenERP($token);
		}

		// On complete create the erp record
		$query = "INSERT INTO erp (namespace, company) VALUES (".fres($namespace).", ".fres($company).");";
		qedb($query);

		return $databaseid;
	}

	function resetDatabase($database,$company) {
		$dbSync = new DBSync;
		$dbSync->setCompany($company);
		
		$dbSync->setDBOneConnection($GLOBALS['WLI_GLOBALS']['RDS_HOSTNAME'],  $GLOBALS['WLI_GLOBALS']['RDS_USERNAME'], $GLOBALS['WLI_GLOBALS']['RDS_PASSWORD'], $database);

		$dbSync->resetOneDB();
	}

	function revokeDatabase($database,$company) {
		$dbSync = new DBSync;
		$dbSync->setCompany($company);
		
		// Set the connection to be used currently in the system
		$dbSync->setDBOneConnection($GLOBALS['WLI_GLOBALS']['RDS_HOSTNAME'],  $GLOBALS['WLI_GLOBALS']['RDS_USERNAME'], $GLOBALS['WLI_GLOBALS']['RDS_PASSWORD'], $database);

		// Get whatever database is currently set into the first connection and set the privileges
		// $flag == false means that we are revoking.... true is the reverse and grants privielges to the user
		$dbSync->editPrivileges($database);
	}


	function editDatabase($company, $database, $databaseid) {
		$query = "REPLACE erp (company, namespace, id) VALUES (".fres($company).", ".fres($database).", ".res($databaseid).");";
		qedb($query);
	}

	// Set all the form posted elements to be used in this page
	$company = '';
	if (isset($_REQUEST['company'])) { $company = trim($_REQUEST['company']); }
	$database = '';
	if (isset($_REQUEST['database'])) { $database = slug(trim($_REQUEST['database'])); }
	
	$reset = false;
	if (isset($_REQUEST['reset'])) { $reset = slug(trim($_REQUEST['reset'])); }

	$revoke = false;
	if (isset($_REQUEST['revoke'])) { $revoke = slug(trim($_REQUEST['revoke'])); }

	$databaseid = 0;
	if (isset($_REQUEST['databaseid'])) { $databaseid = trim($_REQUEST['databaseid']); }

	$token = '';
	if (isset($_REQUEST['token'])) { $token = trim($_REQUEST['token']); }

	$password = '';
	if (isset($_REQUEST['password'])) { $password = trim($_REQUEST['password']); }

	if(! $reset AND ! $revoke) {
		$databaseid = addDatabase($company, $database, $token, $password);
	} else if($_REQUEST['erp_admin'] AND $reset AND ($GLOBALS['U']['admin'] OR $GLOBALS['U']['manager'])) {
		// In this if statement we are also taking the precaution to make sure that it is the admin page and also that the user who is currently invoking this is an actual admin in the system
		resetDatabase('sb_'.$reset,$company);
	} else if($_REQUEST['erp_admin'] AND $revoke AND ($GLOBALS['U']['admin'] OR $GLOBALS['U']['manager'])) {
		revokeDatabase('sb_'.$revoke,$company);
	} else {
		$ALERT = 'Something went wrong with the system (database generation). Please contact an admin for assistance.';
	}

	if($_REQUEST['erp_admin'] AND ($GLOBALS['U']['admin'] OR $GLOBALS['U']['manager'])) {
		header('Location: /erp_admin.php'.($ALERT?'?ALERT='.$ALERT:''));	
	} else {
		// Go straight to the new namespace if no alert
		if(! $ALERT) {
			//header('Location: http://'.$database.'.'.$_SERVER['HTTP_HOST']);
			header('Location: http://'.$database.'.'.str_replace('www.','',$_SERVER['HTTP_HOST']));
		} else {
			header('Location: /signup/installer.php?token='.$token.'&ALERT='.$ALERT);	
		}
	}

	exit;
