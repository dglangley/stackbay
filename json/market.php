<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/jsonDie.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getField.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getShelflife.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getDQ.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getNotes.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	function getRows($type,$this_month,$partid_csv) {
		global $months_back;

		$T = order_type($type);

		// get data from supply/demand columns, then get pricing data also from orders tables to supplement pricing info
		$rows = array();
		$query = "SELECT ".$T['amount']." amount, LEFT(".$T['datetime'].",7) ym FROM ".$T['items']." t, ".$T['orders']." m ";
		$query .= "WHERE partid IN (".$partid_csv.") AND ".$T['datetime']." >= '".format_date($this_month,'Y-m-01 00:00:00',array('m'=>-$months_back))."' ";
		$query .= "AND m.".str_replace('metaid','id',$T['order'])." = t.".$T['order']." AND ".$T['amount']." > 0 ";
		$query .= "GROUP BY companyid, amount ORDER BY ".$T['datetime']." ASC; ";
//		if (strstr($partid_csv,'38308')) { die($query); }
		$result = qedb($query);
		while ($r = qrow($result)) {
			$rows[] = $r;
		}

		// get related orders data
		if ($type=='Supply') { $T = order_type('Purchase'); }
		else { $T = order_type('Sale'); }

		$query = "SELECT ".$T['amount']." amount, LEFT(".$T['datetime'].",7) ym FROM ".$T['items']." t, ".$T['orders']." m ";
		$query .= "WHERE partid IN (".$partid_csv.") AND ".$T['datetime']." >= '".format_date($this_month,'Y-m-01 00:00:00',array('m'=>-$months_back))."' ";
		$query .= "AND m.".str_replace('metaid','id',$T['order'])." = t.".$T['order']." AND ".$T['amount']." > 0 ";
		$query .= "GROUP BY companyid, amount ORDER BY ".$T['datetime']." ASC; ";
//		if (strstr($partid_csv,'38308')) { die($query); }
		$result = qedb($query);
		while ($r = qrow($result)) {
			$rows[] = $r;
		}

		return ($rows);
	}

	$months_back = 11;
	function getHistory($partids,$this_month) {
		global $months_back;

		$range = array('min'=>0,'max'=>0);
		$records = array('chart'=>array(),'range'=>$range);

		if (count($partids)==0) { return ($records); }

		$Q = array('Supply'=>'offer','Demand'=>'quote');

		$partid_csv = '';
		foreach ($partids as $partid) {
			if ($partid_csv) { $partid_csv .= ','; }
			$partid_csv .= $partid;
		}

		$starts_at = 0;
		$running_avg = 0;
		foreach ($Q as $type => $field) {

			$history = array();
			$rows = getRows($type,$this_month,$partid_csv);
			foreach ($rows as $r) {
				$history[$r['ym']][] = $r['amount'];
				if (! $starts_at AND $r['ym']>$starts_at) { $starts_at = $r['ym']; }

				if ($type=='Supply') {
					if (! $range['min'] OR $r['amount']<$range['min']) {
						$range['min'] = number_format($r['amount'],2);
					}
					if (! $range['max'] OR $r['amount']>$range['max']) {
						$range['max'] = number_format($r['amount'],2);
					}
				}
			}

			if (count($history)==0) { continue; }

			// the tips of the lines on the chart
			$open = 0;
			$close = 0;
			// the filled bars on the chart
			$high = 0;
			$low = 0;

			$last_high = 0;
			$last_low = 0;
			$start = false;
			$price_arr = array();
			for ($m=$months_back; $m>=0; $m--) {
				$ym = format_date(date("Y-m-01"),'Y-m',array('m'=>-$m));
				//$mo = format_date(date("Y-m-01"),"M 'y",array('m'=>-$m));

				$prices = 0;
				$n = count($history[$ym]);
				if ($n==0 AND ! $start) { continue; }

				foreach ($history[$ym] as $price) {
					$prices += $price;
					$price_arr[] = $price;

					if ($price>$high) { $high = $price; }
					if (! $low OR $price<$low) { $low = $price; }
				}

				if ($low AND $high AND $high==$low) {
					$high *= 1.2;
					$low *= .8;
				}

				if ($n>0 AND $prices>0) {
//					$mean = array_sum($price_arr)/$n;

					$avg_price = $prices/$n;
					$running_avg = $avg_price;
				}

				if (! $open OR $last_high<>$high) { $open = $last_high; }
				if (! $close OR $last_low<>$low) { $close = $last_low; }

/*
				if (! $open AND $high>0) { $open = $high; }
				if (! $close AND $low>0) { $close = $low; }
*/
$open = $high;
$close = $low;

				$records['chart'][$ym][$field] = array(
					'h' => (float)$high,
					'l' => (float)$low,
					'c' => (float)$close,
					'o' => (float)$open,
					't' => $ym,
					/*number_format($running_avg,2),*/
				);
				$start = true;

				$last_high = $high;
				$last_low = $low;

				$open = 0;
				$close = 0;
				$high = 0;
				$low = 0;
			}
		}
		ksort($records['chart']);

		$records['range'] = $range;

		return ($records);
	}

	$slid = 0;
	$search_string = '';
	if (isset($_REQUEST['search']) AND trim($_REQUEST['search'])) { $search_string = trim($_REQUEST['search']); }
	if (isset($_REQUEST['slid'])) { $slid = $_REQUEST['slid']; }

	if (! $slid AND ! $search_string) { jsonDie("No search provided"); }

	$filter_PR = 0;
	if (isset($_REQUEST['PR']) AND is_numeric($_REQUEST['PR'])) { $filter_PR = $_REQUEST['PR']; }

	//default field handling variables
	$col_search = 0;
	$sfe = false;//search from end
	$col_qty = 1;
	$qfe = false;//qty from end
	$col_price = false;
	$pfe = false;//price from end

	$this_month = date("Y-m-01");
	$recent_date = format_date($today,'Y-m-d 00:00:00',array('d'=>-15));

	if ($search_string) {
		$lines = array($search_string);
		$col_search = 1;
		$col_qty = false;
	} else {
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
	}

	$ln = 0;
	$results = array();
	foreach ($lines as $i => $line) {
		$F = preg_split('/[[:space:]]+/',$line);
//		print_r($F);echo '<BR><BR>';
//		if ($i<=5) { continue; }

		$search = getField($F,$col_search,$sfe);
		if ($search===false OR ! $search) { continue; }

		$search_qty = getField($F,$col_qty,$qfe);
//		die($search_qty);
		if (! $search_qty OR ! is_numeric($search_qty)) { $search_qty = 1; }

		$search_price = getField($F,$col_price,$pfe);
		if ($search_price===false) { $search_price = ''; }

		$searches = array($search=>true);

		$partids = array();

		$stock = array();
		$zerostock = array();
		$nonstock = array();
//		echo 'search '.$search.'<BR>';
		$H = hecidb($search);
		foreach ($H as $partid => $row) {
			$qty = getQty($partid);
			if ($qty===false) { $qty = ''; }

			$row['qty'] = $qty;

			// flag this as a primary match (non-sub)
			if ($row['rank']=='primary') {
				$row['class'] = 'primary';
			} else {
				$row['class'] = 'sub';
			}

			$partids[$partid] = $partid;

			if ($row['heci'] AND strlen($search)<>7) { $searches[substr($row['heci'],0,7)] = true; }
			$searches[format_part($row['primary_part'])] = true;
			foreach ($row['aliases'] as $alias) {
				if (strlen($alias)<=2) { continue; }

				$searches[format_part($alias)] = true;
			}

			$nflag = '';
			$notes = getNotes($partid);
			$row['notes'] = array();
			foreach ($notes as $note) {
				if ($note['datetime']>$recent_date) { $nflag = '<span class="item-notes text-danger"><i class="fa fa-sticky-note"></i></span>'; }
				else if (! $nflag) { $nflag = '<span class="item-notes text-warning"><i class="fa fa-sticky-note"></i></span>'; }

				$row['notes'][] = $note['id'];
			}

			if (! $nflag) { $nflag = '<span class="item-notes"><i class="fa fa-sticky-note-o"></i></span>'; }
			$row['notes_flag'] = $nflag;

			// gymnastics to force json to not re-sort array results, which happens when the key is an integer instead of string
			unset($H[$partid]);

			if ($qty>0) { $stock[$partid."-"] = $row; }
			else if ($qty===0) { $zerostock[$partid."-"] = $row; }
			else { $nonstock[$partid."-"] = $row; }
//			$H[$partid."-"] = $row;
		}

		// sort by stock first
		foreach ($stock as $k => $row) { $H[$k] = $row; }
		foreach ($zerostock as $k => $row) { $H[$k] = $row; }
		foreach ($nonstock as $k => $row) { $H[$k] = $row; }

		$stock = array();
		$zerostock = array();
		$nonstock = array();
		foreach ($searches as $str => $bool) {
			// don't use a string matching the $search above
			if ($search==$str) { continue; }

//			echo $str.'<BR>';
			$db = hecidb($str);
			// we don't want subs that are more likely bogus results
			if (count($db)>50) { continue; }

			foreach ($db as $partid => $row) {
				// don't duplicate a result already stored above
				if (isset($H[$partid."-"])) { continue; }

				$qty = getQty($partid);
				if ($qty===false) { $qty = ''; }
				$row['qty'] = $qty;

				// flag this result as a sub
				$row['class'] = 'sub';

				$row['notes'] = getNotes($partid);

				unset($H[$partid]);
				if ($qty>0) { $stock[$partid."-"] = $row; }
				else if ($qty===0) { $zerostock[$partid."-"] = $row; }
				else { $nonstock[$partid."-"] = $row; }
//				$H[$partid."-"] = $row;
			}
		}

		// sort by stock first
		foreach ($stock as $k => $row) { $H[$k] = $row; }
		foreach ($zerostock as $k => $row) { $H[$k] = $row; }
		foreach ($nonstock as $k => $row) { $H[$k] = $row; }

		$PR = getDQ($partids);
		if ($PR<$filter_PR) { continue; }

		$market = getHistory($partids,$this_month);
		/*$avg_cost = number_format(getCost($partids),2);*/
		$shelflife = getShelflife($partids);

		$r = array(
			'ln'=>$ln,
			'search'=>$search,
			'qty'=>$search_qty,
			'price'=>$search_price,
			'chart'=>$market['chart'],
			'range'=>$market['range'],
			/*'avg_cost'=>$avg_cost,*/
			'shelflife'=>$shelflife,
			'pr'=>$PR,
			'results'=>$H,
		);
		$results[$ln] = $r;

		$ln++;
	}

	header("Content-Type: application/json", true);
	echo json_encode(array('results'=>$results,'message'=>''));
	exit;
?>
