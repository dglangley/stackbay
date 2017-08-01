<?php

	//Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/calcQuarters.php';
	
//========================================================================================
//------------------------------- Filter Gathering Section -------------------------------
//========================================================================================
	$company_filter = '';
	if ($_REQUEST['companyid'] && is_numeric($_REQUEST['companyid']) && $_REQUEST['companyid']>0) { 
		$company_filter = $_REQUEST['companyid']; 
	}
	//Report type is set to summary as a default. This is where the button functionality comes in to play
	$report_type = 'detail';
	if (isset($_REQUEST['report_type']) AND ($_REQUEST['report_type']=='summary' OR $_REQUEST['report_type']=='detail')) { $report_type = $_REQUEST['report_type']; }
	else if (isset($_COOKIE['report_type']) AND ($_COOKIE['report_type']=='summary' OR $_COOKIE['report_type']=='detail')) { $report_type = $_COOKIE['report_type']; }
	
	//This is saved as a cookie in order to cache the results of the button function within the same window
	setcookie('report_type',$report_type);
	
	//Part is requested, converted with hecidb to a new system
	$part = '';
	$part_string = '';
	if ($_REQUEST['part']){
    	$part = $_REQUEST['part'];
    	$part_list = hecidb($part);
    	foreach ($part_list as $id => $array) {
    	    $part_string .= $id.',';
    	}
    	$part_string = rtrim($part_string, ",");
    }
	
	$min_price = '';
	$max_price = '';
	if ($_REQUEST['min']){
		$min_price = $_REQUEST['min'];
	}
	if ($_REQUEST['max']){
		$max_price = $_REQUEST['max'];
	}
	
	$endDate = format_date($today,'m/d/Y');
	if ($_REQUEST['END_DATE']){
		$endDate = format_date($_REQUEST['END_DATE'],'m/d/Y');
	}
	// for getRecords()
	$record_end = $endDate;

	//Calculate the standard year range, output quarters as an array, and make 
	$last_week = date('m/d/Y', strtotime('-1 week', strtotime($today)));

	$startDate = format_date($last_week, 'm/d/Y');
 	if ($_REQUEST['START_DATE']){
		$startDate = format_date($_REQUEST['START_DATE'], 'm/d/Y');
	}
	// for getRecords()
	$record_start = $startDate;
	
	//Stackbay Button
	$market_table = 'demand';//default
	if ($_REQUEST['market_table']){
		$market_table = $_REQUEST['market_table'];
	}
/*
	$year = date('Y');
	$m = date('m');
	$q = (ceil($m/3)*3)-2;
	if (strlen($q)==1) { $q = '0'.$q; }
	$quarter_start = $q.'/01/'.$year;
	if (! $startDate) { $startDate = $quarter_start; }

	//Calculate the standard year range, output quarters as an array, and make 
	//prepend a 0 if a single-digit month
	$current_date = date('m/d/Y');
*/

?>


<!----------------------------------------------------------------------------->
<!------------------------------- HEADER OUTPUT ------------------------------->
<!----------------------------------------------------------------------------->
<!DOCTYPE html>
<html>
<!-- Declaration of the standard head with S&D home set as title -->
<head>
	<title>Supply and Demand</title>
	<?php
		//Standard headers included in the function
		include_once $rootdir.'/inc/scripts.php';
	?>
</head>

<body class="sub-nav accounts-body">

<!-- Outputs the Navbar -->	
	<?php include 'inc/navbar.php'; ?>

	<!-- Wraps the entire page into a form to allow the php to get the filters -->
	<form class="form-inline" method="get" action="/supply_demand.php">

