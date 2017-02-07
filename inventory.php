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
	
	$searched = (grab("search") != '' ? grab("search") : grab("s"));
	$_REQUEST['s'] = '';

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
		
		table.serial {
			width: 95%;
			margin: 0 auto;
		}
		
		.pointer {
			cursor: pointer;
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
		
		.edit {
			display: none;
		}
		
		.addRows .product-rows:first-child label {
			display: block;
		}
		
		.nopadding {
		   padding: 0 !important;
		   margin: 0 !important;
		}
		
		.input-group.datepicker-date {
			width: auto;
			min-width: auto;
			max-width: 100%;
		}
		
		@media screen and (max-width: 767px){
			.addRows label {
				display: block;
			}
		}
		#modalHistoryBody div:nth-child(even){
			background-color:#f7f7f7;
		}
s
	</style>

</head>

<body class="sub-nav">
	
<!----------------------------------------------------------------------------->
<!------------------------- Output the navigation bar ------------------------->
<!----------------------------------------------------------------------------->

	<?php include 'inc/navbar.php'; ?>
	<?php include_once 'modal/history.php'?>
	
<!----------------------------------------------------------------------------->
<!-------------------------- Header/Filter Bar/Title -------------------------->
<!----------------------------------------------------------------------------->
	<div class="table-header" style="width: 100%; min-height: 48px;">
		<div class="row" style="padding: 8px;" id = "filterBar">

			<div class="col-md-2 col-sm-2">
				<!--<input class="form-control" type="text" name="" placeholder="Location"/>-->
				<div class="row">
					<div class='col-md-6' style = 'padding-right:0px;'><?= loc_dropdowns('place')?></div>
					<div class='col-md-3 nopadding'><?= loc_dropdowns('instance')?></div>
					<div class="col-md-3" style  = 'padding-right:0px;padding-left:5px;'>
						<div class="input-group">
			              <input type="text" class="form-control input-sm" id="po_filter" placeholder="PO">
			            </div>
					</div>
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
			<div class="col-md-2 col-sm-2 text-center">
            	<h2 class="minimal">Inventory</h2>
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
			
			<!--Condition Drop down Handler-->
			<div class="col-md-1 col-sm-1">
				<?php
					$condition_selected = grab('condition','');
					echo dropdown('condition',$condition_selected,'','',false,"condition_global");
				?>
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

<!---------------------------------------------------------------------------->
<!------------------------------ Alerts Section ------------------------------>
<!---------------------------------------------------------------------------->

	<div id="inventory_loading" class="alert alert-warning fade in text-center" style="display: none;">
	    <strong>Loading...</strong>
	</div>
	<div id="item-updated" class="alert alert-success fade in text-center" style="display: none;">
	    <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
	    <strong>Success!</strong> Changes have been updated. Refresh required to re-organize data.
	</div>
	<div id="item-failed" class="alert alert-danger fade in text-center" style="display: none;">
	    <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
	    <strong>Uh Oh!</strong> Something went wrong with the update, please look into a fix for this error.
	</div>
	
<!----------------------------------------------------------------------------->
<!---------------------------------- Body Out --------------------------------->
<!----------------------------------------------------------------------------->

	<div class="loading_element_listing" style="display: none;">
		<div class='col-sm-12' style='padding-top: 20px'>
			<select class='revisions' multiple>
				
			</select>
			<img class='img-responsive' src='http://placehold.it/125x75' style='padding-right: 10px; float:left; padding-bottom: 10px;'>
		</div>
		<div class='col-sm-12'>
			<div class='table-responsive'>
				<table class='shipping_update table table-hover table-condensed' style='margin-top: 15px;'>
					<thead class = 'headers'>
						
					</thead>
					<tbody class='parts'>

					</tbody>
				</table>
			</div>
		</div>
	</div>

	<div style='display: none;'>
		<div class="locations row">
			<div class="col-md-6" style="padding-right: 5px;">
				<?=loc_dropdowns('place')?>
			</div>
			<div class="col-md-6" style="padding-left: 5px;">
				<?=loc_dropdowns('instance')?>
			</div>
		</div>
		
		<div class="conditions row">
			<div class="col-md-12">
				<?=dropdown('condition')?>
			</div>
		</div>
		
		<div class="status_select row">
			<div class="col-md-12">
				<?=dropdown('status')?>
			</div>
		</div>
	</div>

<?php include_once 'inc/footer.php'; ?>
<script src="js/operations.js"></script>

