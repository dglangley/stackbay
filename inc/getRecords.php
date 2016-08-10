<?php
	include_once 'getPipeIds.php';
	include_once 'getPartId.php';
	
	$record_start = '';
	$record_end = '';
	
	function getRecords($search_str = '',$partid_array = '',$array_format='csv',$market_table='demand') {
		global $record_start,$record_end,$oldid,$company_filter;
		$unsorted = array();

		if ((!$search_str && !$partid_array)&&(!$record_start && !$record_end)){
//			echo 'Valid search result or date range not entered';
			echo 'Please enter filters to get values.';
			return $unsorted;
			
		}


		$partid_str = '';
		
		if ($partid_array){
			if ($array_format=='statement') {
				$partid_str = $partid_array;
			} else if ($array_format=='csv') {
				$partid_str = 'partid IN ('.$partid_array.')';
			}
		}
		switch ($market_table) {
			case 'demand':
				
				$query = "SELECT datetime, request_qty qty, quote_price price, companyid cid, name, partid FROM demand, search_meta, companies ";
				$query .= "WHERE  demand.metaid = search_meta.id AND companies.id = search_meta.companyid ";
				if ($partid_str){$query .= " AND (".$partid_str.") ";}
				if ($record_start && $record_end){$query .= " AND datetime between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
				if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
				$query .= "ORDER BY datetime ASC; ";
				$unsorted = get_coldata($search_str,'demand');

				break;

			case 'purchases':
				$query = "SELECT datetime, companyid cid, name, purchase_orders.id, qty, price, partid FROM purchase_items, purchase_orders, companies ";
				$query .= "WHERE purchase_items.purchase_orderid = purchase_orders.id AND companies.id = purchase_orders.companyid ";
				if ($partid_str){$query .= " AND (".$partid_str.") ";}
				if ($record_start && $record_end){$query .= " AND datetime between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
				$query .= "ORDER BY datetime ASC; ";

				$unsorted = get_coldata($search_str,'purchases');
				break;

			case 'sales':
			default:
				$query = "SELECT datetime, companyid cid, name, sales_orders.id, qty, price, partid FROM sales_items, sales_orders, companies ";
				$query .= "WHERE sales_items.sales_orderid = sales_orders.id AND companies.id = sales_orders.companyid ";
				if ($partid_str){$query .= " AND (".$partid_str.") ";}
				if ($record_start && $record_end){$query .= " AND datetime between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
				$query .= "ORDER BY datetime ASC; ";

				$unsorted = get_coldata($search_str,'sales');
				break;
		}

		// get local data
		if($partid_str || !$search_str){
				$result = qdb($query);
			while ($r = mysqli_fetch_assoc($result)) {
				$unsorted[$r['datetime']][] = $r;
			}
		}
		// sort local and piped data together in one results array, combining where necessary (to elim dups)
		$consolidate = true;
		if (! $search_str AND ! $partid_array) { $consolidate = false; }
		$results = sort_results($unsorted,'desc',$consolidate);

		return ($results);
	}

	function sort_results($unsorted,$sort_order='asc',$consolidate=true) {
		if ($sort_order=='asc') { ksort($unsorted); }
		else if ($sort_order=='desc') { krsort($unsorted); }

		$results = array();
		$uniques = array();
		$k = 0;
		foreach ($unsorted as $date => $arr) {
			foreach ($arr as $r) {
				$key = $r['name'].'.'.$date;
				if (! $consolidate) { $key .= '.'.$r['partid']; }

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
		$pipe_ids = array();
		if ($search){
			$pipe_ids = getPipeIds($search);
		}
		$id_csv = '';
		foreach ($pipe_ids as $r) {
			if ($id_csv) { $id_csv .= ','; }
			$id_csv .= $r['id'];
		}

		if ($coldata=='demand') {
			$unsorted = get_details($id_csv,'outgoing_quote',$unsorted);
			$unsorted = get_details($id_csv,'outgoing_request',$unsorted);
			$unsorted = get_details($id_csv,'userrequest',$unsorted);
		} else if ($coldata=='sales') {
			$unsorted = get_details($id_csv,'sales',$unsorted);
		} else if ($coldata=='purchases') {
			$unsorted = get_details($id_csv,'incoming_quote',$unsorted);
		}
		return ($unsorted);
	}

//	$SALE_QUOTES = array();
	function get_details($id_csv,$table_name,$results) {
//		global $SALE_QUOTES, $record_start, $record_end;
		global $record_start, $record_end;

		$db_results = array();

		if (!$id_csv && (!$record_start && !$record_end)||(!$id_csv && ($table_name == 'sales' || $table_name == 'incoming_quote'))){
//			echo 'Valid search result or date range not entered';
			return $db_results;
		}

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
		if ($table_name == 'outgoing_request' || $table_name == 'outgoing_quote' || $table_name == 'userrequest'){
			if ($record_start && $record_end){$and_where .= "AND date between CAST('".substr($record_start,0,10)."' AS DATETIME) and CAST('".substr($record_end,0,10)."' AS DATETIME) ";}
		}

/*
		if ($orig_table=='outgoing_quote' AND isset($SALE_QUOTES[$invid])) {
			$db_results = $SALE_QUOTES[$invid];
		} else {
*/
			$query = "SELECT date datetime, quantity qty, price, inventory_company.name, company_id cid, part_number , clei ".$add_field;
			$query .= "FROM inventory_".$table_name.", inventory_company, inventory_inventory ";
			if ($table_name=='incoming_quote') { $query .= ", inventory_purchaseorder "; }
			$query .= "WHERE inventory_".$table_name.".company_id = inventory_company.id AND quantity > 0 ";
			if ($id_csv) { $query .= "AND inventory_id IN (".$id_csv.") "; }
			$query .= $and_where;
			if ($table_name=='userrequest') { $query .= "AND incoming = '0' "; }
			$query .= "AND inventory_inventory.id = inventory_id ";
			$query .= "ORDER BY date ASC, inventory_".$table_name.".id ASC; ";
//			echo $orig_table.':<BR>'.$query.'<BR>';
			$result = qdb($query,'PIPE') OR die(qe('PIPE'));

			while ($r = mysqli_fetch_assoc($result)) {
				$r['partid'] = getPartId($r['part_number'],$r['clei']);
				$db_results[] = $r;
			}

			if ($orig_table=='sales') { $SALE_QUOTES[$invid] = $db_results; }
//		}
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
