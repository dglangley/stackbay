<?php
	include_once 'getPartId.php';
	include_once 'getRep.php';
	include_once 'getCompany.php';
	include_once 'format_price.php';
	include_once 'format_date.php';
	include_once 'keywords.php';
	
	$record_start = '';
	$record_end = '';

	function getRecords($search_arr = '',$partid_array = '',$array_format='csv',$market_table='demand',$results_mode=0, $start = '', $end = '', $order_status, $item_id = '') {//, $start = '', $end = '') {
		global $record_start,$record_end,$oldid,$company_filter,$sales_min,$sales_max,$min_price,$max_price;
		$unsorted = array();

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

		if ((count($search_arr)==0 && ! $partid_array)&&(! $record_start && ! $record_end) && ! $item_id && ! $company_filter){
			echo 'Please enter filters to get values.';
			return $unsorted;
		}

		// added 3-21-18 to build partids array based on keyword ($search_arr) if passed in (previously, this function
		// did not seem to honor this parameter)
//		echo $market_table.':'.$partid_array.':'.$item_id.'<BR>';print_r($search_arr);return ($unsorted);
		$orders = '';
		if (count($search_arr)>0) {
			foreach ($search_arr as $str) {
				if (is_numeric($str)) {
					if ($orders) { $orders .= ','; }
					$orders .= $str;
				}
				$H = hecidb($str);
				foreach ($H as $partid => $r) {
					if ($partid_array) { $partid_array .= ','; }
					$partid_array .= $partid;
				}
			}
			// no db results to search string, no point in querying results below
			if (! $partid_array) {
//				return ($unsorted);
			}
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
			case 'Demand':
				
				$query = "SELECT datetime, request_qty qty, quote_price price, companyid cid, name, partid, userid, 'Active' status, 'Sale' order_type ";
				$query .= "FROM demand, search_meta, companies ";
				$query .= "WHERE  demand.metaid = search_meta.id AND companies.id = search_meta.companyid ";
				if ($orders) {
					$query .= "AND (demand.metaid IN (".$orders.") ";
					if ($partid_str) {
						$query .= "OR (".$partid_str.") ";
					}
					$query .= ") ";
				} else if ($partid_str) {
					$query .= " AND (".$partid_str.") ";
				}
				if ($record_start) { $query .= "AND datetime >= '".$record_start."' "; }
				if ($record_end) { $query .= "AND datetime <= '".$record_end."' "; }
//				if ($record_start && $record_end){$query .= " AND datetime between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
				if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
				if ($min_price){$query .= " AND quote_price >= ".$min_price." ";}
				if ($max_price){$query .= " AND quote_price <= ".$max_price." ";}
				// show results only with prices
				if ($results_mode==1) { $query .= "AND quote_price > 0 "; }
				$query .= "ORDER BY datetime ASC; ";

				break;

			case 'purchases':
			case 'Purchase':
				$query = "SELECT created datetime, companyid cid, name, '' cust_ref, purchase_orders.po_number order_num, freight_carrier_id, freight_services_id, qty, price, partid, ";
				$query .= "sales_rep_id userid, part, heci, purchase_orders.status, 'Purchase' order_type ";
				$query .= "FROM purchase_items, purchase_orders, companies, parts ";
				$query .= "WHERE purchase_items.po_number = purchase_orders.po_number AND companies.id = purchase_orders.companyid ";
				$query .= "AND parts.id = purchase_items.partid ";
				if ($orders) {
					$query .= "AND (purchase_orders.po_number IN (".$orders.") ";
					if ($partid_str) {
						$query .= "OR (".$partid_str.") ";
					}
					$query .= ") ";
				} else if ($partid_str) {
					$query .= " AND (".$partid_str.") ";
				}
				if ($record_start) { $query .= "AND created >= '".$record_start."' "; }
				if ($record_end) { $query .= "AND created <= '".$record_end."' "; }
//				if ($record_start && $record_end){$query .= " AND created between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
				if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
				if ($min_price){$query .= " AND price >= ".$min_price." ";}
				if ($max_price){$query .= " AND price <= ".$max_price." ";}
				// show results only with prices
				if ($results_mode==1) { $query .= "AND quote_price > 0 "; }
				if ($order_status){$query .= " AND purchase_orders.status IN ('".$order_status."') ";}
				if ($item_id){$query .= " AND purchase_items.id = '".$item_id."' ";}
				$query .= "ORDER BY created ASC; ";

				break;

			case 'sales':
			case 'Sale':
				$query = "SELECT created datetime, companyid cid, name, cust_ref, sales_orders.so_number order_num, freight_carrier_id, freight_services_id, qty, price, partid, ";
				$query .= "sales_rep_id userid, part, heci, sales_orders.status, 'Sale' order_type ";
				$query .= "FROM sales_items, sales_orders, companies, parts ";
				$query .= "WHERE sales_items.so_number = sales_orders.so_number AND companies.id = sales_orders.companyid ";
				$query .= "AND parts.id = sales_items.partid ";
				if ($orders) {
					$query .= "AND (sales_orders.so_number IN (".$orders.") ";
					if ($partid_str) {
						$query .= "OR (".$partid_str.") ";
					}
					$query .= ") ";
				} else if ($partid_str) {
					$query .= " AND (".$partid_str.") ";
				}
				if ($record_start) { $query .= "AND created >= '".$record_start."' "; }
				if ($record_end) { $query .= "AND created <= '".$record_end."' "; }
//				if ($record_start && $record_end){$query .= " AND created between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
				if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
				if ($min_price){$query .= " AND price >= ".$min_price." ";}
				if ($max_price){$query .= " AND price <= ".$max_price." ";}
				if ($order_status){$query .= " AND sales_orders.status IN ('".$order_status."') ";}
				if ($item_id){$query .= " AND sales_items.id = '".$item_id."' ";}
				if (! $item_id AND ! $order_status) { $query .= "AND price > 0 "; }
				$query .= "ORDER BY created ASC; ";

				break;

			case 'supply':
			case 'Supply':
				$query = "SELECT datetime, avail_qty qty, avail_price price, companyid cid, name, partid, userid, 'Active' status, 'Purchase' order_type ";
				$query .= "FROM availability, search_meta, companies ";
				$query .= "WHERE  availability.metaid = search_meta.id AND companies.id = search_meta.companyid ";
				if ($orders) {
					$query .= "AND (availability.metaid IN (".$orders.") ";
					if ($partid_str) {
						$query .= "OR (".$partid_str.") ";
					}
					$query .= ") ";
				} else if ($partid_str) {
					$query .= " AND (".$partid_str.") ";
				}
				if ($record_start) { $query .= "AND datetime >= '".$record_start."' "; }
				if ($record_end) { $query .= "AND datetime <= '".$record_end."' "; }
//				if ($record_start && $record_end){$query .= " AND datetime between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
				if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
				if ($min_price){$query .= " AND avail_price >= ".$min_price." ";}
				if ($max_price){$query .= " AND avail_price <= ".$max_price." ";}
				// show results only with prices
				if ($results_mode==1) { $query .= "AND avail_price > 0 "; }
				$query .= "ORDER BY datetime ASC; ";

				break;

			//Add in the queries for repair items	
			case 'repairs_quoted':
				$query = "SELECT datetime, qty qty, price price, companyid cid, name, partid, userid, 'Active' status, 'Repair' order_type ";
				$query .= "FROM repair_quotes, search_meta, companies ";
				$query .= "WHERE  repair_quotes.metaid = search_meta.id AND companies.id = search_meta.companyid ";
				if ($orders) {
					$query .= "AND (repair_quotes.metaid IN (".$orders.") ";
					if ($partid_str) {
						$query .= "OR (".$partid_str.") ";
					}
					$query .= ") ";
				} else if ($partid_str) {
					$query .= " AND (".$partid_str.") ";
				}
				if ($record_start) { $query .= "AND datetime >= '".$record_start."' "; }
				if ($record_end) { $query .= "AND datetime <= '".$record_end."' "; }
//				if ($record_start && $record_end){$query .= " AND datetime between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
				if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
				if ($min_price){$query .= " AND price >= ".$min_price." ";}
				if ($max_price){$query .= " AND price <= ".$max_price." ";}
				// show results only with prices
				if ($results_mode==1) { $query .= "AND price > 0 "; }
				$query .= "ORDER BY datetime ASC; ";

				break;

			case 'repair_sources':
				$query = "SELECT datetime, qty qty, price price, companyid cid, name, partid, userid, 'Active' status, 'Repair' order_type ";
				$query .= "FROM repair_sources, search_meta, companies ";
				$query .= "WHERE  repair_sources.metaid = search_meta.id AND companies.id = search_meta.companyid ";
				if ($orders) {
					$query .= "AND (repair_sources.metaid IN (".$orders.") ";
					if ($partid_str) {
						$query .= "OR (".$partid_str.") ";
					}
					$query .= ") ";
				} else if ($partid_str) {
					$query .= " AND (".$partid_str.") ";
				}
				if ($record_start) { $query .= "AND datetime >= '".$record_start."' "; }
				if ($record_end) { $query .= "AND datetime <= '".$record_end."' "; }
//				if ($record_start && $record_end){$query .= " AND datetime between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
				if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
				if ($min_price){$query .= " AND price >= ".$min_price." ";}
				if ($max_price){$query .= " AND price <= ".$max_price." ";}
				// show results only with prices
				if ($results_mode==1) { $query .= "AND price > 0 "; }
				$query .= "ORDER BY datetime ASC; ";

				break;

			case 'repairs_completed':
			case 'Repair':
				$query = "SELECT ro.created datetime, qty qty, ri.price price, companyid cid, name, cust_ref, partid, ro.ro_number order_num, freight_carrier_id, freight_services_id, ro.created_by userid, ro.status, 'Repair' order_type ";
				$query .= "FROM repair_orders ro, repair_items ri, companies ";
				//$query .= "WHERE  companies.id = ro.companyid AND ri.repair_code_id IS NOT NULL AND ro.ro_number = ri.ro_number";
				$query .= "WHERE companies.id = ro.companyid AND ro.ro_number = ri.ro_number ";
				if ($orders) {
					$query .= "AND (ro.ro_number IN (".$orders.") ";
					if ($partid_str) {
						$query .= "OR (".$partid_str.") ";
					}
					$query .= ") ";
				} else if ($partid_str) {
					$query .= " AND (".$partid_str.") ";
				}
				if ($record_start) { $query .= "AND ro.created >= '".$record_start."' "; }
				if ($record_end) { $query .= "AND ro.created <= '".$record_end."' "; }
//				if ($record_start && $record_end){$query .= " AND ro.created between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
				if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
				if ($min_price){$query .= " AND ri.price >= ".$min_price." ";}
				if ($max_price){$query .= " AND ri.price <= ".$max_price." ";}
				// show results only with prices
				if ($results_mode==1) { $query .= "AND ri.price > 0 "; }
				if ($order_status){$query .= " AND ro.status IN ('".$order_status."') ";}
				if ($item_id){$query .= " AND ri.id = '".$item_id."' ";}
				$query .= "ORDER BY created ASC; ";

				break;

			// New additional code added to handle Services
			case 'Service':
				$query = "SELECT so.datetime, qty qty, si.amount price, companyid cid, name, '' cust_ref, item_id, so.so_number order_num, '' freight_carrier_id, '' freight_services_id, so.sales_rep_id userid, 'Active' status, 'Service' order_type ";
				$query .= "FROM service_orders so, service_items si, companies ";
				//$query .= "WHERE  companies.id = so.companyid AND si.repair_code_id IS NOT NULL AND so.ro_number = si.ro_number";
				$query .= "WHERE companies.id = so.companyid AND so.so_number = si.so_number ";
				if ($orders) {
					$query .= "AND (so.so_number IN (".$orders.") ";
					if ($partid_str) {
						//$query .= "OR (".$partid_str.") ";
					}
					$query .= ") ";
				} else if ($partid_str) {
					//$query .= " AND (".$partid_str.") ";
				}
				if ($record_start) { $query .= "AND so.datetime >= '".$record_start."' "; }
				if ($record_end) { $query .= "AND so.datetime <= '".$record_end."' "; }
//				if ($record_start && $record_end){$query .= " AND so.datetime between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
				if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
				if ($min_price){$query .= " AND si.amount >= ".$min_price." ";}
				if ($max_price){$query .= " AND si.amount <= ".$max_price." ";}
				if ($order_status){$query .= " AND so.status IN ('".$order_status."') ";}
				if ($item_id){$query .= " AND si.id = '".$item_id."' ";}
				// show results only with prices
				if ($results_mode==1) { $query .= "AND si.price > 0 "; }
				$query .= "ORDER BY datetime ASC; ";

				break;

			case 'in_repair':
				$query = "SELECT ro.created datetime, qty qty, ri.price price, companyid cid, name, '' cust_ref, partid, ro.ro_number order_num, '' freight_carrier_id, '' freight_services_id, ro.created_by userid, 'Active' status, 'Repair' order_type ";
				$query .= "FROM repair_orders ro, repair_items ri, companies ";
				$query .= "WHERE  companies.id = ro.companyid AND ro.repair_code_id IS NULL AND ro.ro_number = ri.ro_number ";
				if ($orders) {
					$query .= "AND (ro.ro_number IN (".$orders.") ";
					if ($partid_str) {
						$query .= "OR (".$partid_str.") ";
					}
					$query .= ") ";
				} else if ($partid_str) {
					$query .= " AND (".$partid_str.") ";
				}
				if ($record_start) { $query .= "AND datetime >= '".$record_start."' "; }
				if ($record_end) { $query .= "AND datetime <= '".$record_end."' "; }
//				if ($record_start && $record_end){$query .= " AND datetime between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
				if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
				if ($min_price){$query .= " AND ri.price >= ".$min_price." ";}
				if ($max_price){$query .= " AND ri.price <= ".$max_price." ";}
				// show results only with prices
				if ($results_mode==1) { $query .= "AND ri.price > 0 "; }
				$query .= "ORDER BY created ASC; ";

				break;

			case 'repairs':
				$query = "SELECT datetime, qty qty, price price, companyid cid, name, '' cust_ref, partid, '' order_num, '' freight_carrier_id, '' freight_services_id, userid, 'Active' status, 'Repair' order_type ";
				$query .= "FROM repair_quotes, search_meta, companies ";
				$query .= "WHERE  repair_quotes.metaid = search_meta.id AND companies.id = search_meta.companyid ";
				if ($orders) {
					$query .= "AND (repair_quotes.metaid IN (".$orders.") ";
					if ($partid_str) {
						$query .= "OR (".$partid_str.") ";
					}
					$query .= ") ";
				} else if ($partid_str) {
					$query .= " AND (".$partid_str.") ";
				}
				if ($record_start) { $query .= "AND datetime >= '".$record_start."' "; }
				if ($record_end) { $query .= "AND datetime <= '".$record_end."' "; }
//				if ($record_start && $record_end){$query .= " AND datetime between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
				if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
				if ($min_price){$query .= " AND price >= ".$min_price." ";}
				if ($max_price){$query .= " AND price <= ".$max_price." ";}
				// show results only with prices
				if ($results_mode==1) { $query .= "AND price > 0 "; }
				//$query .= "ORDER BY datetime ASC ";
				$query .= "UNION ";
					$query .= "SELECT ro.created datetime, qty qty, price price, companyid cid, name, '' cust_ref, partid, ro.ro_number order_num, '' freight_carrier_id, '' freight_services_id, ro.created_by userid, 'Active' status, 'Repair' order_type ";
					$query .= "FROM repair_orders ro, repair_items ri, companies ";
					$query .= "WHERE  companies.id = ro.companyid  AND ro.ro_number = ri.ro_number";
					if ($orders) {
						$query .= "AND (ro.ro_number IN (".$orders.") ";
						if ($partid_str) {
							$query .= "OR (".$partid_str.") ";
						}
						$query .= ") ";
					} else if ($partid_str) {
						$query .= " AND (".$partid_str.") ";
					}
					if ($record_start) { $query .= "AND ro.created >= '".$record_start."' "; }
					if ($record_end) { $query .= "AND ro.created <= '".$record_end."' "; }
//					if ($record_start && $record_end){$query .= " AND ro.created between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
					if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
					if ($min_price){$query .= " AND price >= ".$min_price." ";}
					if ($max_price){$query .= " AND price <= ".$max_price." ";}
					// show results only with prices
					if ($results_mode==1) { $query .= "AND price > 0 "; }
				//$query .= "ORDER BY datetime ASC ";
				$query .= "UNION ";
					$query .= "SELECT datetime, qty qty, price price, companyid cid, name, '' cust_ref, partid, '' order_num, '' freight_carrier_id, '' freight_services_id, userid, 'Active' status, 'Repair' order_type ";
					$query .= "FROM repair_sources, search_meta, companies ";
					$query .= "WHERE  repair_sources.metaid = search_meta.id AND companies.id = search_meta.companyid ";
					if ($orders) {
						$query .= "AND (repair_sources.metaid IN (".$orders.") ";
						if ($partid_str) {
							$query .= "OR (".$partid_str.") ";
						}
						$query .= ") ";
					} else if ($partid_str) {
						$query .= " AND (".$partid_str.") ";
					}
					if ($record_start) { $query .= "AND datetime >= '".$record_start."' "; }
					if ($record_end) { $query .= "AND datetime <= '".$record_end."' "; }
//					if ($record_start && $record_end){$query .= " AND datetime between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
					if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
					if ($min_price){$query .= " AND price >= ".$min_price." ";}
					if ($max_price){$query .= " AND price <= ".$max_price." ";}
					// show results only with prices
					if ($results_mode==1) { $query .= "AND price > 0"; }
				$query .= "ORDER BY datetime ASC; ";

				break;

			case 'sales_summary':
				$query = "SELECT created datetime, companyid cid, name, '' cust_ref, sales_orders.so_number order_num, '' freight_carrier_id, '' freight_services_id, qty, price, partid, ";
				$query .= "sales_rep_id userid, part, heci, sales_orders.status, 'Sale' order_type ";
				$query .= "FROM sales_items, sales_orders, companies, parts ";
				$query .= "WHERE sales_items.so_number = sales_orders.so_number AND companies.id = sales_orders.companyid ";
				$query .= "AND parts.id = sales_items.partid ";
				if ($orders) {
					$query .= "AND (sales_orders.so_number IN (".$orders.") ";
					if ($partid_str) {
						$query .= "OR (".$partid_str.") ";
					}
					$query .= ") ";
				} else if ($partid_str) {
					$query .= " AND (".$partid_str.") ";
				}
				if ($record_start) { $query .= "AND created >= '".$record_start."' "; }
				if ($record_end) { $query .= "AND created <= '".$record_end."' "; }
//				if ($record_start && $record_end){$query .= " AND created between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
				if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
				// show results only with prices
				if ($results_mode==1) { $query .= "AND price > 0 "; }
				$query .= "UNION ";	
					$query .= "SELECT created datetime, companyid cid, name, '' cust_ref, purchase_orders.po_number order_num, '' freight_carrier_id, '' freight_services_id, qty, price, partid, ";
					$query .= "sales_rep_id userid, part, heci, purchase_orders.status, 'Purchase' order_type ";
					$query .= "FROM purchase_items, purchase_orders, companies, parts ";
					$query .= "WHERE purchase_items.po_number = purchase_orders.po_number AND companies.id = purchase_orders.companyid ";
					$query .= "AND parts.id = purchase_items.partid ";
					if ($orders) {
						$query .= "AND (purchase_orders.po_number IN (".$orders.") ";
						if ($partid_str) {
							$query .= "OR (".$partid_str.") ";
						}
						$query .= ") ";
					} else if ($partid_str) {
						$query .= " AND (".$partid_str.") ";
					}
					if ($record_start) { $query .= "AND created >= '".$record_start."' "; }
					if ($record_end) { $query .= "AND created <= '".$record_end."' "; }
//					if ($record_start && $record_end){$query .= " AND created between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
					if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
					// show results only with prices
					if ($results_mode==1) { $query .= "AND price > 0 "; }
				$query .= "ORDER BY datetime ASC; ";

				break;

			case 'outsourced':
			case 'Outsourced':
				$query = "SELECT datetime, companyid cid, name, '' cust_ref, outsourced_orders.os_number order_num, '' freight_carrier_id, '' freight_services_id, qty, price, item_id partid, ";
				$query .= "sales_rep_id userid, part, heci, outsourced_orders.status, 'Outsourced' order_type ";
				$query .= "FROM outsourced_orders, companies, outsourced_items LEFT JOIN parts ON (outsourced_items.item_id = parts.id AND outsourced_items.item_label = 'partid') ";
				$query .= "WHERE outsourced_items.os_number = outsourced_orders.os_number AND companies.id = outsourced_orders.companyid ";
				if ($orders) {
					$query .= "AND (outsourced_orders.os_number IN (".$orders.") ";
					if ($partid_str) {
						$query .= "OR (".$partid_str.") ";
					}
					$query .= ") ";
				} else if ($partid_str) {
					$query .= " AND (".$partid_str.") ";
				}
				if ($record_start) { $query .= "AND datetime >= '".$record_start."' "; }
				if ($record_end) { $query .= "AND datetime <= '".$record_end."' "; }
//				if ($record_start && $record_end){$query .= " AND datetime between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
				if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
				if ($min_price){$query .= " AND price >= ".$min_price." ";}
				if ($max_price){$query .= " AND price <= ".$max_price." ";}
				if ($order_status){$query .= " AND outsourced_orders.status IN ('".$order_status."') ";}
				if ($item_id){$query .= " AND outsourced_items.id = '".$item_id."' ";}
				$query .= "ORDER BY datetime ASC; ";

				break;

			// Newly added case for ops dash using the getRecords format
			case 'Return':
				$query = "SELECT created datetime, companyid cid, name, '' cust_ref, returns.rma_number order_num, '' freight_carrier_id, '' freight_services_id, return_items.qty, '' price, return_items.partid, ";
				$query .= "created_by userid, part, heci, returns.status, 'Return' order_type ";
				$query .= "FROM returns, companies, return_items LEFT JOIN inventory ON (return_items.inventoryid = inventory.id) LEFT JOIN parts ON (return_items.partid = parts.id) ";
				$query .= "WHERE return_items.rma_number = returns.rma_number AND companies.id = returns.companyid ";
				if ($orders) {
					$query .= "AND (returns.rma_number IN (".$orders.") ";
					if ($partid_str) {
						$query .= "OR (return_items.".$partid_str.") ";
					}
					$query .= ") ";
				} else if ($partid_str) {
					$query .= " AND (return_items.".$partid_str.") ";
				}
				if ($record_start) { $query .= "AND created >= '".$record_start."' "; }
				if ($record_end) { $query .= "AND created <= '".$record_end."' "; }
//				if ($record_start && $record_end){$query .= " AND created between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
				if ($company_filter){$query .= " AND companyid = '".$company_filter."' ";}
				if ($min_price){$query .= " AND price >= ".$min_price." ";}
				if ($max_price){$query .= " AND price <= ".$max_price." ";}
				if ($order_status){$query .= " AND returns.status IN ('".$order_status."') ";}
				if ($item_id){$query .= " AND return_items.id = '".$item_id."' ";}
				$query .= "ORDER BY datetime ASC; ";

				break;

			default:
				$query = "";
				break;
		}

		// get local data
		if ($query AND ($partid_str OR count($search_arr)==0 OR $orders)) {
			$result = qedb($query);
			while ($r = qrow($result)) {
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
		$counter = 1;

		foreach ($unsorted as $date => $arr) {
			foreach ($arr as $r) {
				if (! $r['userid']) {
					if (substr($r['name'],0,7)=='Verizon') { $r['userid'] = 1; }
					else { $r['userid'] = 2; }
				}
				unset($r['repid']);

				$key = $r['name'].'.'.$date;
				if (! $consolidate) { $key .= '.'.$r['partid']; }

				// added 1/23/18 because Michelle!
				if ($market_table=='purchases') { $key .= '.'.$r['price']; }
				if ($market_table=='Purchase' OR $market_table=='Outsourced' OR $market_table=='Service' OR $market_table=='Sale' OR $market_table=='Return' OR $market_table=='Repair') { $key .= '.'.$counter++; }

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
					if ((format_price($r['price'],true,'',true)>0 AND (! $results[$uniques[$key]]['price'] OR $results[$uniques[$key]]['price']=='0.00'))) {
						$results[$uniques[$key]]['price'] = $r['price'];
					}

					// Group the entire order as 1 total for services with a qty of 1
					if($market_table=='Service' OR $market_table=='Outsourced') {
						$results[$uniques[$key]]['price'] += $r['price'];
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
