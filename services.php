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
	include_once $rootdir.'/inc/strip_space.php';
	include_once $rootdir.'/inc/getProfile.php';
	$WORKDAY_START = 19;//workday end is set automatically in getTimesheet.php based on this
	include_once $rootdir.'/inc/getTimesheet.php';
	include_once $rootdir.'/inc/getExpenses.php';
	include_once $rootdir.'/inc/getMaterials.php';
	include_once $rootdir.'/inc/getOutsideServices.php';

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
	$startDate = '';
	if (isset($_REQUEST['START_DATE']) AND $_REQUEST['START_DATE']) {
		$startDate = format_date($_REQUEST['START_DATE'], 'm/d/Y');
	}
	if (! $startDate) { $startDate = format_date($today,'m/01/Y',array('m'=>-1)); }

	$endDate = date('m/d/Y');
	if (isset($_REQUEST['END_DATE']) AND $_REQUEST['END_DATE']){
		$endDate = format_date($_REQUEST['END_DATE'], 'm/d/Y');
	}

	if (! isset($_REQUEST['status']) OR ! $_REQUEST['status']) { $status = 'open'; }
	else { $status = $_REQUEST['status']; }

	$financials = false;
	if (isset($_REQUEST['financials']) AND $_REQUEST['financials']) { $financials = true; }

	$manager = '';
	if (isset($_REQUEST['manager']) AND $_REQUEST['manager'] AND $_REQUEST['manager']<>'All') {
		$manager = $_REQUEST['manager'];
	} else if ($U['name']=='David Oden' OR $U['name']=='Robert Cumer') {
		$manager = $U['name'];
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
			<div class="btn-group medium">
				<input type="hidden" name="status" id="status" value="<?php echo $status; ?>">
				<button class="btn btn-warning btn-sm btn-status left<?php if (! $status OR $status=='open') { echo ' active'; } ?>" type="button" data-status="open" data-toggle="tooltip" data-placement="right" title="Open/Idle"><i class="fa fa-folder-open"></i></button>
				<button class="btn btn-primary btn-sm btn-status middle<?php if ($status=='complete') { echo ' active'; } ?>" type="button" data-status="complete" data-toggle="tooltip" data-placement="bottom" title="Tech Completed"><i class="fa fa-folder"></i></button>
				<button class="btn btn-success btn-sm btn-status middle<?php if ($status=='closed') { echo ' active'; } ?>" type="button" data-status="closed" data-toggle="tooltip" data-placement="bottom" title="Closed"><i class="fa fa-check-square"></i></button>
				<button class="btn btn-info btn-sm btn-status right<?php if ($status=='all') { echo ' active'; } ?>" type="button" data-status="all" data-toggle="tooltip" data-placement="bottom" title="All"><i class="fa fa-square"></i></button>
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
					<button class="btn btn-primary btn-sm" type="submit" ><i class="fa fa-filter" aria-hidden="true"></i></button>
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
            <h2 class="minimal">Services Jobs</h2>
		</td>
		
		<td class="col-md-2 text-center">
			<div class="row">
				<div class="col-sm-7">
					<div class="input-group">
						<input type="text" name="keyword" class="form-control input-sm upper-case auto-select" value ='<?php echo $keyword?>' placeholder = "Search..." autofocus />
						<span class="input-group-btn">
							<button class="btn btn-primary btn-sm" type="submit" ><i class="fa fa-filter" aria-hidden="true"></i></button>
						</span>
					</div>
				</div>
				<div class="col-sm-5">
					<input type="checkbox" name="financials" id="financials" value="1"<?php if ($financials) { echo ' checked'; } ?>><label for="financials"> Financials</label>
				</div>
			</div>
		</td>
		<td class="col-md-1">
			<select name="manager" size="1" class="form-control input-xs">
				<option value="All"<?php if (! $manager) { echo ' selected'; } ?>>- All -</option>
				<option value="Robert Cumer"<?php if ($manager=='Robert Cumer') { echo ' selected'; } ?>>Robert Cumer</option>
				<option value="David Oden"<?php if ($manager=='David Oden') { echo ' selected'; } ?>>David Oden</option>
			</select>
		</td>
		<td class="col-md-2">
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
	$query .= "ORDER BY date_entered DESC, job_no DESC; ";
	
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

	$totalProfit = 0;
	$numJobs = 0;
	$techProfits = array();
	$techTimes = array();
	foreach ($result as $job) {
		if ($manager AND $job['fullname']<>$manager) { continue; }

		$po = '';
		$query2 = "SELECT po_number, po_file FROM services_jobpo WHERE job_id = '".$job['id']."'; ";
		$result2 = qdb($query2,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$po .= $r2['po_number'].'<BR>';
		}

		$assigns = array();
		$query2 = "SELECT fullname, assigned_to_id FROM services_jobtasks jt, services_userprofile up ";
		$query2 .= "WHERE job_id = '".$job['id']."' AND assigned_to_id = up.id; ";
		$result2 = qdb($query2,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$assigns[$r2['assigned_to_id']] = array('name'=>$r2['fullname'],'status'=>'');
		}

		/*** STATUS ***/
		// do this before assignments since we check the timesheet here
		$row_status = '';
		if ($job['cancelled']==1) {
			if ($status<>'all') { continue; }

			$row_status = '<span class="label label-danger">Canceled</span>';
//		} else if ($job['customer_po_complete']==1 AND $job['timesheets_complete']==1 AND $job['materials_complete']
//		AND $job['outside_services_complete']==1 AND $job['expenses_complete']==1 AND $job['admin_complete']==1) {
		} else if ($job['admin_complete']==1) {
			if ($status<>'all' AND $status<>'closed') { continue; }

			$row_status = '<span class="label label-success">Closed</span>';
		} else {
			$query2 = "SELECT * FROM services_techtimesheet WHERE job_id = '".$job['id']."'; ";
			$result2 = qdb($query2,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query2);
			if (mysqli_num_rows($result2)>0) {
				if ($status<>'all' AND $status<>'open') { continue; }

				$row_status = '<span class="label label-warning">In Progress</span>';
			}
			if ($job['on_hold']==1) {
				if ($status<>'all' AND $status<>'open') { continue; }

				$row_status = '<span class="label label-danger">On Hold</span>';
			}
/*
			$query2 = "SELECT * FROM services_closeoutdoc WHERE job_id = '".$job['id']."'; ";
			$result2 = qdb($query2,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query2);
			if (mysqli_num_rows($result2)>0) {
				if ($status<>'all' AND $status<>'complete') { continue; }

				$row_status = '<span class="label label-info">Complete</span>';
			}
*/
			while ($r2 = mysqli_fetch_assoc($result2)) {
				if ($r2['datetime_in'] AND ! $r2['datetime_out']) {
					$assigns[$r2['tech_id']]['status'] = format_date($r2['datetime_in'],'g:ia');
				}
			}

			if (! $row_status) {
				if ($status<>'all' AND $status<>'open') { continue; }
				$row_status = '<span class="label label-default">Idle</span>';
			}
		}

		if ($financials) {
			// get expenses prior to checking tech timesheets so expenses can be added to it
			$expenses = getExpenses($job['id']);
		}

		/*** ASSIGNMENTS AND LABOR COST / PROFITS ***/
		$assignments = '';
		$laborTotal = 0;
		$laborTotalSecs = 0;
		foreach ($assigns as $techid => $a) {
			$timeLogged = '';

			if ($financials) {
				// calculate labor costs
				list($techLaborCost,$techSecsWorked) = getTimesheet($techid,$job);
				$laborTotal += $techLaborCost;
				$laborTotalSecs += $techSecsWorked;
				if (! isset($techTimes[$techid])) { $techTimes[$techid] = array(); }
				if ($techSecsWorked>0) {
					$techTimes[$techid][] = $techSecsWorked;
				}

				$timeLogged = toTime($techSecsWorked);
			}

			if ($a['status']) {
				$assignments .= '<span class="label label-warning">'.$a['name'].' &nbsp; '.$a['status'].'</span>';
			} else {
				$assignments .= $a['name'].' '.$timeLogged;
			}
			$assignments .= '<BR/>';
		}

		/***** CALCULATE JOB FINANCIALS TOTALS *****/
		$financial_col = '';
		if ($financials) {
			// don't sum expenses until now, after getting tech's timesheets for any mileage reimbursements
			$expensesTotal = 0;
			foreach ($expenses as $e) { $expensesTotal += $e['amount']; }

			$materialsTotal = 0;
			$materials = getMaterials($job['id']);
			foreach ($materials as $m) { $materialsTotal += $m['cost']; }

			$outsideTotal = 0;
			$outsideServices = getOutsideServices($job['id']);
			foreach ($outsideServices as $o) { $outsideTotal += $o['cost']; }

			$jobTotal = $laborTotal + $materialsTotal + $expensesTotal + $outsideTotal;
			$jobProfit = $job['price_quote']-$jobTotal;
			$jobProfitPct = ($jobProfit/$job['price_quote']);
			$totalProfit += $jobProfit;
			$totalProfitPct += $jobProfitPct;
			$numJobs++;

			// go back and add profits to each tech on the job
			foreach ($assigns as $techid => $a) {
				if (! isset($techProfits[$techid])) { $techProfits[$techid] = array(); }
				$techProfits[$techid][] = $jobProfitPct;//$jobProfit/$job['price_quote'];
			}

			$finances = format_price(round($job['price_quote'],2),true,' ').' quote';
			if ($jobTotal>0) {
				$finances .= '<br/>- '.format_price(round($jobTotal,2),true,' ').' cost<br/>'.
					'<hr class="no-margin"> <strong>';
				if ($jobProfit>0) {
					$finances .= '<span class="text-black">'.format_price(round($jobProfit,2),true,' ').'</span>';
				} else {
					$finances .= '<span class="text-danger">'.format_price(round($jobProfit,2),true,' ').'</span>';
				}
				$finances .= '</strong> profit';
			}

			$financial_col = '
                                <td class="text-right">
									'.$finances.'
                                </td>
			';
		}

		$rows .= '
                            <!-- row -->
                            <tr>
                                <td>
                                    '.format_date($job['date_entered'],'M j, Y').'
                                </td>
                                <td class="word-wrap160">
                                    <a href="job.php?id='.$job['id'].'">'.$job['job_no'].'</a><br/>
									'.preg_replace('/^N[\/]?A$/i','',str_replace(chr(10),'<BR>',$job['site_access_info_contact'])).'
                                </td>
                                <td>
	                                <a href="#">'.$job['customer'].'</a><br/>
									<span class="info">'.strip_space($job['site_access_info_address']).'</span>
                                </td>
                                <td>
                                    '.$job['customer_job_no'].'
                                </td>
                                <td>
									'.$po.'
                                </td>
                                <td>
                                    '.format_date($job['scheduled_date_of_work'],'D, M j, Y').'<br/>
									to<br/>
                                    '.format_date($job['scheduled_completion_date'],'D, M j, Y').'
                                </td>
                                <td>
                                    '.$job['fullname'].'
                                </td>
                                <td>
                                    '.$assignments.'
                                </td>
								'.$financial_col.'
                                <td class="text-center">
									'.$row_status.'
                                </td>

                            </tr>
		';
	}

	$date_span = 2;
	if ($financials) {
		$date_span = 1;

		$numTechs = count($techTimes);
		$rem = $numTechs%4;
		for ($i=0; $i<(4-$rem); $i++) {
			$techTimes['null'.$i] = array();
		}

		$stats_rows = '';
		$i = 0;
		foreach ($techTimes as $techid => $r) {
			$tech = '';
			if (is_numeric($techid)) {
				$tech = getProfile($techid);
			}
			if ($i%4==0) {
				$stats_rows .= '
			</div>
			<div class="row stats-row">
				';
			}
			$avgSecs = '';
			$n = count($r);
			$jobs = '';
			if ($n>0) {
				$avgSecs = toTime(round(array_sum($r)/$n));
				// append 's' to 'job' when plural
				$s = '';
				if ($n<>1) { $s = 's'; }

				$techProfit = '';
				$pnum = count($techProfits[$techid]);
				if ($pnum>0) {
					$techProfit = ' at avg '.(round(array_sum($techProfits[$techid])/$pnum,2)*100).'% profit';
				}

				$jobs = '<span class="aux">'.$n.' job'.$s.$techProfit.'</span>';
			}

			$stats_rows .= '
                <div class="col-md-3 col-sm-3 stat">
                    <div class="data">
                        <span class="number text-brown">'.$avgSecs.'</span>
						<span class="info">'.$tech.'</span>
                    </div>
					'.$jobs.'
                </div>
			';
			$i++;
		}
?>

        <!-- upper main stats -->
        <div id="main-stats">
            <div class="row stats-row">
				<?php echo $stats_rows; ?>
            </div>
        </div>
        <!-- end upper main stats -->

		<hr>

<?php
	}/*end if ($financials)*/
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
                                    Job# / Contact
                                </th>
                                <th class="col-md-2">
                                    <span class="line"></span>
                                    Company
                                </th>
                                <th class="col-md-1">
                                    <span class="line"></span>
                                    Project ID#
                                </th>
                                <th class="col-md-1">
                                    <span class="line"></span>
                                    PO#
                                </th>
                                <th class="col-md-<?php echo $date_span; ?>">
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
<?php if ($financials) { ?>
                                <th class="col-md-1 text-center">
                                    <span class="line"></span>
									Financials
                                </th>
<?php } ?>
                                <th class="col-md-1 text-center">
                                    <span class="line"></span>
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                        	<?php echo $rows; ?>
<?php if ($financials) { ?>
                            <!-- row -->
                            <tr class="first">
                                <td colspan="8">
									<strong><?php echo $numJobs; ?></strong><br/>total jobs
                                </td>
                                <td class="text-right" style="vertical-align:bottom">
									<strong><?php echo format_price(round($totalProfit,2),true,' '); ?></strong><br/>
									total profit
                                </td>
                                <td class="text-center">
									<strong><?php echo round(($totalProfitPct/$numJobs)*100,2); ?>%</strong><br/>
									total pct
                                </td>
<?php } ?>
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

/*
        $(document).ready(function() {
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
