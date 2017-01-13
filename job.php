<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/svcs_pipe.php';

	$jobid = 0;
	if (isset($_REQUEST['id']) AND is_numeric($_REQUEST['id']) AND $_REQUEST['id']>0) { $jobid = $_REQUEST['id']; }

	if (! $jobid AND isset($_REQUEST['s']) AND $_REQUEST['s']) {
		$query = "SELECT id FROM services_job WHERE job_no = '".res(trim($_REQUEST['s']))."'; ";
		$result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$jobid = $r['id'];
		}
		$_REQUEST['s'] = '';
	}

	if (! $jobid) {
		header('Location: services.php');
		exit;
	}

	$WORKDAY_START = 19;//getTimesheet.php will automatically adjust workday end accordingly
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/htmlcp1252.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/strip_space.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getProfile.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getTimesheet.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getExpenses.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getMaterials.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOutsideServices.php';

	$db_path = 'https://db.ven-tel.com:10086/service/usermedia/';
	$job_out = '';
	$job = array();
	$contact = '';
	$special_instructions = '';
	$username = '';
	$po_ln = '';
	if ($jobid) {
		$query = "SELECT * FROM services_job WHERE id = '".res($jobid)."'; ";
		$result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query);
		$job = mysqli_fetch_assoc($result);

		$contact = preg_replace('/^N[\/]?A$/i','',str_replace(chr(10),'<BR>',$job['site_access_info_contact']));
		if ($contact) { $contact .= "<BR>"; }

		//strip out N/A and NA
		$special_instructions = preg_replace('/^N[\/]?A$/i','',str_replace(chr(10),'<BR>',trim($job['site_access_info_special'])));
//		if ($special_instructions) { $special_instructions .= "<BR><BR>"; }

		$job_out = '# '.$job['job_no'];
		$job['status'] = 'ACTIVE';
		$job['status_color'] = 'success';
		$job['status_flag'] = 'flag-checkered';
		if ($job['admin_complete']) {
			$job['status'] = 'COMPLETE';
			$job['status_color'] = '';
			$job['status_flag'] = 'flag';
		} else if ($job['on_hold']) {
			$job['status'] = 'ON HOLD';
			$job['status_color'] = 'danger';
			$job['status_flag'] = 'flag-o';
		}

		$username = getProfile($job['entered_by_id']);

		// get job po link
		$query2 = "SELECT po_number, po_file FROM services_jobpo WHERE job_id = '".$job['id']."'; ";
		$result2 = qdb($query2,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$po_ln .= $r2['po_number'].' &nbsp; <a href="'.$db_path.$r2['po_file'].'" target="_new" title="'.$r2['po_file'].'">';
			if (stristr($r2['po_file'],'pdf')) { $po_ln .= '<i class="fa fa-file-pdf-o"></i>'; }
			else if (stristr($r2['po_file'],'jpg') OR stristr($r2['po_file'],'jpeg') OR stristr($r2['po_file'],'png')) { $po_ln .= '<i class="fa fa-file-image-o"></i>'; }
			else if (stristr($r2['po_file'],'xls') OR stristr($r2['po_file'],'xlsx')) { $po_ln .= '<i class="fa fa-file-excel-o"></i>'; }
			else if (stristr($r2['po_file'],'doc') OR stristr($r2['po_file'],'docx')) { $po_ln .= '<i class="fa fa-file-word-o"></i>'; }
			else { $po_ln .= '<i class="fa fa-file-text-o"></i>'; }
			$po_ln .= '</a> &nbsp; <a href="#"><i class="fa fa-pencil"></i></a> &nbsp; <a href="#" class="text-danger"><i class="fa fa-close"></i></a><BR/>';
		}
	}
?><!DOCTYPE html>
<html lang="en">
<head>
	<title>Job# <?php echo $job['job_no']; ?></title>
	<?php
		include_once 'inc/scripts.php';
	?>
</head>
<body class="sub-nav margin-bottom-220">

	<?php include_once 'inc/navbar.php'; ?>

