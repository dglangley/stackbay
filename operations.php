<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	
	require_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/dictionary.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getContact.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/getPipeIds.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/dropPop.php';
	include_once $rootdir.'/inc/locations.php';
	include_once $rootdir.'/inc/jsonDie.php';
	include_once $rootdir.'/inc/filter.php';
	include_once $rootdir.'/inc/display_part.php';
	include_once $rootdir.'/inc/order_parameters.php';
//==============================================================================
//================== Function Delcaration (Declaration?) =======================
//==============================================================================
	
	//Output Module acts as the general output for each of the dashboard sections.
	//	INPUTS: Order(p,s);  Status(Active,Complete)

	$po_updated = $_REQUEST['po'];
	$so_updated = $_REQUEST['so'];
	
	$filter = $_REQUEST['filter'];
	if (! isset($table_filter)) { $table_filter = ''; }
	if (isset($_REQUEST['table_filter'])) { $table_filter = $_REQUEST['table_filter']; }
	
	// if (isset($_REQUEST['companyid'])) { $companyid = $_REQUEST['companyid']; }
	// if (isset($_REQUEST['START_DATE'])) { $start = $_REQUEST['START_DATE']; }
	// if (isset($_REQUEST['END_DATE'])) { $endif = $_REQUEST['END_DATE']; }
	
	//Search first by the global seach if it is set or by the parameter after if global is not set
	$search = ($_REQUEST['s'] ? $_REQUEST['s'] : $_REQUEST['search']);
	if(!$search && grab("form_search")){
		$search = grab("form_search");
	}
	if(!$filter && !$_REQUEST['s']) { 
		$filter = 'active';
	} else if(!$filter && $_REQUEST['s']) {
		$filter = 'all';
	}

	$levenshtein = false;
	$nothingFound = true;
	$found = false;
	$serialDetection = array("po" => 'false', "so" => 'false', "rma" => 'false', "ro" => 'false');
	
	function grabDashFilters(){
		global $GLOBALS;
		//Returns an array of the filters from the dash
		$search = ($_REQUEST['s'] ? $_REQUEST['s'] : $_REQUEST['search']);
		if(!$search && grab("form_search")){
			$search = grab("form_search");
		}
		$filter = $_REQUEST['filter'];
		if(!$filter && grab("form_filter")){
			$filter = grab("form_filter");
		}
		if(!$filter && !$search) { 
			$filter = 'active';
		} else if(!$filter && $search) {
			$filter = 'all';
		}
		$table_filter = $_REQUEST['table_filter'];
		if(!$table_filter && grab("form_table_filter")){
			$table_filter = grab("form_table_filter");
		}
		$f = array(
			"start" => grab("START_DATE"),
			"end" => format_date(grab("END_DATE",$GLOBALS['now']),"Y-m-d"),
			"coid" => grab("coid"),
			"table_filter" => $table_filter,
			"filter" => $filter,
			"search" => $search
			);
		return ($f);
	}
	
	function searchQuery($search, $type) {
		global $found, $levenshtein, $nothingFound;
		$trigger;
		$triggerArray = array();
		$o = o_params($type);
		$f = grabDashFilters();

		
		$initial = array();
		$arrayID = array();
		$query;
		$parts = hecidb($search);
		
		//If Heci DB detects anything from the search then create a trigger to also search through the parts ID
		if(!empty($parts)) {
			foreach($parts as $id) {
				$arrayID[] = $id['id'];
			}
			$trigger = 'parts';
		}
		$query = "
		SELECT * FROM ".$o['tables']." 
		AND i.".$o['id'].' = '.prep(strtoupper($search))." 
		AND status <> 'Void'
		".dFilter("created",$f['start'],$f['end'])."
		".sFilter("o.`companyid`",$f['coid'])."
		;";
		// echo($query);
		
		$result = qdb($query) OR die(qe());
		
		while ($row = $result->fetch_assoc()) {
			$initial[] = $row;
		}
		
		if($trigger == 'parts') {
			$query = "
			SELECT * FROM ".$o['tables']. " 
			AND i.partid IN (" . implode(',', array_map('intval', $arrayID)) . ") 
			AND status <> 'Void'
			".dFilter("created",$f['start'],$f['end'])."
			".sFilter("o.`companyid`",$f['coid'])."
			;";

			if($query) {
				$result = qdb($query) OR die(qe());
			
				while ($row = $result->fetch_assoc()) {
					$initial[] = $row;
				}
			}
		}
		$query = "SELECT * FROM inventory inv, inventory_history h, ".$o['item']." i, `".$o['order']."` o WHERE inv.serial_no = ".prep(strtoupper($search))." ";
		$query .= "AND h.field_changed = '".$o['inv_item_id']."' AND o.".$o['id']." = i.".$o['id']." ";
		$query .= "AND h.value = i.id AND inv.id = h.invid AND o.status <> 'Void'
		".dFilter("created",$f['start'],$f['end'])."
		".sFilter("o.`companyid`",$f['coid'])."
		; ";

		$result = qdb($query) OR die(qe());
		while ($row = $result->fetch_assoc()) {
			//Checks if the array row already exists within the array, if not add it to the list
			if (!in_array($row, $initial)) {
			    $initial[] = $row;
			}
		}
		
		//If the initial search is empty populate the data with close alternates
		if(empty($initial) && $type != 'rma' && $type != 'ro' && $type != 'bo') {
			$initial = soundsLike($search, $type);
		} else if (!empty($initial)) {
			//Items were found so remove any warning messages ever
			$found = true;
			$nothingFound = false;
			$levenshtein = false;
		} else {
			//$levenshtein = false;
		}

		return $initial;
	}
	
	function soundsLike($search, $type) {
		global $levenshtein, $nothingFound, $found;
		$arr = array();
		$initial = array();
		$query;
		
		$query = 'SELECT * FROM inventory WHERE soundex(serial_no) LIKE soundex("'.res(strtoupper($search)).'");';

		$result = qdb($query) OR die(qe());
		
		if (mysqli_num_rows($result)>0) {
			while ($row = $result->fetch_assoc()) {
				$arr[] = $row['serial_no'];
			}
		} 
		else {
			$query = 'SELECT * 
						FROM parts p, inventory i
						WHERE SOUNDEX( part ) LIKE SOUNDEX(  "'.res(strtoupper($search)).'" ) AND p.id = i.partid';
						
			$result = qdb($query) OR die(qe());
			
			if (mysqli_num_rows($result)>0) {
				while ($row = $result->fetch_assoc()) {
					$arr[] = $row['serial_no'];
				}
			} 
		}
		
		if(!empty($arr)) {
			//Something was found similar to the search
			if(!$levenshtein)
				$levenshtein = true;
			
			//This prevents duplicate entries of similar results
			$arr = array_values(array_unique($arr));
			for($i=0; $i<count($arr); $i++) {
				$holder = $arr[$i];
				//Run the levenshtein step search
		   		$temp_arr[$i] = levenshtein($search, $holder);
			}
			
			asort($temp_arr);
			
			foreach($temp_arr as $k => $v) {
			    $sorted_arr[] = $arr[$k];
			}
			
			//$closest_arr = join(', ', array_slice($sorted_arr, 0, 3));
			
			$closest_arr = "'" . implode("','", array_slice($sorted_arr, 0, 3)) . "'";
			
			switch ($type) {
			    case 's':
			    	$query = "SELECT DISTINCT * FROM inventory inv, sales_items i, sales_orders o WHERE serial_no IN (" . $closest_arr . ") ";
					$query .= "AND inv.sales_item_id = i.id AND o.so_number = i.so_number;";
			        break;
			    case 'p':
			    	$query = "SELECT DISTINCT * FROM inventory inv, purchase_items i, purchase_orders o WHERE serial_no IN (" . $closest_arr . ") ";
					$query .= "AND inv.purchase_item_id = i.id AND o.po_number = i.po_number;";
			        break;
			    default:
					//Should rarely ever happen
					break;
			}
			//echo $query;
			//print_r($query); die();
			// $query = "SELECT DISTINCT * FROM inventory WHERE serial_no IN (" . implode(',', array_map('intval', $closest_arr)) . ");";
			$result = qdb($query) OR die(qe());
			
			while ($row = $result->fetch_assoc()) {
				//Checks if the array row already exists within the array, if not add it to the list
				if (!in_array($row, $initial)) {
				    $initial[] = $row;
				}
			}
			
		}
		
		return $initial;
	}

	function getRepairCode($repair_code){
		$repair_text = "";
				
		$select = "SELECT description FROM repair_codes WHERE id = ".prep($repair_code).";";
		$results = qdb($select);

		if (mysqli_num_rows($results)>0) {
			$results = mysqli_fetch_assoc($results);
			$repair_text = $results['description'];
		}

		return $repair_text;
	}
	
	function output_module($order, $search){
		
		 $order_out;
		
		if($order =="p") {
			$type = 'PO';
			$order_out = 'Purchase';
		} else if($order =="s") {
			$type = 'SO';
			$order_out = 'Sales';
		} else if($order =="rma") {
			$order_out = 'RMA';
			$type = 'RMA';
		} else if($order =="ro") {
			$type = 'RO';
			$order_out = 'Repair';
		} else {
			$type = 'BO';
			$order_out = 'Builds';
		}
		echo '
		<div class="col-lg-6 pad-wrapper data-load" style="margin: 15px 0 20px 0; display: none;">
			<div class="shipping-dash" id="'.$order_out.'_panel">
				<div class="shipping_section_head" data-title="'.$order_out.' Orders">
					'.$status_out.$order_out. (($order =="bo") ? '':' Orders').
				'</div>
				<div class="table-responsive">
		            <table class="table heighthover heightstriped table-condensed '.$order.'_table">
		';
		echo output_header($order,$type);
		echo '
						<tbody>
		';
		echo output_rows($order, $search);
		echo '
						</tbody>
		            </table>
		    	</div>
		    	<div class="col-sm-12 text-center shipping_section_foot shipping_section_foot_lock more" style="padding:0px !important; vertical-align:bottom !important">
	            	<a class="show_more_link" href="#">Show more</a>
	            </div>
            </div>
        </div>
		';
	}
	
	function output_header($order,$type='Order'){
			$o = o_params($order);
			echo'<thead>';
			echo'<tr>';
			$order_label = $type.'#';
			$date_w = '1';
			$item_w = '3';

			if ($type=='RO') {
				$order_label = 'Repair#';
			} else if ($type=='BO') {
				$order_label = 'Build#';
				$date_w = '2';
				$item_w = '5';
			}

			echo'	<th class="col-sm-'.$date_w.'">';
			echo'		Date';
			echo'	</th>';
			if ($type<>'BO') {
				echo'	<th class="col-sm-3 company_col">';
				echo'	<span class="line"></span>';
				echo'		Company';
				echo'	</th>';
			}
            echo'	<th class="col-sm-2">';
            echo'		<span class="line"></span>';
            echo'		'.$order_label;
            echo'	</th>';
        	echo'   <th class="col-sm-'.$item_w.' item_col">';
            echo'   	<span class="line"></span>';
            echo'       Item';
            echo'	</th>';
			if($type=='BO' OR $type=='PO' OR $type=='SO') {
	            echo'   <th class="col-sm-1 qty_col '.($order == 's' || $order == 'p' ? $order.'o': $order).'-column">';
	            echo'   	<span class="line"></span>';
	            echo'   	Qty';
	            echo'  	</th>';
	        } else {
	        	echo'   <th class="col-sm-2 item_col">';
	            echo'   	<span class="line"></span>';
	            echo'       Serial';
	            echo'	</th>';
				if ($type=='RO') {
		            echo'   <th class="col-sm-1">';
		            echo'   	<span class="line"></span>';
		            echo'   	Due';
		            echo'  	</th>';
				}
	        }
            echo'   <th class="col-sm-1 status-column" style="display: none;">';
            echo'   	<span class="line"></span>';
            echo'   	Status';
            echo'  	</th>';
			echo'  	<th class="col-sm-1">';
            echo'   	<span class="line"></span>';
            echo'  		Action';
            echo'  	</th>';
	        echo'</tr>';
			echo'</thead>';
	}
	
	//Inputs expected:
	//	- Status: Completed, Active
	//	- Order: s, p, rma, ro
	function output_rows($order = '', $search = ''){
		global $serialDetection, $USER_ROLES;
		$results;
		$status;
		$type = '';
		$o = o_params($order);
		$type = $o['short'];
		$f = grabDashFilters();
		// if ($order == 'p') {
		// 	$type = 'po';
		// } else if ($order == 's') {
		// 	$type = 'so';
		// } 
		// else if ($order == 'rma') {
		// 	$type = 'rma';
		// } else {
		// 	$type = 'ro';
		// }
		//if($order != 'rma' && $order != 'ro') {
			if($search =='') {
				$query = "SELECT * ";
				if ($order=='bo') { $query .= ", b.id bid "; }
				$query .= "FROM ".$o['mq_base']." 
				AND o.status <> 'Void' 
				and o.status <> 'Processed' 
				".sFilter("o.companyid",$f['coid'])."
				".dFilter("created",$f['start'],$f['end'])."
				ORDER BY ".$o['order_by']." 
				DESC LIMIT 0, 200;";
				$results = qdb($query) or die(qe()." | $query");
			} else {
				$results = searchQuery($search, $order);
				//print_r($results); //die;
			}
			
			//display only the first N rows, but output all of them
			$count = 0;
			$active = 1;
			$complete = 1;
			//Loop through the results.
			if(!empty($results)) {
				//print_r($results);
				foreach ($results as $r){
					if ($order=='bo') { unset($r['serial_no']); }
					//set if a serial is present or not
					$serialDetection[$type] = ($r['serial_no'] != '' ? 'true' : 'false');
					
					//echo $type . " " .($r['serial_no'] != '' ? 'true' : 'false');
					
					$count++;
					$order_num = $r[$o['id']];
					// if ($order == 's'){ $order_num = $r['so_number']; }
					// else if ($order == 'p'){ $order_num = $r['po_number']; }
					// else if ($order == 'rma'){ $order_num = $r['rma_number']; }
					// else if ($order == 'ro'){ $order_num = $r['ro_number']; }
					// else if ($order == 'bo'){ 
					// 	$order_num = $r['bid']; 
					// 	//$build = $r['bid'];
					// }
					if ($order=='bo') { $order_num = $r['bid']; }

					//$date = date("m/d/Y", strtotime($r['ship_date'] ? $r['ship_date'] : $r['created']));
					$date = date("n/j/y", strtotime($r['created']));
					$due_date = strtotime($r['receive_date']);
					$company = getCompany($r['companyid']);
					$company_ln = '';
					if ($company) { $company_ln = '<div class="company-overflow">'.$company.'</div> <a href="/profile.php?companyid='. $r['companyid'] .'"><i class="fa fa-arrow-right"></i></a>'; }
					$item = display_part($r['partid'], true);
					$qty = $r['qty'];
					
					if ($order != 's' && $order != 'rma' && $order != 'ro'){
						$status = ($r['qty_received'] >= $r['qty'] ? 'complete_item' : 'active_item');
					} else if ($order == 's') {
						$status = ($r['qty_shipped'] >= $r['qty'] ? 'complete_item' : 'active_item');
					} else if($order == 'rma') {
						$status = ($r['returns_item_id'] ? 'complete_item' : 'active_item');
					} else if($order == 'ro' || $order == 'bo') {
						$status = ($r['repair_code_id'] ? 'complete_item' : 'active_item');
						$status_name = ($r['repair_code_id'] ? getRepairCode($r['repair_code_id']) : 'Active');
						//print_r($r['status'] );
					}
				
					if($count<=10){
						echo'	<tr data-order="'.$order_num.'" data-date="'.$due_date.'" class="filter_item '.$status.'">';
					} else{
						echo'	<tr data-order="'.$order_num.'" data-date="'.$due_date.'" class="filter_item show_more '.$status.'" style="display:none;">';
					}

					echo'        <td>'.$date.'</td>';

					if ($order<>'bo') {
						echo'        <td>'.$company_ln.'</td>';
					} 
					if($o['build']){
						echo'        <td>'.$order_num.' <a href="/repair.php?on='.$order_num.'&build=true"><i class="fa fa-arrow-right" aria-hidden="true"></i></a></td>';
					} else if($o['ro']) {
						echo'        <td>'.$order_num.' <a href="/order_form.php?ps=repair&on='.$order_num.'"><i class="fa fa-arrow-right" aria-hidden="true"></i></a></td>';
					}

					//Either go to inventory add or PO or shipping for SO
					if($o['po'] || $o['so']) {
						$base = $o['url'].'.php';
						if(in_array("3", $USER_ROLES) || in_array("1", $USER_ROLES)) {
							$base = 'order_form.php';
						}
						echo'    <td>'.$order_num.'&nbsp;<a href="/'.$base.'?on='.$order_num.'&ps='.$order.'"><i class="fa fa-arrow-right"></i></a></td>';
					} else if($order == 'rma') {
						echo'        <td>'.$order_num.' <a href="/rma.php?rma='.$order_num.'"><i class="fa fa-arrow-right"></i></a></td>';
					}

					echo'        <td><div class="desc">'.$item.'</div></td>';
					if($order != 'ro' && $order != 'bo') {
						echo'    	<td>'.($r['serial_no'] ? $r['serial_no'] : $qty).'</td>';
					} else {
						$serial;
						if($r['id']) {
							$query = "SELECT serial_no FROM inventory WHERE repair_item_id = ".prep($r['id']).";";
							$result = qdb($query) or die(qe() . ' ' . $query);

							if (mysqli_num_rows($result)>0) {
								$rq = mysqli_fetch_assoc($result);
								$serial = $rq['serial_no'];
							}
						}
						echo'        <td>'.($serial ? $serial : '').'</td>';
						//echo'        <td>'.$r['public_notes'].'</td>';
					}

					if($order == 'ro') {
						global $now;
						echo'    	<td>'.format_date($r['due_date']).'</td>';
					}

					if($o['ro']) {
						echo'    	<td style="display: none;" class="status-column">'.(($status == 'active_item') ? '<span class="label label-warning active_label status_label" style="display: none;">'.$status_name.'</span> ' : '' ).(($status == 'complete_item') ? '<span class="label label-'.($status_name == "Not Reparable" ? 'danger' : ($status_name == 'NTF' ? 'info' : 'success')).' complete_label status_label" style="display: none;">'.$status_name.'</span> ' : '' ).'</td>';
					} else {
						
						echo'    	<td style="display: none;" class="status-column">'.(($status == 'active_item') ? '<span class="label label-warning active_label status_label" style="display: none;">Active</span> ' : '' ).(($status == 'complete_item') ? '<span class="label label-success complete_label status_label" style="display: none;">Complete</span> ' : '' ).'</td>';
					}
					if($order != 'ro' && $order != 'rma' && $order != 'bo') {
						echo'    	<td class="status text-right">';		
						if($order == 's') {
							echo'			<a href="/rma.php?on='.$order_num.'" class="rma_icon"><i style="margin-right: 5px;" class="fa fa-question-circle-o" aria-hidden="true"></i></a>';
						}

						echo'			<a href="/'.($order == 'p' ? 'inventory_add' : 'shipping').'.php?on='.$order_num.'&ps='.$order.'"><i style="margin-right: 5px;" class="fa fa-truck" aria-hidden="true"></i></a>';
						if(in_array("3", $USER_ROLES) || in_array("1", $USER_ROLES)) {
							echo'		<a href="/order_form.php?on='.$order_num.'&ps='.$order.'"><i style="margin-right: 5px;" class="fa fa-pencil" aria-hidden="true"></i></a>';
						}
						echo'		</td>'; 			
					} else if($order == 'rma'){
						echo'    	<td class="status text-right">';	
						echo'			<a href="/rma_add.php?on='.$order_num.'"><i style="margin-right: 5px;" class="fa fa-truck" aria-hidden="true"></i></a>';
						if(in_array("3", $USER_ROLES) || in_array("1", $USER_ROLES)) {
							echo'		<a href="/rma.php?rma='.$order_num.'"><i style="margin-right: 5px;" class="fa fa-pencil" aria-hidden="true"></i></a>';
						}
						echo'		</td>'; 
					} else {
						echo'    	<td class="status text-right">';		
						if($order != 'bo') {
							echo'			<a href="/repair.php?on='.$order_num.'"><i style="margin-right: 5px;" class="fa fa-user-circle-o" aria-hidden="true"></i></a>';
							echo'			<a href="/repair_add.php?on='.$order_num.($order == 'bo' ? '&build=true' : '').'"><i style="margin-right: 5px;" class="fa fa-truck" aria-hidden="true"></i></a>';
							echo'			<a href="/order_form.php?on='.$order_num.'&ps=ro"><i style="margin-right: 5px;" class="fa fa-pencil" aria-hidden="true"></i></a>';
						} else {
							echo'			<a href="/builds_management.php?on='.$order_num.'"><i style="margin-right: 5px;" class="fa fa-pencil" aria-hidden="true"></i></a>';
						}
						echo'		</td>'; 							
					}

					
					echo'	</tr>';
				}
		}
	}
	
	
	$f = grabDashFilters();
	if(!$table_filter){
		$table_filter = $f['table_filter'];
	}
