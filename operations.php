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
	
//==============================================================================
//================== Function Delcaration (Declaration?) =======================
//==============================================================================
	
	//Output Module acts as the general output for each of the dashboard sections.
	//	INPUTS: Order(p,s);  Status(Active,Complete)

	$po_updated = $_REQUEST['po'];
	$so_updated = $_REQUEST['so'];
	
	$filter = $_REQUEST['filter'];

	//Search first by the global seach if it is set or by the parameter after if global is not set
	$search = ($_REQUEST['s'] ? $_REQUEST['s'] : $_REQUEST['search']);

	if(!$filter && !$_REQUEST['s']) { 
		$filter = 'active';
	} else if(!$filter && $_REQUEST['s']) {
		$filter = 'all';
	}

	$levenshtein = false;
	$nothingFound = true;
	$found = false;
	$serialDetection = array("po" => 'false', "so" => 'false', "rma" => 'false', "ro" => 'false');
	
	function searchQuery($search, $type) {
		global $found, $levenshtein, $nothingFound;
		$trigger;
		$triggerArray = array();
		
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
		
		switch ($type) {
		    case 's':
        		$query = "SELECT * FROM sales_items i, sales_orders o WHERE i.so_number = '".res(strtoupper($search))."' AND o.so_number = i.so_number;";
		        break;
		    case 'p':
        		$query = "SELECT * FROM purchase_items i, purchase_orders o WHERE i.po_number = '".res(strtoupper($search))."' AND o.po_number = i.po_number;";
		        break;
		    //Holder for future RMA and RO
			case 'rma':
        		$query = "SELECT * FROM return_items i, returns r WHERE i.rma_number = '".res(strtoupper($search))."' AND r.rma_number = i.rma_number;";
		        break;
			case 'ro':
        		$query = "";
		        break;
			default:
				//Should rarely ever happen
				//$query = "SELECT * FROM sales_items i, sales_orders o WHERE i.so_number = '".res(strtoupper($search))."' AND o.so_number = i.so_number;";
				break;
		}
		
		if(empty($query)) {
			return '';
		}
		
		$result = qdb($query) OR die(qe());
		
		while ($row = $result->fetch_assoc()) {
			$initial[] = $row;
		}
		
		if($trigger == 'parts') {
			
			switch ($type) {
			    case 's':
	        		$query = "SELECT * FROM sales_items i, sales_orders o WHERE i.partid IN (" . implode(',', array_map('intval', $arrayID)) . ") AND o.so_number = i.so_number;";
			        break;
			    case 'p':
	        		$query = "SELECT * FROM purchase_items i, purchase_orders o WHERE i.partid IN (" . implode(',', array_map('intval', $arrayID)) . ") AND o.po_number = i.po_number;";
			        break;
			    case 'rma':
	        		$query = "SELECT * FROM return_items i, returns o WHERE i.partid IN (" . implode(',', array_map('intval', $arrayID)) . ") AND o.rma_number = i.rma_number;";
			        break;
			    default:
					//Should rarely ever happen
					break;
			}

			$result = qdb($query) OR die(qe());
			
			while ($row = $result->fetch_assoc()) {
				$initial[] = $row;
			}
		}
		
		switch ($type) {
		    case 's':
		    	$query = "SELECT * FROM inventory inv, sales_items i, sales_orders o WHERE serial_no = '".res(strtoupper($search))."' ";
				$query .= "AND inv.sales_item_id = i.id AND o.so_number = i.so_number;";
		        break;
		    case 'p':
		    	$query = "SELECT * FROM inventory inv, purchase_items i, purchase_orders o WHERE serial_no = '".res(strtoupper($search))."' ";
				$query .= "AND inv.purchase_item_id = i.id AND o.po_number = i.po_number;";
		        break;
		    case 'rma':
		    	$query = "SELECT * FROM inventory inv, return_items i, returns o WHERE serial_no = '".res(strtoupper($search))."' ";
				$query .= "AND inv.returns_item_id = i.id AND o.rma_number = i.rma_number;";
		        break;
		    default:
				//Should rarely ever happen
				break;
		}
		
		$result = qdb($query) OR die(qe());
		
		while ($row = $result->fetch_assoc()) {
			//Checks if the array row already exists within the array, if not add it to the list
			if (!in_array($row, $initial)) {
			    $initial[] = $row;
			}
		}
		//If the initial search is empty populate the data with close alternates
		if(empty($initial) && $type != 'rma') {
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
		} else {
			$type = 'RO';
			$order_out = 'Repair';
		}
		echo"
			<div class='col-lg-6 pad-wrapper data-load' style='margin: 15px 0 20px 0; display: none;'>
			<div class='shipping-dash'>
				<div class='shipping_section_head' data-title='".$order_out." Orders'>";
		echo $status_out.$order_out.' Orders';
		// echo "<a href = '/order_form.php?ps=$order_out' ><div class = 'btn btn-sm btn-standard pull-right' style = 'color:white;margin-top:-5px;display:block;'>
		// <i class='fa fa-plus'></i> 
		// </div></a>";
		echo	'</div>
				<div class="table-responsive">
		            <table class="table heighthover heightstriped table-condensed '.$order.'_table">';
		            output_header($order,$type);
		echo	'<tbody>';
        			output_rows($order, $search);
		echo '	</tbody>
		            </table>
		    	</div>
		    	<div class="col-sm-12 text-center shipping_section_foot shipping_section_foot_lock more" style="padding:0px !important; vertical-align:bottom !important">
	            	<a class="show_more_link" href="#">Show more</a>
	            </div>
            </div>
        </div>';
	}
	
	function output_header($order,$type='Order'){
			echo'<thead>';
			echo'<tr>';
			if($type != 'RO') {
				echo'	<th class="col-sm-1">';
				echo'		Date';
				echo'	</th>';
				echo'	<th class="col-sm-3 company_col">';
				echo'	<span class="line"></span>';
				echo'		Company';
				echo'	</th>';
	            echo'	<th class="col-sm-1">';
	            echo'		<span class="line"></span>';
	            echo'		'.$type.'#';
	            echo'	</th>';
	        	echo'   <th class="col-sm-5 item_col">';
	            echo'   	<span class="line"></span>';
	            echo'       Item';
	            echo'	</th>';
	            echo'   <th class="col-sm-1 qty_col '.($order == 's' || $order == 'p' ? $order.'o': $order).'-column">';
	            echo'   	<span class="line"></span>';
	            echo'   	Qty';
	            echo'  	</th>';
	            echo'   <th class="col-sm-1 status-column" style="display: none;">';
	            echo'   	<span class="line"></span>';
	            echo'   	Status';
	            echo'  	</th>';
				echo'  	<th class="col-sm-1">';
	            echo'   	<span class="line"></span>';
	            echo'  		Action';
	            echo'  	</th>';
	        } else {
	        	echo'	<th class="col-sm-1">';
				echo'		Date';
				echo'	</th>';
				echo'	<th class="col-sm-1 company_col">';
				echo'	<span class="line"></span>';
				echo'		Repair#';
				echo'	</th>';
	            echo'	<th class="col-sm-2">';
	            echo'		<span class="line"></span>';
	            echo'		Item';
	            echo'	</th>';
	        	echo'   <th class="col-sm-3 item_col">';
	            echo'   	<span class="line"></span>';
	            echo'       Serial';
	            echo'	</th>';
	            echo'   <th class="col-sm-3 item_col">';
	            echo'   	<span class="line"></span>';
	            echo'       Notes';
	            echo'	</th>';
	            echo'   <th class="col-sm-1">';
	            echo'   	<span class="line"></span>';
	            echo'   	Due';
	            echo'  	</th>';
	            echo'   <th class="col-sm-1 status-column" style="display: none;">';
	            echo'   	<span class="line"></span>';
	            echo'   	Status';
	            echo'  	</th>';
				echo'  	<th class="col-sm-1">';
	            echo'   	<span class="line"></span>';
	            echo'  		Action';
	            echo'  	</th>';
	        }
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
		if ($order == 'p') {
			$type = 'po';
		} else if ($order == 's') {
			$type = 'so';
		} 
		else if ($order == 'rma') {
			$type = 'rma';
		} else {
			$type = 'ro';
		}
		//if($order != 'rma' && $order != 'ro') {
			if($search =='') {
				//Select a joint summary query of the order we are requesting
				$query = "SELECT * FROM ";
				if ($order == 'p') {
					$query .= "purchase_orders o, purchase_items i ";
					$query .= "WHERE o.po_number = i.po_number ";
					$query .= "ORDER BY o.po_number DESC LIMIT 0, 200; ";
					// $query .= "purchase_orders o, purchase_items i 
					// 			WHERE o.po_number = i.po_number 
					// 			ORDER BY 
					// 			CASE WHEN (cast(i.qty_received as signed) - cast(i.qty as signed)) >= 0 THEN o.po_number END DESC, 
					// 			CASE WHEN (cast(i.qty_received as signed) - cast(i.qty as signed)) < 0 THEN i.receive_date END ASC LIMIT 0 , 200;";
				} else if ($order == 's') {
					$query .= "sales_orders o, sales_items i ";
					$query .= "WHERE o.so_number = i.so_number ";
					$query .= "ORDER BY o.so_number DESC LIMIT 0, 200; ";
				} else if ($order == 'rma') {
					$query .= "returns o, return_items i, inventory c WHERE o.rma_number = i.rma_number AND i.inventoryid = c.id ";
					$query .= "ORDER BY o.rma_number DESC LIMIT 0, 200; ";
				} else if($order == 'ro') {
					$query .= "repair_orders o, repair_items i ";
					$query .= "WHERE o.ro_number = i.ro_number ";
					$query .= "ORDER BY o.created ASC LIMIT 0, 200; ";
				}
				
				$results = qdb($query);
			} else {
				$results = searchQuery($search, $order);
				//print_r($results); die;
			}
			
			//display only the first N rows, but output all of them
			$count = 0;
			$active = 1;
			$complete = 1;
			//Loop through the results.
			if(!empty($results)) {
				//print_r($results);
				foreach ($results as $r){
					//set if a serial is present or not
					$serialDetection[$type] = ($r['serial_no'] != '' ? 'true' : 'false');
					
					//echo $type . " " .($r['serial_no'] != '' ? 'true' : 'false');
					
					$count++;
					if ($order == 's'){ $order_num = $r['so_number']; }
					else if ($order == 'p'){ $order_num = $r['po_number']; }
					else if ($order == 'rma'){ $order_num = $r['rma_number']; }
					else if ($order == 'ro'){ $order_num = $r['ro_number']; }
					
					//$date = date("m/d/Y", strtotime($r['ship_date'] ? $r['ship_date'] : $r['created']));
					$date = date("m/d/Y", strtotime($r['created']));
					$due_date = strtotime($r['receive_date']);
					$company = getCompany($r['companyid']);
					$item = format($r['partid'], true);
					$qty = $r['qty'];
					
					if ($order != 's' && $order != 'rma'){
						$status = ($r['qty_received'] >= $r['qty'] ? 'complete_item' : 'active_item');
					} else if ($order == 's') {
						$status = ($r['qty_shipped'] >= $r['qty'] ? 'complete_item' : 'active_item');
					} else if($order == 'rma') {
						$status = ($r['returns_item_id'] ? 'complete_item' : 'active_item');
					}
				
					if($count<=10){
						echo'	<tr data-order="'.$order_num.'" data-date="'.$due_date.'" class="filter_item '.$status.'">';
					} else{
						echo'	<tr data-order="'.$order_num.'" data-date="'.$due_date.'" class="filter_item show_more '.$status.'" style="display:none;">';
					}

					echo'        <td>'.$date.'</td>';

					if($order != 'ro') {
						echo'        <td><a href="/profile.php?companyid='. $r['companyid'] .'">'.$company.'</a></td>';
					} else {
						echo'        <td>'.$order_num.' <a href="/repair.php?on='.$order_num.'"><i class="fa fa-arrow-right" aria-hidden="true"></i></a></td>';
					}

					//Either go to inventory add or PO or shipping for SO
					if($order == 'p') {
						$base = 'inventory_add.php';
						if(in_array("3", $USER_ROLES) || in_array("1", $USER_ROLES)) {
							$base = 'order_form.php';
						}
						echo'    <td><a href="/'.$base.'?on='.$order_num.'&ps='.$order.'">'.$order_num.'</a></td>';
					} else if($order == 's') {
						$base = 'shipping.php';
						if(in_array("3", $USER_ROLES) || in_array("1", $USER_ROLES)) {
							$base = 'order_form.php';
						}
						echo'    <td><a href="/'.$base.'?on='.$order_num.'&ps='.$order.'">'.$order_num.'</a></td>';
					} else if($order == 'rma') {
						echo'        <td><a href="/rma.php?rma='.$order_num.'">'.$order_num.'</a></td>';
					}

					echo'        <td><div class="desc">'.$item.'</div></td>';
					if($order != 'ro') {
						echo'    	<td>'.($r['serial_no'] ? $r['serial_no'] : $qty).'</td>';
					} else {
						$serial;
						if($r['id']) {
							$query = "SELECT serial_no FROM inventory WHERE repair_item_id = ".prep($r['id']).";";
							$result = qdb($query) or die(qe() . ' ' . $query);

							if (mysqli_num_rows($result)>0) {
								$r = mysqli_fetch_assoc($result);
								$serial = $r['serial_no'];
							}
						}
						echo'        <td>'.($serial ? $serial : 'TBA').'</td>';
						echo'        <td>'.$r['public_notes'].'</td>';
					}

					if($order == 'ro') {
						global $now;
						echo'    	<td>'.format_date($now).'</td>';
					}
					
					echo'    	<td style="display: none;" class="status-column">'.(($status == 'active_item') ? '<span class="label label-warning active_label status_label" style="display: none;">Active</span> ' : '' ).(($status == 'complete_item') ? '<span class="label label-success complete_label status_label" style="display: none;">Complete</span> ' : '' ).'</td>';
					if($order != 'ro' && $order != 'rma') {
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
						echo'			<a href="/repair.php?on='.$order_num.'"><i style="margin-right: 5px;" class="fa fa-user-circle-o" aria-hidden="true"></i></a>';
						echo'			<a href="/repair_add.php?on='.$order_num.'"><i style="margin-right: 5px;" class="fa fa-truck" aria-hidden="true"></i></a>';
						echo'			<a href="/order_form.php?on='.$order_num.'&ps=ro"><i style="margin-right: 5px;" class="fa fa-pencil" aria-hidden="true"></i></a>';
						echo'		</td>'; 							
					}

					
					echo'	</tr>';
				}
		}
	}
	
	function format($partid, $desc = true){
		$r = reset(hecidb($partid, 'id'));
		$parts = explode(' ',$r['part']);
	    $display = "<span class = 'descr-label'>".$parts[0]." &nbsp; ".$r['heci']."</span>";
	    if($desc)
    		$display .= '<div class="description desc_second_line descr-label" style = "color:#aaa;">'.dictionary($r['manf']).' &nbsp; '.dictionary($r['system']).
				'</span> <span class="description-label">'.dictionary(substr($r['description'],0,30)).'</span></div>';

	    return $display;
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
		
	</style>
</head>

<body class="sub-nav accounts-body">
<!----------------------------------------------------------------------------->
<!------------------------- Output the navigation bar ------------------------->
<!----------------------------------------------------------------------------->

	<?php include 'inc/navbar.php'; ?>
	<div class="table-header" style="width: 100%; min-height: 48px;">
		<div class="row" style="padding: 8px;" id = "filterBar">
			<div class="col-md-1">
			    <div class="btn-group medium" data-toggle="buttons">
			        <button data-toggle="tooltip" data-placement="right" title="" data-original-title="Active" class="btn btn-warning btn-sm btn-status left filter_status <?=($filter == 'active' ? 'active' : '');?>" type="submit" data-filter="active">
			        	<i class="fa fa-sort-numeric-desc"></i>	
			        </button>
			        <button data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Completed" class="btn btn-success btn-sm btn-status middle filter_status <?=($filter == 'complete' ? 'active' : '');?>" type="submit" data-filter="complete">
			        	<i class="fa fa-history"></i>	
			        </button>
					<button data-toggle="tooltip" data-placement="bottom" title="" data-original-title="All" class="btn btn-info btn-sm btn-status right filter_status <?=(($filter == 'all' || $filter == '') ? 'active' : '');?>" type="submit" data-filter="all">
			        	All
			        </button>
			    </div>

			</div>
			<div class = "col-md-3">
			</div>
			<div class="col-md-4 text-center">
            	<h2 class="minimal" id="filter-title">Operations Dashboard</h2>
			</div>
			
			<!--This Handles the Search Bar-->

			<div class="col-md-2 col-sm-2">
			</div>
			

			<div class="col-md-2 col-sm-2">
			</div>
		</div>
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
	

	<table class="" style="display:none;">
		<tr id = "filterTableOutput">
			<td class = "col-md-2">
	
			    <div class="btn-group">
			        <button class="glow left large btn-report <?php if ($report_type=='summary') { echo ' active'; } ?>" type="submit" data-value="summary">
			        	<i class="fa fa-sort-numeric-desc"></i>	
			        </button>
					<input type="radio" name="report_type" value="summary" class="hidden"<?php if ($report_type=='summary') { echo ' checked'; } ?>>
			        <button class="glow right large btn-report<?php if ($report_type=='detail') { echo ' active'; } ?>" type="submit" data-value="detail">
			        	<i class="fa fa-history"></i>	
			        </button>
			        <input type="radio" name="report_type" value="detail" class="hidden"<?php if ($report_type=='detail') { echo ' checked'; } ?>>
			    </div>
				<div class="btn-group">
			        <button class="glow left large btn-report" type="submit" data-value="Sales" id = "sales">
			        	Sales	
			        </button>
					<input type="radio" name="market_table" value="Sales" class="hidden"<?php if ($market_table=='Sales') { echo ' checked'; } ?>>
			        <button class="glow right large btn-report<?php if ($market_table=='Purchases') { echo ' active'; } ?>" id="purchases" type="submit" data-value="Purchases">
			        	Purchases
			        </button>
			        <input type="radio" name="market_table" value="Purchases" class="hidden"<?php if ($market_table=='Purchases') { echo ' checked'; } ?>>
			    </div>
			</td>
			<td class = "col-md-1">
				<div class="input-group date datetime-picker-filter">
		            <input type="text" name="START_DATE" class="form-control input-sm" value="<?php echo $startDate; ?>" style = "min-width:50px;"/>
		            <span class="input-group-addon">
		                <span class="fa fa-calendar"></span>
		            </span>
		        </div>
			</td>
			<td class = "col-md-1">
				<div class="input-group date datetime-picker-filter">
			            <input type="text" name="END_DATE" class="form-control input-sm" value="<?php echo $endDate; ?>" style = "min-width:50px;"/>
			            <span class="input-group-addon">
			                <span class="fa fa-calendar"></span>
			            </span>
			    </div>
			</td>
			<td class = "col-md-1 btn-group" data-toggle="buttons" id="shortDateRanges">
				<div class="date-options">
					<div class="btn btn-default btn-sm">&gt;</div>
			        <button class="btn btn-sm btn-default left large btn-report" id = "MTD" type="radio">MTD</button>
	    			<button class="btn btn-sm btn-default center small btn-report" id = "Q1" type="radio">Q1</button>
					<button class="btn btn-sm btn-default center small btn-report" id = "Q2" type="radio">Q2</button>
					<button class="btn btn-sm btn-default center small btn-report" id = "Q3" type="radio">Q3</button>		
					<button class="btn btn-sm btn-default center small btn-report" id = "Q4" type="radio">Q4</button>	
					<button class="btn btn-sm btn-default right small btn-report" id = "YTD" type="radio">YTD</button>
				</div>
			</td>
			<td class = "col-md-2">
				<input type="text" name="part" class="form-control input-sm" value ='<?php echo $part?>' placeholder = 'Part/HECI'/>
			</td>
			<td class = "col-md-2">
				<div class="input-group">
					<input type="text" name="min" class="form-control input-sm" value ='<?php if($min_price > 0){echo format_price($min_price);}?>' placeholder = 'Min $'/>
					<span class="input-group-addon">-</span>
					<input type="text" name="max" class="form-control input-sm" value ='<?php echo format_price($max_price);?>' placeholder = 'Max $'/>
				</div>
			</td>
			<td class = "col-md-3">
				<div class="pull-right form-inline">
					<div class="input-group">
						<select name="companyid" id="companyid" class="company-selector">
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
				</div>
			</td>
		</tr>
	</table>
	
	<div class="row table-holder">
		<?php 
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
    <?php //print_r($serialDetection);?>

<?php include_once 'inc/footer.php'; ?>
<script src="js/operations.js?id=<?php if (isset($V)) { echo $V; } ?>"></script>
<script>
	(function($){
		$('#item-updated-timer').delay(3000).fadeOut('fast');
	
		//Triggering Aaron 2017
		var search = "<?=($_REQUEST['s'] ? $_REQUEST['s'] : $_REQUEST['search']); ?>";
		var filter = "<?=$filter;?>";
		
		var levenshtein = "<?=$levenshtein;?>";
		var searched = "<?=$nothingFound;?>";
		var serialDetection = <?= json_encode($serialDetection) ?>;

		//Load in the objects after the page is loaded for less jumpy frenziness
		$('.data-load').fadeIn();
		
		//Search parameter has been passed in that case show the search results
		if(search != '') {
			if(filter != '') {
				window.history.replaceState(null, null, "/operations.php?search=" + search + "&filter=all");
			} else {
				window.history.replaceState(null, null, "/operations.php?search=" + search);
			}
			
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
				$('.'+key+'-column').html('<span class="line"></span> Serial');
				//If a serial is detected then change the table headers and sizes or anything else that needs to be altered
				$('.'+key+'-column').closest(".qty_col").addClass('col-sm-2').removeClass('col-sm-1');
				$('.'+key+'-column').closest(".item_col").addClass('col-sm-4').removeClass('col-sm-5');
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
		
        //Prefilter if loaded with a parameter in url
		if(filter != '') {
			var type = filter;
			//alert(filter);
			$('.filter_item').hide();

			if(type == 'complete') {
				$('.show_more').hide();
				sortTheTable('complete');
				$('.p_table .complete_item:lt(10)').show();
				$('.s_table .complete_item:lt(10)').show();
				$('.rma_table .complete_item:lt(10)').show();
				$('.ro_table .complete_item:lt(10)').show();
			} else if(type == 'active') {
				$('.show_more').hide();
				sortTheTable('active');
				$('.p_table .active_item:lt(10)').show();
				$('.s_table .active_item:lt(10)').show();
				$('.rma_table .active_item:lt(10)').show();
				$('.ro_table .active_item:lt(10)').show();
			} else {
				$('.p_table .filter_item:lt(10)').show();
				$('.s_table .filter_item:lt(10)').show();
				$('.rma_table .filter_item:lt(10)').show();
				$('.show_more').hide();
				$('.status_label').show();
				$('.status-column').show();
				sortTheTable('all');
			}
		}

		$(document).on("click onload", ".filter_status", function(){
			var type = $(this).data('filter');
			//alert($('.show_more_link:first').text() == "Show more");

			$('.filter_item').hide();
			$('.filter_status').removeClass('active');

			if(type == 'complete') {
				if($('.show_more_link:first').text() == "Show more") {
					$('.p_table .complete_item:lt(10)').show();
					$('.s_table .complete_item:lt(10)').show();
					$('.rma_table .complete_item:lt(10)').show();
					$('.ro_table .complete_item:lt(10)').show();
				} else {
					$('.complete_item').show();
				}
				$('.active_item').hide();
				$('.status-column').hide();
				$('.status_label').hide();
				sortTheTable('complete');
				//alert("here");
			} else if(type == 'active') {
				if($('.show_more_link:first').text() == "Show more") {
					$('.p_table .active_item:lt(10)').show();
					$('.s_table .active_item:lt(10)').show();
					$('.rma_table .active_item:lt(10)').show();
					$('.ro_table .active_item:lt(10)').show();
				} else {
					$('.active_item ').show();
				}
				$('.complete_item').hide();
				$('.status-column').hide();
				$('.status_label').hide();
				sortTheTable('active');
			} else {
				//$('.filter_item').show();
				if($('.show_more_link:first').text() == "Show more") {
					$('.p_table .filter_item:lt(10)').show();
					$('.s_table .filter_item:lt(10)').show();
					$('.rma_table .filter_item:lt(10)').show();
					$('.ro_table .filter_item:lt(10)').show();
				} else {
					$('.filter_item').show();
				}
				$('.status_label').show();
				$('.status-column').show();
				sortTheTable('all');
			}
			
			if(search != '') {
				window.history.replaceState(null, null, "/operations.php?search=" + search + "&filter=all");
			} else {
				window.history.replaceState(null, null, "/operations.php?filter=" + type);
			}
			$(this.element).addClass('active');
		});
		
	})(jQuery);
</script>

</body>
</html>