<script>
	(function($){
		// $('.disabled_input').find('select').prop('disabled', true)
		var filter_grab = function (){

			//Set an array up with the filter fields from the filter bar
			var output = {
				place : $("#filterBar").find(".place").val(),
				location : $("#filterBar").find(".instance").val(),
				start : $("#filterBar").find("input[name='START_DATE']").val(),
				end : $("#filterBar").find("input[name='END_DATE']").val(),
				condition : $("#filterBar").find("#condition_global").val(),
				vendor : $("#filterBar").find("#companyid").val()
			};
			console.log(output);
			return output;
		};
		var inventory_history = function (search) {

			var place = $("#filterBar").find(".place").val();
			var location = $("#filterBar").find(".instance").val();
			var start = $("#filterBar").find("input[name='START_DATE']").val();
			var end = $("#filterBar").find("input[name='END_DATE']").val();
			var condition = $("#filterBar").find("#condition_global").val();
			var vendor = $("#filterBar").find("#companyid").val();
			var order = $("#filterBar").find("#po_filter").val();

			$.ajax({
					type: "POST",
					url: '/json/inventory-out.php',
					data: {
						"search": search,
						"place" : place,
						"location" : location,
						"start" : start,
						"end" : end,
						"condition" : condition,
						"vendor" : vendor,
						"order" : order
					},
					dataType: 'json',
					success: function(part) {
						console.log('Successfully called: /json/inventory-out.php?search='+search+"&place="+place+"&location="+location+"&start="+start+"&end="+end+"&condition="+condition+"&vendor="+vendor);
						// Add feature to auto update the URL without a refresh
						if(search == '') {
							window.history.replaceState(null, null, "/inventory.php");
						} else {
							window.history.replaceState(null, null, "/inventory.php?search=" + search);	
						}
						var headers = '<tr>';
						if (!search){
							headers +=	"<th>Items</th>";
						}
						if (place == 'null'){
							headers +=	"<th>Location</th>";
						}
						headers +=	"<th>Qty</th>";
						
						if (condition != 'no'){
							headers +=	"<th>Condition</th>";
						}
						
						if(!order){
							headers +=	"<th>Purchase Order</th>";
						}
						if(!vendor){
							headers +=	"<th>Vendor</th>";
						}
						headers +=	"<th>Date Added</th>";
						headers += "</tr>";
						
						$(".revisions").empty();
						$(".headers").empty();
						$(".parts").empty();
						
						$(".part-container").html("").remove();	
						// var p = JSON.parse(part)
						console.log(part);
						var revisions, parts;
						var locations = $('.locations').clone();
						
						$('.conditions').find('label').remove();
						var conditions = $('.conditions').clone();
						
						$('.status_select').find('label').remove();
						var status = $('.status_select').clone();
						
						var counter = 1;
						revisions = "<option value='' selected>All</option>";
						//If there are multiple parts being returned, loop through them all
						$.each(part, function(partid, macro){
							//Add each part to the revisions page
							counter++;
							$.each(macro, function(key,info){
								revisions += "<option value='parts-"+counter+"'>"+info.part_name+"</option>";
								var key = key.split("+");
								console.log(key);
								parts += "<tr class='parts-list parts-"+counter+"' data-serial= 'serial_listing_"+info.unique+"'>";
								if (!search){
									parts += 	"<td>"+info.part_name+"</td>";
								}
								if (place == 'null'){
									parts += 	"<td>"+info.location+"</td>";
								}
									parts += 	"<td><span class='check_serials' style='color: #428bca; cursor: pointer;'>"+info.qty+"</span></td>";
								
								if (condition != 'no'){
									parts += 	"<td>"+key[2]+"</td>";
								}
								if(!order){
									parts += 	"<td>"+key[1]+"</td>";
								}
								if(!vendor){
									parts += 	"<td>"+info.vendor+"</td>";
								}
									parts += 	"<td>"+key[3]+"</td>";
									parts += "</tr>";

									parts += "<tr class='serial_listing serial_listing_"+info.unique+"' style='display: none;'>\
												<td colspan='12'>";
												parts += "<table class='table serial table-hover table-condensed'>\
															<thead>\
																<tr>";
												parts += "			<th>Serial Number</th>";
												parts += "			<th>qty</th>";
												parts += "			<th>Status</th>";
												parts += "			<th>Location</th>";
												parts += "			<th>Condition</th>";
												parts += "			<th></th>";
												parts += "		</tr>\
															</thead>\
															<tbody>";
									$.each(info.serials, function(i,s_string){
										var serial = s_string.split(", ");
										//console.log(history);
										parts += "<tr class='serial_listing_"+info.unique+"' style='display: none;'>\
													<td class='data pointer serial_original' style='color: #428bca; cursor: pointer;' data-id='"+serial[0]+"'>"+serial[1]+"</td>\
													<td class='edit'><input class='newSerial form-control' value='"+serial[1]+"' data-serial='"+serial[1]+"'/></td>";
										parts += "	<td class='data qty_original'>"+serial[2]+"</td>";
										parts += "	<td class='data status_original'>"+serial[3]+"</td>";
										parts += "	<td class='data '>"+info.location+"</td>";
										parts += "	<td class='data '>"+key[2]+"</td>";
										parts += "<td class='edit'><input class='newQty form-control' value='"+serial[2]+"' data-id='"+serial[0]+"'></td>\
												<td class='edit status_holder' data-status='"+serial[3]+"'></td>";
										// console.log(history);
										// var init = true;			
										// $.each(history.history,function(record, details){
										// 	console.log(details);
										// 	if(init) {
												
															
										// 		if(details.last_sale != null) {
										// 			//parts += "<td class='last_sale data'>"+details.last_sale+"</td>";
										// 			//parts += "<td class='edit'><input class='newSO form-control' placeholder='"+details.last_sale+"'>"+details.last_sale+"</td>";
										// 		} else {
										// 			//parts += "<td class='last_sale data'></td>";
										// 			//parts += "<td class='edit'><input class='newSO form-control' placeholder=''></td>";
										// 		}
												
										// 		init = false;
										// 	}
										// });
										parts += "<td class='data'></td><td class='data'></td>";
										parts += "<td class='edit location_holder' data-place='"+info.place+"' data-instance='"+info.instance+"'></td>\
													<td class='edit condition_holder' data-condition='"+key[3]+"'></td>";
													
										parts += "<td style='text-align: right;'>\
											<i style='margin-right: 5px;' class='fa fa-pencil edit_button pointer' aria-hidden='true'></i>\
											<a class='edit save_button btn-sm btn-flat success pull-right multipart_sub'>\
	                						<i class='fa fa-check fa-4' aria-hidden='true'></i></a>\
	                						<i style='margin-right: 5px;' class='fa fa-trash delete_button pointer' aria-hidden='true'></i></td>";
										parts += "</tr>";
									}); //Serials loop end
									parts += "</tbody>\
											</table>\
										</td>\
									</tr>";
									
							// 		// parts += "<tr>\
							// 		// <td colspan='12'>\
							// 		// </td>\
							// 		// </tr>"
									
							});
							$('.parts').append(parts);
							parts = "";
	
						});
						$('.revisions').append(revisions);
						$('.headers').append(headers);
						
						$('.location_holder').append(locations);
						$('.condition_holder').append(conditions);
						$('.status_holder').append(status);
						
						//GO through each of the conditions and locations and set each one to the respective value
						// $('.location_holder').each(function() {
						// 	var actualPlace = $(this).data('place');
						// 	var actualInstance = $(this).data('instance');
							
						// 	$(this).find('select').val(actualPlace);
						// 	$(this).find('select:last').val(actualInstance);
							
						// 	//alert(actualPlace);
						// });
						
						$('.condition_holder').each(function() {
							var actualCondition = $(this).data('condition');
							$(this).find('select').val(actualCondition);
						});
						
						$('.status_holder').each(function() {
							var actualStatus = $(this).data('status');
							$(this).find('select').val(actualStatus);
						});
						$(".location_holder").each(function() {
							var place = $(this).data('place');
							var instance = $(this).data('instance');
							$(this).find(".place").val(place);
							$(this).find(".instance option[data-place!='"+place+"']").hide();
							$(this).find(".instance").val(instance);
						});
						
						if(part != '') {
							$(".loading_element_listing").show();
						} else {
							$(".loading_element_listing").hide();
					  		alert("No Parts Found with those parameters");
						}
					},
					error: function(xhr, status, error) {
						$(".loading_element_listing").hide();
						console.log('Failed call: /json/inventory-out.php?search='+search+"&place="+place+"&location="+location+"&start="+start+"&end="+end+"&condition="+condition+"&vendor="+vendor);
						alert(error);
					   	alert("No Parts Found with those parameters");
					},			
			});
		}
		
		$(document).on('click', '.edit_button', function(e) {
			e.preventDefault();
			
			$(this).closest('tr').find('.edit').show();
			$(this).closest('tr').find('.data').hide();
			$(this).closest('table').find('th span.edit').show();
			
			$(this).closest('tr').find('.delete_button').hide();
			$(this).hide();
		});
		
		$(document).on('click', '.save_button', function(e) {
			e.preventDefault();
			var $save =$(this);
			
			var id = $save.closest('tr').find('.newQty').data('id');
			var newSerial = $save.closest('tr').find('.newSerial').val();
			// alert(newSerial);
			var newQty = $save.closest('tr').find('.newQty').val();
			var newStatus = $save.closest('tr').find('#status').val();
			var newSales = $save.closest('tr').find('.newSO').val();
			var newPlace = $save.closest('tr').find('.place').val();
			var newInstance = $save.closest('tr').find('.instance').val();
			var newCondition = $save.closest('tr').find('#condition').val();
			
			//alert("INVID: " + id + " New Serial: " + newSerial + " Qty: " + newQty + " Status: " + newStatus + " New SO: " + newSales + " New Place: " + newPlace + " New Instance: " + newInstance + " New Condition: " + newCondition);
			
			$.ajax({
				type: "POST",
				url: '/json/inventory-edit.php',
				data: {
					"id": id,
					"serial_no": newSerial,
					"qty": newQty,
					"status": newStatus,
					"so": newSales,
					"place": newPlace,
					"instance": newInstance,
					"condition": newCondition
				},
				dataType: 'json',
				success: function(result) {
					console.log('Success call: /json/inventory-edit.php?id='+id+'&serial_no='+newSerial+'&qty='+newQty+'&status='+newStatus+'&so='+newSales+'&place='+newPlace+'&instance='+newInstance+'&condition='+newCondition);
					if(result) {
						$save.closest('tr').find('.edit').hide();
						$save.closest('tr').find('.data').show();
						$save.closest('table').find('th span.edit').hide();
						$save.closest('tr').find('.edit_button').show();
						$save.closest('tr').find('.delete_button').show();
						
						$save.hide();
						$('.alert-success').show();
						$('.alert-success').delay(6000).fadeOut('fast');
						
						$save.closest('tr').find('.serial_original').html(newSerial);
						$save.closest('tr').find('.qty_original').html(newQty);
						$save.closest('tr').find('.status_original').html(newStatus);
					}
				},
				error: function(xhr, status, error) {
					// $(".loading_element_listing").hide();
					console.log('Failed call: /json/inventory-edit.php?id='+id+'&serial_no='+newSerial+'&qty='+newQty+'&status='+newStatus+'&so='+newSales+'&place='+newPlace+'&instance='+newInstance+'&condition='+newCondition);
					alert(error);
				   	alert("No Parts Found with those parameters");
				},
			});
		});
		
		//finish adding the filters
		
		$(document).ready(function() {
			//Triggering Aaron 2017
			var phpStuff = "<?=$_REQUEST['s']; ?>";
			if($("#part_search").val() || phpStuff != ''){
				var search = $("#part_search").val();
				if(search == '') {
					search = phpStuff;
					$("#part_search").val(phpStuff);
				}
				
				inventory_history(search);
			}
		});
		
		$(document).on('click', '.delete_button', function() {
			var $delete = $(this);
			if (window.confirm("Are you sure you want to delete this serial?")) {
				var id = $delete.closest('tr').find('.newQty').data('id');
				
	            $.ajax({
					type: "POST",
					url: '/json/inventory-edit.php',
					data: {
						"id": id,
						"delete": true
					},
					dataType: 'json',
					success: function(result) {
						//alert(result);
						if(result) {
							$delete.closest('tr').remove();
						}
					}
				});
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
			inventory_history(search);
		});

		
		$("#part_search").on("keyup",function(e){
			if (e.keyCode == 13) {
				var search = $("#part_search").val();
				inventory_history(search);
			}
		});

		$(document).on('click', '.revisions', function() {
			$('.serial_listing').hide();
			$('.parts-list').hide();
			var element = $(this).val();
			if(element != '') {
				$('.revisions :selected').each(function(i, selected){
					var part = selected.value;
					if (part != ''){
						$('.' + part).show();
					}
					else{
						$('.parts-list').show();
						return false;
					}
				});
			}
			else {
				$('.parts-list').show();
			}
		});
	
	})(jQuery);

</script>

</body>
</html>
