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
		<div class="row" style="padding-top: 15px; margin: 0 10px;" id = "filterBar">
			<div class="col-md-2 col-sm-2" style="padding-bottom: 15px;">
				<div class="input-group">
	              <input type="text" class="form-control" id="part_search" placeholder="Filter By Part/Serial" value=<?=grab("search")?>>
              		<span class="input-group-btn">
	                	<button class="btn btn-primary part_filter"><i class="fa fa-filter"></i></button>              
	            	</span>
	            </div>
			</div>

			<div class="col-md-1 col-sm-1" style="padding-bottom: 15px;">
				<!--<input class="form-control" type="text" name="" placeholder="Location"/>-->
				<?= loc_dropdowns('place')?>
			</div>
			<div class="col-md-1 col-sm-1" style="padding-bottom: 15px;">
				<?= loc_dropdowns('instance')?>
			</div>
			<div class="col-md-1 col-sm-1" style="padding-bottom: 15px;">
				<?php
					$condition_selected = grab('condition','');
					echo dropdown('condition',$condition_selected,'','',false,"condition_global");
				?>
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
			<div class="col-md-2 col-sm-2" style="padding-bottom: 15px;">
               	<button class="btn btn-primary inventory_filter pull-right"><i class="fa fa-filter"></i></button>              
			<!--	<div class="btn-group" role="group">-->
			<!--		<button class="btn btn-default active">In Stock</button>-->
			<!--		<button class="btn btn-default">Out Of Stock</button>-->
			<!--	</div>-->
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
							<th>Purchase Order</th>
							<th>Vendor</th>
							<th>Date Added</th>
						</tr>
					</thead>
					<tbody class='parts'>
						
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<div style='display: none;'>
		<div class="locations row-fluid">
			<div class="col-md-6">
				<?=loc_dropdowns('place')?>
			</div>
			<div class="col-md-6">
				<?=loc_dropdowns('instance')?>
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
		

		
		$(document).on('change keyup paste','input, select', function() {
			$(this).closest('.addItem').find('.updateAll').prop("disabled", false);
			$(this).closest('.product-rows').addClass('product-rows-edited');
			$(this).closest('.product-rows').find('.update').prop("disabled", false);
		});


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

	// }
	var inventory_history = function (search, serial) {
		$.ajax({
				type: "POST",
				url: '/json/inventory-out.php',
				data: {
					"search": search,
					
					},
				dataType: 'json',
				success: function(part) {
					$(".revisions").empty();
					$(".parts").empty();
					
					$(".part-container").html("").remove();	
					// var p = JSON.parse(part)
					console.log(part);
					var revisions, parts;
					var locations = $('.locations').clone();
					
					var counter = 1;
					revisions = "<option value='' selected>All</option>";
					//If there are multiple parts being returned, loop through them all
					$.each(part, function(i, info){
						//Add each part to the revisions page
						counter++;
						revisions += "<option value='parts-"+counter+"'>"+i+"</option>";
						$.each(info, function(label,row){
							parts += "<tr class='parts-list parts-"+counter+"' data-serial= 'serial_listing_"+row.unique+"'>\
								<td>"+row.location+"</td>\
								<td><span class='check_serials' style='color: #428bca; cursor: pointer;'>"+row.qty+"</span></td>\
								<td>"+row.condition+"</td>\
								<td>"+row.last_purchase+"</td>\
								<td>"+row.vendor+"</td>\
								<td>"+row.date_created+"</td>\
								</tr>";
							var s = row.serials;
							//alert (s);
							if(s){
								parts += "<tr class='serial_listing_"+row.unique+"' style='display: none;'>\
											<td colspan='12'>";
											parts += "<table class='table table-hover table-condensed'>\
														<thead>\
															<tr>\
															<th>Serial Number</th>\
															<th>New Serial</th>\
															<th>New Location</th>\
															<th>New Condition</th>\
															<th></th>\
															</tr>\
														</thead>\
														<tbody>";
								$.each(s, function(serial,history){
									parts += "<tr class='serial_listing_"+row.unique+"' style='display: none;'>\
													<td>"+serial+"</td>\
													<td><input class='newSerial form-control' placeholder='New Serial' data-serial='"+serial+"'/></td>\
													<td class='location_holder'></td>\
													<td><select class='newCondition form-control'></select></td>";
									$.each(history,function(record, details){
										if(details.last_sale != null) {
											parts += "<td>"+details.last_sale+"</td>";
										} else {
											parts += "<td></td>";
										}
										console.log(details);
									});
										parts += "<td>\
											<i style='margin-right: 5px;' class='fa fa-pencil' aria-hidden='true'></i>\
											<a class='btn-flat success pull-right multipart_sub'>\
                    						<i class='fa fa-check fa-4' aria-hidden='true'></i></a></td>";
									parts += "</tr>";
								}); //Serials loop end
								parts += "</tbody>\
										</table>";

							}
								
						});

					});
					$('.revisions').append(revisions);
					$('.parts').append(parts);
					
					$('.location_holder').append(locations);

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
	
	//finish adding the filters
	// var filter_grab = function (){
	// 	//Set an array up with the filter fields from the filter bar
	// 	var output = {
	// 		location : 
	// 	}
	// }
	
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
