<?php	
		
	$rootdir = $_SERVER['ROOT_DIR'];
		
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getInvoice.php';
	include_once $rootdir.'/inc/order_type.php';

	$companyid = 0;
	$lumpid = 0;
	$order_type = '';
	$table_rows = '';
	$lump_grouping = array();
	$lump_created_msg = "";
	if (isset($_REQUEST['companyid'])) { $companyid = $_REQUEST['companyid']; }
	if (isset($_REQUEST['lumpid'])) { $lumpid = $_REQUEST['lumpid']; }
	if (isset($_REQUEST['order_type']) AND ($_REQUEST['order_type']=='Repair' OR $_REQUEST['order_type']=='Sale')) { $order_type = $_REQUEST['order_type']; }

	$startDate = format_date($today,'01/01/Y',array('y'=>-1));//date('m/d/Y');
	if (isset($_REQUEST['START_DATE']) AND $_REQUEST['START_DATE']){
		$startDate = format_date($_REQUEST['START_DATE'], 'm/d/Y');
	}
	$endDate = date('m/d/Y');
	if (isset($_REQUEST['END_DATE']) AND $_REQUEST['END_DATE']){
		$endDate = format_date($_REQUEST['END_DATE'], 'm/d/Y');
	}

	$dbStartDate = '';
	$dbEndDate = '';
	if ($startDate) {
		$dbStartDate = format_date($startDate, 'Y-m-d').' 00:00:00';
		$dbEndDate = format_date($endDate, 'Y-m-d').' 23:59:59';
	}

	if(count($_REQUEST['check'])){
		$lump_insert = 'Insert into `invoice_lumps` (`date`) VALUES ('.prep(format_date($now,"Y-m-d")).');';
		qdb($lump_insert) or die(qe()." | $lump_insert");
		$lumpid = qid();
		foreach($_REQUEST['check'] as $invno => $state){
			$item_insert ="INSERT INTO `invoice_lump_items` (lumpid, invoice_no)values ('$lumpid','$invno');";
			qdb($item_insert) or die(qe()." | $item_insert");
		}
		$lump_created_msg = "Lump #$lumpid Successfully Created!";
	}

	$lump_title = '<em>Please select a company or invoice lump</em>';
	$query = "SELECT ili.lumpid, il.date, i.companyid FROM invoice_lumps il, invoice_lump_items ili, invoices i ";
	$query .= "WHERE il.id = ili.lumpid AND i.invoice_no = ili.invoice_no ";
	if ($companyid) { $query .= "AND i.companyid = '".res($companyid)."' "; }
	if ($dbStartDate) {
		$query .= "AND ((i.date_invoiced BETWEEN CAST('".$dbStartDate."' AS DATETIME) AND CAST('".$dbEndDate."' AS DATETIME)) ";
		$query .= "OR (il.date BETWEEN CAST('".$dbStartDate."' AS DATE) AND CAST('".$dbEndDate."' AS DATE))) ";
	}
	$query .= "GROUP BY il.id ORDER BY il.id DESC; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$s = '';
		$lump_info = $r['lumpid'].' '.format_date($r['date'],'n/j/y').' '.getCompany($r['companyid']);
		if ($lumpid==$r['lumpid']) {
			$s = ' selected';
			$lump_title = $lump_info;
		}
		$lump_options .= '<option value="'.$r['lumpid'].'"'.$s.'>'.$lump_info.'</option>'.chr(10);
	}

	if ($companyid OR $lumpid) {
		$query = "SELECT i.* FROM invoices i ";
		if ($lumpid) { $query .= ", invoice_lump_items ili "; }
		else { $query .= "LEFT JOIN invoice_lump_items ili ON ili.invoice_no = i.invoice_no "; }
		$query .= "WHERE 1 = 1 ";
		if ($companyid) { $query .= "AND i.companyid = '".res($companyid)."' "; }
		if ($lumpid) { $query .= "AND ili.invoice_no = i.invoice_no AND ili.lumpid = '".res($lumpid)."' "; }
		else { $query .= "AND ili.invoice_no IS NULL "; }
		if ($dbStartDate) {
			$query .= "AND (i.date_invoiced BETWEEN CAST('".$dbStartDate."' AS DATETIME) AND CAST('".$dbEndDate."' AS DATETIME)) ";
		}
		if ($order_type) { $query .= "AND i.order_type = '".res($order_type)."' "; }
		$query .= "ORDER BY i.invoice_no DESC; ";
		$result = qdb($query) or die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$total = getInvoice($r['invoice_no']);
			$T = order_type($r['order_type']);

			$table_rows .= "
				<tr>
					<td>".format_date($r['date_invoiced'],"M d Y")."</td>
					<td>".$r['invoice_no'].' <a href="/docs/INV'.$r['invoice_no'].'.pdf" target="_blank"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></a>'."</td>
					<td>".strtoupper($T['abbrev'])."# ".$r['order_number'].' <a href="/'.$T['abbrev'].$r['order_number'].'"><i class="fa fa-arrow-right" aria-hidden="true"></i></a>'."</td>
					<td>".format_price($total)."</td>
					<td>".(($lumpid)? "" : "<input type='checkbox' class='form_handle lump_checks pull-right' name=check[".$r['invoice_no']."]/>")."</td>
				</tr>
			";
		}
	}