<?php
	$VENDORS = array();
	function getVendor($id,$input='id',$output='name') {
		global $VENDORS;

		if (! $id) { return false; }

		if (isset($VENDORS[$id][$input])) { return ($VENDORS[$id][$input][$output]); }

		$VENDORS[$id][$input] = array($output=>false);
		$query = "SELECT * FROM services_company WHERE id = '".$id."'; ";
		$result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query);
		if (mysqli_num_rows($result)>0) {
			$VENDORS[$id][$input] = mysqli_fetch_assoc($result);
		}
		return ($VENDORS[$id][$input][$output]);
	}

	// determine which column should be wider based on content: description or special instructions
	$col1 = '4';//description
	$col2 = '5';//special instructions
	$job_strlen = strlen($job['description']);
	$ins_strlen = strlen($special_instructions);
	if ($job_strlen==0) { $col1 = '2'; $col2 = '7'; }
	else if ($ins_strlen==0) { $col1 = '7'; $col2 = '2'; }
	else if ($job_strlen>$ins_strlen) { $col1 = '5'; $col2 = '4'; }

	/***** MATERIALS DATA *****/
	$material_rows = '';
	$materialsTotal = 0;
	$materials = getMaterials($job['id']);
	foreach ($materials as $r) {
		// truncate to 2-decimals if not more than 2 are used
		$cost = format_price($r['cost'],true,' ');
		$addl_cost = format_price($r['addtl_cost'],true,' ');
		$vendor = getVendor($r['vendor_id']);
		$materialsTotal += ($r['cost']);//+$r['addtl_cost']);
		$material_rows .= '
                            <!-- row -->
                            <tr>
                                <td>
                                    '.$r['part_number'].'
                                </td>
                                <td>
                                    '.htmlcp1252($r['description']).'
                                </td>
                                <td>
                                    '.$vendor.'
                                </td>
                                <td class="text-right">
                                    '.$cost.'
                                </td>
                                <td class="text-right">
                                    '.$addl_cost.'
                                </td>
                                <td>
                                    '.$r['po_id'].'
                                </td>
                                <td>
                                    '.$r['required_quantity'].'
                                </td>
                                <td>
                                    '.$r['received_quantity'].'
                                </td>
                                <td>
                                    '.$r['at_site'].'
                                </td>
                            </tr>
		';
	}

	/***** LABOR DATA *****/
	$labor = array();
	// get assigned techs to the job so we know who to query from tech time sheets
	$query = "SELECT * FROM services_jobtasks WHERE job_id = '".$job['id']."'; ";
	$result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$labor[$r['assigned_to_id']][] = $r;
	}

	// get expenses first so other expenses like mileage within labor results can be added to it
	$expenses = getExpenses($job['id']);
	$laborTotal = 0;
	$laborTotalSecs = 0;
	$labor_rows = '';
	foreach ($labor as $techid => $arr) {
		/*** debugging variables for output when needing a little addl detail ***/
		$debugSecsReg = 0;
		$debugSecsOT = 0;
		$debugSecsPd = 0;
		$debugRegPayTotal = 0;
		$debugOTPayTotal = 0;
		$debugPdPayTotal = 0;
		/*** end debugging vars ***/

		list($techLaborCost,$techSecsWorked) = getTimesheet($techid,$job);
		$laborTotalSecs += $techSecsWorked;

		if ($techLaborCost==0) { continue; }
		// round for display purposes
		$techLaborCost = round($techLaborCost,2);
		$laborTotal += $techLaborCost;
		$timeLogged = toTime($techSecsWorked);

		// get all tasks (assignments) from our earlier query primarily for display purposes
		$completed = false;
		$admin_completed = false;
		$assignments = '';
		foreach ($arr as $task) {
			if ($task['employee_completed'] AND $completed===false) { $completed = true; }
			else if (! $task['employee_completed']) { $completed = 0; }
			if ($task['admin_completed'] AND $admin_completed===false) { $admin_completed = true; }
			else if (! $task['admin_completed']) { $admin_completed = 0; }
			$notes = '';
			if ($task['notes']) { $notes = '<br/>&nbsp; <span class="info">'.$task['notes'].'</span>'; }
			$assigned_dates = '';
			if ($task['date_scheduled'] AND $task['date_scheduled_complete']) {
				$assigned_dates = format_date($task['date_scheduled'],'M j, Y').' to '.format_date($task['date_scheduled_complete'],'M j, Y');
			}

			$assignments .= $assigned_dates.$notes.'<br/>';
		}

		if ($completed) { $completed = '<i class="fa fa-check text-success"></i>'; }
		else { $completed = ''; }
		if ($admin_completed) { $admin_completed = '<i class="fa fa-check text-success"></i>'; }
		else { $admin_completed = ''; }

		$labor_rows .= '
                            <!-- row -->
                            <tr class="valign-top">
                                <td>
									'.getProfile($techid).'
                                </td>
                                <td>
									'.$task['name'].'
                                </td>
                                <td>
									'.$assignments.'
                                </td>
                                <td>
									'.$timeLogged.'<br/> &nbsp; <span class="info">'.timeToStr($timeLogged).'</span>
<!--
<BR>std '.toTime($debugSecsReg).' = '.$debugRegPayTotal.'<BR>
OT '.toTime($debugSecsOT).' = '.$debugOTPayTotal.'<BR>
PD '.toTime($debugSecsPd).' = '.$debugPdPayTotal.'<BR>
-->
                                </td>
                                <td class="text-right">
									'.format_price($techLaborCost,true,' ').'
                                </td>
                                <td class="text-center">
									'.$completed.'
                                </td>
                                <td class="text-center">
									'.$admin_completed.'
                                </td>
                            </tr>
		';
	}
	$laborTotalTime = toTime($laborTotalSecs);

	uasort($expenses,'cmp_timestamp');

	$expenses_rows = '';
	$expensesTotal = 0;
	foreach ($expenses as $r) {
		$expensesTotal += $r['amount'];
		if ($r['tech_id']>0) { $employeeid = $r['tech_id']; }
		else { $employeeid = $r['entered_by_id']; }

		$expenses_rows .= '
                            <!-- row -->
                            <tr class="valign-top">
                                <td>
									'.getProfile($employeeid).'
                                </td>
                                <td>
									'.format_date($r['timestamp'],'n/j/y').'
                                </td>
                                <td>
									'.$r['label'].'
                                </td>
                                <td class="text-right">
									'.format_price($r['amount'],true,' ').'
                                </td>
                                <td class="text-right">
									'.$r['type'].'
                                </td>
							</tr>
		';
	}

	$expensesTotal = round($expensesTotal,2);

	$outside_rows = '';
	$outsideTotal = 0;
	$outsideServices = getOutsideServices($job['id']);

	foreach ($outsideServices as $r) {
		$po_date = '';
		$employee = '';
		if ($r['po_id']) {
			$query2 = "SELECT * FROM services_joboutsideservicepo WHERE id = '".$r['po_id']."'; ";
			$result2 = qdb($query2,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$po_date = format_date($r2['po_date'],'n/j/y');
				$employee = getProfile($r2['entered_by_id']);
			}
		}
		$outsideTotal += $r['cost'];
		$outside_rows .= '
                            <!-- row -->
                            <tr class="valign-top">
                                <td>
									'.$employee.'
                                </td>
                                <td>
									'.getVendor($r['vendor_id']).'
                                </td>
                                <td>
									'.$po_date.'
                                </td>
                                <td>
									'.$r['scope'].'
                                </td>
                                <td class="text-right">
									'.format_price($r['cost'],true,' ').'
                                </td>
							</tr>
		';
	}
	$outsideTotal = round($outsideTotal,2);

	$jobComm = 0;
	$query = "SELECT SUM(amount) comm FROM services_commission WHERE job_id = '".$job['id']."'; ";
	$result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query);
	if (mysqli_num_rows($result)>0) {
		$r = mysqli_fetch_assoc($result);
		$jobComm = $r['comm'];
	}
	$jobComm = round($jobComm,2);

	$jobTotal = $laborTotal + $materialsTotal + $expensesTotal + $outsideTotal;
	$jobProfit = $job['price_quote']-$jobTotal;
	if ($jobProfit>0) { $themeProfit = 'text-success'; }
	else if ($jobProfit<0) { $themeProfit = 'text-danger'; }
	else { $themeProfit = 'text-info'; }

	$laborProfit = ($job['quote_labor']-$laborTotal);

	$progressCls = '';
	$jobPct = round((($laborProfit/$job['quote_labor'])*100));
	$jobPctOut = $jobPct;
	if ($jobPct<0) {
		$progressCls = ' progress-bar-danger';
		$jobPct = -$jobPct;
	}

	// if no start date passed in, or invalid, set to beginning of previous month
	if (! $startDate) { $startDate = format_date($today,'m/01/Y',array('m'=>-1)); }
