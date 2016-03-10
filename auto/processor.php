<?php
    include_once $_SERVER["DOCUMENT_ROOT"].'/inc/dbconnect.php';
	include_once $_SERVER["DOCUMENT_ROOT"].'/inc/processUpload.php';

	$query = "SELECT uploads.*, uploads.id uploadid FROM uploads, search_meta ";
//	if ($test) { $query .= "WHERE filename = 'nave_131114.csv' "; }
	$query .= "WHERE processed IS NULL AND uploads.metaid = search_meta.id ";
	$query .= "ORDER BY search_meta.datetime ASC; ";
	$result = qdb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		processUpload($r['id']);
	}
?>
