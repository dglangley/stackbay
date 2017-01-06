<!DOCTYPE html>
<html lang="en">
<head>
	<title>Job</title>
	<?php
		include_once 'inc/scripts.php';
	?>
</head>
<body>

	<?php include_once 'inc/navbar.php'; ?>

    <div id="pad-wrapper">

<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/svcs_pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/htmlcp1252.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';

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

	$PROFILES = array();
	function getProfile($id,$input='id',$output='fullname') {
		global $PROFILES;

		if (! $id) { return false; }

		if (isset($PROFILES[$id][$input])) { return ($PROFILES[$id][$input][$output]); }

		$PROFILES[$id][$input] = array($output=>false);
		$query = "SELECT * FROM services_userprofile WHERE id = '".$id."'; ";
		$result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query);
		if (mysqli_num_rows($result)>0) {
			$PROFILES[$id][$input] = mysqli_fetch_assoc($result);
		}
		return ($PROFILES[$id][$input][$output]);
	}

	function toTime($secs) {
		// given $secs seconds, what is the time g:i:s format?
		$hours = floor($secs/3600);

		// what are the remainder of seconds after taking out hours above?
		$secs -= ($hours*3600);

		$mins = floor($secs/60);

		$secs -= ($mins*60);

		return (str_pad($hours,2,0,STR_PAD_LEFT).':'.str_pad($mins,2,0,STR_PAD_LEFT).':'.str_pad($secs,2,0,STR_PAD_LEFT));
	}

	function timeToStr($time) {
		$t = explode(':',$time);
		$hours = $t[0];
		$mins = $t[1];
		if (! $mins) { $mins = 0; }
		$secs = $t[2];
		if (! $secs) { $secs = 0; }

		$days = floor($hours/24);
		$hours -= ($days*24);

		$str = '';
		if ($days>0) { $str .= $days.' d, '; }
		if ($hours>0 OR $str) { $str .= (int)$hours.' h, '; }
		if ($mins>0 OR $str) { $str .= (int)$mins.' m, '; }
		if ($secs>0 OR $str) { $str .= (int)$secs.' s'; }

		return ($str);
	}

	function calcTimeDiff($datetime_start,$datetime_end) {
		$timeStart = new DateTime($datetime_start);
		$timeEnd = new DateTime($datetime_end);
		$diff = $timeStart->diff($timeEnd);

		$hours = $diff->format("%H");
		$mins = $diff->format("%I");
		$secs = $diff->format("%S");
		$diff_in_secs = ($hours*3600)+($mins*60)+$secs;

		return ($diff_in_secs);
	}

	$WEEK_SECS = 60*60*40;
	$DAY_SECS = 60*60*8;
	$DT_SECS = 60*60*12;
	$OT = array();
	function calcOT($techid,$weekStart,$weekEnd,$shiftid=0) {
		global $OT,$WEEK_SECS,$DAY_SECS,$DT_SECS,$WORKDAY_START,$WORKDAY_END;

		if (! isset($OT[$techid])) { $OT[$techid] = array(); }
		if (! isset($OT[$techid][$weekStart])) {
			$OT[$techid][$weekStart] = array('shifts'=>array(),'total'=>0);

			$cumDaySecs = array();//keeps cumulative day-keyed seconds worked
			$cumReg = 0;//cumulative regular pay work seconds
			$cumOT = 0;//cumulative OT pay work seconds, whether over 8hr in a day or over 40hr (40 non-OT hours) in a week
			$cumDT = 0;//cumulative double-time pay work seconds (over 12hrs in a day)

			$shifts = array();
			// Query timesheet and get credited workdate based on the start time - currently, Brian is counting 7pm as the start time for the following work date.
			// Aaron FTW on the CASE statement! 1/5/17
			$query2 = "SELECT *, CASE WHEN SUBSTRING(datetime_in,12,2) >= '".$WORKDAY_START."' ";
			$query2 .= "THEN DATE_SUB(LEFT(datetime_in,10), INTERVAL -1 DAY) ELSE LEFT(datetime_in,10) END workdate ";
			$query2 .= "FROM services_techtimesheet WHERE tech_id = '".$techid."' ";
			// get any shift that either started BEFORE this week's start and ended within this week (ie, sat night to sun morning),
			// OR started within the week (and subsequently ended within the week)
			$query2 .= "AND ( (datetime_in < CAST('".$weekStart."' AS DATETIME) AND datetime_out > CAST('".$weekStart."' AS DATETIME)) ";
			$query2 .= "OR (datetime_in >= CAST('".$weekStart."' AS DATETIME) AND datetime_out <= CAST('".$weekEnd."' AS DATETIME)) ); ";
			$result2 = qdb($query2,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query2);
			while ($r2 = mysqli_fetch_assoc($result2)) {
				// if date start precedes week start, or date end runs over week end, set to those parameters
				if ($r2['datetime_in']<$weekStart) { $r2['datetime_in'] = $weekStart; }
				if ($r2['datetime_out']>$weekEnd) { $r2['datetime_out'] = $weekEnd; }

				$workdate_end = format_date($r2['workdate'],'Y-m-d',array('d'=>1));
				// if the shift ends after the 24-hour period beginning at the workdate, credit the shift
				// to workdate until the end of that window, then split the remaining shift into the following date
				if ($r2['datetime_out']>$workdate_end) {
					$datetime_out = $r2['datetime_out'];//save for use on the split shift
					$r2['datetime_out'] = $workdate_end.' '.$WORKDAY_END.':59:59';
					$shifts[] = $r2;

					// now prep for adding to $shifts[] below
					$r2['workdate'] = $workdate_end;
					$r2['datetime_in'] = $workdate_end.' '.$WORKDAY_START.':00:00';
					$r2['datetime_out'] = $datetime_out;
				}

				$shifts[] = $r2;
			}

			foreach ($shifts as $r2) {
				$datetime_in = $r2['datetime_in'];
				$datetime_out = $r2['datetime_out'];
				if (! isset($OT[$techid][$weekStart]['shifts'][$r2['id']])) { $OT[$techid][$weekStart]['shifts'][$r2['id']] = 0; }
				if (! isset($cumDaySecs[$r2['workdate']])) { $cumDaySecs[$r2['workdate']] = 0; }
//				echo $r2['datetime_in'].' to '.$r2['datetime_out'].' (workdate: '.$r2['workdate'].')<BR>';

				$shiftSecs = calcTimeDiff($datetime_in,$datetime_out);

				$regSecs = $shiftSecs;//by default, the entire shift is regular pay
				$otSecs = 0;
				$dtSecs = 0;

				// accumulate time toward's 40hr/wk max, but only up to a standard 8hr day per day
				if ($shiftSecs>$DAY_SECS) {
					$regSecs = $DAY_SECS;//add up to the 8hrs as regular pay
					if ($shiftSecs>$DT_SECS) {//if over 12hrs, start counting as double time
						$otSecs = $DT_SECS-$DAY_SECS;
						$dtSecs = $shiftSecs-$DT_SECS;
					} else {
						$otSecs = $shiftSecs-$DAY_SECS;//add the overage as OT time
					}
				}

				// have we already surpassed a regular week's pay into overtime? if so, count the entire shift as OT
				if (($regSecs+$cumReg)>$WEEK_SECS) {
					$otSecs += ($regSecs+$cumReg)-$WEEK_SECS;
					$regSecs = $WEEK_SECS-$cumReg;
				}

				if (($regSecs+$cumDaySecs[$r2['workdate']])>$DAY_SECS) {
					$thisOT = ($regSecs+$cumDaySecs[$r2['workdate']])-$DAY_SECS;
					$regSecs -= $thisOT;
					$otSecs += $thisOT;
					$cumDaySecs[$r2['workdate']] = $DAY_SECS;//if spilling over, set to max for a single day, then allot remaining balance as OT
				} else {
					$cumDaySecs[$r2['workdate']] += $regSecs;
				}

				$cumReg += $regSecs;
				$cumOT += $otSecs;
				$cumDT += $dtSecs;

				// capture all OT data into array for easy-lookup and eliminating re-querying later
				$OT[$techid][$weekStart]['shifts'][$r2['id']] += $otSecs;
				$OT[$techid][$weekStart]['total'] += $otSecs;
			}
		}

		if ($shiftid) {
//			echo $weekStart.' to '.$weekEnd.': Work Secs '.toTime($regSecs).' (Shift OT: '.toTime($OT[$techid][$weekStart]['shifts'][$shiftid]).')<BR>';
			if (isset($OT[$techid][$weekStart]['shifts'][$shiftid])) {
				return ($OT[$techid][$weekStart]['shifts'][$shiftid]);
			} else {
				return 0;
			}
		} else {
//			echo $weekStart.' to '.$weekEnd.': Work Secs '.toTime($regSecs).' (Week OT: '.toTime($OT[$techid][$weekStart]['total']).')<BR>';
			return ($OT[$techid][$weekStart]['total']);
		}
	}

	$jobid = 0;
	if (isset($_REQUEST['id']) AND is_numeric($_REQUEST['id']) AND $_REQUEST['id']>0) { $jobid = $_REQUEST['id']; }

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

		$job_out = ' #'.$job['job_no'];
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
?>