?>

	<form class="form-inline" action="services.php" method="get">

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
	include_once $_SERVER["ROOT_DIR"].'/inc/calcQuarters.php';

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
            <h2 class="minimal">Job<?php echo $job_out; ?></h2>
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

    <div id="pad-wrapper">

<div class="container">
    <section class="margin-bottom">
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <div class="text-icon">
                    <span class="icon-ar icon-ar-sm bg-info"><i class="fa fa-building-o"></i></span>
                    <div class="text-icon-content">
                        <h5 class="no-margin"><?php echo $job['customer']; ?></h5>
                        <p class="info"><small>Customer</small></p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6">
                <div class="text-icon">
                    <span class="icon-ar icon-ar-sm bg-info"><i class="fa fa-hashtag"></i></span>
                    <div class="text-icon-content">
                        <h5 class="no-margin"><?php echo $job['customer_job_no']; ?></h5>
                        <p class="info"><small>Project ID</small></p>
                    </div>
				</div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="text-icon">
                    <span class="icon-ar icon-ar-sm bg-info"><i class="fa fa-file-o"></i></span>
                    <div class="text-icon-content">
                        <h5 class="no-margin"><?php echo $po_ln; ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6">
                <div class="text-icon">
                    <span class="icon-ar icon-ar-sm bg-info"><i class="fa fa-user"></i></span>
                    <div class="text-icon-content">
                        <h5 class="no-margin"><?php echo $username; ?></h5>
                        <p class="info"><small>Created <?php echo format_date($job['date_entered'],'M d, Y'); ?></small></p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6">
                <div class="text-icon">
                    <span class="icon-ar icon-ar-sm fa-stack bg-brown"><i class="fa fa-calendar-o fa-stack-2x fa-stack-md"></i><span class="fa-stack-xs calendar-text"><?php echo format_date($job['scheduled_date_of_work'],'d'); ?></span></span>
                    <div class="text-icon-content">
                        <h5 class="no-margin"><?php echo format_date($job['scheduled_date_of_work'],'D M j, Y'); ?></h5>
                        <p class="info"><small>Scheduled Work</small></p>
                    </div>
                    <span class="icon-ar icon-ar-sm fa-stack bg-brown"><i class="fa fa-calendar-o fa-stack-2x fa-stack-md"></i><span class="fa-stack-xs calendar-text"><?php echo format_date($job['scheduled_completion_date'],'d'); ?></span></span>
                    <div class="text-icon-content">
                        <h5 class="no-margin"><?php echo format_date($job['scheduled_completion_date'],'D M j, Y'); ?></h5>
                        <p class="info"><small>Scheduled Completion</small></p>
                    </div>
                </div>
            </div>
		</div>
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <div class="text-icon">
<?php
	// strip range of non-printable characters at end of address string from copying and pasting (side-eyes Dave Oden)
	$address = strip_space($job['site_access_info_address']);
