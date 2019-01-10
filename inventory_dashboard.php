<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/datepickers.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getLocation.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCondition.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getUser.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPart.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/display_part.php';

/***** DAVID *****/
/*
To do:
1) Incoming PO's as top, italicized lines
2) What to do with customer property? In repair?
3) Serial results should show part# in multiple-select dropdown, with a filter on Serial that can be cleared to reveal all part results
*/

	function getSource($id,$type='Purchase') {
		if (! $id) { return false; }

		$T = order_type('Purchase');

		$query = "SELECT ".$T['order']." order_number FROM ".$T['items']." WHERE id = '".res($id)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) {
			return false;
		}
		$r = mysqli_fetch_assoc($result);
		return ($r['order_number']);
	}

	function getAuditIDS() {
		$audit_ids = array();

		$query = "SELECT id, datetime FROM location_audits ORDER BY datetime DESC;";
		$result = qedb($query);

		while($r = qrow($result)) {
			$audit_ids[] = $r;
		}

		return $audit_ids;
	}

	function getAuditID($auditid) {
		// We need to overwrite the locationid with the correct one
		global $locationid;

		$audit_info = array();

		$query = "SELECT * FROM location_audits la, inventory_audits ia WHERE la.id = ".res($auditid)." AND la.id = ia.auditid;";
		$result = qedb($query);

		while($r = qrow($result)) {
			$locationid = $r['locationid'];
			$audit_info[$r['partid']] = $r['qty'];
			$audit_info['datetime'] = $r['datetime'];
		}

		return $audit_info;
	}

	function getAssignments($inventoryid,&$assigned) {
		$assignments = '';

		$query = "SELECT * FROM inventory_dni WHERE inventoryid = '".res($inventoryid)."' ORDER BY datetime DESC; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			if ($assignments) { $assignments .= '<BR>'; }
			// if any record is without an UNassigned field (datetime), trip the flag so we don't show it as available
			if (! $r['unassigned']) { $assigned = true; }

			$assignments .= '<i class="fa fa-tag"></i> '.format_date($r['datetime'],'n/j/y g:ia').' '.
				'Assigned to <a href="javascript:void(0);" class="owner_filter" data-ownerid="'.$r['ownerid'].'">'.getUser($r['ownerid']).'</a>';
		}

		return ($assignments);
	}

	function getCompanyID($order_number,$type='Purchase') {
		if (! $order_number) { return false; }

		$T = order_type('Purchase');

		$query = "SELECT companyid FROM ".$T['orders']." WHERE ".$T['order']." = '".res($order_number)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) {
			return false;
		}
		$r = mysqli_fetch_assoc($result);
		return ($r['companyid']);
	}

	function buildFilter($TYPE) {
		// filters
		global $locationid, $summary_btn, $detail_btn, $visible_btn, $internal_btn, $order_search, $startDate, $endDate, $ownerid, $companyid, $internal, $multiline, $auditid, $search;
		// buttons
		global $goodstock_btn, $badstock_btn, $outstock_btn, $goodstock_text, $badstock_text, $outstock_text;
		// data
		global $TITLE, $TYPE, $goodcount, $badcount, $outcount, $goodstock, $badstock, $outstock;
		$html = '';

		if($TYPE == 'audit') {
			$html = '
				<div class="col-sm-2 col-location">
					<select name="locationid" size="1" class="location-selector" id="location-filter">
			';

			if ($locationid) { 
				$html .= '<option value="'.$locationid.'" selected>'.getLocation($locationid).'</option>'.chr(10); 
			} else { 
				$html .= '<option value="">- Select Location -</option>'; 
			}

			$html .= '
					</select>
				</div>
				<div class="col-sm-1">
				</div>
				<div class="col-sm-1">
				</div>
				<div class="col-sm-4 text-center"><h2 class="minimal">'.$TITLE.'</h2></div>
			';
			
			$html .= '
				<div class="col-sm-1">
				</div>

				<div class="col-sm-2">
					<select class="form-control select2 audit-select" name="auditid" id="audit-filter" placeholder="- Select Audit -">
						<option value="">- Select Audit -</option>
			';
			foreach(getAuditIDS() as $aid) {
				$html .= '<option value="'.$aid['id'].'" '.($aid['id'] == $auditid ? 'selected' : '').'>'.format_date($aid['datetime'], 'n/j/y g:i A').'</option>';
			}
			$html .= '
					</select>
				</div>
				<div class="col-sm-1">
					<button class="btn btn-sm btn-success pull-right" id="audit_save"><i class="fa fa-save"></i> Save</button>
				</div>
			';
		} else {
			$html = '
				<div class="col-sm-1">
						<div class="btn-group">
							<button type="submit" name="inventory-summary" id="inventory-summary" value="1" class="btn btn-'.$summary_btn.' btn-xs left" data-toggle="tooltip" data-placement="bottom" title="Summary Results (default)"><i class="fa fa-th-large"></i></button>
							<button type="submit" name="inventory-detail" id="inventory-detail" value="1" class="btn btn-'.$detail_btn.' btn-xs right" data-toggle="tooltip" data-placement="bottom" title="Detail Results"><i class="fa fa-th"></i></button>
						</div>
						<div class="btn-group pull-right">
							<button type="submit" name="inventory-visibility" id="inventory-visibility" value="1" class="btn btn-'.$visible_btn.' btn-xs left" data-toggle="tooltip" data-placement="bottom" title="Visible Inventory"><i class="fa fa-eye"></i></button>
							<button type="submit" name="inventory-internal" id="inventory-internal" value="1" class="btn btn-'.$internal_btn.' btn-xs right" data-toggle="tooltip" data-placement="bottom" title="Internal Use"><i class="fa fa-eye-slash"></i></button>
						</div>
				</div>
				<div class="col-sm-1 col-location">
					<select name="locationid" size="1" class="location-selector" id="location-filter">
			';

			if ($locationid) { 
				$html .= '<option value="'.$locationid.'" selected>'.getLocation($locationid).'</option>'.chr(10); 
			} else { 
				$html .= '<option value="">- Select Location -</option>'; 
			}

			$datepickers = datepickers($startDate,$endDate);

			$html .= '
					</select>
				</div>
				<div class="col-sm-1">
						<div class="input-group">
							<input type="text" name="order_search" value="'.$order_search.'" class="form-control input-sm" placeholder="PO/RO/RMA...">
							<span class="input-group-btn">
								<button class="btn btn-sm btn-primary" type="submit"><i class="fa fa-filter"></i></button>
							</span>
						</div>
				</div>
				<div class="col-sm-2">
					'.$datepickers.'
				</div>
				<div class="col-sm-2 text-center"><h2 class="minimal">'.$TITLE.'</h2></div>
			';
			
			if ($internal) {
				$html .= '
					<div class="col-sm-1">
						<select name="ownerid" size="1" class="form-control user-selector">
				';

				if($ownerid) {
					$html .= '<option value="'.$ownerid.'" selected>'.getUser($ownerid).'</option>';
				}

				$html .= '
						</select>
					</div>
					<div class="col-sm-1">
				';
			} else {
				$html .= '
					<div class="col-sm-2">
				';
			}
			$html .= '
					<div class="input-group">
			';

			if ($multiline) {
				$html .= '
					<textarea name="s2" rows="2" class="form-control input-sm" placeholder="Part/Serial...">'.$search.'</textarea>
				';
			} else {
				$html .= '
					<input type="text" name="s2" value="'.$search.'" class="form-control input-sm" placeholder="Part/Serial...">
				';
			}

			$html .= '
						<span class="input-group-btn">
							<button class="btn btn-sm btn-primary" type="submit"><i class="fa fa-filter"></i></button>
						</span>
					</div>
				</div>
				<div class="col-sm-1 text-center">
					<div class="btn-group">
						<button class="btn btn-'.$goodstock_btn.' btn-narrow btn-sm left" name="btn-goodstock" value="'.!$goodstock.'" data-toggle="tooltip" data-placement="bottom" title="Good Stock"><i class="fa fa-dot-circle-o'.$goodstock_text.'"></i> '.$goodcount.'</button>
						<button class="btn btn-'.$badstock_btn.' btn-narrow btn-sm middle" name="btn-badstock" value="'.!$badstock.'" data-toggle="tooltip" data-placement="bottom" title="Bad Stock"><i class="fa fa-circle'.$badstock_text.'"></i> '.$badcount.'</button>
						<button class="btn btn-'.$outstock_btn.' btn-narrow btn-sm right" name="btn-outstock" value="'.!$outstock.'" data-toggle="tooltip" data-placement="bottom" title="Zero Stock"><i class="fa fa-minus-circle'.$outstock_text.'"></i> '.$outcount.'</button>
					</div>
				</div>
				<div class="col-sm-2">
					<button data-toggle="tooltip" name="view" value="print" data-placement="bottom" title="" data-original-title="Print View" class="btn btn-default btn-sm filter-types pull-right">
						<i class="fa fa-print" aria-hidden="true"></i>
					</button>

					<div class="form-group pull-right" style="margin-right: 10px;">
						<select name="companyid" size="1" class="company-selector">
							<option value="">- Select Company -</option>
			';

			if ($companyid) { 
				$html .= '<option value="'.$companyid.'" selected>'.getCompany($companyid).'</option>'.chr(10); 
			}

			$html .= '
						</select>
						<button class="btn btn-sm btn-primary" type="submit"><i class="fa fa-filter"></i></button>
					</div>
				</div>
			';
		}

		return $html;
	}

	$pricing_header1 = '';
	$pricing_header2 = '';
	if (in_array("1", $USER_ROLES) OR in_array("4", $USER_ROLES) OR in_array("5", $USER_ROLES)) {
		$pricing_header1 = 'Cost';
		$pricing_header2 = 'Actual Cost';
	}

	$search = '';
	if (isset($_REQUEST['s']) AND trim($_REQUEST['s'])) { $search = trim($_REQUEST['s']); }
	else if (isset($_REQUEST['s2']) AND trim($_REQUEST['s2'])) { $search = trim($_REQUEST['s2']); }
	else if (isset($_REQUEST['search']) AND trim($_REQUEST['search'])) { $search = trim($_REQUEST['search']); }
	$_REQUEST['s'] = '';

	if (! isset($self_url)) { $self_url = 'inventory.php'; }
	
