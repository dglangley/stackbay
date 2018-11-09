<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	// Check the Mobile
	if(is_mobile()) {
		include_once $_SERVER["ROOT_DIR"].'/responsive_dashboard.php';

		exit;
	}

	// Hack to invoke quotes view change
	if(isset($_REQUEST['quote'])) {
		$quote =  $_REQUEST['quote'];
	}

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
		$query .= "OR CONCAT(i.so_number,'-',i.line_number) = '".res($keyword)."' OR task_name RLIKE '".res($keyword)."' ";
		$query .= "OR public_notes RLIKE '".res($keyword)."' ";
		$query .= "); ";
		$result = qdb($query) OR die(qe().'<BR>'.$keyword);
		while ($r = mysqli_fetch_assoc($result)) {
			if ($r['id']) {
				if ($matches_csv) { $matches_csv .= ','; }
				$matches_csv .= $r['id'];
			}
			$matches[] = $r['so_number'].'-'.$r['line_number'];
		}

		$query = "SELECT so_number, i.id, i.line_number FROM service_items i, addresses a ";
		$query .= "LEFT JOIN company_addresses ca ON ca.addressid = a.id ";
		$query .= "WHERE a.id = item_id AND item_label = 'addressid' ";
		$query .= "AND (a.street RLIKE '".res($keyword)."' OR a.city RLIKE '".res($keyword)."' ";
		$query .= "OR ca.nickname RLIKE '".res($keyword)."' OR ca.alias RLIKE '".res($keyword)."' OR ca.notes RLIKE '".res($keyword)."') ";

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

		// in order for user to be able to 'reset' view to default services home, we want to reset search string
		// so that if they at first were switching between modes (say, sales to services) with a sales-related
		// search string, the subsequent click would show all services instead of the bogus search string
		$_REQUEST['s'] = '';
	}

	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getAddresses.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getClass.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getSiteName.php';
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
	include_once $_SERVER["ROOT_DIR"].'/inc/getMaterialsCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getMaterialsQuote.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOutsideServices.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOutsideServicesQuote.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getUser.php';

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
	if (! $startDate) { $startDate = format_date($today,'m/d/Y',array('y'=>-1)); }

	$endDate = date('m/d/Y');
	if (isset($_REQUEST['END_DATE']) AND $_REQUEST['END_DATE']){
		$endDate = format_date($_REQUEST['END_DATE'], 'm/d/Y');
	}

	// is the user permitted for any management roles?
	$sales = array_intersect($USER_ROLES, array(5));
	$logistics = array_intersect($USER_ROLES, array(9));
	$management = array_intersect($USER_ROLES, array(1,4,7));
	if (! $management) {
		$startDate = '';
		$endDate = '';
	}

	if (! isset($_REQUEST['status']) OR ! $_REQUEST['status']) { $status = 'open'; }
	else { $status = $_REQUEST['status']; }

	// keyword trumps other filters
	if ($keyword) {
		$startDate = '';
		$endDate = '';
		$status = 'all';
	}

	$financials = false;
	$show_financials = false;
	// 4==management role
	if ($U['manager']) {
		$financials = true;
	}

	// just says whether to show the column for financials, not specifically financial info because each line may differ for the sales role
	if ($financials OR $sales) {
		$show_financials = true;
	}

	$classid = 0;
	if (isset($_REQUEST['classid']) AND $_REQUEST['classid']) { $classid = $_REQUEST['classid']; }

	$managerid = 0;
	if (isset($_REQUEST['managerid']) AND is_numeric($_REQUEST['managerid']) AND $_REQUEST['managerid']>0) {
		if ($management OR $sales) { $managerid = $_REQUEST['managerid']; }
	} else if ($U['manager']) {
		$managerid = $U['id'];
	}


	/****** FUNCTIONS ******/
	function calcServiceQuote($order_number) {
		return (0);
	}