?>
                    <span class="icon-ar icon-ar-sm bg-brown"><i class="fa fa-map-marker"></i></span>
                    <div class="text-icon-content">
						<?php if ($contact) { echo '<h5 class="no-margin">'.$contact.'</h5><p class="info"><small>Contact</small></p>'; } else { echo '<br/><br/><br/>'; } ?>
                        <h5 class="no-margin"><?php echo $address; ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-<?php echo $col1; ?> col-sm-6 long-text">
                <div class="text-icon">
                    <span class="icon-ar icon-ar-sm bg-info"><i class="fa fa-info-circle"></i></span>
                    <div class="text-icon-content">
                        <small><?php echo str_replace(chr(10),'<BR>',htmlcp1252($job['description'])); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-<?php echo $col2; ?> col-sm-6 long-text">
                <div class="text-icon">
                    <span class="icon-ar icon-ar-sm bg-info"><i class="fa fa-sticky-note-o"></i></span>
                    <div class="text-icon-content">
                        <small><?php echo $special_instructions; ?></small>
                    </div>
                </div>
            </div>
        </div> <!-- row -->
    </section>

	<hr>

    </section>
        <!-- upper main stats -->
        <div id="main-stats">
            <div class="row stats-row">
                <div class="col-md-3 col-sm-3 stat">
                    <div class="data">
                        <span class="number text-brown"><?php echo format_price($job['price_quote'],true,''); ?></span>
						<span class="info">Job Quote</span>
                    </div>
                </div>
                <div class="col-md-3 col-sm-3 stat">
                    <div class="data">
                        <span class="number text-black"><?php echo format_price($jobTotal,true,''); ?></span>
						<span class="info">Total Cost</span>
                    </div>
                </div>
                <div class="col-md-3 col-sm-3 stat">
                    <div class="data">
                        <span class="number text-black"><?php echo format_price($jobComm,true,''); ?></span>
						<span class="info">Commission</span>
                    </div>
                </div>
                <div class="col-md-3 col-sm-3 stat last">
                    <div class="data">
                        <span class="number <?php echo $themeProfit; ?>"><?php echo format_price($jobProfit,true,''); ?></span>
						<span class="info">Job Profit</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- end upper main stats -->

