<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/jsonDie.php';
	include_once '../inc/format_date.php';
	header("Content-Type: application/json", true);

	$partids = array();
	if (isset($_REQUEST['partids'])) {
		if (is_array($_REQUEST['partids'])) {
			$partids = $_REQUEST['partids'];
		} else {
			$partids = explode(',',$_REQUEST['partids']);
		}
	}

	$partid_csv = '';
	foreach ($partids as $partid) {
		if ($partid_csv) { $partid_csv .= ','; }
		$partid_csv .= $partid;
	}

	$costs = array();
	$query = "SELECT * FROM average_costs WHERE partid IN (".$partid_csv.") ORDER BY datetime DESC; ";
	$result = qedb($query);
	while ($r = qrow($result)) {
		$r['dt'] = format_date($r['datetime'],'n/j/y g:ia');
		$costs[] = $r;
	}

	echo json_encode($costs);
	exit;
?>
