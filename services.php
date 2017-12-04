<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$keyword = '';
	if ((isset($_REQUEST['s']) AND $_REQUEST['s']) OR (isset($_REQUEST['keyword']) AND $_REQUEST['keyword'])) {
		if (isset($_REQUEST['s']) AND $_REQUEST['s']) { $keyword = $_REQUEST['s']; }
		else if (isset($_REQUEST['keyword']) AND $_REQUEST['keyword']) { $keyword = $_REQUEST['keyword']; }
		$keyword = trim($keyword);

		$matches = array();
		$matches_csv = '';
		$query = "SELECT i.so_number, i.id, i.line_number FROM service_orders o ";
		$query .= "LEFT JOIN service_items i ON o.so_number = i.so_number ";
		$query .= "WHERE (cust_ref = '".res($keyword)."' OR o.so_number = '".res($keyword)."' ";
		$query .= "OR CONCAT(i.so_number,'-',i.line_number) = '".res($keyword)."' OR task_name RLIKE '".res($keyword)."'); ";
		$result = qdb($query) OR die(qe().'<BR>'.$keyword);
		while ($r = mysqli_fetch_assoc($result)) {
			if ($matches_csv) { $matches_csv .= ','; }
			$matches_csv .= $r['id'];
			$matches[] = $r['so_number'].'-'.$r['line_number'];
		}

		$query = "SELECT so_number, i.id, i.line_number FROM addresses a, service_items i ";
		$query .= "WHERE a.id = item_id AND item_label = 'addressid' AND street RLIKE '".res($keyword)."' ";
		if ($matches_csv) {
			$query .= "AND i.id NOT IN (".$matches_csv.") ";
		}
		$query .= "; ";
		$result = qdb($query) OR die(qe().'<BR>'.$keyword);
		while ($r = mysqli_fetch_assoc($result)) {
			$matches[] = $r['so_number'].'-'.$r['line_number'];
		}

		if (count($matches)==1) {
			header('Location: service.php?order_number='.$matches[0]);
			exit;
		}

/*dl 12-1-17
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
*/
		// in order for user to be able to 'reset' view to default services home, we want to reset search string
		// so that if they at first were switching between modes (say, sales to services) with a sales-related
		// search string, the subsequent click would show all services instead of the bogus search string
		$_REQUEST['s'] = '';
	}

	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getAddresses.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPart.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPipeIds.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/calcQuarters.php';
//	include_once $_SERVER["ROOT_DIR"].'/inc/strip_space.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getProfile.php';
	$WORKDAY_START = 19;//workday end is set automatically in getTimesheet.php based on this
	include_once $_SERVER["ROOT_DIR"].'/inc/getTimesheet.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getExpenses.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getMaterials.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOutsideServices.php';

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
	if (! $startDate) { $startDate = format_date($today,'m/d/Y',array('d'=>-30)); }

	$endDate = date('m/d/Y');
	if (isset($_REQUEST['END_DATE']) AND $_REQUEST['END_DATE']){
		$endDate = format_date($_REQUEST['END_DATE'], 'm/d/Y');
	}

	if (! isset($_REQUEST['status']) OR ! $_REQUEST['status']) { $status = 'open'; }
	else { $status = $_REQUEST['status']; }

	$financials = false;
	if (isset($_REQUEST['financials']) AND $_REQUEST['financials']) { $financials = true; }

	$managerid = 0;
	if (isset($_REQUEST['managerid']) AND $_REQUEST['managerid']>0) {
		$managerid = $_REQUEST['managerid'];
	} else if (in_array("4", $USER_ROLES)) {
		$managerid = $U['id'];
	}


	/****** FUNCTIONS ******/
	function calcServiceQuote($order_number) {
		return (0);
	}
?>

<!------------------------------------------------------------------------------------------->
<!-------------------------------------- HEADER OUTPUT -------------------------------------->
<!------------------------------------------------------------------------------------------->
<!DOCTYPE html>
<html>
<!-- Declaration of the standard head with Services home set as title -->
<head>
	<title>Services</title>
	<?php
		//Standard headers included in the function
		include_once $_SERVER["ROOT_DIR"].'/inc/scripts.php';
	?>
	<style type="text/css">
		.table td {
			vertical-align:top !important;
		}
		@media print{
			.table-header {
				display:none;
				visibility:hidden;
			}
		}
	</style>