<!--
            <div class="col-md-2 col-sm-6">
                <div class="text-icon">
                    <span class="icon-ar icon-ar-sm bg-info"><i class="fa fa-dollar"></i></span>
                    <div class="text-icon-content">
                        <h5 class="no-margin"><?php echo format_price($job['price_quote'],true,''); ?></h5>
                        <p class="info"><small>Job Quote</small></p>
                    </div>
                    <span class="icon-ar icon-ar-sm <?php echo $themeProfit; ?>"><i class="fa fa-money"></i></span>
                    <div class="text-icon-content">
                        <h5 class="no-margin"><?php echo format_price($jobProfit,true,''); ?></h5>
                        <p class="info"><small>Job Profit</small></p>
                    </div>
                    <span class="icon-ar icon-ar-sm bg-<?php echo $job['status_color']; ?>"><i class="fa fa-<?php echo $job['status_flag']; ?>"></i></span>
                    <div class="text-icon-content">
                        <h5 class="no-margin text-<?php echo $job['status_color']; ?>"><?php echo $job['status']; ?></h5>
                        <p class="info"><small>Status</small></p>
                    </div>
                </div>
            </div>
-->
	</section>

	<hr>

    <section>
			<!-- Nav tabs -->
			<ul class="nav nav-tabs nav-tabs-ar">
				<li class="active"><a href="#labor" data-toggle="tab"><i class="fa fa-users"></i> Labor &nbsp; <?php echo format_price($laborTotal,true,''); ?></a></li>
				<li><a href="#materials" data-toggle="tab"><i class="fa fa-list"></i> Materials &nbsp; <?php echo format_price($materialsTotal,true,''); ?></a></li>
				<li><a href="#expenses" data-toggle="tab"><i class="fa fa-credit-card"></i> Expenses &nbsp; <?php echo format_price($expensesTotal,true,''); ?></a></li>
				<li><a href="#outside" data-toggle="tab"><i class="fa fa-suitcase"></i> Outside Services &nbsp; <?php echo format_price($outsideTotal,true,''); ?></a></li>
				<li class="pull-right"><a href="#" data-toggle="tab"><strong><i class="fa fa-shopping-cart"></i> Total &nbsp; <?php echo format_price($jobTotal,true,''); ?></strong></a></li>
			</ul>
 
			<!-- Tab panes -->
			<div class="tab-content">

				<!-- Materials pane -->
				<div class="tab-pane" id="materials">
                    <table class="table table-hover table-condensed">
                        <thead class="no-border">
                            <tr>
                                <th class="col-md-2">
                                    Item
                                </th>
                                <th class="col-md-2">
                                    Description
                                </th>
                                <th class="col-md-2">
                                    Vendor
                                </th>
                                <th class="col-md-1">
                                    Cost
                                </th>
                                <th class="col-md-1">
                                    Addl Cost
                                </th>
                                <th class="col-md-1">
                                    PO
                                </th>
                                <th class="col-md-1">
                                    Req Qty
                                </th>
                                <th class="col-md-1">
                                    Avail Qty
                                </th>
                                <th class="col-md-1">
                                    On Site Qty
                                </th>
                            </tr>
                        </thead>
                        <tbody>
							<?php echo $material_rows; ?>
                            <!-- row -->
                            <tr class="first">
                                <td colspan="3">
                                </td>
                                <td class="text-right">
                                    <strong><?php echo format_price($materialsTotal,true,' '); ?></strong>
                                </td>
                                <td colspan="6">
                                </td>
                            </tr>
						</tbody>
					</table>
				</div><!-- Materials pane -->

				<!-- Labor pane -->
				<div class="tab-pane active" id="labor">
                    <table class="table table-hover table-condensed">
                        <thead class="no-border">
                            <tr>
                                <th class="col-md-2">
                                    Employee
                                </th>
                                <th class="col-md-1">
                                    Role
                                </th>
                                <th class="col-md-4">
                                    Assignments
                                </th>
                                <th class="col-md-2">
                                    Total Hours Logged
                                </th>
                                <th class="col-md-1 text-center">
                                    Cost
                                </th>
                                <th class="col-md-1 text-center">
									<div data-toggle="tooltip" data-placement="left" title="Tech Complete?"><i class="fa fa-id-badge"></i></div>
                                </th>
                                <th class="col-md-1 text-center">
									<div data-toggle="tooltip" data-placement="left" title="Admin Complete?"><i class="fa fa-briefcase"></i></div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
							<?php echo $labor_rows; ?>
                            <!-- row -->
                            <tr class="first">
                                <td colspan="2">
									<div class="progress progress-lg">
										<div class="progress-bar<?php echo $progressCls; ?>" role="progressbar" aria-valuenow="<?php echo $jobPct; ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $jobPct; ?>%"><?php echo $jobPctOut; ?>%</div>
									</div>
                                </td>
                                <td>
									<?php echo format_price($laborProfit,true,''); ?> profit of <?php echo format_price($job['quote_labor'],true,''); ?> quoted Labor
                                </td>
                                <td>
									<strong><?php echo $laborTotalTime.' &nbsp; '.timeToStr($laborTotalTime); ?></strong>
                                </td>
                                <td class="text-right">
                                    <strong><?php echo format_price($laborTotal,true,' '); ?></strong>
                                </td>
                                <td colspan="2">
                                </td>
                            </tr>
						</tbody>
					</table>
				</div><!-- Labor pane -->

				<!-- Expenses pane -->
				<div class="tab-pane" id="expenses">
                    <table class="table table-hover table-condensed">
                        <thead class="no-border">
                            <tr>
                                <th class="col-md-2">
                                    Employee
                                </th>
                                <th class="col-md-1">
                                    Date
                                </th>
                                <th class="col-md-6">
                                    Description
                                </th>
                                <th class="col-md-2 text-center">
                                    Amount
                                </th>
                                <th class="col-md-1 text-center">
                                    Type
                                </th>
                            </tr>
                        </thead>
                        <tbody>
							<?php echo $expenses_rows; ?>
                            <!-- row -->
                            <tr class="first">
                                <td colspan="3">
                                </td>
                                <td class="text-right">
                                    <strong><?php echo format_price($expensesTotal,true,' '); ?></strong>
                                </td>
                                <td>
                                </td>
                            </tr>
						</tbody>
					</table>
				</div><!-- Expenses pane -->

				<!-- Outside Services pane -->
				<div class="tab-pane" id="outside">
                    <table class="table table-hover table-condensed">
                        <thead class="no-border">
                            <tr>
                                <th class="col-md-1">
                                    Employee
                                </th>
                                <th class="col-md-2">
                                    Vendor
                                </th>
                                <th class="col-md-1">
                                    Date
                                </th>
                                <th class="col-md-6">
                                    Description
                                </th>
                                <th class="col-md-2 text-center">
                                    Amount
                                </th>
                            </tr>
                        </thead>
                        <tbody>
							<?php echo $outside_rows; ?>
                            <!-- row -->
                            <tr class="first">
                                <td colspan="4">
                                </td>
                                <td class="text-right">
                                    <strong><?php echo format_price($outsideTotal,true,' '); ?></strong>
                                </td>
                            </tr>
						</tbody>
					</table>
				</div><!-- Outside Services pane -->

			</div><!-- tab-content -->
    </section>
</div> <!-- container -->

</div> <!-- pad-wrapper -->

	</form>

<?php include_once 'inc/footer.php'; ?>

</body>
</html>
