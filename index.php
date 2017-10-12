<?php
	include_once 'inc/dbconnect.php';
	include_once 'inc/pipe.php';
	include_once 'inc/format_date.php';
	include_once 'inc/format_price.php';
	include_once 'inc/getPipeIds.php';
	include_once 'inc/getPipeQty.php';
	include_once 'inc/getRecords.php';
	include_once 'inc/getShelflife.php';
	include_once 'inc/getCost.php';
	include_once 'inc/getQty.php';
	include_once 'inc/array_stristr.php';
	include_once 'inc/calcQuarters.php';
	include_once 'inc/format_market.php';

	if ($SEARCH_MODE<>'/' AND $SEARCH_MODE<>'index.php' AND ! $_REQUEST) {
		header('Location: '.$SEARCH_MODE);
		exit;
	}



	$listid = 0;
	if (isset($_REQUEST['listid']) AND is_numeric($_REQUEST['listid']) AND $_REQUEST['listid']>0) { $listid = $_REQUEST['listid']; }
	else if (isset($_REQUEST['upload_listid']) AND is_numeric($_REQUEST['upload_listid']) AND $_REQUEST['upload_listid']>0) { $listid = $_REQUEST['upload_listid']; }
	$pg = 1;
	//if (isset($_REQUEST['pg']) AND is_numeric($_REQUEST['pg']) AND $_REQUEST['pg']>0) { $pg = $_REQUEST['pg']; }

	$yesterday = format_date(date("Y-m-d"),'Y-m-d',array('d'=>-1));
	$lastWeek = format_date(date("Y-m-d"),'Y-m-d',array('d'=>-7));
	$lastYear = format_date(date("Y-m-d"),'Y-m-01',array('m'=>-11));

	// function getQtyPatch($partid=0) { // Creating a temporary fix to no-stock and never-stock to prevent getQty usage from breaking on other pages
	// 	global $QTYS;

	// 	$qty = 0;

	// 	if (! $partid OR ! is_numeric($partid)) { return ($qty); }

	// 	$QTYS[$partid] = 0;
	// 	$query = "SELECT SUM(qty) qty FROM inventory WHERE partid = '".$partid."';";
	// 	//$query .= "AND conditionid >= 0 AND (status = 'shelved' OR status = 'received'); ";//status <> 'scrapped' AND status <> 'in repair'; ";
	// 	$result = qdb($query) OR die(qe().' '.$query);
	// 	if (mysqli_num_rows($result)==0) { return ('null'); }
	// 	$r = mysqli_fetch_assoc($result);
	// 	$qty = $r['qty'];

	// 	return ($qty);
	// }

	function filterResults($search_strs='',$partid_csv='') {//,$sales_count,$sales_min,$sales_max,$demand_min,$demand_max,$start_date,$end_date) {
		global $record_start,$record_end,$today,$SALES,$DEMAND,$sales_count,$sales_min,$sales_max,$demand_min,$demand_max,$start_date,$end_date;

		if (! $search_strs) { $search_strs = array(); }

		// set start and end dates for getRecords() calls below, then reset at the end of this section
		if (! $start_date) { $start_date = '0000-00-00'; }
		if (! $end_date) { $end_date = $today; }
		$record_start = $start_date;
		$record_end = $end_date;

//echo '<BR><BR>';
		$results = array();
		if ($sales_count!==false OR $sales_min!==false OR $sales_max!==false) {
			$sales = getRecords($search_strs,$partid_csv,'csv','sales');

			// exclude results not meeting minimum sales count, if set
			if ($sales_count!==false AND count($sales)<$sales_count) { return ($results); }

			// if sales-related filters are set, get sales here and set within a global scope so we can re-use data within getRecords()
			if ((count($search_strs)>0 OR $partid_csv) AND $start_date=='' AND $end_date==$today) {
				$SALES = $sales;
			}

			// build new array with specific fields to fit the form of sales within the broader array, and to match partid keys
			foreach ($sales as $r) {
// just for now
if (! $r['partid']) { return ($results); }
				$fprice = format_price($r['price'],false,'',true);//reformat in true float format, for comparison

				// exclude results outside of pricing parameters
				if (($sales_min!==false AND $fprice<$sales_min) OR ($sales_max!==false AND $fprice>$sales_max)) { return ($results); }

				if (! isset($results[$r['partid']])) {
					$r['sales'] = array();
					$r['demand'] = array();
					$results[$r['partid']] = $r;
				}
				// add every sale to array
				$results[$r['partid']]['sales'][] = $fprice;
			}
		}

		// if demand-related filters are set, get data from getRecords() as above with sales
		if ($demand_min!==false OR $demand_max!==false) {
			$demand = getRecords($search_strs,$partid_csv,'csv','demand');

			// exclude results not meeting minimum demand count or exceeding max count, if set
			if (($demand_min!==false AND count($demand)<$demand_min) OR ($demand_max!==false AND count($demand)>$demand_max)) { return ($results); }

			// if sales-related filters are set, get sales here and set within a global scope so we can re-use data within getRecords()
			if ((count($search_strs)>0 OR $partid_csv) AND $start_date=='' AND $end_date==$today) {
				$DEMAND = $demand;
			}

			// build new array with specific fields to fit the form of demand within the broader array, and to match partid keys
			foreach ($demand as $r) {
// just for now
if (! $r['partid']) { return ($results); }

				if (! isset($results[$r['partid']])) {
					$r['sales'] = array();
					$r['demand'] = array();
					$results[$r['partid']] = $r;
				}
				// add uniquely-dated demand
				$results[$r['partid']]['demand'][$r['datetime']] = format_price($r['price'],false,'',true);//reformat in true float format, for comparison
			}
		}

		$record_start = '';
		$record_end = '';

		return ($results);
	}

