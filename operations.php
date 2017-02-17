<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	
	require_once $rootdir.'/inc/dbconnect.php';
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
	
	//Search first by the global seach if it is set or by the parameter after if global is not set
	$search = ($_REQUEST['s'] ? $_REQUEST['s'] : $_REQUEST['search']);
	
	function searchQuery($search) {
		$initial = array();
		
		//$initial = hecidb($search);
		$query = "SELECT * FROM inventory WHERE serial_no = '".res(strtoupper($search))."';";
		$result = qdb($query) OR die(qe());
		
		while ($row = $result->fetch_assoc()) {
			$initial[] = $row;
		}
		
		//If the initial search is empty populate the data with close alternates
		if(empty($initial))
			$initial = soundsLike($search);
		
		return $initial;
	}
	
	function soundsLike($search) {
		$arr = array();
		//SQL Soundex searches the table for similar
		$query = 'SELECT * FROM inventory WHERE soundex(serial_no) LIKE soundex("'.res(strtoupper($search)).'");';

		$result = qdb($query) OR die(qe());
		
		//if (mysqli_num_rows($result)>0) {
			while ($row = $result->fetch_assoc()) {
				$arr[] = $row['serial_no'];
			}
		//}
		
		//This prevents duplicate entries of similar results
		$arr = array_values(array_unique($arr));
		for($i=0; $i<count($arr); $i++) {
			$holder = $arr[$i];
			//Run the levenshtein step search
	   		$temp_arr[$i] = levenshtein($search, $holder);
		}
		
		return $arr;
	}
	
	//print_r(searchQuery($search));
	//die();
	
	function output_module($order,$status){
		
		$status_out = ($status =="Active") ? 'Outstanding ' : "Completed ";
		$order_out = ($order =="p") ? "Purchase" : "Sales";

		echo"
			<div class='col-lg-6 pad-wrapper' style='margin: 25px 0;'>
			<div class='shipping-dash'>

				<div class='shipping_section_head' data-title='".$status_out.$order_out." Orders'>";
		echo $status_out.$order_out.' Orders';
		// echo "<a href = '/order_form.php?ps=$order_out' ><div class = 'btn btn-sm btn-standard pull-right' style = 'color:white;margin-top:-5px;display:block;'>
		// <i class='fa fa-plus'></i> 
		// </div></a>";
		echo	'</div>
				<div class="table-responsive">
		            <table class="table heighthover heightstriped table-condensed">';
		            output_header($status);
		echo	'<tbody>';
            		output_rows($order,$status);
		echo '	</tbody>
		            </table>
		    	</div>
		    	<div class="col-md-12 text-center shipping_section_foot more" style="padding-bottom: 15px;">
	            	<a href="#">Show more</a>
	            </div>
            </div>
        </div>';
	}
	
	function output_header($status){
			echo'<thead>';
			echo'<tr>';
			if($status=="Complete"){
				echo'	<th class="col-md-1">';
				echo'		Ship Date';
				echo'	</th>';
			} else {
				echo'	<th class="col-md-1">';
				echo'		Date';
				echo'	</th>';
			}
			echo'	<th class="col-md-4">';
			echo'	<span class="line"></span>';
			echo'		Company';
			echo'	</th>';
            echo'	<th class="col-md-1">';
            echo'		<span class="line"></span>';
            echo'		Order#';
            echo'	</th>';
        if($status=="Active"){
            echo'   <th class="col-md-4">';
        }
        else {
        	echo'   <th class="col-md-4">';
        }
            echo'   	<span class="line"></span>';
            echo'       Item';
            echo'	</th>';
            echo'   <th class="col-md-1">';
            echo'   	<span class="line"></span>';
            echo'   	Qty';
            echo'  	</th>';
		if($status=="Complete"){
            echo'  	<th class="col-md-2">';
            echo'  		&nbsp;';
            echo'  	</th>';
            echo'</tr>';
            echo'</thead>';
		} else {
			echo'  	<th class="col-md-2">';
            echo'  		&nbsp;';
            echo'  	</th>';
            echo'</tr>';
			echo'</thead>';
		}
	}
	
	//Inputs expected:
	//	- Status: Completed, Active
	//	- Order: s, p
	function output_rows($order, $status){
		
		//Select a joint summary query of the order we are requesting
		$query = "SELECT * FROM ";
		if ($order == 'p'){
			$query .= "purchase_orders o, purchase_items i ";
			$query .= "WHERE o.po_number = i.po_number ";
			if($status == 'Active') {
				$query .= "AND (CAST(i.qty AS SIGNED) - CAST(i.qty_received AS SIGNED)) > 0 ";
			} else {
				$query .= "AND (CAST(i.qty AS SIGNED) - CAST(i.qty_received AS SIGNED)) <= 0 ";
			}
			//$query .= "AND status = '" . res($status) . "' ";
			$query .= "ORDER BY o.po_number DESC LIMIT 0 , 100;";
		}
		else{
			$query .= "sales_orders o, sales_items i ";
			$query .= "WHERE o.so_number = i.so_number ";
			if($status == 'Active') {
				$query .= "AND i.ship_date IS NULL ";
				$query .= "ORDER BY o.so_number DESC LIMIT 0 , 100;";
			} else {
				$query .= "AND i.ship_date IS NOT NULL ";
				$query .= "ORDER BY i.ship_date DESC LIMIT 0 , 100;";
			}
		}
		
		$results = qdb($query);
	
		//display only the first N rows, but output all of them
		$count = 0;
		
		//Loop through the results.
		foreach ($results as $r){
			$count++;
			if ($order == 's'){
				$purchaseOrder = $r['so_number'];
			}
			else{
				$purchaseOrder = $r['po_number'];
			}
			$date = date("m/d/Y", strtotime($r['ship_date'] ? $r['ship_date'] : $r['created']));
			$company = getCompany($r['companyid']);
			$item = getPart($r['partid']);
			$qty = $r['qty'];
		
		
			if($count<=10){
				echo'	<tr>';
			}
			else{
				echo'	<tr class="show_more" style="display:none;">';
			}
				echo'        <td>'.$date.'</td>';
				echo'        <td><a href="/profile.php?companyid='. $r['companyid'] .'">'.$company.'</a></td>';
				//Either go to inventory add or PO or shipping for SO
				if($order == 'p')
					echo'        <td><a href="/inventory_add.php?on='.$purchaseOrder.'&ps='.$order.'">'.$purchaseOrder.'</a></td>';
				else
					echo'        <td><a href="/shipping.php?on='.$purchaseOrder.'&ps='.$order.'">'.$purchaseOrder.'</a></td>';
				echo'        <td>'.$item.'</td>';
				echo'    	<td>'.$qty.'</td>';
			if($status=="Complete"){
				$arr = explode(' ',trim($item));
				echo'    	<td class="status">
								<a href="/inventory.php?search='.$arr[0].'"><i style="margin-right: 5px;" class="fa fa-qrcode" aria-hidden="true"></i></a>
								<a href="/'.($order == p ? 'inventory_add' : 'shipping').'.php?on='.$purchaseOrder.'&ps='.$order.'"><i style="margin-right: 5px;" class="fa fa-truck" aria-hidden="true"></i></a>
								<a href="/order_form.php?on='.$purchaseOrder.'&ps='.$order.'"><i style="margin-right: 5px;" class="fa fa-pencil" aria-hidden="true"></i></a>
							</td>';
			} else {
				echo'    	<td class="status">
								<a href="/'.($order == p ? 'inventory_add' : 'shipping').'.php?on='.$purchaseOrder.'&ps='.$order.'"><i style="margin-right: 5px;" class="fa fa-truck" aria-hidden="true"></i></a>
								<a href="/order_form.php?on='.$purchaseOrder.'&ps='.$order.'"><i style="margin-right: 5px;" class="fa fa-pencil" aria-hidden="true"></i></a>
							</td>'; 
			}
			echo'	</tr>';
		}
		
		// //If there are less than ten rows, fill with blanks
		while ($count < 10){
			echo'	<tr class = "empty_row">';
			echo'        <td>&nbsp;</td>';
			echo'        <td>&nbsp;</td>';
			echo'        <td>&nbsp;</td>';
			echo'        <td>&nbsp;</td>';
			echo'   	 <td>&nbsp;</td>';
			if($status=="Active"){
				echo'    	<td class="status">&nbsp;</td>';
			}
			echo'	</tr>';
		 echo'	</tr>';
		 $count++;
		}
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

			<div class="col-md-2 col-sm-2">
				<!--<input class="form-control" type="text" name="" placeholder="Location"/>-->
<!--
				<div class="row">
					<div class='col-md-6' style = 'padding-right:0px;'><?= loc_dropdowns('place')?></div>
					<div class='col-md-3 nopadding'><?= loc_dropdowns('instance')?></div>
					<div class="col-md-3" style  = 'padding-right:0px;padding-left:5px;'>
						<div class="input-group">
			              <input type="text" class="form-control input-sm" id="po_filter" placeholder="PO">
			            </div>
					</div>
				</div>
-->
			</div>
			<div class = "col-md-2">
<!--
				<div class="form-group col-md-6 nopadding">
					<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY">
			            <input type="text" name="START_DATE" class="form-control input-sm" value="<?php echo $startDate; ?>">
			            <span class="input-group-addon">
			                <span class="fa fa-calendar"></span>
			            </span>
			        </div>
				</div>
				<div class="form-group col-md-6 nopadding">
					<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY" data-maxdate="<?php echo date("m/d/Y"); ?>">
			            <input type="text" name="END_DATE" class="form-control input-sm" value="<?php echo $endDate; ?>">
			            <span class="input-group-addon">
			                <span class="fa fa-calendar"></span>
			            </span>
				    </div>
				</div>
-->
			</div>
			<div class="col-md-4 col-sm-4 text-center">
            	<h2 class="minimal" id="filter-title">Operations Dashboard</h2>
			</div>
			
			<!--This Handles the Search Bar-->
			<div class="col-md-2 col-sm-2">
<!--
				<div class="input-group">
	              <input type="text" class="form-control input-sm" id="part_search" placeholder="Filter By Part/Serial" value="<?=$searched;?>">
              		<span class="input-group-btn">
	                	<button class="btn btn-sm btn-primary part_filter"><i class="fa fa-filter"></i></button>              
	            	</span>
	            </div>
-->
			</div>
			
			<div class="col-md-2 col-sm-2">
<!--
				<div class="company input-group">
					<select name='companyid' id='companyid' class='form-control input-xs company-selector required' >
						<option value=''>Select a Company</option>
					</select>
					<span class="input-group-btn">
						<button class="btn btn-sm btn-primary inventory_filter"><i class="fa fa-filter"></i></button>   
					</span>
				</div>
-->
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
	<div class="row">
		<?php 
			output_module("p","Active");
			output_module("s","Active");
		?>
    </div>
	<div class="row">
		<?php 
			output_module("p","Complete");
			output_module("s","Complete");
		?>
    </div>    



<?php include_once 'inc/footer.php'; ?>
<script src="js/operations.js?id=<?php if (isset($V)) { echo $V; } ?>"></script>
<script>
	(function($){
		$('#item-updated-timer').delay(3000).fadeOut('fast');
	})(jQuery);
	
	$(document).ready(function() {
		//Triggering Aaron 2017
		var search = "<?=$_REQUEST['s']; ?>";
		window.history.replaceState(null, null, "/inventory.php?search=" + search);	
	});
</script>

</body>
</html>
