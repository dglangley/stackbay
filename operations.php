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
	
//==============================================================================
//================== Function Delcaration (Declaration?) =======================
//==============================================================================
	
	//Output Module acts as the general output for each of the dashboard sections.
	//	INPUTS: Order(p,s);  Status(Active,Complete)
	
	$po_updated = $_REQUEST['po'];
	$so_updated = $_REQUEST['so'];
	
	function params_array($type){

		$info = array();
		if($type == "p"){
			$info['display'] = "Purchase";
			$info['tables'] = " purchase_orders o, purchase_items i WHERE o.po_number = i.po_number ";
			$info['short'] = "po";
			$info['active'] = " AND (CAST(i.qty AS SIGNED) - CAST(i.qty_received AS SIGNED)) > 0 ";
			$info['inactive'] = " AND (CAST(i.qty AS SIGNED) - CAST(i.qty_received AS SIGNED)) <= 0 ";
			$info['url'] = "inventory_add"
		}
		else if ($type == "s"){
			$info['display'] = "Sales";
			$info['tables'] = " sales_orders o, sales_items i WHERE o.so_number = i.so_number ";
			$info['short'] = "po";
			$info['active'] = " AND i.ship_date IS NULL ";
			$info['inactive'] = " AND i.ship_date IS NOT NULL  ";
			$info['url'] = "shipping"
		}
		else if ($type == "rma"){
			$info['display'] = "RMA";
			$info['tables'] = " sales_orders o, sales_items i WHERE o.so_number = i.so_number ";
			$info['short'] = "po";
			$info['active'] = " AND i.ship_date IS NULL ";
			$info['inactive'] = " AND i.ship_date IS NOT NULL  ";
			$info['url'] = "inventory_add"
		}
		else{
				$info['case'] = $type;
		}
		return $info;
	}
	
	function output_module($type,$status){
		
		// $status_out = ($status =="Active") ? 'Outstanding ' : "Completed ";
		// $order_out = ($order =="p") ? "Purchase" : "Sales";
		$i = params_array($type);
		echo"
			<div class='col-lg-6 pad-wrapper' style='margin: 25px 0;'>
			<div class='shipping-dash'>

				<div class='shipping_section_head' data-title='".$i['display']." Orders'>";
		echo $i['display'].' Orders';
		// echo "<a href = '/order_form.php?ps=$order_out' ><div class = 'btn btn-sm btn-standard pull-right' style = 'color:white;margin-top:-5px;display:block;'>
		// <i class='fa fa-plus'></i> 
		// </div></a>";
		echo	'</div>
				<div class="table-responsive">
		            <table class="table heighthover heightstriped table-condensed">';
		            output_header($i,$status);
		echo	'<tbody>';
            		output_rows($i,$status);
		echo '	</tbody>
		            </table>
		    	</div>
		    	<div class="col-md-12 text-center shipping_section_foot more" style="padding-bottom: 15px;">
	            	<a href="#">Show more</a>
	            </div>
            </div>
        </div>';
	}
	
	function output_header($info, $status){
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
	function output_rows($info, $status){
		
		$i = $info;
		//Select a joint summary query of the order we are requesting
		$query = "SELECT * FROM ".$i['tables'];
		// $query .= ($status == "Active")? $i['active'] : $i['inactive'];
		$query .= " LIMIT 0,100;";
		
		// if ($order == 'p'){
		// 	$query .= "purchase_orders o, purchase_items i ";
		// 	$query .= "WHERE o.po_number = i.po_number ";
		// 	if($status == 'Active') {
		// 		$query .= "AND (CAST(i.qty AS SIGNED) - CAST(i.qty_received AS SIGNED)) > 0 ";
		// 	} else {
		// 		$query .= "AND (CAST(i.qty AS SIGNED) - CAST(i.qty_received AS SIGNED)) <= 0 ";
		// 	}
		// 	//$query .= "AND status = '" . res($status) . "' ";
		// 	$query .= "ORDER BY o.po_number DESC LIMIT 0 , 100;";
		// }
		// else{
		// 	$query .= "sales_orders o, sales_items i ";
		// 	$query .= "WHERE o.so_number = i.so_number ";
		// 	if($status == 'Active') {
		// 		$query .= "AND i.ship_date IS NULL ";
		// 		$query .= "ORDER BY o.so_number DESC LIMIT 0 , 100;";
		// 	} else {
		// 		$query .= "AND i.ship_date IS NOT NULL ";
		// 		$query .= "ORDER BY i.ship_date DESC LIMIT 0 , 100;";
		// 	}
		// }
		
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
		
		/*.select2-container {*/
		/*    width: 90% !important;*/
		/*}*/
		
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
			<div class="col-md-1">
			    <div class="btn-group">
			        <button class="glow left large btn-report <?=($report_type=='summary')? ' active' : ''?>" type="submit" data-value="summary">
			        	<i class="fa fa-sort-numeric-desc"></i>	
			        </button>
					<input type="radio" name="report_type" value="summary" class="hidden"<?=($report_type=='summary')? ' checked' : ''?>>
			        <button class="glow center large btn-report<?=($report_type=='detail')? ' active' : '' ?>" type="submit" data-value="detail">
			        	<i class="fa fa-history"></i>	
			        </button>
			        <input type="radio" name="report_type" value="detail" class="hidden"<?php if ($report_type=='detail') { echo ' checked'; } ?>>
					<button class="glow right large btn-report<?=($report_type=='all')? ' active' : '' ?>" type="submit" data-value="detail">
			        	All<!--<i class="fa fa-history"></i>	-->
			        </button>
			        <input type="radio" name="report_type" value="detail" class="hidden"<?php if ($report_type=='detail') { echo ' checked'; } ?>>
			    </div>
			</div>
			<div class = "col-md-3">
				<div class="form-group col-md-4 nopadding">
					<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY">
			            <input type="text" name="START_DATE" class="form-control input-sm" value="<?php echo $startDate; ?>">
			            <span class="input-group-addon">
			                <span class="fa fa-calendar"></span>
			            </span>
			        </div>
				</div>
				<div class="form-group col-md-4 nopadding">
					<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY" data-maxdate="<?php echo date("m/d/Y"); ?>">
			            <input type="text" name="END_DATE" class="form-control input-sm" value="<?php echo $endDate; ?>">
			            <span class="input-group-addon">
			                <span class="fa fa-calendar"></span>
			            </span>
				    </div>
				</div>
				<div class="form-group col-md-4 nopadding">
					<div class="btn-group" id="dateRanges">
						<div id="btn-range-options">
							<button class="btn btn-default btn-sm">&gt;</button>
							<div class="animated fadeIn hidden" id="date-ranges" style = 'width:217px;'>
						        <button class="btn btn-sm btn-default left large btn-report" type="button" data-start="<?php echo date("m/01/Y"); ?>" data-end="<?php echo date("m/d/Y"); ?>">MTD</button>
				    			<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("01/01/Y"); ?>" data-end="<?php echo date("03/31/Y"); ?>">Q1</button>
								<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("04/01/Y"); ?>" data-end="<?php echo date("06/30/Y"); ?>">Q2</button>
								<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("07/01/Y"); ?>" data-end="<?php echo date("09/30/Y"); ?>">Q3</button>
								<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("10/01/Y"); ?>" data-end="<?php echo date("12/31/Y"); ?>">Q4</button>
							</div><!-- animated fadeIn -->
						</div><!-- btn-range-options -->
					</div><!-- btn-group -->
				</div><!-- form-group -->
			</div>
			<div class="col-md-4 col-sm-4 text-center">
            	<h2 class="minimal" id="filter-title">Operations Dashboard</h2>
			</div>
			
			<!--This Handles the Search Bar-->
			<div class="col-md-2 col-sm-2">

				<div class="input-group">
	              <input type="text" class="form-control input-sm" id="part_search" placeholder="Filter By Part/Serial" value="<?=$searched;?>">
              		<span class="input-group-btn">
	                	<button class="btn btn-sm btn-primary part_filter"><i class="fa fa-filter"></i></button>              
	            	</span>
	            </div>

			</div>
			
			<div class="col-md-2 col-sm-2">

				<div class="company input-group">
					<select name='companyid' id='companyid' class='form-control input-xs company-selector required' >
						<option value=''>Select a Company</option>
					</select>
					<span class="input-group-btn">
						<button class="btn btn-sm btn-primary inventory_filter"><i class="fa fa-filter"></i></button>   
					</span>
				</div>

			</div>
		</div>
	</div>
	
	<?php if($po_updated || $so_updated): ?>
		<div id="item-updated-timer" class="alert alert-success fade in text-center" style="position: fixed; width: 100%; z-index: 9999; top: 48px;">
		    <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
		    <strong>Success!</strong> <?php echo ($po_updated ? 'Purchase' : 'Sales'); ?> Order Updated.
		</div>
	<?php endif; ?>
	
	<div class="row">
		<?php 
			output_module("p");
			output_module("s");
		?>
    </div>
	<div class="row">
		<?php 
			output_module("rma");
			output_module("s");
		?>
    </div>    



<?php include_once 'inc/footer.php'; ?>
<script src="js/operations.js?id=<?php if (isset($V)) { echo $V; } ?>"></script>
<script>
	(function($){
		$('#item-updated-timer').delay(3000).fadeOut('fast');
	})(jQuery);
</script>

</body>
</html>
