<?php
	include_once $_SERVER["DOCUMENT_ROOT"].'/inc/dbconnect.php';
	include_once $_SERVER["DOCUMENT_ROOT"].'/inc/format_price.php';

	function get_details($invid,$table_name,$date_limit,$results) {
			$query2 = "SELECT date datetime, quantity qty, price, inventory_company.name, company_id cid, inventory_id ";
			$query2 .= "FROM inventory_".$table_name.", inventory_company ";
			$query2 .= "WHERE inventory_id = '".$invid."' AND inventory_".$table_name.".company_id = inventory_company.id AND quantity > 0 ";
			if ($table_name=='userrequest') { $query2 .= "AND incoming = '0' "; }
			if ($date_limit) { $query2 .= "AND date >= '".$date_limit."' "; }
			$query2 .= "ORDER BY date ASC, inventory_".$table_name.".id ASC; ";
//			echo $query2.'<BR>';
			$result2 = qdb($query2,'PIPE') OR die(qe('PIPE'));
			while ($r2 = mysqli_fetch_assoc($result2)) {
				if ($r2['price']=='0.00') { $r2['price'] = ''; }
				else { $r2['price'] = format_price($r2['price'],2); }
				$results[$r2['datetime']][] = $r2;
			}
			return ($results);
	}

	function get_summary($invid,$table_name,$date_limit,$results) {
			$data = array();
			//$query2 = "SELECT date datetime, SUM(quantity) qty, ((SUM(quantity)*price)/SUM(quantity)) price, '' cid, '' inventory_id ";
			$query2 = "SELECT date datetime, quantity qty, price, company_id cid, '' inventory_id ";
			$query2 .= "FROM inventory_".$table_name.", inventory_company ";
			$query2 .= "WHERE inventory_id = '".$invid."' AND inventory_".$table_name.".company_id = inventory_company.id AND quantity > 0 ";
			if ($table_name=='userrequest') { $query2 .= "AND incoming = '0' "; }
			if ($date_limit) { $query2 .= "AND date < '".$date_limit."' "; }
			//$query2 .= "GROUP BY LEFT(date,7) ORDER BY date DESC; ";
			$query2 .= "ORDER BY date DESC, price DESC; ";
//			echo $query2.'<BR>';
			$result2 = qdb($query2,'PIPE') OR die(qe('PIPE'));
			while ($r2 = mysqli_fetch_assoc($result2)) {
				if (! $r2['price'] OR $r2['price']=='0.00') { $r2['price'] = ''; }
				else { $r2['price'] = number_format($r2['price'],2,'.',''); }
				$results[$r2['datetime']][] = $r2;
			}

			return ($results);
	}

	function sort_results($unsorted,$sort_order='asc') {
		if ($sort_order=='asc') { ksort($unsorted); }
		else if ($sort_order=='desc') { krsort($unsorted); }

		$results = array();
		$uniques = array();
		$grouped = array();
		$k = 0;
		foreach ($unsorted as $date => $arr) {
			foreach ($arr as $r) {
				if (! $r['inventory_id']) {
					$key = $r['cid'].'.'.$r['datetime'];
					if (isset($uniques[$key])) { continue; }
					$uniques[$key] = $r;

					$month = substr($r['datetime'],0,7);

					if (isset($grouped[$month])) {
						if ($r['price']>0) {
							$grouped[$month]['sum_qty'] += $r['qty'];
							$grouped[$month]['sum_price'] += $r['price']*$r['qty'];
						}
						$grouped[$month]['total_qty'] += $r['qty'];
						continue;
					}
					if ($r['price']>0) {
						$r['sum_qty'] = $r['qty'];
						$r['sum_price'] = $r['price']*$r['qty'];
					}
					$r['total_qty'] = $r['qty'];
					unset($r['qty']);
					unset($r['price']);
					unset($r['cid']);
					unset($r['inventory_id']);
					$grouped[$month] = $r;
					continue;
				}

				$key = $r['cid'].'.'.$r['datetime'].'.'.$r['inventory_id'];
				if (isset($uniques[$key])) {
					if ($r['cid'] AND $r['inventory_id']) {
						$results[$uniques[$key]]['qty'] = $r['qty'];
					}

					continue;
				}
				$uniques[$key] = $k;

				$results[$k++] = $r;
			}
		}

		// if $grouped has elements, it's a summary array of uniquely-identified elements above so convert here
		foreach ($grouped as $month => $r) {
			$r['price'] = '';
			if ($r['sum_price']>0) {
				$r['price'] = format_price($r['sum_price']/$r['sum_qty'],2);
				$r['qty'] = $r['total_qty'];
			} else {
				$r['qty'] = $r['total_qty'];
			}
			unset($r['sum_price']);
			unset($r['sum_qty']);
			unset($r['total_qty']);
			$results[] = $r;
		}

		return ($results);
	}

	$PIPE = mysqli_connect('db.ven-tel.com', 'david', '33WbkcY6YBMs5cLWe7sD', 'inventory', '13306');
	if (mysqli_connect_errno($PIPE)) {
		echo "Failed to connect to MySQL: " . mysqli_connect_error();
	}
//	echo '<BR><BR><BR>';

	function pipe($search,$table_type='demand',$date_limit=false) {
		$unsorted = array();
		$unsorted2 = array();

		$search = preg_replace('/[^[:alnum:]]+/','',$search);

		$query = "SELECT id, manufacturer_id_id manfid, part_number part, short_description description, ";
		$query .= "clei heci, quantity_stock qty, notes ";
		$query .= "FROM inventory_inventory WHERE (clean_part_number LIKE '".res($search,'PIPE')."%' ";
		if (strlen($search)==7 OR strlen($search)==10 AND ! is_numeric($search)) { $query .= "OR clei LIKE '".res(substr($search,0,7),'PIPE')."%' "; }
		$query .= "); ";
		$result = qdb($query,'PIPE') OR die(qe('PIPE'));
		while ($r = mysqli_fetch_assoc($result)) {
//			print "<pre>".print_r($r,true)."</pre>";
			$invid = $r['id'];
			$key = $r['part'];
			if ($r['heci']) { $key .= '.'.$r['heci']; }

			$unsorted = get_details($invid,'outgoing_quote',$date_limit,$unsorted);
			$unsorted = get_details($invid,'outgoing_request',$date_limit,$unsorted);
			$unsorted = get_details($invid,'userrequest',$date_limit,$unsorted);

			$unsorted2 = get_summary($invid,'outgoing_quote',$date_limit,$unsorted2);
			$unsorted2 = get_summary($invid,'outgoing_request',$date_limit,$unsorted2);
			$unsorted2 = get_summary($invid,'userrequest',$date_limit,$unsorted2);
		}

		$results = sort_results($unsorted,'asc');
		$results2 = sort_results($unsorted2,'desc');
		return (array($results,$results2));
	}

//	$search = 'NT5C07AC';
//	pipe($search);
?>
