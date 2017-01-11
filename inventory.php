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



	$parts_array = array();
	
	$page = grab('page');
	
	($page == '' ? $page = 1: '');

	$offset = ($page - 1) * 5;
	
	$query  = "SELECT * FROM parts where id IN (SELECT partid FROM inventory) LIMIT " . res($offset) . ", 5;";
	$result = qdb($query);
	
	while ($row = $result->fetch_assoc()) {
		$parts_array[] = $row;
	}
	
	// Get Pages function determines how many pages the inventory should output.
	function getPages($show_num = '5',$results='') {
		//Find out what page number we are on.
		global $page;
		
		//Set a null counter for the number of rows
		$rows = 0;
		if (!$results){
		//Static query which gets all the parts from the inventory screen
		$query  = "SELECT COUNT(*) as rows FROM (SELECT DISTINCT  `partid` FROM  `inventory`) AS t1;";
		$result = mysqli_fetch_assoc(qdb($query));
		$results = $result['rows'];
	}
		$pages = ceil($results / $show_num);
		
		for($i = 1; $i <= $pages; $i++) {
			//echo $page;
			echo '<li class="' .($page == $i || ($page == '' && $i == 1) ? 'active':''). '"><a href="?page=' .$i. '">'.$i.'</a></li>';
		}
	}
	
	//this function works to gather a general manufacturer by ID
	function getManufacture($manfid = 0) {
		$manf;
		
		$query  = "SELECT * FROM manfs where id = " . res($manfid) . ";";
		$result = qdb($query);
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$manf = $result['name'];
		}
		
		return $manf;
	}
	
	function getSystemName($systemid = 0) {
		$system;
		
		if($system != 0) {
			$query  = "SELECT * FROM systems where id = " . res($systemid) . ";";
			$result = qdb($query);
			
			if (mysqli_num_rows($result)>0) {
				$result = mysqli_fetch_assoc($result);
				$system = $result['system'];
			}
		}
		
		return $systemid;
	}
	
	function getPartSerials($partid = 0) {
		$partSerial_array = array();
		
		$query  = "SELECT * FROM inventory where partid = " . res($partid) . " ORDER BY
				  CASE item_condition
				    WHEN 'new' THEN 1
				    WHEN 'used' THEN 2
				    ELSE 3
				  END, qty DESC;";
		$result = qdb($query);
		
		while ($row = $result->fetch_assoc()) {
			$partSerial_array[] = $row;
		}
		
		return $partSerial_array;
	}
	
	function getItemHistory($invid = 0) {
		$partHistory_array = array(); 
		
		$query  = "SELECT * FROM inventory_history WHERE invid =" . res($invid) . ";";
		$result = qdb($query);
		
		while ($row = $result->fetch_assoc()) {
			$partHistory_array[] = $row;
		}
		
		return $partHistory_array;
	}
	
	function getRepName($repid = 0) {
		$name;
		
		$query  = "SELECT name FROM contacts WHERE id =" . res($repid) .";";
		$result = qdb($query);
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$name = $result['name'];
		}
		
		return $name;
	}
	
	function getStatusStock($stock = '', $partid = 0) {
		$stockNumber;
		
		if($stock == 'pending') {
			$query  = "SELECT SUM(qty) FROM inventory WHERE partid =" . res($partid) . " AND (status = 'ordered');";
		} else if($stock == 'instock') {
			$query  = "SELECT SUM(qty) FROM inventory WHERE partid =" . res($partid) . " AND (status = 'received' OR status = 'shelved');";
		}
		$result = qdb($query);
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$stockNumber = $result['SUM(qty)'];
		}
		
		// while ($row = $result->fetch_assoc()) {
		// 	$stockNumber= $row['serial_no'];
		// }
		if(!$stockNumber) {
			$stockNumber = 0;
		}
		
		return $stockNumber;
	}
	
	//Get the past Purchase/Sales Order for each part in the inventory
	function getOrder($order_type,$partid = 0) {
		$order_array = array();
		$order_message = "";
		
		//Determine which order type to look for in this system
		$order_table = $order_type == 'po' ? 'purchase_items' : 'sales_items';
		$selector = $order_type == 'po' ? 'po_number' : 'so_number';
		
		//Order by the lastest order and limit the order # to 3 at a time
		$query = "SELECT ". res($selector) ." FROM " . res($order_table) . " WHERE partid = ". res($partid) ." ORDER BY ". res($selector) ." DESC LIMIT 3;";
		$result = qdb($query) or die(qe());
		
		while ($row = $result->fetch_assoc()) {
			$order_array[] = $row;
		}
		
		if(!empty($order_array)){
			//$order_message = strtoupper($order_type) . " Number Found";
			foreach($order_array as $item) {
				$order_message .= "<a href='order_form.php?on=$item[$selector]&ps=" . ($order_type == 'po' ? 'p' : 's') . "'>#" .$item[$selector] . "</a> ";
			}
		} else {
			//Else there is no record order number matched to this inventory addition
			//Assuming that in item was added manually
			$order_message = "No " . strtoupper($order_type) . " Orders Found";
		}
		
		return $order_message;
	}
	
	function getAvgPrice($partid) {
		$order_array = array();
		$avg = 0;
		$counter = 0;
		
		$query = "SELECT price FROM sales_items WHERE partid = ". res($partid) .";";
		$result = qdb($query) or die(qe());
		
		while ($row = $result->fetch_assoc()) {
			$order_array[] = $row;
		}
		
		foreach($order_array as $price){
			$avg += $price['price'];
			$counter++;
		}
		
		return $avg;
	}
	
	function getStock($stock = '', $partid = 0) {
		$stockNumber;
		
		//echo $stock . $partid;
		
		
		$query  = "SELECT SUM(qty) FROM inventory WHERE partid =" . res($partid) . " AND item_condition = '" . res($stock) . "';";
		$result = qdb($query);
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$stockNumber = $result['SUM(qty)'];
		}
		
		// while ($row = $result->fetch_assoc()) {
		// 	$stockNumber= $row['serial_no'];
		// }
		if(!$stockNumber) {
			$stockNumber = 0;
		}

		return $stockNumber;
	}
	
