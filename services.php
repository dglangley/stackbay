<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/svcs_pipe.php';

	$keyword = '';
	if ((isset($_REQUEST['s']) AND $_REQUEST['s']) OR (isset($_REQUEST['keyword']) AND $_REQUEST['keyword'])) {
		if (isset($_REQUEST['s']) AND $_REQUEST['s']) { $keyword = $_REQUEST['s']; }
		else if (isset($_REQUEST['keyword']) AND $_REQUEST['keyword']) { $keyword = $_REQUEST['keyword']; }
		$keyword = trim($keyword);

		$query = "SELECT id FROM services_job WHERE job_no = '".res($keyword)."'; ";
		$result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query);
		if (mysqli_num_rows($result)==1) {
			header('Location: job.php?s='.$keyword);
			exit;
		}
		$query = "SELECT id FROM services_job WHERE (job_no RLIKE '".res($keyword)."' OR site_access_info_address RLIKE '".res($keyword)."'); ";
		$result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query);
		if (mysqli_num_rows($result)==1) {
			$r = mysqli_fetch_assoc($result);

			header('Location: job.php?id='.$r['id']);
			exit;
		}
		// in order for user to be able to 'reset' view to default services home, we want to reset search string
		// so that if they at first were switching between modes (say, sales to services) with a sales-related
		// search string, the subsequent click would show all services instead of the bogus search string
		$_REQUEST['s'] = '';
	}

	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/getPipeIds.php';
	include_once $rootdir.'/inc/calcQuarters.php';

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

	// if no start date passed in, or invalid, set to beginning of previous month
	if (! $startDate) { $startDate = format_date($today,'m/01/Y',array('m'=>-1)); }

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
<!-- Declaration of the standard head with Services home set as title -->
<head>
	<title>Services Home</title>
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
	<form class="form-inline" method="get" action="/services.php">

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
<?php
	$quarters = calcQuarters();
	foreach ($quarters as $qnum => $q) {
		echo '
				    			<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="'.$q['start'].'" data-end="'.$q['end'].'">Q'.$qnum.'</button>
		';
	}
?>
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
					<button class="btn btn-primary btn-sm" type="submit" ><i class="fa fa-filter" aria-hidden="true"></i></button>
			</div><!-- form-group -->
		</td>
		<td class="col-md-2 text-center">
            <h2 class="minimal">Services Jobs</h2>
		</td>
		
		<td class="col-md-2 text-center">
			<div class="input-group">
				<input type="text" name="keyword" class="form-control input-sm upper-case auto-select" value ='<?php echo $keyword?>' placeholder = "Search..." autofocus />
				<span class="input-group-btn">
					<button class="btn btn-primary btn-sm" type="submit" ><i class="fa fa-filter" aria-hidden="true"></i></button>
				</span>
			</div>
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
            <!-- jobs table -->
            <div class="table-wrapper">

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
	
	//Write the query for the gathering of Pipe data
	$query = "SELECT s.*, u.fullname ";
	$query .= "FROM services_job s, services_userprofile u WHERE entered_by_id = u.id ";
   	if ($keyword) {
		$query .= "AND (s.job_no RLIKE '".$keyword."' OR site_access_info_address RLIKE '".$keyword."') ";
	} else if ($startDate) {
   		$dbStartDate = format_date($startDate, 'Y-m-d');
   		$dbEndDate = format_date($endDate, 'Y-m-d');
   		//$dbStartDate = date("Y-j-m", strtotime($startDate));
   		//$dbEndDate = date("Y-j-m", strtotime($endDate));
   		$query .= "AND date_entered between CAST('".$dbStartDate."' AS DATE) and CAST('".$dbEndDate."' AS DATE) ";
	}
	$query .= "ORDER BY date_entered DESC; ";
	
##### UNCOMMENT IF THE DATA IS BEING PULLED FROM THE NEW DATABASE INSTEAD OF THE PIPE
	//$query = "SELECT * FROM sales_orders ";
	//if ($company_filter) { $query .= "WHERE companyid = '".$company_filter."' "; }
	//$query .= "ORDER BY datetime DESC, id DESC; ";
#####

//Search for the results. Leave the second parameter null if the pipe is not being used

	$result = qdb($query,'SVCS_PIPE');

	//The aggregation method of form processing. Take in the information, keyed on primary sort field,
	//will prepare the results rows to make sorting and grouping easier without having to change the results
	$summary_rows = array();

	foreach ($result as $r) {
		$assigns = array();
		$assignments = '';
		$query2 = "SELECT fullname, assigned_to_id FROM services_jobtasks jt, services_userprofile up ";
		$query2 .= "WHERE job_id = '".$r['id']."' AND assigned_to_id = up.id; ";
		$result2 = qdb($query2,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			if (! isset($assignments[$r2['assigned_to_id']])) {
				$assigns[$r2['assigned_to_id']] = true;
				$assignments .= $r2['fullname'].'<BR/>';
			}
		}

		$rows .= '
                            <!-- row -->
                            <tr>
                                <td>
                                    '.format_date($r['date_entered'],'M j, Y').'
                                </td>
                                <td>
                                    <a href="job.php?id='.$r['id'].'">'.$r['job_no'].'</a>
                                </td>
                                <td>
	                                <a href="#">'.$r['customer'].'</a><br/>
									<span class="info">'.str_replace(chr(10),'<br/>',trim($r['site_access_info_address'])).'</span>
                                </td>
                                <td>
									'.$r['site_access_info_contact'].'
                                </td>
                                <td>
                                    '.$r['customer_job_no'].'
                                </td>
                                <td>
                                    '.$r['purchase_order'].'
                                </td>
                                <td>
                                    '.format_date($r['scheduled_date_of_work'],'D, M j, Y').'<br/>
									to<br/>
                                    '.format_date($r['scheduled_completion_date'],'D, M j, Y').'
                                </td>
                                <td>
                                    '.$r['fullname'].'
                                </td>
                                <td>
                                    '.$assignments.'
                                </td>
                                <td class="text-center">
                                    <span class="label label-success">Status</span>
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
                                    Date 
                                </th>
                                <th class="col-md-1">
                                    <span class="line"></span>
                                    Job#
                                </th>
                                <th class="col-md-2">
                                    <span class="line"></span>
                                    Company
                                </th>
                                <th class="col-md-1">
                                    <span class="line"></span>
                                    Contact
                                </th>
                                <th class="col-md-1">
                                    <span class="line"></span>
                                    Project ID#
                                </th>
                                <th class="col-md-1">
                                    <span class="line"></span>
                                    PO#
                                </th>
                                <th class="col-md-1">
                                    <span class="line"></span>
                                    Start / End
                                </th>
                                <th class="col-md-1">
                                    <span class="line"></span>
									Manager
                                </th>
                                <th class="col-md-2">
                                    <span class="line"></span>
                                    Assignments
                                </th>
                                <th class="col-md-1 text-center">
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
