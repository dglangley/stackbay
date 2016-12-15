<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/getPipeIds.php';

	//=========================================================================================
	//==================================== FILTERS SECTION ====================================
	//=========================================================================================
	
	//Company Id is grabbed from the search field at the top, but only if one has been passed in
	$company_filter = 0;
	if (isset($_REQUEST['companyid']) AND is_numeric($_REQUEST['companyid']) AND $_REQUEST['companyid']>0) { 
		$company_filter = $_REQUEST['companyid']; 
	}
	
	//This is saved as a cookie in order to cache the results of the button function within the same window
	setcookie('report_type',$report_type);
	
	$order = '';
	if (isset($_REQUEST['order']) AND $_REQUEST['order']){
		$report_type = 'detail';
		$order = $_REQUEST['order'];
	}
	
	$part = '';
	$part_string = '';
	if (isset($_REQUEST['part']) AND $_REQUEST['part']){
    	$part = $_REQUEST['part'];

    	$part_list = getPipeIds($part);
    	foreach ($part_list as $id => $array) {
    	    $part_string .= $id.',';
    	}
    	$part_string = rtrim($part_string, ",");
    }
	
	$startDate = '';
	if (isset($_REQUEST['START_DATE']) AND $_REQUEST['START_DATE']) {
		$startDate = format_date($_REQUEST['START_DATE'], 'm/d/Y');
	}
	// if no start date passed in, or invalid, set to beginning of quarter by default
	if (! $startDate) {
		$year = date('Y');
		$m = date('m');
		$q = (ceil($m/3)*3)-2;
		if (strlen($q)==1) { $q = '0'.$q; }
		$startDate = $q.'/01/'.$year;
	}

	$endDate = date('m/d/Y');
	if (isset($_REQUEST['END_DATE']) AND $_REQUEST['END_DATE']){
		$endDate = format_date($_REQUEST['END_DATE'], 'm/d/Y');
	}
?>

<!------------------------------------------------------------------------------------------->
<!-------------------------------------- HEADER OUTPUT -------------------------------------->
<!------------------------------------------------------------------------------------------->
<!DOCTYPE html>
<html>
<!-- Declaration of the standard head with P&L home set as title -->
<head>
	<title>Profit and Loss Report</title>
	<?php
		//Standard headers included in the function
		include_once $rootdir.'/inc/scripts.php';
	?>
	<style type="text/css">
		.table td {
			vertical-align:top !important;
		}
	</style>
</head>

<body class="sub-nav accounts-body">
	
	<?php include 'inc/navbar.php'; ?>

	<!-- Wraps the entire page into a form for the sake of php trickery -->
	<form class="form-inline" method="get" action="/profit_loss.php">

    <table class="table table-header table-filter">
		<tr>
		<td class = "col-md-2">

		</td>

		<td class = "col-md-3">
			<div class="form-group">
				<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY">
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
					<div class="btn-group" id="dateRanges">
						<div id="btn-range-options">
							<button class="btn btn-default btn-sm">&gt;</button>
							<div class="animated fadeIn hidden" id="date-ranges">
						        <button class="btn btn-sm btn-default left large btn-report" type="button" data-start="<?php echo date("m/01/Y"); ?>" data-end="<?php echo date("m/d/Y"); ?>">MTD</button>
				    			<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("01/01/Y"); ?>" data-end="<?php echo date("03/31/Y"); ?>">Q1</button>
								<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("04/01/Y"); ?>" data-end="<?php echo date("06/30/Y"); ?>">Q2</button>
								<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("07/01/Y"); ?>" data-end="<?php echo date("09/30/Y"); ?>">Q3</button>		
								<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("10/01/Y"); ?>" data-end="<?php echo date("12/31/Y"); ?>">Q4</button>	
<?php
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
		</td>
		<td class="col-md-2 text-center">

			<input type="text" name="order" class="form-control input-sm" value ='<?php echo $order?>' placeholder = "Order #" disabled />
		</td>
		
		<td class="col-md-2 text-center">
			<input type="text" name="part" class="form-control input-sm" value ='<?php echo $part?>' placeholder = 'Part/HECI' disabled />
		</td>
		<td class="col-md-3">
			<div class="pull-right form-group">
			<select name="companyid" id="companyid" class="company-selector" disabled >
					<option value="">- Select a Company -</option>
				<?php 
				if ($company_filter) {echo '<option value="'.$company_filter.'" selected>'.(getCompany($company_filter)).'</option>'.chr(10);} 
				else {echo '<option value="">- Select a Company -</option>'.chr(10);} 
				?>
				</select>
					<button class="btn btn-primary btn-sm" type="submit" >
						<i class="fa fa-filter" aria-hidden="true"></i>
					</button>
			</div>
			</td>
		</tr>
	</table>
	<!-- If the summary button is pressed, inform the page and depress the button -->
	
	