?>

<!----------------------------------------------------------------------------->
<!------------------------------- HEADER OUTPUT ------------------------------->
<!----------------------------------------------------------------------------->
<!DOCTYPE html>
<html>
<!-- Declaration of the standard head with Accounts home set as title -->
<head>
	<title>VMM Inventory</title>
	<?php
		//Standard headers included in the function
		include_once $rootdir.'/inc/scripts.php';
	?>
	<link rel="stylesheet" href="../css/operations-overrides.css" type="text/css" />
	<style>
		hr {
			margin-top: 0;
			margin-bottom: 10px;
		}
		
		tbody th {
			border-top-color: #edf2f7 !important;
		}
		
		.product-rows-edited .btn-primary {
		    /*color: #ffffff;*/
		    /*background-color: #5cb85c;*/
		    /*border-color: #4cae4c;*/
		}
		
		#item-updated, #item-failed {
			position: fixed;
		    width: 100%;
		    z-index: 1;
		}
		
		.serial-page {
			display: none;
		}
		
		.page-1 {
			display: block;
		}
		
		.addRows label {
			display: none;
		}
		
		.addRows .product-rows:first-child label {
			display: block;
		}
		
		@media screen and (max-width: 767px){
			.addRows label {
				display: block;
			}
		}
	</style>

</head>

<body class="sub-nav">
	
<!----------------------------------------------------------------------------->
<!------------------------- Output the navigation bar ------------------------->
<!----------------------------------------------------------------------------->

	<?php include 'inc/navbar.php'; ?>
	
	