<!----------------------------------------------------------------------------->
<!--------------------------- Output the filter bar --------------------------->
<!----------------------------------------------------------------------------->
    <table class="table table-header">
		<tr id = "filterTableOutput">
			<td class = "col-sm-2">
	
			    <div class="btn-group">
			        <button class="glow left large btn-radio <?php if ($report_type=='summary') { echo ' active'; } ?>" type="submit" data-value="summary" data-toggle="tooltip" data-placement="bottom" title="most requested">
			        	<i class="fa fa-sort-numeric-desc"></i>	
			        </button>
					<input type="radio" name="report_type" value="summary" class="hidden"<?php if ($report_type=='summary') { echo ' checked'; } ?>>
			        <button class="glow right large btn-radio<?php if ($report_type=='detail') { echo ' active'; } ?>" type="submit" data-value="detail" data-toggle="tooltip" data-placement="bottom" title="most recent">
			        	<i class="fa fa-history"></i>	
			        </button>
			        <input type="radio" name="report_type" value="detail" class="hidden"<?php if ($report_type=='detail') { echo ' checked'; } ?>>
			    </div>
				<div class="btn-group">
			        <button class="glow left large btn-radio <?php if ($market_table=='supply') { echo ' active'; } ?>" type="submit" data-value="supply">
			        	Supply	
			        </button>
					<input type="radio" name="market_table" value="supply" class="hidden"<?php if ($market_table=='supply') { echo ' checked'; } ?>>
			        <button class="glow right large btn-radio<?php if ($market_table=='demand') { echo ' active'; } ?>" type="submit" data-value="demand">
			        	Demand
			        </button>
			        <input type="radio" name="market_table" value="demand" class="hidden"<?php if ($market_table=='demand') { echo ' checked'; } ?>>
			    </div>
			</td>
			<td class="col-sm-3">
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
					<div class="btn-group" id="shortDateRanges">
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
			</td>
			<td class = "col-sm-2 text-center">
            	<h2 class="minimal"><?php echo ucfirst($market_table); if($company_filter){ echo ': '; echo getCompany($company_filter); } ?></h2>
			</td>
			<td class = "col-sm-2 text-center">
<!--
				<input type="text" name="part" class="form-control input-sm" value ='<?php echo $part?>' placeholder = 'Part/HECI'/>
-->
				<div class="input-group">
					<input type="text" name="min" class="form-control input-sm" value ='<?php if($min_price > 0){echo format_price($min_price);}?>' placeholder = 'Min $'/>
					<span class="input-group-addon">-</span>
					<input type="text" name="max" class="form-control input-sm" value ='<?php echo format_price($max_price);?>' placeholder = 'Max $'/>
				</div>
			</td>
			<td class = "col-sm-3">
				<div class="pull-right form-inline">
					<div class="input-group">
						<select name="companyid" id="companyid" class="company-selector">
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
				</div>
			</td>
		</tr>
	</table>


<!----------------------------------------------------------------------------->
<!----------------------------- Begin Body Output ----------------------------->
<!----------------------------------------------------------------------------->

	
    <div id="pad-wrapper">
		<div class="row filter-block">

            <!-- orders table -->
            <div class="table-wrapper">

<?php
	// format col widths based on content (company column, items detail, etc)
	// If there is a company declared, do not show the collumn for the company data. Set width by array
	if ($report_type == 'summary'){
		$widths = array(1,9,1,1);		
	}
	else{
		if ($company_filter) {
			$widths = array(2,5,1,2,1,1);
		} else {
			$widths = array(1,3,3,1,1,1,1,1);
		}
	}
	
	$c = 0;
?>