<div class="container">
    <section class="margin-bottom">
        <div class="row">
            <div class="col-md-12">
                <h2 class="right-line">Job<?php echo $job_out; ?></h2>
            </div>
		</div>
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
            <div class="col-md-3 col-sm-6">
                <div class="text-icon">
                    <span class="icon-ar icon-ar-sm bg-info"><i class="fa fa-hashtag"></i></span>
                    <div class="text-icon-content">
                        <h5 class="no-margin"><?php echo $job['customer_job_no']; ?></h5>
                        <p class="info"><small>Project ID</small></p>
                    </div>
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
                        <h5 class="no-margin"><?php echo format_date($job['scheduled_date_of_work'],'D M d, Y'); ?></h5>
                        <p class="info"><small>Scheduled Work</small></p>
                    </div>
                    <span class="icon-ar icon-ar-sm fa-stack bg-brown"><i class="fa fa-calendar-o fa-stack-2x fa-stack-md"></i><span class="fa-stack-xs calendar-text"><?php echo format_date($job['scheduled_completion_date'],'d'); ?></span></span>
                    <div class="text-icon-content">
                        <h5 class="no-margin"><?php echo format_date($job['scheduled_completion_date'],'D M d, Y'); ?></h5>
                        <p class="info"><small>Scheduled Completion</small></p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6">
                <div class="text-icon">
                    <span class="icon-ar icon-ar-sm bg-<?php echo $job['status_color']; ?>"><i class="fa fa-<?php echo $job['status_flag']; ?>"></i></span>
                    <div class="text-icon-content">
                        <h5 class="no-margin text-<?php echo $job['status_color']; ?>"><?php echo $job['status']; ?></h5>
                        <p class="info"><small>Status</small></p>
                    </div>
                </div>
            </div>
		</div>
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <div class="text-icon">
                    <span class="icon-ar icon-ar-sm bg-brown"><i class="fa fa-map-marker"></i></span>
                    <div class="text-icon-content">
						<?php if ($contact) { echo '<h5 class="no-margin">'.$contact.'</h5><p class="info"><small>Contact</small></p>'; } else { echo '<br/><br/><br/>'; } ?>
                        <h5 class="no-margin"><?php echo str_replace(chr(10),'<BR>',$job['site_access_info_address']); ?></h5>
                    </div>
                </div>
            </div>