?>
<!DOCTYPE html>
<!----------------------------------------------------------------------------->
<!------------------------------- HEADER OUTPUT ------------------------------->
<!----------------------------------------------------------------------------->
<!DOCTYPE html>
<html>
<!-- Declaration of the standard head with Accounts home set as title -->
<head>
	<title>Lumps</title>
	<?php
		//Standard headers included in the function
		include_once $rootdir.'/inc/scripts.php';
	?>
	<link rel="stylesheet" href="../css/operations-overrides.css?id=<?php if (isset($V)) { echo $V; } ?>" type="text/css" />

</head>

<body class="sub-nav">
	
<!----------------------------------------------------------------------------->
<!------------------------- Output the navigation bar ------------------------->
<!----------------------------------------------------------------------------->
	<?php include 'inc/navbar.php'; ?>
	<?php include_once 'modal/history.php'?>
	
<!----------------------------------------------------------------------------->
<!-------------------------- Header/Filter Bar/Title -------------------------->
<!----------------------------------------------------------------------------->
	<form id="lump_sub" method="GET" action="/lumps.php">
		<div class="table-header" style="width: 100%; height: 60px;">
			<div class="row" style="padding: 8px;" id = "filterBar">
				<div class = "col-md-1 col-sm-1">
					<select name="order_type" id="order_type" class="input-sm form-control">
						<option value="">- Order Type -</option>
						<option value="Repair"<?php if ($order_type=='Repair') { echo ' selected'; } ?>>Repair</option>
						<option value="Sale"<?php if ($order_type=='Sale') { echo ' selected'; } ?>>Sale</option>
					</select>
				</div>
				<div class = "col-md-1 col-sm-1">