<!----------------------------------------------------------------------------->
<!------------------------------- PRINT TABLE ROWS ---------------------------->
<!----------------------------------------------------------------------------->
<?php
	//Establish a blank array for receiving the results from the table
	$results = array();
	$oldid = 0;
	$rows = '';


	//If there is a company id, translate it to the old identifier
	if($company_filter != 0){$oldid = dbTranslate($company_filter, false);}

	//Write the query for the gathering of Pipe data
    $result = getRecords($part,$part_string,'csv',$market_table);
    $rows = '';
    $summary_rows = array();
    $unsorted = array();
    
    //Summary row contains four rows: Last Req Date, Items, # REQUESTS, SUM QTY
    if($report_type == 'summary'){
        foreach ($result as $row){
            $part = $row['partid'];
            
            if(!$part){continue;}
            if(!array_key_exists($part, $summary_rows)){
                $summary_rows[$part] = array(
                    'last_date' => '',
                    'qty' => '',
                    'total' => ''
                    );
            }

        	if(!($summary_rows[$part]['last_date'])){
        		$summary_rows[$part]['last_date'] = $row['datetime'];
        	}
            $summary_rows[$part]['qty'] += $row['qty'];
            $summary_rows[$part]['total']++;
		}

        foreach($summary_rows as $part => $info){
				$descr = (getPart($part,'part').' &nbsp; '.getPart($part,'heci'));
	            $last_date = $info['last_date'];
	            $summed_qtys = $info['qty'];
	            $times_requested = $info['total'];

			$unsorted[] = array(
				'part' => $descr,
				'date' => $last_date,
				'qty'  => $summed_qtys,
				'rqs' => $times_requested
				);
        }
		
		function cmp($a, $b) {
		    if ($a['rqs'] == $b['rqs']) {
		        return 0;
		    }
		    return ($a['rqs'] < $b['rqs']) ? 1 : -1;
		}
		uasort($unsorted,'cmp');
		
        foreach($unsorted as $row){
	            $rows .= '
	                <tr>
	                    <td>'.format_date($row['date'], 'M j, Y').'</td>
	                    <td>'.$row['part'].'</td>
	                    <td>'.$row['rqs'].'</td>
	                    <td>'.$row['qty'].'</td>
	                </tr>
	            ';
	        }    
	}
	else{ 
	    foreach ($result as $r){
			//Set the amount to zero for the number of items and the total price
			$amt = 0;
			$num_items = 0;
			
			//Set the value of the company to the individual row if there is no company ID preset
			if (! $company_filter) {
				$company_col = '
	                                <td>
		                                    <a href="#">'.$r['name'].'</a>
	                                </td>
				';
			}
			$price = trim($r['price'],"$");
			$this_amt = format_price($price,false,'',true) * $r['qty'];
			$amt += $this_amt;
			$num_items += $r['qty'];
	
			$qty_col = '
	                            <td>
	                                '.$r['qty'].'
	                            </td>
			';
			$price_col = '
	                            <td class="text-right">
	                                '.format_price($r['price']).'
	                            </td>
			';
	
			$descr = getPart($r['partid'],'part').' &nbsp; '.getPart($r['partid'],'heci');
			$row = array(
				'datetime'=>$r['datetime'],
				'company_col'=>$company_col,
				/*'id'=>$r['id'],*/
				'detail'=>$descr,
				'userid'=>$r['userid'],
				'qty_col'=>$qty_col,
				'price_col'=>$price_col,
				'amt'=>$this_amt,
				'status'=>'<span class="label label-success">Completed</span>'
			);
	
			$results[] = $row;
		}
    	foreach ($results as $r) {
			$rows .= '
                                <!-- row -->
                                <tr>
                                    <td>
                                        '.format_date($r['datetime'],'M j, Y').'
                                    </td>
    								'.$r['company_col'].'
                                    <td>
                                        '.$r['detail'].'
                                    </td>
    						 		'.$r['qty_col'].'
    								'.$r['price_col'].'
                                    <td class="text-right">
			';
			if ($r['amt']) {
				$rows .= format_price($r['amt']);
			}
			$rows .='
                                    </td>
                                    <td class="text-center">
                                        '.getRep($r['userid']).'
                                    </td>
                                </tr>
    		';
		}
	}


?>

	<!-- Declare the class/rows dynamically by the type of information requested (could be transitioned to jQuery) -->
            <div class="row">
                    <table class="table table-hover table-striped table-condensed">
                        <thead>
                            <tr>
                                <th class="col-sm-<?php echo $widths[$c++]; ?>">
                                    <?php if($report_type == 'summary'){echo("Last Req ");} ?>
                                    Date
	                            </th>
		


<?php if (!$company_filter && $report_type == 'detail') {?>
                                <th class="col-sm-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Company
                                </th>
<?php } ?>

                                <th class="col-sm-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Items
                                </th>
<?php if ($report_type == 'summary') { ?>
								<th class="col-sm-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    # Requests
                                </th>
<?php } ?>
                                <th class="col-sm-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    <?php if($report_type == 'summary'){echo ('Sum ');}?>
                                    Qty
                                </th>
<?php if ($report_type == 'detail') { ?>
                                <th class="col-sm-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Quote Price
                                </th>
                                <th class="col-sm-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Total Quote
									<br>
                                </th>
                                <th class="col-sm-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Sales Rep
									<br>
                                </th>
<?php } ?>


                            </tr>
                        </thead>
                        <tbody>
                        	<?php echo($rows); ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- end orders table -->


	</div>
	</div>
	</form>
<?php include_once 'inc/footer.php'; ?>
<script type="text/javascript">
			
/*
	$(document).ready(function() {
		//Change the value of the     	

		$('.btn-report').click(function() {
			var btnValue = $(this).data('value');
			$(this).closest("div").find("input[type=radio]").each(function() {
				if ($(this).val()==btnValue) { $(this).attr('checked',true); }
			});
		});
	});
*/

</script>
</body>
</html>
