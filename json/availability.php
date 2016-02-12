<?php
	include_once '../inc/format_date.php';

	$attempt = 0;
	if (isset($_REQUEST['attempt']) AND is_numeric($_REQUEST['attempt'])) { $attempt = $_REQUEST['attempt']; }
	$today = date("Y-m-d");
	$yesterday = format_date(date("Y-m-d"),'Y-m-d',array('d'=>-1));
	$lastWeek = format_date(date("Y-m-d"),'Y-m-d',array('d'=>-7));
	$lastYear = format_date(date("Y-m-d"),'Y-m-01',array('m'=>-11));
	$market = array(
		$today => array(
			0 => array(
				'company' => 'Pics Telecom',
				'qty' => 8,
				'price' => false,
				'changeFlag' => 'chevron-up',
				'sources' => array(
					'bb',
				),
			),
			1 => array(
				'company' => 'WestWorld',
				'qty' => 5,
				'price' => false,
				'changeFlag' => 'circle-o',
				'sources' => array(
					'bb',
					'ps',
				),
			),
			2 => array(
				'company' => 'Excel Computers',
				'qty' => 1,
				'price' => false,
				'changeFlag' => 'chevron-down',
				'sources' => array(
					'et',
				),
			),
		),
		$yesterday => array(
			0 => array(
				'company' => 'Alcatel-Lucent',
				'qty' => 1,
				'price' => 550,
				'changeFlag' => 'circle-o',
				'sources' => array(
					'alu',
				),
			),
			1 => array(
				'company' => 'WestWorld',
				'qty' => 5,
				'price' => false,
				'changeFlag' => 'circle-o',
				'sources' => array(
					'bb',
					'ps',
				),
			),
			2 => array(
				'company' => 'Excel Computers',
				'qty' => 3,
				'price' => false,
				'changeFlag' => 'circle-o',
				'sources' => array(
					'et',
				),
			),
		),
	);

	$newResults = array('results'=>array(),'done'=>true);
	foreach ($market as $rDate => $r) {
		$newRows = array();
		foreach ($r as $k => $row) {
			if ($k>=$attempt AND $rDate==$today) {
//				echo $attempt.':'.$k.',,,'.$rDate.' = '.$today.chr(10);
				$newResults['done'] = false;
				continue;
			}
			$newRows[] = $row;
		}

		if ($rDate==$today) { $rDate = 'Today'; }
		else if ($rDate==$yesterday) { $rDate = 'Yesterday'; }
		else if ($rDate>$lastWeek) { $rDate = format_date($rDate,'D'); }
		else if ($rDate>=$lastYear) { $rDate = format_date($rDate,'M j'); }
		else { $rDate = format_date($rDate,'M j, y'); }

		$newResults['results'][$rDate] = $newRows;
	}
//	print "<pre>".print_r($newResults,true)."</pre>";

	header("Content-Type: application/json", true);
	echo json_encode($newResults);
	exit;
?>