//	$save_form = ($TYPE == 'audit' ? 'save-audit.php' : 'save-inventory.php');
	if ($TYPE=='audit') {
		$save_form = 'save-audit.php';
	} else {
		$save_form = 'save-inventory.php';
	}

	$view = '';
	if (isset($_REQUEST['view'])) { $view = trim($_REQUEST['view']); }

	$taskid = 0;
	if (isset($_REQUEST['taskid']) AND $_REQUEST['taskid']>0) { $taskid = trim($_REQUEST['taskid']); }
	$task_label = '';
	if (isset($_REQUEST['task_label'])) { $task_label = trim($_REQUEST['task_label']); }

	$locationid = 0;
	if (isset($_REQUEST['locationid']) AND $_REQUEST['locationid']) { $locationid = strtoupper(trim($_REQUEST['locationid'])); }

	$companyid = 0;
	if (isset($_REQUEST['companyid']) AND $_REQUEST['companyid']>0) { $companyid = trim($_REQUEST['companyid']); }

	$ownerid = 0;
	if (isset($_REQUEST['ownerid']) AND $_REQUEST['ownerid']>0) { $ownerid = trim($_REQUEST['ownerid']); }

	$inventory_descr = 0;
	if (isset($_REQUEST['inventory-descr']) AND $_REQUEST['inventory-descr']>0) { $inventory_descr = trim($_REQUEST['inventory-descr']); }

	$partids = array();
	if (isset($_REQUEST['partids']) AND is_array($_REQUEST['partids'])) {
		// convert into partid-keyed array for use below with other $partids usage
		$ids = $_REQUEST['partids'];
		foreach ($ids as $partid) {
			$H = hecidb($partid,'id');
			$partids[$partid] = $H[$partid];
		}
	}

	$auditid = 0;
	$audit_info = array();
	if (isset($_REQUEST['auditid']) AND $_REQUEST['auditid']>0) { 
		$auditid = trim($_REQUEST['auditid']); 

		$audit_info = getAuditID($auditid);

		if($audit_info){
			$TITLE .= ' '.format_date($audit_info['datetime'], "n/j/y g:i A");
		}
	}
	

	$expiry = time() + (7 * 24 * 60 * 60);
	$past_time = time() - 1000;
	$summary = '';
	$detail = '';
	if (isset($_REQUEST['inventory-summary'])) {
		$summary = $_REQUEST['inventory-summary'];
		setcookie('inventory-summary',$_REQUEST['inventory-summary'],$expiry);
		setcookie('inventory-detail',false,$past_time);
	} else if (isset($_REQUEST['inventory-detail'])) {
		$detail = $_REQUEST['inventory-detail'];
		setcookie('inventory-detail',$_REQUEST['inventory-detail'],$expiry);
		setcookie('inventory-summary',false,$past_time);
	} else {
		if (isset($_COOKIE['inventory-summary'])) { $summary = $_COOKIE['inventory-summary']; }
		if (isset($_COOKIE['inventory-detail'])) { $detail = $_COOKIE['inventory-detail']; }
	}

	$visible = false;
	$internal = false;
	if (isset($_REQUEST['inventory-visibility'])) {
		$visible = 1;
		$internal = 0;
		setcookie('inventory-visibility',$_REQUEST['inventory-visibility'],$expiry);
		setcookie('inventory-internal',false,$past_time);
	} else if (isset($_REQUEST['inventory-internal'])) {
		$internal = 1;
		$visible = 0;
		setcookie('inventory-internal',$_REQUEST['inventory-internal'],$expiry);
		setcookie('inventory-visibility',false,$past_time);
	} else {
		if (isset($_COOKIE['inventory-visibility'])) { $visible = $_COOKIE['inventory-visibility']; }
//		if (isset($_COOKIE['inventory-internal'])) { $internal = $_COOKIE['inventory-internal']; }
	}
	if ($visible===false AND $internal===false) { $visible = 1; $internal = 0; }

	$order_search = '';
	if (isset($_REQUEST['order_search']) AND trim($_REQUEST['order_search'])) { $order_search = trim($_REQUEST['order_search']); }

	$order_type = '';
	if (isset($_REQUEST['order_type']) AND trim($_REQUEST['order_type'])) { $order_type = trim($_REQUEST['order_type']); }

	$startDate = '';
	if (isset($_REQUEST['START_DATE']) AND $_REQUEST['START_DATE']) {
		$startDate = format_date($_REQUEST['START_DATE'], 'm/d/Y');
	}
	$endDate = date('m/d/Y');
	if (isset($_REQUEST['END_DATE']) AND $_REQUEST['END_DATE']){
		$endDate = format_date($_REQUEST['END_DATE'], 'm/d/Y');
	}
	if ($startDate) {
		$dbStartDate = format_date($startDate, 'Y-m-d').' 00:00:00';
		$dbEndDate = format_date($endDate, 'Y-m-d').' 23:59:59';
	}

	$goodstock_text = ' text-warning';
	$badstock_text = ' text-purple';
	$outstock_text = ' text-danger';
	$goodstock_btn = 'default';
	$badstock_btn = 'default';
	$outstock_btn = 'default';
	$badstock = 0;
	$outstock = 0;
	if (isset($_REQUEST['btn-goodstock'])) {
		if ($_REQUEST['btn-goodstock']==1) {
			$goodstock = 1;
		} else {
			$goodstock = 0;
		}
	} else if (isset($_COOKIE['goodstock'])) {
		$goodstock = $_COOKIE['goodstock'];
	}

	if (isset($_REQUEST['btn-badstock'])) {
		if ($_REQUEST['btn-badstock']) {
			$badstock = 1;
		} else {
			$badstock = 0;
		}
	} else if (isset($_COOKIE['badstock'])) {
		$badstock = $_COOKIE['badstock'];
	}

	if (isset($_REQUEST['btn-outstock'])) {
		if ($_REQUEST['btn-outstock']) {
			$outstock = 1;
		} else {
			$outstock = 0;
		}
	} else if (isset($_COOKIE['outstock'])) {
		$outstock = $_COOKIE['outstock'];
	}

	// if selected, or if no buttons selected, select good stock by default
	if ($goodstock OR (! $goodstock AND ! $badstock AND ! $outstock)) {
		$goodstock = 1;
		setcookie('goodstock',$goodstock,$expiry);
	} else {
		$goodstock_btn = 'default';
		setcookie('goodstock',$goodstock,$past_time);
	}
	if ($badstock) {
		setcookie('badstock',$badstock,$expiry);
	} else {
		$badstock_btn = 'default';
		setcookie('badstock',$badstock,$past_time);
	}
	if ($outstock) {
		setcookie('outstock',$outstock,$expiry);
	} else {
		$outstock_btn = 'default';
		setcookie('outstock',$outstock,$past_time);
	}


	/***** DON'T MOVE THIS CODE: strategically placed so we can activate stock buttons when user is looking for ORDER RESULTS *****/

	// get all purchase_item_id, returns_item_id, repair_item_id and sales_item_id from respective orders matching $order_search
	$ids = array('purchase_item_id'=>array(),'returns_item_id'=>array(),'repair_item_id'=>array(),'sales_item_id'=>array());
	$order_matches = 0;
	if ($taskid AND $task_label) {
		$ids[$task_label][] = $taskid;
		$order_matches++;

		$goodstock = 1;
		$badstock = 1;
		$outstock = 1;
		$detail = 1;
	} else if ($order_search OR $companyid) {
		$goodstock = 1;
		$badstock = 1;
		$outstock = 1;

		$case_types = array('Purchase','Sale','Return','Repair');
		foreach ($case_types as $type) {
			if ($order_type AND $type<>$order_type) { continue; }

			$T = order_type($type);

			$query = "SELECT items.id FROM ".$T['items']." items ";
			if ($companyid) { $query .= ", ".$T['orders']." orders "; }
			$query .= "WHERE 1 = 1 ";
			if ($order_search) { $query .= "AND items.".$T['order']." = '".res($order_search)."' "; }
			if ($companyid) { $query .= "AND items.".$T['order']." = orders.".$T['order']." AND orders.companyid = '".res($companyid)."' "; }
			$query .= "; ";
			$result = qdb($query) OR die(qe().'<BR>'.$query);
			while ($r = mysqli_fetch_assoc($result)) {
				$ids[$T['inventory_label']][] = $r['id'];
				$order_matches++;
			}
		}
	}

	/***** END DONT MOVE *****/


	$multiline = false;
	$part_options = '';
	$part_str = '';
	$qtys = array();
	$inv_rows = '';
	$serial_match = array();//when set, is keyed by partid so results on a given partid only show the discovered serial ($search)
	if ($search) {
		$results = array();
		$lines = explode(chr(10),$search);
		if (count($lines)>1) {
			$multiline = true;
		}

		foreach ($lines as $str) {
			$str = trim($str);

			$H = hecidb($str);
			foreach ($H as $partid => $P) {
				$results[] = $P;

				// gather unique list of partids
				$partids[$partid] = $P;
			}

			$query = "SELECT * FROM inventory WHERE serial_no = '".res($str)."' ";
			if ($internal) { $query .= "AND status = 'internal use' "; }
			$query .= "; ";
			$result = qedb($query);
			if (mysqli_num_rows($result)>0) {
				$goodstock = 1;
				$badstock = 1;
				$outstock = 1;
				$detail = 1;
				$summary = 0;
			}
			while ($r = mysqli_fetch_assoc($result)) {
				if (! isset($partids[$r['partid']])) {
					$P = hecidb($r['partid'],'id');
					$partids[$r['partid']] = $P[$r['partid']];
				}
				$serial_match[$r['partid']][] = $r['serial_no'];
			}
		}
	}

	$partids_csv = '';
	foreach ($partids as $partid => $P) {
		if ($partids_csv) { $partids_csv .= ','; }
		$partids_csv .= $partid;
	}

