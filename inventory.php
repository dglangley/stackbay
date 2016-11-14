<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	
	require_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/getPipeIds.php';
	
	$parts_array = array();
	
	$page = $_GET['page'];
	
	($page == '' ? $page = 1: '');
	
	$offset = ($page - 1) * 2;
	
	$query  = "SELECT * FROM parts where id IN (SELECT partid FROM inventory) LIMIT " . res($offset) . ", 2;";
	$result = qdb($query);
	
	while ($row = $result->fetch_assoc()) {
		$parts_array[] = $row;
	}
	
	function getPages() {
		global $page;
		
		$rows = 0;
		$query  = "SELECT * FROM parts where id IN (SELECT partid FROM inventory);";
		$result = qdb($query);
		
		while ($row = $result->fetch_assoc()) {
			$rows++;
		}
		$pages = ceil($rows / 2);
		for($i = 1; $i <= $pages; $i++) {
			echo '<li class="' .($page == $i || ($page == '' && $i == 1) ? 'active':''). '"><a href="?page=' .$i. '">'.$i.'</a></li>';
		}
	}
	
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
		
		$query  = "SELECT * FROM systems where id = " . res($systemid) . ";";
		$result = qdb($query);
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$system = $result['system'];
		}
		
		return $system;
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
	
	function getStock($stock = '', $partid = 0) {
		$stockNumber = 0;
		
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
	
	function getEnumValue( $table = 'inventory', $field = 'item_condition' ) {
		$statusVals;
		
	    $query = "SHOW COLUMNS FROM {$table} WHERE Field = '" . res($field) ."';";
	    $result = qdb($query);
	    
	    if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$statusVals = $result;
		}
		
		preg_match("/^enum\(\'(.*)\'\)$/", $statusVals['Type'], $matches);
		
		$enum = explode("','", $matches[1]);
		
		return $enum;
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
		
		.loading_element {
			visibility: hidden;
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
			<div class="col-md-5 col-sm-5" style="padding-bottom: 15px;">
				<input class="form-control" type="text" name="" placeholder="Part"/>
			</div>
			<div class="col-md-1 col-sm-1" style="padding-bottom: 15px;">
				<input class="form-control" type="text" name="" placeholder="Date"/>
			</div>
			<div class="col-md-1 col-sm-1" style="padding-bottom: 15px;">
				<input class="form-control" type="text" name="" placeholder="Location"/>
			</div>
			<div class="col-md-1 col-sm-1" style="padding-bottom: 15px;">
				<input class="form-control" type="text" name="" placeholder="Status"/>
			</div>
			<div class="col-md-1 col-sm-1" style="padding-bottom: 15px;">
				<input class="form-control" type="text" name="" placeholder="Condition"/>
			</div>
			<div class="col-md-3 col-sm-3" style="padding-bottom: 15px;">
				Toggles:
				<div class="btn-group" role="group">
					<button class="btn btn-default active">Up</button>
					<button class="btn btn-default">Down</button>
				</div>
				
				<div class="btn-group" role="group">  
					<button class="btn btn-default active">MVP</button>
					<button class="btn btn-default">...</button>
				</div>
			</div>
		</div>
	</div>
	
	<div id="item-updated" class="alert alert-success fade in text-center" style="display: none;">
	    <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
	    <strong>Success!</strong> Changes have been updated.
	</div>
	
	<div id="item-failed" class="alert alert-danger fade in text-center" style="display: none;">
	    <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
	    <strong>Uh Oh!</strong> Something went wrong with the update, please look into a fix for this error.
	</div>
	
	<div class="loading_element">
		<?php foreach($parts_array as $part): ?>
			<div class="part-container" data-partid="<?php echo $part['id']; ?>">
				<div class="row partDescription" style="margin: 35px 0 0 0;">
					<div class="col-md-2 col-sm-2">
						<div class="row" style="margin: 0">
							<div class="col-md-2 col-sm-2 col-xs-2">
								<button class="btn btn-success buttonAdd" style="margin-top: 24px;"><i class="fa fa-plus" aria-hidden="true"></i></button>
							</div>
							<div class="col-md-10 col-sm-10 col-xs-10">
								<img class="img-responsive" src="http://placehold.it/350x150">
							</div>
						</div>
					</div>
					<div class="col-md-3 col-sm-3">
						<strong><?php echo getSystemName($part['systemid']); ?> - <?php echo $part['part']; ?></strong>
						<hr>
						Description, <?php echo getManufacture($part['manfid']); ?><br><br>
						<i>Alias: David, Aaron, Andrew</i>
					</div>
					<div class="col-md-2 col-sm-2">
						<strong>Order History</strong>
						<hr>
						<span title="Purchase Order" style="text-decoration: underline;">PO</span>: <a href="">#123</a>, <a href="">#234</a><br>
						<span title="Sales Order" style="text-decoration: underline;">SO</span>: <a href="">#111</a>, <a href="">#222</a>
					</div>
					<div class="col-md-2 col-sm-2">
						<strong>Status</strong>
						<hr>
						<button title="In-stock" class="btn btn-danger">1</button>
						<button title="Sold" class="btn btn-success">2</button>
						<button title="Market" class="btn btn-primary">3</button>
					</div>
					<div class="col-md-2 col-sm-2">
						<strong>Condition</strong>
						<hr>
						<button title="New" class="btn btn-success new_stock"><?php echo getStock('new', $part['id']); ?></button>
						<button title="Used" class="btn btn-primary used_stock"><?php echo getStock('used', $part['id']); ?></button>
						<button title="Refurbished" class="btn btn-danger refurb_stock"><?php echo getStock('refurbished', $part['id']); ?></button>
					</div>
					<div class="col-md-1 col-sm-1">
						<strong>Cost Avg.</strong>
						<hr>
						$1,000 - 1,500
					</div>
				</div>
			
				<div class="row addItem" style="margin-top: 60px; margin-left: 0; margin-right: 0; border: 1px solid #E7E7E7; padding: 20px; display: none;">
					<div class="row">
						<div class="col-md-12 col-sm-12">
							<button class="btn btn-success buttonAddRows btn-sm add pull-right" style="margin-right: 5px;"><i class="fa fa-plus" aria-hidden="true"></i></button>
							<button class="btn btn-warning btn-sm add pull-right updateAll" style="margin-right: 5px;" disabled>Save Changes</button>
							<h3><?php echo getSystemName($part['systemid']); ?> - <?php echo $part['part']; ?></h3>
							<p style="">Description Manufacture <i>Alias: David, Aaron, Andrew</i></p>
						</div>
					</div>
					
					<hr>
					<div class="addRows">
						<?php $parts = getPartSerials($part['id']); $element = 0; $page = 1; foreach($parts as $serial): (($element % 5) == 0 && $element != 0 ? $page++ : ''); $element++; ?>
							<div class="product-rows serial-page page-<?php echo $page; ?>" style="padding-bottom: 10px;" data-id="<?php echo $serial['id']; ?>">
								<div class="row">
								<div class="col-md-2 col-sm-2">
									<label for="serial">Serial/Lot Number</label>
									<input class="form-control serial" type="text" name="serial" placeholder="#123" value="<?php echo $serial['serial_no']; ?>"/>
									<div class="form-text"></div>
								</div>
								<div class="col-md-2 col-sm-2">
									<label for="date">Date</label>
									<input class="form-control date" type="text" name="date" placeholder="00/00/0000" value="<?php echo date_format(date_create($serial['date_created']), 'm/d/Y'); ?>"/>
									<div class="form-text"></div>
								</div>
								<div class="col-md-2 col-sm-2">
									<label for="date">Location</label>
									<input class="form-control location" type="text" name="date" placeholder="Warehouse Location" value="<?php echo $serial['locationid']; ?>"/>
									<div class="form-text"></div>
								</div>
								<div class="col-md-1 col-sm-1">
									<label for="qty">Qty</label>
									<input class="form-control qty" type="text" name="qty" placeholder="Quantity" value="<?php echo $serial['qty']; ?>"/>
									<div class="form-text"></div>
								</div>
								<div class="col-md-2 col-sm-2">
									<div class="form-group">
										<label for="condition">Condition</label>
										<select class="form-control condition" name="condition">
											<?php foreach(getEnumValue() as $condition): ?>
												<option <?php echo ($condition == $serial['item_condition'] ? 'selected' : '') ?>><?php echo $condition; ?></option>
											<?php endforeach; ?>
										</select>
									</div>
									<div class="form-text"></div>
								</div>
								<div class="col-md-1 col-sm-1">
									<div class="form-group">
										<label for="status">status</label>
										<select class="form-control status" name="status">
											<?php foreach(getEnumValue('inventory', 'status') as $status): ?>
												<option <?php echo ($status == $serial['status'] ? 'selected' : '') ?>><?php echo $status; ?></option>
											<?php endforeach; ?>
										</select>
									</div>
									<div class="form-text"></div>
								</div>
								<div class="col-md-2 col-sm-2">
									<div class="row">
										<div class="col-md-7 col-sm-7">
											<label for="price">Cost</label>
											<input class="form-control cost" type="text" name="price" placeholder="$" value=""/>
											<div class="form-text"></div>
										</div>
										<div class="col-md-5 col-sm-5">
											<label for="add-delete">&nbsp;</label>
											<div class="btn-group" role="group" name="add-delete" style="display: block;">
												<button class="btn btn-primary btn-sm update" disabled><i class="fa fa-check" aria-hidden="true"></i></button>
												<button class="btn btn-danger delete btn-sm" disabled><i class="fa fa-minus" aria-hidden="true"></i></button>
											</div>
										</div>
									</div>
								</div>
								</div>
								<div class="row">
								<div class="col-sm-12">
									<a href="#" class="show_history">Show History +</a>
									
									<div class="row history_listing" style="display: none;">
										<div class="col-md-12">
											<?php //print_r(getItemHistory($serial['id'])); ?>
											<div class="table-responsive">
												<table class="table table-striped">
													<thead>
														<tr>
															<th>Date</th>
															<th>Rep</th>
															<th>Field Changed</th>
															<th>History</th>
														</tr>
													</thead>
													<tbody>
														<?php foreach(getItemHistory($serial['id']) as $history): ?>
															<tr>
																<th><?php echo date_format(date_create($history['date_changed']), 'm/d/Y'); ?></th>
																<td><?php echo getRepName($history['repid']); ?></td>
																<td><?php echo $history['field_changed']; ?></td>
																<td><?php echo $history['changed_from']; ?></td>
															</tr>
														<?php endforeach; ?>
													</tbody>
												</table>
											</div>
										</div>
									</div>
								</div>
								</div>
							</div>
						<?php endforeach; ?>
						<div class="col-md-12 text-center"><a class="show-more" href="#">Show More</a></div>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
		
		<div class="row" style="margin: 0;">
			<div class="col-md-12">
				<ul class="pagination">
					<?php getPages(); ?>
			    </ul>
		    </div>
	    </div>
	</div>



<?php include_once 'inc/footer.php'; ?>
<script src="js/operations.js"></script>

<script>
	(function($){
	
		//Show more data for a specific product, Serial etc
		$('.buttonAdd').click(function(){
			$(this).closest('.part-container').children('.addItem').slideToggle('fast');
			
			$(this).children('.fa-plus').toggleClass('fa-minus');
			$(this).toggleClass('btn-success btn-danger');
		});
		
		//Drop down a list of the history of the specific item
		$('.show_history'). click(function(e) {
			e.preventDefault();
			$(this).next('.history_listing').slideToggle();
		});
		
		
		$('.updateAll, .update').click(function () {
			var serial, date, location, qty, condition, status, cost, id, partid;
			//run through each of the rows that pertains to the class stated below and grab all the data
			$(this).closest('.addItem').find('.product-rows-edited').each(function () {
				//Declare Variables
				var element = this;

				//Add value to each variable depending on the data in the row
				id = $(this).data('id');
				serial = $(this).find('.serial').val();
				date = $(this).find('.date').val();
				location = $(this).find('.location').val();
				qty = $(this).find('.qty').val();
				condition = $(this).find('.condition').val();
				status = $(this).find('.status').val();
				cost = $(this).find('.cost').val();
				
				//get the specific part id based on position of the element
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
							
							$(element).closest('.addItem').find('.product-rows-edited').find('.update').prop("disabled", true);
							$(element).closest('.addItem').find('.product-rows-edited').removeClass('product-rows-edited');
							
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
		
		$('input, select').on('change keyup paste', function() {
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
							<button class="btn btn-danger delete btn-sm"><i class="fa fa-minus" aria-hidden="true"></i></button>\
						</div>\
					</div>\
				</div>\
			</div>';
		
		//Once button is clicked the new row will be appended
		$('.buttonAddRows').click(function(){
			$(this).closest('.part-container').find('.addRows').append(element);
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
		$('.delete').click(function(){
			$($(this).closest('.new-row')).slideUp("normal", function() { $(this).remove(); });
		});
		
		//Show hide serial products
		$('.show-more').click(function(e){
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
	})(jQuery);
</script>

</body>
</html>
