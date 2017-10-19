<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/getQty.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';

	$DQ_date = format_date($today,'Y-m-d',array('y'=>-1));
	function getDQ($partid_csv,$start_date,$end_date) {
		global $DQ_date;

		$results = array();

		$query = "SELECT datetime, d.request_qty qty, companyid FROM demand d, search_meta m ";
		$query .= "WHERE d.partid IN (".$partid_csv.") AND d.metaid = m.id ";
		$query .= "AND datetime > CAST('".$DQ_date."' AS DATETIME); ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$key = substr($r['datetime'],0,7).'.'.$r['companyid'];
			if (isset($results[$key])) {
				if ($r['qty']>$results[$key]['qty']) { $results[$key]['qty'] = $r['qty']; }
				continue;
			}

			$results[$key] = array('datetime'=>$r['datetime'],'qty'=>$r['qty'],'companyid'=>$r['companyid']);
		}

		$query = "SELECT created datetime, qty, companyid FROM sales_items i, sales_orders o ";
		$query .= "WHERE i.partid IN (".$partid_csv.") AND i.so_number = o.so_number ";
		$query .= "AND created > CAST('".$DQ_date."' AS DATETIME); ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$key = substr($r['datetime'],0,7).'.'.$r['companyid'];
			if (isset($results[$key])) {
				if ($r['qty']>$results[$key]['qty']) { $results[$key]['qty'] = $r['qty']; }
				continue;
			}

			$results[$key] = array('datetime'=>$r['datetime'],'qty'=>$r['qty'],'companyid'=>$r['companyid']);
		}

		// sort descending
		krsort($results);
//		print "<pre>".print_r($results,true)."</pre>";
		$today = $GLOBALS['today'];

		$DQ = 1;//always start with 1
		$months = 24;
		$enddate = $today;
		for ($m=1; $m<=$months; $m++) {
			$backdate = format_date($today,'Y-m-d',array('m'=>-$m,'d'=>1));

			$divisor = round(1+($m/10),2);

			foreach ($results as $r) {
				$date = substr($r['datetime'],0,10);

				if ($date>$enddate) { continue; }
				else if ($date<$backdate) { break; }

				$enddate = format_date($today,'Y-m-d',array('m'=>-$m));

				$prorated_qty = ceil($r['qty']/$divisor);
				$DQ += $prorated_qty;
//				echo $r['qty'].' valued at '.($prorated_qty).'<BR>';
			}
		}

		$partids = explode(',',$partid_csv);
		$sum_qty = 0;
		foreach ($partids as $partid) {
			$sum_qty += getQty($partid);
		}

		$DQ -= $sum_qty;

		return ($DQ);
	}
?>
