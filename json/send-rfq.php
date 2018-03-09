<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/getCompany.php';
	include_once '../inc/jsonDie.php';
	include_once '../inc/sendCompanyRFQ.php';
	include_once '../inc/logRFQ.php';

	$message_body = '';
	if (isset($_REQUEST['message_body'])) { $message_body = $_REQUEST['message_body']; }
	$message_body = str_replace(chr(10),'<br/>',$message_body);
	$sbj = "Quote request";
	if (isset($_REQUEST['message_subject'])) { $sbj = $_REQUEST['message_subject']; }
	if (! $sbj) { $sbj = "Quote request"; }
	$companyids = array();
	if (isset($_REQUEST['companyids']) AND is_array($_REQUEST['companyids'])) { $companyids = $_REQUEST['companyids']; }
	$partid_csv = array();
	if (isset($_REQUEST['partids'])) { $partid_csv = $_REQUEST['partids']; }
	$suspend = false;
	if (isset($_REQUEST['suspend']) AND $_REQUEST['suspend']) { $suspend = true; }

	if (count($companyids)==0) {
		jsonDie('Oops! Did you forget to select a company?');
	}

	$partids = explode(",",$partid_csv);

	foreach ($companyids as $cid) {
		sendCompanyRFQ($cid,$message_body,$sbj);

		if (! $SEND_ERR) {
			foreach ($partids as $partid) {
				$rfqid = logRFQ($partid,$cid);
			}
		}
	}

	if ($SEND_ERR) { jsonDie($SEND_ERR); } else { jsonDie('Success'); }
?>
