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

	function addDatabase($company, $database) {
		global $ALERT;
		$query = "SELECT * FROM erp WHERE company = ".fres($company)." AND namespace = ".fres($database).";";
		$result = qedb($query);

		if(qnum($result) == 0) {
			$query2 = "INSERT INTO erp (namespace, company) VALUES (".fres($database).", ".fres($company).");";
			qedb($query2);
			$databaseid = qid();
		} else {
			$ALERT = 'Database already exists for this company.';
			return 0;
		}

		if($databaseid) {
			// It has been recorded on the system so add it inside here
			$query = "CREATE DATABASE ".res($database).";";
			qedb($query);

			$dbSync = new DBSync;
			$dbSync->setCompany($company);

			// Set the DB for what will be used... For this Instance we will use vmmdb or the current one so its more universal
			// Eventually we need to convert it over to the corresponding host that will have the dummy data

			// Host, User, Pass, Name
			$dbSync->setDBOneConnection($_SERVER['RDS_HOSTNAME'],  $_SERVER['RDS_USERNAME'], $_SERVER['RDS_PASSWORD'], $_SERVER['DEFAULT_DB']);

			// Host user etc will be the same thanks to David
			// Only change is db name at the end...
			$dbSync->setDBTwoConnection($_SERVER['RDS_HOSTNAME'],  $_SERVER['RDS_USERNAME'], $_SERVER['RDS_PASSWORD'], $database);

			$dbSync->matchTables();
		}

		return $databaseid;
	}

	function resetDatabase($database,$company) {
		$dbSync = new DBSync;
		$dbSync->setCompany($company);
		
		$dbSync->setDBOneConnection($_SERVER['RDS_HOSTNAME'],  $_SERVER['RDS_USERNAME'], $_SERVER['RDS_PASSWORD'], $database);

		$dbSync->resetOneDB();
	}

	function editDatabase($company, $database, $databaseid) {
		$query = "REPLACE erp (company, namespace, id) VALUES (".fres($company).", ".fres($database).", ".res($databaseid).");";
		qedb($query);
	}

	$company = '';
	if (isset($_REQUEST['company'])) { $company = trim($_REQUEST['company']); }
	$database = '';
	if (isset($_REQUEST['database'])) { $database = slug(trim($_REQUEST['database'])); }
	
	$reset = false;
	if (isset($_REQUEST['reset'])) { $reset = slug(trim($_REQUEST['reset'])); }

	$databaseid = 0;
	if (isset($_REQUEST['databaseid'])) { $databaseid = trim($_REQUEST['databaseid']); }

	if(! $reset) {
		$databaseid = addDatabase($company, $database);
	}  else {
		resetDatabase($reset,$company);
	}

	header('Location: /erp_admin.php'.($ALERT?'?ALERT='.$ALERT:''));	

	exit;
