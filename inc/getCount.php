<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	function getCount($partids,$startDate,$endDate='',$type='Demand') {
		if ($partids AND ! is_array($partids)) { $partids = array($partids); }

		if (count($partids)==0) { return (0); }

		$T = order_type($type);

		$partid_csv = '';
		foreach ($partids as $partid) {
			if ($partid_csv) { $partid_csv .= ','; }
			$partid_csv .= $partid;
		}

		$startDate = format_date($startDate,'Y-m-d');
		$endDate = format_date($endDate,'Y-m-d');

		$query = "SELECT LEFT(p.heci,7) heci, COUNT(DISTINCT(LEFT(".$T['datetime'].",10))) n ";
		$query .= "FROM ".$T['orders']." o, ".$T['items']." i, parts p ";
		$query .= "WHERE o.".str_replace('metaid','id',$T['order'])." = i.".$T['order']." AND i.partid = p.id AND p.id IN (".$partid_csv.") ";
		if ($startDate) { $query .= "AND o.".$T['datetime']." >= '".res($startDate)." 00:00:00' "; }
		if ($endDate) { $query .= "AND o.".$T['datetime']." <= '".res($endDate)." 23:59:59' "; }
		$query .= "; ";
		$result = qedb($query);
		if (qnum($result)==0) { return (0); }

		$r = qrow($result);

		return ($r['n']);
	}
?>
