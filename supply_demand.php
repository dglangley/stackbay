<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	
	

	//Get the company filter from the   	
	$company_filter = '';
	if ($_REQUEST['companyid'] && is_numeric($_REQUEST['companyid']) && $_REQUEST['companyid']>0) { 
		$company_filter = $_REQUEST['companyid']; 
	}
	//Report type is set to summary as a default. This is where the button functionality comes in to play
	$report_type = 'summary';
	if (isset($_REQUEST['report_type']) AND ($_REQUEST['report_type']=='summary' OR $_REQUEST['report_type']=='detail')) { $report_type = $_REQUEST['report_type']; }
	else if (isset($_COOKIE['report_type']) AND ($_COOKIE['report_type']=='summary' OR $_COOKIE['report_type']=='detail')) { $report_type = $_COOKIE['report_type']; }

	//This is saved as a cookie in order to cache the results of the button function within the same window
	setcookie('report_type',$report_type);
	
/*	$order = '';
	if ($_REQUEST['order']){
//		$report_type = 'detail';
		$order = $_REQUEST['order'];
	}
*/	
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
	
	$record_end = $today;
	if ($_REQUEST['END_DATE']){
		$record_end = format_date($_REQUEST['END_DATE'],'Y-m-d');
	}
	//Calculate the standard year range, output quarters as an array, and make 
	$quarter = array('01/01/','04/01/','07/01/','10/01/');
	$last_week = date('m/d/Y', strtotime('-1 week', strtotime($today)));
	$quarter_start = $quarter[floor(date('m')/3)];
	$quarter_start .=  date('Y');

	$record_start = format_date($last_week, 'Y-m-d');
	if ($_REQUEST['START_DATE']){
		$record_start = format_date($_REQUEST['START_DATE'], 'Y-m-d');
	}

?>


<!------------------------------------------------------------------------------------------->
<!-------------------------------------- HEADER OUTPUT -------------------------------------->
<!------------------------------------------------------------------------------------------->
<!DOCTYPE html>
<html>
<!-- Declaration of the standard head with S&D home set as title -->
<head>
	<title>VMM Supply and Demand</title>
	<?php
		//Standard headers included in the function
		include_once $rootdir.'/inc/scripts.php';
	?>
</head>

