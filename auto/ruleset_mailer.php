<?php
	set_time_limit(0);
	ini_set('memory_limit', '2000M');
	ini_set('mbstring.func_overload', '2');
	ini_set('mbstring.internal_encoding', 'UTF-8');

	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/ruleset_script.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/sendCompanyRFQ.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_part.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/logRFQ.php';

	$DEBUG = 0;
	setGoogleAccessToken(1);

	$H = date("H");

	//$query = "SELECT * FROM ruleset_actions WHERE time LIKE '".res(date("H:i"))."%'; ";
	$query = "SELECT * FROM ruleset_actions WHERE time LIKE '".$H."%'; ";
	$result = qedb($query);
	while ($r = qrow($result)) {
		$id = $r['rulesetid'];

		$companyids = array();
		$query2 = "SELECT * FROM ruleset_mailers WHERE rulesetid = '".res($id)."'; ";
		$result2 = qedb($query2);
		while ($r2 = qrow($result2)) {
			$companyids[] = $r2['companyid'];
		}

		$ruleset_data = getRulesetData($id, $companyids);

		foreach ($companyids as $companyid) {
			$partids = array();

			$rows = $ruleset_data[$companyid]['parts'];

			$message_body = 'Good morning,<br/><br/>Please check stock:<br/><br/>';
			$message_strings = '';
			foreach ($rows as $str) {
				$str = trim($str);
				if (! $str OR $str=='0') { continue; }

				$message_strings .= $str.'<br/>';

				$prestr = '';//adds part# if using only 7-digit heci in $str below
				$H = hecidb($str);
				foreach ($H as $partid => $r3) {
					$partids[] = $partid;
					if ($r3['heci7']==$str AND ! $prestr) { $prestr = format_part($r3['primary_part']).' '; }
				}
				$message_strings .= $prestr.$str.'<br/>';
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
