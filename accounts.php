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
	//Report type is set to summary as a default. This is where the button functionality comes in to play
	$report_type = 'summary';
	if (isset($_REQUEST['report_type']) AND ($_REQUEST['report_type']=='summary' OR $_REQUEST['report_type']=='detail')) { $report_type = $_REQUEST['report_type']; }
	else if (isset($_COOKIE['report_type']) AND ($_COOKIE['report_type']=='summary' OR $_COOKIE['report_type']=='detail')) { $report_type = $_COOKIE['report_type']; }
	
	//This is saved as a cookie in order to cache the results of the button function within the same window
	setcookie('report_type',$report_type);
	
	$order = '';
	if (isset($_REQUEST['order'])){
		$report_type = 'detail';
		$order = $_REQUEST['order'];
	}
	
	$part = '';
	$part_string = '';
	if (isset($_REQUEST['part'])){
    	$part = $_REQUEST['part'];
    	echo($part);
    	$part_list = getPipeIds($part);
    	foreach ($part_list as $id => $array) {
    	    $part_string .= $id.',';
    	}
    	$part_string = rtrim($part_string, ",");
    	echo(' | '.$part_string); 	
    }
	
	$endDate = '';
	if (isset($_REQUEST['END_DATE'])){
		$endDate = $_REQUEST['END_DATE'];
	}
	
	$startDate = '';
	if (isset($_REQUEST['START_DATE'])){
		$startDate = $_REQUEST['START_DATE'];
	}
	
?>

<!------------------------------------------------------------------------------------------->
<!-------------------------------------- HEADER OUTPUT -------------------------------------->
<!------------------------------------------------------------------------------------------->
<!DOCTYPE html>
<html>
<!-- Declaration of the standard head with Accounts home set as title -->
<head>
	<title>VMM Accounts Home</title>
	<?php
		//Standard headers included in the function
		include_once $rootdir.'/inc/scripts.php';
	?>
</head>