//	if ($multiline) { $search = ''; }

	// style settings for summary/detail buttons
	$summary_btn = 'default';
	$detail_btn = 'default';
	if ($summary) {
		$summary_btn = 'primary active';
	} else if ($detail) {
		$detail_btn = 'primary active';
	}

	$visible_btn = 'default';
	$internal_btn = 'default';
	if ($visible) {
		$visible_btn = 'primary active';
	} else if ($internal) {
		$internal_btn = 'danger active';
	}

	// placed separately here for purposes of single-user overrides (such as in $order_search) instead of saving cookies
	if ($goodstock) {
		$goodstock_btn = 'warning active';
		$goodstock_text = '';
	}
	if ($badstock) {
		$badstock_btn = 'purple active';
		$badstock_text = '';
	}
	if ($outstock) {
		$outstock_btn = 'danger active';
		$outstock_text = '';
	}

	$records = array();
	if ($partids_csv OR $locationid OR $ownerid OR $internal OR $order_matches OR ($dbStartDate AND $dbEndDate)) {
		$query = "SELECT i.* FROM inventory i ";
		if ($ownerid) { $query .= ", inventory_dni dni "; }
		if ($order_matches>0) {
			$query .= ", inventory_history h ";
		}
		$query .= "WHERE 1 = 1 ";
		if ($partids_csv) { $query .= "AND i.partid IN (".$partids_csv.") "; }
		if ($locationid) {
			if ($locationid=='ALL') {
				$query .= "AND i.status = 'received' ";
			} else if ($locationid=='RC') {
				$query .= "AND i.status = 'received' AND i.locationid >= 156 AND i.locationid <= 180 ";
			} else {
				$query .= "AND i.locationid = '".res($locationid)."' ";
				if ($TYPE=='audit') { $query .= "AND i.status = 'received' "; }
			}
		}
		if ($internal) { $query .= "AND i.status = 'internal use' "; }
		if ($ownerid) { $query .= "AND dni.ownerid = '".res($ownerid)."' AND dni.inventoryid = i.id AND unassigned IS NULL "; }
		if ($order_matches>0) {
			$query .= "AND h.invid = i.id ";
			$subquery = "";
			foreach ($ids as $item_label => $arr) {
				if (count($arr)==0) { continue; }
	
				foreach ($arr as $item_id) {
					if ($subquery) { $subquery .= "OR "; }
					$subquery .= "(h.field_changed = '".$item_label."' AND h.value = '".$item_id."') ";
				}
			}
			if ($subquery) { $query .= "AND (".$subquery.") "; }
		} else {
/*
			if (! $outstock) {
				$query .= "AND (i.status = 'shelved' OR i.status = 'received') ";
				if (! $badstock AND $goodstock) { $query .= "AND i.conditionid > 0 "; }
				if (! $goodstock AND $badstock) { $query .= "AND i.conditionid < 0 "; }
			}
*/
		}
		if ($dbStartDate AND $dbEndDate) {
			$query .= "AND i.date_created BETWEEN CAST('".$dbStartDate."' AS DATETIME) AND CAST('".$dbEndDate."' AS DATETIME) ";
		}
		if ($ownerid) { $query .= "GROUP BY i.id "; }
		if ($multiline) {
			if ($partids_csv) {
				$query .= "ORDER BY FIELD (partid,".$partids_csv.") ";
			}
		} else {
			if ($locationid=='ALL' OR $locationid=='RC') {
				$query .= "ORDER BY i.locationid ";
			} else {
				$query .= "ORDER BY IF(status='received',0,1), IF(conditionid>0,0,1), date_created DESC ";
			}
		}
		$query .= "; ";
//		echo $query.'<BR>';
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			if (isset($serial_match[$r['partid']]) AND array_search($r['serial_no'],$serial_match[$r['partid']])===false) { continue; }//$serial_match[$r['partid']]<>$r['serial_no']) { continue; }
			$r['assigned'] = '';

			if($TYPE == 'audit') {
				if (! $goodstock AND $r['conditionid']>0) { continue; }
				if (! $badstock AND $r['conditionid']<0) { continue; }
				if (! $outstock AND $r['status']<>'received') { continue; }

				$key = $r['partid'];
			} else {
				$key = $r['partid'].'.'.$r['locationid'].'.'.$r['conditionid'].'.'.$r['status'].'.'.$r['purchase_item_id'].'.'.substr($r['date_created'],0,10);
			}

			// gather unique list of partids
			if (! isset($partids[$r['partid']])) {
				$H = hecidb($r['partid'],'id');
				$partids[$r['partid']] = $H[$r['partid']];
			}

			$qty = $r['qty'];
			if ($r['serial_no']) { $qty = 1; }

			if (! isset($records[$key])) {
				$r['qty'] = 0;
				$r['entries'] = array();
				$records[$key] = $r;
			}
			$records[$key]['qty'] += $qty;
			$records[$key]['entries'][] = array('serial_no'=>$r['serial_no'],'repair_item_id'=>$r['repair_item_id'],'status'=>$r['status'],'notes'=>$r['notes'],'id'=>$r['id']);
		}
	}

	$inner_display = ' style="display:none"';
	if ($detail) {
		$inner_display = '';
	}

	// displayed only on first occurrence of a nested/inner table
	$inner_header = '
					<tr class="inner-result"'.$inner_display.'>
						<th class="col-sm-3">Serial</th>
						<th class="col-sm-1">'.$pricing_header2.'</th>
						<th class="col-sm-2">Assignments</th>
						<th class="col-sm-4">Notes</th>
						<th class="col-sm-1">Status</th>
						<th class="col-sm-1">Action</th>
					</tr>
	';

	$goodcount = 0;
	$badcount = 0;
	$outcount = 0;
	$j = 0;

	foreach ($records as $r) {
		$prefix = '';
		$order_number = getSource($r['purchase_item_id'],'Purchase');
		$order_ln = '';

		// exclude results that the user hasn't included
		if (! $internal) {
			if ($r['conditionid']>0 AND $r['status']=='received') { $goodcount += $r['qty']; }
			if ($r['conditionid']<0 AND $r['status']=='received') { $badcount += $r['qty']; }
			if ($r['status']<>'received') { $outcount += $r['qty']; }

			if (! $goodstock AND $r['conditionid']>0) { continue; }
			if (! $badstock AND $r['conditionid']<0) { continue; }
			if (! $outstock AND $r['status']<>'received') { continue; }
		}

		if (! isset($qtys[$r['partid']])) { $qtys[$r['partid']] = 0; }
		$qtys[$r['partid']] += $r['qty'];

		$company = '';
		$company_ln = '';
		if ($order_number) {
			$prefix = 'PO';
			$order_ln = ' <a href="/'.$prefix.$order_number.'" target="_new"><i class="fa fa-arrow-right"></i></a>';
			$cid = getCompanyID($order_number,'Purchase');
			$company = getCompany($cid);
			$company_ln = ' <a href="/company.php?companyid='.$cid.'" target="_new"><i class="fa fa-building"></i></a>';
		}

		$cls = '';
		if ($r['status']=='received') {
			if ($r['conditionid']>0) {
				$cls = 'in-stock';
			} else {
				$cls = 'bad-stock';
			}
		} else {
			$cls = 'out-stock';
		}

		if ($r['status']=='received') { $qty = $r['qty']; }
		else { $qty = '0 <span class="info">('.$r['qty'].')</span>'; }

		// repair link used for each serial
		$repair_ln = '';
		if ($r['status']=='received') {
			$repair_ln = '<li><a href="javascript:void(0);" class="repair"><i class="fa fa-wrench"></i> Send to Repair</i></a></li>';
		}

		// scrap link used for each serial
		$scrap_ln = '';
		if ($r['status']=='received') {
			$scrap_ln = '<li><a href="javascript:void(0);" class="scrap"><i class="fa fa-recycle"></i> Scrap</i></a></li>';
		}

		$assigned_qty = 0;
		$sum_actual = 0;
		$inventoryids = '';
		$inners = '';
		foreach ($r['entries'] as $entry) {
			$status = $entry['status'];
			if ($inventoryids) { $inventoryids .= ','; }
			$inventoryids .= $entry['id'];

			$entry_cls = '';
			$edit_ln = '<li><a href="javascript:void(0);" class="edit-inventory"><i class="fa fa-pencil"></i> Edit this entry</a></li>';
			if ($status=='scrapped') {
				$status = '<i class="fa fa-recycle"></i> '.$status;

				$entry_cls = ' text-danger';
				$edit_ln = '<li><a href="javascript:void(0);"><span class="info"><i class="fa fa-pencil"></i> Edit (disabled)</span></a></li>';
			} else if ($status=='in repair') {
				$status_ln = '';

				$query2 = "SELECT ro_number FROM repair_items WHERE id = '".$entry['repair_item_id']."'; ";
				$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
				if (mysqli_num_rows($result2)>0) {
					$r2 = mysqli_fetch_assoc($result2);
					$status_ln = ' <a href="/RO'.$r2['ro_number'].'"><i class="fa fa-arrow-right"></i></a>';
				}

				$status = '<i class="fa fa-wrench"></i> '.$status.' '.$status_ln;
			}

			$actual_cost = '';
			if ($pricing_header1) {
				$query2 = "SELECT actual FROM inventory_costs WHERE inventoryid = '".$entry['id']."' ORDER BY id DESC LIMIT 0,1; ";
				$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
				if (mysqli_num_rows($result2)>0) {
					$r2 = mysqli_fetch_assoc($result2);
					$actual_cost = format_price($r2['actual'],true,' ');
					$sum_actual += $r2['actual'];
				}
			}

			$assigned = false;
			$assignments = getAssignments($entry['id'],$assigned);

			// append a mark at the top of assignments showing that it's available (UNASSIGNED) if $assigned above wasn't set to true
			if (! $assigned) {
				$assignments = '<div class="text-success"><i class="fa fa-check"></i> UNASSIGNED</div>'.$assignments;
			} else {
				$assigned_qty++;
			}

			$assign_ln = '';
			if ($internal) {
				$assign_ln = '<li><a href="javascript:void(0);" class="assign" data-assigned="'.$assigned.'"><i class="fa fa-tag"></i> Assign</a></li>';
			}

			$inners .= $inner_header.'
					<tr class="">
						<td class="col-sm-3">'.$entry['serial_no'].'</td>
						<td class="col-sm-1">'.$actual_cost.'</td>
						<td class="col-sm-2">'.$assignments.'</td>
						<td class="col-sm-4">'.$entry['notes'].'</td>
						<td class="col-sm-1 upper-case'.$entry_cls.'" style="font-weight:bold">'.$status.'</td>
						<td class="col-sm-1 text-right">
							<input type="checkbox" name="inventoryids[]" value="'.$entry['id'].'" class="item-check" checked>
							<div class="dropdown" data-inventoryids="'.$entry['id'].'">
								<a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-chevron-down"></i></a>
								<ul class="dropdown-menu pull-right text-left" data-inventoryids="'.$entry['id'].'">
									<li><a href="javascript:void(0);" data-id="'.$entry['id'].'" class="btn-history"><i class="fa fa-history"></i> History</i></a></li>
									'.$repair_ln.'
									'.$scrap_ln.'
									'.$assign_ln.'
									'.$edit_ln.'
								</ul>
							</div>
						</td>
					</tr>
			';

			$inner_header = '';
		}

		// for internal tools, qty is calculated by what's assigned out, not by what is sold
		if ($internal) {
			$goodcount += ($r['qty']-$assigned_qty);
			$outcount += $assigned_qty;

			if ($assigned_qty<>$r['qty']) {
				$qty = $r['qty'];
			} else {
				$qty = ($r['qty']-$assigned_qty).' <span class="info">('.$r['qty'].')</span>';
			}
		}

		$fsum_price = '';
		if ($pricing_header1) {
			$fsum_price = format_price($sum_actual,true,' ');
		}

		$inv_rows .= '
		<tr class="valign-top '.$cls.'" data-partid="'.$r['partid'].'" data-role="summary" data-row="'.$j.'">
			<td class="part-name">'.($inventory_descr ?  display_part($r['partid'], true) : getPart($r['partid'])).'</td>
			<td>'.getLocation($r['locationid']).'</td>
			<td>
		';
		
		if($TYPE == 'audit') {

			$inv_rows .= '
				<div class="col-sm-6 remove-pad">
					<div class="qty results-toggler">'.$qty.'</div>
				</div>
				<div class="col-sm-6 remove-pad">
					<input name="qoh['.$r['partid'].']" value="'.($audit_info[$r['partid']]?:($auditid?'0':'')).'" class="inputs_qoh form-control input-xs" style="max-width: 50px;" />
				</div>
			';
		} else {
			$inv_rows .= '
					<div class="qty results-toggler">'.$qty.'</div>
			';
		}
		$inv_rows .= '
			</td>
			<td>'.$fsum_price.'</td>
		';

		if($TYPE != 'audit') {
			$inv_rows .= '
				<td>'.getCondition($r['conditionid']).'</td>
				<td>'.$prefix.$order_number.$order_ln.'</td>
				<td>'.$company.$company_ln.'</td>
				<td>'.format_date($r['date_created'],'n/j/y').'</td>
			';
		}

		$inv_rows .= '
			<td class="text-center">
				<input type="checkbox" name="partid[]" value="'.$r['partid'].'" class="item-check checkInner" checked>
				<a href="javascript:void(0);" class="results-toggler"><i class="fa fa-list-ol"></i><sup><i class="fa fa-sort-desc"></i></sup></a>
				<div class="dropdown" data-inventoryids="'.$inventoryids.'">
					<a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-chevron-down"></i></a>
					<ul class="dropdown-menu pull-right text-left" role="menu" data-inventoryids="'.$inventoryids.'">
						<li><a href="javascript:void(0);" class="scrap-group"><i class="fa fa-recycle"></i> Scrap group</i></a></li>
						<li><a href="javascript:void(0);" class="edit-inventory"><i class="fa fa-pencil"></i> Edit group</a></li>
					</ul>
				</div>
			</td>
		</tr>
		<tr class="inner-result" data-partid="'.$r['partid'].'" data-role="inner" data-row="'.$j.'"'.$inner_display.'>
			<td colspan="8" class="text-center">
				<table class="table table-condensed table-results text-left">
					'.$inners.'
				</table>
			</td>
		</tr>
		';
		$j++;
	}

	foreach ($partids as $partid => $P) {
		$parts = explode(' ',$P['part']);
		$part_str = $parts[0];
		if ($P['heci']) { $part_str .= ' '.$P['heci']; }

		$qty = 0;
		if (isset($qtys[$partid]) AND $qtys[$partid]>0) { $qty = $qtys[$partid]; }
		$part_options .= '<option value="'.$partid.'" data-descr="'.$part_str.'" data-info="'.current(hecidb($partid,"id"))['description'].'">Qty '.$qty.'- '.$P['part'].' '.$P['heci'].'</option>'.chr(10);
	}

	$n = count($partids);
	$ext = 's';
	if ($n==1) { $ext = ''; }

	if (! isset($TITLE)) { $TITLE = 'Inventory'; }