<?php
	// determine which column should be wider based on content: description or special instructions
	$col1 = '4';//description
	$col2 = '5';//special instructions
	$job_strlen = strlen($job['description']);
	$ins_strlen = strlen($special_instructions);
	if ($job_strlen==0) { $col1 = '2'; $col2 = '7'; }
	else if ($ins_strlen==0) { $col1 = '7'; $col2 = '2'; }
	else if ($job_strlen>$ins_strlen) { $col1 = '5'; $col2 = '4'; }
?>
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

<?php
	/***** MATERIALS DATA *****/
	$material_rows = '';
	$materialTotal = 0;
	$query = "SELECT * FROM services_component c, services_jobbulkinventory jbi ";
	$query .= "LEFT JOIN services_jobmaterialpo jpo ON jbi.po_id = jpo.id ";
	$query .= "WHERE job_id = '".$job['id']."' AND component_id = c.id; ";
	$result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		// truncate to 2-decimals if not more than 2 are used
		$cost = format_price($r['cost'],true,' ');
		$addl_cost = format_price($r['addtl_cost'],true,' ');
		$vendor = getVendor($r['vendor_id']);
		$materialTotal += ($r['cost']+$r['addtl_cost']);
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
	$query = "SELECT * FROM services_jobtasks WHERE job_id = '".$job['id']."'; ";
	$result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$labor[$r['assigned_to_id']][] = $r;
	}

	$WORKDAY_START = 19;
	if ($WORKDAY_START==0) { $WORKDAY_END = 23; }
	else { $WORKDAY_END = $WORKDAY_START-1; }
	$WORKDAY_START = str_pad($WORKDAY_START,2,0,STR_PAD_LEFT);
	$WORKDAY_END = str_pad($WORKDAY_END,2,0,STR_PAD_LEFT);

	$expenses = array();
	$laborTotal = 0;
	$laborTotalSecs = 0;
	$labor_rows = '';
	foreach ($labor as $techid => $arr) {
//		if ($techid<>46) { continue; }

		$cumLabor = 0;
		$cumSecs = 0;
$OTSecs = 0;
$stdSecs = 0;
$secsStd = 0;
$secsOt = 0;
$secsPd = 0;
$stdPayTotal = 0;
$otPayTotal = 0;
$pdPayTotal = 0;
		$query = "SELECT *, DATE_SUB(LEFT(datetime_in,10), INTERVAL DAYOFWEEK(datetime_in)-1 DAY) start_day, ";
		//$query .= "DATE_SUB(LEFT(datetime_in,10), INTERVAL DAYOFWEEK(datetime_in)-7 DAY) end_day ";
		$query .= "DATE_SUB(LEFT(datetime_in,10), INTERVAL DAYOFWEEK(datetime_in)-8 DAY) end_day ";
		$query .= "FROM services_techtimesheet WHERE job_id = '".$job['id']."' AND tech_id = '".$techid."'; ";
		$result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$weekStart = $r['start_day'].' '.$WORKDAY_START.':00:00';
			$weekEnd = $r['end_day'].' '.$WORKDAY_END.':59:59';
			$shiftid = $r['id'];

			$secsDiff = calcTimeDiff($r['datetime_in'],$r['datetime_out']);

			// OT seconds of this shift within the scope of a week's work
			$OTSecs = calcOT($techid,$weekStart,$weekEnd,$r['id']);
$secsStd += $secsDiff-$OTSecs;
$secsOT += $OTSecs;

			$cumSecs += $secsDiff;
			$laborTotalSecs += $secsDiff;

			$tech_rate = $r['tech_rate'];
			$rate_in_secs = $tech_rate/3600;

			$stdSecs = ($secsDiff-$OTSecs);
			$stdPay = ($rate_in_secs*$stdSecs);
$stdPayTotal += $stdPay;
			$otPay = ($rate_in_secs*$OTSecs)*1.5;
$otPayTotal += $otPay;
			$cumLabor += ($stdPay+$otPay)*1.13;
			if (! $r['no_perdiem']) {
$secsPd += $stdSecs;
				// no perdiem for overtime
				$pdPay = (($r['tech_perdiem']/3600)*$stdSecs);
$pdPayTotal += $pdPay;
				$cumLabor += $pdPay;
			}

			// add mileage to expenses (Brian had it under Tasks, which I now called Labor)
			if ($r['mileage']>0) {
				$expenses[] = array(
					'timestamp'=>$r['datetime_in'],
					'entered_by_id'=>$techid,
					'job_id'=>$job['id'],
					'tech_id'=>$techid,
					'label'=>'Mileage: '.$r['notes'],
					'date'=>substr($r['datetime_in'],0,10),
					'amount'=>($r['mileage']*$job['mileage_rate']),
					'file'=>'',
					'vendor'=>'',
				);
			}
		}
		$assignments = '';
		foreach ($arr as $task) {
			$assignments .= '
				'.format_date($task['date_scheduled'],'M d, Y').' to '.format_date($task['date_scheduled_complete'],'M d, Y').'<br/>
			';
		}

		$cumLabor = round($cumLabor,2);