<body class="sub-nav accounts-body">
	
	<?php include 'inc/navbar.php'; ?>

	<!-- Wraps the entire page into a form for the sake of php trickery -->
	<form class="form-inline" method="get" action="/accounts.php">

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
		<?php 
			//Calculate the standard year range, output quarters as an array, and make 
			$year = date('Y');
			$quarter = array('01/01/','03/01/','06/01/','09/01/');
			$today = date('m/d/Y');
			$quarter_start = $quarter[floor(date('m')/3)];
		?>
		<div class = "col-md-1">
			Dates between
				<div class="input-group date datetime-picker-filter">
		            <input type="text" name="START_DATE" class="form-control input-sm" value="
		                <?php if($startDate){echo $startDate;}else{echo $quarter_start.$year;}?>" />
		            <span class="input-group-addon">
		                <span class="fa fa-calendar"></span>
		            </span>
		        </div>
			</div>
		<div class = "col-md-1 ">
			and
					<div class="input-group date datetime-picker-filter">
		            <input type="text" name="END_DATE" class="form-control input-sm" value="
		            <?php if($endDate){echo $endDate;}else{echo $today;}?>" />
		            <span class="input-group-addon">
		                <span class="fa fa-calendar"></span>
		            </span>
		    </div>
		</div>

		<div class="col-md-1 text-center">
			Order #
			<input type="text" name="order" class="form-control input-sm" value ='<?php echo $order?>'/>
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
                        <?php echo ('Accounts');
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
	if ($company_filter) {
		if ($report_type=='summary') {
			$widths = array(3,3,2,2,2);
		} else {
			$widths = array(2,2,4,1,1,1,1);
		}
	} else {
		if ($report_type=='summary') {
			$widths = array(2,4,2,1,2,1);
		} else {
			$widths = array(1,3,1,3,1,1,1,1);
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
//	echo getCompany($company_filter,'id','oldid');
//	echo('Value Passed in: '.$company_filter);
	//If there is a company id, translate it to the old identifier
	if($company_filter != 0){$oldid = dbTranslate($company_filter, false);}
//	echo '<br>The value of this company in the old database is: '.$oldid;
	
	//Write the query for the gathering of Pipe data
	$query = "SELECT ";
    $query .= "s.so_date 'datetime', c.`id` 'companyid', c.name 'company_name', q.company_id 'company', ";
    $query .= "q.quantity 'qty', i.clei 'heci', q.inventory_id, i.part_number 'part', q.quote_id 'id', q.price price ";
    $query .= "From inventory_inventory i, inventory_salesorder s, inventory_outgoing_quote q, inventory_company c ";
    $query .= "WHERE q.inventory_id = i.`id` AND q.quote_id = s.quote_ptr_id AND c.id = q.company_id ";
   	if ($company_filter) { $query .= "AND q.company_id = '".$oldid."' "; }
   	if ($startDate) {
   		$dbStartDate = format_date($startDate, 'Y-m-d');
   		$dbEndDate = format_date($endDate, 'Y-m-d');
   		//$dbStartDate = date("Y-j-m", strtotime($startDate));
   		//$dbEndDate = date("Y-j-m", strtotime($endDate));
   		$query .= "AND s.so_date between CAST('".$dbStartDate."' AS DATE) and CAST('".$dbEndDate."' AS DATE) ";}
   	if ($order){ $query .= "AND q.quote_id = '".$order."' ";}
   	if ($part_string){ $query .= "AND i.id IN (".$part_string.") ";}
   	//if ($endDate) { $query .= "AND s.so_date <'".$endDate."'  ";}
    $query .= "Order By s.so_date DESC;";
	echo $query; exit;

##### UNCOMMENT IF THE DATA IS BEING PULLED FROM THE NEW DATABASE INSTEAD OF THE PIPE
	//$query = "SELECT * FROM sales_orders ";
	//if ($company_filter) { $query .= "WHERE companyid = '".$company_filter."' "; }
	//$query .= "ORDER BY datetime DESC, id DESC; ";
#####

//Search for the results. Leave the second parameter null if the pipe is not being used

	$result = qdb($query,'PIPE');
	
	foreach ($result as $r){

		//Set the amount to zero for the number of items and the total price
		$amt = 0;
		$num_items = 0;
		
		//Set the value of the company to the individual row if there is no company ID preset
		if (! $company_filter) {
			$company_col = '
                                <td>
	                                    <a href="#">'.$r['company_name'].'</a>
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

			if ($report_type=='detail') {
				$descr = $r['part'].' &nbsp; '.$r['heci'];
				$row = array('datetime'=>$r['datetime'],'company_col'=>$company_col,'id'=>$r['id'],'detail'=>$descr,'qty_col'=>$qty_col,'price_col'=>$price_col,'amt'=>$this_amt,'status'=>'<span class="label label-success">Completed</span>');
			}


		if ($report_type=='summary') {
			$row = array('datetime'=>$r['datetime'],'company_col'=>$company_col,'id'=>$r['id'],'detail'=>$num_items,'amt'=>$amt,'status'=>'<span class="label label-success">Completed</span>');
		}

		$results[] = $row;
	}

	$rows = '';
	foreach ($results as $r) {
		$rows .= '
                            <!-- row -->
                            <tr>
                                <td>
                                    '.format_date($r['datetime'],'M j, Y').'
                                </td>
								'.$r['company_col'].'
                                <td>
                                    <a href="#">'.$r['id'].'</a>
                                </td>
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
?>


	<!-- Declare the class/rows dynamically by the type of information requested (could be transitioned to jQuery) -->
                <div class="row">
                    <table class="table table-hover table-striped table-condensed">
                        <thead>
                            <tr>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    Date 
                            		<?php
                            			if($startDate and $endDate){
                            				echo ('('.$startDate.' - '.$endDate.')');
                            				echo '<i class="fa fa-times" style="color:red" aria-hidden="true" id = "date_filter"></i>';
                            			}
                            		?>
                            			
                                    <br>

                                </th>
<?php if (! $company_filter) { ?>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Company
                                </th>
<?php } ?>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Order#
                                </th>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Items
                                </th>
<?php if ($report_type=='detail') { ?>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Qty
                                </th>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Price (ea)
                                </th>
<?php } ?>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Total Amount
									<br>
                                </th>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                        	<?php echo $rows; ?>
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
            $('#accounts').dataTable({
                "sPaginationType": "full_numbers"
            });
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
