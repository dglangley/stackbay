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
		<td class = "col-md-2">

		    <div class="btn-group">
		        <button class="glow left large btn-report<?php if ($report_type=='summary') { echo ' active'; } ?>" type="submit" data-value="summary">
		        <i class="fa fa-ticket"></i>	
		        </button>
				<input type="radio" name="report_type" value="summary" class="hidden"<?php if ($report_type=='summary') { echo ' checked'; } ?>>
		        <button class="glow right large btn-report<?php if ($report_type=='detail') { echo ' active'; } ?>" type="submit" data-value="detail">
		        	<i class="fa fa-list"></i>	
	        	</button>
				<input type="radio" name="report_type" value="detail" class="hidden"<?php if ($report_type=='detail') { echo ' checked'; } ?>>
		    </div>
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
								<button class="btn btn-sm btn-default right small btn-report" type="button" data-start="<?php echo date("01/01/Y"); ?>" data-end="<?php echo date("12/31/Y"); ?>">YTD</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</td>
		<td class="col-md-2 text-center">

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
		$footer_span2 = 1;
		if ($report_type=='summary') {
			$footer_span1 = 2;
			$widths = array(3,3,2,2);
		} else {
			$footer_span1 = 3;
			$widths = array(2,2,4,1,1,1);
		}
	} else {
		$footer_span2 = 2;
		if ($report_type=='summary') {
			$footer_span1 = 3;
			$widths = array(1,4,4,2,1);
		} else {
			$footer_span1 = 4;
			$widths = array(1,4,1,3,1,1,1);
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

	$rows = '';
	$total_pcs = 0;
	$total_amt = 0;
	
	//Write the query for the gathering of Pipe data
	$query = "SELECT ";
    $query .= "s.so_date 'datetime', c.`id` 'companyid', c.name 'company_name', q.company_id 'company', ";
    $query .= "q.quantity 'qty', i.clei 'heci', q.inventory_id, i.part_number 'part', q.quote_id 'id', q.price price ";
    $query .= "From inventory_inventory i, inventory_salesorder s, inventory_outgoing_quote q, inventory_company c ";
    $query .= "WHERE q.inventory_id = i.`id` AND q.quote_id = s.quote_ptr_id AND c.id = q.company_id AND q.quantity > 0 ";
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
	
##### UNCOMMENT IF THE DATA IS BEING PULLED FROM THE NEW DATABASE INSTEAD OF THE PIPE
	//$query = "SELECT * FROM sales_orders ";
	//if ($company_filter) { $query .= "WHERE companyid = '".$company_filter."' "; }
	//$query .= "ORDER BY datetime DESC, id DESC; ";
#####

//Search for the results. Leave the second parameter null if the pipe is not being used

	$result = qdb($query,'PIPE');
	
	//The aggregation method of form processing. Take in the information, keyed on primary sort field,
	//will prepare the results rows to make sorting and grouping easier without having to change the results
	$summary_rows = array();
	if($report_type == 'summary'){

	    foreach ($result as $row){
            $id = $row['id'];

			$ext_amt = $row['price']*$row['qty'];
			$total_pcs += $row['qty'];
			$total_amt += $ext_amt;

            if(!array_key_exists($id, $summary_rows)){
                $summary_rows[$id] = array(
                    'date' => '',
                    'company' => '',
                    'items' => '',
                    'summed' => ''
                    );
            }
			$summary_rows[$id]['date'] = $row['datetime'];
            $summary_rows[$id]['items'] += $row['qty'];
            $summary_rows[$id]['summed'] += $ext_amt;
            $summary_rows[$id]['company'] = $row['company_name'];
        }
        foreach ($summary_rows as $id => $info) {

        	$rows .= '
                <tr>
                    <td>'.format_date($info['date'], 'M j, Y').'</td>';
                    if(!$company_filter){$rows .= '<td>'.$info['company'].'</td>';}
            $rows .='
            		<td>'.$id.'</td>
                    <td>'.$info['items'].'</td>
                    <td class="text-right">'.format_price($info['summed']).'</td>
                </tr>
            ';
        }
	}

if ($report_type=='detail') {
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

		$total_pcs += $r['qty'];
		$total_amt += $amt;

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

			$descr = $r['part'].' &nbsp; '.$r['heci'];
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
                                    <?php if($report_type == 'summary'){
                                    	echo ('Pieces');
                                    }
                                    else{
                                    	echo ('Items');
                                    }
                                    ?>
                                </th>
<?php if ($report_type=='detail') { ?>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Qty
                                </th>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Price ( ea)
                                </th>
<?php } ?>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Total Amount
									<br>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                        	<?php echo $rows; ?>
							<?php if ($rows) { ?>
                            <!-- row -->
                            <tr class="warning nohover">
                                <td colspan="<?php echo $footer_span1; ?>"> </td>
                                <td><strong><?php echo $total_pcs; ?></td></strong>
                                <td class="text-right" colspan="<?php echo $footer_span2; ?>">
                                    <strong><?php echo format_price($total_amt,true,' '); ?></strong>
                                </td>
                            </tr>
							<?php } ?>
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
