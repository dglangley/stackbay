<?php
	include_once 'inc/dbconnect.php';
	include_once 'inc/pipe.php';
	include_once 'inc/format_date.php';
	include_once 'inc/format_price.php';

	$listid = 0;
	if (isset($_REQUEST['listid']) AND is_numeric($_REQUEST['listid']) AND $_REQUEST['listid']>0) { $listid = $_REQUEST['listid']; }
	$pg = 1;
	if (isset($_REQUEST['pg']) AND is_numeric($_REQUEST['pg']) AND $_REQUEST['pg']>0) { $pg = $_REQUEST['pg']; }

	$FREQS = array('demand'=>array(),'supply'=>array());
	$freq_min = 2;
	$query = "SELECT * FROM company_activity WHERE demand_volume > $freq_min OR supply_volume > $freq_min; ";
	$result = qdb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		if ($r['demand_volume']>$freq_min) {
			$FREQS['demand'][$r['companyid']] = $r['demand_volume'];
		}
		if ($r['supply_volume']>$freq_min) {
			$FREQS['supply'][$r['companyid']] = $r['supply_volume'];
		}
	}

	$PIPE_IDS = array();
	$avg_cost = '';
	function getPipeIds($search_str,$search_by='') {
		global $PIPE_IDS,$avg_cost;

		$searches = explode(' ',$search_str);

		$pipe_ids = array();//all ids for the search string passed in

		foreach ($searches as $search) {
			$search = strtoupper(preg_replace('/[^[:alnum:]]+/','',$search));
			$search_by = strtolower($search_by);
			$keysearch = $search;
			if ($search_by) { $keysearch .= '.'.$search_by; }

			$ids = array();
			if (isset($PIPE_IDS[$keysearch])) {
				foreach ($PIPE_IDS[$keysearch] as $id => $r) {
					$pipe_ids[$id] = $r;
				}
			} else {
				//$query = "SELECT id, manufacturer_id_id manfid, part_number part, short_description description, ";
				//$query .= "clei heci, quantity_stock qty, notes ";
				$query = "SELECT id, avg_cost FROM inventory_inventory WHERE (";
				$subquery = "";
				if ($search_by<>'heci') {
					$subquery .= "clean_part_number LIKE '".res($search,'PIPE')."%' ";
				}
				if ((strlen($search)==7 OR strlen($search)==10 OR $search_by<>'part') AND ! is_numeric($search)) {
					if ($subquery) { $subquery .= "OR "; }
					if ($search_by=='heci' AND strlen($search)==10) {
						$subquery .= "clei = '".res($search,'PIPE')."' ";
					} else {
						$subquery .= "clei LIKE '".res(substr($search,0,7),'PIPE')."%' OR heci LIKE '".res(substr($search,0,7),'PIPE')."%' ";
					}
				}
				if (! $subquery) { $subquery .= "1 = 1 "; }
				$query .= $subquery."); ";
				$result = qdb($query,'PIPE') OR die(qe('PIPE'));
				while ($r = mysqli_fetch_assoc($result)) {
					if ($r['avg_cost']>0) { $avg_cost = $r['avg_cost']; }
					$ids[$r['id']] = $r;//ids for just this sub-divided search str
					$pipe_ids[$r['id']] = $r;//ids for all results of exploded search string
				}
				$PIPE_IDS[$keysearch] = $ids;
			}

			// check aliases?
			if ($search_by<>'heci') {
			}
		}

		return ($pipe_ids);
	}

	function getPipeQty($heci='',$part='') {
		if (strlen($heci)==7 OR strlen($heci)==10) {
			$pipe_ids = getPipeIds($heci,'heci');
			if (count($pipe_ids)==0 AND strlen($heci)==10) { $pipe_ids = getPipeIds(substr($heci,0,7),'heci'); }
		} else {
			$pipe_ids = getPipeIds($part);
		}

		$qty = 0;
		foreach ($pipe_ids as $r) {
			$query = "SELECT COUNT(inventory_itemlocation.id) AS qty ";
			$query .= "FROM inventory_itemlocation, inventory_location ";
			$query .= "WHERE inventory_id = '".$r['id']."' AND no_sales = '0' ";
			$query .= "AND inventory_itemlocation.location_id = inventory_location.id; ";
			$result = qdb($query,'PIPE') OR die(qe('PIPE'));
			while ($r = mysqli_fetch_assoc($result)) {
				$qty += $r['qty'];
			}
		}

		return ($qty);
	}

	function format_market($partid_str,$market_table,$search_str) {
		global $FREQS;

		$last_date = '';
		$last_sum = '';
		$market_str = '';
		$dated_qty = 0;
		$monthly_totals = array();
		$unsorted = array();

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
		$num_results = count($results);
		// number of detailed results instead of month-groups, which is normally only results within the past month
		// unless the only records available are outdated, in which case it would be good to show the first couple in detail
		$num_detailed = 0;

		$summary_past = $GLOBALS['summary_past'];

		$grouped = array();
		$last_date = '';
		foreach ($results as $r) {
			$datetime = substr($r['datetime'],0,10);
			if ($num_detailed==0 OR $market_table<>'demand' OR $datetime>=$summary_past) {
				$group_date = substr($r['datetime'],0,10);
				$last_date = $group_date;
				if ($num_detailed==0 AND $datetime<$summary_past) {
					$summary_past = substr($datetime,0,7).'-01';
					$GLOBALS['summary_past'] = $summary_past;
				}
				$num_detailed++;
			} else {
				// update date baseline for summarize_date() below to show just month rather than full date

				$group_date = substr($r['datetime'],0,7);
			}
			if (! isset($grouped[$group_date])) { $grouped[$group_date] = array('count'=>0,'sum_qty'=>0,'sum_price'=>0,'total_qty'=>0,'datetime'=>'','companies'=>array()); }

			$fprice = format_price($r['price'],true,'',true);
			$grouped[$group_date]['count']++;
			if ($fprice>0) {
				$grouped[$group_date]['sum_qty'] += $r['qty'];
				$grouped[$group_date]['sum_price'] += ($fprice*$r['qty']);
			}
			$grouped[$group_date]['datetime'] = $r['datetime'];
			$grouped[$group_date]['total_qty'] += $r['qty'];

			if ($r['cid']>0 AND ! isset($grouped[$group_date]['companies'][$r['cid']])) {
				$grouped[$group_date]['companies'][$r['cid']] = $r['name'];
			}
		}
		ksort($grouped);

		$cls1 = '';
		$cls2 = '';
		foreach ($grouped as $order_date => $r) {
			// summarized date for heading line
			$sum_date = summarize_date($r['datetime']);

			// add group heading (date with summarized qty)
			if ($last_sum AND $sum_date<>$last_sum) {
				$market_str = $cls1.format_dateTitle($last_date,$dated_qty).$cls2.$market_str;
				$dated_qty = 0;//reset for next date
			}

			$cls1 = '';
			$cls2 = '';
			$summary_form = false;
			if ($r['datetime']<$GLOBALS['summary_lastyear']) {
				$cls1 = '<span class="archives">';
				$cls2 = '</span>';
				$summary_form = true;
			} else if ($r['datetime']<$summary_past) {
				$cls1 = '<span class="summary">';
				$cls2 = '</span>';
				$summary_form = true;
			}

			if (strlen($order_date)==10 AND strlen($last_date)==7) {
				$market_str = '<HR>'.$market_str;
			}

			$last_date = $order_date;
			$last_sum = $sum_date;
			$dated_qty += $r['total_qty'];

			$companies = '';
			$cid = 0;
			foreach ($r['companies'] as $cid => $name) {
				if ((($market_table=='demand' OR $market_table=='sales') AND (! $summary_form OR $r['count']==1 OR isset($FREQS['demand'][$cid])))
					OR (($market_table=='purchases') AND (! $summary_form OR $r['count']==1 OR isset($FREQS['supply'][$cid]))))
				{
					if ($companies) { $companies .= '<br> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;'; }
					$companies .= '<a href="profile.php?companyid='.$cid.'" class="market-company">'.$name.'</a>';
				}
			}
			$price = '';
			if ($r['sum_qty']>0) { $price = format_price(round($r['sum_price']/$r['sum_qty'])); }

			$line_str = '<div class="market-data">';
			if (strlen($order_date)==7) {
				$line_str .= '<span class="pa">'.$r['count'].'x</span> ';
			}
			$line_str .= '<span class="pa">'.round($r['total_qty']/$r['count']).'</span> &nbsp; '.
				$companies.' <span class="pa">'.format_price($price,false).'</span></div>';

			// append to column string
			$market_str = $cls1.$line_str.$cls2.$market_str;
		}

		// append last remaining data
		if ($num_results>0) {
			$market_str = $cls1.format_dateTitle($order_date,$dated_qty).$cls2.$market_str;
		}

		if ($market_str) {
			$market_str = '<a href="#" class="market-title">'.ucfirst($market_table).'</a><div class="market-body">'.$market_str.'</div>';
		} else {
			$market_str = '<span class="info">- No '.ucfirst($market_table).' -</span>';
		}

		return ($market_str);
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

	function sort_results($unsorted,$sort_order='asc') {
		if ($sort_order=='asc') { ksort($unsorted); }
		else if ($sort_order=='desc') { krsort($unsorted); }

		$results = array();
		$uniques = array();
//		$grouped = array();
		$k = 0;
		foreach ($unsorted as $date => $arr) {
			foreach ($arr as $r) {
				$key = $r['name'].'.'.$date;
/*
				if (! $r['partid']) {
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
//					unset($r['cid']);
					unset($r['partid']);
					$grouped[$month] = $r;
					continue;
				}
*/

//				$key = $r['cid'].'.'.$r['datetime'].'.'.$r['partid'];
				if (isset($uniques[$key])) {
					if ($r['qty']>$results[$uniques[$key]]['qty']) {
						$results[$uniques[$key]]['qty'] = $r['qty'];
					}

					continue;
				}
				$uniques[$key] = $k;

				$results[$k++] = $r;
			}
		}

/*
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
*/

		return ($results);
	}

	$yesterday = format_date(date("Y-m-d"),'Y-m-d',array('d'=>-1));
	$lastWeek = format_date(date("Y-m-d"),'Y-m-d',array('d'=>-7));
	$lastYear = format_date(date("Y-m-d"),'Y-m-01',array('m'=>-11));
	function format_dateTitle($order_date,$dated_qty) {
		global $today,$yesterday;

/*
		if ($order_date==$today) { $date = 'Today'; }
		else if ($order_date==$yesterday) { $date = 'Yesterday'; }
		else if ($order_date>$lastWeek) { $date = format_date($order_date,'D'); }
		else if ($order_date>=$lastYear) { $date = format_date($order_date,'M j'); }
		else { $date = format_date($order_date,'M j, y'); }
*/
		$date = summarize_date($order_date);

		$dtitle = '<div class="date-group"><a href="javascript:void(0);" class="modal-results" data-target="marketModal">'.$date.': '.
			'qty '.$dated_qty.' <i class="fa fa-list-alt"></i></a></div>';
		return ($dtitle);
	}

?>
<!DOCTYPE html>
<html>
<head>
	<title>VenTel Market Manager</title>
	<?php
		include_once 'inc/scripts.php';
	?>
</head>
<body class="sub-nav">

	<?php include_once 'modal/results.php'; ?>
	<?php include_once 'modal/notes.php'; ?>
	<?php include_once 'inc/keywords.php'; ?>
	<?php include_once 'inc/dictionary.php'; ?>
	<?php include_once 'inc/logSearch.php'; ?>
	<?php include_once 'inc/format_price.php'; ?>
	<?php include_once 'inc/getQty.php'; ?>

	<?php include_once 'inc/navbar.php'; ?>

	<form class="form-inline results-form" method="post" action="save-results.php" enctype="multipart/form-data" >

<?php
	if (! $s AND ! $listid) {
?>
    <div id="pad-wrapper">

    <table class="table">
		<tr>
			<td class="col-md-12 text-center">
				Enter your search above, or tap <i class="fa fa-list-ol"></i> for more options...
			</td>
		</tr>
	</table>
<?php
	} else {
		$searchlistid = 0;
		if ($s) { $searchlistid = logSearch($s,$search_field,$search_from_right,$qty_field,$qty_from_right,$price_field,$price_from_right); }
?>
	<input type="hidden" name="searchlistid" value="<?php echo $searchlistid; ?>">

    <table class="table table-header">
		<tr>
			<td class="col-md-2">
				<div id="remote-warnings"><a class="btn btn-danger btn-sm hidden" id="remote-bb"><img src="/img/bb.png" /></a></div>
			</td>
			<td class="text-center col-md-5">
			</td>
			<td class="col-md-4">
				<div class="pull-right form-group">
					<input class="btn btn-success btn-sm" type="submit" name="save-demand" value="SALES REQUEST">
					<select name="companyid" id="companyid" class="company-selector">
						<option value="">- Select a Company -</option>
					</select>
					<input class="btn btn-warning btn-sm" type="submit" name="save-availability" value="AVAILABILITY">
				</div>
			</td>
		</tr>
	</table>

    <div id="pad-wrapper">

        <!-- the script for the toggle all checkboxes from header is located in js/theme.js -->
        <div class="table-products">
            <div class="row">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th class="col-md-6">
								<span class="qty-header">Qty</span>
                                Product Description
								<span class="price-header">Price</span>
                            </th>
                            <th class="col-md-6 text-center">
                                <span class="line"></span>Market
                            </th>
<!--
                            <th class="col-md-1">
                                <span class="line"></span>
								<span class="pull-right">Response</span>
                            </th>
-->
                        </tr>
                    </thead>
<?php
//	if (! $s) { $s = 'UN375F'.chr(10).'090-42140-13'.chr(10).'IXCON'; }

	$lines = array();
	if ($listid) {
		$search_index = 0;
		$qty_index = 1;
//		$query = "SELECT part, heci FROM market, parts WHERE source = '".res($listid)."' AND parts.id = market.partid; ";
		$query = "SELECT search_meta.id metaid, uploads.type FROM search_meta, uploads ";
		$query .= "WHERE uploads.id = '".res($listid)."' AND uploads.metaid = search_meta.id; ";
		$result = qdb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			if ($r['type']=='demand') { $table_qty = 'request_qty'; }
			else { $table_qty = 'avail_qty'; }

			$query2 = "SELECT search, ".$table_qty." qty FROM parts, ".$r['type'].", searches ";
			$query2 .= "WHERE metaid = '".$r['metaid']."' AND parts.id = partid AND ".$r['type'].".searchid = searches.id; ";
			$result2 = qdb($query2);
			while ($r2 = mysqli_fetch_assoc($result2)) {
				if (array_search($r2['search'],$lines)!==false) { continue; }

				$lines[] = $r2['search'].' '.$r2['qty'];
			}
		}
	} else {
		$lines = explode(chr(10),$s);
	}

	foreach ($lines as $n => $line) {
		$line = trim($line);
		if (! $line) { continue; }

		$rows[] = $line;
	}
	unset($lines);

	$per_pg = 50;
	$min_ln = ($pg*$per_pg)-$per_pg;
	$max_ln = ($min_ln+$per_pg)-1;
	$num_rows = count($rows);

	$x = 0;//line number index for tracking under normal circumstances, but also for favorites-only views
	foreach ($rows as $ln => $line) {
		$terms = preg_split('/[[:space:]]+/',$line);
		$search_str = trim($terms[$search_index]);
		if (! $search_str) { continue; }

		$search_qty = 1;//default
		if (isset($terms[$qty_index])) {
			$qty_text = trim($terms[$qty_index]);
			$qty_text = preg_replace('/^(qty|qnty|quantity)?([.]|-)?0?([0-9]+)([.]|-)?(x|ea)?/i','$3',$qty_text);

			if (is_numeric($qty_text) AND $qty_text>0) { $search_qty = $qty_text; }
		}

		$search_price = "0.00";//default
		if ($price_index!==false AND isset($terms[$price_index])) {
			$price_text = trim($terms[$price_index]);
			$price_text = preg_replace('/^([$])([0-9]+)([.][0-9]{0,2})?/i','$2$3',$price_text);

			if ($price_text) { $search_price = number_format($price_text,2,'.',''); }
		}

		// if 10-digit string, detect if qualifying heci, determine if heci so we can search by 7-digit instead of full 10
		$heci7_search = false;
		if (strlen($search_str)==10 AND ! is_numeric($search_str) AND preg_match('/^[[:alnum:]]{10}$/',$search_str)) {
			$query = "SELECT heci FROM parts WHERE heci LIKE '".substr($search_str,0,7)."%'; ";
			$result = qdb($query);
			if (mysqli_num_rows($result)>0) { $heci7_search = true; }
		}

		if ($heci7_search) {
			$results = hecidb(substr($search_str,0,7));
		} else {
			$results = hecidb(format_part($search_str));
		}

		// gather all partid's first
		$partid_str = "";
		$partids = "";//comma-separated for data-partids tag

		$favs = array();
		$num_favs = 0;

		// pre-process results so that we can build a partid string for this group as well as to group results
		// if the user is showing just favorites
		foreach ($results as $partid => $P) {
//			print "<pre>".print_r($P,true)."</pre>";
//                                        <img src="/products/images/echo format_part($P['part']).jpg" alt="pic" class="img" />
			if ($partid_str) { $partid_str .= "OR "; }
			$partid_str .= "partid = '".$partid."' ";
			if ($partids) { $partids .= ","; }
			$partids .= $partid;

			// check favorites
			$favs[$partid] = 'star-o';
			$query = "SELECT * FROM favorites WHERE partid = '".$partid."'; ";
			$result = qdb($query);
			$num_favs += mysqli_num_rows($result);
			while ($r = mysqli_fetch_assoc($result)) {
				if ($r['userid']==$U['id']) { $favs[$partid] = 'star text-danger'; }
				else if ($favs[$partid]<>'star text-danger') { $favs[$partid] = 'star-half-o text-danger'; }
			}
		}

		if ($favorites AND $num_favs==0) { continue; }

		if ($x<$min_ln) { $x++; continue; }
		else if ($x>$max_ln) { break; }
		$x++;

		$num_results = count($results);
		$s = '';
		if ($num_results<>1) { $s = 's'; }

		$avg_cost = '';
		$results_rows = '';
		$k = 0;
		foreach ($results as $partid => $P) {
			//$itemqty = getQty($partid);
			$itemqty = getPipeQty($P['heci'],$P['part']);
			$rowcls = '';
			if ($itemqty>0) { $rowcls = ' info'; }

			$itemprice = "0.00";
			$fav_flag = $favs[$partid];

			$partstrs = explode(' ',$P['part']);
			$primary_part = $partstrs[0];

			$chkd = '';
			if ($k==0 OR $itemqty>0) { $chkd = ' checked'; }

			$results_rows .= '
                        <!-- row -->
                        <tr class="product-results" id="row-'.$partid.'">
                            <td class="descr-row'.$rowcls.'">
								<div class="product-action text-center">
                                	<div><input type="checkbox" class="item-check" name="items['.$ln.']['.$k.']" value="'.$partid.'"'.$chkd.'></div>
<!--
<div class="action-items">
-->
                                    <a href="javascript:void(0);" data-partid="'.$partid.'" class="fa fa-'.$fav_flag.' fa-lg fav-icon"></a>
<!--
</div>
-->
								</div>
								<div class="qty">
									<div class="form-group">
										<input name="sellqty['.$ln.'][]" type="text" value="'.$itemqty.'" size="2" placeholder="Qty" class="input-xs form-control" />
									</div>
								</div>
                                <div class="product-img">
                                    <img src="http://www.ven-tel.com/img/parts/'.format_part($primary_part).'.jpg" alt="pic" class="img" data-part="'.$primary_part.'" />
                                </div>
                                <div class="product-descr" data-partid="'.$partid.'">
									<span class="descr-label"><span class="part-label">'.$P['Part'].'</span> &nbsp; <span class="heci-label">'.$P['HECI'].'</span></span>
                                   	<div class="description descr-label"><span class="manfid-label">'.dictionary($P['manf']).'</span> <span class="systemid-label">'.dictionary($P['system']).'</span> <span class="description-label">'.dictionary($P['description']).'</span></div>

									<div class="descr-edit hidden">
										<p>
		        							<button type="button" class="close parts-edit"><span>&times;</span></button>
											<div class="form-group">
												<input type="text" value="'.$P['Part'].'" class="form-control" data-partid="'.$partid.'" data-field="part">
											</div>
											<div class="form-group">
												<input type="text" value="'.$P['HECI'].'" class="form-control" data-partid="'.$partid.'" data-field="heci">
											</div>
										</p>
										<p>
											<input type="text" name="descr[]" value="'.$P['description'].'" class="form-control" data-partid="'.$partid.'" data-field="description">
										</p>
										<p>
											<div class="form-group">
												<select name="manfid[]" class="manf-selector" data-partid="'.$partid.'" data-field="manfid">
													<option value="'.$P['manfid'].'">'.$P['manf'].'</option>
												</select>
											</div>
											<div class="form-group">
												<select name="systemid[]" class="system-selector" data-partid="'.$partid.'" data-field="systemid">
													<option value="'.$P['systemid'].'">'.$P['system'].'</option>
												</select>
											</div>
										</p>
									</div>
								</div>
								<div class="price">
									<div class="form-group">
										<div class="input-group sell">
											<span class="input-group-btn">
												<button class="btn btn-default input-xs control-toggle" type="button"><i class="fa fa-lock"></i></button>
											</span>
											<input type="text" name="sellprice['.$ln.'][]" value="'.$itemprice.'" size="6" placeholder="0.00" class="input-xs form-control price-control sell-price" />
										</div>
									</div>
								</div>
                            </td>
			';

			// if on the first result, build out the market column that runs down all rows of results
			if ($k==0) {
				$sales_col = format_market($partid_str,'sales',$search_str);
				$demand_col = format_market($partid_str,'demand',$search_str);
				$purchases_col = format_market($partid_str,'purchases',$search_str);

				$results_rows .= '
							<!-- market-row for all items within search result section -->
                            <td rowspan="'.($num_results+1).'" class="market-row">
								<table class="table market-table">
									<tr>
										<td class="col-sm-3 bg-availability">
											<a href="javascript:void(0);" class="market-title">Supply</a> <a href="javascript:void(0);" class="market-download"><i class="fa fa-download"></i></a>
											<div class="market-results" id="'.$ln.'-'.$partid.'" data-partids="'.$partids.'" data-ln="'.$ln.'"></div>
										</td>
										<td class="col-sm-3 bg-purchases">
											'.$purchases_col.'
										</td>
										<td class="col-sm-3 bg-sales">
											'.$sales_col.'
										</td>
										<td class="col-sm-3 bg-demand">
											'.$demand_col.'
										</td>
									</tr>
								</table>
                            </td>
				';
			}
			$k++;

			$results_rows .= '
                            <td class="product-actions text-right">
								<div class="price">
									<div class="form-group">
<!--
										<div class="input-group buy">
											<span class="input-group-btn">
												<button class="btn btn-default input-xs control-toggle" type="button"><i class="fa fa-lock"></i></button>
											</span>
											<input name="buyprice['.$ln.'][]" type="text" value="0.00" size="6" placeholder="Buy" class="input-xs form-control price-control" />
										</div>
-->
									</div>
								</div>
                            </td>
                        </tr>
			';
		}
?>

                    <tbody>
                        <!-- row -->
                        <tr class="first">
                            <td>
								<div class="product-action text-center">
	                                <div><input type="checkbox" class="checkAll" checked></div>
<div class="action-meta">
					           		<a href="javascript:void(0);" class="parts-merge" title="merge two selected part(s) into one"><i class="fa fa-chain fa-lg"></i></a>
					           		<a href="javascript:void(0);" class="parts-edit" title="edit selected part(s)"><i class="fa fa-pencil fa-lg"></i></a>
</div>
								</div>
								<div class="qty">
									<input type="text" name="search_qtys[<?php echo $ln; ?>]" value="<?php echo $search_qty; ?>" class="form-control input-xs search-qty input-primary" /><br/>
									<span class="info">their qty</span>
								</div>
								<div class="product-descr">
									<input type="text" name="searches[<?php echo $ln; ?>]" value="<?php echo $search_str; ?>" class="product-search text-primary" /><br/>
									<span class="info"><?php echo $num_results.' result'.$s; ?></span>
								</div>
								<div class="price pull-right">
									<div class="form-group target text-right">
										<input name="list_price[<?php echo $ln; ?>]" type="text" value="<?php echo $search_price; ?>" size="6" placeholder="0.00" class="input-xs form-control price-control input-primary" />
										<span class="info">their price</span>
									</div>
								</div>
							</td>
                            <td>
								<div class="row">
									<div class="col-sm-3 text-center"><br/><span class="info">market pricing</span></div>
									<div class="col-sm-3 text-center"><?php echo format_price($avg_cost); ?><br/><span class="info">avg cost</span></div>
									<div class="col-sm-3 text-center"><br/><span class="info">shelflife</span></div>
									<div class="col-sm-3 text-center"><br/><span class="info">quotes-to-sale</span></div>
								</div>
							</td>
<!--
                            <td class="text-right">
								<div class="price">
									<div class="form-group target">
										<input name="list_price[<?php echo $ln; ?>]" type="text" value="<?php echo $search_price; ?>" size="6" placeholder="0.00" class="input-xs form-control price-control input-primary" />
										<span class="info">their price</span>
									</div>
								</div>
							</td>
-->
						</tr>

						<?php echo $results_rows; ?>

                        <!-- row -->
                        <tr>
                            <td> </td>
                            <td> </td>
                        </tr>
                    </tbody>
<?php
	}
?>
                </table>
            </div>
<?php
		if ($num_rows>$per_pg) {
			$pages = ceil($num_rows/$per_pg);
			$paginates = '';
			$end_pg = '';
			if ($pages>4) {
				$paginates = '<li><a href="javascript:void(0);" data-pg="1" data-listid="'.$listid.'">&laquo;</a></li>'.chr(10);
                $end_pg = '<li><a href="javascript:void(0);" data-pg="'.$pages.'" data-listid="'.$listid.'">&raquo;</a></li>'.chr(10);
			}
			$pstart = 1;
			if ($pg>2) { $pstart = $pg-2; }
			for ($p=$pstart; $p<=($pstart+3); $p++) {
				$cls = '';
				if ($p==$pg) { $cls = ' class="active"'; }
                $paginates .= '<li'.$cls.'><a href="javascript:void(0);" data-pg="'.$p.'" data-listid="'.$listid.'">'.$p.'</a></li>'.chr(10);
			}
			$paginates .= $end_pg;
?>
            <ul class="pagination">
				<?php echo $paginates; ?>
            </ul>
<?php
		}
?>
        </div>
<?php
	}//end if ($s)
?>

    </div>

	</form>

<div class="modal fade" id="image-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="prod-image-title"></h4>
      </div>
      <div class="modal-body">
		<img id="modal-prod-img">
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<?php include_once 'inc/footer.php'; ?>

</body>
</html>
