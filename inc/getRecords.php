<?php
	include_once 'getPipeIds.php';

	function getRecords($search_str,$partid_array,$array_format='csv',$market_table='demand') {
		$unsorted = array();

		$partid_str = '';
		if ($array_format=='statement') {
			$partid_str = $partid_array;
		} else if ($array_format=='csv') {
		}

		switch ($market_table) {
			case 'demand':
				$query = "SELECT datetime, request_qty qty, quote_price price, companyid cid, name, partid FROM demand, search_meta, companies ";
				$query .= "WHERE (".$partid_str.") AND demand.metaid = search_meta.id AND companies.id = search_meta.companyid ";
				$query .= "ORDER BY datetime ASC; ";

				$unsorted = get_coldata($search_str,'demand');
				break;

			case 'purchases':
				$query = "SELECT datetime, companyid cid, name, purchase_orders.id, qty, price, partid FROM purchase_items, purchase_orders, companies ";
				$query .= "WHERE (".$partid_str.") AND purchase_items.purchase_orderid = purchase_orders.id AND companies.id = purchase_orders.companyid ";
				$query .= "ORDER BY datetime ASC; ";

				$unsorted = get_coldata($search_str,'purchases');
				break;

			case 'sales':
			default:
				$query = "SELECT datetime, companyid cid, name, sales_orders.id, qty, price, partid FROM sales_items, sales_orders, companies ";
				$query .= "WHERE (".$partid_str.") AND sales_items.sales_orderid = sales_orders.id AND companies.id = sales_orders.companyid ";
				$query .= "ORDER BY datetime ASC; ";

				$unsorted = get_coldata($search_str,'sales');
				break;
		}

		// get local data
		$result = qdb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$unsorted[$r['datetime']][] = $r;
		}
		// sort local and piped data together in one results array, combining where necessary (to elim dups)
		$results = sort_results($unsorted,'desc');

		return ($results);
	}

	function sort_results($unsorted,$sort_order='asc') {
		if ($sort_order=='asc') { ksort($unsorted); }
		else if ($sort_order=='desc') { krsort($unsorted); }

		$results = array();
		$uniques = array();
		$k = 0;
		foreach ($unsorted as $date => $arr) {
			foreach ($arr as $r) {
				$key = $r['name'].'.'.$date;

				if (isset($uniques[$key])) {
					if ($r['qty']>$results[$uniques[$key]]['qty']) {
						$results[$uniques[$key]]['qty'] = $r['qty'];
					}
					if (format_price($r['price'],true,'',true)>0 AND (! $results[$uniques[$key]]['price'] OR $results[$uniques[$key]]['price']=='0.00')) {
						$results[$uniques[$key]]['price'] = $r['price'];
					}

					continue;
				}
				$uniques[$key] = $k;

				$results[$k++] = $r;
			}
		}

		return ($results);
	}

	function get_coldata($search,$coldata='demand') {
		$unsorted = array();

		$pipe_ids = getPipeIds($search);

		foreach ($pipe_ids as $r) {
			$invid = $r['id'];

			if ($coldata=='demand') {
				$unsorted = get_details($invid,'outgoing_quote',$unsorted);
				$unsorted = get_details($invid,'outgoing_request',$unsorted);
				$unsorted = get_details($invid,'userrequest',$unsorted);
			} else if ($coldata=='sales') {
				$unsorted = get_details($invid,'sales',$unsorted);
			} else if ($coldata=='purchases') {
				$unsorted = get_details($invid,'incoming_quote',$unsorted);
			}
		}

		return ($unsorted);
	}

	$SALE_QUOTES = array();
	function get_details($invid,$table_name,$results) {
		global $SALE_QUOTES;

		$orig_table = $table_name;

		$and_where = '';
		$add_field = '';
		if ($table_name=='sales') {
			$table_name = 'outgoing_quote';
//			$and_where = "AND win = '1' ";
			$add_field = ', quote_id, win ';
		} else if ($table_name=='incoming_quote') {
			$and_where = "AND inventory_purchaseorder.purchasequote_ptr_id = inventory_incoming_quote.quote_id ";
			$add_field = ', quote_id ';
		}

		$db_results = array();
		if ($orig_table=='outgoing_quote' AND isset($SALE_QUOTES[$invid])) {
			$db_results = $SALE_QUOTES[$invid];
		} else {
			$query = "SELECT date datetime, quantity qty, price, inventory_company.name, company_id cid, inventory_id partid ".$add_field;
			$query .= "FROM inventory_".$table_name.", inventory_company ";
			if ($table_name=='incoming_quote') { $query .= ", inventory_purchaseorder "; }
			$query .= "WHERE inventory_id = '".$invid."' AND inventory_".$table_name.".company_id = inventory_company.id AND quantity > 0 ";
			$query .= $and_where;
			if ($table_name=='userrequest') { $query .= "AND incoming = '0' "; }
			$query .= "ORDER BY date ASC, inventory_".$table_name.".id ASC; ";
//			echo $orig_table.':<BR>'.$query.'<BR>';
			$result = qdb($query,'PIPE') OR die(qe('PIPE'));
			while ($r = mysqli_fetch_assoc($result)) {
				$db_results[] = $r;
			}
			if ($orig_table=='sales') { $SALE_QUOTES[$invid] = $db_results; }
		}

		return (handle_results($db_results,$orig_table,$results));
	}

	function handle_results($db_results,$table_name,$results) {
		foreach ($db_results as $r) {
			if ($r['price']=='0.00') { $r['price'] = ''; }
			else { $r['price'] = format_price($r['price'],2); }

			if ($table_name=='sales' OR $table_name=='incoming_quote') {
				if ($table_name=='sales') {
					if (! $r['win']) { continue; }
					$query3 = "SELECT so_date date FROM inventory_salesorder WHERE quote_ptr_id = '".$r['quote_id']."'; ";
				} else if ($table_name=='incoming_quote') {
					$query3 = "SELECT po_date date FROM inventory_purchaseorder WHERE purchasequote_ptr_id = '".$r['quote_id']."'; ";
				}
				$result3 = qdb($query3,'PIPE') OR die(qe('PIPE'));
				if (mysqli_num_rows($result3)>0) {
					$r3 = mysqli_fetch_assoc($result3);
					$r['datetime'] = $r3['date'];
				}
			}

			$results[$r['datetime']][] = $r;
		}

		return ($results);
	}
?>
