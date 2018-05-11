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

	// This is the include files for the part / address selector tool
	include_once $_SERVER["ROOT_DIR"].'/inc/buildDescrCol.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setInputSearch.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getItems.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/detectDefaultType.php';

	// Set GLOBAL Costs used through this page
	$SERVICE_LABOR_COST = 0.00;
	$SERVICE_LABOR_QUOTE = 0.00;
	$SERVICE_MATERIAL_COST = 0.00;
	$SERVICE_OUTSIDE_COST = 0.00;
	$SERVICE_EXPENSE_COST = 0.00;
	$SERVICE_TOTAL_COST = 0.00;
	$SERVICE_TOTAL_PROFIT = 0.00;

	$SERVICE_CHARGE = $ORDER_DETAILS['amount'];


	// Depict here the users access
	$manager_access = array_intersect($USER_ROLES,array(1,4));

	//Bypass tool for quotes and sales
	if($QUOTE_TYPE AND array_intersect($USER_ROLES,array(5))) {
		$manager_access = true;
	}

	// Depict the accounting access
	$accounting_access = array_intersect($USER_ROLES,array(7));

	// if the activity hasn't been set then set it here as a backup
	if(! $ACTIVE)
		$ACTIVE = ($_REQUEST['tab']?:'activity');
	
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
				SELECT '' as id, created_by as techid, created as datetime, CONCAT('".$T['type']." Order Created') as notes FROM repair_orders WHERE item_id = ".fres($ORDER_DETAILS[$T['id']])." AND item_id_label = 'repair_item_id'
				UNION
				SELECT '' as id, userid as techid, date_created as datetime, CONCAT('Received ".$T['type']." Serial: <b>', serial_no, '</b>') as notes FROM inventory WHERE id in (SELECT invid FROM inventory_history where field_changed = 'repair_item_id' and `value` = ".fres($ORDER_DETAILS['id']).") AND serial_no IS NOT NULL
				UNION
				SELECT '' as id, '' as techid, datetime as datetime, CONCAT('Tracking# ', IFNULL(tracking_no, 'N/A')) as notes FROM packages WHERE order_number = ".fres($ORDER_DETAILS[$T['order']])." AND order_type = 'Repair'
				UNION
				SELECT '' as id, '' as techid, datetime as datetime, CONCAT('<b>', part, '</b> pulled to Order') as notes FROM repair_components, inventory, parts WHERE item_id = ".fres($ORDER_DETAILS[$T['id']])." AND item_id_label = 'repair_item_id' AND inventory.id = repair_components.invid AND parts.id = inventory.partid
			";
		}

		$query .= "
			ORDER BY datetime DESC;
		";

		// $query = "
		// 		SELECT activity_log.id, userid techid, datetime, notes FROM activity_log WHERE item_id = '".res($ORDER_DETAILS['id'])."' AND item_id_label = '".res($T['item_label'])."'
		// 		UNION
		// 		SELECT '' as id, '' as techid, i.date_created as datetime, CONCAT('Component <b>', p.part, '</b> Received') FROM purchase_requests pr, purchase_items pi, parts p, inventory i WHERE pr.item_id = ".fres($ORDER_DETAILS['id'])." AND pr.item_id_label = ".fres($T['item_label'])." AND pr.po_number = pi.po_number AND pr.partid = pi.partid AND pi.qty <= pi.qty_received AND p.id = pi.partid AND i.purchase_item_id = pi.id
		// 		UNION
		// 		SELECT '' as id, '' as techid, pr.requested as datetime, CONCAT('Component <b>', p.part, '</b> Requested') FROM purchase_requests pr, parts p WHERE pr.item_id = ".fres($ORDER_DETAILS['id'])." AND pr.item_id_label = ".fres($T['item_label'])." AND pr.partid = p.id
		// 		ORDER BY datetime DESC;";

		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$notes[] = $r;
		}

		return $notes;
	}

	function mainStats() {
		global $T;
		$statsHTML = '';

		if($GLOBALS['manager_access']) {
			if($T['record_type'] != 'quote') {
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
											<span class="number text-gray">$'.number_format($GLOBALS['SERVICE_LABOR_QUOTE'], 2, '.', '').'</span>
											<br>
											<span class="info">Total Labor</span>
										</div>
									</div>
									
									<div class="col-md-2 col-sm-2 stat">
										<div class="data" style="min-height: 35px;">
											<span class="number text-brown">$'.number_format($GLOBALS['SERVICE_CHARGE'], 2, '.', '').'</span>
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
											<span class="number text-success">$'.number_format($GLOBALS['SERVICE_CHARGE'] - $GLOBALS['SERVICE_TOTAL_COST'], 2, '.', '').'</span>
											<br>
											<span class="info">Total Profit</span>
										</div>
									</div>
								</div>
							</div>';
			} else {
				$statsHTML = '<div id="main-stats">
								<div class="row stats-row">
									<div class="col-md-3 col-sm-3 stat">
										<div class="data">
											<span class="number text-gray">$'.number_format($GLOBALS['SERVICE_LABOR_COST'], 2, '.', '').'</span>
											<br>
											<span class="info">Total Labor</span>
										</div>
									</div>

									<div class="col-md-3 col-sm-3 stat">
										<div class="data">
											<span class="number text-brown">$'.number_format($GLOBALS['SERVICE_MATERIAL_COST'], 2, '.', '').'</span>
											<br>
											<span class="info">Total Materials</span>
										</div>
									</div>	
									
									<div class="col-md-3 col-sm-3 stat">
										<div class="data" style="min-height: 35px;">
											<span class="number text-black">$'.number_format($GLOBALS['SERVICE_EXPENSE_COST'], 2, '.', '').'</span>
											<br>
											<span class="info">Outside Services</span>
										</div>
									</div>

									<div class="col-md-3 col-sm-3 stat last">
										<div class="data">
											<span class="number text-success">$'.number_format($GLOBALS['SERVICE_TOTAL_COST'], 2, '.', '').'</span>
											<br>
											<span class="info">Quote Total</span>
										</div>
									</div>
								</div>
							</div>';
			}
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
		global $ORDER, $ORDER_DETAILS, $T, $QUOTE_TYPE, $QUOTE_DETAILS, $SERVICE_LABOR_QUOTE;

		// $QUOTE_TYPE == true changes some of the form fields and enables some
		$rowHTML = '';

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
							'.buildDetails().'
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

				<button class="btn btn-success btn-sm pull-right" name="closeout" value="true" type="submit" style="margin-right: 10px;">Closeout</button>
			';
		} else if($tab['id'] == 'labor') {
			$labor_hours = ($ORDER_DETAILS['labor_hours']?:($QUOTE_DETAILS['labor_hours']?:0));
			$labor_rate = ($ORDER_DETAILS['labor_rate']?:($QUOTE_DETAILS['labor_rate']?:0));
			$SERVICE_LABOR_QUOTE = (($ORDER_DETAILS['labor_rate'] AND $ORDER_DETAILS['labor_hours']) ? $ORDER_DETAILS['labor_hours'] * $ORDER_DETAILS['labor_rate'] : (($QUOTE_DETAILS['labor_rate'] AND $QUOTE_DETAILS['labor_hours']) ? $QUOTE_DETAILS['labor_hours'] * $QUOTE_DETAILS['labor_rate'] : 0));

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
									<input class="form-control input-sm labor_hours" type="text" name="labor_hours" placeholder="Hours" value="'.$labor_hours.'" '.($QUOTE_TYPE?'':'disabled=""').'>
									<span class="input-group-addon"><i class="fa fa-clock-o" aria-hidden="true"></i></span>
								</div>
							</td>
							<td>
							<div class="input-group" style="max-width: 200px">
								<span class="input-group-addon">$</span>
								<input class="form-control input-sm labor_rate" type="text" name="labor_rate" placeholder="Rate" value="'.$labor_rate.'" '.($QUOTE_TYPE?'':'disabled=""').'>
							</div>
							</td>
							<td>
								<span style="border: 1px solid #468847; display: block; padding: 3px 10px;">$'.number_format($SERVICE_LABOR_QUOTE, 2, '.', '').'</span>
							</td>
						</tr>
					</tbody>
				</table>
			';

			// Below allows the user to add / assign users to the task
			if(! $QUOTE_TYPE) {
			
			$rowHTML .= '
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
						'.buildLabor($ORDER_DETAILS['id']);

				// This is here because buildlabor calculates the global cost of labor based on user clockins
				$labor_progress = 100*round(($GLOBALS['SERVICE_LABOR_COST']/($labor_hours * $labor_rate)),2);

				$progress_bg = '';
				if ($labor_progress>=100) { $progress_bg = 'bg-danger'; }
				else if ($labor_progress<50) { $progress_bg = 'bg-success'; }

				$rowHTML .= '
						<tr>
							<td>
								<div class="progress progress-lg">
								<div class="progress-bar '.$progress_bg.'" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: '.$labor_progress.'%">'.$labor_progress.'%</div>
								</div>

								$'.number_format($GLOBALS['SERVICE_LABOR_COST'], 2,'.','').' labor used from <span class="labor_cost">$'.number_format($SERVICE_LABOR_QUOTE, 2, '.', '').'</span> quoted labor
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
			}
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
		} else if($tab['id']=="outside" AND ! $QUOTE_TYPE) {
			$rowHTML .= '
				<div class="row">
					<div class="col-sm-12">
						<h3 class="pull-left">Outsourced Service Orders</h3>
						<a target="_blank" href="/manage_outsourced.php?order_type='.$T['type'].'&order_number='.$ORDER_DETAILS[$T['order']].'&ref_2='.$ORDER_DETAILS['id'].'&ref_2_label='.$T['item_label'].'" class="btn btn-primary btn-sm pull-right" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Create Order">
							<i class="fa fa-plus"></i>
						</a>
					</div>
				</div>

				<br>

				<table class="table table-striped table-condensed">
					<thead>
					<tr>
						<th>Vendor</th>
						<th>Description</th>
						<th>Quote</th>
						<th>Order</th>
						<th>Cost</th>
						<th>Quoted</th>
						<th><button type="submit" class="btn btn-default btn-sm btn-os text-primary import_button pull-right" disabled>Import <i class="fa fa-level-up"></i></button></th>
					</tr>
					</thead>
					<tbody>
						'.buildOutsourced($ORDER_DETAILS['id']).'
					</tbody>
				</table>
			';
		} else if($tab['id']=="outside" AND $QUOTE_TYPE) {
			$rowHTML .= '
				<table class="table table-striped table-condensed">
					<thead class="no-border">
						<tr>
							<th class="col-md-3">
								Vendor
							</th>
							<th class="col-md-5">
								Description
							</th>
							<th class="col-md-1">
								Cost
							</th>
							<th class="col-md-1">
								Markup
							</th>
							<th class="col-md-1">
								Quoted Price
							</th>
							<th class="col-md-1 text-right">
								Action
							</th>
						</tr>
					</thead>

					<tbody>
						<tr>
							<td class="select2_os">
								<input type="hidden" name="outsourced[3][quoteid]" value="0">
								<select name="companyid" class="form-control input-xs company-selector">
								</select>
							</td>
							<td>
								<input class="form-control input-sm" type="text" name="description" value="">
							</td>
							<td>
								<span class="input-group">
									<span class="input-group-btn">
										<button class="btn btn-default btn-sm" type="button"><i class="fa fa-usd"></i></button>
									</span>
									<input class="form-control input-sm os_amount" type="text" name="amount" placeholder="0.00" value="">
								</span>
							</td>
							<td>
								<span class="input-group">
									<input class="form-control input-sm os_amount_profit" type="text" name="" placeholder="0" value="">
									<span class="input-group-btn">
										<button class="btn btn-default btn-sm" type="button"><i class="fa fa-percent"></i></button>
									</span>
								</span>
							</td>
							<td>
								<span class="input-group">
									<span class="input-group-btn">
										<button class="btn btn-default btn-sm" type="button"><i class="fa fa-usd"></i></button>
									</span>
									<input class="form-control input-sm os_amount_total" type="text" name="quote" placeholder="0.00" value="">
								</span>
							</td>
							<td>
								<button class="btn btn-primary btn-sm pull-right os_expense_add">
									<i class="fa fa-plus"></i>	
								</button>
							</td>
						</tr>
						'.buildQuoteOutsourced($ORDER_DETAILS['id']).'
					</tbody>
				</table>
			';
		} else if($tab['id']=="images") {
			$rowHTML .= '
				<div id="sticky-footer">
					<ul id="bxslider-pager">
						<li data-slideIndex="0" class="file_container">
							<a href="#" class="upload_link" style="text-decoration: none;">
								<div class="dropImage" style="width: 200px; height: 200px; background: #E9E9E9;">
									<i class="fa fa-plus-circle" aria-hidden="true"></i>
								</div>
							</a>

							<input type="file" class="upload imageUploader" name="filesImage" accept="image/*" value="">
						</li>
					</ul>
				</div>
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

		if(! $taskid) {
			return 0 ;
		}

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
							<td>$'.number_format($r['amount'] * ($r['units']?:1) ,2,'.','').'</td>
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

						<input class="pull-right" type="checkbox" name="files[]" value="'.$row['id'].'" style="margin-right: 10px;">
					</td>
				</tr>
			';
		}

		return $rowHTML;
	}

	function buildLabor($taskid) {
		global $T, $SERVICE_LABOR_COST, $QUOTE_DETAILS;

		// If there is no taskid then assume it is a quote or assume that there is nothing to be assigned
		if(! $taskid) {
			return 0;
		}

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

	function buildDetails() {
		global $EDIT, $ORDER_DETAILS, $T;

		$rowHTML = '<tr>';

		if($EDIT) {
			$P = array();
			$A = array();

			$A['name'] = format_address($ORDER_DETAILS['item_id'],', ',true,'');
			$A['id'] = $ORDER_DETAILS['item_id'];

			$id = false;
			$override = false;

			$items = getItems($T['item_label']);
			$def_type = detectDefaultType($items,$T['type']);

			if($ORDER_DETAILS['item_label'] == 'partid') {
				$rowHTML .= '
					<td class="part-container">'.buildDescrCol($P,$id,'Part',$items, $override, true).buildDescrCol($A,$id,'Site',$items, false, false).'</td>
					<td><textarea class="form-control" name="description" rows="3" placeholder="Scope">'.$ORDER_DETAILS['description'].'</textarea></td>
				';
			} else {
				$rowHTML .= '
					<td class="part-container">'.buildDescrCol($A,$id,'Site',$items, $override, true).buildDescrCol($P,$id,'Part',$items, false, false).'</td>
					<td><textarea class="form-control" name="description" rows="3" placeholder="Scope">'.$ORDER_DETAILS['description'].'</textarea></td>
				';;
			} 
		} else {
			$rowHTML .= '
				<td>'.format_address($ORDER_DETAILS['item_id'], '<br/>', true, '', $ORDER['companyid']).'</td>
				<td>'.$ORDER_DETAILS['description'].'</td>
			';
		}

		$rowHTML .= '</tr>';

		return $rowHTML;
	}

	function buildOutsourced($taskid) {
		global $T, $ORDER_DETAILS, $QUOTE_DETAILS, $SERVICE_OUTSIDE_COST; 

		$outsourced_quote = array();

		// If QUOTE and has outsourced
		if(! empty($QUOTE_DETAILS) AND $QUOTE_DETAILS['id']) {
			$query = "SELECT * FROM service_quote_outsourced WHERE quote_item_id = ".res($QUOTE_DETAILS['id']).";";
			$result = qedb($query);

			while($r = mysqli_fetch_assoc($result)) {
				$outsourced_quote[$r['id']] = $r;
			}
		}

		$rowHTML = '';

		$query = "SELECT o.companyid, o.public_notes, i.* FROM outsourced_orders o, outsourced_items i WHERE  ref_2_label=".fres($T['item_label'])." AND ref_2 = ".res($taskid)." AND o.os_number = i.os_number;";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$quote_title = '';
			$quoted = '';
			// If this is set then look for the outsourced order information
			if($r['ref_1_label'] == 'service_quote_outsourced_id'){
				// print_r($outsourced_quote[$r['ref_1']]);
				$quoted = '$'.number_format($outsourced_quote[$r['ref_1']]['quote'],2);
				$quote_title = $GLOBALS['quote_order'].'-'.$GLOBALS['quote_linenumber'];
			}

			$SERVICE_OUTSIDE_COST += ($r['price'] ?:0);

			$rowHTML .= '
						<tr>
							<td>'.getCompany($r['companyid']).'</td>
							<td>'.$r['public_notes'].'</td>
							<td>'.$quote_title.'</td>
							<td>'.($r['os_number']?'OS '.$r['os_number']:'').' <a target="_blank" href="/OS'.$r['os_number'].'"><i class="fa fa-arrow-right"></i></a></td>
							<td>'.($r['price']? '$'.number_format($r['price'],2):'').'</td>
							<td>'.$quoted.'</td>
						</tr>
			';

			if($r['ref_1_label'] == 'service_quote_outsourced_id'){
				// remove the outsourced info from the array as it is already generated
				unset($outsourced_quote[$r['ref_1']]);
			}
		}

		if(! empty($outsourced_quote)){
			// Create a row for the left behind quotes
			foreach($outsourced_quote as $r) {
				$rowHTML .= '
						<tr>
							<td>'.getCompany($r['companyid']).'</td>
							<td>'.$r['description'].'</td>
							<td>'.$GLOBALS['quote_order'].'-'.$GLOBALS['quote_linenumber'].'</td>
							<td></td>
							<td></td>
							<td>$'.number_format($r['quote'],2).'</td>
							<td><input class="pull-right quote_check" type="checkbox" name="quoteImport[]" value="'.$r['id'].'"></td>
						</tr>
				';
			}
		}

		return $rowHTML;
	}

	function buildQuoteOutsourced($taskid){
		global $T, $ORDER_DETAILS;
		$rowHTML = '';

		$query = "SELECT * FROM service_quote_outsourced WHERE quote_item_id = ".res($taskid).";";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$rowHTML .= '
						<tr>
							<td>'.getCompany($r['companyid']).'</td>
							<td>'.$r['description'].'</td>
							<td>$'.number_format($r['amount'],2).'</td>
							<td>'.((($r['quote'] / $r['amount']) * 100) - 100).'</td>
							<td>$'.number_format($r['quote'],2).'</td>
							<td>
								<a href="javascript:void(0);" class="pull-right quote_outsourced_delete" data-outsourced="'.$r['id'].'"><i class="fa fa-trash fa-4"></i></a>
							</td>
						</tr>
			';
		}

		return $rowHTML;
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

		/* BX Slider Image Documentation CSS */
		.bx-wrapper .bx-viewport {
			box-shadow: none;
			border: 0;
			left: 0;
		}

		.dropImage > i {
			text-align: center;
			display: block;
			font-size: 40px;
			vertical-align: middle;
			height: 200px;
			line-height: 200px;
			color: #C9C9C9;
		}

		.imageDrop:hover {
			text-decoration: none;
		}

		.imageDrop:hover i {
			color: #999;
		}

		.bx-wrapper {
			width: 100%;
			max-width: 100% !important;
			min-height: 200px;
		}

		.bx-wrapper .bx-viewport {
			height: 200px !important;
			background: transparent;
		}

		#bxslider-pager li {
			width: 200px !important;
			list-style: none;
		}

		.table td {
			vertical-align: top !important;
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
			<button class="btn btn-md btn-success pull-right <?=($QUOTE_TYPE ? 'save_quote' : 'complete_order');?>">
				<i class="fa fa-floppy-o" aria-hidden="true"></i>
				<?=($QUOTE_TYPE ? 'Save' : 'Complete');?>
			</button>
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

		$('.quote_outsourced_delete').click(function(e) {
			e.preventDefault();
			
			var outsourced = $(this).data('outsourced');

			if (confirm("Are you sure you want to delete this outsourced quote?")) {

				if(outsourced) {
					var input = $("<input>").attr("type", "hidden").attr("name", "delete").val(outsourced);
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

		$('.quote_check').change(function(e) {
			// At least one checkbox is checked
			if($('.quote_check:checked').length > 0) {
				$('.import_button').prop('disabled', false);
			} else {
				$('.import_button').prop('disabled', true);
			}
		});

		$('.complete_order').click(function(e){
			e.preventDefault();
		});

		$('.save_quote').click(function(e){
			e.preventDefault();

			$('form[action="task_labor.php"]').submit();
		});
	});
</script>

</body>
</html>
