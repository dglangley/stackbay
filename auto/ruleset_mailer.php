<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/ruleset_script.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/sendCompanyRFQ.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/logRFQ.php';

	$DEBUG = 0;
	setGoogleAccessToken(1);

	//$query = "SELECT * FROM ruleset_actions WHERE time LIKE '".res(date("H:i"))."%'; ";
	$query = "SELECT * FROM ruleset_actions WHERE time LIKE '".res(date("H"))."%'; ";
	$result = qedb($query);
	while ($r = qrow($result)) {
		$id = $r['rulesetid'];

		$query2 = "SELECT * FROM ruleset_mailers WHERE rulesetid = '".res($id)."'; ";
		$result2 = qedb($query2);
		while ($r2 = qrow($result2)) {
			$companyid = $r2['companyid'];
			$partids = array();

			$ruleset_data = getRulesetData($id, $companyid);

			$rows = $ruleset_data[$companyid]['parts'];

			$message_body = 'Good morning,<br/><br/>Please check stock:<br/><br/>';
			$message_strings = '';
			foreach ($rows as $str) {
				$str = trim($str);
				if (! $str OR $str=='0') { continue; }

				$message_strings .= $str.'<br/>';

				$H = hecidb($str);
				foreach ($H as $partid => $r3) {
					$partids[] = $partid;
				}
			}
			$message_body .= $message_strings;
			$sbj = 'WTB '.date('n/j/y ga');

			if ($DEBUG) {
				echo $message_body.'<BR>';
			} else if ($message_strings) {
				$sent = sendCompanyRFQ($companyid,$message_body,$sbj);

				// confirm message was sent before logging rfq's
				if ($sent) {
					foreach($partids as $partid) {
						$rfqid = logRFQ($partid,$companyid);
					}
				}
			}
		}
	}
?>
