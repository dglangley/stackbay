<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/jsonDie.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	if (! isset($_REQUEST['partids'])) { jsonDie("No partids"); }

	$summary_past = format_date($today,'Y-m-01',array('m'=>-11));

	$partids = $_REQUEST['partids'];
	$type = '';
	if (isset($_REQUEST['type'])) { $type = $_REQUEST['type']; }
	$pricing = 0;
	if (isset($_REQUEST['pricing'])) { $pricing = $_REQUEST['pricing']; }

	$T = order_type($type);

//	$GROUP = 'SUM';
//	if ($type=='Supply' OR $type=='Demand') { $GROUP = 'MAX'; }

	$dates = array();
	$recent_date = format_date($today,'Y-m-d 00:00:00',array('d'=>-7));
	$res = array();
	$query = "SELECT name, companyid, ".$T['datetime']." date, (".$T['qty'].") qty, ".$T['amount']." price, t.".$T['order']." order_number, '".$T['abbrev']."' abbrev ";
	$query .= "FROM ".$T['items']." t, ".$T['orders']." o, companies c ";
	$query .= "WHERE partid IN (".$partids.") AND ".$T['qty']." > 0 ";
	if ($pricing) { $query .= "AND ".$T['amount']." > 0 "; }
	$query .= "AND t.".$T['order']." = o.".str_replace('meta','',$T['order'])." AND companyid = c.id ";
	if ($pricing) {
//		$query .= "GROUP BY t.".$T['order'].", ".$T['amount']." ";
	} else {
//		$query .= "GROUP BY companyid, LEFT(".$T['datetime'].",10), ".$T['amount']." ";
	}
	$query .= "ORDER BY LEFT(".$T['datetime'].",10) DESC, IF(".$T['amount'].">0,0,1), ".$T['qty']." DESC, t.id DESC; ";
	$result = qedb($query);
	while ($r = qrow($result)) {
		if (count($dates)>=5) { break; }

		if ($pricing) {
			$key = $r['order_number'].'.'.$r['price'];
		} else {
			$key = $r['companyid'].'.'.substr($r['date'],0,10);//.'.'.$r['price'];
		}

		if (isset($res[$key])) {
			foreach ($res[$key] as $k => $r2) {
				if ($r['qty']>$r2['qty']) {
					if ($type=='Supply' OR $type=='Demand') { $res[$key][$k]['qty'] = $r['qty']; }
					else { $res[$key][$k]['qty'] += $r['qty']; }
				}       
				if (! $r2['price'] AND $r['price']) { $res[$key][$k]['price'] = $r['price']; }
			}
			continue;
		}

		$amt = $r['price'];
		if (round($amt)==$amt) { $amt = round($amt); }
		else { $amt = number_format($r['price'],2); }
		$r['price'] = $amt;

		$r['highlight'] = '';
		if ($r['date']>=$recent_date) {
			$r['highlight'] = '1';
		}

		$dates[substr($r['date'],0,10)] = true;
		$r['date'] = summarize_date($r['date']);
		$res[$key][] = $r;
	}

	// restructure array without $key so we have a plain numerically-indexed array
	$results = array();
	foreach ($res as $key => $r2) {
		foreach ($r2 as $r) {
			$results[] = $r;
		}
	}

	header("Content-Type: application/json", true);
	echo json_encode(array('results'=>$results,'message'=>''));
	exit;
?>
