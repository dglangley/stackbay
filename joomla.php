<?php
	function qdb($query) { return (mysqli_query($GLOBALS['WLI'],$query)); }
	function qid() { return (mysqli_insert_id($GLOBALS['WLI'])); }
	function qe() { return (mysqli_error($GLOBALS['WLI'])); }
	function res($str) { return (mysqli_real_escape_string($GLOBALS['WLI'],$str)); }
	$WLI_GLOBALS = array();
	$root_dir = '/Users/Shared/WebServer/Sites/marketmanager';
	$_SERVER["DEFAULT_DB"] = 'bvn';

	if (! isset($_SERVER['RDS_HOSTNAME'])) { die('could not connect to host'.chr(10)); }

	$WLI_GLOBALS = array(
		'RDS_HOSTNAME' => $_SERVER['RDS_HOSTNAME'],
		'RDS_USERNAME' => $_SERVER['RDS_USERNAME'],
		'RDS_PASSWORD' => $_SERVER['RDS_PASSWORD'],
		'db' => $_SERVER['DEFAULT_DB'],
		'RDS_PORT' => $_SERVER['RDS_PORT']
	);
	if (! $WLI_GLOBALS['db']) { $WLI_GLOBALS['db'] = 'bvn'; }

	$WLI = mysqli_connect($WLI_GLOBALS['RDS_HOSTNAME'], $WLI_GLOBALS['RDS_USERNAME'], $WLI_GLOBALS['RDS_PASSWORD'], $WLI_GLOBALS['db'], $WLI_GLOBALS['RDS_PORT']);
	if (mysqli_connect_errno($WLI)) {
		echo "Failed to connect to MySQL: " . mysqli_connect_error();
	}

/*
	id
	created = post_date & post_date_gmt
	title = post_title
	alias = post_name
	title_alias
	introtext = post_excerpt
	fulltext = post_content
	state
	'publish' = post_status
	'open' = comment_status
	'open' = ping_status
	'' = post_status
	'' = to_ping
	'' = pinged
	modified = post_modified, post_modified gmt
	'' = post_content_filtered
	'0' = post_parent
	
*/

	$query = "SELECT * FROM jos_content LIMIT 0,100; ";
	$result = qdb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		print "<pre>".print_r($r,true)."</pre>";
echo '<BR>';
	}
?>
