<?php
	$WLI_GLOBALS = array();
	if (! isset($root_dir)) { $root_dir = ''; }
	if (isset($_SERVER["ROOT_DIR"]) AND ! $root_dir) { $root_dir = $_SERVER["ROOT_DIR"]; }
	else if (! $root_dir) { $root_dir = '/var/www/html'; }
	if (! isset($_SERVER["DEFAULT_DB"]) OR ! $_SERVER["DEFAULT_DB"]) { $_SERVER["DEFAULT_DB"] = 'vmmdb'; }

	$SUBDOMAIN = '';

	// print_r($_SERVER['HTTP_HOST']);

	if (isset($_SERVER['HTTP_HOST'])) {
		$expl = explode('.', $_SERVER['HTTP_HOST']);
		if (count($expl)>2) { $SUBDOMAIN = $expl[0]; }

		if (strtolower($SUBDOMAIN)=='www') { $SUBDOMAIN = ''; }
		if ($SUBDOMAIN) {
			$_SERVER["DEFAULT_DB"] = 'sb_'.strtolower($SUBDOMAIN);

			// Also set the according user and password here
			$_SERVER['RDS_USERNAME'] = 'sb_'.strtolower($SUBDOMAIN) . '_admin';
			$_SERVER['RDS_PASSWORD'] = 'asb_'.strtolower($SUBDOMAIN).'pass02!';
		}
	}

	if (! isset($_SERVER['RDS_HOSTNAME'])) {
		// not set in global env
		die('Host configuration error, could not connect'.chr(10));
	}

	$WLI_GLOBALS = array(
		'RDS_HOSTNAME' => $_SERVER['RDS_HOSTNAME'],
		'RDS_USERNAME' => $_SERVER['RDS_USERNAME'],
		'RDS_PASSWORD' => $_SERVER['RDS_PASSWORD'],
		'db' => $_SERVER['DEFAULT_DB'],
		'RDS_PORT' => $_SERVER['RDS_PORT']
	);
$WLI_GLOBALS['RDS_USERNAME'] = 'puser';
if (isset($_REQUEST['user'])) { $WLI_GLOBALS['RDS_USERNAME'] = trim($_REQUEST['user']); }
$WLI_GLOBALS['RDS_PASSWORD'] = trim($_REQUEST['password']);

	if ($WLI_GLOBALS['RDS_HOSTNAME']<>'localhost') { $WLI_GLOBALS['RDS_PASSWORD'] = 'avenpass02!'; }

	if (! $WLI_GLOBALS['db']) { $WLI_GLOBALS['db'] = 'vmmdb'; }
	// if ($_SERVER["RDS_HOSTNAME"]=='localhost') { $root_dir = '/Users/Shared/WebServer/Sites/marketmanager'; }

	// debugging:
	// 0 = all queries executed
	// 1 = echo INSERT/REPLACE/UPDATE/DELETE/ALTER, but NO EXECUTION
	// 2 = echo INSERT/REPLACE/UPDATE/DELETE/ALTER, AND execute
	// 3 = echo ALL queries, but NO EXECUTION
	if (! isset($DEBUG)) { $DEBUG = 0; }

	// print_r($WLI_GLOBALS); die();
	$WLI = mysqli_connect('p:'.$WLI_GLOBALS['RDS_HOSTNAME'], $WLI_GLOBALS['RDS_USERNAME'], $WLI_GLOBALS['RDS_PASSWORD'], $WLI_GLOBALS['db']);
	if (mysqli_connect_errno($WLI)) {

		// Redirect only once and if the page is already a 404 don't continually redirect as an infinite loop
		if ($_SERVER['REQUEST_URI'] != "/403") {
//			header('Location: /403');
//include 'database_error.php';
//header('HTTP/1.0 403 Forbidden');
		}

//		exit;

		// retired this method so we don't disclose secrets
		echo "Failed to connect to MySQL: " . mysqli_connect_error();
	}
	
	function qdb($query,$db_connection='WLI') { return (mysqli_query($GLOBALS[$db_connection],$query)); }
	function qid($db_connection='WLI') { return (mysqli_insert_id($GLOBALS[$db_connection])); }
	function qar($db_connection='WLI') { return (mysqli_affected_rows($GLOBALS[$db_connection])); }
	function qe($db_connection='WLI') { return (mysqli_error($GLOBALS[$db_connection])); }
	function qfetch($result) { return (mysqli_fetch_assoc($result)); }
	function qnum($result) { return (mysqli_num_rows($result)); }
	function qrow($result,$err='') {
		if (mysqli_num_rows($result)==0) {
			if ($err) {
				die($err);
			} else {
				return array();
			}
		}
		return (mysqli_fetch_assoc($result));
	}
	function qedb($query,$db_connection='WLI') {
		$DEBUG = $GLOBALS['DEBUG'];

		$executor = preg_match('/INSERT|REPLACE|UPDATE|DELETE|ALTER/',$query);

		if (($executor AND $DEBUG) OR $DEBUG==3) { echo $query.'<BR>'; }

		if ($DEBUG==0 OR $DEBUG==2 OR ! $executor) {
			$result = qdb($query,$db_connection) OR die(qe($db_connection)."<BR>".$query);
			return ($result);
		} else {
			return false;
		}
	}
	function res($str,$db_connection='WLI') { return (mysqli_real_escape_string($GLOBALS[$db_connection],$str)); }
	function fres($str,$repl_str = "NULL",$db_connection='WLI') {
		$str = trim($str);
		$output = (! empty($str)) ? "'".res($str,$db_connection)."'" : $repl_str;

		return ($output);
	}

	if (isset($NO_CACHE) AND $NO_CACHE===true) {
		header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
		header('Last-Modified: ' . gmdate( 'D, d M Y H:i:s') . ' GMT');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Cache-Control: post-check=0, pre-check=0', false);
		header('Pragma: no-cache'); 
	} else {
		ini_set('session.cache_limiter','public');
		session_cache_limiter(false);
	}
?>
