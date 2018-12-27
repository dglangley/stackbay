<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';

	function getQ2S($companyid,$list_type) {
		$info = array('q'=>0,'s'=>0,'d'=>'');

		if (! $companyid OR ($list_type<>'Sale' AND $list_type<>'Demand')) { return ($info); }

		$quotes = array();
		$sales = array();
		$backdate = '';
		$query = "SELECT sm.id, datetime FROM search_meta sm, demand d ";
		$query .= "WHERE companyid = '".res($companyid)."' AND sm.id = d.metaid ";
		$query .= "AND quote_price > 0 ";
		$query .= "ORDER BY datetime DESC LIMIT 0,30; ";
		$result = qedb($query);
		while ($r = qrow($result)) {
			$quotes[$r['id']] = true;
			$backdate = $r['datetime'];
			$info['d'] = format_date($backdate,'n/j/y');
		}
		$info['q'] = count($quotes);

		if ($backdate) {
			$query = "SELECT so.so_number FROM sales_orders so, sales_items si ";
			$query .= "WHERE companyid = '".res($companyid)."' AND so.so_number = si.so_number ";
			$query .= "AND price > 0 AND created >= '".res($backdate)."' ";
			$query .= "GROUP BY so.so_number, companyid ";
			$query .= "ORDER BY created DESC LIMIT 0,20; ";
			$result = qedb($query);
			while ($r = qrow($result)) {
				$sales[$r['so_number']] = true;
			}
		}
		$info['s'] = count($sales);

		return ($info);
	}
?>