<!----------------------------------------------------------------------------->
<!-------------------------- Header/Filter Bar/Title -------------------------->
<!----------------------------------------------------------------------------->
	<div class="table-header" style="width: 100%; min-height: 60px;">
		<div class="row" style="padding-top: 15px; margin: 0 10px;">
			<div class="col-md-2 col-sm-2" style="padding-bottom: 15px;">
				<div class="input-group">
	              <input type="text" class="form-control" id="part_search" placeholder="Filter By Part..." value=<?=grab("search")?>>
              		<span class="input-group-btn">
	                	<button class="btn btn-primary part_filter"><i class="fa fa-filter"></i></button>              
	            	</span>
	            </div>
			</div>
			<div class="col-md-2 col-sm-2" style="padding-bottom: 15px;">
				<div class="input-group">
	              <input type="text" class="form-control" id="serial_filter" placeholder="Search By Serial...">
              		<span class="input-group-btn">
	                	<button class="btn btn-primary serial_filter"><i class="fa fa-filter"></i></button>              
	            	</span>
	            </div>
			</div>
			<div class="col-md-1 col-sm-1" style="padding-bottom: 15px;">
				<input class="form-control" type="text" name="" placeholder="Location"/>
			</div>
			<div class="col-md-1 col-sm-1" style="padding-bottom: 15px;">
				<?php
					$status_selected = grab('status','shelved');
					echo dropdown('status',$status_selected,'','',false);
				?>
			</div>
			<div class="col-md-1 col-sm-1" style="padding-bottom: 15px;">
				<?php
					$condition_selected = grab('condition','');
					echo dropdown('condition',$condition_selected,'','',false);
				?>
			</div>
			<div class="col-md-2 col-sm-2" style="padding-bottom: 15px;">

				<div class="btn-group" role="group">
					<button class="btn btn-default active">In Stock</button>
					<button class="btn btn-default">Out Of Stock</button>
				</div>
			</div>
			<div class = "col-md-3">
				<div class="form-group col-md-4">
					<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY">
			            <input type="text" name="START_DATE" class="form-control input-sm" value="<?php echo $startDate; ?>">
			            <span class="input-group-addon">
			                <span class="fa fa-calendar"></span>
			            </span>
			        </div>
				</div>
				<div class="form-group col-md-4">
					<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY" data-maxdate="<?php echo date("m/d/Y"); ?>">
			            <input type="text" name="END_DATE" class="form-control input-sm" value="<?php echo $endDate; ?>">
			            <span class="input-group-addon">
			                <span class="fa fa-calendar"></span>
			            </span>
				    </div>
			</div>
				<div class="form-group col-md-4">
						<div class="btn-group" id="dateRanges">
							<div id="btn-range-options">
								<button class="btn btn-default btn-sm">&gt;</button>
								<div class="animated fadeIn hidden" id="date-ranges">
							        <button class="btn btn-sm btn-default left large btn-report" type="button" data-start="<?php echo date("m/01/Y"); ?>" data-end="<?php echo date("m/d/Y"); ?>">MTD</button>
					    			<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("01/01/Y"); ?>" data-end="<?php echo date("03/31/Y"); ?>">Q1</button>
									<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("04/01/Y"); ?>" data-end="<?php echo date("06/30/Y"); ?>">Q2</button>
									<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("07/01/Y"); ?>" data-end="<?php echo date("09/30/Y"); ?>">Q3</button>		
									<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("10/01/Y"); ?>" data-end="<?php echo date("12/31/Y"); ?>">Q4</button>	
									<?php
										for ($m=1; $m<=5; $m++) {
											$month = format_date($today,'M m/t/Y',array('m'=>-$m));
											$mfields = explode(' ',$month);
											$month_name = $mfields[0];
											$mcomps = explode('/',$mfields[1]);
											$MM = $mcomps[0];
											$DD = $mcomps[1];
											$YYYY = $mcomps[2];
											echo '
																	<button class="btn btn-sm btn-default right small btn-report" type="button" data-start="'.date($MM."/01/".$YYYY).'" data-end="'.date($MM."/".$DD."/".$YYYY).'">'.$month_name.'</button>
											';
										}
									?>
								</div><!-- animated fadeIn -->
							</div><!-- btn-range-options -->
						</div><!-- btn-group -->
			</div><!-- form-group -->
			</div>
		</div>
	</div>

<!---------------------------------------------------------------------------->
<!------------------------------ Alerts Section ------------------------------>
<!---------------------------------------------------------------------------->

	<div id="item-updated" class="alert alert-success fade in text-center" style="display: none;">
	    <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
	    <strong>Success!</strong> Changes have been updated.
	</div>
	
	<div id="item-failed" class="alert alert-danger fade in text-center" style="display: none;">
	    <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
	    <strong>Uh Oh!</strong> Something went wrong with the update, please look into a fix for this error.
	</div>


	