/*
	function getSiteName($companyid, $addressid) {
		$sitename = '';

		$query = "SELECT * FROM company_addresses WHERE companyid = ".fres($companyid)." AND addressid = ".fres($addressid).";";
		$result = qedb($query);

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);

			$sitename = $r['nickname'] . '<br>';
		}

		return $sitename;
	}
*/
?>

<!------------------------------------------------------------------------------------------->
<!-------------------------------------- HEADER OUTPUT -------------------------------------->
<!------------------------------------------------------------------------------------------->
<!DOCTYPE html>
<html>
<!-- Declaration of the standard head with Services home set as title -->
<head>
	<title><?=(($dashboard == 'Quote' OR $quote) ? 'Quotes' : 'Services');?></title>
	<?php
		//Standard headers included in the function
		include_once $_SERVER["ROOT_DIR"].'/inc/scripts.php';
	?>
	<style type="text/css">
		.table td {
			vertical-align:top !important;
		}

		.scope {
			display: inline-block; 
			display: -webkit-box;  
			-webkit-line-clamp: 2; 
			-webkit-box-orient: vertical;
			overflow: hidden;
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

    <table class="table table-header table-filter">
		<tr>
		<td class = "col-md-2">
			<?php if($quote) { ?>
			<input type="hidden" name="quote" value="true">
			<?php } ?>
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
            <h2 class="minimal"><?=($quote ? 'Quotes <span class="text-purple">BETA</span>' : 'Services');?></h2>
		</td>
		<td class="col-md-1 text-center">
			<div class="input-group">
				<input type="text" name="keyword" class="form-control input-sm upper-case auto-select" value ='<?php echo $keyword?>' placeholder = "Search..." autofocus />
				<span class="input-group-btn">
					<button class="btn btn-primary btn-sm" type="submit" ><i class="fa fa-filter" aria-hidden="true"></i></button>
				</span>
			</div>
		</td>
		<td class="col-md-1">
			<select name="classid" class="class-selector form-control">
				<?php if ($classid) { echo '<option value="'.$classid.'" selected>'.getClass($classid).'</option>'.chr(10); } ?>
			</select>
		</td>
		<td class="col-md-1">
<?php
	$user_classes = '';
	$user_class_array = array();
	if ($management OR $sales) {
		// get classes for each manager so that authorized users can act on manager's behalf, just for the classes they themselves belong to
		if (! $U['admin']) {
			$query = "SELECT classid FROM user_classes WHERE userid = '".$U['id']."'; ";
			$result = qedb($query);
			while ($r = qrow($result)) {
				if ($user_classes) { $user_classes .= ','; }
				$user_classes .= $r['classid'];
				$user_class_array[$r['classid']] = $r['classid'];
			}
		}

		// get managers
		$managers = array();
		$query = "SELECT u.id, name FROM contacts c, users u, user_roles ur, user_classes uc, user_privileges up ";
		$query .= "WHERE c.id = u.contactid AND u.id = ur.userid AND u.id = uc.userid ";
		$query .= "AND ur.privilegeid = up.id AND up.privilege = 'Management' ";
		if ($user_classes) { $query .= "AND uc.classid IN (".$user_classes.") "; }
		$query .= "GROUP BY u.id ORDER BY name; ";
		$result = qedb($query);
		while ($r = qrow($result)) {
			$managers[$r['id']] = $r['name'];
		}
?>

			<select name="managerid" size="1" class="form-control input-xs select2" data-placeholder="- Managers -" style="max-width:90px">
				<option value="All">- Managers -</option>

<?php
		foreach ($managers as $id => $name) {
			echo '<option value="'.$id.'"'.($managerid==$id ? ' selected' : '').'>'.$name.'</option>'.chr(10);
		}
		if ($sales AND ! $management) { echo '<option value="0"'.(! $managerid ? ' selected' : '').'>'.getUser($U['id']).'</option>'.chr(10); }
?>
			</select>
			<button class="btn btn-primary btn-sm left" type="submit" ><i class="fa fa-filter" aria-hidden="true"></i></button>

<?php } ?>
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
//$U['id'] = 29;

	//Establish a blank array for receiving the results from the table
	$results = array();

	$rows = '';
	$total_pcs = 0;
	$total_amt = 0;

	$query = "SELECT o.*, i.* FROM ";
	// if no permissions, join the table with assignments to be sure this user is assigned in order to view
	if (! $management AND ! $managerid AND ! $logistics AND ! $sales) { $query .= "service_assignments sa, "; }
	// Create an extra bypass for the user with privilege of logistics
	// If the user is logistics and doesnt have any of the management or admin privileges then show based on their class
	if (! $management AND ! $managerid AND $logistics) { $query .= "user_classes uc, "; }
	$query .= "service_orders o, service_items i ";
	$query .= "LEFT JOIN addresses a ON (i.item_id = a.id AND i.item_label = 'addressid') ";
	$query .= "LEFT JOIN company_addresses ca ON ca.addressid = a.id ";
	$query .= "WHERE o.so_number = i.so_number ";
	// Omitt CCO AND ICO from the query
	$query .= "AND (i.ref_2_label <> 'service_item_id' OR i.ref_2_label IS NULL) ";
	if (! $management AND ! $managerid AND ! $logistics AND ! $sales) { $query .= "AND sa.userid = '".$U['id']."' AND sa.item_id = i.id AND sa.item_id_label = 'service_item_id' "; }

	// If the user is logistics and doesnt have any of the management or admin privileges then show based on their class
	if (! $management AND ! $managerid AND $logistics) { $query .= "AND o.classid = uc.classid AND uc.userid = '".$U['id']."' "; }
   	if ($keyword) {
//		$query .= "AND (i.task_name RLIKE '".$keyword."' OR a.street RLIKE '".$keyword."' OR a.city RLIKE '".$keyword."' OR o.public_notes RLIKE '".$keyword."') ";
		$query .= "AND (";
		$query .= "i.task_name RLIKE '".$keyword."' OR a.street RLIKE '".$keyword."' OR a.city RLIKE '".$keyword."' OR o.public_notes RLIKE '".$keyword."' ";
		$query .= "OR o.so_number = '".$keyword."' OR CONCAT(i.so_number,'-',i.line_number) = '".$keyword."' ";
		$query .= "OR ca.nickname RLIKE '".res($keyword)."' OR ca.alias RLIKE '".res($keyword)."' OR ca.notes RLIKE '".res($keyword)."' ";
		$query .= ") ";
	} else if ($startDate) {
   		$dbStartDate = format_date($startDate, 'Y-m-d 00:00:00');
   		$dbEndDate = format_date($endDate, 'Y-m-d 23:59:59');
   		$query .= "AND datetime BETWEEN CAST('".$dbStartDate."' AS DATETIME) AND CAST('".$dbEndDate."' AS DATETIME) ";
	}
	if ($company_filter) {
		$query .= "AND o.companyid = '".res($company_filter)."' ";
	}
	if ($classid) {
		$query .= "AND o.classid = '".res($classid)."' ";
	}
	$query .= "GROUP BY i.id ";
	$query .= "ORDER BY datetime DESC, o.so_number DESC, i.line_number ASC, task_name ASC; ";

	if($quote) {
		// Change the query to quotes only
		$query = "SELECT o.*, i.* FROM ";
		$query .= "service_quotes o, service_quote_items i ";
		$query .= "LEFT JOIN addresses a ON (i.item_id = a.id AND i.item_label = 'addressid') ";
		$query .= "LEFT JOIN company_addresses ca ON ca.addressid = a.id ";
		$query .= "WHERE o.quoteid = i.quoteid ";

		if ($keyword) {
			$query .= "AND (";
			$query .= "a.street RLIKE '".$keyword."' OR a.city RLIKE '".$keyword."' OR o.public_notes RLIKE '".$keyword."' ";
			$query .= "OR o.quoteid = '".$keyword."' OR CONCAT(i.quoteid,'-',i.line_number) = '".$keyword."' ";
			$query .= "OR ca.nickname RLIKE '".res($keyword)."' OR ca.alias RLIKE '".res($keyword)."' OR ca.notes RLIKE '".res($keyword)."' ";
			$query .= ") ";
		} else if ($startDate) {
				$dbStartDate = format_date($startDate, 'Y-m-d 00:00:00');
				$dbEndDate = format_date($endDate, 'Y-m-d 23:59:59');
				$query .= "AND datetime BETWEEN CAST('".$dbStartDate."' AS DATETIME) AND CAST('".$dbEndDate."' AS DATETIME) ";
		}
		if ($company_filter) {
			$query .= "AND companyid = '".res($company_filter)."' ";
		}
		if ($classid) {
			$query .= "AND o.classid = '".res($classid)."' ";
		}
		$query .= "GROUP BY i.id ";
		$query .= "ORDER BY datetime DESC, o.quoteid DESC, i.line_number ASC; ";
	}

	$result = qdb($query) OR die(qe().'<BR>'.$query);


	//The aggregation method of form processing. Take in the information, keyed on primary sort field,
	//will prepare the results rows to make sorting and grouping easier without having to change the results
	$summary_rows = array();

	$totalProfit = 0;
	$numJobs = 0;
	$techProfits = array();
	$techTimes = array();
	foreach ($result as $job) {
		if ($managerid>0 AND $job['sales_rep_id']<>$managerid AND ! $U['admin']) { continue; }

		$po = '';

		$assigns = array();
		$query2 = "SELECT name, userid FROM service_assignments a, users u, contacts c ";
		$query2 .= "WHERE item_id = '".$job['id']."' AND item_id_label = 'service_item_id' ";
		$query2 .= "AND u.id = a.userid AND u.contactid = c.id; ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$assigns[$r2['userid']] = array('name'=>$r2['name'],'status'=>'');
		}
		// assignments may come in the form of actual time worked before they were removed from the job
		$timesheets = array();
		$query2 = "SELECT * FROM timesheets WHERE taskid = '".$job['id']."' AND task_label = 'service_item_id'; ";
		$result2 = qdb($query2) OR die(qe().' '.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$timesheets[] = $r2;//for use below

			if (! isset($assigns[$r2['userid']])) { $assigns[$r2['userid']] = array('name'=>getUser($r2['userid']),'status'=>''); }
			if ($r2['clockin'] AND ! $r2['clockout']) {
				$assigns[$r2['userid']]['status'] = format_date($r2['clockin'],'g:ia');
			}
		}
		if (! $U['manager'] AND $sales AND $U['id']<>$job['sales_rep_id'] AND ! $logistics AND (! isset($user_class_array[$job['classid']]) OR ! isset($assigns[$U['id']]))) { continue; }

		$class = '';
		if ($job['task_name']) { $class = $job['task_name']; }
		else { $class = getClass($job['classid']); }
		if ($class=='Internal' AND $classid<>10) { continue; }

		/*** STATUS ***/
		// do this before assignments since we check the timesheet here
		$row_status = '';
		if ($job['status_code']==4) {//canceled
			if ($status<>'all') { continue; }

			$row_status = '<span class="label label-danger">Canceled</span>';
		} else if ($job['status_code']==2) {
			if ($status<>'all' AND $status<>'closed') { continue; }

			$row_status = '<span class="label label-success">Closed</span>';
		} else {
			if (count($timesheets)>0) {
				if ($status<>'all' AND $status<>'open') { continue; }

				$row_status = '<span class="label label-warning">In Progress</span>';
			}
			if ($job['status_code']==3) {
				if ($status<>'all' AND $status<>'open') { continue; }

				$row_status = '<span class="label label-danger">On Hold</span>';
			}

			if (! $row_status) {
				if ($status<>'all' AND $status<>'open') { continue; }
				$row_status = '<span class="label label-default">Idle</span>';
			}
		}

		$task_label = 'service_item_id';

		if($quote) {
			$task_label = 'service_quote_item_id';
		}

		$expenses = array();
		if ($financials OR ($sales AND $job['sales_rep_id']==$U['id'] AND isset($user_class_array[$job['classid']]))) {
			// get expenses prior to checking tech timesheets so expenses can be added to it
			$expenses = getExpenses($job['id'],$task_label);
		}

		/*** ASSIGNMENTS AND LABOR COST / PROFITS ***/
		$assignments = '';
		$laborTotal = 0;
		$laborTotalSecs = 0;
		foreach ($assigns as $techid => $a) {
			$timeLogged = '';

			if ($financials OR ($sales AND $job['sales_rep_id']==$U['id'] AND isset($user_class_array[$job['classid']]))) {
				// calculate labor costs
				list($techLaborCost,$techSecsWorked) = getTimesheet($techid,$job['id'],$task_label,'list');

				$laborTotal += $techLaborCost;
				$laborTotalSecs += $techSecsWorked;
				if (! isset($techTimes[$techid])) { $techTimes[$techid] = array(); }
				if ($techSecsWorked>0) {
					$techTimes[$techid][] = $techSecsWorked;
				}

				$timeLogged = toTime($techSecsWorked,false);
			}

			if ($a['status']) {
				$assignments .= '<span class="label label-warning">'.$a['name'].' &nbsp; '.$a['status'].'</span>';
			} else {
				$names = explode(' ',$a['name']);
				$assignments .= substr($names[0],0,1).'&nbsp;'.$names[1].'&nbsp;'.$timeLogged;
			}
			$assignments .= '<BR/>';
		}

		// Retreive the labor cost quoted from a service quote as service quotes will not have logged time in the timesheet
		if($quote) {
			// Using job id get the quote labor hours and labor rate and add it to the labor totals
			$query = "SELECT labor_hours, labor_rate FROM service_quote_items WHERE id = ".res($job['id']).";";
			$result = qedb($query);

			if(qnum($result)) {
				$row = qrow($result);

				$laborTotal = ($row['labor_rate'] * $row['labor_hours']);
				$laborTotalSecs = ($row['labor_hours'] * 60 * 60);
			}
		}

		/***** CALCULATE JOB FINANCIALS TOTALS *****/
		$financial_col = '';
		if ($financials OR ($sales AND $job['sales_rep_id']==$U['id'] AND isset($user_class_array[$job['classid']]))) {
			// don't sum expenses until now, after getting tech's timesheets for any mileage reimbursements
			$expensesTotal = 0;
			foreach ($expenses as $e) { $expensesTotal += $e['amount']; }

			$materialsTotal = 0;
			if($quote) {
				$gmc = getMaterialsQuote($job['id']);
				$materialsTotal = $gmc;
			} else {
				$gmc = getMaterialsCost($job['id'],$task_label);
				$materialsTotal = $gmc['cost'];
			}
			//foreach ($materials as $m) { $materialsTotal += $m['cost']; }

			$outsideTotal = 0;
			if($quote) {
				$outsideTotal = getOutsideServicesQuote($job['id']);
			} else {
				$outsideServices = getOutsideServices($job['so_number'],'Service');
				foreach ($outsideServices as $o) { $outsideTotal += $o['cost']; }
			}

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
		} else if ($sales) {//create an empty column for financials in case another row for this $sales user matches classid above and shows financials
			$financial_col = '
                                <td class="text-right">
                                </td>
			';
		}

		if ($job['item_id']) {
			$address = address_out($job['item_id']);
		} else { // for legacy data
			$address = $job['public_notes'];
		}

		if($quote) {

			// Check if there is a task linked to this quote already
			$query3 = "SELECT i.*, o.classid FROM service_items i, service_orders o WHERE quote_item_id = ".res($job['id'])." AND i.so_number = o.so_number;";
			$result3 = qedb($query3);

			$statusValue = 'Pending';

			// Check if the materials have all been sourcing requested or partially or none
			$query4 = "SELECT partid, SUM(qty) totalOrdered FROM service_quote_materials WHERE quote_item_id = ".res($job['id'])." GROUP BY partid;";
			$result4 = qedb($query4);

			while($r4 = mysqli_fetch_assoc($result4)) {
				// Fetch each line of materials
				// get all of the same partid requested for the exact order and sum it together
				$query5 = "SELECT SUM(qty) totalRequested FROM purchase_requests WHERE item_id_label = 'quote_item_id' AND item_id = ".res($job['id'])." and partid = ".$r4['partid']." GROUP BY partid;";
				$result5 = qedb($query5);

				// echo $query5;
				// die();

				if(mysqli_num_rows($result5)) {
					
					$r5 = mysqli_fetch_assoc($result5);
					if($r5['totalRequested'] >= $r4['totalOrdered'] AND $statusValue != "Partial") {
						//$statusValue .= $r5['totalRequested'] . ' ' . $r4['totalOrdered'] . ' tes ';
						$statusValue = "Sourced";
					}
				} else {
					if($statusValue == "Sourced") {
						$statusValue = "Partial";
					}
				}

			}

			if($statusValue == 'Sourced') {
				$row_status = '<span class="label label-success">'.$statusValue.'</span>';
			} else if($statusValue == 'Partial') {
				$row_status = '<span class="label label-warning">'.$statusValue.'</span>';
			} else {
				$row_status = '<span class="label label-default">'.$statusValue.'</span>';
			}


			$rows .= '
	                            <!-- row -->
	                            <tr>
	                                <td>
	                                    <span class="hidden-xs hidden-sm">'.format_date($job['datetime'],'M j, Y').'</span>
	                                    <span class="hidden-md hidden-lg"><small>'.format_date($job['datetime'],'n/j/y').'</small></span>
	                                </td>
	                                <td class="word-wrap160">
	                                    '.$class . ' '.$job['quoteid'].'-'.$job['line_number'].'
	                                    <a href="/service_quote.php?taskid='.$job['id'].'"><i class="fa fa-arrow-right"></i></a><br/>
	                                </td> 
	                                <td>';

	        if(mysqli_num_rows($result3)) {
				$r3 = mysqli_fetch_assoc($result3);
				$rows .= '			'.getClass($r3['classid']).' '.$r3['so_number'].'-'.$r3['line_number'].'
									<a href="service.php?taskid='.$r3['id'].'&order_type=Service"><i class="fa fa-arrow-right"></i></a><br/>';
			}

	        $rows .= '
	                                </td>
	                                <td class="word-wrap160">
	                                    '.getCompany($job['companyid']).'
		                                <a href="profile.php?companyid='.$job['companyid'].'"><i class="fa fa-building"></i></a><br/>
	                                    '.getContact($job['contactid']).'
	                                </td>
	                                <td>
		                                '.getSiteName($job['companyid'], $job['item_id']).'
										<p class="info scope">'.$job['description'].'</p>
	                                </td>
	                                <td>
	                                    '.getUser($job['userid']).'
	                                </td>
									'.$financial_col.'
	                                <td class="text-center hidden-xs hidden-sm">
										'.$row_status.'
	                                </td>

	                            </tr>
			';
		} else  {

			$rows .= '
	                            <!-- row -->
	                            <tr>
	                                <td>
	                                    <span class="hidden-xs hidden-sm">'.format_date($job['datetime'],'M j, Y').'</span>
	                                    <span class="hidden-md hidden-lg"><small>'.format_date($job['datetime'],'n/j/y').'</small></span>
	                                </td>
	                                <td class="word-wrap160">
	                                    '.$class . ' '.$job['so_number'].'-'.$job['line_number'].'
	                                    <a href="service.php?taskid='.$job['id'].'&order_type=Service"><i class="fa fa-arrow-right"></i></a><br/>
	                                </td> 
	                                <td class="word-wrap160">
		                                <a href="profile.php?companyid='.$job['companyid'].'"><i class="fa fa-building"></i></a>
	                                    '.getCompany($job['companyid']).'<br/>
	                                    '.($job['contactid'] ? '<i class="fa fa-user"></i> '.getContact($job['contactid']) : '').'
	                                </td>
	                                <td>
		                                '.getSiteName($job['companyid'], $job['item_id']).'
										<p class="info scope">'.$job['description'].'</p>
	                                </td>
	                                <td class="hidden-xs hidden-sm">
	                                    '.$job['cust_ref'].'
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
	}

	if ($financials AND $management) {
		$numTechs = count($techTimes);
		$rem = $numTechs%4;
		for ($i=0; $i<(4-$rem); $i++) {
			$techTimes['null'.$i] = array();
		}

		$stats_rows = '';
		$i = 0;
		foreach ($techTimes as $techid => $r) {
			$tech = '';
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
						<span class="info">'.getUser($techid).'</span>
                    </div>
					'.$jobs.'
                </div>
			';
			$i++;
		}
?>

	<?php if(! $quote) { ?>

        <!-- upper main stats -->
        <div id="main-stats">
            <div class="row stats-row">
				<?php echo $stats_rows; ?>
            </div>
        </div>
        <!-- end upper main stats -->

		<hr>
	<?php } ?>

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
                                <?php if($quote) { ?>
                                <th class="col-sm-1">
                                    <span class="line"></span>
									Quote
                                </th>
                                <?php } ?>
                                <th class="col-sm-1">
                                    <span class="line"></span>
									Task
                                </th>
                                <th class="col-sm-1 hidden-xs hidden-sm">
                                    <span class="line"></span>
									Company
                                </th>
                                <th class="col-sm-3">
                                    <span class="line"></span>
                                    <span class="hidden-xs hidden-sm">Description</span>
                                    <span class="hidden-md hidden-lg">Descr</span>
                                </th>
                                <?php if(! $quote) { ?>
                                <th class="col-sm-1 hidden-xs hidden-sm">
                                    <span class="line"></span>
                                    PO#
                                </th>
                                <th class="col-sm-1">
                                    <span class="line"></span>
                                    <span class="hidden-xs hidden-sm">Due Date</span>
                                    <span class="hidden-md hidden-lg">Due</span>
                                </th>
                                <?php } ?>
                                <th class="col-sm-1">
                                    <span class="line"></span>
                                    <span class="hidden-xs hidden-sm">Manager</span>
                                    <span class="hidden-md hidden-lg">Mgr</span>
                                </th>
                                <?php if(! $quote) { ?>
                                <th class="col-sm-1">
                                    <span class="line"></span>
                                    <span class="hidden-xs hidden-sm">Assignments</span>
                                    <span class="hidden-md hidden-lg">Techs</span>
                                </th>
                                <?php } ?>
<?php if ($show_financials) { ?>
                                <th class="col-sm-1 text-center">
                                    <span class="line"></span>
									Financials
                                </th>
<?php } ?>
                                <th class="col-sm-1 text-center hidden-xs hidden-sm">
                                    <span class="line"></span>
                                    <?=($quote ? 'Materials' : '')?> Status
                                </th>
                            </tr>
                        </thead>
                        <tbody>
							<?= ($rows ? $rows : '<tr><td colspan="11" class="text-center">- There are no tasks available -</td></tr>'); ?>
<?php if ($show_financials AND ! $quote) { ?>
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
                                <td></td>
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
    </script>

</body>
</html>
