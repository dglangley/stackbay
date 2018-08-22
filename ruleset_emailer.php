<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	//Getter
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';

	// Emailer
	include_once $_SERVER["ROOT_DIR"].'/inc/send_gmail.php';
	// Maybe using subscriptions
	include_once $_SERVER["ROOT_DIR"].'/inc/getSubEmail.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getDefaultEmail.php';

	$DEBUG = 0;
	$ALERT = '';
	setGoogleAccessToken(5);//5 is amea’s userid, this initializes her gmail session

	function generateEmails($emails) {
		global $ALERT, $DEV_ENV;

		foreach($emails as $companyid => $r) {
			// If it is not checked then continue to the next company
			if($r['status'] != 'on' OR ! $r['status']) {
				continue;
			}

			$company_info = getDefaultEmail($companyid);

			// print_r($r);
			// echo'<BR>';
			// print_r($company_info);
			// echo'<BR><BR>';

			if(! $DEV_ENV) {
				$email_body_html = nl2br($r['content']);
				$email_subject = ($r['type'] == 'Demand' ? 'WTS '.date('n/j/y ga') : 'WTB '.date('n/j/y ga'));
	
				$recipients = $company_info[0];
				// $recipients = 'andrew@ven-tel.com';
				$bcc = 'david@ven-tel.com';
				
				$send_success = send_gmail($email_body_html,$email_subject,$recipients,$bcc);
				if (! $send_success) {
					$ALERT = json_encode(array('message'=>$SEND_ERR));
				}
			}

		}
	}

	$rulesetid = '';
	if (isset($_REQUEST['rulesetid'])) { $rulesetid = trim($_REQUEST['rulesetid']); }

	$emails = array();
	if (isset($_REQUEST['email'])) { $emails = $_REQUEST['email']; }

	generateEmails($emails);

	// Edit ruleset happens on the miner page so make the redirect accordingly
	header('Location: /ruleset_actions.php?rulesetid='.$rulesetid.($ALERT?'&ALERT='.$ALERT:''));
	exit;