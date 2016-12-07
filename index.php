<?php
	include_once 'inc/dbconnect.php';
	include_once 'inc/pipe.php';
	include_once 'inc/format_date.php';
	include_once 'inc/format_price.php';
	include_once 'inc/getPipeIds.php';
	include_once 'inc/getPipeQty.php';
	include_once 'inc/getRecords.php';
	include_once 'inc/getShelflife.php';
	include_once 'inc/array_stristr.php';

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

	function format_market($partid_str,$market_table,$search_strs) {
		global $FREQS;

		$last_date = '';
		$last_sum = '';
		$market_str = '';
		$dated_qty = 0;
		$monthly_totals = array();

		$results = getRecords($search_strs,$partid_str,'statement',$market_table);

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
				// append the companyid to the date to avoid grouping results on the same date
				// because otherwise we end up with averaged prices when we want to see breakouts instead
				$group_date = substr($r['datetime'],0,10).'-'.$r['cid'];

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

			if ($r['cid']>0 AND ! isset($grouped[$group_date]['companies'][$r['cid']]) AND array_search($r['name'],$grouped[$group_date]['companies'])===false) {
				$grouped[$group_date]['companies'][$r['cid']] = $r['name'];
			}
		}
		ksort($grouped);

		$cls1 = '';
		$cls2 = '';
		foreach ($grouped as $order_date => $r) {
			// because we're grouping dates above with suffixed companyid's, we need to shorten them back to just the date
			$order_date = substr($order_date,0,10);

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
		// highlight any records in the past week
		$dateSty = '';
		if ($order_date>$GLOBALS['lastWeek']) { $dateSty = ' style="font-weight:bold"'; }

		$dtitle = '<div class="date-group"><a href="javascript:void(0);" class="modal-results" data-target="marketModal"'.$dateSty.'>'.$date.': '.
			'qty '.$dated_qty.' <i class="fa fa-list-alt"></i></a></div>';
		return ($dtitle);
	}

?>
<!DOCTYPE html>
<html>
<head>
	<title>Stackbay</title>
	<?php
		include_once 'inc/scripts.php';
	?>
</head>
<body class="sub-nav">

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
				Enter your search above, or tap <i class="fa fa-list-ol"></i> for advanced search options...
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
				<div id="remote-warnings">
<?php
	$query = "SELECT * FROM remotes ORDER BY id ASC; ";
	$result = qdb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		echo '<a class="btn btn-danger btn-sm hidden btn-remote" id="remote-'.$r['remote'].'" data-name="'.$r['name'].'"><img src="/img/'.$r['remote'].'.png" /></a>';
	}
