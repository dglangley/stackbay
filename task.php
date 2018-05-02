<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	// Getter Tools
	include_once $_SERVER["ROOT_DIR"].'/inc/getContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getUser.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getFinancialAccounts.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getCategory.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';

	// Formatting tools
	include_once $_SERVER["ROOT_DIR"].'/inc/format_address.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';

	// Timesheet tool to calculate the users time on this specific job
	include_once $_SERVER['ROOT_DIR'] . '/inc/newTimesheet.php';
	include_once $_SERVER['ROOT_DIR'] . '/inc/payroll.php';

	// Clocker tool
	include_once $_SERVER["ROOT_DIR"] . '/inc/is_clockedin.php';

	// Set GLOBAL Costs used through this page
	$SERVICE_LABOR_COST = 0.00;
	$SERVICE_MATERIAL_COST = 0.00;
	$SERVICE_OUTSIDE_COST = 0.00;
	$SERVICE_EXPENSE_COST = 0.00;
	$SERVICE_TOTAL_COST = 0.00;


	// Depict here the users access
	$manager_access = array_intersect($USER_ROLES,array(1,4));

	//Bypass tool for quotes and sales
	if($quote AND array_intersect($USER_ROLES,array(5))) {
		$manager_access = true;
	}

	// Depict the accounting access
	$accounting_access = array_intersect($USER_ROLES,array(7));

	// TABS 
	$SERVICE_TABS = array();
	$ACTIVE = ($_REQUEST['tab']?:'activity');
	// Generate an example for tabs
	$SERVICE_TABS[] = array('name' => 'Activity', 'icon' => 'fa-folder-open-o', 'price' => '', 'id' => 'activity');
	$SERVICE_TABS[] = array('name' => 'Details', 'icon' => 'fa-list', 'price' => '', 'id' => 'details');
	$SERVICE_TABS[] = array('name' => 'Documentation', 'icon' => 'fa-file-pdf-o', 'price' => '', 'id' => 'documentation');
	$SERVICE_TABS[] = array('name' => 'Labor', 'icon' => 'fa-users', 'price' => 'SERVICE_LABOR_COST', 'id' => 'labor');
	$SERVICE_TABS[] = array('name' => 'Materials', 'icon' => 'fa-microchip', 'price' => 'SERVICE_MATERIAL_COST', 'id' => 'materials');
	$SERVICE_TABS[] = array('name' => 'Expenses', 'icon' => 'fa-credit-card', 'price' => 'SERVICE_EXPENSE_COST', 'id' => 'expenses');
	$SERVICE_TABS[] = array('name' => 'Outside Services', 'icon' => 'fa-suitcase', 'price' => 'SERVICE_OUTSIDE_COST', 'id' => 'outside');
	$SERVICE_TABS[] = array('name' => 'Images', 'icon' => 'fa-file-image-o', 'price' => '', 'id' => 'images');
	$SERVICE_TABS[] = array('name' => 'Total', 'icon' => 'fa-shopping-cart', 'price' => 'SERVICE_TOTAL_COST', 'id' => 'total');
	
	// print '<pre>' . print_r($ORDER_DETAILS) . '</pre>';

	// Get the activities tied to this item_id and type
	function getActivities() {
		$notes = array();

		global $ORDER_DETAILS, $T;

		$query = "
			SELECT activity_log.id, userid techid, datetime, notes FROM activity_log WHERE item_id = '".res($ORDER_DETAILS['id'])."' AND item_id_label = '".res($T['item_label'])."'
			UNION
			SELECT '' as id, '' as techid, i.date_created as datetime, CONCAT('Component <b>', p.part, '</b> Received') FROM purchase_requests pr, purchase_items pi, parts p, inventory i WHERE pr.item_id = ".fres($ORDER_DETAILS['id'])." AND pr.item_id_label = ".fres($T['item_label'])." AND pr.po_number = pi.po_number AND pr.partid = pi.partid AND pi.qty <= pi.qty_received AND p.id = pi.partid AND i.purchase_item_id = pi.id
			UNION
			SELECT '' as id, '' as techid, pr.requested as datetime, CONCAT('Component <b>', p.part, '</b> Requested') FROM purchase_requests pr, parts p WHERE pr.item_id = ".fres($ORDER_DETAILS['id'])." AND pr.item_id_label = ".fres($T['item_label'])." AND pr.partid = p.id";

		// These are notes pertaining to repair items and having certain components received or the item scanned in for repair
		if($T['type'] == 'Repair') {
			$query .= "	
				UNION
				SELECT '' as id, '' as techid, i.date_created as datetime, CONCAT('Component Received ', `partid`, ' Qty: ', qty ) as notes FROM inventory i WHERE i.repair_item_id = ".fres($ORDER_DETAILS['id'])." AND serial_no IS NULL
				UNION
				SELECT '' as id, created_by as techid, created as datetime, CONCAT('".$T['type']." Order Created') as notes FROM repair_orders WHERE ro_number = ".fres($ORDER_DETAILS[$T['order']])."
				UNION
				SELECT '' as id, userid as techid, date_created as datetime, CONCAT('Received ".$T['type']." Serial: <b>', serial_no, '</b>') as notes FROM inventory WHERE id in (SELECT invid FROM inventory_history where field_changed = 'repair_item_id' and `value` = ".fres($ORDER_DETAILS['id']).") AND serial_no IS NOT NULL
				UNION
				SELECT '' as id, '' as techid, datetime as datetime, CONCAT('Tracking# ', IFNULL(tracking_no, 'N/A')) as notes FROM packages WHERE order_number = ".fres($ORDER_DETAILS[$T['order']])." AND order_type = 'Repair'
				UNION
				SELECT '' as id, '' as techid, datetime as datetime, CONCAT('<b>', part, '</b> pulled to Order') as notes FROM repair_components, inventory, parts WHERE ro_number = ".fres($ORDER_DETAILS[$T['order']])." AND inventory.id = repair_components.invid AND parts.id = inventory.partid
			";
		}

		$query .= "
			ORDER BY datetime DESC;
		";

		$query = "
				SELECT activity_log.id, userid techid, datetime, notes FROM activity_log WHERE item_id = '".res($ORDER_DETAILS['id'])."' AND item_id_label = '".res($T['item_label'])."'
				UNION
				SELECT '' as id, '' as techid, i.date_created as datetime, CONCAT('Component <b>', p.part, '</b> Received') FROM purchase_requests pr, purchase_items pi, parts p, inventory i WHERE pr.item_id = ".fres($ORDER_DETAILS['id'])." AND pr.item_id_label = ".fres($T['item_label'])." AND pr.po_number = pi.po_number AND pr.partid = pi.partid AND pi.qty <= pi.qty_received AND p.id = pi.partid AND i.purchase_item_id = pi.id
				UNION
				SELECT '' as id, '' as techid, pr.requested as datetime, CONCAT('Component <b>', p.part, '</b> Requested') FROM purchase_requests pr, parts p WHERE pr.item_id = ".fres($ORDER_DETAILS['id'])." AND pr.item_id_label = ".fres($T['item_label'])." AND pr.partid = p.id
				ORDER BY datetime DESC;";

		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$notes[] = $r;
		}

		return $notes;
	}

	function mainStats() {
		$statsHTML = '';

		if($GLOBALS['manager_access']) {
			$statsHTML = '<div id="main-stats">
				            <div class="row stats-row">
				        		<div class="col-md-2 col-sm-2 stat">
					                <div class="data">
					                    <span class="number text-gray">$'.number_format($GLOBALS['SERVICE_MATERIAL_COST'], 2, '.', '').'</span>
					                    <br>
										<span class="info">Total Materials</span>
					                </div>
					            </div>	

					            <div class="col-md-2 col-sm-2 stat">
					                <div class="data">
					                    <span class="number text-gray">$'.number_format($GLOBALS['SERVICE_LABOR_COST'], 2, '.', '').'</span>
					                    <br>
										<span class="info">Total Labor</span>
					                </div>
					            </div>
								
						        <div class="col-md-2 col-sm-2 stat">
						            <div class="data" style="min-height: 35px;">
				                    	<span class="number text-brown">$'.number_format($GLOBALS['SERVICE_EXPENSE_COST'], 2, '.', '').'</span>
					                	<br>
					                	<span class="info">Total Charge</span>
				                    </div>
						        </div>
						        <div class="col-md-3 col-sm-3 stat">
						            <div class="data">
						                <span class="number text-black">$'.number_format($GLOBALS['SERVICE_TOTAL_COST'], 2, '.', '').'</span>
						                <br>
										<span class="info">Total Cost</span>
						            </div>
						        </div>

						        <div class="col-md-3 col-sm-3 stat last">
						            <div class="data">
						                <span class="number text-success">$'.number_format($GLOBALS['SERVICE_TOTAL_COST'], 2, '.', '').'</span>
						                <br>
										<span class="info">Total Profit</span>
						            </div>
						        </div>
						    </div>
						</div>';
		}

		return $statsHTML;
	}

	function buildTabHeader($SERVICE_TABS, $active = '') {
		// $SERVICE_TABS contains first the tab name then the tab icon eg `fa-file-pdf-o` and price if there is one
		// $SERVICE_TABS = array(name, icon, price, array)

		$rowHTML = '
			<ul class="nav nav-tabs nav-tabs-ar">';

		$counter = 1;
		$length = count($SERVICE_TABS);

		// Moved here due to hierachy to allow the cost to update correctly
		$contentHTML = buildTabContent($SERVICE_TABS, $active);

		// Calculate the total
		$GLOBALS['SERVICE_TOTAL_COST'] = $GLOBALS['SERVICE_LABOR_COST'] + $GLOBALS['SERVICE_MATERIAL_COST'] + $GLOBALS['SERVICE_EXPENSE_COST'] + $GLOBALS['SERVICE_OUTSIDE_COST'];

		foreach($SERVICE_TABS as $tab) {
			$rowHTML .= '
				<li class="'.($active == $tab['id'] ? 'active' : '').' '.($counter == $length ? 'pull-right' : '').'">
        			<a href="#'.$tab['id'].'" data-toggle="tab"><span class="hidden-xs hidden-sm"><i class="fa '.$tab['icon'].' fa-lg"></i> '.$tab['name'].($tab['price'] ? '<span class="'.$tab['id'].'_cost">&nbsp; $'.number_format($GLOBALS[$tab['price']],2,'.','').'</span>' : '').'</span><span class="hidden-md hidden-lg"><i class="fa '.$tab['icon'].' fa-2x"></i></span></a>
        		</li>
        	';

        	$counter++;
		}
														
		$rowHTML .= '</ul>';

		$rowHTML .= $contentHTML;

		return $rowHTML;
	}

	function buildTabContent($SERVICE_TABS, $active) {
		global $T;

		$rowHTML = '
			<div class="tab-content">
		';

		foreach($SERVICE_TABS as $tab) {
			$rowHTML .= '<div class="tab-pane '.($active == $tab['id'] ? 'active' : '').'" id="'.$tab['id'].'">';
			$rowHTML .= '	
							<form class="form-inline" method="post" action="task_'.$tab['id'].'.php" enctype="multipart/form-data">
								<input type="hidden" name="taskid" value="'.$GLOBALS['ORDER_DETAILS']['id'].'">
								<input type="hidden" name="type" value="'.$T['type'].'">
								<input type="hidden" name="order_number" value="'.$GLOBALS['ORDER_DETAILS'][$T['order']].'">
			';
			$rowHTML .= 		buildContent($tab);
			$rowHTML .= '	</form>';
			$rowHTML .= '</div>';
		}

		$rowHTML .= '
			</div>
		';

		return $rowHTML;
	}

	function buildContent($tab) {
		global $ORDER, $ORDER_DETAILS;

		// print_r($ORDER_DETAILS);
		$rowHTML = '';
		// $rowHTML = '<div class="container">';

		// Create the contents in each tab and generate a form for each
		// Leaving out Materials in this if else statement until it is more built out
		if($tab['id'] == 'activity') {
			$rowHTML .= '
				<div class="row row-no-margin">
					<div class="input-group">
						<input type="text" name="notes" class="form-control input-sm" placeholder="Notes...">
						<span class="input-group-btn">
							<button class="btn btn-sm btn-primary" type="submit" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Save Entry">
								<i class="fa fa-save"></i>
							</button>
						</span>
					</div>
				</div>

				<br>

				<table class="table table-condensed table-striped table-hover">
					<thead>
						<tr>
							<th class="col-sm-2">Date/Time</th>
							<th class="col-sm-4"><span class="line"></span> Tech</th>
							<th class="col-sm-5"><span class="line"></span> Activity</th>
							<th class="col-sm-1"><span class="line"></span> Notify</th>
						</tr>
					</thead>

					<tbody>
						'.buildActivity().'
					</tbody>
				</table>
			';
		} else if($tab['id'] == 'details') {
			$rowHTML .= '
				<div class="row row-no-margin">
					<table class="table table-condensed">
						<thead>
							<tr>
								<th class="col-sm-3">Site</th>
								<th class="col-sm-3"><span class="line"></span> Notes</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>'.format_address($ORDER_DETAILS['item_id'], '<br/>', true, '', $ORDER['companyid']).'</td>
								<td>'.$ORDER_DETAILS['description'].'</td>
							</tr>
						</tbody>
					</table>
				</div>
			';
		} else if($tab['id'] == 'documentation') {
			$rowHTML .= '
				<table class="table table-condensed table-striped table-hover">
					<thead>
						<tr>
							<th class="col-sm-2">Date/Time</th>
							<th class="col-sm-4"><span class="line"></span> Notes</th>
							<th class="col-sm-2"><span class="line"></span> Type</th>
							<th class="col-sm-3"><span class="line"></span> File</th>
							<th class="col-sm-1"><span class="line"></span> Action</th>
						</tr>
					</thead>

					<tbody>
						<tr>
							<td></td>
							<td>
								<input class="form-control input-sm" type="text" name="notes">
							</td>
							<td>
								<select class="form-control input-sm select2" name="doc_type">
									<option value="">- Select Type -</option>
									<option value="MOP">MOP</option>
									<option value="SOW">SOW</option>
									<option value="COP">COP</option>
								</select>
							</td>
							<td class="file_container">
								<span class="file_name"></span>
								<input type="file" class="upload" name="files" accept="image/*,application/pdf,application/vnd.ms-excel,application/msword,text/plain,*.htm,*.html,*.xml,.docx" value="">
								<a href="#" class="upload_link btn btn-default btn-sm">
									<i class="fa fa-folder-open-o"></i> Browse...
								</a>
							</td>
							<td style="cursor: pointer;">
								<button class="btn btn-primary btn-sm pull-right" type="submit">
						        	<i class="fa fa-upload"></i>	
						        </button>
							</td>
						</tr>
						'.buildDocuments($ORDER_DETAILS['id']).'
					</tbody>
				</table>
			';
		} else if($tab['id'] == 'labor') {
			$rowHTML .= '
				<table class="table table-condensed table-striped table-hover">
					<thead>
						<tr>
							<th>Est. Hours</th>
							<th>Bill Rate</th>
							<th>Quoted Price</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>
								<div class="input-group" style="max-width: 200px;">
									<input class="form-control input-sm labor_hours" type="text" placeholder="Hours" value="" disabled="">
									<span class="input-group-addon"><i class="fa fa-clock-o" aria-hidden="true"></i></span>
								</div>
							</td>
							<td>
							<div class="input-group" style="max-width: 200px">
								<span class="input-group-addon">$</span>
								<input class="form-control input-sm labor_rate" type="text" placeholder="Rate" value="" disabled="">
							</div>
							</td>
						</tr>
					</tbody>
				</table>

				<BR>

				<table class="table table-condensed table-striped table-hover">
					<thead>
						<tr>
							<th class="col-sm-4">Tech</th>
							<th class="col-sm-2"><span class="line"></span> Start</th>
							<th class="col-sm-2"><span class="line"></span> End</th>
							<th class="col-sm-2"><span class="line"></span> Labor Time</th>
							<th class="col-sm-1 text-right">
								<span class="line"></span> Cost</th>
							<th class="col-sm-1 text-right"></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>
								<select class="form-control input-xs tech-selector" name="userid">
								</select>
							</td>
							<td>
								<div class="input-group datepicker-datetime date datetime-picker" data-hposition="right">
									<input type="text" name="start" class="form-control input-sm" value="">
									<span class="input-group-addon">
										<span class="fa fa-calendar"></span>
									</span>
								</div>
							</td>
							<td>
								<div class="input-group datepicker-datetime date datetime-picker" data-hposition="right">
									<input type="text" name="end" class="form-control input-sm" value="">
									<span class="input-group-addon">
										<span class="fa fa-calendar"></span>
									</span>
								</div>
							</td>
							<td></td>
							<td></td>
							<td>
								<button class="btn btn-sm btn-primary pull-right" type="submit">
									<i class="fa fa-plus"></i>
								</button>
							</td>
						</tr>
						'.buildLabor($ORDER_DETAILS['id']).'
						<tr>
							<td>
								<div class="progress progress-lg">
								<div class="progress-bar bg-success" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">0%</div>
								</div>

								$'.number_format($GLOBALS['SERVICE_LABOR_COST'], 2,'.','').' labor used from <span class="labor_cost">$0.00</span> quoted labor
							</td>
							<td>
								<strong>00:00:00 &nbsp; </strong>
							</td>
							<td colspan=3></td>
							<td class="text-right">
								<strong>$0.00</strong>
							</td>
						</tr>
					</tbody>
				</table>
			';
		} else if($tab['id'] == 'expenses') {

			$accountOptions = getFinancialAccounts();
			$accountHTML = '';

			foreach($accountOptions as $row) {
				$accountHTML .= '<option value="'.$row['accountid'].'">'. $row['bank'] .' '. $row['nickname'] .' '. substr($row['account_number'], -4) .'</option>';
			}

			$rowHTML .= '
				<div class="row">
					<div class="col-md-6">
						<strong>Milage Rate:</strong>
					</div>
					<div class="col-md-6">
						<button class="btn btn-sm btn-primary pull-right" type="submit">
							<i class="fa fa-plus"></i>
						</button>
					</div>
				</div>
				<br>
				<table class="table table-striped table-condensed">
					<thead>
					<tr>
						<th class="col-md-1">Expense Date</th>
						<th class="col-md-1">User</th>
						<th class="col-md-2">Category</th>
						<th class="col-md-1">Account</th>
						<th class="col-md-2">Vendor</th>
						<th class="col-md-1 th-units hidden">Miles</th>
						<th class="col-md-1">Amount</th>
						<th class="col-md-2">Notes</th>
						<th class="col-md-1">Action</th>
					</tr>
					</thead>
						<tr>
							<td>
								<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY" data-hposition="right">
									<input type="text" name="date" class="form-control input-sm" value="'.format_date($GLOBALS['now']).'">
									<span class="input-group-addon">
										<span class="fa fa-calendar"></span>
									</span>
								</div>
							</td>
							<td>
								<select class="form-control user-selector input-xs" name="userid">
									<option value="'.$GLOBALS['U']['id'].'">'.getUser($GLOBALS['U']['id']).'</option>
								</select>
							</td>
							<td>
								<select class="form-control category-selector input-xs" name="categoryid">
								</select>
							</td>
							<td>
								<select name="accountid" size="1" class="form-control input-sm select2" data-placeholder="- Account -">
									<option value =""> - Account - </option>
									'.$accountHTML.'
								</select>
							</td>
							<td>
								<select class="form-control company-selector input-xs" name="companyid" data-scope="Expenses">
								</select>
							</td>
							<td class="td-units hidden">
								<div class="input-group ">
									<span class="input-group-addon">
										<span class="fa fa-car"></span>
									</span>
									<input type="text" name="units" class="form-control input-sm" value="" placeholder="">
								</div>
							</td>
							<td class="td-amount">
								<div class="input-group ">
									<span class="input-group-addon">
										<span class="fa fa-usd"></span>
									</span>
									<input type="text" name="amount" class="form-control input-sm" value="" placeholder="0.00">
								</div>
							</td>
							<td>
								<input type="text" class="form-control input-sm" name="notes" value="" placeholder="">
							</td>
							<td class="file_container">
								<div class="pull-left">
								<input type="checkbox" name="reimb" id="reimb">
								<br>
								<label for="reimb">Reimb?</label>
								</div>
								<div class="pull-right">
									<span class="file_name"></span>
									<input type="file" class="upload" name="files" accept="image/*,application/pdf,application/vnd.ms-excel,application/msword,text/plain,*.htm,*.html,*.xml,.docx" value="">
									<a href="#" class="upload_link btn btn-default btn-sm">
										<span style="float: left;"><i class="fa fa-folder-open-o"></i></span><span class="hidden-xs hidden-sm" style="margin-left: 15px;">...</span>
									</a>
								</div>
							</td>
						</tr>
						'.buildExpenses($ORDER_DETAILS['id']).'
					<tbody>
					</tbody>
				</table>
			';
		}

		// $rowHTML .= '</div>';

		return $rowHTML;
	}

	// Building the activity rows
	function buildActivity() {
		$rowHTML = '';
		$activity_notes = getActivities();

		// print_r($activity_notes);

		foreach($activity_notes as $note) {
			$rowHTML .= '
				<tr>
					<td>'.format_date($note['datetime'], 'n/j/y g:ia').'</td>
					<td>'.getContact($note['techid'], 'userid').'</td>
					<td>'.$note['notes'].'</td>
					<td>
						'.($GLOBALS['U']['id'] == $note['techid'] ? '<a href="javascript:void(0);" class="pull-right forward_activity" data-activityid="'.$note['id'].'"><i class="fa fa-envelope-o"></i></a>' : '').'

						'.($GLOBALS['U']['id'] == $note['techid'] ? '<a href="javascript:void(0);" style="margin-right: 10px;" class="pull-right delete_activity" data-activityid="'.$note['id'].'"><i class="fa fa-trash-o"></i></a>' : '').'
					</td>
				</tr>
			';
		}

		return $rowHTML;
	}

	function buildExpenses($taskid) {
		global $T, $SERVICE_EXPENSE_COST;

		$rowHTML = '';

		$query = "SELECT * FROM expenses WHERE item_id=".res($taskid)." AND item_id_label=".fres($T['item_label']).";";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$SERVICE_EXPENSE_COST += $r['amount'] * ($r['units']?:1);

			$rowHTML .= '
						<tr>
							<td>'.format_date($r['expense_date']).'</td>
							<td>'.getUser($r['userid']).'</td>
							<td>'.getCategory($r['categoryid']).'</td>
							<td>'.getFinanceName($r['financeid']).'</td>
							<td>'.getCompany($r['companyid']).'</td>
							<td class="td-units hidden">'.($r['units']?:0).'</td>
							<td>$'.number_format($r['amount'],2,'.','').'</td>
							<td>'.$r['description'].'</td>
							<td>
								'.($r['reimbursement']?'Yes': 'No').'

								'.($r['file']?'<a href="'.$r['file'].'" target="_new" style="margin-left: 10px;"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></a>':'').'

								<div class="pull-right">
									<a href="javascript:void(0);" class="delete_expense" data-expenseid="'.$r['id'].'">
										<i class="fa fa-trash"></i>
									</a>
								</div>
							</td>
						</tr>
			';
		}

		return $rowHTML;
	}

	function buildDocuments($taskid) {
		global $T;

		$rowHTML = '';

		$documentData = array();

		// Query all DOcuments that pertain to this order / task
		$query = "SELECT * FROM service_docs WHERE item_id=".res($taskid)." AND item_label =".fres($T['item_label']).";";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$documentData[] = $r;
		}

		foreach($documentData as $row) {
			$rowHTML .= '
				<tr>
					<td>
						'.format_date($row['datetime'], 'n/j/y g:ia').'
					</td>
					<td>
						'.$row['notes'].'
					</td>
					<td>
						'.$row['type'].'
					</td>
					<td>
						<a href="'.str_replace($TEMP_DIR,'uploads/',$row['filename']).'">'.substr($row['filename'], strrpos($row['filename'], '/') + 1).'</a>
					</td>
					<td>
						<button class="btn btn-xs btn-danger pull-right" type="submit" name="delete" value="'.$row['id'].'">
							<i class="fa fa-trash"></i>
						</button>
					</td>
				</tr>
			';
		}

		return $rowHTML;
	}

	function buildLabor($taskid) {
		global $T, $SERVICE_LABOR_COST;

		// Object created for payroll to calculate OT and DT
		// These are needed to operate Payroll correctly
		$payroll = new Payroll;

		$payroll->setHours(336);

		$currentPayroll = $payroll->getCurrentPeriodStart();
		$currentPayrollEnd = $payroll->getCurrentPeriodEnd();

		$rowHTML = '';

		// Labor data will have the userid, the user status E.G. active or inactive (with a date check, if they try to reclock in past the end date then they are not allowed), time worked
		$labor_data = array();

		// Query into both service_assignments and the timesheet to see who has worked on this
		$query = "SELECT * FROM service_assignments WHERE item_id=".res($taskid)." AND item_id_label=".fres($T['item_label']).";";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			// If they are on this table then they are active as long as they are within the expiration of their pass to this assignment
			// If no end date then ignore
			$status = 'active';

			// End date is smaller than current date so access is now expired
			if(strtotime($r['end_datetime']) < strtotime($GLOBALS['now']) AND $r['end_datetime']) {
				$status = 'inactive';
			}
			
			// Set the used data here
			$labor_data[$r['userid']]['status'] = $status;
			$labor_data[$r['userid']]['start_datetime'] = $r['start_datetime'];
			$labor_data[$r['userid']]['end_datetime'] = $r['end_datetime'];
		}

		// Now query into the timesheet and see all users that have worked on this order
		// If the user is not on service assignments but has clocked time then add it in and leave them as status inactive
		$query = "SELECT * FROM timesheets WHERE taskid=".res($taskid)." AND task_label=".fres($T['item_label']).";";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			// User does not exist based on the service assignments query so set the user has inactive
			if(empty($labor_data[$r['userid']])) {
				$labor_data[$r['userid']]['status'] = 'inactive';
			}
		}


		foreach($labor_data as $userid => $row) {
			// utilizing the timesheet function get the pay of the user including OT and DT
			// Then might as well grab the total seconds using this
			$timesheet_data = $payroll->getTimesheets($userid, false, '', '', $taskid, $T['item_label']);
			$totalSeconds = 0;
			$totalPay = 0;
			// print_r($timesheet_data);
			foreach($timesheet_data as $time) {
				$userTimesheet = getTimesheet($time['userid']);
				$totalSeconds += $userTimesheet[$time['id']]['REG_secs'] + $userTimesheet[$time['id']]['OT_secs'] + $userTimesheet[$time['id']]['DT_secs'];
				$totalPay += ($userTimesheet[$time['id']]['laborCost']);
			}

			// Per user include the cost into the total
			$SERVICE_LABOR_COST += $totalPay;

			$rowHTML .= '
				<tr '.($row['status'] == 'inactive' ? 'class="labor-inactive"' : '').'>
					<td>'.getUser($userid).'</td>
					<td>'.format_date($row['start_datetime'], 'n/j/y g:ia').'</td>
					<td>'.format_date($row['end_datetime'], 'n/j/y g:ia').'</td>
					<td>'.timeToStr(toTime($totalSeconds)).'</td>
					<td class="text-right">$'.number_format($totalPay,2,'.','').'</td>
					<td>'.
						($row['status'] != 'inactive' ? 
							'<button class="btn btn-xs btn-danger pull-right" type="submit" name="delete" value="'.$userid.'">
								<i class="fa fa-trash"></i>
							</button>'
						: '' )
					.'</td>
				</tr>
			';
		}

		return $rowHTML;
	}

	function clockedButton($taskid) {
		global $U, $T;

		// Will turn in an array using the is_clockedin function
		$clock = false;
		if ($U['hourly_rate']) {
			$clock = is_clockedin($U['id'], $taskid, $T['item_label']);
			if ($clock===false) {
				$clock = is_clockedin($U['id']);
				if (! $manager_access) { $view_mode = true; }
			}
		}

		$clockers = '';

		if ($U['hourly_rate']) {
			if ($taskid AND $clock['taskid']==$taskid) {
				$rp_cls = 'default btn-clock';
				$rp_title = 'Switch to Regular Pay';
				$tt_cls = 'default btn-clock';
				$tt_title = 'Switch to Travel Time';

				if ($clock['rate']==11) {
					$tt_cls = 'warning';
					$tt_title = 'Clocked In';
				} else {
					$rp_cls = 'primary';
					$rp_title = 'Clocked In';
				}

				$clockers = '
				<button class="btn btn-'.$rp_cls.'" type="button" data-type="clock" data-clock="in" data-toggle="tooltip" data-placement="bottom" title="'.$rp_title.'"><i class="fa fa-briefcase"></i></button>
				<button class="btn btn-'.$tt_cls.'" type="button" data-type="travel" data-clock="in" data-toggle="tooltip" data-placement="bottom" title="'.$tt_title.'"><i class="fa fa-car"></i></button>
				<button class="btn btn-default btn-clock text-danger" type="button" data-type="out" data-clock="out" data-toggle="tooltip" data-placement="bottom" title="Clock Out"><i class="fa fa-close"></i></button>
				';
			} else if ($clock['taskid']) {
				if ($clock['task_label']=='repair_item_id') { $task_type = 'Repair'; }
				else { $task_type = 'Service'; }
	
				$clockers = '
				<a class="btn btn-default" href="service.php?order_type='.$task_type.'&order_number='.getItemOrder($clock['taskid'], $clock['task_label']).'" data-toggle="tooltip" data-placement="bottom" title="Clocked In"><i class="fa fa-clock-o"></i> '.getItemOrder($clock['taskid'], $clock['task_label'], true).'</a>
				';
			} else {
				$clockers = '
				<button class="btn btn-danger pull-left" style="margin-right: 10px;" type="button" data-toggle="tooltip" data-placement="bottom" title="Not Clocked In"><i class="fa fa-close"></i></button>
				';
			}
		}

		return $clockers;
	}

	$pageHTML = buildTabHeader($SERVICE_TABS, $ACTIVE);
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo $TITLE; ?></title>
	<?php
		/*** includes all required css includes ***/
		include_once 'inc/scripts.php';
	?>

	<!-- any page-specific customizations -->
	<style type="text/css">
		.row-no-margin {
			margin : 0;
		}

		.labor-inactive {
			opacity: 0.5;
		}
	</style>