//date_scheduled, date_scheduled_complete, admin_completed, name, notes, employee_completed
		$time_logged = toTime($cumSecs);
		$laborTotal += $cumLabor;
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
									'.$time_logged.' &nbsp; ('.timeToStr($time_logged).')
<!--
<BR>std '.toTime($secsStd).' = '.$stdPayTotal.'<BR>
OT '.toTime($secsOt).' = '.$otPayTotal.'<BR>
PD '.toTime($secsPd).' = '.$pdPayTotal.'<BR>
-->
                                </td>
                                <td class="text-right">
									'.format_price($cumLabor,true,' ').'
                                </td>
                                <td>
                                </td>
                                <td>
                                </td>
                            </tr>
		';
	}
	$laborTotalTime = toTime($laborTotalSecs);

	$query = "SELECT * FROM services_expense WHERE job_id = '".$job['id']."'; ";
	$result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$expenses[] = $r;
	}

	// sort by date
	function cmp($a, $b) {
		if ($a['timestamp'] == $b['timestamp']) {
			return 0;
		}
		return ($a['timestamp'] > $b['timestamp']) ? 1 : -1;
	}
	uasort($expenses,'cmp');

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
							</tr>
		';
	}

	$expensesTotal = round($expensesTotal,2);
?>

    <section>
			<!-- Nav tabs -->
			<ul class="nav nav-tabs nav-tabs-ar">
				<li class="active"><a href="#labor" data-toggle="tab"><i class="fa fa-users"></i> Labor</a></li>
				<li><a href="#materials" data-toggle="tab"><i class="fa fa-list"></i> Materials</a></li>
				<li><a href="#expenses" data-toggle="tab"><i class="fa fa-credit-card"></i> Expenses</a></li>
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
                                    <strong><?php echo format_price($materialTotal,true,' '); ?></strong>
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
                                <th class="col-md-3">
                                    Assignments
                                </th>
                                <th class="col-md-3">
                                    Total Hours Logged
                                </th>
                                <th class="col-md-1">
                                    Cost
                                </th>
                                <th class="col-md-1">
                                    Completed?
                                </th>
                                <th class="col-md-1">
                                    Admin?
                                </th>
                            </tr>
                        </thead>
                        <tbody>
							<?php echo $labor_rows; ?>
                            <!-- row -->
                            <tr class="first">
                                <td colspan="3">
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
                                <th class="col-md-2">
                                    Date
                                </th>
                                <th class="col-md-6">
                                    Description
                                </th>
                                <th class="col-md-2">
                                    Amount
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
                                <td colspan="6">
                                </td>
                            </tr>
						</tbody>
					</table>
				</div><!-- Expenses pane -->

			</div><!-- tab-content -->
    </section>
</div> <!-- container -->

</div> <!-- pad-wrapper -->

<?php include_once 'inc/footer.php'; ?>

</body>
</html>
