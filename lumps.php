<?php	
		
	$rootdir = $_SERVER['ROOT_DIR'];
		
		include_once $rootdir.'/inc/dbconnect.php';
		include_once $rootdir.'/inc/format_date.php';
		include_once $rootdir.'/inc/format_price.php';
		include_once $rootdir.'/inc/getCompany.php';
		include_once $rootdir.'/inc/getPart.php';
		include_once $rootdir.'/inc/form_handle.php';
		include_once $rootdir.'/inc/getUser.php';
		include_once $rootdir.'/inc/keywords.php';
		include_once $rootdir.'/inc/filter.php';
		include_once $rootdir.'/inc/order_parameters.php';
		
		//VarDec
		$companyid = '';
		$startDate = '';
		$endDate = '';
		$lump_no = '';
		$table_rows = '';
		$lump_grouping = array();
		$lump_created_msg = "";
		$startDate = $_REQUEST['START_DATE'];
		$endDate = $_REQUEST['END_DATE'];
		$companyid = $_REQUEST['companyid'];
		$lump_no = $_REQUEST['lumpid'];
		if(!$startDate){
			$startDate = format_date($now,"Y-m-d",array("m"=>-1));
		}
	
		if(count($_REQUEST['check'])){
			$lump_insert = 'Insert into `invoice_lumps` (`date`) VALUES ('.prep(format_date($now,"Y-m-d")).');';
			qdb($lump_insert) or die(qe()." | $lump_insert");
			$lump_no = qid();
			foreach($_REQUEST['check'] as $invno => $state){
				$item_insert ="INSERT INTO `invoice_lump_items` (lumpid, invoice_no)values ('$lump_no','$invno');";
				qdb($item_insert) or die(qe()." | $item_insert");
			}
			$lump_created_msg = "Lump #$lump_no Successfully Created!";
		}
		// echo("<script>console.log(".prep($lump_no,'"Null"').")</script>");
		if($companyid){
			$invoice_select = "
			SELECT i . * , max(il.id) AS lump, il.date as lumpdate, SUM( ii.amount ) AS total
			FROM invoices i
			LEFT JOIN `invoice_items` ii ON ii.invoice_no = i.invoice_no
			LEFT JOIN `invoice_lump_items` ili ON ili.`invoice_no` = i.invoice_no
			LEFT JOIN `invoice_lumps` il ON ili.lumpid = il.id
			".sFilter('companyid',$companyid,true)."
			AND (
			".dFilter('date_invoiced',$startDate,$endDate,'')."
			".dFilter('`il`.`date`',$startDate,$endDate,'OR').")
			group by i.invoice_no
			order by i.invoice_no desc
			;";
			// echo("<script>console.log(".prep($invoice_select,'"Null"').");</script>");
			$invoices = qdb($invoice_select) or die(qe()." | $invoice_select");
			foreach($invoices as $invoice){
				if($invoice['lump'] && $invoice['lumpdate']){
					if(!$lump_grouping[$invoice['lump']]){
						$lump_options .= "<option value='".$invoice['lump']."'";
						$lump_options .= (($lump_no==$invoice['lump'])?' selected':'').">LUMP #".$invoice['lump']." (".$invoice['lumpdate'].")</option>";
						$lump_grouping[$invoice['lump']] = true;
					}
				}

				//Either print out the rows with invoices or the rows which have
				if($lump_no && $invoice['lump'] !=$lump_no){continue;}
				else if(!$lump_no && $invoice['lump']){continue;}
				$o_display = o_params($invoice['order_type']);
				$table_rows .= "
				<tr>
					<td>".format_date($invoice['date_invoiced'],"M d Y")."</td>
					<td>".$invoice['invoice_no'].' <a href="/docs/INV'.$invoice['invoice_no'].'.pdf" target="_blank"><i class="fa fa-arrow-right" aria-hidden="true"></i></a>'."</td>
					<td>".strtoupper($o_display['short'])."# ".$invoice['order_number'].' <a href="/'.$o_display['short'].$invoice['order_number'].'" target="_blank"><i class="fa fa-arrow-right" aria-hidden="true"></i></a>'."</td>
					<td>".format_price($invoice['total'])."</td>
					<td>".(($lump_no)? "" : "<input type='checkbox' class='form_handle lump_checks pull-right' name=check[".$invoice['invoice_no']."]/>")."</td>
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
	<form id="lump_sub" method='POST' action='/lumps.php'>
		<div class="table-header" style="width: 100%; min-height: 48px;">
			<div class="row" style="padding: 8px;" id = "filterBar">
				<div class = "col-md-3">
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
				<div class="col-md-2 col-sm-2" style='padding-right:0px;'></div>
				<div class="col-md-2 col-sm-2 text-center">
	            	<h2 class="minimal">Lumps</h2>
				</div>
				<!--This Handles the Search Bar-->
				<div class="col-md-1 col-sm-1"></div>			
				<!--Condition Drop down Handler-->
				<div class="col-md-2 col-sm-2">
					<select id = "lumpselect" name = 'lumpid' class ="form-control">
						<option value = ''>Select a lump</option>
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
			<?php if(!($companyid)): ?>
			<div class="text-center">
				<em>Please enter a company to see lumpable invoices</em>
			</div>
			<?php endif;?>
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
	(function($){
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
			if($(".lump_checks:checked").length > 1){
				$("#lump_these").prop( "disabled", false );
				$("#lump_these").addClass("btn-success");
			} else {
				$("#lump_these").prop( "disabled", true );
				$("#lump_these").removeClass("btn-success");
			}
		});
		
	})(jQuery);
</script>
			
