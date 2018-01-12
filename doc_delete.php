<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	$DEBUG = 0;

	$docs = array();
	if (isset($_REQUEST['docs'])) { $docs = explode(',',$_REQUEST['docs']); }

	$taskid = '';
	$order_type = 'Service';

	foreach ($docs as $docid) {
		$query = "SELECT * FROM service_docs WHERE id = '".res($docid)."'; ";
		$result = qedb($query);
		if (mysqli_num_rows($result)==0) { die("Document ".$docid." does not exist"); }
		$r = mysqli_fetch_assoc($result);
		if ($r['userid']<>$U['id']) {
			die("You are not authorized to delete document ".$docid);
		}
		$taskid = $r['item_id'];
		$T = order_type($r['item_label']);
		$order_type = $T['type'];

		$query = "DELETE FROM service_docs WHERE id = '".res($docid)."'; ";
		$result = qedb($query);
	}

	if ($DEBUG) { exit; }

	header('Location: service.php?order_type='.$order_type.'&taskid='.$taskid.'&tab=documentation');
	exit;
?>