?>
<!DOCTYPE html>
<html>
<head>
	<title><?= $TITLE; ?><?php if ($search) { echo ' "'.strtoupper($search).'"'; } ?></title>
	<?php
		include_once 'inc/scripts.php';
	?>

	<style type="text/css">
		.table-results {
			width:95%;
			margin-left:auto;
			margin-right:auto;
		}
		.qty {
			border:1px inset #eee !important;
			background-color:#fafafa !important;
			border-radius:3px;
			width:40px;
			min-width:30px;
			max-width:80px;
			text-align:center;
			font-weight:bold;
		}
		.results-toggler {
			cursor:pointer;
		}
		a.results-toggler {
			margin-right:12px;
		}

		.print .table-header, .print .rev-select, .print .table-inventory tbody tr.valign-top td:last-child, .print .table-inventory thead th:last-child {
			display: none !important;
		}

		.print #pad-wrapper {
			margin-top: 0 !important;
		}

		.print a, .print input, .print .btn-group, .print .dropdown {
			display: none;
		}

		.input-group.datepicker-date {
			width:90px;
			min-width:90px;
			max-width:90px;
		}
		.input-group.datepicker-date .input-sm {
			padding-left:3px;
			padding-right:3px;
			font-size:80%;
		}
		.input-group .input-group-addon {
			padding: 2px 4px;
		}
		.col-location .select2-container {
			width:120px !important;
		}
		.company-selector {
			width:190px !important;
		}
	</style>
