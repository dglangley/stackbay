<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	
	require_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/dictionary.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
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
	
	function params_array($type){
		$info = array();
		if($type == "p"){
			$info['display'] = "Purchase";
			$info['tables'] = " purchase_orders o, purchase_items i WHERE o.po_number = i.po_number ";
			$info['short'] = "po";
			$info['active'] = " AND (CAST(i.qty AS SIGNED) - CAST(i.qty_received AS SIGNED)) > 0 ";
			$info['inactive'] = " AND (CAST(i.qty AS SIGNED) - CAST(i.qty_received AS SIGNED)) <= 0 ";
			$info['url'] = "inventory_add";
		}
		else if ($type == "s"){
			$info['display'] = "Sales";
			$info['tables'] = " sales_orders o, sales_items i WHERE o.so_number = i.so_number ";
			$info['short'] = "po";
			$info['active'] = " AND i.ship_date IS NULL ";
			$info['inactive'] = " AND i.ship_date IS NOT NULL  ";
			$info['url'] = "shipping";
		}
		else if ($type == "rma"){
			$info['display'] = "RMA";
			$info['tables'] = " sales_orders o, sales_items i WHERE o.so_number = i.so_number ";
			$info['short'] = "po";
			$info['active'] = " AND i.ship_date IS NULL ";
			$info['inactive'] = " AND i.ship_date IS NOT NULL  ";
			$info['url'] = "inventory_add";
		}
		else{
				$info['case'] = $type;
		}
		return $info;
	}
	

	//Search first by the global seach if it is set or by the parameter after if global is not set
	$search = ($_REQUEST['s'] ? $_REQUEST['s'] : $_REQUEST['search']);
	$levenshtein = false;
	$nothingFound = false;
	$serialDetection = false;
	
	function searchQuery($search, $type) {
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
		    // case 'rma':
      //  		$query = "SELECT DISTINCT * FROM inventory WHERE last_rma = '".res(strtoupper($search))."';";
		    //     break;
		    // case 'ro':
      //  		$query = "SELECT DISTINCT * FROM inventory WHERE last_ro = '".res(strtoupper($search))."';";
		    //     break;
			default:
				//Should rarely ever happen
				break;
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
		if(empty($initial))
			$initial = soundsLike($search, $type);
		
		return $initial;
	}
	
	function soundsLike($search, $type) {
		global $levenshtein, $nothingFound;
		$arr = array();
		$initial = array();
		$query;
		
		$query = 'SELECT * FROM inventory WHERE soundex(serial_no) LIKE soundex("'.res(strtoupper($search)).'");';

		$result = qdb($query) OR die(qe());
		
		if (mysqli_num_rows($result)>0) {
			while ($row = $result->fetch_assoc()) {
				$arr[] = $row['serial_no'];
			}
		} else {
			$nothingFound = true;
		}
		
		//print_r($arr); die();
		
		if(!empty($arr)) {
			//Something was found similar to the search
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
			
			$closest_arr = join(', ', array_slice($sorted_arr, 0, 3));
			
			switch ($type) {
			    case 's':
			    	$query = "SELECT DISTINCT * FROM inventory inv, sales_items i, sales_orders o WHERE serial_no IN ('" . $closest_arr . "') ";
					$query .= "AND inv.sales_item_id = i.id AND o.so_number = i.so_number;";
			        break;
			    case 'p':
			    	$query = "SELECT DISTINCT * FROM inventory inv, purchase_items i, purchase_orders o WHERE serial_no IN ('" . $closest_arr . "') ";
					$query .= "AND inv.purchase_item_id = i.id AND o.po_number = i.po_number;";
			        break;
			    default:
					//Should rarely ever happen
					break;
			}
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
			$order_out = 'Purchase';
		} else if($order =="s") {
			$order_out = 'Sales';
		} else if($order =="rma") {
			$order_out = 'RMA';
		} else {
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
		            <table class="table heighthover heightstriped table-condensed">';
		            output_header();
		echo	'<tbody>';
        			output_rows($order, $search);
		echo '	</tbody>
		            </table>
		    	</div>
		    	<div class="col-md-12 text-center shipping_section_foot shipping_section_foot_lock more" style="padding-bottom: 15px;">
	            	<a href="#">Show more</a>
	            </div>
            </div>
        </div>';
	}
	
	function output_header(){
			echo'<thead>';
			echo'<tr>';
			echo'	<th class="col-md-1">';
			echo'		Date';
			echo'	</th>';
			echo'	<th class="col-md-4 company_col">';
			echo'	<span class="line"></span>';
			echo'		Company';
			echo'	</th>';
            echo'	<th class="col-md-1">';
            echo'		<span class="line"></span>';
            echo'		Order#';
            echo'	</th>';
        	echo'   <th class="col-md-4">';
            echo'   	<span class="line"></span>';
            echo'       Item';
            echo'	</th>';
            echo'   <th class="col-md-1 qty_col">';
            echo'   	<span class="line"></span>';
            echo'   	Qty';
            echo'  	</th>';
			echo'  	<th class="col-md-2">';
            echo'  		&nbsp;';
            echo'  	</th>';
            echo'</tr>';
			echo'</thead>';
	}
	
	//Inputs expected:
	//	- Status: Completed, Active
	//	- Order: s, p, rma, ro
	function output_rows($order = '', $search = ''){
		global $serialDetection;
		$results;
		$status;
		
		if($order != 'rma' && $order != 'ro') {
			if($search =='') {
				//Select a joint summary query of the order we are requesting
				$query = "SELECT * FROM ";
				if ($order == 'p'){
					$query .= "purchase_orders o, purchase_items i ";
					$query .= "WHERE o.po_number = i.po_number ";
					$query .= "ORDER BY o.po_number DESC LIMIT 0 , 100;";
				}
				else{
					$query .= "sales_orders o, sales_items i ";
					$query .= "WHERE o.so_number = i.so_number ";
				}
				
				$results = qdb($query);
			} else {
				$results = searchQuery($search, $order);

			}
		
			//display only the first N rows, but output all of them
			$count = 0;
			
			//Loop through the results.
			if(!empty($results)) {
				foreach ($results as $r){
					//set if a serial is present or not
					$serialDetection = ($r['serial_no'] != '' ? true : false);
					$count++;
					if ($order == 's'){
						$purchaseOrder = $r['so_number'];
					}
					else{
						$purchaseOrder = $r['po_number'];
					}
					$date = date("m/d/Y", strtotime($r['ship_date'] ? $r['ship_date'] : $r['created']));
					$company = getCompany($r['companyid']);
					$item = format($r['partid'], false);
					$qty = $r['qty'];
					if ($order != 's'){
						$status = ($r['qty_received'] >= $r['qty'] ? 'complete_item' : 'active_item');
					} else {
						$status = ($r['qty_shipped'] >= $r['qty'] ? 'complete_item' : 'active_item');
					}
				
					if($count<=10){
						echo'	<tr class="filter_item '.$status." ".($status == "complete_item" ? "danger": "").'">';
					}
					else{
						echo'	<tr class="show_more '.$status.'" style="display:none;">';
					}
						echo'        <td>'.$date.'</td>';
						echo'        <td><a href="/profile.php?companyid='. $r['companyid'] .'">'.$company.'</a></td>';
						//Either go to inventory add or PO or shipping for SO
						if($order == 'p')
							echo'        <td><a href="/inventory_add.php?on='.$purchaseOrder.'&ps='.$order.'">'.$purchaseOrder.'</a></td>';
						else
							echo'        <td><a href="/shipping.php?on='.$purchaseOrder.'&ps='.$order.'">'.$purchaseOrder.'</a></td>';
						echo'        <td>'.$item.'</td>';
						echo'    	<td>'.($r['serial_no'] ? $r['serial_no'] : $qty).'</td>';
						echo'    	<td class="status">
										<a href="/'.($order == p ? 'inventory_add' : 'shipping').'.php?on='.$purchaseOrder.'&ps='.$order.'"><i style="margin-right: 5px;" class="fa fa-truck" aria-hidden="true"></i></a>
										<a href="/order_form.php?on='.$purchaseOrder.'&ps='.$order.'"><i style="margin-right: 5px;" class="fa fa-pencil" aria-hidden="true"></i></a>
									</td>'; 
					echo'	</tr>';
				}
			}
		}
	}
	
	function format($partid, $desc = true){
		$r = reset(hecidb($partid, 'id'));
	    $display = "<span class = 'descr-label'>".$r['part']." &nbsp; ".$r['heci']."</span>";
	    if($desc)
    		$display .= '<div class="description desc_second_line descr-label" style = "color:#aaa;">'.dictionary($r['manf'])." &nbsp; ".dictionary($r['system']).'</span> <span class="description-label">'.dictionary($r['description']).'</span></div>';

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
	<title>VMM Operations Dashboard</title>
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
			min-height: 410px;
		}
		
		.shipping_section_foot_lock {
			padding-bottom: 15px;
		    position: absolute;
		    bottom: 0px;
		}
		
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
			    <div class="btn-group" data-toggle="buttons">
			        <button class="glow left large btn-report filter_status <?=($filter == 'active' ? 'active' : '');?>" type="submit" data-filter="active">
			        	<i class="fa fa-sort-numeric-desc"></i>	
			        </button>
					<!--<input type="radio" name="report_type" value="summary" class="hidden">-->
			        <button class="glow center large btn-report filter_status <?=($filter == 'complete' ? 'active' : '');?>" type="submit" data-filter="complete">
			        	<i class="fa fa-history"></i>	
			        </button>
			        <!--<input type="radio" name="report_type" value="detail" class="hidden">-->
					<button class="glow right large btn-report filter_status <?=(($filter == 'all' || $filter == '') ? 'active' : '');?>" type="submit" data-filter="all" checked>
			        	All<!--<i class="fa fa-history"></i>	-->
			        </button>
			        <!--<input type="radio" name="report_type" value="detail" class="hidden"<?php if ($report_type=='detail') { echo ' checked'; } ?>>-->
			    </div>

			</div>
			<div class = "col-md-3">
				<!--<div class="form-group col-md-4 nopadding">-->
				<!--	<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY">-->
			 <!--           <input type="text" name="START_DATE" class="form-control input-sm" value="<?php echo $startDate; ?>">-->
			 <!--           <span class="input-group-addon">-->
			 <!--               <span class="fa fa-calendar"></span>-->
			 <!--           </span>-->
			 <!--       </div>-->
				<!--</div>-->
				<!--<div class="form-group col-md-4 nopadding">-->
				<!--	<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY" data-maxdate="<?php echo date("m/d/Y"); ?>">-->
			 <!--           <input type="text" name="END_DATE" class="form-control input-sm" value="<?php echo $endDate; ?>">-->
			 <!--           <span class="input-group-addon">-->
			 <!--               <span class="fa fa-calendar"></span>-->
			 <!--           </span>-->
				<!--    </div>-->
				<!--</div>-->
				<!--<div class="form-group col-md-4 nopadding">-->
				<!--	<div class="btn-group" id="dateRanges">-->
				<!--		<div id="btn-range-options">-->
				<!--			<button class="btn btn-default btn-sm">&gt;</button>-->
				<!--			<div class="animated fadeIn hidden" id="date-ranges" style = 'width:217px;'>-->
				<!--		        <button class="btn btn-sm btn-default left large btn-report" type="button" data-start="<?php echo date("m/01/Y"); ?>" data-end="<?php echo date("m/d/Y"); ?>">MTD</button>-->
				<!--    			<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("01/01/Y"); ?>" data-end="<?php echo date("03/31/Y"); ?>">Q1</button>-->
				<!--				<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("04/01/Y"); ?>" data-end="<?php echo date("06/30/Y"); ?>">Q2</button>-->
				<!--				<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("07/01/Y"); ?>" data-end="<?php echo date("09/30/Y"); ?>">Q3</button>-->
				<!--				<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("10/01/Y"); ?>" data-end="<?php echo date("12/31/Y"); ?>">Q4</button>-->
				<!--			</div><!-- animated fadeIn -->
				<!--		</div><!-- btn-range-options -->
				<!--	</div><!-- btn-group -->
				<!--</div><!-- form-group -->
			</div>
			<div class="col-md-4 text-center">
            	<h2 class="minimal" id="filter-title">Operations Dashboard</h2>
			</div>
			
			<!--This Handles the Search Bar-->

			<div class="col-md-2 col-sm-2">
				<!--<div class="input-group">-->
	   <!--           <input type="text" class="form-control input-sm" id="part_search" placeholder="Filter By Part/Serial" value="<?=$searched;?>">-->
    <!--          		<span class="input-group-btn">-->
	   <!--             	<button class="btn btn-sm btn-primary part_filter"><i class="fa fa-filter"></i></button>              -->
	   <!--         	</span>-->
	   <!--         </div>-->
			</div>
			

			<div class="col-md-2 col-sm-2">
				<!--<div class="company input-group">-->
				<!--	<select name='companyid' id='companyid' class='form-control input-xs company-selector required' >-->
				<!--		<option value=''>Select a Company</option>-->
				<!--	</select>-->
				<!--	<span class="input-group-btn">-->
				<!--		<button class="btn btn-sm btn-primary inventory_filter"><i class="fa fa-filter"></i></button>   -->
				<!--	</span>-->
				<!--</div>-->
			</div>
		</div>
	</div>
	
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
		var serialDetection = "<?=$serialDetection;?>";
		
		//Load in the objects after the page is loaded for less jumpy frenziness
		$('.data-load').fadeIn();
		
		//Search parameter has been passed in that case show the search results
		if(search != '') {
			if(filter != '') {
				window.history.replaceState(null, null, "/operations.php?search=" + search + "&filter=" + filter);
			} else {
				window.history.replaceState(null, null, "/operations.php?search=" + search);
			}
			if(levenshtein) {
				modalAlertShow("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning", "No items found for <b>" + search + "</b>.<br><br> Listed are similar results.", false);
			} else if(searched) {
				modalAlertShow("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning", "No items found for <b>" + search + "</b>.", false);
			}
		}
		
		//If a serial is detected then change the table headers and sizes or anything else that needs to be altered
		if(serialDetection) {
			$('.qty_col').text('Serial');
			$('.qty_col').addClass('col-md-2');
			$('.qty_col').removeClass('col-md-1');
			$('.company_col').addClass('col-md-3');
			$('.company_col').removeClass('col-md-4');
		}
		
		//Prefilter if loaded with a parameter in url
		if(filter != '') {
			var type = filter;
			
			$('.filter_item').hide();

			if(type == 'complete') {
				$('.complete_item').show();
			} else if(type == 'active') {
				$('.active_item ').show();	
			} else {
				$('.filter_item').show();
			}
		}
		
		$(document).on("click onload", ".filter_status", function(){
			var type = $(this).data('filter');
			
			$('.filter_item').hide();
			$('.filter_status').removeClass('active');
			
			if(type == 'complete') {
				$('.complete_item').show();
				$(this).addClass('active');
			} else if(type == 'active') {
				$('.active_item ').show();	
				$(this).addClass('active');
			} else {
				$('.filter_item').show();
				$(this).addClass('active');
			}
			
			if(search != '') {
				window.history.replaceState(null, null, "/operations.php?search=" + search + "&filter=" + type);
			} else {
				window.history.replaceState(null, null, "/operations.php?filter=" + type);
			}
		});
		
	})(jQuery);
</script>

</body>
</html>
