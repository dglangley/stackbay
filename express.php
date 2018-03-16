<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getRecords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/calcQuarters.php';

	$report_type = 'summary';
	if (isset($_REQUEST['report_type']) AND $_REQUEST['report_type']=='detail') { $report_type = 'detail'; }

	$market_table = 'Demand';
	if (isset($_REQUEST['market_table']) AND $_REQUEST['market_table']=='detail') { $market_table = 'Supply'; }

	$min_records = '';
	if (isset($_REQUEST['min_records'])) { $min_records = trim($_REQUEST['min_records']); }

	$min_price = '';
	$max_price = '';
	if ($_REQUEST['min']){
		$min_price = trim($_REQUEST['min']);
	}
	if ($_REQUEST['max']){
		$max_price = trim($_REQUEST['max']);
	}

	$favorites = 0;
	if ($_REQUEST['favorites']) { $favorites = $_REQUEST['favorites']; }

	//Calculate the standard year range, output quarters as an array, and make 
	$last_week = date('m/d/Y', strtotime('-1 week', strtotime($today)));

	$startDate = format_date($last_week, 'm/d/Y');
 	if ($_REQUEST['START_DATE']){
		$startDate = format_date($_REQUEST['START_DATE'], 'm/d/Y');
	} else if ($market_table=='demand') {
		$startDate = '';
	}

	$endDate = '';
	if ($_REQUEST['END_DATE']){
		$endDate = format_date($_REQUEST['END_DATE'],'m/d/Y');
	}

	if (! $startDate AND ! $endDate AND ! $min_records AND ! $min_price AND ! $max_price) {
		$startDate = format_date($last_week, 'm/d/Y');
		if ($market_table=='demand') { $min_price = 1; }
	}
	// for getRecords()
	$record_start = $startDate;
	$record_end = $endDate;

	$companyid = 0;
	if (isset($_REQUEST['companyid'])) { $companyid = $_REQUEST['companyid']; }

	if ($favorites) {
		$query = "SELECT * FROM favorites f, parts p ";
		$query .= "WHERE p.id = f.partid ";
		$query .= "ORDER BY f.id DESC; ";
		$results = qedb($query);
	} else {
	    $results = getRecords('','','csv',$market_table);
	}

	foreach ($results as $r) {
		$partid = $r['partid'];
	}

	$TITLE = 'Express';
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo $TITLE; ?></title>
	<?php
		/*** includes all required css includes ***/
		include_once 'inc/scripts.php';
	?>

	<!-- any page-specific customizations -->
	<style type="text/css">
	</style>
</head>
<body>

<?php include_once 'inc/navbar.php'; ?>