</head>
<body data-order-type="<?=$T['type']?>">

<?php include_once 'inc/navbar.php'; ?>

<!-- FILTER BAR -->
<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px; max-height:60px;">
	<form class="form-inline" method="get" action="" enctype="multipart/form-data" id="filters-form" >

	<div class="row" style="padding:8px">
		<div class="col-sm-3">
			<?=clockedButton($ORDER_DETAILS['id']);?>
		</div>
		<div class="col-sm-2">
		</div>
		<div class="col-sm-2 text-center">
			<h2 class="minimal"><?php echo $TITLE; ?></h2>
			<span class="info"></span>
		</div>
		<div class="col-sm-2">
		</div>
		<div class="col-sm-2">
			<select name="task" class="form-control service-task-selector task_selection pull-right">
				<option selected=""><?php echo $TITLE; ?></option>
			</select>
		</div>
		<div class="col-sm-1">
		</div>
	</div>

	</form>
</div>
<div class="container-fluid">
	<?php include 'sidebar.php'; ?>
	<div id="pad-wrapper">

	<!-- <form class="form-inline" method="get" action="" enctype="multipart/form-data" > -->
		<?=mainStats();?>

		<br>

		<?=$pageHTML;?>
	<!-- </form> -->
	</div><!-- pad-wrapper -->
