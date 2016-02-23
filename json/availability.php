<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/format_date.php';
	include_once '../inc/format_price.php';

	function array_append(&$arr1,$arr2) {
		foreach ($arr2 as $date => $arr) {
			if (! isset($arr1[$date])) { $arr1[$date] = array(); }

			foreach ($arr as $result) {
				foreach ($result as $r) {
					$arr1[$date][] = $r;
				}
			}
		}
	}

	$attempt = 0;
	if (isset($_REQUEST['attempt']) AND is_numeric($_REQUEST['attempt'])) { $attempt = $_REQUEST['attempt']; }
	$partid = 0;
	if (isset($_REQUEST['partid']) AND is_numeric($_REQUEST['partid'])) { $partid = $_REQUEST['partid']; }

	$today = date("Y-m-d");
	$yesterday = format_date(date("Y-m-d"),'Y-m-d',array('d'=>-1));
	$lastWeek = format_date(date("Y-m-d"),'Y-m-d',array('d'=>-7));
	$lastYear = format_date(date("Y-m-d"),'Y-m-01',array('m'=>-11));

	$matches = array();

	$keys = array();//prevent duplicate results on same day
	$query = "SELECT name, datetime, SUM(qty) qty, price, source, companyid FROM market, companies ";
	$query .= "WHERE partid = '".$partid."' AND market.companyid = companies.id ";
	$query .= "GROUP BY datetime, companyid, source ORDER BY datetime DESC; ";
	$result = qdb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		$date = substr($r['datetime'],0,10);

		// log based on keyed result to avoid duplicates
		if (isset($keys[$date.'.'.$r['companyid'].'.'.$r['source']])) { continue; }
		$keys[$date.'.'.$r['companyid'].'.'.$r['source']] = true;

		$price = false;
		if ($r['price']>0) { $price = $r['price']; }
		$source = false;
		if (! is_numeric($r['source']) AND $r['source']<>'List') { $source = $r['source']; }

		if (! isset($matches[$date])) { $matches[$date] = array(); }
		if (! isset($matches[$date][$r['companyid']])) {
			$matches[$date][$r['companyid']] = array(
				'company' => $r['name'],
				'qty' => $r['qty'],
				'price' => $price,
				'changeFlag' => 'circle-o',
				'sources' => array(),
			);
		} else if ($r['qty']>$matches[$date][$r['companyid']]['qty']) {
			$matches[$date][$r['companyid']]['qty'] = $r['qty'];
		}

		if ($source) { $matches[$date][$r['companyid']]['sources'][] = $source; }
	}
	unset($keys);

	$priced = array();
	$standard = array();
	foreach ($matches as $date => $companies) {
		foreach ($companies as $companyid => $r) {
			if ($r['price']) {
				$priced[$date][$r['price']][] = $r;
			} else {
				$standard[$date][$r['qty']][] = $r;
			}
			// sort descending by keys
			krsort($priced[$date]);
			krsort($standard[$date]);
		}
	}

	$market = array();
	array_append($market,$priced);
	array_append($market,$standard);

	unset($priced);
	unset($standard);

/*
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
*/

	$newResults = array('results'=>array(),'done'=>true);
	$n = 0;
	foreach ($market as $rDate => $r) {
//for now, just show past 5 dates
		if ($n>=5) { break; }

		$newRows = array();
		foreach ($r as $k => $row) {
/*
			if ($k>=$attempt AND $rDate==$today) {
				$newResults['done'] = false;
				continue;
			}
*/
			$newRows[] = $row;
		}

		if ($rDate==$today) { $rDate = 'Today'; }
		else if ($rDate==$yesterday) { $rDate = 'Yesterday'; }
		else if ($rDate>$lastWeek) { $rDate = format_date($rDate,'D'); }
		else if ($rDate>=$lastYear) { $rDate = format_date($rDate,'M j'); }
		else { $rDate = format_date($rDate,'M j, y'); }

		$newResults['results'][$rDate] = $newRows;
		$n++;
	}
//	print "<pre>".print_r($newResults,true)."</pre>";

	header("Content-Type: application/json", true);
	echo json_encode($newResults);
	exit;
?>