<!-- FILTER BAR -->
<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px; max-height:60px;">
	<form class="form-inline" method="get" action="express.php" enctype="multipart/form-data" id="filters-form" >

	<div class="row" style="padding:8px">
		<div class="col-sm-1">
		    <div class="btn-group">
		        <button class="btn btn-xs left btn-radio <?= ($report_type=='summary' ? 'active btn-primary' : ''); ?>" type="submit" data-value="summary" data-toggle="tooltip" data-placement="bottom" title="most requested">
		        	<i class="fa fa-sort-numeric-desc"></i>	
		        </button>
				<input type="radio" name="report_type" value="summary" class="hidden"<?php if ($report_type=='summary') { echo ' checked'; } ?>>
		        <button class="btn btn-xs right btn-radio <?= ($report_type=='detail' ? 'active btn-primary' : ''); ?>" type="submit" data-value="detail" data-toggle="tooltip" data-placement="bottom" title="most recent">
		        	<i class="fa fa-history"></i>	
		        </button>
		        <input type="radio" name="report_type" value="detail" class="hidden"<?php if ($report_type=='detail') { echo ' checked'; } ?>>
		    </div>
		</div>
		<div class="col-sm-1">
			<div class="btn-group">
		        <button class="btn btn-xs left btn-radio <?= ($market_table=='Supply' ? 'active btn-warning' : ''); ?>" type="submit" data-value="Supply">
		        	Supply	
		        </button>
				<input type="radio" name="market_table" value="Supply" class="hidden"<?php if ($market_table=='Supply') { echo ' checked'; } ?>>
		        <button class="btn btn-xs right btn-radio <?= ($market_table=='Demand' ? 'active btn-success' : ''); ?>" type="submit" data-value="Demand">
		        	Demand
		        </button>
		        <input type="radio" name="market_table" value="Demand" class="hidden"<?php if ($market_table=='Demand') { echo ' checked'; } ?>>
		    </div>
		</div>
		<div class="col-sm-3">
			<div class="form-group">
				<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY" data-maxdate="<?php echo date("m/d/Y"); ?>">
		            <input type="text" name="START_DATE" class="form-control input-sm" value="<?php echo $startDate; ?>">
		            <span class="input-group-addon">
		                <span class="fa fa-calendar"></span>
		            </span>
		        </div>
			</div>
			<div class="form-group">
				<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY" data-maxdate="<?php echo date("m/d/Y"); ?>">
		            <input type="text" name="END_DATE" class="form-control input-sm" value="<?php echo $endDate; ?>">
		            <span class="input-group-addon">
		                <span class="fa fa-calendar"></span>
		            </span>
			    </div>
			</div>
			<div class="form-group">
				<button class="btn btn-primary btn-sm" type="submit" ><i class="fa fa-filter" aria-hidden="true"></i></button>
				<div class="btn-group" id="dateRanges">
<!--
				<div class="btn-group" id="shortDateRanges">
-->
					<div id="btn-range-options">
						<button class="btn btn-default btn-sm">&gt;</button>
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
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="col-sm-2 text-center">
			<h2 class="minimal"><?php echo $TITLE; ?></h2>
			<span class="info"></span>
		</div>
		<div class="col-sm-1 text-center">
			<div class="row">
				<div class="col-sm-4">
					<input name="favorites" value="1" class="hidden" type="checkbox"<?=($favorites ? ' checked' : '');?>>
					<button type="button" class="btn btn-xs btn-favorites btn-<?=($favorites ? 'danger' : 'default');?>" title="Favorites" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-star"></i></button>
				</div>
				<div class="col-sm-8">
					<input type="text" name="min_records" class="form-control input-sm" value="<?=$min_records;?>" placeholder="Min#">
				</div>
			</div>
		</div>
		<div class="col-sm-1 text-center">
			<div class="input-group">
				<input type="text" name="min" class="form-control input-sm" value="<?= ($min_price<>'' ? format_price($min_price, false, '', true) : ''); ?>" placeholder = "Min$"/>
				<span class="input-group-addon">-</span>
				<input type="text" name="max" class="form-control input-sm" value="<?= ($max_price<>'' ? format_price($max_price, false, '', true) : ''); ?>" placeholder = "Max$"/>
			</div>
		</div>
		<div class="col-sm-2">
			<div class="pull-right form-inline">
				<div class="input-group">
					<select name="companyid" id="companyid" class="company-selector">
						<?= ($companyid ? '<option value="'.$companyid.'" selected>'.getCompany($companyid).'</option>'.chr(10) : ''); ?>
					</select>
					<button class="btn btn-primary btn-sm" type="submit" ><i class="fa fa-filter" aria-hidden="true"></i></button>
				</div>
			</div>
		</div>
		<div class="col-sm-1">
			<div class="dropdown pull-right">
				<button class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"><i class="fa fa-chevron-down"></i></button>
				<ul class="dropdown-menu pull-right text-left" role="menu">
					<li><a href="javascript:void(0);" class="btn-market"><i class="fa fa-cubes"></i> Open in Market</a></li>
				</ul>
			</div>
		</div>
	</div>

	</form>
</div>

<div id="pad-wrapper">
<form class="form-inline" method="get" action="" enctype="multipart/form-data" >

</form>
</div><!-- pad-wrapper -->

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<script type="text/javascript">
	$(document).ready(function() {
	});
</script>

</body>
</html>