<!----------------------------------------------------------------------------->
<!---------------------------------- Body Out --------------------------------->
	
	<div class="loading_element_listing" style="display: none;">
		<div class='col-sm-12' style='padding-top: 20px'>
			<select class='revisions' multiple>
				
			</select>
			<img class='img-responsive' src='http://placehold.it/125x75' style='padding-right: 10px; float:left; padding-bottom: 10px;'>
		</div>
		<div class='col-sm-12'>
			<div class='table-responsive'>
				<table class='shipping_update table table-hover table-condensed' style='margin-top: 15px;'>
					<thead>
						<tr>
							<th>Location</th>
							<th>Qty</th>
							<th>Condition</th>
							<th>Vendor</th>
							<th>Puchase Order</th>
							<th>Date Added</th>
						</tr>
					</thead>
					<tbody class='parts'>
						
					</tbody>
				</table>
			</div>
		</div>
	</div>

	

<?php include_once 'inc/footer.php'; ?>
<script src="js/operations.js"></script>

<script>
	(function($){
		
		$(document).on('change', '.revisions', function() {
			$('.parts-list').hide();
			
			if($(this).val() == '') {
				$('.parts-list').show();
			} else {
				$('.' + $(this).val()).show();
			}
		});
		
		// //Show more data for a specific product, Serial etc
		// $(document).on("click",".buttonAdd",function(){
		// 	$(this).closest('.part-container').children('.addItem').slideToggle('fast');
		// 	$(this).children('').toggleClass('fa-chevron-down fa-chevron-up');
		// });
		
		// //Drop down a list of the history of the specific item
		// $(document).on("click",".show_history",function(e){
		// 	e.preventDefault();
		// 	$(this).next('.history_listing').slideToggle();
		// });
		
		
		// $(document).on("click",'.updateAll, .update',function(e) {
		// 	var serial, date, location, qty, condition, status, cost, id, partid;
		// 	//run through each of the rows that pertains to the class stated below and grab all the data
		// 	$(this).closest('.addItem').find('.product-rows-edited').each(function () {
		// 		//Declare Variables
		// 		var element = this;
				
		// 		//Add value to each variable depending on the data in the row
		// 		id = $(this).data('id');
		// 		serial = $(this).find('.serial').val();
		// 		date = $(this).find('.date').val();
		// 		location = $(this).find('.location').val();
		// 		qty = $(this).find('.qty').val();
		// 		condition = $(this).find('.condition').val();
		// 		status = $(this).find('.status').val();
		// 		cost = $(this).find('.cost').val();
				
		// 		//get the specific part id based on position of the element
		// 		partid = $(element).closest('.part-container').data('partid');

		// 		$.ajax({
		// 			type: 'POST',
		// 			url: '/json/inventory-edit.php',
		// 			data: ({id : id, serial_no : serial, date_created: date, locationid: location, qty : qty, condition : condition, status : status, cost : cost, partid : partid}),
		// 			dataType: 'json',
		// 			success: function(data) {
		// 				if(data.result){
		// 					$(element).closest('.part-container').find('.partDescription').find('.new_stock').html(data.new_stock);
		// 					$(element).closest('.part-container').find('.partDescription').find('.used_stock').html(data.used_stock);
		// 					$(element).closest('.part-container').find('.partDescription').find('.refurb_stock').html(data.refurb_stock);
							
		// 					$(element).closest('.addItem').find('.product-rows-edited').find('.update').prop("disabled", true);
		// 					$(element).closest('.addItem').find('.product-rows-edited').removeClass('product-rows-edited');
							
		// 					$('#item-updated').show();
		// 					setTimeout(function() { 
		// 						$('#item-updated').fadeOut(); 
		// 					}, 5000);

		// 				} else {
		// 					$('#item-failed').show();
		// 					setTimeout(function() { 
		// 						$('#item-failed').fadeOut(); 
		// 					}, 5000);
		// 				}
		// 			}
		// 		});
				
		// 	});
			
		// });
		
		$(document).on('change keyup paste','input, select', function() {
			$(this).closest('.addItem').find('.updateAll').prop("disabled", false);
			$(this).closest('.product-rows').addClass('product-rows-edited');
			$(this).closest('.product-rows').find('.update').prop("disabled", false);
		});
		
		//$('.update').click(function () {
			// $($(this).closest('.product-rows').find('.serial').next('.form-text')).html($(this).closest('.product-rows').find('.serial').val());
			// $(this).closest('.product-rows').find('.serial').hide();
			
			// $($(this).closest('.product-rows').find('.date').next('.form-text')).html($(this).closest('.product-rows').find('.date').val());
			// $(this).closest('.product-rows').find('.date').hide();
			
			// $($(this).closest('.product-rows').find('.location').next('.form-text')).html($(this).closest('.product-rows').find('.location').val());
			// $(this).closest('.product-rows').find('.location').hide();
			
			// $($(this).closest('.product-rows').find('.qty').next('.form-text')).html($(this).closest('.product-rows').find('.qty').val());
			// $(this).closest('.product-rows').find('.qty').hide();
			
			// $($(this).closest('.product-rows').find('.condition').next('.form-text')).html($(this).closest('.product-rows').find('.condition').val());
			// $(this).closest('.product-rows').find('.condition').hide();
			
			// $($(this).closest('.product-rows').find('.status').next('.form-text')).html($(this).closest('.product-rows').find('.status').val());
			// $(this).closest('.product-rows').find('.status').hide();
			
			// $($(this).closest('.product-rows').find('.cost').next('.form-text')).html($(this).closest('.product-rows').find('.cost').val());
			// $(this).closest('.product-rows').find('.cost').hide();
			// alert($(this).closest('.product-rows').find('.serial').val());
		//});
		
		//Append new row of data
		var element = '<div class="product-rows row new-row appended" style="padding-bottom: 10px; display: none;">\
				<div class="col-md-2 col-sm-2">\
					<label for="serial">Serial/Lot Number</label>\
					<input class="form-control serial" type="text" name="serial" placeholder="#123" value=""/>\
				</div>\
				<div class="col-md-2 col-sm-2">\
					<label for="date">Date</label>\
					<input class="form-control date" type="text" name="date" placeholder="00/00/0000" value="<?php echo date("n/j/Y");  ?>"/>\
				</div>\
				<div class="col-md-2 col-sm-2">\
					<label for="date">Location</label>\
					<input class="form-control location" type="text" name="date" placeholder="Warehouse Location" value=""/>\
				</div>\
				<div class="col-md-1 col-sm-1">\
					<label for="qty">Qty</label>\
					<input class="form-control qty" type="text" name="qty" placeholder="Quantity" value=""/>\
				</div>\
				<div class="col-md-2 col-sm-2">\
					<div class="form-group">\
						<label for="condition">Condition</label>\
						<select class="form-control condition" name="condition">\
							<?php foreach(getEnumValue() as $condition): ?>
								<option><?php echo $condition; ?></option>\
							<?php endforeach; ?>
						</select>\
					</div>\
					<div class="form-text"></div>\
				</div>\
				<div class="col-md-1 col-sm-1">\
					<div class="form-group">\
						<label for="status">status</label>\
						<select class="form-control status" name="status">\
							<?php foreach(getEnumValue('inventory', 'status') as $status): ?>
								<option><?php echo $status; ?></option>\
							<?php endforeach; ?>
						</select>\
					</div>\
					<div class="form-text"></div>\
				</div>\
				<div class="col-md-2 col-sm-2">\
					<div class="col-md-7 col-sm-7">\
						<div class="row">\
							<label for="price">Cost</label>\
							<input class="form-control cost" type="text" name="price" placeholder="$$$" value=""/>\
						</div>\
					</div>\
					<div class="col-md-5 col-sm-5">\
						<div class="btn-group" role="group" style="margin: 23px auto 0; display: block;">\
							<button class="btn btn-primary btn-sm inserted-row"><i class="fa fa-check" aria-hidden="true"></i></button>\
							<button class="btn btn-danger delete btn-sm"><i class="fa fa-chevron-up" aria-hidden="true"></i></button>\
						</div>\
					</div>\
				</div>\
			</div>';
		
		//Once button is clicked the new row will be appended
		$(document).on("click",".buttonAddRows",function(){
			$(this).closest('.part-container').find('.product-rows:last').after(element);
			$(this).closest('.part-container').find('.appended').slideDown().removeClass('appended');
			
			$('.delete').click(function(){
				$($(this).closest('.new-row')).slideUp("normal", function() { $(this).remove(); });
			});
			
			$('.inserted-row').click(function(){
				var serial, date, location, qty, condition, status, cost, partid;
				var element = $(this).closest('.product-rows');
				
				id = "";
				serial = $(element).find('.serial').val();
				date = $(element).find('.date').val();
				location = $(element).find('.location').val();
				qty = $(element).find('.qty').val();
				condition = $(element).find('.condition').val();
				status = $(element).find('.status').val();
				cost = $(element).find('.cost').val();
				
				partid = $(element).closest('.part-container').data('partid');

				$.ajax({
					type: 'POST',
					url: '/json/inventory-edit.php',
					data: ({id : id, serial_no : serial, date_created: date, locationid: location, qty : qty, condition : condition, status : status, cost : cost, partid : partid}),
					dataType: 'json',
					success: function(data) {
						if(data.result){
							$(element).closest('.part-container').find('.partDescription').find('.new_stock').html(data.new_stock);
							$(element).closest('.part-container').find('.partDescription').find('.used_stock').html(data.used_stock);
							$(element).closest('.part-container').find('.partDescription').find('.refurb_stock').html(data.refurb_stock);
							
							$(element).closest('.product-rows').find('.inserted-row').prop("disabled", true);
							$(element).closest('.product-rows').find('.delete').prop("disabled", true);
							
							$('#item-updated').show();
							setTimeout(function() { 
								$('#item-updated').fadeOut(); 
							}, 5000);

						} else {
							$('#item-failed').show();
							setTimeout(function() { 
								$('#item-failed').fadeOut(); 
							}, 5000);
						}
					}
				});
			});
		});
		
		
		//Remove rows
		$(document).on("click",".delete",function(){
			$($(this).closest('.new-row')).slideUp("normal", function() { $(this).remove(); });
		});
		
		//Show hide serial products
		$(document).on("click",".show-more",function(e){
	
			e.preventDefault();
			$(this).closest('.addItem').find('.page-2').slideToggle();
			
			$(this).closest('.addItem').find('.page-2').toggleClass('show-less');
			
		});
		

		//Update all query
		// $('.updateAll').click(function() {
		// 	//Get how many rows created + initial row
		// 	var totalRows = $('.product-rows').length;
		// 	var results = new Array();
		// 	$('.product-rows').each(function() {
				
		// 	});
		// });
//===============================================================================
	// var serial_history = function(){
	// 	$.ajax({
	// 		type: "POST",
	// 		url: '/json/item_history.php',
	// 		data: {
	// 			"inventory": 1,
	// 			},
	// 		dataType: 'json',
	// 		success: function(part) {
	// 			var output = "";
	// 			alert('sub');
	// 			 part.forEach(function(item){
	// 				 output += "\
	// 					<tr>\
	// 						<td>"+item["Date"]+"</td>\
	// 						<td>"+item["Rep"]+"</td>\
	// 						<td>"+item["Field"]+"</td>\
	// 						<td>"+item["History"]+"</td>\
	// 					</tr>\
	// 				";
	// 			});

	// 		},
	// 		error: function(xhr, status, error) {
	// 			   	alert(error+" | "+status+" | "+xhr);
	// 		},			
	// 	});

	// }
	var inventory_history = function (search, serial) {
		$.ajax({
				type: "POST",
				url: '/json/inventory-out.php',
				data: {
					"search": search,
					"serial": serial
					},
				dataType: 'json',
				success: function(part) {
					$(".revisions").empty();
					$(".parts").empty();
					
					$(".part-container").html("").remove();	
					// var p = JSON.parse(part)
					console.log(part);
					var revisions, parts;
					
					var counter = 1;
					
					revisions = "<option value='' selected>All</option>";
					//Loop through each record of ID
					part.forEach(function(item, info){
						
						revisions += "<option value='parts-"+counter+"'>"+item.part+"</option>";
						
						//Begin the item level transverse
							var locs = item.locations;
							$.each(locs, function(loc,arr){
								var i = 1;
										parts += "<tr class='parts-list parts-"+counter+"' data-serial= 'serial_listing_"+i + "-" + item.id +"'>\
												<td>"+arr.display+"</td>\
												<td><span class='check_serials' style='color: #428bca; cursor: pointer;'>"+arr.sumqty+"</span></td>\
												<td>"+i+"</td>";
										//Append the item history
										parts += "<td>";
												
												$.each(item.po_history, function(history, info){
													parts += info.vendor+": "+info.number+" &nbsp;";
												});
										parts += "</td>";
										parts += "<td>";
								// $.each(locs.serial)
			
										parts += "</td>";
												//item.po_history+"</td>\
										parts += "<td ='date_added'>"+item.date+"</td>\
											</tr>";

								// $.each(traverse.loc, function(i){
								// 	alert (i);
								// });

							// 	//Handler for all the serials per part
								parts += "<tr class='serial_listing_"+ i + '-' + item.id +"' style='display: none;'>\
											<td colspan='12'>\
												<div class='table-responsive'>\
													<table class='shipping_update table table-hover table-condensed'>\
														<thead>\
															<tr>\
																<th>Serial</th>\
																<th>Location</th>\
																<th>Qty</th>\
																<th>Date Added</th>\
															</tr>\
														</thead>\
														<tbody>";
														//For each related serial, output a row in the subtable
														$.each(arr.serial, function(serial,s_info){
															if(serial.condition == i) {
																parts += "\
																		<tr>\
																			<td>"+serial.serial_no+"</td>\
																			<td>"+serial.location+"</td>\
																			<td>"+serial.qty+"</td>\
																			<td ='date_added'>"+serial.date+"</td>\
																		</tr>";
															}
														});
														
								parts +=				"</tbody>\
													</table>\
												</div>\
											</td>\
										</tr>";
						counter++;
						
						}); //END OF LOCATION LOOP
					}); //END OF ITEM LOOP
					$('.revisions').append(revisions);
					$('.parts').append(parts);
					
					// //Changing the outout to a table
					// output = "\
					// 	<div class='col-sm-12' style='padding-top: 20px'>\
					// 		<strong class='part_name'>"+"</strong>\
					// 		<select>";
					// 		part.forEach(function(item){
					// 			output += "<option>Test</option>";
					// 		});
					// output +=	"</select>\
					// 		<br>\
					// 		<img class='img-responsive' src='http://placehold.it/125x75' style='padding-right: 10px; float:left; padding-bottom: 10px'>\
					// 	</div>\
					// 	<div class='col-sm-12'>\
					// 		<div class='table-responsive'>\
					// 			<table class='shipping_update table table-hover table-striped table-condensed' style='margin-top: 15px;'>\
					// 				<thead>\
					// 					<tr>\
					// 						<th>Vendor</th>\
					// 						<th>Location</th>\
					// 						<th>Qty</th>\
					// 						<th>Condition</th>\
					// 						<th>Puchase Order</th>\
					// 						<th>Date Added</th>\
					// 					</tr>\
					// 				</thead>\
					// 				<tbody>";
					// //Function to parse through all the serials
					// var lastSerial = '';
					// item.serials.forEach(function(serial){				
					// 	output +=			"<tr>\
					// 							<td>Vendor</td>\
					// 							<td>Location</td>\
					// 							<td>"+serial.qty+"</td>\
					// 							<td>"+serial.condition+"</td>\
					// 							<td>"+item.po_history+"</td>\
					// 							<td>"+serial.date+"</td>\
					// 						</tr>";
					// });	
					// output +=		"</tbody>\
					// 			</table>\
					// 		</div>\
					// 	</div>\
					// ";
					if(part != '') {
						$(".loading_element_listing").show();
					} else {
						$(".loading_element_listing").hide();
				  		alert("No Parts Found with those parameters");
					}
				},
				error: function(xhr, status, error) {
					$(".loading_element_listing").hide();
					alert(error);
				   	alert("No Parts Found with those parameters");
				},			
		});
	}
	$(document).ready(function() {
		if($("#part_search").val()){
			var search = $("#part_search").val();
			inventory_history(search,"");
		}
	});
	
	//This function show all the serial if the user clicks on the qty link
	$(document).on('click', '.check_serials', function(e) {
		e.preventDefault
		
		var parent = $(this).closest('.parts-list').data('serial');
		//alert($(this).text());
		
		$('.' + parent).toggle();
	});
	
	$(document).on("click",".part_filter",function(){
		var search = $("#part_search").val();
		if (search){
			inventory_history(search,"");
		}
	});
	$(document).on("click",".serial_filter",function(){
		var serial = $("#serial_filter").val();
		alert(serial)
		if (serial){
			inventory_history("",serial);
		}
	});
	
	
	$("#part_search").on("keyup",function(e){
		if (e.keyCode == 13) {
			var search = $("#part_search").val();
			inventory_history(search,"");
		}
	});
	$("#serial_filter").on("keyup",function(e){
		if (e.keyCode == 13) {
			var serial = $("#serial_filter").val();
			inventory_history("",serial);
		}
	});
	 
	 
	
	})(jQuery);

</script>

</body>
</html>