<!------------------------------------------------------------------------------------>
<!---------------------------------- FILTERS OUTPUT ---------------------------------->
<!------------------------------------------------------------------------------------>
    <div id="pad-wrapper">
		<div class="row filter-block">

		<br>
            <!-- orders table -->
            <div class="table-wrapper">

<!-- If there is a company id, output the text of that company id to the top of the screen -->
                <div class="row head text-center">
                    <div class="col-md-12">
                        <h2>Profit and Loss Report</h2>
                    </div>
                </div>

			<!-- If the summary button is pressed, inform the page and depress the button -->


<!--================================================================================-->
<!--=============================   PRINT TABLE ROWS   =============================-->
<!--================================================================================-->
<?php
	//Establish a blank array for receiving the results from the table
	$results = array();
	$oldid = 0;
//	echo getCompany($company_filter,'id','oldid');
//	echo('Value Passed in: '.$company_filter);
	//If there is a company id, translate it to the old identifier
	if($company_filter != 0){$oldid = dbTranslate($company_filter, false);}
//	echo '<br>The value of this company in the old database is: '.$oldid;

	$rows = '';
	$total_pcs = 0;
	$total_amt = 0;

	$results = array();
	
	//Write the query for the gathering of Pipe data
	$query = "SELECT si.serial, si.cost actual_cost, i.id, si.so_id, si.price, si.rep_id, si.freight_cost, si.po, si.avg_cost, ";
	$query .= "si.invoice_id, si.orig_cost, so.po_number, so.complete, i.part_number, i.heci, i.clei, c.name, so.so_date, oq_id ";
	$query .= "FROM inventory_solditem si, inventory_salesorder so, inventory_inventory i, inventory_outgoing_quote oq, inventory_company c ";
	$query .= "WHERE so.quote_ptr_id = si.so_id AND si.inventory_id = i.id AND oq_id = oq.id AND c.id = company_id ";

   	if ($startDate) {
   		$dbStartDate = format_date($startDate, 'Y-m-d');
   		$dbEndDate = format_date($endDate, 'Y-m-d');
   		$query .= "AND so_date between CAST('".$dbStartDate."' AS DATE) and CAST('".$dbEndDate."' AS DATE) ";
	}
	$query .= "ORDER BY so_id ASC, so_date ASC; ";
	$result = qdb($query,'PIPE') OR die(qe('PIPE').' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$key = $r['so_date'].'.A'.$r['so_id'].'.'.$r['oq_id'];
		if (! isset($results[$key])) {
			$results[$key] = array(
				'type'=>'Sale',
				'qty'=>0,
				'cogs'=>0,
				'income'=>0,
				'order'=>$r['so_id'],
				'description'=>$r['part_number'].' '.$r['clei'],
				'price'=>$r['price'],
				'company'=>$r['name'],
				'actual_cost'=>$r['actual_cost'],
				'avg_cost'=>$r['avg_cost'],
				'date'=>$r['so_date'],
				'po_number'=>$r['po_number'],
			);
		}
		$results[$key]['qty']++;
		// Brian uses a COGS basis that floats between avg cost and actual cost: when avg is 0, use actual
		if ($r['avg_cost']>0) {
			$results[$key]['cogs'] += $r['avg_cost'];
		} else {
			$results[$key]['cogs'] += $r['actual_cost'];
		}
		$results[$key]['income'] += $r['price'];
	}

	$query = "SELECT cm.id cm_id, cm.date, cm.ref_no, cm.po_number, cm.rma_id, ";
	$query .= "li.desc, li.quantity qty, li.amount, c.name, si.cost actual_cost, si.avg_cost ";
	$query .= "FROM inventory_creditmemo cm, inventory_creditmemoli li, inventory_rmaticket r, inventory_solditem si, inventory_company c ";
	$query .= "WHERE li.cm_id = cm.id AND customer_id = c.id AND cm.voided = 0 AND cm.rma_id = r.id AND r.item_id = si.id ";
   	if ($startDate) {
   		$dbStartDate = format_date($startDate, 'Y-m-d');
   		$dbEndDate = format_date($endDate, 'Y-m-d');
   		$query .= "AND cm.date between CAST('".$dbStartDate."' AS DATE) and CAST('".$dbEndDate."' AS DATE) ";
	}
	$query .= "ORDER BY li.cm_id ASC, cm.date ASC; ";
	$result = qdb($query,'PIPE') OR die(qe('PIPE').' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$key = $r['date'].'.B'.$r['cm_id'];
		if (! isset($results[$key])) {
			$results[$key] = array(
				'type'=>'Credit',
				'qty'=>0,
				'cogs'=>0,
				'income'=>0,
				'order'=>$r['cm_id'],
				'description'=>$r['desc'],
				'price'=>$r['amount'],
				'company'=>$r['name'],
				'actual_cost'=>$r['actual_cost'],
				'avg_cost'=>$r['avg_cost'],
				'date'=>$r['date'],
				'po_number'=>$r['po_number'],
			);
		}
		$results[$key]['qty']++;
		// Brian uses a COGS basis that floats between avg cost and actual cost: when avg is 0, use actual
		if ($r['avg_cost']>0) {
			$results[$key]['cogs'] += $r['avg_cost'];
		} else {
			$results[$key]['cogs'] += $r['actual_cost'];
		}
		$results[$key]['income'] += $r['price'];
	}

	ksort($results);

	$sum_qty = 0;
	$sum_ext_price = 0;
	$sum_cogs = 0;
	$sum_profit = 0;
	foreach ($results as $r) {
		if ($r['cogs']=='') { $r['cogs'] = '0.00'; }
		$ext_price = ($r['qty']*$r['price']);
		$profit = ($r['income']-$r['cogs']);

		if ($r['type']=='Sale') {
        	$type = '<span class="label label-success label-box">Sale</span>';

			$sum_qty += $r['qty'];
			$sum_ext_price += $ext_price;
			$sum_cogs += $r['cogs'];
			$sum_profit += $profit;
		} else {
        	$type = '<span class="label label-danger label-box">Credit</span>';

			$sum_qty -= $r['qty'];
			$sum_ext_price -= $ext_price;
			$sum_cogs -= $r['cogs'];
			$sum_profit += $profit;
		}

		$rows .= '
                            <!-- row -->
                            <tr>
                                <td>
                                    '.$type.' '.format_date($r['date'],'M j, Y').'
                                </td>
                                <td>
                                    '.$r['description'].'
                                </td>
                                <td>
                                    '.$r['qty'].'
                                </td>
                                <td class="text-right">
                                    '.format_price($r['price'],true,' ').'
                                </td>
                                <td class="text-right">
                                    '.format_price($ext_price,true,' ').'
                                </td>
                                <td>
                                    <strong>'.$r['order'].'</strong>
                                </td>
                                <td>
	                                <a href="#">'.$r['company'].'</a>
                                </td>
                                <td class="text-right">
                                    '.format_price($r['cogs'],true,' ').'
                                </td>
                                <td class="text-right">
                                    '.format_price($profit,true,' ').'
                                </td>
                            </tr>
		';
	}