</div>

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<script type="text/javascript">
	$(document).ready(function(e) {
		// This is an anchor activity delete submit
		$('.delete_activity').click(function(e) {
			e.preventDefault();

			var activityid = $(this).data('activityid');

		    if (confirm("Are you sure you want to delete this notification?")) {

				if(activityid) {
					var input = $("<input>").attr("type", "hidden").attr("name", "delete").val(activityid);
					//console.log(input);
					$(this).closest('form').append($(input));
				}

				$(this).closest('form').submit();
			}
		});

		$('.delete_expense').click(function(e) {
			e.preventDefault();
			
			var expenseid = $(this).data('expenseid');

			if (confirm("Are you sure you want to delete this expense?")) {

				if(expenseid) {
					var input = $("<input>").attr("type", "hidden").attr("name", "delete").val(expenseid);
					//console.log(input);
					$(this).closest('form').append($(input));
				}

				$(this).closest('form').submit();
			}
		});

		$('.forward_activity').click(function() {
			var activityid = $(this).data('activityid');

			if (confirm("Please Confirm notification email")) {
				if(activityid) {
					var input = $("<input>").attr("type", "hidden").attr("name", "email").val(activityid);
					//console.log(input);
					$(this).closest('form').append($(input));
				}

				$(this).closest('form').submit();
			}
		});

		$('select[name="categoryid"]').change(function() {
			var categoryid = $(this).val();

			if(categoryid == 91) {
				$('.th-units, .td-units').removeClass('hidden');
				$('.td-amount').find("input").prop("disabled", true);
			} else {
				$('.th-units, .td-units').addClass('hidden');
				$('.td-amount').find("input").prop("disabled", false);
			}
		});
	});
</script>

</body>
</html>