<?php if ($lumpid) { ?>
						<a target="_blank" href="/docs/LUMP<?php echo $lumpid; ?>.pdf" class="btn btn-brown btn-sm pull-left"><i class="fa fa-file-pdf-o"></i></a>
<?php } ?>
				</div>
				<div class = "col-md-3 col-sm-3">
					<div class="form-group col-md-4 nopadding">
						<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY">
				            <input type="text" name="START_DATE" class="form-control input-sm" value="<?php echo $startDate; ?>">
				            <span class="input-group-addon">
				                <span class="fa fa-calendar"></span>
				            </span>
				        </div>
					</div>
					<div class="form-group col-md-4 nopadding">
						<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY" data-maxdate="<?php echo date("m/d/Y"); ?>">
				            <input type="text" name="END_DATE" class="form-control input-sm" value="<?php echo $endDate; ?>">
				            <span class="input-group-addon">
				                <span class="fa fa-calendar"></span>
				            </span>
					    </div>
					</div>
					<div class="form-group col-md-4 nopadding">
						<div class="btn-group" id="dateRanges">
							<div id="btn-range-options">
								<button class="btn btn-default btn-sm">&gt;</button>
								<div class="animated fadeIn hidden" id="date-ranges" style = 'width:217px;'>
							        <button class="btn btn-sm btn-default left large btn-report" type="button" data-start="<?php echo date("m/01/Y"); ?>" data-end="<?php echo date("m/d/Y"); ?>">MTD</button>
					    			<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("01/01/Y"); ?>" data-end="<?php echo date("03/31/Y"); ?>">Q1</button>
									<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("04/01/Y"); ?>" data-end="<?php echo date("06/30/Y"); ?>">Q2</button>
									<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("07/01/Y"); ?>" data-end="<?php echo date("09/30/Y"); ?>">Q3</button>
									<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("10/01/Y"); ?>" data-end="<?php echo date("12/31/Y"); ?>">Q4</button>
								</div><!-- animated fadeIn -->
							</div><!-- btn-range-options -->
						</div><!-- btn-group -->
					</div><!-- form-group -->
				</div>
				<div class="col-md-2 col-sm-2 text-center">
	            	<h2 class="minimal">Lumps</h2>
					<span class="info"><?php echo $lump_title; ?></span>
				</div>
				<!--This Handles the Search Bar-->
				<div class="col-md-1 col-sm-1"></div>			
				<!--Condition Drop down Handler-->
				<div class="col-md-2 col-sm-2">
					<select id = "lumpid" name = 'lumpid' class ="form-control input-sm">
						<option value = ''>- Select a Lump -</option>
						<?=$lump_options?>
					</select>
				</div>
				<div class="col-md-2 col-sm-2">
					<div class="company input-group">
						<select name='companyid' id='companyid' class='form-control input-xs company-selector required' >
							<option value=''>Select a Company</option>
							<?php if($companyid){
								echo"<option value='$companyid' selected>".getCompany($companyid)."<option>";
							}?>
						</select>
						<span class="input-group-btn">
							<button class="btn btn-sm btn-primary part_filter" type = 'submit'><i class="fa fa-filter"></i></button>   
						</span>
					</div>
				</div>
			</div>
		</div>
		
	    <div id="pad-wrapper">
			    <div class ='col-md-12'>
			    	<div class='table-responsive'>
			    		<table class='table table-hover table-striped table-condensed'>
			    		    <thead>
			    		        <th class='col-md-4'>Date Invoiced</th>
			    		        <th class='col-md-4'>Invoice #</th>
			    		        <th class='col-md-2'>Order</th>
			    		        <th class='col-md-3'>Amount</th>
			    		        <th class='col-md-2'><button id='lump_these'class='btn btn-xs' type='submit' disabled>Lump</button></th>
			    		    </thead>
			    		    <tbody>
			                    <?=$table_rows?>
			                </tbody>
			    		</table>
			    	</div>
			    </div>
		</div>
	</form>
	
</body>
	<?php
		include 'inc/footer.php';
	?>
<script type="text/javascript">
	$(document).ready(function() {
		function toggleLoader(msg) {
			if ($("#loading-bar").is(':visible')) {
				$("#loading-bar").fadeOut('fast');
			} else {
				if (! msg) { msg = 'Loading'; }
				$("#loading-bar").html(msg);
	
				$("#loading-bar").show();
				setTimeout("toggleLoader()",1000);
			}
		}

		$(".lump_checks").click(function(){
			if($(".lump_checks:checked").length > 0){
				$("#lump_these").prop( "disabled", false );
				$("#lump_these").addClass("btn-success");
			} else {
				$("#lump_these").prop( "disabled", true );
				$("#lump_these").removeClass("btn-success");
			}
		});
		$("#lumpid,#order_type").select2();
		$("#lumpid,#order_type").change(function() {
			$(this).closest("form").submit();
		});
		
	});
</script>
			