</head>
<body class="<?=$view;?>">

	<?php if($view != 'print') { include_once 'inc/navbar.php'; } ?>

	<div class="table-header hidden-xs hidden-sm" id="filter_bar" style="width: 100%; min-height: 48px;">
		<form class="form-inline" method="get" action="<?=$self_url;?>" enctype="multipart/form-data" id="filters-form" >
		<input type="hidden" name="inventoryid" value="">
		<input type="hidden" name="inventory-partid" value="">
		<input type="hidden" name="inventory-status" value="">

		<div class="row" style="padding:8px">
			<?=buildFilter($TYPE);?>
		</div>

		</form>
	</div>


<div id="pad-wrapper">

	<?php if($view == 'print') { ?>
		<button data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Exit Print View" class="btn btn-default btn-sm pull-right exit_print">
		    <i class="fa fa-print" aria-hidden="true"></i>
		</button>
	<?php } ?>

<form class="form-inline" id="inventory-form" method="POST" action="<?=$save_form;?>" enctype="multipart/form-data" >
	<input type="hidden" name="locationid" value="<?=$locationid;?>">

	<div class="row hidden-xs hidden-sm">
		<div class="col-sm-3">
<?php if ($n>0) { ?>
			<select name="revs[]" class="form-control rev-select" data-placeholder="" data-allow-clear="false" multiple="multiple">
				<option value="">- <?php echo $n; ?> Result<?php echo $ext; ?> -</option>
				<?php echo $part_options; ?>
			</select>
<?php } ?>
		</div>
		<div class="col-sm-6">
			<h3 class="text-center" id="page-title">
				<?php if ($n==1) { echo $part_str; } ?>
			</h3>

			<span id="page-info" class="descr-label part_description text-center" style = "color:#aaa; display: block;"><?php if ($n==1) { echo display_part($partid, true, true, false); } ?></span>

			<span id="original-title" class="hidden"></span>
		</div>
		<div class="col-sm-3">
		</div>
	</div>
	<br/>

	<div class="row">
		<div class="table-wrapper">

	<table class="table table-striped table-condensed table-inventory">
		<thead><tr data-row="">
			<th class="col-sm-2 part-name">
				Part
			</th>
			<th class="col-sm-2">
				Location
			</th>
			<th class="col-sm-1">
				<?php if($TYPE == 'audit') {
					echo '<div class="col-sm-6 remove-pad">
								Qty
							</div>
							<div class="col-sm-6 remove-pad">
								QOH
						</div>';
				 } else {
					echo 'Qty';
				 } ?>
			</th>
			<th class="col-sm-1">
				<?php echo $pricing_header1; ?>
			</th>
			<?php if($TYPE != 'audit') { ?>
				<th class="col-sm-1">
					Condition
				</th>
				<th class="col-sm-1">
					Source
				</th>
				<th class="col-sm-2">
					Company
				</th>
				<th class="col-sm-1">
					Date
				</th>
			<?php } ?>
			<th class="col-sm-1">
				<input type="checkbox" value="1" class="checkAll" checked>
				<a href="javascript:void(0);" id="results-toggle" class="results-toggler"><i class="fa fa-list-ol"></i><sup><i class="fa fa-sort-desc"></i></sup></a>
