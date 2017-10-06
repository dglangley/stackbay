<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/getTerms.php';
	include_once '../inc/jsonDie.php';

	$companyid = 0;
	if (isset($_REQUEST['companyid'])) { $companyid = $_REQUEST['companyid']; }
	$scope = '';
	if (isset($_REQUEST['scope'])) { $scope = $_REQUEST['scope']; }
	if ($scope=='Sale' OR $scope=='Repair' OR $scope=='Invoice') {
		$accounting_system = 'AR';
	} else {
		$accounting_system = 'AP';
	}

	$company_terms = getCompanyTerms($companyid,$accounting_system);

	// build new array with 'id','text' for select2 dropdown
	$terms = array();
	foreach ($company_terms as $termsid => $term) {
		$terms[] = array('id'=>$termsid,'text'=>$term['terms']);
	}

	header("Content-Type: application/json", true);
	echo json_encode($terms);
	exit;
?>