?>
<!DOCTYPE html>
<html>
<head>
	<title>Sales</title>
	<?php
		include_once 'inc/scripts.php';
	?>
	<style type="text/css">
		.product-img .img {
		    height: 31px !important;
		    /*float: left;*/
		    background: white;
		    width: 31px !important;
		    border: 1px solid #dfe4eb !important;
		    text-align: center !important;
		    cursor: pointer;
		}

		.last_week .date-group {
			font-weight: bold !important;
		}

		.table-header .form-group {
			margin-bottom: 0;
		}

		.total_price_text, .seller_qty, .seller_price, .seller_times {
			font-size: 12px;
			color: #000;
			border: 1px solid #357ebd;
			padding: 2px 7px;
			border-radius: 3px;
		}

		.buy_text {
			font-weight: bold;
			font-size: 12px;
			color: #428bca;
			padding: 2px 7px;
		}

		.bid-input {
			color: #428bca;
			font-weight: bold;
		}

		#pad-wrapper {
			padding: 0 25px;
			margin-top: 110px;
		}

		#pad-wrapper .part_info {
			margin-bottom: 5px;
		}

		.list_total {
			font-weight: bold;
			font-size: 15px;
			color: #428bca;
		}

		.market-table {
			margin-bottom: 10px;
		}

		.descr-row {
			height: auto !important;
		}

		.market-company {
			display: inline-block;
		}

		.filter-group {
		    position: relative;
		    display: inline-block;
		    vertical-align: middle;
		}

		.first .part-modal-show {
			visibility: visible !important;
		}

		.product-descr .part-modal-show {
			visibility: hidden;
		}

		.descr-row:hover .part-modal-show {
			visibility: visible;
		}

		.part_info .bg-availability, .part_info .bg-purchases, .part_info .bg-sales, .part_info .bg-demand, .part_info .bg-repair {
			padding: 5px;
			min-height: 140px;
			height: 100%;
		}

		.form-group .input-group.sell {
			width: auto;
			max-width: 90px;
		}

		.table-header .select2 {
			width: 80% !important;
		}

		.spinner_lock {
			margin: 0 auto;
			width: 12px;
			padding-bottom: 25px;
		}

		.descr-row {
			margin-left: 0;
		}

		/*Temporarily kill the product first row action options (Merge etc)*/
		.product-action .action-items {
			display: none !important;
		}

		.bg-demand .market-download {
			display: none;
		}

		.slider-box .slider-frame {/*.toggle-results {*/
			z-index:1000;
			position:relative;
			top:2px;
			right:5px;
			height:16px;
			width:60px;
		}

		.bg-demand .fa-circle-o {
			visibility: hidden;
		}

		@media (max-width: 992px) {
			#pad-wrapper {
				margin-top: 130px;
			}
		}

		@media (max-width: 767px) {
			.table-header .select2 {
				width: 100% !important;
			}
		}
	</style>
</head>
<body>

	<?php include_once 'inc/keywords.php'; ?>
	<?php include_once 'inc/dictionary.php'; ?>
	<?php include_once 'inc/logSearch.php'; ?>
	<?php include_once 'inc/format_price.php'; ?>
	<?php include_once 'inc/getQty.php'; ?>

	<?php include_once 'inc/navbar.php'; ?>

	<div id="sales_loader" class="loader text-muted">
		<div>
			<i class="fa fa-refresh fa-5x fa-spin"></i><br>
			<h1 id="loader-message">Please wait for Sales results to load...</h1>
		</div>
	</div>

