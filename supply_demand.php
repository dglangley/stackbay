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
	
	
  	
	//Company Id is grabbed from the search field at the top, but only if one has been passed in
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

	$record_start = format_date($quarter_start, 'Y-m-d');
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
			<td class="col-md-2">
                <input type="text" class="search order-search" id="accounts-search" placeholder="Order#, Part#, HECI..." autofocus />
			</td>
			<td class="text-center col-md-6">
			</td>
			<td class="col-md-3">
				<div class="pull-right form-group">
					<select name="companyid" id="companyid" class="company-selector">
						<option value="">- Select a Company -</option>
					<?php if ($company_filter) { echo '<option value="'.$company_filter.'" selected>'.
					($company_filter).'</option>'.chr(10); } else { echo '<option value="">- Select a Company -</option>'.chr(10); } ?>
					</select>
					<input class="btn btn-primary btn-sm" type="submit" value="Go">
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
		<div class="col-md-2">
			Detail Level <br>
		    <div class="btn-group">
		        <button class="glow left large btn-report<?php if ($report_type=='summary') { echo ' active'; } ?>" type="submit" data-value="summary">Summary</button>
				<input type="radio" name="report_type" value="summary" class="hidden"<?php if ($report_type=='summary') { echo ' checked'; } ?>>
		        <button class="glow right large btn-report<?php if ($report_type=='detail') { echo ' active'; } ?>" type="submit" data-value="detail">Detail</button>
				<input type="radio" name="report_type" value="detail" class="hidden"<?php if ($report_type=='detail') { echo ' checked'; } ?>>
		    </div>
		</div>

		<div class = "col-md-1">
			Dates between
				<div class="input-group date datetime-picker-filter">
		            <input type="text" name="START_DATE" class="form-control input-sm" value="
		                <?php echo format_date($record_start, 'm/d/Y');?>" />
		            <span class="input-group-addon">
		                <span class="fa fa-calendar"></span>
		            </span>
		        </div>
			</div>
		<div class = "col-md-1 ">
			and
					<div class="input-group date datetime-picker-filter">
		            <input type="text" name="END_DATE" class="form-control input-sm" value="
		            <?php echo format_date($record_end, 'm/d/Y');?>
		            " />
		            <span class="input-group-addon">
		                <span class="fa fa-calendar"></span>
		            </span>
		    </div>
		</div>

	
		<div class="col-md-2 text-center">
			Part/Heci
			<input type="text" name="part" class="form-control input-sm" value ='<?php echo $part?>'/>
		</div>
		<div class="col-md-5 text-right">
		    <div class="btn-group pull-right">
		        <button class="glow left large active">All</button>
		        <button class="glow middle large">Pending</button>
		        <button class="glow right large">Completed</button>
		    </div>
		</div>
	    </div>
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
		$widths = array(1,3,5,1,1,1);		
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
//	echo '<br>The value of this company in the old database is: '.$oldid;
	//Write the query for the gathering of Pipe data
	
    $result = getRecords($part,$part_string);
    $rows = '';
	if (empty($result)){echo "Please enter valid search parameters";}
    $summary_rows = array();
    if($report_type == 'summary'){
        foreach ($result as $row){
            $part = $row['partid'];
            if(!array_key_exists($part, $summary_rows)){
                $summary_rows[$part] = array(
                    'last_date' => '',
                    'qty' => '',
                    'total' => ''
                    );
            }
            $summary_rows[$part]['last_date'] = $row['datetime'];
            $summary_rows[$part]['qty'] += $row['qty'];
            $summary_rows[$part]['total']++;
        }

        foreach($summary_rows as $part => $info){
            $descr = (getPart($part,'part').' &nbsp; '.getPart($part,'heci'));
            $last_date = $info['last_date'];
            $qty = $info['qty'];
            $total_requested = $info['total'];
            $rows .= '
                <tr>
                    <td>'.$last_date.'</td>
                    <td>'.$descr.'</td>
                    <td>'.$total_requested.'</td>
                    <td>'.$qty.'</td>
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
                                    <td class="text-right">
    									'.$r['status'].'
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
                                    Date 

                            		<?php
                            			if($report_type == 'summary'){echo("Last Ordered ");}
                            			if($record_start and $record_end){
                            				echo ('<br>('.$record_start.' - '.$record_end.')');
                            				echo '<i class="fa fa-times" style="color:red" aria-hidden="true" id = "date_filter"></i>';
                            			}
                            		?>

                                    <br>

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
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Qty
                                    <?php if($report_type == 'summary'){echo ('Ordered');}?>
                                </th>
<?php if ($report_type == 'detail') { ?>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Price (ea)
                                </th>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Total Amount
									<br>
                                </th>
<?php } ?>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Status
                                </th>
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
