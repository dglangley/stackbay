<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$DEBUG = 0;

	function editBucketCategory($category){

		// Make sure the record does not already exists before adding in the account
		$query = "SELECT * FROM bucket_categories WHERE category = ".fres($category).";";
		$result = qedb($query);

		if(mysqli_num_rows($result) == 0) {
			$query2 = "INSERT bucket_categories (category, status) VALUES (".fres($category).", 'Active');";
			qedb($query2);
		} else {
			// Exists already so just update the status accordingly
			$r = mysqli_fetch_assoc($result);
			$query2 = "UPDATE bucket_categories SET status = 'Active' WHERE id = ".fres($r['id']).";";
			qedb($query2);
		}
	}

	function voidBucket($account_id) {
		$query = "UPDATE bucket_categories SET status = 'Void' WHERE id = ".fres($account_id).";";
		qedb($query);
	}

	
	if ($DEBUG) { print '<pre>' . print_r($_REQUEST, true). '</pre>'; }

	$category = '';
	if (isset($_REQUEST['category'])) { $category = $_REQUEST['category']; }

	$deleteid = '';
	if (isset($_REQUEST['deleteid'])) { $deleteid = $_REQUEST['deleteid']; }

	if($deleteid) {
		voidBucket($deleteid);
	}

	if($category) {
		editBucketCategory($category);
	}

	if ($DEBUG) { exit; }
	
	header('Location: /bucket.php');

	exit;