</head>

<body class="sub-nav accounts-body" data-scope="Service">
	
	<?php include 'inc/navbar.php'; ?>

	<!-- Wraps the entire page into a form for the sake of php trickery -->
	<form class="form-inline" method="get" action="/services.php">

    <table class="table table-header table-filter hidden-xs hidden-sm hidden-md">
		<tr>
		<td class = "col-md-2">
			<div class="btn-group medium">
				<input type="hidden" name="status" id="status" value="<?php echo $status; ?>">
				<button class="btn  btn-default btn-sm btn-status left<?php if (! $status OR $status=='open') { echo ' active btn-warning'; } ?>" type="button" data-status="open" data-toggle="tooltip" data-placement="right" title="Open/Idle"><i class="fa fa-folder-open"></i></button>
				<button class="btn  btn-default btn-sm btn-status middle<?php if ($status=='complete') { echo ' active btn-primary'; } ?>" type="button" data-status="complete" data-toggle="tooltip" data-placement="bottom" title="Tech Completed"><i class="fa fa-folder"></i></button>
				<button class="btn  btn-default btn-sm btn-status middle<?php if ($status=='closed') { echo ' active btn-success'; } ?>" type="button" data-status="closed" data-toggle="tooltip" data-placement="bottom" title="Closed"><i class="fa fa-check-square"></i></button>
				<button class="btn  btn-default btn-sm btn-status right<?php if ($status=='all') { echo ' active btn-info'; } ?>" type="button" data-status="all" data-toggle="tooltip" data-placement="bottom" title="All"><i class="fa fa-square"></i></button>
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
            <h2 class="minimal">Services</h2>
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
			<select name="managerid" size="1" class="form-control input-xs select2">
				<option value=""<?php if (! $managerid) { echo ' selected'; } ?>>- All -</option>
				<option value="8"<?php if ($managerid==8) { echo ' selected'; } ?>>Scott Johnston</option>
				<option value="27"<?php if ($managerid==27) { echo ' selected'; } ?>>Michael Camarillo</option>
			</select>
		</td>
		<td class="col-md-2">
			<div class="pull-right form-group">
				<select name="companyid" id="companyid" class="company-selector">
					<?php if ($company_filter) {echo '<option value="'.$company_filter.'" selected>'.getCompany($company_filter).'</option>'.chr(10);} ?>
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

            <!-- jobs table -->
            <div class="table-wrapper">

			<!-- If the summary button is pressed, inform the page and depress the button -->


<!--================================================================================-->
<!--=============================   PRINT TABLE ROWS   =============================-->
<!--================================================================================-->
<?php
//DAVID: change when going live
$U['id'] = 29;

	//Establish a blank array for receiving the results from the table
	$results = array();

	$rows = '';
	$total_pcs = 0;
	$total_amt = 0;