?>

<!----------------------------------------------------------------------------->
<!------------------------------- HEADER OUTPUT ------------------------------->
<!----------------------------------------------------------------------------->
<!DOCTYPE html>
<html>
<!-- Declaration of the standard head with  home set as title -->
<head>
	<title>Operations Dashboard</title>
	<?php
		//Standard headers included in the function
		include_once $rootdir.'/inc/scripts.php';
	?>
	<link rel="stylesheet" href="../css/operations-overrides.css?id=<?php if (isset($V)) { echo $V; } ?>" type="text/css" />
	<style>
		body {
			overflow-x: hidden;
			margin-top: 10px;
		}
		
		.date-options {
			height: 30px;
			overflow: hidden;
		}
		
		.date-options button {
			display: inline;
		}
		
		.date-options {
		    height: 30px;
		    overflow: hidden;
		    position: absolute;
		    /*width: 250px;*/
		    z-index: 1;
		    background: #ddd;
		}
		
		.nopadding {
		   padding: 0 !important;
		   margin: 0 !important;
		}
		
		.shipping-dash {
			min-height: 510px;
		}
		
		.shipping_section_foot_lock {
			padding-bottom: 15px;
		    position: absolute;
		    bottom: 0px;
		}
		
		.desc {
			max-width:320px;
			overflow:hidden;
			white-space:nowrap;
			text-overflow:ellipsis;
		}
		.descr-label {
			white-space:nowrap;
    		overflow:hidden;
		}

		.rma_icon {
			display: none;
		}

		.complete_item .rma_icon {
			display: inline;
		}
		
		.table tbody > tr > td { vertical-align:top !important; }

		@media screen and (max-width: 991px){
			.date-options {
				position: relative;
			}
		}
		.company-overflow {
			vertical-align:bottom;
			padding-right:3px;
			display:inline-block;
			white-space:nowrap;
			overflow:hidden;
			max-width:130px;
			text-overflow:ellipsis;
		}
		
	</style>
