<?php
	include_once 'getPartId.php';
	include_once 'getRep.php';
	include_once 'getCompany.php';
	include_once 'format_price.php';
	include_once 'format_date.php';
	
	$record_start = '';
	$record_end = '';
	
	function getRecords($search_arr = '',$partid_array = '',$array_format='csv',$market_table='demand',$results_mode=0, $start = '', $end = '') {
		global $record_start,$record_end,$oldid,$company_filter,$sales_min,$sales_max;
		$unsorted = array();

		$favorites = 0;
		if (isset($_REQUEST['favorites']) AND $_REQUEST['favorites']==1) { $favorites = 1; }
		$invlistid = 0;
		if (isset($_REQUEST['invlistid']) AND is_numeric($_REQUEST['invlistid']) AND $_REQUEST['invlistid']>0) {
			// validate id in uploads table
			$query = "SELECT * FROM uploads WHERE id = '".res($_REQUEST['invlistid'])."'; ";
			$result = qdb($query);
			if (mysqli_num_rows($result)==1) {
				$invlistid = $_REQUEST['invlistid'];
			}
		}

		$min = format_price($sales_min,'','',true);
		$max = format_price($sales_max,'','',true);

		//Allow filtering of dates dynamically
		if(isset($start) && $start != ''){$record_start = $start;}
		if(isset($end) && $end != ''){$record_end = $end;}
		
		// append times to start/end dates, if missing (based on strlen)
		if (strlen($record_start)==10) { $record_start .= ' 00:00:00'; }
		if (strlen($record_end)==10) { $record_end .= ' 23:59:59'; }
		$record_start = format_date($record_start,'Y-m-d H:i:s');
		$record_end = format_date($record_end,'Y-m-d H:i:s');

		// convert a string to an array of string element
		if (! is_array($search_arr)) {
			$new_array = array();
			if ($search_arr) { $new_array[] = $search_arr; }
			$search_arr = $new_array;
		}

		if ((count($search_arr)==0 && !$partid_array)&&(!$record_start && !$record_end)){
//			echo 'Valid search result or date range not entered';
			echo 'Please enter filters to get values.';
			return $unsorted;
			
		}

		// funky but true: getPipeIds() (within get_coldata() below) accepts space-separated strings and
		// will split them apart automatically, so we need to convert array to string
		$search_str = '';
		foreach ($search_arr as $each_search) {
			if ($search_str) { $search_str .= ' '; }
			$search_str .= $each_search;
		}

		$partid_str = '';
		
		if ($partid_array){
			if ($array_format=='statement') {//legacy method, deprecated
				$partid_str = $partid_array;
			} else if ($array_format=='csv') {//aaron's enlightenment
				$partid_str = 'partid IN ('.$partid_array.')';
			}
		}
		switch ($market_table) {
			case 'demand':
				
				$query = "SELECT datetime, request_qty qty, quote_price price, companyid cid, name, partid, userid, 'Active' status ";
				$query .= "FROM demand, search_meta, companies ";
				$query .= "WHERE  demand.metaid = search_meta.id AND companies.id = search_meta.companyid ";
				if ($partid_str){$query .= " AND (".$partid_str.") ";}
				if ($record_start && $record_end){$query .= " AND datetime between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
				if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
				if ($min_price){$query .= " AND quote_price >= ".$min." ";}
				if ($max_price){$query .= " AND quote_price <= ".$max." ";}
				// show results only with prices
				if ($results_mode==1) { $query .= "AND quote_price > 0 "; }
				$query .= "ORDER BY datetime ASC; ";

				//$unsorted = get_coldata($search_str,'demand');
				break;

			case 'purchases':
				$query = "SELECT created datetime, companyid cid, name, purchase_orders.po_number order_num, qty, price, partid, ";
				$query .= "sales_rep_id userid, part, heci, purchase_orders.status ";
				$query .= "FROM purchase_items, purchase_orders, companies, parts ";
				$query .= "WHERE purchase_items.po_number = purchase_orders.po_number AND companies.id = purchase_orders.companyid ";
				$query .= "AND parts.id = purchase_items.partid ";
				if ($partid_str){$query .= " AND (".$partid_str.") ";}
				if ($record_start && $record_end){$query .= " AND created between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
				if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
				if ($min_price){$query .= " AND price >= ".$min." ";}
				if ($max_price){$query .= " AND price <= ".$max." ";}
				// show results only with prices
				if ($results_mode==1) { $query .= "AND quote_price > 0 "; }
				$query .= "ORDER BY created ASC; ";

				//$unsorted = get_coldata($search_str,'purchases');
				break;

			case 'sales':
				$query = "SELECT created datetime, companyid cid, name, sales_orders.so_number order_num, qty, price, partid, ";
				$query .= "sales_rep_id userid, part, heci, sales_orders.status ";
				$query .= "FROM sales_items, sales_orders, companies, parts ";
				$query .= "WHERE sales_items.so_number = sales_orders.so_number AND companies.id = sales_orders.companyid ";
				$query .= "AND parts.id = sales_items.partid ";
				if ($partid_str){$query .= " AND (".$partid_str.") ";}
				if ($record_start && $record_end){$query .= " AND created between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
				if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
				if ($min_price){$query .= " AND price >= ".$min." ";}
				if ($max_price){$query .= " AND price <= ".$max." ";}
				// show results only with prices
				if ($results_mode==1) { $query .= "AND price > 0 "; }
				$query .= "ORDER BY created ASC; ";

				//$unsorted = get_coldata($search_str,'sales');
				break;

			case 'supply':
				$query = "SELECT datetime, avail_qty qty, avail_price price, companyid cid, name, partid, userid, 'Active' status ";
				$query .= "FROM availability, search_meta, companies ";
				$query .= "WHERE  availability.metaid = search_meta.id AND companies.id = search_meta.companyid ";
				if ($partid_str){$query .= " AND (".$partid_str.") ";}
				if ($record_start && $record_end){$query .= " AND datetime between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
				if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
				if ($min_price){$query .= " AND avail_price >= ".$min." ";}
				if ($max_price){$query .= " AND avail_price <= ".$max." ";}
				// show results only with prices
				if ($results_mode==1) { $query .= "AND avail_price > 0 "; }
				$query .= "ORDER BY datetime ASC; ";

				//$unsorted = get_coldata($search_str,'supply');
				break;

			//Add in the queries for repair items	
			case 'repair_quoted':
				$query = "SELECT datetime, qty qty, price price, companyid cid, name, partid, userid, 'Active' status ";
				$query .= "FROM repair_quotes, search_meta, companies ";
				$query .= "WHERE  repair_quotes.metaid = search_meta.id AND companies.id = search_meta.companyid ";
				if ($partid_str){$query .= " AND (".$partid_str.") ";}
				if ($record_start && $record_end){$query .= " AND datetime between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
				if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
				if ($min_price){$query .= " AND price >= ".$min." ";}
				if ($max_price){$query .= " AND price <= ".$max." ";}
				// show results only with prices
				if ($results_mode==1) { $query .= "AND price > 0 "; }
				$query .= "ORDER BY datetime ASC; ";

				break;

			case 'repair_sources':
				$query = "SELECT datetime, qty qty, price price, companyid cid, name, partid, userid, 'Active' status ";
				$query .= "FROM repair_sources, search_meta, companies ";
				$query .= "WHERE  repair_sources.metaid = search_meta.id AND companies.id = search_meta.companyid ";
				if ($partid_str){$query .= " AND (".$partid_str.") ";}
				if ($record_start && $record_end){$query .= " AND datetime between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
				if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
				if ($min_price){$query .= " AND price >= ".$min." ";}
				if ($max_price){$query .= " AND price <= ".$max." ";}
				// show results only with prices
				if ($results_mode==1) { $query .= "AND price > 0 "; }
				$query .= "ORDER BY datetime ASC; ";

				break;

			case 'repairs_completed':
				$query = "SELECT ro.created datetime, qty qty, ri.price price, companyid cid, name, partid, ro.ro_number order_num, ro.created_by userid, 'Active' status ";
				$query .= "FROM repair_orders ro, repair_items ri, companies ";
				$query .= "WHERE  companies.id = ro.companyid AND ro.repair_code_id IS NOT NULL AND ro.ro_number = ri.ro_number";
				if ($partid_str){$query .= " AND (".$partid_str.") ";}
				if ($record_start && $record_end){$query .= " AND datetime between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
				if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
				if ($min_price){$query .= " AND ri.price >= ".$min." ";}
				if ($max_price){$query .= " AND ri.price <= ".$max." ";}
				// show results only with prices
				if ($results_mode==1) { $query .= "AND ri.price > 0 "; }
				$query .= "ORDER BY created ASC; ";

				break;

			case 'in_repair':
				$query = "SELECT ro.created datetime, qty qty, ri.price price, companyid cid, name, partid, ro.ro_number order_num, ro.created_by userid, 'Active' status ";
				$query .= "FROM repair_orders ro, repair_items ri, companies ";
				$query .= "WHERE  companies.id = ro.companyid AND ro.repair_code_id IS NULL AND ro.ro_number = ri.ro_number";
				if ($partid_str){$query .= " AND (".$partid_str.") ";}
				if ($record_start && $record_end){$query .= " AND datetime between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
				if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
				if ($min_price){$query .= " AND ri.price >= ".$min." ";}
				if ($max_price){$query .= " AND ri.price <= ".$max." ";}
				// show results only with prices
				if ($results_mode==1) { $query .= "AND ri.price > 0 "; }
				$query .= "ORDER BY created ASC; ";

				break;

			case 'repairs':
				$query = "SELECT datetime, qty qty, price price, companyid cid, name, partid, '' order_num, userid, 'Active' status ";
				$query .= "FROM repair_quotes, search_meta, companies ";
				$query .= "WHERE  repair_quotes.metaid = search_meta.id AND companies.id = search_meta.companyid ";
				if ($partid_str){$query .= " AND (".$partid_str.") ";}
				if ($record_start && $record_end){$query .= " AND datetime between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
				if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
				if ($min_price){$query .= " AND price >= ".$min." ";}
				if ($max_price){$query .= " AND price <= ".$max." ";}
				// show results only with prices
				if ($results_mode==1) { $query .= "AND price > 0 "; }
				//$query .= "ORDER BY datetime ASC ";
				$query .= "UNION ";
					$query .= "SELECT ro.created datetime, qty qty, price price, companyid cid, name, partid, ro.ro_number order_num, ro.created_by userid, 'Active' status ";
					$query .= "FROM repair_orders ro, repair_items ri, companies ";
					$query .= "WHERE  companies.id = ro.companyid  AND ro.ro_number = ri.ro_number";
					if ($partid_str){$query .= " AND (".$partid_str.") ";}
					if ($record_start && $record_end){$query .= " AND ro.created between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
					if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
					if ($min_price){$query .= " AND price >= ".$min." ";}
					if ($max_price){$query .= " AND price <= ".$max." ";}
					// show results only with prices
					if ($results_mode==1) { $query .= "AND price > 0 "; }
				//$query .= "ORDER BY datetime ASC ";
				$query .= "UNION ";
					$query .= "SELECT datetime, qty qty, price price, companyid cid, name, partid, '' order_num, userid, 'Active' status ";
					$query .= "FROM repair_sources, search_meta, companies ";
					$query .= "WHERE  repair_sources.metaid = search_meta.id AND companies.id = search_meta.companyid ";
					if ($partid_str){$query .= " AND (".$partid_str.") ";}
					if ($record_start && $record_end){$query .= " AND datetime between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
					if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
					if ($min_price){$query .= " AND price >= ".$min." ";}
					if ($max_price){$query .= " AND price <= ".$max." ";}
					// show results only with prices
					if ($results_mode==1) { $query .= "AND price > 0"; }
				$query .= "ORDER BY datetime ASC; ";

				//$unsorted = get_coldata($search_str,'demand');
				break;

			case 'sales_summary':

				$query = "SELECT created datetime, companyid cid, name, sales_orders.so_number order_num, qty, price, partid, ";
				$query .= "sales_rep_id userid, part, heci, sales_orders.status ";
				$query .= "FROM sales_items, sales_orders, companies, parts ";
				$query .= "WHERE sales_items.so_number = sales_orders.so_number AND companies.id = sales_orders.companyid ";
				$query .= "AND parts.id = sales_items.partid ";
				if ($partid_str){$query .= " AND (".$partid_str.") ";}
				if ($record_start && $record_end){$query .= " AND created between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
				if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
				// show results only with prices
				if ($results_mode==1) { $query .= "AND price > 0 "; }
				$query .= "UNION ";	
					$query .= "SELECT created datetime, companyid cid, name, purchase_orders.po_number order_num, qty, price, partid, ";
					$query .= "sales_rep_id userid, part, heci, purchase_orders.status ";
					$query .= "FROM purchase_items, purchase_orders, companies, parts ";
					$query .= "WHERE purchase_items.po_number = purchase_orders.po_number AND companies.id = purchase_orders.companyid ";
					$query .= "AND parts.id = purchase_items.partid ";
					if ($partid_str){$query .= " AND (".$partid_str.") ";}
					if ($record_start && $record_end){$query .= " AND created between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
					if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
					// show results only with prices
					if ($results_mode==1) { $query .= "AND price > 0 "; }
				$query .= "ORDER BY datetime ASC; ";

				//$unsorted = get_coldata($search_str,'demand');
				break;

			default:
				$query = "";
				break;
		}

		// get local data
		if ($query AND ($partid_str OR count($search_arr)==0)) {
			$result = qdb($query) OR die(qe().' '.$query);
			while ($r = mysqli_fetch_assoc($result)) {
				$unsorted[$r['datetime']][] = $r;
			}
		}
		// sort in one results array, combining where necessary (to elim dups)
		$consolidate = true;
		if ($market_table=='sales' OR $market_table=='purchases' OR (count($search_arr)==0 AND ! $partid_array)) { $consolidate = false; }
		$results = sort_results($unsorted,'desc',$consolidate,$market_table);

		return ($results);
	}

	function sort_results($unsorted,$sort_order='asc',$consolidate=true,$market_table) {
		if ($sort_order=='asc') { ksort($unsorted); }
		else if ($sort_order=='desc') { krsort($unsorted); }

		$results = array();
		$uniques = array();
		$k = 0;
		foreach ($unsorted as $date => $arr) {
			foreach ($arr as $r) {
				// if we're getting repid from the old db, cross-reference it using getRep() just to get the new db userid
/*
				if (isset($r['repid']) AND (! isset($r['userid']) OR ! $r['userid'])) {
					$r['userid'] = getRep($r['repid'],'repid','id');
				}
*/
				if (! $r['userid']) {
					if (substr($r['name'],0,7)=='Verizon') { $r['userid'] = 1; }
					else { $r['userid'] = 2; }
				}
				unset($r['repid']);

				$key = $r['name'].'.'.$date;
				if (! $consolidate) { $key .= '.'.$r['partid']; }
				if ($market_table=='sales' OR $market_table=='purchases') { $key .= '.'.$r['order_num']; }

				if (isset($uniques[$key])) {
					if ($market_table=='sales' OR $market_table=='purchases' OR $market_table=='supply') {
						$results[$uniques[$key]]['qty'] += $r['qty'];
					} else {
						if ($r['qty']>$results[$uniques[$key]]['qty']) {
							$results[$uniques[$key]]['qty'] = $r['qty'];
						}
					}
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
			// if a search string is passed in but there are no results from brians db, don't continue
			if (count($pipe_ids)==0) { return ($unsorted); }
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
			$unsorted = get_details($id_csv,'purchases',$unsorted);
		} else if ($coldata=='supply') {
			$unsorted = get_details($id_csv,'incoming_quote',$unsorted);
		}
		return ($unsorted);
	}

	function get_details($id_csv,$table_name,$results) {
		global $record_start,$record_end,$oldid,$company_filter,$min_price,$max_price;
		$min = format_price($min_price,'','',true);
		$max = format_price($max_price,'','',true);
		
		$db_results = array();

		if (! $id_csv AND ! $record_start AND ! $record_end) {
//			echo 'Valid search result or date range not entered';
			return $db_results;
		}

		// save the original table name passed in above because below we change it to accommodate various other db lookups
		$orig_table = $table_name;

		$and_where = '';
		$add_field = '';
		if ($table_name=='sales') {
			$table_name = 'outgoing_quote';
//			$and_where = "AND win = '1' ";
			$add_field = ', quote_id, win ';
		} else if ($table_name=='purchases' OR $table_name=='incoming_quote') {
			if ($table_name=='purchases') {
				$table_name = 'incoming_quote';
				$and_where = "AND inventory_purchaseorder.purchasequote_ptr_id = inventory_incoming_quote.quote_id ";
			}
			$add_field = ', quote_id ';
		}
		// add sales/purchaser rep id
		if ($table_name=='outgoing_quote' OR $table_name=='incoming_quote') { $add_field .= ", creator_id repid "; } else { $add_field .= ", '' repid "; }

		if ($record_start && $record_end){$and_where .= "AND date between CAST('".substr($record_start,0,10)."' AS DATETIME) and CAST('".substr($record_end,0,10)."' AS DATETIME) ";}

		$query = "SELECT date datetime, quantity qty, price, inventory_company.name, ";
		$query .= "company_id cid, part_number , clei ".$add_field;
		$query .= "FROM inventory_".$table_name.", inventory_company, inventory_inventory ";
		if ($orig_table=='purchases') { $query .= ", inventory_purchaseorder "; }
		$query .= "WHERE inventory_".$table_name.".company_id = inventory_company.id AND quantity > 0 ";
		if ($id_csv) { $query .= "AND inventory_id IN (".$id_csv.") "; }
		if ($oldid) { $query .= "AND company_id = '".$oldid."' "; }
		$query .= $and_where;
		if ($table_name=='userrequest') { $query .= "AND incoming = '0' "; }
		$query .= "AND inventory_inventory.id = inventory_id ";
		if ($min_price){$query .= " AND price >= ".$min." ";}
		if ($max_price){$query .= " AND price <= ".$max." ";}

		$query .= "ORDER BY date ASC, inventory_".$table_name.".id ASC; ";
		$result = qdb($query,'PIPE') OR die(qe('PIPE'));
			
		while ($r = mysqli_fetch_assoc($result)) {
			$r['partid'] = getPartId($r['part_number'],$r['clei']);
			// translate old id to new
			$r['cid'] = dbTranslate($r['cid']);
			if ($company_filter AND $r['cid']<>$company_filter) { continue; }

			$r['name'] = getCompany($r['cid']);
			$db_results[] = $r;
		}

		return (handle_results($db_results,$orig_table,$results));
	}

	function handle_results($db_results,$table_name,$results) {
		foreach ($db_results as $r) {
			if ($r['price']=='0.00') { $r['price'] = ''; }
			else { $r['price'] = format_price($r['price'],2); }

			if ($table_name=='sales' OR $table_name=='purchases') {
				if ($table_name=='sales') {
					if (! $r['win']) { continue; }
					$query3 = "SELECT so_date date FROM inventory_salesorder WHERE quote_ptr_id = '".$r['quote_id']."'; ";
				} else if ($table_name=='purchases') {
					$query3 = "SELECT po_date date FROM inventory_purchaseorder WHERE purchasequote_ptr_id = '".$r['quote_id']."'; ";
				}
				$result3 = qdb($query3,'PIPE') OR die(qe('PIPE').' '.$query3);
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