?>


	<!-- Declare the class/rows dynamically by the type of information requested (could be transitioned to jQuery) -->
                <div class="row">
                    <table class="table table-hover table-striped table-condensed">
                        <thead>
                            <tr>
                                <th class="col-md-1">
                                    <span class="line"></span>
                                    Date 
                                </th>
                                <th class="col-md-3">
                                    <span class="line"></span>
                                    Description
                                </th>
                                <th class="col-md-1">
                                    <span class="line"></span>
                                    Qty
                                </th>
                                <th class="col-md-1 text-right">
                                    <span class="line"></span>
                                    Price &nbsp;
                                </th>
                                <th class="col-md-1 text-right">
                                    <span class="line"></span>
                                    Ext Price &nbsp;
                                </th>
                                <th class="col-md-1">
                                    <span class="line"></span>
                                    Order#
                                </th>
                                <th class="col-md-2">
                                    <span class="line"></span>
                                    Company
                                </th>
                                <th class="col-md-1 text-right">
                                    <span class="line"></span>
									COGS &nbsp;
                                </th>
                                <th class="col-md-1 text-right">
                                    Profit
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                        	<?php echo $rows; ?>
							<tr>
								<td colspan="2"> </td>
								<td><?php echo $sum_qty; ?></td>
								<td> </td>
                                <td class="text-right">
                                    <?php echo format_price($sum_ext_price,true,' '); ?>
                                </td>
								<td colspan="2"> </td>
                                <td class="text-right">
                                    <?php echo format_price($sum_cogs,true,' '); ?>
                                </td>
                                <td class="text-right">
                                    <?php echo format_price($sum_profit,true,' '); ?>
                                </td>
							</tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- end orders table -->


	</div>
	</form>
<?php include_once 'inc/footer.php'; ?>

    <script type="text/javascript">

        $(document).ready(function() {
			$('.btn-report').click(function() {
				var btnValue = $(this).data('value');
				$(this).closest("div").find("input[type=radio]").each(function() {
					if ($(this).val()==btnValue) { $(this).attr('checked',true); }
				});
			});
        });
    </script>

</body>
</html>
