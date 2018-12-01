<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	include_once $_SERVER["ROOT_DIR"] . '/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/dictionary.php';

	// Getter Tools
	include_once $_SERVER["ROOT_DIR"].'/inc/getActivities.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getUser.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getFinancialAccounts.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getCategory.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getMaterials.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getMaterialsCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getLocation.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCondition.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getStatusCode.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getInventoryCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getTravelRate.php';
	
	// Formatting tools
	include_once $_SERVER["ROOT_DIR"].'/inc/format_address.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';

	// Timesheet tool to calculate the users time on this specific job
	include_once $_SERVER['ROOT_DIR'] . '/inc/getTimesheet.php';
	include_once $_SERVER['ROOT_DIR'] . '/inc/getAssignment.php';
	include_once $_SERVER['ROOT_DIR'] . '/inc/payroll.php';

	// Clocker tool
	include_once $_SERVER["ROOT_DIR"] . '/inc/is_clockedin.php';

	// This is the include files for the part / address selector tool
	include_once $_SERVER["ROOT_DIR"].'/inc/buildDescrCol.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setInputSearch.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getItems.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/detectDefaultType.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/getUserClasses.php';

	// Responsive stuff being added here
	include_once $_SERVER["ROOT_DIR"].'/responsive/activity.php';

	// For repairs currently to see is an so is already built
	include_once $_SERVER['ROOT_DIR'] . '/inc/shipOrder.php';

	// Set GLOBAL Costs used through this page
	$SERVICE_LABOR_COST = 0.00;
	$SERVICE_LABOR_QUOTE = 0.00;
	$SERVICE_MATERIAL_COST = 0.00;
	$SERVICE_MATERIAL_QUOTE = 0.00;
	$SERVICE_OUTSIDE_COST = 0.00;
	$SERVICE_EXPENSE_COST = 0.00;
	$SERVICE_TOTAL_COST = 0.00;
	$SERVICE_TOTAL_PROFIT = 0.00;

	$SERVICE_CHARGE = $ORDER_DETAILS['amount'];

	$UNPULLED = false;
	$SCANNED = false;

	if (! isset($view_mode)) { $view_mode = false; }

	// Depict here the users access
	$manager_access = false;
	$sales_access = false;
	$engineering_access = false;

	$USER_CLASSES = getUserClasses($U['id']);
	// check assignments
	$ASSIGNED = getAssignment($U['id'],$taskid,$T['item_label']);

	if ($U['admin']) {
		$manager_access = true;
	} else if ($QUOTE_TYPE AND $U['sales']) {
		//Bypass tool for quotes and sales
		$manager_access = true;
	} else if ($U['manager']) {//check to see if they belong on this order
		if ($ORDER['sales_rep_id'] == $U['id']) { $manager_access = true; }
		else if ($ASSIGNED) { $manager_access = true; }
	} else if ($U['sales']) {
		if (array_search($ORDER['classid'],$USER_CLASSES)!==false AND ($U['id']==$ORDER['sales_rep_id'] OR $ASSIGNED==='LEAD')) {
			$sales_access = true;
//			if ($ASSIGNED==='LEAD') { $engineering_access = true; }
			$engineering_access = true;
		}
	} else if (array_intersect($USER_ROLES,array(9))) {
		if (array_search($ORDER['classid'],$USER_CLASSES)!==false) {
			$engineering_access = true;
		}
	}

	if (array_intersect($USER_ROLES,array(10)) AND $ASSIGNED) {
		$engineering_access = true;
	}

	if ($manager_access) {
		$sales_access = true;
		$engineering_access = true;
	}

	// Depict the accounting access
	$accounting_access = $U['accounting'];

	if (! $ASSIGNED AND $U['hourly_rate'] AND (in_array("8", $USER_ROLES) AND ! $USER_CLASSES['Repair']) AND ! $QUOTE_TYPE AND ! in_array("9", $USER_ROLES) AND ! $sales_access) {
		header('Location: /');
		exit;
	}

	// if the activity hasn't been set then set it here as a backup
	if(! $ACTIVE)
		$ACTIVE = ($_REQUEST['tab']?:'activity');
	
	// print '<pre>' . print_r($ORDER_DETAILS) . '</pre>';


	function mainStats() {
		global $T, $manager_access, $accounting_access;

		$statsHTML = '';

		if (! $manager_access AND ! $accounting_access) { return ($statsHTML); }

		$statsHTML = '
							<div id="main-stats">
								<div class="row stats-row">
		';

		if($T['record_type'] != 'quote') {
			$market_url = 'market.php?list_type='.$T['type'].'&listid='.$GLOBALS['taskid'];
			$quote_link = '';
			$id = $GLOBALS['taskid'];
			$quoted_materials = getQuotedMaterials($GLOBALS['ORDER_DETAILS']['quote_item_id']);
			if (count($quoted_materials)>0) { $quote_link = '<br/><a href="'.$market_url.'&import_quote=true"><i class="fa fa-list-alt"></i> View Quoted Materials</a>'; }

			$statsHTML .= '
									<div class="col-md-2 col-sm-2 stat">
										<div class="data">
											<span class="number text-gray">
												$'.number_format($GLOBALS['SERVICE_MATERIAL_QUOTE'], 2, '.', ',').'
											</span>
											<a href="'.$market_url.'"><i class="fa fa-pencil fa-2x text-primary"></i></a>
											<br>
											<span class="info">Materials Quote</span>'.$quote_link.'
										</div>
									</div>	

									<div class="col-md-2 col-sm-2 stat">
										<div class="data">
											<span class="number text-gray">$'.number_format($GLOBALS['SERVICE_LABOR_QUOTE'], 2, '.', ',').'</span>
											<br>
											<span class="info">Labor Quote</span>
										</div>
									</div>
									
									<div class="col-md-2 col-sm-2 stat">
										<div class="data" style="min-height: 35px;">'.
											(((($GLOBALS['SERVICE_LABOR_QUOTE'] + $GLOBALS['SERVICE_MATERIAL_COST']) !=  $GLOBALS['SERVICE_CHARGE'] AND ($GLOBALS['SERVICE_LABOR_QUOTE'] OR $GLOBALS['SERVICE_MATERIAL_COST']))) ? '<i class="fa fa-warning fa-2x" title="" data-toggle="tooltip" data-placement="bottom" data-original-title="Quote ($'.number_format($GLOBALS['SERVICE_LABOR_QUOTE'] + $GLOBALS['SERVICE_MATERIAL_COST'], 2, '.', '').') does not agree with billed amount."></i>' : '').'
											<span class="number text-brown">$'.number_format($GLOBALS['SERVICE_CHARGE'], 2, '.', '').'</span>
											<br>
											<span class="info">Billed Amount</span>
										</div>
									</div>
									<div class="col-md-3 col-sm-3 stat">
										<div class="data">
											<span class="number text-black">$'.number_format($GLOBALS['SERVICE_TOTAL_COST'], 2, '.', ',').'</span>
											<br>
											<span class="info">Total Cost</span>
										</div>
									</div>

									<div class="col-md-3 col-sm-3 stat last">
										<div class="data">
											<span class="number text-success">$'.number_format($GLOBALS['SERVICE_CHARGE'] - $GLOBALS['SERVICE_TOTAL_COST'], 2, '.', ',').'</span>
											<br>
											<span class="info">Total Profit</span>
										</div>
									</div>
			';
		} else {
			$statsHTML .= '
									<div class="col-md-3 col-sm-3 stat">
										<div class="data">
											<span class="number text-gray">$'.number_format($GLOBALS['SERVICE_LABOR_QUOTE'], 2, '.', ',').'</span>
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
											<span class="number text-black">$'.number_format($GLOBALS['SERVICE_OUTSIDE_COST'], 2, '.', '').'</span>
											<br>
											<span class="info">Outside Services</span>
										</div>
									</div>

									<div class="col-md-3 col-sm-3 stat last">
										<div class="data">
											<span class="number text-success">$'.number_format($GLOBALS['SERVICE_LABOR_QUOTE'] + $GLOBALS['SERVICE_MATERIAL_COST'] + $GLOBALS['SERVICE_OUTSIDE_COST'], 2, '.', '').'</span>
											<br>
											<span class="info">Quote Total</span>
										</div>
									</div>
			';
		}
		$statsHTML .= '
								</div>
							</div>
		';

		return $statsHTML;
	}

	function buildTabHeader($SERVICE_TABS, $active = '') {
		global $NEW_QUOTE, $QUOTE_TYPE, $manager_access, $engineering_access, $accounting_access;

		if($QUOTE_TYPE AND $active != 'active') {
			$active = "details";
		}

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
			$pricing_info = '';
			if ($tab['price'] AND ($manager_access OR $accounting_access OR ($engineering_access AND $tab['id']<>'labor'))) {
				$pricing_info = '<span class="'.$tab['id'].'_cost">&nbsp; $'.number_format($GLOBALS[$tab['price']],2,'.','').'</span>';
			}

			$rowHTML .= '
				<li class="'.($active == $tab['id'] ? 'active' : '').' '.($counter == $length ? 'pull-right' : '').'">
        			<a href="#'.$tab['id'].'" data-toggle="tab"><span class="hidden-xs hidden-sm"><i class="fa '.$tab['icon'].' fa-lg"></i> '.$tab['name'].$pricing_info.'</span><span class="hidden-md hidden-lg"><i class="fa '.$tab['icon'].' fa-2x"></i></span></a>
        		</li>
        	';

        	$counter++;
		}
														
		$rowHTML .= '</ul>';

		$rowHTML .= $contentHTML;
		

		return $rowHTML;
	}

	function buildTabContent($SERVICE_TABS, $active) {
		global $T, $NEW_QUOTE;

		$rowHTML = '
			<div class="tab-content">
		';

		// NEW_QUOTE pretty much combines all the forms into 1 vs having multi forms

		foreach($SERVICE_TABS as $tab) {
			$rowHTML .= '<div class="tab-pane '.($active == $tab['id'] ? 'active' : '').'" id="'.$tab['id'].'">';
			if(! $NEW_QUOTE) {
			$rowHTML .= '	
							<form class="form-inline" method="post" action="task_'.$tab['id'].'.php" enctype="multipart/form-data">
								<input type="hidden" name="taskid" value="'.$GLOBALS['ORDER_DETAILS']['id'].'">
								<input type="hidden" name="type" value="'.$T['type'].'">
								<input type="hidden" name="order_number" value="'.$GLOBALS['ORDER_DETAILS'][$T['order']].'">
			';
			}
			$rowHTML .= 		buildContent($tab);
			if(! $NEW_QUOTE) {
			$rowHTML .= '	</form>';
			}
			$rowHTML .= '</div>';
		}

		$rowHTML .= '
			</div>
		';

		return $rowHTML;
	}

	function buildContent($tab) {
		global $ORDER, $ORDER_DETAILS, $T, $QUOTE_TYPE, $QUOTE_DETAILS, $SERVICE_LABOR_QUOTE, $NEW_QUOTE, $manager_access, $engineering_access;

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
			// Generate a different table if partid is used
			if($ORDER_DETAILS['partid']) {
				$rowHTML .= '
					<div class="row row-no-margin">
						<table class="table table-condensed">
							<thead>
								<tr>
									<th class="col-sm-3">Description</th>
									<th class="col-sm-2"><span class="line"></span> Serial(s)</th>
									<th class="col-sm-2"><span class="line"></span> RMA#</th>
									<th class="col-sm-2"><span class="line"></span> REFS</th>
									<th class="col-sm-3"><span class="line"></span> Notes</th>
								</tr>
							</thead>
							<tbody>
								'.buildDetails().'
							</tbody>
						</table>
					</div>
				';
			} else {
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
			}
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
									<option value="">- Select -</option>
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

				<button class="btn btn-warning btn-sm pull-right" name="closeout" value="true" type="submit" style="margin-right: 10px;">Closeout</button>
			';
		} else if($tab['id'] == 'labor') {
			$labor_hours = ($ORDER_DETAILS['labor_hours']?:($QUOTE_DETAILS['labor_hours']?:0));
			$labor_rate = ($ORDER_DETAILS['labor_rate']?:($QUOTE_DETAILS['labor_rate']?:0));
			$SERVICE_LABOR_QUOTE = (($ORDER_DETAILS['labor_rate'] AND $ORDER_DETAILS['labor_hours']) ? $ORDER_DETAILS['labor_hours'] * $ORDER_DETAILS['labor_rate'] : (($QUOTE_DETAILS['labor_rate'] AND $QUOTE_DETAILS['labor_hours']) ? $QUOTE_DETAILS['labor_hours'] * $QUOTE_DETAILS['labor_rate'] : 0));

			if ($manager_access) {
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
									<input class="form-control input-sm labor_hours" type="text" name="labor_hours" placeholder="Hours" value="'.($labor_hours?:'').'" '.($QUOTE_TYPE?'':'disabled=""').'>
									<span class="input-group-addon"><i class="fa fa-clock-o" aria-hidden="true"></i></span>
									'.($QUOTE_DETAILS['id']?'<a style="margin-left: 10px;" href="/quoteNEW.php?taskid='.$QUOTE_DETAILS['id'].'&tab=labor"><i class="fa fa-pencil"></i></a>':'').'
								</div>
							</td>
							<td>
							<div class="input-group" style="max-width: 200px">
								<span class="input-group-addon">$</span>
								<input style="max-width: 150px;" class="form-control input-sm labor_rate" type="text" name="labor_rate" placeholder="Rate" value="'.($labor_rate?:'').'" '.($QUOTE_TYPE?'':'disabled=""').'>
								'.($QUOTE_DETAILS['id']?'<a class="pull-right" style="margin-left: 10px; margin-top: 5px;" href="/quoteNEW.php?taskid='.$QUOTE_DETAILS['id'].'&tab=labor"><i class="fa fa-pencil"></i></a>':'').'
							</div>
							</td>
							<td>
								<span style="border: 1px solid #468847; display: block; padding: 3px 10px;">$'.number_format($SERVICE_LABOR_QUOTE, 2, '.', '').'</span>
							</td>
						</tr>
					</tbody>
				</table>
				';
			}

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
							'.(($manager_access) ? '<th class="col-sm-1 text-right"><span class="line"></span> Cost</th><th class="col-sm-1' : '<th class="col-sm-2').' text-right"></th>
						</tr>
					</thead>
					<tbody>
				';
				if ($manager_access) {
					$rowHTML .= '
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
							'.(($manager_access) ? '<td></td>' : '').'
							<td>
								<button class="btn btn-sm btn-primary pull-right" type="submit">
									<i class="fa fa-plus"></i>
								</button>
							</td>
						</tr>
					';
				}
				$rowHTML .= buildLabor($ORDER_DETAILS['id']);

				// This is here because buildlabor calculates the global cost of labor based on user clockins
				$labor_progress = 100*round(($GLOBALS['SERVICE_LABOR_COST']/($labor_hours * $labor_rate)),2);

				$progress_bg = '';
				if ($labor_progress>=100) { $progress_bg = 'bg-danger'; }
				else if ($labor_progress<50) { $progress_bg = 'bg-success'; }

				if ($manager_access) {
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
							<td colspan='.(($manager_access) ? '2' : '1').'></td>
							<td class="text-right">
								<strong>$'.number_format($GLOBALS['SERVICE_LABOR_COST'],2).'</strong>
							</td>
							<td> </td>
						</tr>
					';
				}
				$rowHTML .= '
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
						<strong>Milage Rate: '.($manager_access ? '<input style="max-width: 100px;" class="form-control input-xs" name="mileage" value='.$ORDER_DETAILS['mileage_rate'].' >' : $ORDER_DETAILS['mileage_rate'] . 'test').'</strong>
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
						'.(($engineering_access) ? '<th>Cost</th>' : '').'
						'.(($engineering_access) ? '<th>Quoted</th>' : '').'
						<th><button type="submit" class="btn btn-default btn-sm btn-os text-primary import_button pull-right" disabled>Convert <i class="fa fa-level-down"></i></button></th>
					</tr>
					</thead>
					<tbody>
						'.buildOutsourced($ORDER_DETAILS['id']).'
					</tbody>
				</table>
			';
		} else if($tab['id']=="outside" AND $QUOTE_TYPE) {
			if($NEW_QUOTE) {
				$rowHTML .= '
					<div class="alert alert-warning text-center" role="alert">
						Quote must be generated first before quoting outsourced orders.
					</div>
				';
			}
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
								<input class="form-control input-sm" type="text" name="os_description" value="">
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
								<button class="btn btn-primary btn-sm pull-right os_expense_add" '.($NEW_QUOTE ? 'disabled' : '').'>
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
				<div id="sticky-footer" style="min-height: 220px;">
					<ul id="bxslider-pager">
						<li data-slideIndex="0" class="file_container">
							<a href="#" class="upload_link" style="text-decoration: none;">
								<div class="dropImage" style="width: 200px; height: 200px; background: #E9E9E9;">
									<i class="fa fa-plus-circle" aria-hidden="true"></i>
								</div>
							</a>

							<input type="file" class="upload imageUploader" name="filesImage" accept="image/*" value="">
						</li>

						'.buildImages($ORDER_DETAILS['id']).'
					</ul>
				</div>
			';
		} else if($tab['id']=="materials") {
			if($T['type'] == 'service_quote') {
				$rowHTML .= '
					<div class="table-responsive">
						<table class="table table-condensed table-striped">
							<thead>
								<tr>
									<th class="col-md-3">Material</th>
									<th class="col-md-2">Qty &amp; Cost (ea)</th>

									<th class="col-md-3">Leadtime &amp; Due Date</th>
									<th>Markup</th>
									<th>Quoted Total</th>
									<th class="" style="padding-right:0px !important">
									</th>
								</tr>
							</thead>
							<tbody>
								'.buildMaterials($ORDER_DETAILS['id'], $T).'
							</tbody>
						</table>
					</div>
				';
			} else {
				$rowHTML .= '
					<div class="table-responsive">
						<table class="table table-condensed table-striped">
							<thead>
								<tr>
									<th>
										Material
									</th>
									<th>
										Requested<br/>
										<span class="info">By Engineering</span>
									</th>
									<th>
										Installed<br/>
										<span class="info">Qty used on this task</span>
									</th>
									<th>
										Outstanding<br/>
										<span class="info">Requested less Installed</span>
									</th>
									<th>
										Qty<br/>
										<span class="info"><i class="fa fa-level-down"></i> Click qtys to view Inventory</span>
									</th>
									'.(($manager_access) ? '<th>Cost</th>':'').'
									<th class="text-right">
										<a target="_blank" href="/purchases.php?taskid='.$ORDER_DETAILS['id'].'&filter=all" class="btn btn-sm btn-warning" style="margin-left: 10px;" title="View Purchases" data-toggle="tooltip" data-placement="bottom">P</a>
										'.(($engineering_access) ? '<a class="btn btn-default btn-sm text-primary" href="market.php?list_type='.$T['type'].'&listid='.$GLOBALS['taskid'].'" title="Edit Materials" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-pencil"></i></a>' : '').'
									</th>
								</tr>
							</thead>
							<tbody>
								'.buildMaterials($ORDER_DETAILS['id'], $T).'
							</tbody>
						</table>
					</div>
				';
			}
		}

		// $rowHTML .= '</div>';

		return $rowHTML;
	}

	// Building the activity rows
	function buildActivity() {
		global $ORDER_DETAILS, $T, $ORDER;

		$rowHTML = '';
		$activity_notes = getActivities($ORDER_DETAILS, $T, $ORDER);

		foreach($activity_notes as $note) {
			$rowHTML .= '
				<tr>
					<td>'.format_date($note['datetime'], 'n/j/y g:ia').'</td>
					<td>'.getContact($note['techid'], 'userid').'</td>
					<td>'.$note['notes'].'</td>
					<td>
						'.($GLOBALS['U']['id'] == $note['techid'] ? '<a href="javascript:void(0);" class="pull-right forward_activity" data-activityid="'.$note['id'].'"><i class="fa fa-envelope-o"></i></a>' : '').'

						'.($GLOBALS['U']['id'] == $note['techid'] ? '<a href="javascript:void(0);" style="margin-right: 10px;" class="pull-right delete_activity" data-activityid="'.$note['id'].'"><i class="fa fa-trash-o text-danger"></i></a>' : '').'
					</td>
				</tr>
			';
		}

		return $rowHTML;
	}

	function partDescription($partid, $desc = true){
		$r = reset(hecidb($partid, 'id'));

	    $display = "<span class = 'descr-label'>".$r['primary_part']." &nbsp; ".$r['heci']."</span>";
	    if($desc)
    		$display .= '<div class="description desc_second_line descr-label" style = "color:#aaa;">'.dictionary($r['manf']).' &nbsp; '.dictionary($r['system']).
				'</span> <span class="description-label">'.dictionary($r['description']).'</span></div>';

	    return $display;
	}

	function buildMaterials($taskid, $T) {
		global $SERVICE_MATERIAL_COST, $SERVICE_MATERIAL_QUOTE, $UNPULLED, $manager_access, $sales_access;

		$rowHTML = '';

		// No taskid means it is something new
		// Like Quote New

		$materials = array();
		$CO_materials = array();

		if($T['type'] == 'service_quote' AND $taskid) {
			$materials = getQuotedMaterials($taskid);
		} else if($taskid) {
			$materials = getMaterials($taskid, $T);
			$CO_materials = getCOMaterials($taskid, $T);
		}
		
		// print_r($materials);
		if($T['type'] != 'service_quote' AND $T['type'] != 'Outsourced') {
			$materials_cost = getMaterialsCost($taskid,$T['item_label']);
		}

		// print_r($materials_cost);

		$SERVICE_MATERIAL_COST = $materials_cost['cost'];

		foreach($materials as $partkey => $row) {

			$cost = 0;
			$partid = $row['partid'];

			// get the total materials cost based on the partid
			foreach($materials_cost['items'] as $cost_row) {
				if($cost_row['partid'] == $partid) {
					$cost += $cost_row['cost'];
				}
			}

			$SERVICE_MATERIAL_QUOTE += ($row['quote'] * $row['requested']);
			$totalAvailable = 0;

			// Sum all the available here by going through the avail array
			foreach($row['available'] as $data) {
				$totalAvailable += $data['available'];
			}

			// Build for a quoting page in which we use the bom  tool
			if($T['type'] == 'service_quote') {
				foreach($row as $row2) {
							//<td>'.partDescription($partid).'</td>
					$rowHTML .= '
						<tr>
							<td>'.partDescription($row2['partid']).'</td>
							<td>
								<div class="col-md-4 remove-pad" style="padding-right: 5px;">
									<input class="form-control input-sm part_qty" type="text" name="qty['.$row2['id'].']" placeholder="QTY" value="'.$row2['qty'].'">
								</div>
								<div class="col-md-8 remove-pad">
									<div class="form-group" style="margin-bottom: 0;">
										<div class="input-group">
											<span class="input-group-addon">
												<i class="fa fa-usd" aria-hidden="true"></i>
											</span>
											<input class="form-control input-sm part_amount" type="text" name="amount['.$row2['id'].']" placeholder="0.00" value="'.$row2['amount'].'">
										</div>
									</div>
								</div>
							</td>
							<td class="datetime">										
								<div class="col-md-2 remove-pad">											
									<input class="form-control input-sm date_number" type="text" name="leadtime['.$row2['id'].']" placeholder="#" value="'.$row2['leadtime'].'">
								</div>
								<div class="col-md-4">
									<select class="form-control input-sm date_span">
										<option value="days">Days</option>
										<option value="weeks">Weeks</option>
										<option value="months">Months</option>
									</select>
								</div>										
								<div class="col-md-6 remove-pad">											
									<div class="form-group" style="margin-bottom: 0; width: 100%;">												
										<div class="input-group datepicker-date date datetime-picker" style="min-width: 100%; width: 100%;" data-format="MM/DD/YYYY">										            
											<input type="text" name="delivery_date['.$row2['id'].']" class="form-control input-sm delivery_date" value="">										            
											<span class="input-group-addon">										                
												<span class="fa fa-calendar"></span>										            
											</span>										        
										</div>											
									</div>										
								</div>									
							</td>
							<td>
								<div class="form-group" style="margin-bottom: 0;">
									<div class="input-group">
										<input type="text" class="form-control input-sm part_perc" name="profit_pct['.$row2['id'].']" value="'.number_format($row2['profit_pct'],2).'" placeholder="0">
										<span class="input-group-addon">
											<i class="fa fa-percent" aria-hidden="true"></i>
										</span>
									</div>
								</div>
							</td>
							<td>
								<div class="form-group" style="margin-bottom: 0;">										
									<div class="input-group">											
										<span class="input-group-addon">								                
											<i class="fa fa-usd" aria-hidden="true"></i>								           
										</span>								            
										<input type="text" placeholder="0.00" class="form-control input-sm quote_amount" name="quote['.$row2['id'].']" value="'.number_format($row2['quote'],2).'">								        
									</div>									
								</div>
							</td>
							<td>
								<i class="fa fa-times text-danger cancel_quote_request pull-right" data-quoteid="'.$row2['id'].'" aria-hidden="true" data-toggle="tooltip" data-placement="bottom" data-original-title="Delete Material"></i>
							</td>
					';

					//$SERVICE_MATERIAL_COST += $row2['quote'];
					$SERVICE_MATERIAL_QUOTE += $row2['quote'];

					$rowHTML .= '
						</tr>
					';
				}
			
			// Build for a standard repair and service page
			} else {
				$options = false;

				$purchase_ids = '';
				$OG_PR_ids = array();

				// Use this variable to track the pr status
				// aka Active as no po_number, and Closed as ordered
				// Void = no options
				$pr_status = 'Active';

				foreach($row['requests'] as $pr_row) {
					// Set the original purchase request id here
					$OG_PR_ids[] = $pr_row['purchase_request_id'];

					// $purchase_ids[] = $pr_row['purchase_request_id'];
					if ($purchase_ids) { $purchase_ids .= ','; }
					$purchase_ids .= $pr_row['purchase_request_id'];

					// If void then the entire line is considered complete, even if there is outstanding
					if($pr_row['status'] == 'Void') {
						$pr_status = 'Void';
						break;
					}

					//if($pr_row['po_number'] != '' OR $pr_row['status']=='Closed') {
					if($pr_row['status']=='Closed') {
						// PR has been ordered
						$pr_status = 'Closed';
					}
				}

				if(count($row['available']) > 1) {
					$options = true;
				}

				if ($pr_status=='Active' AND ($row['requested']>$row['installed'])) {
					$UNPULLED = true;
				}

				$rowHTML .= '
					<tr>
						<td>'.partDescription($partid).'</td>
						<td>'.$row['requested'].' '.(($manager_access OR $sales_access) ? '<a href="purchase_requests.php" title="View Requests" data-toggle="tooltip" data-placement="right"><i class="fa fa-tags"></i></a>' : '').'</td>
						<td>'.$row['installed'].'</td>
						<td>'.(($row['requested']-$row['installed']) >= 0 ?($row['requested']-$row['installed']):0).'</td>
						<td>
							<div class="input-group" style="max-width: 150px;">
								<span class="input-group-btn" data-toggle="tooltip" data-placement="left" title="" data-original-title="Available">
									<a class="btn btn-default input-sm class_available" href="inventory.php?partid='.$partid.'"><strong>'.$totalAvailable.'</strong></a>
								</span>
								<input type="text" class="form-control input-sm material_pull" data-partid="'.$partid.'" '.(! $options ? 'name="partids['.$partid.']"' : '').' value="" '.((($row['requested']-$row['installed']) <= 0 OR $pr_status == 'Void' OR $pr_status=='Closed') ? 'disabled' : '').'>
								<span class="input-group-btn">
									<button class="btn btn-default btn-sm pull-right material_submit" title="Install entered qty" data-toggle="tooltip" data-placement="right"><img src="/img/build-primary.png" /></button>
								</span>
							</div>
						</td>
						'.(($manager_access) ? '<td>$'.number_format(($row['installed'] ? $cost : 0),2,'.','').'</td>' : '').'
						<td>
				';

				if($pr_status != 'Void' AND $pr_status<>'Closed') {
					if($row['installed'] < $row['requested'] AND ($row['installed'] > 0 AND $row['requested'] > $row['installed'] OR $pr_status != 'Active')) {
						$rowHTML .= '
									<i class="fa fa-archive complete_part pull-right text-primary" data-purchase_id="'.$purchase_ids.'" aria-hidden="true" title="Archive" data-toggle="tooltip" data-placement="top"></i>
						';
					} else if($row['installed'] == 0 OR ! $row['installed']) {
						$rowHTML .= '
									<i class="fa fa-times text-danger cancel_request pull-right" data-purchase_id="'.$purchase_ids.'" data-partid="'.$partid.'" title="Cancel Request" data-toggle="tooltip" data-placement="top" aria-hidden="true"></i>
						';
					}
				}
				$rowHTML .= '
						</td>
					</tr>
				';

				// If there is more than 1 option available then list them out here
				if($options) {
					$stock = 'Stock';

					foreach($row['available'] as $row2) {

						if(in_array($row2['pr_id'], $OG_PR_ids)) {
							if($row2['po_number']) {
								$stock = $row2['po_number'].' <a target="_blank" href="/PO'.$row2['po_number'].'"><i class="fa fa-arrow-right"></i></a></span>';
							}
						}

						$rowHTML .= '
							<tr class="part_'.$partid.'_options grey" style="display:none;">
								<td class="">
									<div class="">
										<div class="col-sm-6">'.$stock.'</div>
										<div class="col-sm-6 text-right">'.$row2['serial'].'</div>
									</div>
								</td>
								<td>'.getLocation($row2['locationid']).'</td>
								<td>'.getCondition($row2['conditionid']).'</td>
								<td></td>
								<td>
									<div class="input-group" style="max-width: 150px;">
										<input type="text" class="form-control input-sm material_options" data-partid="'.$partid.'" data-serial="'.$row2['serial'].'" data-locationid="'.$row2['locationid'].'" data-conditionid="'.$row2['conditionid'].'" value="">

										<span class="input-group-btn">
											<button class="btn btn-default input-sm class_available" disabled=""><strong>'.$row2['available'].'</strong></button>
										</span>
									</div>
								</td>
								<td></td>
								<td></td>
							</tr>
						';
					}
				}
			}
			
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
			$SERVICE_EXPENSE_COST += ($r['units']?$r['amount']*$r['units']:$r['amount']);

			$rowHTML .= '
						<tr>
							<td>'.format_date($r['expense_date']).'</td>
							<td>'.getUser($r['userid']).'</td>
							<td>'.getCategory($r['categoryid']).'</td>
							<td>'.getFinanceName($r['financeid']).'</td>
							<td>'.getCompany($r['companyid']).'</td>
							<td class="td-units hidden">'.($r['units']?:0).'</td>
							<td>$'.number_format(($r['units']?$r['amount']*$r['units']:$r['amount']) ,2,'.','').'</td>
							<td>'.$r['description'].'</td>
							<td>
								'.($r['reimbursement']?'Yes': 'No').'

								'.($r['file']?'<a href="'.$r['file'].'" target="_new" style="margin-left: 10px;"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></a>':'').'

								<div class="pull-right">
									<a href="javascript:void(0);" class="delete_expense" data-expenseid="'.$r['id'].'">
										<i class="fa fa-trash text-danger"></i>
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
						<a href="javascript:void(0);" class="pull-right document_delete" data-delete="'.$row['id'].'"><i class="fa text-danger fa-trash fa-4"></i></a>

						<input class="pull-right" type="checkbox" name="files[]" value="'.$row['id'].'" style="margin-right: 10px;">
					</td>
				</tr>
			';
		}

		return $rowHTML;
	}

	function buildLabor($taskid) {
		global $T, $SERVICE_LABOR_COST, $QUOTE_DETAILS, $U, $manager_access, $sales_access, $ASSIGNED;

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
			$labor_data[$r['userid']]['lead'] = $r['lead'];
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

			$user_ln = '';
			if ($manager_access OR $U['id']==$userid) {
				$user_ln = '<a href="timesheet.php?userid='.$userid.'&taskid='.$taskid.'">'.getUser($userid).'</a>';
			} else {
				$user_ln = getUser($userid);
			}

			$rowHTML .= '
				<tr '.($row['status'] == 'inactive' ? 'class="labor-inactive"' : '').'>
					<td>'.$user_ln.'</td>
					<td>'.format_date($row['start_datetime'], 'n/j/y g:ia').'</td>
					<td>'.format_date($row['end_datetime'], 'n/j/y g:ia').'</td>
					<td>'.(($sales_access OR $ASSIGNED==='LEAD' OR $U['id']==$userid) ? toTime($totalSeconds).'<br> &nbsp; <span class="info">'.timeToStr(toTime($totalSeconds)).'</span>' : '').'</td>
					'.(($manager_access) ? '<td class="text-right">$'.number_format($totalPay,2,'.','').'</td>' : '').'
					<td>'.
						(($row['status'] != 'inactive' AND $manager_access) ? 
							'
							<a href="task_labor.php?taskid='.$taskid.'&type='.$T['type'].'&delete='.$userid.'" class="pull-right" title="Unassign" data-toggle="tooltip" data-placement="left"><i class="fa fa-trash text-danger fa-4"></i></a>
							<input type="radio" name="lead" value="'.$userid.'" class="pull-right lead-role" title="Set as Lead Tech" data-toggle="tooltip" data-placement="left" style="margin-right: 10px;" '.($row['lead'] ? 'checked' : '').'>
							'
						: '' )
					.'</td>
				</tr>
			';
		}

		return $rowHTML;
	}

	function clockedButton($taskid) {
		global $U, $T, $manager_access, $view_mode;

		// Will turn in an array using the is_clockedin function
		$clock = false;
		if ($U['hourly_rate']) {
			$clock = is_clockedin($U['id'], $taskid, $T['item_label']);
			if ($clock===false) {
				$clock = is_clockedin($U['id']);
				$view_mode = true;
			}
		}

		$clockers = '';

		if ($U['hourly_rate']) {
			if ($taskid AND $clock['taskid']==$taskid) {
				$rp_cls = 'default btn-clock';
				$rp_title = 'Switch to Regular Pay';
				$tt_cls = 'default btn-clock';
				$tt_title = 'Switch to Travel Time';

				$travel_rate = getTravelRate($clock['taskid'],$clock['task_label']);
				if ($clock['rate']==$travel_rate) {
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

	function repairButton($taskid, $T) {
		global $ORDER_DETAILS;

		$rowHTML = '';

		// Check if an so is created for this current repair order
		$so_number = shipOrder($taskid, $T);

		// Only for repairs and make sure a status code is set
		if($T['type'] == 'Repair' AND ($ORDER_DETAILS['status_code'] OR $ORDER_DETAILS['repair_code_id'])) { // AND $ORDER_DETAILS['status_code']
			$rowHTML .= '
				<div class="input-group" style="width: 160px; margin-left: 10px;">
			';
			
			if(! $so_number) { 
				$rowHTML .= '
					<a href="/task_edit.php?repair_item_id='.$taskid.'&type='.$T['type'].'&return=true" class="btn btn-default btn-sm text-success"><i class="fa fa-qrcode" aria-hidden="true"></i> Re-stock</a>
					<span class="input-group-addon">or</span>
				';
			}

			$rowHTML .= '
					<a href="/ship_order.php?task_label='.$T['item_label'].'&taskid='.$taskid.'" class="btn btn-default btn-sm text-primary"><i class="fa fa-truck"></i> Ship ('.($so_number?:'NEW').')</a>
				</div>
			';
		}

		return $rowHTML;
	}

	function buildDetails() {
		global $EDIT, $ORDER_DETAILS, $T, $SCANNED;

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
				';
			} 
		} else {
			if($ORDER_DETAILS['partid']) {
				$rowHTML .= '
					<td>'.trim(partDescription($ORDER_DETAILS['partid'], true)).'</td>
				';

				$rowHTML .= '<td>';

				foreach(getDetails($ORDER_DETAILS['id']) as $serial) {
					$SCANNED = true;
					$rowHTML .= $serial.'<br/>'.chr(10);
				}

				$rowHTML .= '</td>';

				// REF
				$rowHTML .= '<td></td>';
				
				$rowHTML .= '
					<td>
						'.$ORDER_DETAILS['ref_1_label'].' '.$ORDER_DETAILS['ref_1'].'<BR>'
						.$ORDER_DETAILS['ref_2_label'].' '.$ORDER_DETAILS['ref_2'].'<BR>
					</td>
				';

				$rowHTML .= str_replace(chr(10),'<BR>',$ORDER_DETAILS['notes']);

			} else {
				$rowHTML .= '
					<td>'.format_address($ORDER_DETAILS['item_id'], '<br/>', true, '', $ORDER['companyid']).'</td>
					<td>'.$ORDER_DETAILS['description'].'</td>
				';
			} 
		}

		$rowHTML .= '</tr>';

		return $rowHTML;
	}

	function getDetails($itemid) {
		$serials = array();

		$query = "SELECT serial_no FROM inventory WHERE repair_item_id = ".res($itemid).";";
		$result = qedb($query);

		while ($row = $result->fetch_assoc()) {
			$serials[] = $row['serial_no'];
		}

		return $serials;
	}

	function buildOutsourced($taskid) {
		global $T, $ORDER_DETAILS, $QUOTE_DETAILS, $SERVICE_OUTSIDE_COST, $engineering_access; 

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

		$query = "SELECT o.companyid, i.* FROM outsourced_orders o, outsourced_items i WHERE  ref_2_label=".fres($T['item_label'])." AND ref_2 = ".res($taskid)." AND o.os_number = i.os_number;";
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

			$SERVICE_OUTSIDE_COST += ($r['price'] * $r['qty'] ?:0);

			$rowHTML .= '
						<tr>
							<td>'.getCompany($r['companyid']).'</td>
							<td>'.$r['notes'].'</td>
							<td>'.$quote_title.'</td>
							<td>'.($r['os_number']?'OS '.$r['os_number']:'').' <a target="_blank" href="/OS'.$r['os_number'].'"><i class="fa fa-arrow-right"></i></a></td>
							'.(($engineering_access) ? '<td>'.($r['price']? '$'.number_format($r['price'] * $r['qty'],2):'').'</td>' : '').'
							'.(($engineering_access) ? '<td>'.$quoted.'</td>' : '').'
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

		if($taskid) {
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
		}

		return $rowHTML;
	}

	function buildImages($taskid) {
		global $T;

		$rowHTML = '';

		$documentData = array();

		// Query all DOcuments that pertain to this order / task
		$query = "SELECT * FROM service_docs WHERE item_id=".res($taskid)." AND item_label =".fres($T['item_label']).";";
		$result = qedb($query);

		// echo $query;

		while($r = mysqli_fetch_assoc($result)) {
			$documentData[] = $r;
		}

		// print_r($documentData);

		foreach($documentData as $row) {
			$imageMimeTypes = array(
				'png',
				'gif',
				'jpeg',
				'jpg',
				'JPG'
			);

			$info = new SplFileInfo($row['filename']);
			$info->getExtension();

			if (in_array($info->getExtension(), $imageMimeTypes)) {
				$rowHTML .= '								    
					<li data-slideIndex="'.$imagecounter.'">
						<a href="'.str_replace($TEMP_DIR,'uploads/',$row['filename']).'">
							<img src="'.($row['filename']).'" style="width: 200px; height: 200px; background: #E9E9E9;">
						</a>
					</li>
					';
			}
		}
		
		return $rowHTML;
	}

	// This function checks for an invoice item
	// If there is one then make the entire task uneditable and don't allow anything to be done
	// Else allow user to do things
	function checkInvoice($taskid) {
		global $T, $QUOTE_TYPE;

		$rowHTML = '';
		$editable = true;

		if($taskid) {
			$query = "SELECT * FROM invoice_items WHERE task_label = ".fres($T['item_label'])." AND taskid = ".res($taskid).";";
			$result = qedb($query);

			if(mysqli_num_rows($result) > 0) {
				$editable = false;
			}
		}

		// No result means no invoices generated for this task
		// Allow user to save or create stuff
		$status = 'disabled';

		// Edtable means no invoice
		// But if not edtable (has invoice and no ticket status also allow the user to complete the order)
		if($editable OR (!$editable AND ! $GLOBALS['ticketStatus'])) {
			$status = '';
		}

		$rowHTML = '
				<button class="btn btn-md btn-success pull-right '.($QUOTE_TYPE ? 'save_quote' : 'complete_order').'" '.(! $QUOTE_TYPE ? 'data-toggle="modal" data-target="#modal-complete"' : '').' '.$status.'>
					<i class="fa fa-floppy-o" aria-hidden="true"></i>
					'.($QUOTE_TYPE ? 'Save' : ($GLOBALS['ticketStatus']?'Change Status':'Complete')).'
				</button>
		';

		return $rowHTML;
	}

	if($ORDER_DETAILS['repair_code_id']) {
		$ORDER_DETAILS['status_code'] = $ORDER_DETAILS['repair_code_id'];
	}

	if($ORDER_DETAILS['status_code']) {
		$ticketStatus = getStatusCode($ORDER_DETAILS['status_code'], $type);
	}

	$clockedButton = clockedButton($ORDER_DETAILS['id']);

	// declared in clockedButton()
	if ($view_mode) {
		$manager_access = false;
		$sales_access = false;
		$engineering_access = false;
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
			float: left;
			position: relative;
			margin-right: 12px;
		}

		.table td {
			vertical-align: top !important;
		}

		.grey, .grey td {
			background-color: rgba(0,0,0,.02) !important;
		}

		.complete_part, .cancel_request, .cancel_quote_request {
			cursor: pointer;
			font-size: 14px;
			margin-top: 7px;
		}

		.tab-content {
			overflow: visible !important;
		}

		.ticket_status_danger {
			color: #a94442;
		}
		.ticket_status_success {
			color: #3c763d;
		}
		.ticket_status_warning {
			color: #8a6d3b;
		}
	</style>
</head>
<body data-order-type="<?=$T['type']?>" data-taskid="<?=$taskid;?>" data-techid="<?=$GLOBALS['U']['id'];?>">

<?php include_once 'inc/navbar.php'; ?>

<?php 
	// If it is not a quote then load in the completing mechanism
	if(! $QUOTE_TYPE) {
		include_once $_SERVER["ROOT_DIR"].'/modal/lici.php';
		include_once $_SERVER["ROOT_DIR"].'/modal/complete_service.php';
	}
?>

<!-- FILTER BAR -->
<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px; max-height:60px;">
	<form class="form-inline" method="get" action="task_status.php" enctype="multipart/form-data" id="filters-form" >

	<input type="hidden" name="taskid" value="<?=$ORDER_DETAILS['id'];?>">
	<input type="hidden" name="type" value="<?=$T['type'];?>">
	<input type="hidden" name="order_number" value="<?=$ORDER_DETAILS[$T['order']];?>">

	<div class="row" style="padding:8px">
		<div class="col-sm-5">
			<div class="pull-left" style="margin-right: 10px;">
				<?= $clockedButton; ?>
			</div>
			<?=repairButton($taskid, $T);?>
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
			<?=checkInvoice($ORDER_DETAILS['id']);?>
		</div>
	</div>

	</form>
</div>

<?php 
	// Special form case for new quotes
	if($NEW_QUOTE OR $QUOTE_TYPE) { 
		echo '<form class="form-inline" id="quote_form" method="post" action="task_quote.php" enctype="multipart/form-data">';
	} 
	
	// If this is not a first time generating a quote then disable the sidebar edit
	if(! $NEW_QUOTE) {
		$EDIT = false;
	}
?>

<div class="container-fluid">
	<?php include 'sidebar.php'; ?>
	<div id="pad-wrapper">
		<?php
			if ($ticketStatus) {
				echo '
			
					<div class="alert alert-default" style="padding:5px; margin:0px">
						<h3 class="text-center">
							<span class="ticket_status_'.(strpos(strtolower($ticketStatus), 'unrepairable') !== false || strpos(strtolower($ticketStatus), 'voided') !== false || strpos(strtolower($ticketStatus), 'canceled') !== false ? 'danger' : (strpos(strtolower($ticketStatus), 'trouble') ? 'warning' : 'success')).'">' .ucwords($ticketStatus) . '</span>
						</h3>
					</div>
					<BR>
				';
			}
		?>

	<!-- <form class="form-inline" method="get" action="" enctype="multipart/form-data" > -->
		<?=mainStats();?>

		<br>

		<?=$pageHTML;?>
	<!-- </form> -->
	</div><!-- pad-wrapper -->
</div>
<?php 
	// Special form close
	if($NEW_QUOTE) { 
		echo '</form>';
	} 
?>

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<?php if(! $ticketStatus) { ?>
	<script type="text/javascript" src="js/lici.js"></script>
<?php } ?>

<script type="text/javascript">
	function completePart(data) {
		data.closest('form').submit();
	}

	function cancelPart(data) {
		data.closest('form').submit();
	}

	$(document).ready(function(e) {
		slid = 1957;

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

		$('.cancel_request').click(function(e) {
			var deleteid = $(this).data('purchase_id');

			if (confirm("Please confirm removal of part.")) {
				if(deleteid) {
					var input = $("<input>").attr("type", "hidden").attr("name", "cancel").val(deleteid);
					//console.log(input);
					$(this).closest('form').append($(input));
				}

				$(this).closest('form').submit();
			}
		});

		$('.complete_part').click(function(e) {
			var purchase_id = $(this).data('purchase_id');

			if (confirm("Please confirm completion of part.")) {
				if(purchase_id) {
					var input = $("<input>").attr("type", "hidden").attr("name", "complete").val(purchase_id);
					//console.log(input);
					$(this).closest('form').append($(input));
				}

				$(this).closest('form').submit();
			}
		});

		$('.save_quote').click(function(e){
			e.preventDefault();

			$('#quote_form').submit();
		});

		$('.delete_user').click(function(e){
			e.preventDefault();

			var deleteid = $(this).data('delete');

//			if (confirm("Please confirm removal of assigned user.")) {
				if(deleteid) {
					var input = $("<input>").attr("type", "hidden").attr("name", "delete").val(deleteid);
					//console.log(input);
					$(this).closest('form').append($(input));

					$(this).closest('form').submit();
				}
//			}
		});

		$('.document_delete').click(function(e){
			e.preventDefault();

			var deleteid = $(this).data('delete');

			if (confirm("Please confirm the document.")) {
				if(deleteid) {
					var input = $("<input>").attr("type", "hidden").attr("name", "delete").val(deleteid);
					//console.log(input);
					$(this).closest('form').append($(input));
				}

				$(this).closest('form').submit();
			}
		});
		
		$('.lead-role').change(function() {
			$(this).closest('form').submit();
		});

		$('.imageUploader').change(function() {
			$(this).closest('form').submit();
		});

		$('.material_submit').click(function(e){
			e.preventDefault();
			// First check if any of the special options have values in them
			$('.material_options').each(function() {
				var amount = $(this).val();

				if(amount) {
					var locationid = $(this).data('locationid');
					var conditionid = $(this).data('conditionid');
					var serial = $(this).data('serial');

					var partid = $(this).data('partid');

					var data = {locationid, conditionid, serial};

					// data['locationid'] = locationid;
					// data['conditionid'] = conditionid;
					// data['serial'] = serial;

					var input = '';
					
					if(! serial) {
						input = $("<input>").attr("type", "hidden").attr("name", "partids["+partid+"]["+locationid+"]["+conditionid+"]").val(amount);
					} else {
						input = $("<input>").attr("type", "hidden").attr("name", "partids["+partid+"]["+locationid+"]["+conditionid+"]["+serial+"]").val(amount);
					}
					//console.log(input);
					$(this).closest('form').append($(input));
				}
			});

			$(this).closest('form').submit();
		});

		$('.cancel_quote_request').click(function(e){
			e.preventDefault();

			if (confirm("Are you sure you want to delete this material?")) {
				var quote_id = $(this).data('quoteid');

				var input = $("<input>").attr("type", "hidden").attr("name", "cancel").val(quote_id);
				//console.log(input);
				$(this).closest('form').append($(input));

				$(this).closest('form').submit();
			}
		});

		// $('.complete_part').click(function(e){
		// 	e.preventDefault();

		// 	modalAlertShow('<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Warning','Please confirm you want to complete this part.',true,'completePart', $(this));
		// });

		// $('.cancel_part').click(function(e){
		// 	e.preventDefault();

		// 	modalAlertShow('<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Warning','Please confirm you cancel this request.',true,'cancelRequest', $(this));
		// });

		$(document).on("change", ".os_amount, .os_amount_profit", function(){
			var container = $(this).closest('tr');
			
			var amount = 0;
			var tax = 0;

			var total = 0;

			if(container.find(".os_amount").val()) {
				amount = parseFloat(container.find(".os_amount").val());	
			}

			if(parseFloat(container.find(".os_amount_profit").val())) {
				tax = parseFloat(parseFloat(container.find(".os_amount_profit").val()));	
			}

			total = (amount) + (amount * (tax / 100));

			container.find(".os_amount_total").val(parseFloat(total).toFixed(2));
		});

		$('.material_pull').keyup(function(){
			var partid = $(this).data('partid');

			var val = $(this).val();

			if ( $( ".part_" + partid + "_options" ).length) {
				if(val) {
					$( ".part_" + partid + "_options" ).slideDown("fast");
				} else {
					$( ".part_" + partid + "_options" ).slideUp("fast");
				}
			}
		});
		
		<?php if($ticketStatus) {
			echo '$("#pad-wrapper :input").prop("disabled", true);';
		} ?>
	});
</script>

</body>
</html>
