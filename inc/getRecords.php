<?php
	include_once 'getPartId.php';
	include_once 'getRep.php';
	include_once 'getCompany.php';
	include_once 'format_price.php';
	include_once 'format_date.php';
	
	$record_start = '';
	$record_end = '';
	
	function getRecords($search_arr = '',$partid_array = '',$array_format='csv',$market_table='demand',$results_mode=0, $start = '', $end = '') {//, $start = '', $end = '') {
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
		if(!empty($start)){$record_start = $start;}
		if(!empty($end)){$record_end = $end;}
		
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
			echo 'Please enter filters to get values.';
			return $unsorted;
			
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
				
				$query = "SELECT datetime, request_qty qty, quote_price price, companyid cid, name, partid, userid, 'Active' status, 'Sale' order_type ";
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

				break;

			case 'purchases':
			case 'Purchase':
				$query = "SELECT created datetime, companyid cid, name, purchase_orders.po_number order_num, qty, price, partid, ";
				$query .= "sales_rep_id userid, part, heci, purchase_orders.status, 'Purchase' order_type ";
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

				break;

			case 'sales':
			case 'Sale':
				$query = "SELECT created datetime, companyid cid, name, sales_orders.so_number order_num, qty, price, partid, ";
				$query .= "sales_rep_id userid, part, heci, sales_orders.status, 'Sale' order_type ";
				$query .= "FROM sales_items, sales_orders, companies, parts ";
				$query .= "WHERE sales_items.so_number = sales_orders.so_number AND companies.id = sales_orders.companyid ";
				$query .= "AND parts.id = sales_items.partid ";
				if ($partid_str){$query .= " AND (".$partid_str.") ";}
				if ($record_start && $record_end){$query .= " AND created between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
				if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
				if ($min_price){$query .= " AND price >= ".$min." ";}
				if ($max_price){$query .= " AND price <= ".$max." ";}
				$query .= "AND price > 0 ";
				$query .= "ORDER BY created ASC; ";

				break;

			case 'supply':
				$query = "SELECT datetime, avail_qty qty, avail_price price, companyid cid, name, partid, userid, 'Active' status, 'Purchase' order_type ";
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

				break;

			//Add in the queries for repair items	
			case 'repairs_quoted':
				$query = "SELECT datetime, qty qty, price price, companyid cid, name, partid, userid, 'Active' status, 'Repair' order_type ";
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
				$query = "SELECT datetime, qty qty, price price, companyid cid, name, partid, userid, 'Active' status, 'Repair' order_type ";
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
			case 'Repair':
				$query = "SELECT ro.created datetime, qty qty, ri.price price, companyid cid, name, partid, ro.ro_number order_num, ro.created_by userid, 'Active' status, 'Repair' order_type ";
				$query .= "FROM repair_orders ro, repair_items ri, companies ";
				//$query .= "WHERE  companies.id = ro.companyid AND ri.repair_code_id IS NOT NULL AND ro.ro_number = ri.ro_number";
				$query .= "WHERE companies.id = ro.companyid AND ro.ro_number = ri.ro_number";
				if ($partid_str){$query .= " AND (".$partid_str.") ";}
				if ($record_start && $record_end){$query .= " AND ro.created between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
				if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
				if ($min_price){$query .= " AND ri.price >= ".$min." ";}
				if ($max_price){$query .= " AND ri.price <= ".$max." ";}
				// show results only with prices
				if ($results_mode==1) { $query .= "AND ri.price > 0 "; }
				$query .= "ORDER BY created ASC; ";

				break;

			// New additional code added to handle Services
			case 'Service':
				$query = "SELECT so.datetime, qty qty, si.amount price, companyid cid, name, item_id, so.so_number order_num, so.sales_rep_id userid, 'Active' status, 'Service' order_type ";
				$query .= "FROM service_orders so, service_items si, companies ";
				//$query .= "WHERE  companies.id = so.companyid AND si.repair_code_id IS NOT NULL AND so.ro_number = si.ro_number";
				$query .= "WHERE companies.id = so.companyid AND so.so_number = si.so_number";
				if ($partid_str){$query .= " AND (".$partid_str.") ";}
				if ($record_start && $record_end){$query .= " AND so.datetime between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
				if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
				if ($min_price){$query .= " AND si.amount >= ".$min." ";}
				if ($max_price){$query .= " AND si.amount <= ".$max." ";}
				// show results only with prices
				if ($results_mode==1) { $query .= "AND si.price > 0 "; }
				$query .= "ORDER BY datetime ASC; ";

				break;

			case 'in_repair':
				$query = "SELECT ro.created datetime, qty qty, ri.price price, companyid cid, name, partid, ro.ro_number order_num, ro.created_by userid, 'Active' status, 'Repair' order_type ";
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
				$query = "SELECT datetime, qty qty, price price, companyid cid, name, partid, '' order_num, userid, 'Active' status, 'Repair' order_type ";
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
					$query .= "SELECT ro.created datetime, qty qty, price price, companyid cid, name, partid, ro.ro_number order_num, ro.created_by userid, 'Active' status, 'Repair' order_type ";
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
					$query .= "SELECT datetime, qty qty, price price, companyid cid, name, partid, '' order_num, userid, 'Active' status, 'Repair' order_type ";
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

				break;

			case 'sales_summary':
				$query = "SELECT created datetime, companyid cid, name, sales_orders.so_number order_num, qty, price, partid, ";
				$query .= "sales_rep_id userid, part, heci, sales_orders.status, 'Sale' order_type ";
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
					$query .= "sales_rep_id userid, part, heci, purchase_orders.status, 'Purchase' order_type ";
					$query .= "FROM purchase_items, purchase_orders, companies, parts ";
					$query .= "WHERE purchase_items.po_number = purchase_orders.po_number AND companies.id = purchase_orders.companyid ";
					$query .= "AND parts.id = purchase_items.partid ";
					if ($partid_str){$query .= " AND (".$partid_str.") ";}
					if ($record_start && $record_end){$query .= " AND created between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
					if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
					// show results only with prices
					if ($results_mode==1) { $query .= "AND price > 0 "; }
				$query .= "ORDER BY datetime ASC; ";

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
		if ($market_table=='sales' OR $market_table=='repairs_completed' OR $market_table=='purchases' OR (count($search_arr)==0 AND ! $partid_array)) { $consolidate = false; }
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
				if (! $r['userid']) {
					if (substr($r['name'],0,7)=='Verizon') { $r['userid'] = 1; }
					else { $r['userid'] = 2; }
				}
				unset($r['repid']);

				$key = $r['name'].'.'.$date;
				if (! $consolidate) { $key .= '.'.$r['partid']; }
				if ($market_table=='sales' OR $market_table=='repairs_completed' OR $market_table=='purchases' OR $market_table=='Service') { $key .= '.'.$r['order_num']; }
				// added 1/23/18 because Michelle!
				if ($market_table=='purchases' OR $market_table=='Service') { $key .= '.'.$r['price']; }

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
?>