<body class="sub-nav accounts-body">
	
	<?php include 'inc/navbar.php'; ?>

	<!-- Wraps the entire page into a form for the sake of php trickery -->
	<form class="form-inline" method="get" action="/supply_demand.php">

    <table class="table table-header">
		<tr>
		<td class = "col-md-1">

		    <div class="btn-group">
		        <button class="glow left large btn-report<?php if ($report_type=='summary') { echo ' active'; } ?>" type="submit" data-value="summary">
		        <i class="fa fa-sort-numeric-desc"></i>	
		        </button>
				<input type="radio" name="report_type" value="summary" class="hidden"<?php if ($report_type=='summary') { echo ' checked'; } ?>>
		        <button class="glow right large btn-report<?php if ($report_type=='detail') { echo ' active'; } ?>" type="submit" data-value="detail">
		        	<i class="fa fa-history"></i>	
		        </button>
		        <input type="radio" name="report_type" value="detail" class="hidden"<?php if ($report_type=='detail') { echo ' checked'; } ?>>
		    </div>
		</td>

		<?php 
			//Calculate the standard year range, output quarters as an array, and make 
			$year = date('Y');
			$quarter = array('01/01/','03/01/','06/01/','09/01/');
			$today = date('m/d/Y');
			$quarter_start = $quarter[floor(date('m')/3)];
		?>
		<td class = "col-md-1">
				<div class="input-group date datetime-picker-filter">
		            <input type="text" name="START_DATE" class="form-control input-sm" value="
		                <?php if($startDate){echo $startDate;}else{echo $quarter_start.$year;}?>" style = "min-width:50px;"/>
		            <span class="input-group-addon">
		                <span class="fa fa-calendar"></span>
		            </span>
		        </div>
		</td>
		<td class = "col-md-1 ">
					<div class="input-group date datetime-picker-filter">
		            <input type="text" name="END_DATE" class="form-control input-sm" value="
		            <?php if($endDate){echo $endDate;}else{echo $today;}?>" style = "min-width:50px;"/>
		            <span class="input-group-addon">
		                <span class="fa fa-calendar"></span>
		            </span>
		    </div>
		</td>

		<td class="col-md-1 text-center">

			<input type="text" name="order" class="form-control input-sm" value ='<?php echo $order?>' placeholder = "Order #"/>
		</td>
		
		<td class="col-md-2 text-center">
			<input type="text" name="part" class="form-control input-sm" value ='<?php echo $part?>' placeholder = 'Part/HECI'/>
		</td>
		<td class="col-md-3">
			<div class="pull-right form-group">
			<select name="companyid" id="companyid" class="company-selector">
					<option value="">- Select a Company -</option>
				<?php 
				if ($company_filter) {echo '<option value="'.$company_filter.'" selected>'.(getCompany($company_filter)).'</option>'.chr(10);} 
				else {echo '<option value="">- Select a Company -</option>'.chr(10);} 
				?>
				</select>
				<input class="btn btn-primary btn-sm" type="submit" value="Apply">
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
                        <h2>
                        <?php echo ("Supply and Demand");
                        if($company_filter){ 
                        	echo ': ';
                        	echo getCompany($company_filter);
                        	} 
                        ?></h2>
                    </div>
                </div>

			<!-- If the summary button is pressed, inform the page and depress the button -->


<?php
	// format col widths based on content (company column, items detail, etc)
	// If there is a company declared, do not show the collumn for the company data. Set width by array
	if ($report_type == 'summary'){
		$widths = array(1,9,1,1);		
	}
	else{
		if ($company_filter) {
			$widths = array(2,6,1,2,1);
		} else {
			$widths = array(1,4,3,1,1,1,1);
		}
	}
	
	$c = 0;
?>
<!--================================================================================-->
<!--=============================   PRINT TABLE ROWS   =============================-->
<!--================================================================================-->
<?php
	//Establish a blank array for receiving the results from the table
	$results = array();
	$oldid = 0;
	$rows = '';
//	echo getCompany($company_filter,'id','oldid');
//	echo('Value Passed in: '.$company_filter);
	//If there is a company id, translate it to the old identifier
	if($company_filter != 0){$oldid = dbTranslate($company_filter, false);}

	//Write the query for the gathering of Pipe data
    $result = getRecords($part,$part_string);
    $rows = '';
    $summary_rows = array();
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

        foreach($unsorted as $row){
	            $rows .= '
	                <tr>
	                    <td>'.format_date($row['date'], 'M d, Y').'</td>
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
		$this_amt = $r['qty']*$r['price'];
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
		$row = array('datetime'=>$r['datetime'],'company_col'=>$company_col,'id'=>$r['id'],'detail'=>$descr,'qty_col'=>$qty_col,'price_col'=>$price_col,'amt'=>$this_amt,'status'=>'<span class="label label-success">Completed</span>');

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
                                        '.format_price($r['amt'],true,' ').'
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
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    <?php if($report_type == 'summary'){echo("Last Req ");} ?>
                                    Date
	                            </th>
		


<?php if (!$company_filter && $report_type == 'detail') {?>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Company
                                </th>
<?php } ?>

                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Items
                                </th>
<?php if ($report_type == 'summary') { ?>
								<th class="col-md-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    # Requests
                                </th>
<?php } ?>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    <?php if($report_type == 'summary'){echo ('Sum ');}?>
                                    Qty
                                </th>
<?php if ($report_type == 'detail') { ?>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Quote Price
                                </th>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Total Quote
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