</head>

<body class="sub-nav accounts-body">
<!----------------------------------------------------------------------------->
<!------------------------- Output the navigation bar ------------------------->
<!----------------------------------------------------------------------------->

	<?php include 'inc/navbar.php'; ?>
		<div class="table-header" id = 'filter_bar' style="width: 100%; min-height: 48px;">
			
			<div class="row" style="padding: 8px;" id = "filterBar">
				<div class="col-md-1">
				    <div class="btn-group medium" data-toggle="buttons">
				        <button data-toggle="tooltip" data-placement="right" title="" data-original-title="Active" class="btn btn-sm btn-status left filter_status <?=($filter == 'active' ? 'active btn-warning' : 'btn-default');?>" type="submit" data-filter="active">
				        	<i class="fa fa-sort-numeric-desc"></i>	
				        </button>
				        <button data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Completed" class="btn btn-sm btn-status middle filter_status <?=($filter == 'complete' ? 'active btn-success' : 'btn-default');?>" type="submit" data-filter="complete">
				        	<i class="fa fa-history"></i>	
				        </button>
						<button data-toggle="tooltip" data-placement="bottom" title="" data-original-title="All" class="btn btn-sm btn-status right filter_status <?=(($filter == 'all' || $filter == '') ? 'active btn-info' : 'btn-default');?>" type="submit" data-filter="all">
				        	All
				        </button>
				    </div>
				</div>
				<form id = 'filter_form' action='operations.php' method = 'post'>
				<div class ='hidden'>
					<!--<input type="text" name="form_search" class="form-control input-sm" value="<?=$f['search']?>" style = "min-width:50px;"/>-->
					<!--<input type="text" name="form_filter" class="form-control input-sm" value="<?=$f['filter']; ?>" style = "min-width:50px;"/>-->
					<!--<input type="text" name="form_table_filter" class="form-control input-sm" value="<?=$table_filter?>" style = "min-width:50px;"/>-->
				</div>
				<div class = "col-md-3">
					<div class="row">
						<div class="col-md-6">
							<div class="input-group date datetime-picker-filter">
					            <input type="text" name="START_DATE" class="form-control input-sm" value="<?=format_date($f['start'],'m/d/Y')?>" style = "min-width:50px;"/>
					            <span class="input-group-addon">
					                <span class="fa fa-calendar"></span>
					            </span>
					        </div>
						</div>
						<div class="col-md-6">
							<div class="input-group date datetime-picker-filter">
					            <input type="text" name="END_DATE" class="form-control input-sm" value="<?=format_date($f['end'],'m/d/Y')?>" style = "min-width:50px;"/>
					            <span class="input-group-addon">
					                <span class="fa fa-calendar"></span>
					            </span>
					    	</div>
						</div>
					</div>
				</div>
				<div class="col-md-4 text-center">
	            	<h2 class="minimal" id="filter-title">Operations Dashboard</h2>
				</div>
				
				<!--This Handles the Search Bar-->
	
				<div class="col-md-2 col-sm-2">
	
				</div>
				
	
				<div class="col-md-2 col-sm-2">
					<div class="pull-right input-group form-group" style="margin-bottom: 0px;">
						<select name ='coid' class="company-selector">
							<option value="">- Select a Company -</option>
							<?php 
								if ($f['coid']) {echo '<option value="'.$f['coid'].'" selected>'.(getCompany($f['coid'])).'</option>'.chr(10);} 
								else {echo '<option value="">- Select a Company -</option>'.chr(10);} 
							?>
						</select>
						<span class="input-group-btn">
							<button class="btn btn-primary btn-sm" type = 'submit'>
								<i class="fa fa-filter" aria-hidden="true"></i>
							</button>
						</span>
					</div>
				</div>
			</div>
			</form>
		</div>
	
	<?php //if($levenshtein || $nothingFound): ?>
	<div id="item-warning-timer" class="alert alert-warning fade in text-center" style="display: none; position: fixed; width: 100%; z-index: 9999; top: 94px;">
	    <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
	    <strong><i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning </strong> <span class="warning-message"></span>
	</div>
	<?php //endif; ?>
	
	<?php if($po_updated || $so_updated): ?>
		<div id="item-updated-timer" class="alert alert-success fade in text-center" style="position: fixed; width: 100%; z-index: 9999; top: 48px;">
		    <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
		    <strong>Success!</strong> <?php echo ($po_updated ? 'Purchase' : 'Sales'); ?> Order Updated.
		</div>
	<?php endif; ?>
	
	
	<div class="row table-holder">
		<?php 
			// print_r(grabDashFilters());
			output_module("p",$search);
			output_module("s",$search);
		?>
    </div>
	<div class="row table-holder">
		<?php 
			output_module("rma",$search);
			output_module("ro",$search);
		?>
    </div> 
    <div class="row table-holder">
		<?php 
			output_module("bo",'');
			//output_module("ro",$search);
		?>
    </div> 
    <?php //print_r($serialDetection);?>