<!--
				<a href="javascript:void(0);"><i class="fa fa-chevron-down"></i></a>
-->
			</th>
		</tr></thead>
		<?php echo $inv_rows; ?>
	</table>

		</div>
	</div>

</form>
</div><!-- pad-wrapper -->


<?php include_once $_SERVER["ROOT_DIR"].'/modal/assignments.php'; ?>
<?php include_once $_SERVER["ROOT_DIR"].'/modal/history.php'; ?>
<?php include_once $_SERVER["ROOT_DIR"].'/modal/inventory.php'; ?>
<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

    <script type="text/javascript">
        $(document).ready(function() {
			$('#loader').hide();

			$(document).on("click", ".exit_print", function(e) {
				location.href=location.href.replace(/&?view=([^&]$|[^&]*)/i, "");
			});

			$(".results-toggler").click(function() {
				toggleResults($(this),$(this).closest("tr").data("row"));
			});
			$("#location-filter").change(function() {
				$('#loader-message').html('Please wait while Inventory is loaded...');
				$('#loader').show();

				$("#audit-filter").val("").trigger("change");

				$(this).closest("form").submit();
			});
			$("#audit-filter").change(function() {
				$('#loader-message').html('Please wait while Inventory is loaded...');
				$('#loader').show();

				$(this).closest("form").submit();
			});
			$(".rev-select").click(function() {
				var partid = $(this).find("option:selected").val();

				var title = $("#original-title").text(); //default
				var info = ''; //default

				if (partid>0) {
					title = $(this).find("option:selected").data('descr');
					info = $(this).find("option:selected").data('info');

					$(".part-name").css('display','none');
				} else {
					$(".part-name").css('display','table-cell');
				}

				$(".table-inventory").find("tr").each(function() {
					row_id = $(this).data('partid');
					if (! row_id) { return; }

					if (partid=='' || partid==0 || row_id==partid) {
						if ($(this).data('role')!='inner' || ($(this).data('role')=='inner' && ! $("#inventory-detail").hasClass('btn-default'))) {
							$(this).show();
						}
					} else {
						$(this).hide();
					}
				});

				$("#page-title").text(title);
				$("#page-info").text(info);
			});
			$(".edit-inventory").click(function() {
				var inventoryids = $(this).closest("div").data('inventoryids');
				if (! inventoryids) { return; }

				$.ajax({
					url: 'json/inventory.php',
					type: 'get',
					data: {'inventoryids':inventoryids},
					success: function(json, status) {
						if (json.message && json.message!='') {
							// alert the user when there are errors
							alert(json.message);
							return;
						}

						var M = $("#modal-inventory");

						$("#modalInventoryTitle").html(json.name);

						$("#inventory-inventoryid").val(json.id);

						if (json.serial_no) {
							$("#inventory-serial").prop('disabled',false);
							$("#inventory-serial").val(json.serial_no);
						} else {
							$("#inventory-serial").prop('disabled',true);
						}

						$("#inventory-partid").data('partid',json.partid);
						$("#inventory-partid").populateSelected(json.partid,json.name);

						$("#inventory-locationid").populateSelected(json.locationid,json.location);

						$("#inventory-conditionid").populateSelected(json.conditionid,json.condition);

						if (json.notes) {
							$("#inventory-notes").prop('disabled',false);
							$("#inventory-notes").val(json.notes);
						} else {
							$("#inventory-notes").prop('disabled',true);
						}

						if ($("#inventory-status").hasClass('invstatus-selector')) {
							$("#inventory-status").populateSelected(json.status,json.status);
						} else {
							$("#inventory-status").html(json.status);
						}

						M.modal("show");
					},
					error: function(xhr, desc, err) {
						console.log("Details: " + desc + "\nError:" + err);
					}
				}); // end ajax call
			});

			$(".repair").click(function() {
				var inventoryid = $(this).closest("ul").data('inventoryids');

				modalAlertShow('<i class="fa fa-wrench"></i> Oh GREAT! Real bullets! You\'re in a LOT of trouble, mister!','By sending this unit to Repair, it will be removed from sellable inventory. Are you ready to go?',true,'repair',inventoryid);
			});
			$(".scrap").click(function() {
				var inventoryid = $(this).closest("ul").data('inventoryids');

				modalAlertShow('<i class="fa fa-recycle"></i> All We Have is Tequila','You are scrapping this item, El Guapo! Are you sure you want to do this?',true,'scrap',inventoryid);
			});
			$(".scrap-group").click(function() {
				var inventoryid = $(this).closest("ul").data('inventoryids');

				modalAlertShow('<i class="fa fa-recycle"></i> Jefe! What is a "plethora"?','You are scrapping a PLETHORA of items, El Guapo! Are you sure you want to do this?',true,'scrap',inventoryid);
			});

			$("#inventory-save, .assignments-save").click(function() {
				$('#loader-message').html('Please wait while updates are saved...');
				$('#loader').show();

				// check for filters form and add elements
				var f = $(this).closest("form");

				var ff = $("#filters-form");
				ff.find("input").each(function() {
					if ($(this).prop('type')=='hidden' || f.find("input[name='"+$(this).prop('name')+"']").length>0) { return; }

					$('<input>').prop({
						type: 'hidden',
						name: $(this).prop('name'),
						value: $(this).val(),
					}).appendTo(f);
				});
				ff.find("select").each(function() {
					$('<input>').prop({
						type: 'hidden',
						name: $(this).prop('name'),
						value: $(this).val(),
					}).appendTo(f);
				});

				let inventoryid = '';
				let locationid = '';
				let conditionid = '';

				inventoryid = $(this).find('#inventory-inventoryid').val();
				locationid = $(this).find('#inventory-locationid').val();
				conditionid = $(this).find('#inventory-conditionid').val();

				// Just in case make it so that all the required values are set before trying to check a location
				if(inventoryid && locationid && conditionid) {
					locationCheck(f, locationid, conditionid);
				} else {
					f.submit();
				}
			});

			$(".assign").on('click',function() {
				var inventoryid = $(this).closest("ul").data('inventoryids');
				if (! inventoryid) { return; }
				var assigned = $(this).data('assigned');

				$.ajax({
					url: 'json/assignments.php',
					type: 'get',
					data: {'inventoryid':inventoryid},
					success: function(json, status) {
						if (json.message && json.message!='') {
							// alert the user when there are errors
							alert(json.message);
							return;
						}

						var M = $("#modal-assignments");

						$("#assignments-inventoryid").val(inventoryid);
						$("#assignments-history").html(json.assignments);
						$("#assignments-status").html(json.status);

						if (assigned=='') { $("#btn-unassign").hide(); }
						else { $("#btn-unassign").show(); }

						M.modal("show");
					},
					error: function(xhr, desc, err) {
						console.log("Details: " + desc + "\nError:" + err);
					}
				}); // end ajax call
			});

			$(".owner_filter").on('click',function() {
				var f = $("#filters-form");
				var ownerid = $(this).data('ownerid');
				var owner = $(this).text();
				f.find("select[name='ownerid']").populateSelected(ownerid,owner);

				f.submit();
			});
		});

		function repair(inventoryid) {
			update_status(inventoryid,'in repair');
		}

		function scrap(inventoryid) {
			update_status(inventoryid,'scrapped');
		}

		function update_status(inventoryid,status) {
			$('#loader-message').html('Please wait while updates are saved...');
			$('#loader').show();

			var f = $("#filters-form");
			f.prop('action','save-inventory.php');
			f.find("input[name='inventoryid']").val(inventoryid);
			// do this for inputs or selects, to cover either scenario
			f.find("input[name='inventory-status']").val(status);
			f.find("select[name='inventory-status']").populateSelected(status,status);

			f.submit();
		}

		function locationCheck(form, locationid, conditionid) {
			// In here we need to check the condition of the item and determine where it is being moved.
			// Extra bad stock for locations now need to be determined and notify the user that they are trying to move a good condition item into the non- sellable location

			console.log(window.location.origin+"/json/locationCondition.php?conditionid="+conditionid+"&locationid="+locationid);
			$.ajax({
				url: 'json/locationCondition.php',
				type: 'get',
				data: {
					'conditionid':conditionid,
					'locationid':locationid,
				},
				success: function(json, status) {
					if (json.message && json.message!='') {
						// alert the user when there are errors
						console.log(json.message);
						return;
					}
					
					if(json.conflict) {
						var msg = "You are attempting to place a good stock item into a passive inventory location.";
							
						modalAlertShow("Warning",msg,true,'confirmLocation',form);
					} else {
						form.submit();
					}
				},
				error: function(xhr, desc, err) {
					console.log("Details: " + desc + "\nError:" + err);
				}
			}); // end ajax call
		}

		function confirmLocation(e) {
			$(e).submit();
		}

		function toggleResults(e,j) {
			$('#loader-message').html('Please wait...');
			$('#loader').show();

//			var toggler = $("#results-toggle").find("sup i.fa");
			var toggler = e.closest("tr").find("sup i.fa");
			var showClass = '';
			var hideClass = '';
			if (toggler.hasClass("fa-sort-desc")) {
				var method = 'show';
				showClass = 'fa-sort-asc';
				hideClass = 'fa-sort-desc';
			} else {
				var method = 'hide';
				showClass = 'fa-sort-desc';
				hideClass = 'fa-sort-asc';
			}

			$(".table-inventory").find(".inner-result").each(function() {
				if (j!=='' && $(this).data('row')!==j) { return; }

				if (method=='show') {
					$(this).fadeIn('fast');
				} else {
					$(this).fadeOut('fast');
				}
			});

			$(".results-toggler").each(function() {
				if (j!=='' && $(this).closest("tr").data("row")!==j) { return; }

				$(this).find("sup i.fa").addClass(showClass).removeClass(hideClass);
			});

			$('#loader').hide();
		}

		$('#audit_save').click(function(e) {
			e.preventDefault();

			$("#inventory-form").submit();
		});

		$(".inputs_qoh").on('keydown', function(e) {
			if (e.which === 9) {
				e.preventDefault(); 

				$(this).closest('.valign-top').next('tr').next('tr').find('.inputs_qoh').focus();
			}
		});
	</script>

</body>
</html>