?>
				</div>
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
		$query = "SELECT search_meta.id metaid, uploads.type, processed FROM search_meta, uploads ";
		$query .= "WHERE uploads.id = '".res($listid)."' AND uploads.metaid = search_meta.id; ";
		$result = qdb($query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);

			if ($r['processed']) {
				if ($r['type']=='demand') { $table_qty = 'request_qty'; }
				else { $table_qty = 'avail_qty'; }

				$query2 = "SELECT search, ".$table_qty." qty FROM parts, ".$r['type'].", searches ";
				$query2 .= "WHERE metaid = '".$r['metaid']."' AND parts.id = partid AND ".$r['type'].".searchid = searches.id; ";
				$result2 = qdb($query2);
				while ($r2 = mysqli_fetch_assoc($result2)) {
					// does this search string (followed by an appended space, as in the following 'search qty' format) already
					// exist in the array? if so, don't add to list for duplication of calculations below
					if (array_stristr($lines,$r2['search'].' ')!==false) { continue; }

					$lines[] = $r2['search'].' '.$r2['qty'];
				}
			} else {
				// if list is not processed, alert the user
				$ALERTS[] = "Please wait while I process your list. If you do not have an email from me within 10 or 15 minutes, ".
					"you may have unorganized data in your list that I cannot handle.";
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
		$search_str = strtoupper(trim($terms[$search_index]));
		if (! $search_str) { continue; }

		$search_qty = 1;//default
		if (isset($terms[$qty_index])) {
			$qty_text = trim($terms[$qty_index]);
			$qty_text = preg_replace('/^(qty|qnty|quantity)?([.]|-)?(\\()?0?([0-9]+)(\\))?([.]|-)?(x|ea)?/i','$4',$qty_text);

			if (is_numeric($qty_text) AND $qty_text>0) { $search_qty = $qty_text; }
		}

		$search_price = "0.00";//default
		if ($price_index!==false AND isset($terms[$price_index])) {
			$price_text = trim($terms[$price_index]);
			$price_text = preg_replace('/^([$])([0-9]+)([.][0-9]{0,2})?/i','$2$3',$price_text);

			if ($price_text) { $search_price = number_format($price_text,2,'.',''); }
		}

		// can contain additional info about the results, if set; presents itself after the "X results" row below the row's search field
		$explanation = '';

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

		// the LARGE majority of items don't have more than 20 results within a certain group of 7-digit hecis
		if (count($results)>20) {
			$explanation = '<i class="fa fa-warning fa-lg"></i> '.count($results).' results found, limited to first 20!';
			// take the top 20 results
			$results = array_slice($results,0,20,true);
		}

		// gather all partid's first
		$partid_str = "";
		$partids = "";//comma-separated for data-partids tag
		$search_strs = array();

		$favs = array();
		$num_favs = 0;

		$pipe_ids = array();
		$pipe_id_assoc = array();
		// pre-process results so that we can build a partid string for this group as well as to group results
		// if the user is showing just favorites
		foreach ($results as $partid => $P) {
//			print "<pre>".print_r($P,true)."</pre>";
//                                        <img src="/products/images/echo format_part($P['part']).jpg" alt="pic" class="img" />
			if ($partid_str) { $partid_str .= "OR "; }
			$partid_str .= "partid = '".$partid."' ";
			if ($partids) { $partids .= ","; }
			$partids .= $partid;

			$results[$partid]['pipe_id'] = 0;
			if ($P['heci']) {
				$ids = getPipeIds(substr($P['heci'],0,7),'heci');
				foreach ($ids as $id => $arr) {
					if ($arr['heci']===$P['heci']) { $pipe_id_assoc[$id] = $partid; unset($pipe_ids[$id]); $results[$partid]['pipe_id'] = $id; }
					else if (! isset($pipe_id_assoc[$id])) { $pipe_ids[$id] = $arr; }
				}
			}
			$ids = getPipeIds($P['part'],'part');
			foreach ($ids as $id => $arr) {
				if (! isset($pipe_id_assoc[$id])) { $pipe_ids[$id] = $arr; }
			}

			$exploded_strs = explode(' ',$P['part']);
			$search_strs = array_merge($search_strs,$exploded_strs);
			if ($P['heci']) {
				$search_strs[] = substr($P['heci'],0,7);
			}

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

		$id_array = "";//pass in comma-separated values for getShelflife()
		foreach ($pipe_id_assoc as $pipe_id => $partid) {
			if ($id_array) { $id_array .= ','; }
			$id_array .= $pipe_id;
		}
		$shelflife = getShelflife($id_array);

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
			$itemqty = 0;
			// add notes from global $NOTES, keyed by this/each pipeid below
			$notes = '';
			$pipeids_str = '';
			// when a single value, handle accordingly; otherwise by array
			if ($P['pipe_id']) {
				$itemqty = getPipeQty($P['pipe_id']);
				// $NOTES is set globally in getPipeQty()
				$notes = $NOTES[$P['pipe_id']];
				$pipeids_str = $P['pipe_id'];
			} else {
				foreach ($pipe_ids as $pipe_id => $arr) {
					$itemqty += getPipeQty($pipe_id);
					// $NOTES is set globally in getPipeQty()
					if (trim($NOTES[$pipe_id])) {
						if ($notes) { $notes .= chr(10).'<HR>'.chr(10); }
						$notes .= $NOTES[$pipe_id];
					}
					if ($pipeids_str) { $pipeids_str .= ','; }
					$pipeids_str .= $pipe_id;
				}
				$pipe_ids = array();
			}

			// if no notes through pipe, check new db
			if (! $notes) {
				$query2 = "SELECT * FROM prices WHERE partid = '".$partid."'; ";
				$result2 = qdb($query2);
				if (mysqli_num_rows($result2)>0) {
					$notes = true;
				}
			}

			//$itemqty = getQty($partid);
			$rowcls = '';
			if ($itemqty>0) { $rowcls = ' info'; }

			$itemprice = "0.00";
			$fav_flag = $favs[$partid];

			$partstrs = explode(' ',$P['part']);
			$primary_part = $partstrs[0];

			$chkd = '';
			if ($k==0 OR $itemqty>0) { $chkd = ' checked'; }

			$notes_icon = '';
			if ($notes) {
				if (isset($NOTIFICATIONS[$partid])) {
					$notes_icon = 'text-danger fa-warning fa-lg';
				} else {
					$notes_icon = 'fa-sticky-note text-warning';
				}
			} else {
				$notes_icon = 'fa-sticky-note-o';
			}
			$notes_flag = '<span class="item-notes"><i class="fa '.$notes_icon.'"></i></span>';

			$results_rows .= '
                        <!-- row -->
                        <tr class="product-results animated" id="row-'.$partid.'">
                            <td class="descr-row'.$rowcls.'">
								<div class="product-action text-center">
                                	<div class="action-box"><input type="checkbox" class="item-check" name="items['.$ln.']['.$k.']" value="'.$partid.'"'.$chkd.'></div>
                                    <a href="javascript:void(0);" data-partid="'.$partid.'" class="fa fa-'.$fav_flag.' fa-lg fav-icon" data-toggle="tooltip" data-placement="right" title="Add/Remove as a Favorite"></a>
								</div>
								<div class="qty">
									<div class="form-group">
										<input name="sellqty['.$ln.'][]" type="text" value="'.$itemqty.'" size="2" placeholder="Qty" class="input-xs form-control" />
									</div>
								</div>
                                <div class="product-img">
                                    <img src="/img/parts/'.format_part($primary_part).'.jpg" alt="pic" class="img" data-part="'.$primary_part.'" />
                                </div>
                                <div class="product-descr" data-partid="'.$partid.'" data-pipeids="'.$pipeids_str.'">
									<span class="descr-label"><span class="part-label">'.$P['Part'].'</span> &nbsp; <span class="heci-label">'.$P['HECI'].'</span> &nbsp; '.$notes_flag.'</span>
                                   	<div class="description descr-label"><span class="manfid-label">'.dictionary($P['manf']).'</span> <span class="systemid-label">'.dictionary($P['system']).'</span> <span class="description-label">'.dictionary($P['description']).'</span></div>

									<div class="descr-edit hidden">
										<p>
		        							<button type="button" class="close parts-edit"><span>&times;</span></button>
											<input type="text" value="'.$P['Part'].'" class="form-control" data-partid="'.$partid.'" data-field="part" placeholder="Part Number">
										</p>
										<p>
											<input type="text" value="'.$P['HECI'].'" class="form-control" data-partid="'.$partid.'" data-field="heci" placeholder="HECI/CLEI">
										</p>
										<p>
											<input type="text" name="descr[]" value="'.$P['description'].'" class="form-control" data-partid="'.$partid.'" data-field="description" placeholder="Description">
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
												<button class="btn btn-default input-xs control-toggle" type="button" tabindex="-1" data-toggle="tooltip" data-placement="left" title="group/ungroup prices for item"><i class="fa fa-lock"></i></button>
											</span>
											<input type="text" name="sellprice['.$ln.'][]" value="'.$itemprice.'" size="6" placeholder="0.00" class="input-xs form-control price-control sell-price" />
										</div>
									</div>
								</div>
                            </td>
			';

			// if on the first result, build out the market column that runs down all rows of results
			if ($k==0) {
				$sales_col = format_market($partid_str,'sales',$search_strs);
				$demand_col = format_market($partid_str,'demand',$search_strs);
				$purchases_col = format_market($partid_str,'purchases',$search_strs);

				// reset after getting col data in format_market() above, which  may alter this date for item-specific results
				$summary_past = format_date($today,'Y-m-01',array('m'=>-1));

				$results_rows .= '
							<!-- market-row for all items within search result section -->
                            <td rowspan="'.($num_results+1).'" class="market-row">
								<table class="table market-table" data-partids="'.$partids.'">
									<tr>
										<td class="col-sm-3 bg-availability">
											<a href="javascript:void(0);" class="market-title modal-results" data-target="marketModal">Supply <i class="fa fa-window-restore"></i></a> <a href="javascript:void(0);" class="market-download" data-toggle="tooltip" data-placement="top" title="force re-download"><i class="fa fa-download"></i></a>
											<div class="market-results" id="'.$ln.'-'.$partid.'" data-ln="'.$ln.'"></div>
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
<!--
                            <td class="product-actions text-right">
								<div class="price">
									<div class="form-group">
										<div class="input-group buy">
											<span class="input-group-btn">
												<button class="btn btn-default input-xs control-toggle" type="button"><i class="fa fa-lock"></i></button>
											</span>
											<input name="buyprice['.$ln.'][]" type="text" value="0.00" size="6" placeholder="Buy" class="input-xs form-control price-control" />
										</div>
									</div>
								</div>
                            </td>
                        </tr>
-->
			';
		}
?>

                    <tbody>
                        <!-- row -->
                        <tr class="first">
                            <td>
								<div class="product-action action-hover text-left">
									<div>
										<input type="checkbox" class="checkAll" checked><br/>
									</div>
									<div class="action-items">
							           	<a href="javascript:void(0);" class="parts-edit" title="edit selected part(s)"><i class="fa fa-pencil"></i></a><br/>
							           	<a href="javascript:void(0);" class="parts-merge" title="merge two selected part(s) into one"><i class="fa fa-chain"></i></a><br/>
<?php if ($num_results==0) { // add link to create a new part ?>
										<a href="javascript:void(0);" class="add-part" title="add to parts db"><i class="fa fa-plus"></i></a>
<?php } else { ?>
										<a href="javascript:void(0);" class="parts-index" title="re-index db (reloads page)"><i class="fa fa-cog"></i></a>
<?php } ?>
									</div>
								</div>
								<div class="qty">
									<input type="text" name="search_qtys[<?php echo $ln; ?>]" value="<?php echo $search_qty; ?>" class="form-control input-xs search-qty input-primary" data-toggle="tooltip" data-placement="top" title="customer request qty or supplier available qty" /><br/>
								</div>
								<div class="product-descr action-hover">
				                	<div class="input-group">
										<input type="text" name="searches[<?php echo $ln; ?>]" value="<?php echo $search_str; ?>" class="product-search text-primary" tabindex="-1" />
<!--
	           		       				<span class="input-group-addon action-items">
										</span>
-->
									</div><!-- /input-group -->
									<span class="info"><?php echo $num_results.' result'.$s; ?></span> &nbsp; <span class="text-danger"><?php echo $explanation; ?></span>
								</div>
								<div class="price pull-right">
									<div class="form-group target text-right">
										<input name="list_price[<?php echo $ln; ?>]" type="text" value="<?php echo $search_price; ?>" size="6" placeholder="0.00" class="input-xs form-control price-control input-primary" data-toggle="tooltip" data-placement="top" title="customer target price or vendor asking price" />
									</div>
								</div>
							</td>
                            <td class="action-hover slider-box">
<!--
								<div class="toggle-results">
								<a href="javascript:void(0);" title="toggle selection of the results in this row">
									<i class="fa fa-toggle-on fa-lg animated"></i>
								</a>
								</div>
-->
									<!-- color-coding the slider backwards because toggled right looks more 'on' in this case than 'off' -->
									<div class="slider-frame default" data-onclass="default" data-offclass="primary">
										<!-- include radio's inside slider-frame to set appropriate actions to them -->
										<input type="radio" name="line_number[<?php echo $ln; ?>]" class="row-status line-number hidden" value="Ln <?php echo ($ln+1); ?>">
										<input type="radio" name="line_number[<?php echo $ln; ?>]" class="row-status line-number hidden" value="Off">
										<span data-on-text="Ln <?php echo ($ln+1); ?>" data-off-text="Off" class="slider-button" data-toggle="tooltip" data-placement="top" title="enable/disable results for this row">Ln <?php echo ($ln+1); ?></span>
									</div>
								<div class="row">
									<div class="col-sm-3 text-center">
<!--
										<span id="marketpricing-<?php echo $ln; ?>"></span> <a href="javascript:void(0);" class="marketpricing-toggle hidden"><i class="fa fa-toggle-off"></i></a>
-->
										<div id="marketpricing-<?php echo $ln; ?>" class="header-text">&nbsp;</div>
										<div class="btn-group btn-resultsmode action-items">
											<button class="btn btn-primary btn-xs" type="button" data-results="0" data-toggle="tooltip" data-placement="top" title="all market results"><i class="fa fa-globe"></i></button>
											<button class="btn btn-default btn-xs" type="button" data-results="1" data-toggle="tooltip" data-placement="top" title="priced results"><i class="fa fa-dollar"></i></button>
											<button class="btn btn-default btn-xs" type="button" data-results="2" data-toggle="tooltip" data-placement="top" title="ghosted inventories"><i class="fa fa-magic"></i></button>
										</div><!-- <br/>
										<span class="info">market pricing</span> -->
									</div>
									<div class="col-sm-3 text-center"><span class="header-text"><?php echo format_price($avg_cost); ?></span><br/><span class="info">avg cost</span></div>
									<div class="col-sm-3 text-center"><span class="header-text"><?php echo $shelflife; ?></span><br/><span class="info">shelflife</span></div>
									<div class="col-sm-3 text-center"><span class="header-text"></span><br/><span class="info">quotes-to-sale</span></div>
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
<!--
                            <td> </td>
-->
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

<?php include_once 'inc/footer.php'; ?>
<?php include_once 'modal/results.php'; ?>
<?php include_once 'modal/notes.php'; ?>
<?php include_once 'modal/remotes.php'; ?>
<?php include_once 'modal/image.php'; ?>
<?php include_once 'inc/jquery-fileupload.php'; ?>

</body>
</html>