<?php include_once 'inc/footer.php'; ?>
<script src="js/operations.js?id=<?php if (isset($V)) { echo $V; } ?>"></script>
<script>
	(function($){
		$('#item-updated-timer').delay(3000).fadeOut('fast');
		function getUrlParameter(sParam) {
		    var sPageURL = decodeURIComponent(window.location.search.substring(1)),
		        sURLVariables = sPageURL.split('&'),
		        sParameterName,
		        i;
		
		    for (i = 0; i < sURLVariables.length; i++) {
		        sParameterName = sURLVariables[i].split('=');
		
		        if (sParameterName[0] === sParam) {
		            return sParameterName[1] === undefined ? true : sParameterName[1];
		        }
		    }
		}
		
		// function grabFilterArray(){
		// 	var f = {
		// 		start : $("#filter_bar").find("input[name='START_DATE']").val(),
		// 		end : $("#filter_bar").find("input[name='END_DATE']").val(),
		// 		coid : $("#filter_bar").find(".company-selector").val(),
		// 		filter : $("#filter_bar").find(".filter_status.active").data("filter"),
		// 		table_filter : "<?=$table_filter;?>",
		// 		search : $("#s").val()
		// 	}
		// 	var form_search = '<?=$_REQUEST['form_search']?>';
		// 	if (!f['search'] && form_search){
		// 		f['search'] = form_search;
		// 		$("#s").val(form_search);
		// 	}
		// 	if(f['search'] || f['coid']){
		// 		f['filter'] = 'all';
		// 	}
		// 	if(!f['start']){
		// 		f['start'] = getUrlParameter("start");
		// 	}
		// 	if(!f['end']){
		// 		f['end'] = getUrlParameter("end");
		// 	}
		// 	if(!f['coid']){
		// 		f['coid'] = getUrlParameter("coid");
		// 	}
		// 	if(!f['table_filter']){
		// 		f['table_filter'] = getUrlParameter("table_filter");
		// 	}
		// 	// if (f['table_filter'] != '') {
		// 	// 	zoomPanel($("#"+f['table_filter']+"_panel").find(".shipping_section_foot a"),'in');
		// 	// }
		// 	console.log(f);
		// 	return f;
		// }
		// function processFilterUrl(){
		// 	var f = grabFilterArray();
		// 	var urlstring = "";
		// 	if(f['start']){
		// 		urlstring += "&start="+f['start'];
		// 	}
		// 	if(f['end']){
		// 		urlstring += "&end="+f['end'];
		// 	}
		// 	if(f['coid']){
		// 		urlstring += "&coid="+f['coid'];
		// 	}
		// 	if(f['filter']){
		// 		urlstring += "&filter="+f['filter'];
		// 	}
		// 	if(f['search']){
		// 		urlstring += "&search="+f['search'];
		// 	}
		// 	if(f['table_filter']){
		// 		urlstring += "&table_filter="+f['table_filter'];
		// 	}
		// 	if(urlstring){
		// 		urlstring = urlstring.slice(1);
		// 		urlstring = "?"+urlstring;
		// 	}
		// 	window.history.replaceState(null, null, "/operations.php"+urlstring);
		// }
		
		
		//Triggering Aaron 2017
		var search = "<?=($_REQUEST['s'] ? $_REQUEST['s'] : $_REQUEST['search']); ?>";
		if(!search){
			search = "<?=$_REQUEST['form_search']?>";
		}
		var filter = "<?=$filter;?>";
		var table_filter = "<?=$table_filter;?>";
		var levenshtein = "<?=$levenshtein;?>";
		var searched = "<?=$nothingFound;?>";
		var serialDetection = <?= json_encode($serialDetection) ?>;
		// grabFilterArray();
		//Load in the objects after the page is loaded for less jumpy frenziness
		$('.data-load').fadeIn();
		
		//Search parameter has been passed in that case show the search results
		

		// alert("Here");
		// 	if(filter != '') {
		// 		window.history.replaceState(null, null, "/operations.php?search=" + search + "&filter=all");
		// 	} else {
		// 		window.history.replaceState(null, null, "/operations.php?search=" + search);
		// 	}
			
		if(search != '') {
			if(levenshtein) {
				$('.warning-message').html("No items found for <b>" + search + "</b>. Listed are similar results.");
				$('#item-warning-timer').show().delay(3000).fadeOut('fast');
				// modalAlertShow("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning", "No items found for <b>" + search + "</b>.<br><br> Listed are similar results.", false);
			} else if(searched) {
				$('.warning-message').html("No items found for <b>" + search + "</b>.");
				$('#item-warning-timer').show().delay(3000).fadeOut('fast');
				// modalAlertShow("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning", "No items found for <b>" + search + "</b>.", false);
			}
		}

		for (var key in serialDetection) {
		    if(serialDetection[key] == 'true') {
//				$('.'+key+'-column').html('<span class="line"></span> Serial');
				//If a serial is detected then change the table headers and sizes or anything else that needs to be altered
				$('.'+key+'-column').closest(".qty_col").addClass('col-sm-2').removeClass('col-sm-1');
//				$('.'+key+'-column').closest(".item_col").addClass('col-sm-4').removeClass('col-sm-5');
		    }
		}

		function sortActive(a, b) {
			var a = $(a).data("date");
			var b = $(b).data("date");

			if(a > b)
				return 1;

			if(a < b)
				return -1;

			return 0;
        }

        function sortAll(a, b) {
			var a = $(a).data("order");
			var b = $(b).data("order");

			if(a < b)
				return 1;

			if(a > b)
				return -1;

			return 0;
        }

        function sortComplete(a, b) {
			var a = $(a).data("order");
			var b = $(b).data("order");

			if(a < b)
				return 1;

			if(a > b)
				return -1;

			// if(a == b) {
			// 	var c = $(a).data("order");
			// 	var d = $(b).data("order");

			// 	if(c < d)
			// 		return 1;

			// 	if(c > d)
			// 		return -1;
			// }

			return 0;
        }

        function sortTheTable(type){
        	if(type == 'active') {
	            var elems = $.makeArray($('table.p_table tbody tr.active_item').remove());
	            elems.sort(sortActive);
	            $('table.p_table').append($(elems));
	        } else if(type == 'all') {
	        	var elems = $.makeArray($('table.p_table tbody tr').remove());
	            elems.sort(sortAll);
	            $('table.p_table').append($(elems));
	        } else if(type == 'complete') {
	        	var elems = $.makeArray($('table.p_table tbody tr.complete_item').remove());
	            elems.sort(sortComplete);
	            $('table.p_table').append($(elems));
	        }
        }
		
		// alert(table_filter);
        //Prefilter if loaded with a parameter in url
		if(filter != '') {
			var type = filter;

			$('.filter_item').hide();
			$('.show_more').hide();

			if (type=='complete' || type=='active') {
				sortTheTable(type);
			} else {
				sortTheTable('all');
				$('.status_label').show();
				$('.status-column').show();
				type = 'filter';
			}
			$('.p_table .'+type+'_item:lt(10)').show();
			$('.s_table .'+type+'_item:lt(10)').show();
			$('.rma_table .'+type+'_item:lt(10)').show();
			$('.ro_table .'+type+'_item:lt(10)').show();
			$('.bo_table .'+type+'_item:lt(10)').show();
		}
		if (table_filter != '') {
			zoomPanel($("#"+table_filter+"_panel").find(".shipping_section_foot a"),'in');
		}
		// processFilterUrl();
		
		$(document).on("click onload", ".filter_status", function(){
			var type = $(this).data('filter');
			$('.filter_item').hide();
			$('.filter_status').removeClass('active');
			$('.filter_status').removeClass('btn-warning');
			$('.filter_status').removeClass('btn-success');
			$('.filter_status').removeClass('btn-info');
			$('.filter_status').addClass('btn-default');

			var btn,type2;
			if (type=='complete') {
				btn = 'success';
				type2 = type;
				$('.active_item').hide();
				$('.status-column').hide();
				$('.status_label').hide();
			} else if (type=='active') {
				btn = 'warning';
				type2 = type;
				$('.complete_item').hide();
				$('.status-column').hide();
				$('.status_label').hide();
			} else {
				type = 'all';
				type2 = 'filter';
				btn = 'info';
				$('.status-column').show();
				$('.status_label').show();
			}

			$('.filter_status[data-filter="'+type+'"]').addClass('btn-'+btn);
			sortTheTable(type);
			if ($('.show_more_link:visible:first').text() == "Show more") {
				$('.p_table .'+type2+'_item:lt(10)').show();
				$('.s_table .'+type2+'_item:lt(10)').show();
				$('.rma_table .'+type2+'_item:lt(10)').show();
				$('.ro_table .'+type2+'_item:lt(10)').show();
				$('.bo_table .'+type2+'_item:lt(10)').show();
			} else {
				$('.'+type2+'_item').show();
			}
			
			// if(search != '') {
			// 	window.history.replaceState(null, null, "/operations.php?search=" + search + "&filter=all");
			// } else {
			// 	window.history.replaceState(null, null, "/operations.php?filter=" + type);
			// }
			$(this).addClass('active');
			// processFilterUrl();
		});
		
	})(jQuery);
</script>

</body>
</html>
