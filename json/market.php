<?php
	include '../inc/dbconnect.php';
	include '../inc/jsonDie.php';
	include '../inc/keywords.php';
	include '../inc/getField.php';
	include '../inc/format_date.php';

	function getHistory($partids,$this_month) {
		$Q = array(
			'Supply' => array(
				'price' => 'avail_price',
				'table' => 'availability',
				'field' => 'offer',
			),
			'Demand' => array(
				'price' => 'quote_price',
				'table' => 'demand',
				'field' => 'quote',
			),
		);

		$partid_csv = '';
		foreach ($partids as $partid) {
			if ($partid_csv) { $partid_csv .= ','; }
			$partid_csv .= $partid;
		}

		$records = array();
		$running_avg = false;
		foreach ($Q as $t) {
			$history = array();
			$query = "SELECT ".$t['price']." tprice, LEFT(datetime,7) ym FROM ".$t['table']." t, search_meta m ";
			$query .= "WHERE partid IN (".$partid_csv.") AND datetime >= '".format_date($this_month,'Y-m-01 00:00:00',array('m'=>-24))."' ";
			$query .= "AND m.id = t.metaid AND ".$t['price']." > 0 ";
			$query .= "GROUP BY companyid, tprice ORDER BY datetime ASC; ";
			$result = qedb($query);
			while ($r = qrow($result)) {
				$history[$r['ym']][] = $r['tprice'];
			}

			krsort($history);

			for ($m=24; $m>=0; $m--) {
				$ym = format_date(date("Y-m-01"),'Y-m',array('m'=>-$m));
				$mo = format_date(date("Y-m-01"),"M 'y",array('m'=>-$m));

				$prices = 0;
				$n = count($history[$ym]);
				foreach ($history[$ym] as $price) {
					$prices += $price;
				}
				if ($n>0 AND $prices>0) {
					$avg_price = $prices/$n;
					$running_avg = $avg_price;
				}

				if ($running_avg!==false) {
					$records[$mo][$t['field']] = $running_avg;
				}
			}
		}
		krsort($records);
print "<pre>".print_r($records,true)."</pre>";exit;

		return ($records);
	}

	$slid = 0;
	if (! isset($_REQUEST['slid'])) { jsonDie("No search list id"); }

	//default field handling variables
	$col_search = 0;
	$sfe = false;//search from end
	$col_qty = 1;
	$qfe = false;//qty from end
	$col_price = false;
	$pfe = false;//price from end

	$this_month = date("Y-m-01");


	$slid = $_REQUEST['slid'];

	$query = "SELECT * FROM search_lists WHERE id = '".res($slid)."'; ";
	$result = qedb($query);
	$list = qfetch($result,'Could not find list');

	$lines = explode(chr(10),$list['search_text']);
	$fields = $list['fields'];
	$col_search = substr($fields,0,1);
	$col_qty = substr($fields,1,1);
	$col_price = substr($fields,2,1);
	if (strlen($list['fields'])>3) {
		$sfe = substr($fields,3,1);
		$qfe = substr($fields,4,1);
		$pfe = substr($fields,5,1);
	}

	$results = array();
	$partids = array();
	foreach ($lines as $i => $line) {
		$ln = $i+1;
		$F = preg_split('/[[:space:]]+/',$line);

		$search = getField($F,$col_search,$sfe);
		if ($search===false) { continue; }

		$qty = getField($F,$col_qty,$qfe);
		if (! $qty) { $qty = 1; }

		$price = getField($F,$col_price,$pfe);
		if ($price===false) { $price = ''; }

		$H = hecidb($search);
		foreach ($H as $partid => $row) {
			$partids[$partid] = $partid;
		}

		$market = getHistory($partids,$this_month);

		$r = array('ln'=>$ln,'search'=>$search,'qty'=>$qty,'market'=>$market,'results'=>$H);
		$results[$ln] = $r;
	}

	header("Content-Type: application/json", true);
	echo json_encode(array('results'=>$results,'message'=>''));
	exit;
?>