/*dl 12-1-17
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
	$result = qdb($query,'SVCS_PIPE');
*/

	// is the user permitted for any management roles?
	$permissions = array_intersect($USER_ROLES, array(1,7));

	$query = "SELECT o.*, i.* FROM ";
	// if no permissions, join the table with assignments to be sure this user is assigned in order to view
	if (! $permissions) { $query .= "service_assignments sa, "; }
	$query .= "service_orders o, service_items i ";
	$query .= "LEFT JOIN addresses a ON (i.item_id = a.id AND i.item_label = 'addressid') ";
	$query .= "WHERE o.so_number = i.so_number ";
	if (! $permissions) { $query .= "AND sa.userid = '".$U['id']."' AND sa.item_id = i.id AND sa.item_id_label = 'service_item_id' "; }
   	if ($keyword) {
		$query .= "AND (i.task_name RLIKE '".$keyword."' OR a.street RLIKE '".$keyword."' OR a.city RLIKE '".$keyword."' OR o.public_notes RLIKE '".$keyword."') ";
	} else if ($startDate) {
   		$dbStartDate = format_date($startDate, 'Y-m-d');
   		$dbEndDate = format_date($endDate, 'Y-m-d');
   		$query .= "AND datetime BETWEEN CAST('".$dbStartDate."' AS DATETIME) AND CAST('".$dbEndDate."' AS DATETIME) ";
	}
	if ($companyid) {
		$query .= "AND companyid = '".res($companyid)."' ";
	}
	$query .= "GROUP BY i.id ";
	$query .= "ORDER BY datetime DESC, o.so_number DESC, i.line_number ASC, task_name ASC; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);

	//The aggregation method of form processing. Take in the information, keyed on primary sort field,
	//will prepare the results rows to make sorting and grouping easier without having to change the results
	$summary_rows = array();

	$totalProfit = 0;
	$numJobs = 0;
	$techProfits = array();
	$techTimes = array();
	foreach ($result as $job) {
		if ($managerid>0 AND $job['sales_rep_id']<>$managerid AND ! in_array("1",$USER_ROLES)) { continue; }

		$po = '';
/*dl 12-1-17
		$query2 = "SELECT po_number, po_file FROM services_jobpo WHERE job_id = '".$job['id']."'; ";
		$result2 = qdb($query2,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$po .= $r2['po_number'].'<BR>';
		}
*/

		$assigns = array();
/*dl 12-1-17
		$query2 = "SELECT fullname, assigned_to_id FROM services_jobtasks jt, services_userprofile up ";
		$query2 .= "WHERE job_id = '".$job['id']."' AND assigned_to_id = up.id; ";
		$result2 = qdb($query2,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$assigns[$r2['assigned_to_id']] = array('name'=>$r2['fullname'],'status'=>'');
		}
*/
		$query2 = "SELECT name, userid FROM service_assignments a, users u, contacts c ";
		$query2 .= "WHERE item_id = '".$job['id']."' AND item_id_label = 'service_item_id' ";
		$query2 .= "AND u.id = a.userid AND u.contactid = c.id; ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$assigns[$r2['userid']] = array('name'=>$r2['name'],'status'=>'');
		}

		/*** STATUS ***/
		// do this before assignments since we check the timesheet here
		$row_status = '';
		//if ($job['cancelled']==1) {
		if ($job['status']=='Void') {
			if ($status<>'all') { continue; }

			$row_status = '<span class="label label-danger">Canceled</span>';
		//} else if ($job['admin_complete']==1) {
		} else if ($job['status_code']==2) {
			if ($status<>'all' AND $status<>'closed') { continue; }

			$row_status = '<span class="label label-success">Closed</span>';
		} else {
/*dl 12-1-17
			$query2 = "SELECT * FROM services_techtimesheet WHERE job_id = '".$job['id']."'; ";
			$result2 = qdb($query2,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query2);
*/
			$query2 = "SELECT * FROM timesheets WHERE taskid = '".$job['id']."' AND task_label = 'service_item_id'; ";
			$result2 = qdb($query2) OR die(qe().' '.$query2);
			if (mysqli_num_rows($result2)>0) {
				if ($status<>'all' AND $status<>'open') { continue; }

				$row_status = '<span class="label label-warning">In Progress</span>';
			}
			//if ($job['on_hold']==1) {
			if ($job['status_code']==3) {
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
				if ($r2['clockin'] AND ! $r2['clockout']) {
					$assigns[$r2['userid']]['status'] = format_date($r2['clockin'],'g:ia');
				}
			}

			if (! $row_status) {
				if ($status<>'all' AND $status<>'open') { continue; }
				$row_status = '<span class="label label-default">Idle</span>';
			}
		}

		$expenses = array();
		if ($financials) {
			// get expenses prior to checking tech timesheets so expenses can be added to it
			$expenses = getExpenses($job['id'],'service_item_id');
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
			$materials = getMaterials($job['id'],'service_item_id');
			foreach ($materials as $m) { $materialsTotal += $m['cost']; }

			$outsideTotal = 0;
			$outsideServices = getOutsideServices($job['so_number'],'Service');
			foreach ($outsideServices as $o) { $outsideTotal += $o['cost']; }

			$jobQuote = $job['qty']*$job['amount'];
			$jobTotal = $laborTotal + $materialsTotal + $expensesTotal + $outsideTotal;
			$jobProfit = $jobQuote-$jobTotal;
			$jobProfitPct = ($jobProfit/$jobQuote);
			$totalProfit += $jobProfit;
			$totalProfitPct += $jobProfitPct;
			$numJobs++;

			// go back and add profits to each tech on the job
			foreach ($assigns as $techid => $a) {
				if (! isset($techProfits[$techid])) { $techProfits[$techid] = array(); }
				$techProfits[$techid][] = $jobProfitPct;//$jobProfit/$jobQuote;
			}

			$finances = format_price(round($jobQuote,2),true,' ').' charge';
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

		if ($job['item_id']) {
			$address = address_out($job['item_id']);
		} else { // for legacy data
			$address = $job['public_notes'];
		}

		$rows .= '
                            <!-- row -->
                            <tr>
                                <td>
                                    <span class="hidden-xs hidden-sm">'.format_date($job['datetime'],'M j, Y').'</span>
                                    <span class="hidden-md hidden-lg"><small>'.format_date($job['datetime'],'n/j/y').'</small></span>
                                </td>
                                <td class="word-wrap160">
                                    <a href="service.php?order_number='.$job['so_number'].'-'.$job['line_number'].'">'.$job['task_name'].'</a><br/>
									'.getContact($job['contactid']).'
                                </td>
                                <td>
	                                <a href="profile.php?companyid='.$job['companyid'].'">'.getCompany($job['companyid']).'</a><br/>
									<span class="info">'.$address.'</span>
                                </td>
                                <td class="hidden-xs hidden-sm">
                                    '.$job['cust_ref'].'
                                </td>
                                <td class="hidden-xs hidden-sm">
									'.$po.'
                                </td>
                                <td>
<!--
                                    '.format_date($job['scheduled_date_of_work'],'D, M j, Y').'<br/>
									to<br/>
-->
                                    '.format_date($job['due_date'],'D, M j, Y').'
                                </td>
                                <td>
                                    '.getUser($job['sales_rep_id']).'
                                </td>
                                <td>
                                    '.$assignments.'
                                </td>
								'.$financial_col.'
                                <td class="text-center hidden-xs hidden-sm">
									'.$row_status.'
                                </td>

                            </tr>
		';
	}

	if ($financials) {
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
                    <table class="table table-hover table-striped table-condensed table-responsive">
                        <thead>
                            <tr>
                                <th class="col-sm-1">
                                    Date
                                </th>
                                <th class="col-sm-1">
                                    <span class="line"></span>
                                    <span class="hidden-xs hidden-sm">Task# / Contact</span>
                                    <span class="hidden-md hidden-lg">Task#</span>
                                </th>
                                <th class="col-sm-2">
                                    <span class="line"></span>
                                    <span class="hidden-xs hidden-sm">Description</span>
                                    <span class="hidden-md hidden-lg">Descr</span>
                                </th>
                                <th class="col-sm-1 hidden-xs hidden-sm">
                                    <span class="line"></span>
									Project#
                                </th>
                                <th class="col-sm-1 hidden-xs hidden-sm">
                                    <span class="line"></span>
                                    PO#
                                </th>
                                <th class="col-sm-1">
                                    <span class="line"></span>
                                    <span class="hidden-xs hidden-sm">Due Date</span>
                                    <span class="hidden-md hidden-lg">Due</span>
                                </th>
                                <th class="col-sm-1">
                                    <span class="line"></span>
                                    <span class="hidden-xs hidden-sm">Manager</span>
                                    <span class="hidden-md hidden-lg">Mgr</span>
                                </th>
                                <th class="col-sm-1">
                                    <span class="line"></span>
                                    <span class="hidden-xs hidden-sm">Assignments</span>
                                    <span class="hidden-md hidden-lg">Techs</span>
                                </th>
<?php if ($financials) { ?>
                                <th class="col-sm-1 text-center">
                                    <span class="line"></span>
									Financials
                                </th>
<?php } ?>
                                <th class="col-sm-1 text-center hidden-xs hidden-sm">
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