<?php
	/* FILTER CONTROLS */
	$start_date = '';
	if ($startDate) { $start_date = format_date($startDate,'Y-m-d'); }
	$end_date = '';
	if ($endDate) { $end_date = format_date($endDate,'Y-m-d'); }

	// check for any filters to be set
	$filtersOn = false;
	if ($sales_count!==false OR $sales_min!==false OR $sales_max!==false OR $stock_min!==false OR $stock_max!==false OR $demand_min!==false OR $demand_max!==false OR $start_date<>'' OR $end_date<>$today) {
		$filtersOn = true;
	}

	// the user is db-mining: no search string passed in, but filters are on to find certain subsets of parts
	if (! $s AND ! $listid AND $filtersOn AND $start_date<>'') {
		// get filtered results and then build linebreak-separated search string
		$results = filterResults();

		// consolidate into a string-keyed array so, with one simple section of code, we can generate a simplified array for building our list of line strings
		$groups = array();
		foreach ($results as $pid => $r) {
			// exclude results not meeting minimum sales count, if set
			if ($sales_count!==false AND count($r['sales'])<$sales_count) { continue; }

			// exclude results not meeting minimum demand count, if set
			if (($demand_min!==false AND count($r['demand'])<$demand_min) OR ($demand_max!==false AND count($r['demand'])>$demand_max)) { continue; }

			$keystr = '';
			// verify data is sound from pipe
			if ($r['clei'] AND ! is_numeric($r['clei']) AND preg_match('/^[[:alnum:]]{10}$/',$r['clei'])) { $keystr = substr($r['clei'],0,7); }
			else if ($r['heci'] AND ! is_numeric($r['heci']) AND preg_match('/^[[:alnum:]]{10}$/',$r['heci'])) { $keystr = substr($r['heci'],0,7); }
			else { $keystr = format_part($r['part_number']); }

			$groups[$keystr] = true;
		}
		// release memory
		unset($results);

//print "<pre>".print_r($groups,true)."</pre>";
//echo str_replace(chr(10),'<BR>',$s);
//exit;

		// iterate through groups and add only those items that match filters
		foreach ($groups as $keystr => $r) {
			$s .= $keystr.chr(10);
		}
		// release memory
		unset($groups);
	}

	if (! $s AND ! $listid AND ! $favorites) {
?>
    <div id="pad-wrapper">

    <table class="table margin-120">
		<tr>
			<td class="col-md-12 text-center">
				Enter your search above, or tap <i class="fa fa-list-ol"></i> for advanced search options...
			</td>
		</tr>
	</table>
<?php
	} else {

	$title = '';

//	if (! $s) { $s = 'UN375F'.chr(10).'090-42140-13'.chr(10).'IXCON'; }

	$lines = array();
	if ($listid) {
		$search_index = 0;
		$qty_index = 1;
		$query = "SELECT search_meta.id metaid, uploads.type, processed, filename FROM search_meta, uploads ";
		$query .= "WHERE uploads.id = '".res($listid)."' AND uploads.metaid = search_meta.id; ";
		$result = qdb($query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$title = $r['filename'];

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
		// if user is just pulling favorites list
		if (! $s AND $favorites) {
			// build results using heci7 as key, or part# if no heci
			$results = array();
			$query = "SELECT part, LEFT(heci,7) heci7 FROM parts, favorites ";
			$query .= "WHERE parts.id = favorites.partid ";
			$query .= "ORDER BY part, rel, heci ";
			$result = qdb($query) OR die(qe().' '.$query);
			while ($r = mysqli_fetch_assoc($result)) {
				if ($r['heci7']) {
					$key = $r['heci7'];
				} else {
					$partstrs = explode(' ',$r['part']);
					$key = format_part($partstrs[0]);
				}
				$results[$key] = $r;
			}

			// using keyed results, build $lines for an auto-indexed array
			$lines = array();
			foreach ($results as $key => $r) {
				$lines[] = $key;
			}
		} else {
			$lines = explode(chr(10),$s);
		}
	}
?>
	<form class="form-inline results-form" method="post" action="save-results.php" enctype="multipart/form-data" >


	<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px;">
		<div class="row" style="padding: 8px;" id="filterBar">
			<div class="col-md-4 mobile-hide" style="max-height: 30px;">
				<div class="col-md-3" style="margin-top: -5px;">
					<!-- <div class="col-md-8 remove-pad" style="margin-top: -5px;"> -->
						<div class="btn-group" data-toggle="buttons">
					        <button data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Line" class="btn btn-xs left filter_status active btn-warning" data-sort="line" data-type="sort" data-listid="<?=$listid;?>" <?=($listid == 0 ? 'disabled' : '')?>>
					        	<i class="fa fa-sort-amount-asc" aria-hidden="true"></i>
					        </button>
					        <button data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Stock" class="btn btn-xs middle filter_status btn-default" data-sort="stock" data-type="sort" data-listid="<?=$listid;?>" <?=($listid == 0 ? 'disabled' : '')?>>
					        	<i class="fa fa-dropbox" aria-hidden="true"></i>	
					        </button>
					    </div>
					    <div class="filter-group">
							<button class="btn btn-xs btn-primary active filter_equipment" type="button" data-type="equipment" data-toggle="tooltip" data-placement="right" title="" data-original-title="Equipments"><i class="fa fa-cube"></i></button><br/>
							<button class="btn btn-xs btn-default filter_equipment" type="button" data-type="component" data-toggle="tooltip" data-placement="right" title="" data-original-title="Components"><i class="fa fa-microchip" aria-hidden="true"></i></button>
						</div>
					<!-- </div> -->
				</div>

				<div class="col-md-9 date_container mobile-hid remove-pad">
					<div class="col-sm-5 remove-pad">
						<div class="input-group date datetime-picker" data-format="MM/DD/YYYY">
				            <input type="text" name="START_DATE" class="form-control input-sm" value="<?php echo $startDate; ?>">
				            <span class="input-group-addon">
				                <span class="fa fa-calendar"></span>
				            </span>
				        </div>
					</div>
					<div class="col-sm-5 remove-pad">
						<div class="input-group date datetime-picker" data-format="MM/DD/YYYY" data-maxdate="<?php echo date("m/d/Y"); ?>">
				            <input type="text" name="END_DATE" class="form-control input-sm" value="<?php echo $endDate; ?>">
				            <span class="input-group-addon">
				                <span class="fa fa-calendar"></span>
				            </span>
					    </div>
					</div>
					<div class="col-sm-2 remove-pad">
							<button class="btn btn-primary btn-sm date_filter" style="padding: 3px 8px; margin-top: 2px;"><i class="fa fa-filter" aria-hidden="true"></i></button>
							<div class="btn-group" id="dateRanges">
								<div id="btn-range-options">
									<button class="btn btn-default btn-sm" style="padding: 3px 8px; margin-top: 2px;">&gt;</button>
									<div class="animated fadeIn hidden" id="date-ranges">
								        <button class="btn btn-sm btn-default left large btn-report" type="button" data-start="<?php echo date("m/01/Y"); ?>" data-end="<?php echo date("m/d/Y"); ?>">MTD</button>
										<?php
											$quarters = calcQuarters();
											foreach ($quarters as $qnum => $q) {
												echo '
									    			<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="'.$q['start'].'" data-end="'.$q['end'].'">Q'.$qnum.'</button>
												';
											}

											for ($m=1; $m<=5; $m++) {
												$month = format_date($today,'M m/t/Y',array('m'=>-$m));
												$mfields = explode(' ',$month);
												$month_name = $mfields[0];
												$mcomps = explode('/',$mfields[1]);
												$MM = $mcomps[0];
												$DD = $mcomps[1];
												$YYYY = $mcomps[2];
												echo '
													<button class="btn btn-sm btn-default right small btn-report" type="button" data-start="'.date($MM."/01/".$YYYY).'" data-end="'.date($MM."/".$DD."/".$YYYY).'">'.$month_name.'</button>
												';
											}
										?>
									</div><!-- animated fadeIn -->
								</div><!-- btn-range-options -->
							</div><!-- btn-group -->
					</div><!-- form-group -->
				</div>
			</div>

			<div class="text-center col-md-4 remove-pad">
				<h2 class="minimal"><?=($title ? $title : '');?></h2>
			</div>

			<div class="col-md-4" style="padding-left: 0; padding-right: 10px;">
				<div class="col-md-3 col-sm-3 phone-hide" style="padding: 5px;">
					<span style="font-size: 15px; font-weight: bold;">Total:</span> <span class="list_total">$0.00</span>
				</div>
				<div class="col-md-2 col-sm-2 col-xs-3">
					<div class="pull-right slider-frame success filter_modes">
						<!-- include radio's inside slider-frame to set appropriate actions to them -->
						<input type="radio" class="sales_mode hidden" value="Buy">
						<input type="radio" class="sales_mode hidden" value="Sell">
						<span data-on-text="Buy" data-off-text="Sell" class="slider-button upload-slider" id="upload-slider">Sell</span>
					</div>
				</div>

				<div class="col-md-7 col-sm-7 col-xs-9 remove-pad">
					<div class="col-sm-10 col-xs-8">
						<div class="pull-right form-group" style="width: 100%;">
							<select name="companyid" id="companyid" class="company-selector">
								<option value="">- Select a Company -</option>
							</select>
							<button class="btn btn-primary btn-sm phone-hide company-filter">
								<i class="fa fa-filter" aria-hidden="true"></i>
							</button>
						</div>
					</div>

					<div class="col-sm-2 col-xs-4 remove-pad">
						<button class="btn btn-success btn-sm pull-right" id="save_sales" type='submit' data-error='' style="padding: 5px 25px;">
							SAVE					
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<?php
		$searchlistid = 0;
		if ($s) { $searchlistid = logSearch($s,$search_field,$search_from_right,$qty_field,$qty_from_right,$price_field,$price_from_right); }
	?>
	<input type="hidden" name="searchlistid" value="<?php echo $searchlistid; ?>">
	<input type="hidden" name="submit_type" value="demand">

    <div id="pad-wrapper">

    	<div id="remote-warnings">
			<?php 
				$query = "SELECT * FROM remotes ORDER BY id ASC; ";
				$result = qdb($query);
				while ($r = mysqli_fetch_assoc($result)) {
					echo '<a style="margin-bottom: 15px;" class="btn btn-danger btn-xs hidden btn-remote" id="remote-'.$r['remote'].'" data-name="'.$r['name'].'"><img src="/img/'.$r['remote'].'.png" /></a>';
				}
			?>
		</div>

<?php
	// set alert message if favorites and/or filters
	$alert_msg = '';
	if ($favorites) {
		$alert_msg = 'limited to Favorites';
	}
	if ($filtersOn) {
		if ($favorites) { $alert_msg .= ', and may be limited due to other filters'; }
		else { $alert_msg = 'may be limited due to filters'; }
		$alert_icon = 'warning';
	} else {
		$alert_msg .= ' only';
		$alert_icon = 'star';
	}

	if ($favorites or $filtersOn) {
		echo '
	<div class="alert alert-warning">
		<i class="fa fa-'.$alert_icon.' fa-2x"></i>
		Results '.$alert_msg.'
	</div>
		';
	}
?>

<?php
	$rows = array();

	foreach ($lines as $n => $line) {
		$line = trim($line);
		if (! $line) { continue; }

		$rows[] = $line;
	}
	unset($lines);

	$per_pg;

	if(! $listid) {
		$per_pg = 50;
	} else {
		$per_pg = 10;
	}

	$min_ln = ($pg*$per_pg)-$per_pg;
	$max_ln = ($min_ln+$per_pg)-1;
	$num_rows = count($rows);

	$x = 0;//line number index for tracking under normal circumstances, but also for favorites-only views
	$rev_total = 0.00;
	foreach ($rows as $ln => $line) {
		$terms = preg_split('/[[:space:]]+/',$line);
		$search_str = strtoupper(trim($terms[$search_index]));
		if (! $search_str) {
			$num_rows--;//mostly impacting pagination
			continue;
		}

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

		//Default show only equipment
		$results = array_filter($results, function($row) { global $equipment_filter; return ($row['classification'] == 'equipment'); });

		// the LARGE majority of items don't have more than 25 results within a certain group of 7-digit hecis
		if (count($results)>25) {
			$explanation = '<i class="fa fa-warning fa-lg"></i> '.count($results).' results found, limited to first 25!';
			// take the top 25 results
			$results = array_slice($results,0,25,true);
		}

		// gather all partid's first
		$partid_csv = "";//comma-separated for data-partids tag and avg_cost
		$search_strs = array();

		// if favorites is NOT set, we're counting rows based on global results and don't need to waste pre-processing in first loop below
		if (! $favorites AND ! $filtersOn) {
			if ($x<$min_ln) { $x++; continue; }
			else if ($x>$max_ln) { 
				echo '<div class="row infinite_scroll spinner_lock" data-page="'.(isset($ln) ? $ln + 1 : '1').'" data-list="'.$listid.'"><i style="display: block; text-align: center;" class="fa fa-circle-o-notch fa-spin"></i></div>';
				break; 
			}
			$x++;
		}

		$favs = array();
		$num_favs = 0;

		$lineqty = 0;
		$pipe_ids = array();
		// stores exact 10-digit heci matched pipe id with partid
		$pipe_id_assoc = array();

		$num_results = count($results);
		// pre-process results so that we can build a partid string for this group as well as to group results
		// if the user is showing just favorites; lastly, also get all pipe ids for qty gathering
		foreach ($results as $partid => $P) {
			$exploded_strs = explode(' ',$P['part']);
			$search_strs = array_merge($search_strs,$exploded_strs);
			if ($P['heci']) {
				$search_strs[] = substr($P['heci'],0,7);
			}

			if ($partid_csv) { $partid_csv .= ","; }
			$partid_csv .= $partid;

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

		// if by now no favorites have been found, and user is asking for favorites, skip line
		if ($favorites AND $num_favs==0) {
			$num_rows--;//mostly impacting pagination
			continue;
		}

		//reset as a boolean because in format_market(), it helps us know if we've previously searched sales/demand; values set to it in filterResults()
		$SALES = false;
		$DEMAND = false;

		// allow only real items with results (i.e., exclude invalid searches)
		if ($sales_count!==false OR $sales_min!==false OR $sales_max!==false OR $demand_min!==false OR $demand_max!==false) {
			if (count($search_strs)==0 AND ! $partid_csv) {
				$num_rows--;//mostly impacting pagination
				continue;
			}

			$subResults = filterResults($search_strs,$partid_csv);
			if (count($subResults)==0) {
				$num_rows--;//mostly impacting pagination
				continue;
			}
		}

		$avg_cost = getCost($partid_csv);

		// now pre-process to get grouped qtys, but it needs to be a separate foreach loop since we're now gathering
		// qtys based on sum of ids above, and we're using those qtys to determine for filters, if present
		foreach ($results as $partid => $P) {
			$itemqty = 0;
			$results[$partid]['notes'] = '';

			// change to this after migration, remove ~7-10 lines above
			if(getQty($partid) !== false) {
				$itemqty = getQty($partid);
				$lineqty += $itemqty;
			} else {
				$itemqty = '';
			}

			$results[$partid]['qty'] = $itemqty;
		}

		// reject lines that don't have qtys that meet filter parameters, if set
		if (($stock_min!==false AND $lineqty<$stock_min) OR ($stock_max!==false AND $lineqty>$stock_max)) {
			$num_rows--;//mostly impacting pagination
			continue;
		}

		// if favorites is set, we're counting rows based on favorites results; we do NOT WANT TO MOVE THIS code until after
		// above filters, even though it costs us extra processing, because numbered results may be impacted by filters
		if ($favorites OR $filtersOn) {
			if ($x<$min_ln) { $x++; continue; }
			else if ($x>$max_ln) { 
				echo '<div class="row infinite_scroll spinner_lock" data-page="'.(isset($ln) ? $ln + 1 : '1').'" data-list="'.$listid.'"><i style="display: block; text-align: center;" class="fa fa-circle-o-notch fa-spin"></i></div>';
				break; 
			}
			$x++;
		}

		$id_array = "";//pass in comma-separated values for getShelflife()
		$shelflife = getShelflife($id_array);

		$s = '';
		if ($num_results<>1) { $s = 's'; }

		$results_rows = '';
		$k = 0;

		$results_rows .= '
                        <!-- row -->
                        <div class="product-results animated row">
                        	<div class="col-md-4 remove-pad parts-container">';

        $last_element = count($results) - 1;

        //If in any case the list item's part has no revisions
		if(empty($results)) {
			//kill parts-container
			$results_rows .= '	</div>';
		}

		foreach ($results as $partid => $P) {
			$itemqty = $P['qty'];
			$notes = $P['notes'];
			$pipeids_str = '';//$P['pipeids_str'];

			// if no notes through pipe, check new db (this is just for the notes flag)
			if (! $notes) {
				$query2 = "SELECT * FROM messages, prices WHERE ref_1 = '".$partid."' AND ref_1_label = 'partid' AND messages.id = prices.messageid; ";
				$result2 = qdb($query2);
				if (mysqli_num_rows($result2)>0) {
					$notes = true;
				}
			}

			$rowcls = '';
			if ($itemqty>0 && $itemqty != 'null') { $rowcls = ' info'; }

			$itemprice = "0.00";
			$fav_flag = $favs[$partid];

			$partstrs = explode(' ',$P['part']);
			$primary_part = $partstrs[0];

			$chkd = '';
			if ($k==0 OR ($itemqty>0 && $itemqty != 'null')) { $chkd = ' checked'; }

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
					<div class="product-results" id="row-'.$partid.'">
                        <div class="row descr-row '.($itemqty > 0 ? 'in-stock' : ($itemqty == null ? 'never-stock' : 'out-stock')).'" style="padding:8px">
                        	<div class="col-sm-3 remove-pad">
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
	                        </div>

	                        <div class="col-sm-7">
	                            <div class="product-descr" data-partid="'.$partid.'" data-pipeids="'.$pipeids_str.'">
									<span class="descr-label"><span class="part-label">'.$P['Part'].'</span> &nbsp; <span class="heci-label">'.$P['HECI'].'</span> &nbsp; '.$notes_flag.'</span><a style="margin-left: 5px;" href="javascript:void(0);" class="part-modal-show" data-partid="'.$partid.'" data-ln="'.($ln+1).'" style="cursor:pointer;"><i class="fa fa-pencil"></i></a>
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
							</div>

							<div class="col-sm-2 ">
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
							</div>
                        </div>
                    </div>
			';

			//Sum up all the revision cost per line number
			$rev_total += $itemprice * $itemqty;

			// if on the first result, build out the market column that runs down all rows of results
			if ($k==$last_element) {
				$sales_col = format_market($partid_csv,'sales',$search_strs, $start_date, $end_date);
				$demand_col = format_market($partid_csv,'demand',$search_strs, $start_date, $end_date);
				$purchases_col = format_market($partid_csv,'purchases',$search_strs, $start_date, $end_date);
				$repair_col = format_market($partid_csv,'repairs',$search_strs, $start_date, $end_date);

				// reset after getting col data in format_market() above, which  may alter this date for item-specific results
				$summary_past = format_date($today,'Y-m-01',array('m'=>-1));

				if($explanation != '') {
					// $results_rows .= '<div class="row infinite_scroll" data-page="'.($page ? $page + 1 : '1').'"><i style="display: block; text-align: center;" class="fa fa-circle-o-notch fa-spin"></i></div>';
				}

				$res1_btn = 'primary';
				$res2_btn = 'default';
				if (isset($_REQUEST['pricing_mode'])) { $res1_btn = 'default'; $res2_btn = 'primary'; }

				$results_rows .= '
							</div>

							<!-- market-row for all items within search result section -->
                            <div class="market-row col-md-8 remove-pad" data-ln="'.$ln.'">
								<div class="table market-table" data-partids="'.$partid_csv.'" style="min-height: 140px;">		
									<div class="col-sm-2 bg-availability">
										<a href="javascript:void(0);" class="market-title modal-results" data-target="marketModal" data-title="Supply Results" data-type="supply">Supply <i class="fa fa-window-restore"></i>
										</a> 
										<a href="javascript:void(0);" class="market-download" data-toggle="tooltip" data-placement="top" title="force re-download"><i class="fa fa-download"></i>
										</a>
										<div class="market-results" id="'.$ln.'-'.$partid.'" data-ln="'.$ln.'" data-type="supply"></div>
										<div class="btn-group btn-resultsmode action-items pull-right">
											<button class="btn btn-'.$res1_btn.' btn-xs" type="button" data-results="0" data-toggle="tooltip" data-placement="top" title="all market results"><i class="fa fa-globe"></i></button>
											<button class="btn btn-'.$res2_btn.' btn-xs" type="button" data-results="1" data-toggle="tooltip" data-placement="top" title="priced results"><i class="fa fa-dollar"></i></button>
											<button class="btn btn-default btn-xs" type="button" data-results="2" data-toggle="tooltip" data-placement="top" title="ghosted inventories"><i class="fa fa-magic"></i></button>
										</div>
									</div>
									<div class="col-sm-2 bg-purchases">
										'.$purchases_col.'
									</div>
									<div class="col-sm-2 bg-sales">
										'.$sales_col.'
									</div>
									<div class="col-sm-2 bg-demand">
										<a href="javascript:void(0);" class="market-title modal-results" data-target="marketModal" data-title="Demand Results" data-type="demand">Demand <i class="fa fa-window-restore"></i></a> <a href="javascript:void(0);" class="market-download" data-toggle="tooltip" data-placement="top" title="force re-download"><i class="fa fa-download"></i></a>
										<div class="market-results" id="'.$ln.'-'.$partid.'" data-ln="'.$ln.'" data-type="demand"></div>
									</div>
									<div class="col-sm-2 bg-repair">
										'.$repair_col.'
									</div>
									<div class="col-sm-2 slider-box">
										<br>
										<div class="row price">
											<div class="col-sm-3">
												<span class="seller_qty" style="display: none;"></span>
											</div>
											<div class="col-sm-1 remove-pad"><span class="seller_x" style="display: none;">x</span></div>
											<div class="col-sm-4 remove-pad">
												<span class="seller_price" style="display: none;"></span>
											</div>
											<div class="col-sm-4 remove-pad">
												<span class="total_price_text" data-total="'.$rev_total.'">'.format_price($rev_total).'</span>
											</div>
										</div>
										<br/>

										<div class="row bid_inputs" style="display: none;">
											<div class="col-sm-3 remove-pad">
												<input type="text" name="bid_qty['.$ln.']" data-type="bid_qty" class="form-control input-xxs bid-input text-center" value="" placeholder="Qty">
											</div>
											<div class="col-sm-1 remove-pad">x</div>
											<div class="col-sm-4 remove-pad">
												<input type="text" name="bid_price['.$ln.']" data-type="bid_price" class="form-control input-xxs bid-input text-center" value="" placeholder="Price">
											</div>
											<div class="col-sm-4 remove-pad">
												<span class="buy_text" data-buy_total="0">
													$0.00
												<span>
											</div>
										</div>
									</div>
								</div>
                            </div>
				';
			}
			$k++;
		}
?>

        <!-- the script for the toggle all checkboxes from header is located in js/theme.js -->
        <div class="table part_info line_<?php echo ($ln+1); ?>">
        	<div class="row first" style="padding: 8px;">
	        	<div class="col-sm-4">
	        		<div class="product-action action-hover text-left">
						<div>
							<input type="checkbox" class="checkAll" checked><br/>
						</div>
						<div class="action-items">
				           	<!-- <a href="javascript:void(0);" class="parts-edit" title="edit selected part(s)"><i class="fa fa-pencil"></i></a><br/> -->
				           	<a href="javascript:void(0);" class="parts-merge" title="merge two selected part(s) into one"><i class="fa fa-chain"></i></a><br/>
	<?php if ($num_results==0) { // add link to create a new part ?>
							<a href="javascript:void(0);" class="add-part" title="add to parts db"><i class="fa fa-plus"></i></a>
	<?php } else { ?>
							<a href="javascript:void(0);" class="parts-index" title="re-index db (reloads page)"><i class="fa fa-cog"></i></a>
	<?php } ?>
						</div>
					</div>
					<div class="qty">
						<input type="text" name="search_qtys[<?php echo $ln; ?>]" value="<?php echo $search_qty; ?>" class="form-control input-xs search-qty input-primary" data-toggle="tooltip" data-placement="top" title="customer request qty or supplier available qty" data-ln="<?php echo $ln; ?>"/><br/>
					</div>
					<div class="product-descr action-hover">
	                	<div class="input-group">
							<input type="text" name="searches[<?php echo $ln; ?>]" data-ln="<?php echo $ln; ?>" value="<?php echo $search_str; ?>" class="product-search text-primary" tabindex="-1" />
	<!--
			       				<span class="input-group-addon action-items">
							</span>
	-->
						</div><!-- /input-group -->
						<span class="info"><?php echo $num_results.' result'.$s; ?></span> &nbsp; <span class="text-danger"><?php echo $explanation; ?></span>
						<?=($num_results == 0 ? '<a href="javascript:void(0);" class="part-modal-show" data-partid="">
								<i class="fa fa-plus"></i></a>' : '');?>
					</div>
					<!-- <div class="price pull-right">
						<div class="form-group target text-right">
							<input name="list_price[<?php echo $ln; ?>]" type="text" value="<?php echo $search_price; ?>" size="6" placeholder="0.00" class="input-xs form-control price-control input-primary" data-toggle="tooltip" data-placement="top" title="customer target price or vendor asking price" />
						</div>
					</div> -->
	        	</div>

	        	<div class="col-sm-8 action-hover slider-box">
					<div class="row">
						<div class="col-sm-2 text-center">
							<div id="marketpricing-<?php echo $ln; ?>" class="header-text">&nbsp;</div>
							<div class="form-group target text-right">
								<input name="list_price[<?php echo $ln; ?>]" type="text" value="<?php echo $search_price; ?>" size="6" placeholder="0.00" class="input-xs form-control price-control input-primary" data-toggle="tooltip" data-placement="top" title="customer target price or vendor asking price" disabled/>
							</div>
						</div>
						<div class="col-sm-2 text-center"><span class="header-text"><?php echo format_price($avg_cost); ?></span><br/><span class="info">avg cost</span></div>
						<div class="col-sm-2 text-center"><span class="header-text"><?php echo $shelflife; ?></span><br/><span class="info">shelflife</span></div>
						<div class="col-sm-2 text-center"><span class="header-text"></span><br/><span class="info">quotes-to-sale</span></div>
						<div class="col-sm-2 text-center">
							<span class="header-text"></span><br/><span class="info">summary</span>
						</div>
						<div class="col-sm-2 text-center">
							<div class="pull-right">
								<a class="line-number-toggle toggle-up" style="cursor: pointer;">
									<i class="fa fa-list-ol"></i>
									<sup><i class="fa options-toggle fa-sort-asc"></i></sup>
								</a>
							</div>

							<div class="slider-frame primary pull-right" data-onclass="default" data-offclass="primary">
								<input type="radio" name="line_number[<?php echo ($ln+1); ?>]" class="row-status line-number hidden" value="Ln <?php echo ($ln+1); ?>">
								<input type="radio" name="line_number[<?php echo ($ln+1); ?>]" class="row-status line-number hidden" value="Off">
								<span data-on-text="Ln <?php echo ($ln+1); ?>" data-off-text="Off" class="slider-button" data-toggle="tooltip" data-placement="top" title="enable/disable results for this row">Ln <?php echo ($ln+1); ?></span>
							</div>
						</div>
					</div>
	        	</div>
        	</div>

        	<div class="part_loader loader text-muted" style="position: relative; margin-top: 25px; margin-bottom: -25px;">
				<div style="color: #777;">
					<i class="fa fa-refresh fa-5x fa-spin"></i><br>
				</div>
			</div>

        	<?php echo $results_rows; ?>

        </div>
         </div>           
<?php } ?>
            
        </div>
<?php
	}//end if ($s)
?>

    </div>

	<!-- hidden values for pagination above -->
	<input type="hidden" name="search_field" value="<?php echo $search_field; ?>" class="search-filter">
	<input type="hidden" name="qty_field" value="<?php echo $qty_field; ?>" class="search-filter">
	<input type="hidden" name="price_field" value="<?php echo $price_field; ?>" class="search-filter">
	<input type="hidden" name="search_from_right" value="<?php echo $search_from_right; ?>" class="search-filter">
	<input type="hidden" name="qty_from_right" value="<?php echo $qty_from_right; ?>" class="search-filter">
	<input type="hidden" name="price_from_right" value="<?php echo $price_from_right; ?>" class="search-filter">
	<input type="hidden" name="startDate" value="<?php echo $startDate; ?>" class="search-filter">
	<input type="hidden" name="endDate" value="<?php echo $endDate; ?>" class="search-filter">
	<input type="hidden" name="favorites" value="<?php echo $favorites; ?>" class="search-filter">
	<input type="hidden" name="sales_count" value="<?php echo $sales_count; ?>" class="search-filter">
	<input type="hidden" name="sales_min" value="<?php echo $sales_min; ?>" class="search-filter">
	<input type="hidden" name="sales_max" value="<?php echo $sales_max; ?>" class="search-filter">
	<input type="hidden" name="stock_min" value="<?php echo $stock_min; ?>" class="search-filter">
	<input type="hidden" name="stock_max" value="<?php echo $stock_max; ?>" class="search-filter">
	<input type="hidden" name="demand_min" value="<?php echo $demand_min; ?>" class="search-filter">
	<input type="hidden" name="demand_max" value="<?php echo $demand_max; ?>" class="search-filter">

	</form>

	<script type="text/javascript">
		var RESULTS_MODE = 0;
		<?php if (isset($_REQUEST['pricing_mode'])) { echo 'RESULTS_MODE = 1'; } ?>
	</script>

<?php include_once 'modal/results.php'; ?>
<?php include_once 'modal/notes.php'; ?>
<?php include_once 'modal/remotes.php'; ?>
<?php include_once 'modal/image.php'; ?>
<?php include_once 'modal/parts.php'; ?>
<?php include_once 'inc/footer.php'; ?>

<script src="js/sales.js"></script>

</body>
</html>
