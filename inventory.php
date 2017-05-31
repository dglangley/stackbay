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
	$qty_filter = grab("qty","in_stock");
	$cond_filter = grab("cond","good");

?>

<!----------------------------------------------------------------------------->
<!------------------------------- HEADER OUTPUT ------------------------------->
<!----------------------------------------------------------------------------->
<!DOCTYPE html>
<html>
<!-- Declaration of the standard head with Accounts home set as title -->
<head>
	<title>Inventory</title>
	<?php
		//Standard headers included in the function
		include_once $rootdir.'/inc/scripts.php';
	?>
	<link rel="stylesheet" href="../css/operations-overrides.css?id=<?php if (isset($V)) { echo $V; } ?>" type="text/css" />
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
		
		.table-head .input-group.datepicker-date {
			width: auto;
			min-width: auto;
			max-width: 100%;
		}
		
		@media screen and (max-width: 767px){
			.addRows label {
				display: block;
			}
		}
		#modalHistoryBody .history_meta{
			padding:2px;
		}
		#modalHistoryBody .history_meta:nth-child(even){
			background-color:#f7f7f7;
		}
		.label-in-repair{
			background-color: rgb(156,106,65);
		}
	</style>

</head>

<body class="sub-nav">
	
<!----------------------------------------------------------------------------->
<!------------------------- Output the navigation bar ------------------------->
<!----------------------------------------------------------------------------->

	<?php include 'inc/navbar.php'; ?>
	<?php include_once 'modal/history.php'?>
	<?php include_once 'modal/rm.php'?>

	
<!----------------------------------------------------------------------------->
<!-------------------------- Header/Filter Bar/Title -------------------------->
<!----------------------------------------------------------------------------->
	<div class="table-header" style="width: 100%; min-height: 48px;">
		<div class="row" style="padding: 8px;" id = "filterBar">
			<div class="col-md-2 col-sm-2" style='padding-right:0px;'>
				<!--<input class="form-control" type="text" name="" placeholder="Location"/>-->
				<div class="row" style = 'padding-right:0px;'>
					<div class='col-md-4' style = 'padding-right:0px; max-width:120px;'>
						<?= loc_dropdowns('place')?>
					</div>
					<div class='col-md-4 nopadding'>
						<div class="input-group">
							<?= loc_dropdowns('instance')?>
							<div class="input-group-btn">
								<button class="btn btn-sm btn-primary part_filter"><i class="fa fa-filter"></i></button>   
							</div>
						</div>
					</div>
					
					<div class="col-md-4" style  = 'padding-right:5px;padding-left:5px;'>
		              	<input type="text" class="form-control input-sm" style='padding-right:0px;padding-left:3px;' id="po_filter" placeholder="PO">
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
				<div class="row">
				    <div class="btn-group condition_filters">
				        <button class="glow filter_cond left large btn-radio<?php if ($cond_filter=='good') { echo ' active'; } ?>" type="submit" data-filter="good" data-toggle="tooltip" data-placement="bottom" title="Good">
				        	<i class="fa fa-thumbs-up"></i>					        
				        </button>
				        <button class="glow right filter_cond large btn-radio<?php if ($cond_filter=='all') { echo ' active'; } ?>" type="submit" data-filter="all" data-toggle="tooltip" data-placement="bottom" title="All">
				        	<i class="fa fa-square"></i>
			        	</button>
				    </div>
					<div class="btn-group qty_filters">
				        <button class="glow left large btn-radio filter_qty<?php if ($qty_filter=='in_stock') { echo ' active'; } ?>" type="submit" data-filter="in_stock" data-toggle="tooltip" data-placement="bottom" title="In Stock">
				        	<i class="fa fa-tag"></i>
				        </button>
				        <button class="glow right large btn-radio filter_qty<?php if ($qty_filter=='all') { echo ' active'; } ?>" type="submit" data-filter="all" data-toggle="tooltip" data-placement="bottom" title="All">
				        	<i class="fa fa-tags"></i>
			        	</button>
				    </div>
			    </div>
			</div>
			
			<div class="col-md-2 col-sm-2">
				<div class="company input-group">
					<select name='companyid' id='companyid' class='form-control input-xs company-selector required' >
						<option value=''>Select a Company</option>
					</select>
					<span class="input-group-btn">
						<button class="btn btn-sm btn-primary part_filter"><i class="fa fa-filter"></i></button>   
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
	    <strong>Success!</strong> Changes have been updated. Refresh required to re-organize data
	</div>
	<div id="item-failed" class="alert alert-danger fade in text-center" style="display: none;">
	    <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
	    <strong>Uh Oh!</strong> Something went wrong with the update, please look into a fix for this error
	</div>
	<div id="item-none" class="alert alert-warning fade in text-center" style="display: none;">
	    <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
        <i class="fa fa-frown-o fa-2x"></i> <strong>Lame!</strong> No item(s) found
	</div>
	
<!----------------------------------------------------------------------------->
<!---------------------------------- Body Out --------------------------------->
<!----------------------------------------------------------------------------->
<!--
	<span class='loading_search' style='text-align:center; display: block; padding-top: 10px; font-weight: bold;'>Loading Search Results...</span>
-->
	
	<div class="loading_element_listing" style="display: none;">
		
		<div class='col-sm-12' style='padding-top: 20px'>
			<select class='revisions' multiple>
				
			</select>
			<img class='img-responsive' src='/img/125x75.png' style='padding-right: 10px; float:left; padding-bottom: 10px;'>
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
		<div class="locations_main row">
			<div class="col-md-6" style="padding-right: 5px;">
				<?=loc_dropdowns('place')?>
			</div>
			<div class="col-md-6" style="padding-left: 5px;">
				<?=loc_dropdowns('instance')?>
			</div>
		</div>
		
		<div class="conditions_main row">
			<div class="col-md-12">
				<?=dropdown('conditionid','','','',false)?>
			</div>
		</div>
		
		<!--<div class="status_select row">-->
		<!--	<div class="col-md-12">-->
		<!--		<?=dropdown('status')?>-->
		<!--	</div>-->
		<!--</div>-->
	</div>

<?php include_once 'inc/footer.php'; ?>
<script src="js/operations.js?id=<?php if (isset($V)) { echo $V; } ?>"></script>

<script>
	(function($){
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
		$(document).on("click onload", ".filter_cond, .filter_qty", function(){
			var search = $("#part_search").val();
			$(this).closest(".btn-group").find(".active").removeClass("active");
			$(this).addClass("active");
			var cond = $(".condition_filters").find(".filter_cond.active").data('filter');
			var qty = $(".qty_filters").find(".filter_qty.active").data("filter");
			//alert($('.show_more_link:first').text() == "Show more");
			/* reset rows to defaults with no filters */
            $(".good_stock").show();
            $(".bad_stock").show();
            $(".in_stock").show();
            $(".no_stock").show();
            if(qty == "in_stock"){
                $(".no_stock").hide();
                $(".out_stock_item").hide();
            }
            if (cond == "good"){
                $(".bad_stock").hide();
                $(".bad_stock_item").hide();
            }
			$("[class^=serial_listing]").hide();
			if(search != '') {
				window.history.replaceState(null, null, "/inventory.php?search=" + search + "&cond=" + cond + "&qty="+qty);
			} else {
				window.history.replaceState(null, null, "/inventory.php?cond=" + cond + "&qty="+qty);
			}
		});

		
		// $('.disabled_input').find('select').prop('disabled', true)
		var filter_grab = function (){
			//Set an array up with the filter fields from the filter bar
			var f = getUrlParameter('search');
			if(!f){
				f = $("#part_search").val();
			}
			var output = {
				'part' : f,
				'place' : $("#filterBar").find(".place").val(),
				'location' : $("#filterBar").find(".instance").val(),
				'start' : $("#filterBar").find("input[name='START_DATE']").val(),
				'end' : $("#filterBar").find("input[name='END_DATE']").val(),
				'vendor' : $("#companyid").val()
			};
			console.log(output);
			return output;
		};
		var inventory_history = function () {
			var s = filter_grab();
			var place = $("#filterBar").find(".place").val();
			var location = $("#filterBar").find(".instance").val();
			var start = $("#filterBar").find("input[name='START_DATE']").val();
			var end = $("#filterBar").find("input[name='END_DATE']").val();
			var vendor = $("#companyid").val();
			var order = $("#po_filter").val();
			var search = s.part;
			$('#loader').show();
			console.log(window.location.origin+'/json/inventory-out.php?search='+s.part+"&place="+s.place+"&location="+s.location+"&start="+s.start+"&end="+s.end+"&vendor="+s.vendor);
			$.ajax({
					type: "POST",
					url: '/json/inventory-out.php',
					data: {
						"search": s.part,
						"place" : place,
						"location" : location,
						"start" : start,
						"end" : end,
						"vendor" : vendor,
						"order" : order
					},
					dataType: 'json',
					complete: function() { $('#loader').hide(); },
					success: function(part) {
						var nothing_found = true;
						var cond_filter = $(".condition_filters").find(".active").data("filter");
						var qty_filter = $(".qty_filters").find(".active").data("filter");
						if (part=='test') {
							console.log("Nothing_found");
							//$(".loading_element_listing").hide();
					  		//alert("No Parts Found with those parameters");
							$("#item-none").show();
							return;
						}
							
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
							
							headers +=	"<th>Condition</th>";
	
							if(!order){
								headers +=	"<th>Purchase Order</th>";
							}
							if(!vendor){
								headers +=	"<th>Vendor</th>";
							}
							headers +=	"<th>Date Added</th>";
							headers +=	"<th><button class = 'all_serials btn-sm btn-flat white pull-right' style='padding-top:3px;padding-bottom:3px;'><i class='fa fa-list'></i></button></th>"
							headers += "</tr>";
							
							$(".revisions").empty();
							$(".headers").empty();
							$(".parts").empty();
							
							//dgl 5-9-17
							//$(".part-container").html("").remove();	

							// var p = JSON.parse(part)
							//console.log(part);
							var revisions, parts;

							
							$('.conditions').find('label').remove();
							// var conditions = $('.conditions').clone();
							
							$('.status_select').find('label').remove();
							// var status = $('.status_select').clone();
							
							var counter = 1;
							var rev_arr = [];
							revisions = "<option value='' selected>All</option>";
							//If there are multiple parts being returned, loop through them all
							$.each(part, function(partid, macro){
								console.log(macro);
								if(macro == '') {
									part = '';
									return false; 
								}
								//Add each part to the revisions page
								counter++;
								$.each(macro, function(key,info){
									if (!(info.part_name in rev_arr)){
										revisions += "<option value='parts-"+counter+"'>"+info.part_name+"</option>";
										rev_arr[info.part_name] = false;
									}
									// break apart key to get relevant data (PO)
									var key = key.split(".");
									console.log(key);
									parts += "<tr class='parts-list parts-"+counter;
									if(info.qty == 0){
										parts += " no_stock ";
									} else {
										parts += " in_stock ";
									}
									if(info.conditionid > 0){
										parts += " good_stock ";
									} else {
										parts += " bad_stock ";
									}
									
									parts +="'";
									
									parts += "data-serial= 'serial_listing_"+info.unique+"'";
									
									//If the part has no quantity and the filter is NOT all, show it OR if the condition is negative and the filter is not negative, hide it.
									if((qty_filter != "all" && info.qty == 0) || (cond_filter != "all" && info.conditionid < 0)){
										parts += "style = 'display:none;'";
									} else {
										nothing_found = false;
									}
									
									parts += ">";
									if (!search){
										parts += 	"<td>"+info.part_name+"</td>";
									}
									if (place == 'null'){
										parts += 	"<td>"+info.location+"</td>";
									}
									
									var counterqty = info.qty;

										parts +=	"<td><button class = 'check_serials btn btn-sm btn-default pull-center' style='padding-top:3px;padding-bottom:3px;'>"+counterqty+"</button></td>";
									
										parts += 	"<td>"+key[2]+"</td>";
									var ofill = '';
									if(!order){
										if (key[1]!='') { ofill = key[1]+"&nbsp;&nbsp;<a href='/PO"+key[1]+"'><i class='fa fa-arrow-right' aria-hidden='true'></i></a>"; }
										parts += 	"<td>"+ofill+"</td>";
									}
									var vfill = '';
									if(!vendor){
										if (info.vendor!='') { vfill = info.vendor+"&nbsp;&nbsp;<a href='/profile.php?companyid="+info.vendorid+"'><i class='fa fa-arrow-right' aria-hidden='true'></i></a>"; }
										parts += 	"<td>"+vfill+"</td>";
									}
										parts += 	"<td>"+key[3]+"</td>";
										parts +=	"<td><button class = 'check_serials btn-sm btn-flat white pull-right' style='padding-top:3px;padding-bottom:3px;'><i class='fa fa-list'></i></button></td>";
										parts += "</tr>";
	
										parts += "<tr class='serial_listing serial_listing_"+info.unique;
										if(info.qty == 0){
											parts += " no_stock ";
										} else {
											parts += " in_stock ";
										}
										if(info.conditionid > 0){
											parts += " good_stock ";
										} else {
											parts += " bad_stock ";
										}
									
										parts += "' style='display: none;'>\
													<td colspan='12'>";
													parts += "<table class='table serial table-hover table-condensed'>\
																<tbody>";
									//SERIAL LEVEL ROW BUILDER	
										$.each(info.serials, function(i,s_string){
											var serial = s_string.split(", ");
											
											var status = serial[3].toLowerCase().replace(/\b[a-z]/g, function(letter) {
											    return letter.toUpperCase();
											});
											var color = '';
											var interpreted = status;
											if (status == "Manifest" || status == "Outbound"){
												interpreted = "Sold";
												color = "label-success";
											} else if (status == "Scrapped"){
												color = "label-danger";
											} else if (status == "In Repair"){
												color = "label-in-repair";
											} else {
												color = "label-warning";
												interpreted = "Active"
											}
											var line_qty = serial[2];
											parts += "<tr class='serial_listing_"+info.unique;
											if(info.conditionid < 0){
												parts += " bad_stock_item ";
											} 
											
											if(interpreted == "Sold"){
												parts += " out_stock_item ";
											}
											if(info.qty == 0){
												parts += " no_stock ";
											} else {
												parts += " in_stock ";
											}
											if(info.conditionid > 0){
												parts += " good_stock ";
											} else {
												parts += " bad_stock ";
											}
											parts += "' data-serial="+serial[1]+" data-part="+partid+" data-status='"+serial[3]+"'";
											parts += " data-invid='"+serial[0]+"' data-locid='"+info.locationid+"' data-place='"+info.place+"' data-instance='"+info.instance+"' data-name='"+info.part_name+"' data-cond = '"+key[2]+"' style='display: none;'>";	
											parts += "	<td class='serial_col data serial_original col-md-2' data-id='"+serial[0]+"'>"+serial[1]+"</td>";
											parts += "	<td class='data col-md-1'></td>";
											parts += "	<td class='data col-md-1'></td>";
											parts += "	<td class='data col-md-2'></td>";
											parts += "	<td class='data col-md-1'></td>";
											parts += "	<td class='notes_col data notes_original col-md-2'>";
											parts += serial[4];
											parts += "</td>";

											parts += "	<td class='serial_col edit'><input class='newSerial input-sm form-control' value='"+serial[1]+"' data-serial='"+serial[1]+"'/></td>";
											parts += "	<td class='qty_col edit'>1</td>";
											parts += "	<td class='status_col edit'>"+status+"</td>";
											parts += "	<td class='location_col edit location_holder' data-place='"+info.place+"' data-instance='"+info.instance+"'></td>";
											parts += "	<td class='condition_col edit condition_holder' data-condition='"+info.conditionid+"'></td>";
											parts += "	<td class='notes_col edit notes_holder'><input class='new_notes input-sm form-control' value='"+serial[4]+"' data-serial='"+serial[1]+"'/></td>";
											parts += "	<td class='edit_col' style='text-align: left;'>";
											parts += "<div class ='btn-group'>";
											parts += "<button type='button' class='btn-sm btn-flat dropdown-toggle white' style='padding-top:3px;padding-bottom:3px;' data-toggle='dropdown'>";
				                            parts += "<i class='fa fa-chevron-down'></i>";
				                            parts +='</button>';
											parts += '<ul class="dropdown-menu">';
											parts += '<li>';
											parts += "<a class='rm_button pointer text-left'><i class='fa fa-random' aria-hidden='true'></i> RM Serial</a>";
											parts += '</li>';
											parts += '<li>';
											parts += "<a class='history_button pointer text-left' data-id='"+serial[0]+"'>";
											parts += "<i style='margin-right: 5px;' class='fa fa-history' aria-hidden='true'></i> Show History";
											parts += "</a>";
												// parts += "<i style='margin-right: 5px;' class='fa fa-history history_button pointer' aria-hidden='true' data-id='"+serial[0]+"'></i>";
											parts += '</li>';
											parts += '<li>';
											parts += "<a class='repair_button pointer text-left' data-invid="+serial[0]+" data-status='"+serial[3]+"'>";
											if(serial[3] == 'in repair') {
												parts += "<i style='margin-right: 5px;' class='fa fa-truck' aria-hidden='true'></i> Mark as Repaired";
											} else {
												parts += "<i style='margin-right: 5px;' class='fa fa-wrench' aria-hidden='true'></i> Send to Repair";
											}
											parts += '</li>';
											parts += '<li>';
											parts += "<a class='edit_button pointer text-left'>";
											parts += "<i style='margin-right: 5px;' class='fa fa-pencil' aria-hidden='true'></i> Edit Serial Details";
											parts += '</a>';
											parts += '</li>';
											parts += '<li>';
											parts += "<a class='scrap_button pointer text-left'  data-invid="+serial[0]+" data-status='"+serial[3]+"'>";											
											parts += "<i style='margin-right: 5px;' class='fa fa-recycle' aria-hidden='true'></i> Scrap Item";
											parts += '</a>';
											parts += '</li>';
											parts += '</ul>';
											parts += "</td>";
											
		                					parts +="<td>\
												<a class='edit save_button btn-sm btn-flat success pull-left'><i class='fa fa-save fa-4' aria-hidden='true'></i></a>\
		                						</td>";
											parts +="<td class = 'text-right'>";

											parts += '<span class="label '+color+' complete_label status_label text-right" style="">'+interpreted+'</span>';
											parts +="</td>";
											parts += "</tr>";
										}); //Serials loop end
										parts += "</tbody>\
												</table>\
											</td>\
										</tr>";
										
								});
								
								$('.parts').append(parts);
								parts = "";
		
							});
							if (nothing_found) {
								console.log("Nothing_found");
								//$(".loading_element_listing").hide();
						  		//alert("No Parts Found with those parameters");
								$("#item-none").show();
							} 
							$('.revisions').append(revisions);
							$('.headers').append(headers);
							
							// $('.location_holder').append(locations);
							// $('.condition_holder').append(conditions);
							// $('.status_holder').append(status);
							
							//GO through each of the conditions and locations and set each one to the respective value
							// $('.location_holder').each(function() {
							// 	var actualPlace = $(this).data('place');
							// 	var actualInstance = $(this).data('instance');
								
							// 	$(this).find('select').val(actualPlace);
							// 	$(this).find('select:last').val(actualInstance);
								
							// 	//alert(actualPlace);
							// });
							
							// $('.condition_holder').each(function() {
							// 	var actualCondition = $(this).data('condition');
							// 	$(this).find('select').val(actualCondition);
							// });
							
							$('.status_holder').each(function() {
								var actualStatus = $(this).data('status');
								$(this).find('select').val(actualStatus);
							});
							/*$(".location_holder").each(function() {
								var place = $(this).data('place');
								var instance = $(this).data('instance');
								$(this).find(".place").val(place);
								$(this).find(".instance option[data-place!='"+place+"']").hide();
								$(this).find(".instance").val(instance);
							});*/
							
							$(".loading_element_listing").show();
					},
					error: function(xhr, status, error) {
						//$(".loading_element_listing").hide();
					   	alert("Error on the parts receipt: "+error);
					},			
			});
		}
		
		
		//============================ Side buttons ============================
		$(document).on('click','.repair_button, .save_button, .delete_button, .scrap_button',function(){
			//Variable Delcarations
			var $save =$(this);
			var id = $save.closest('tr').data('invid');
			var newSerial = $save.closest('tr').find('.newSerial').val();
			var newPlace = $save.closest('tr').find('.place').val();
			var newInstance = $save.closest('tr').find('.instance').val();
			var newCondition = $save.closest('tr').find('#conditionid').val();
			var status = $save.closest('tr').find('.status_col').val();
			var newNotes = $save.closest('tr').find('.new_notes').val(); 
			var action = 'save';
			var valid = true;
			if($(this).hasClass("repair_button")){
				action = 'repair';
				status = $(this).data("status");
			}else if($(this).hasClass("delete_button")){
				action = 'delete';
				valid = false;
				if (window.confirm("Are you sure you want to delete this serial?")) {
					valid = true;
				}
			}else if($(this).hasClass("scrap_button")){
				action = 'scrap';
				if (window.confirm("Are you sure you want to scrap this serial?")) {
					valid = true;
				}
			}
			console.log(window.location.origin+'/json/inventory-edit.php?id='+id+'&serial_no='+newSerial+'&place='+newPlace+'&instance='+newInstance+'&conditionid='+newCondition);
			if (valid){
				$.ajax({
				type: "POST",
				url: '/json/inventory-edit.php',
				data: {
					"id": id,
					"serial_no": newSerial,
					"place": newPlace,
					"instance": newInstance,
					"conditionid": newCondition,
					"notes" : newNotes,
					"status": status,
					"action": action
				},
				dataType: 'json',
				success: function(result) {
					console.log(result);
					if(result && action == 'save') {
						$save.closest('tr').find('.edit').hide();
						$save.closest('tr').find('.data').show();
						$save.closest('table').find('th span.edit').hide();
						$save.closest('tr').find('.edit_button').show();
						$save.closest('tr').find('.delete_button').show();
						
						$save.hide();
						// $('.alert-success').show();
						// $('.alert-success').delay(6000).fadeOut('fast');
						
						$save.closest('tr').find('.serial_original').html(newSerial);
						$save.closest('tr').find('.notes_original').text(newNotes);
					} else if (action == 'repair') {
						location.reload();
						console.log(result);
					} else{
						$save.closest('tr').remove();
					}
					console.log('inv')
				},
				error: function(xhr, status, error) {
					// $(".loading_element_listing").hide();
				   	alert("No Parts Found with those parameters: "+error);
				},
				complete: function(result){
					$("tbody").html("");
					inventory_history();
				}
			});
			}

		});
		
		// parts += "	<td class='serial_col edit'><input class='newSerial input-sm form-control' value='"+serial[1]+"' data-serial='"+serial[1]+"'/></td>";
		// parts += "	<td class='qty_col edit'>1</td>";
		// parts += "	<td class='status_col edit'>"+status+"</td>";
		// parts += "	<td class='location_col edit location_holder' data-place='"+info.place+"' data-instance='"+info.instance+"'></td>";
		// parts += "	<td class='condition_col edit condition_holder' data-condition='"+info.conditionid+"'></td>";
		// parts += "	<td class='notes_col edit notes_holder'><input class='new_notes input-sm form-control' value='"+serial[4]+"' data-serial='"+serial[1]+"'/></td>";
		// parts += "	<td class='edit_col' style='text-align: right;'>";
		$(document).on('click', '.edit_button', function(e) {
			e.preventDefault();
			var loc_col = $(this).closest('tr').find('.edit.location_col');
			if (loc_col.find('.locations').length === 0){
				$('.locations_main').clone().appendTo(loc_col).removeClass("locations_main").addClass('locations');
				var actualInstance = loc_col.data('instance');
				var actualPlace = loc_col.data('place');
				loc_col.find('.instance').val(actualInstance);
				loc_col.find('option[data-place='+actualPlace+']').show();
				loc_col.find('.place').val(actualPlace);
				
			}
			var con_col = $(this).closest('tr').find('.edit.condition_col');
			if (con_col.find('.conditionid').length === 0){
				$('.conditions_main').clone().appendTo(con_col).removeClass("conditions_main").addClass('conditions');
				var actualCon = con_col.data('condition');
				con_col.find('.conditionid').val(actualCon);
			}
			
			
			$(this).closest('tr').find('.edit').show();
			$(this).closest('tr').find('.data').hide();
			$(this).closest('table').find('th span.edit').show();
			
			$(this).closest('tr').find('.delete_button').hide();
			$(this).hide();
		});
		
		
		//One might expect the history button to be here, but it is not: Aaron
		//figures it was something general purpose enough that it should be on
		//operations.js; it might even need to be moved to ventel.js. Similarly 
		//for the RM button, which has it's own independent processing method
		
		var SEARCH = "<?=$searched?>";
		$(document).ready(function() {
			$('#loader-message').html('Please wait for Inventory results to load...');
			if (SEARCH!='') { inventory_history(); }
//			$('#loader').show();

			//Triggering Aaron 2017
//			var phpStuff = "<?=$_REQUEST['s']; ?>";
//			if($("#part_search").val() || phpStuff != ''){
//				SEARCH = $("#part_search").val();
//				if(! SEARCH) {
//					SEARCH = phpStuff;
//					$("#part_search").val(phpStuff);
//				}
				
//				$('#loader').hide();
//			}
//			$('#loader').hide();
		});
		
		
		//This function show all the serial if the user clicks on the qty link
		$(document).on('click', '.check_serials', function(e) {
			e.preventDefault;
			var cond = $(".condition_filters").find(".filter_cond.active").data('filter');
			var qty = $(".qty_filters").find(".filter_qty.active").data("filter");
			var parent = $(this).closest('.parts-list').data('serial');
			if ($("."+parent).is(":visible")){
				$('.' + parent).hide();
			}else{
				$('.' + parent).show();
				if (qty == "in_stock"){
					$(".out_stock_item").hide();
				}
			}
			
			
		});
		$(document).on('click', '.all_serials', function(e) {
			e.preventDefault;
			var cond = $(".condition_filters").find(".filter_cond.active").data('filter');
			var qty = $(".qty_filters").find(".filter_qty.active").data("filter");
	        if ($(".serial_listing:visible").length){
	        	$(".serial_listing").hide();
	        }else{
	        	$("[class^=serial_listing]").show();
				if (qty == "in_stock"){
					$(".out_stock_item").hide();
					$(".no_stock").hide();
				}
				if (cond == "good"){
					$(".bad_stock").hide();
					$(".bad_stock_item").hide();
				}
			}
		});
		$(document).on("click",".part_filter",function(){
			SEARCH = $("#part_search").val();
			inventory_history();
		});

		$("#part_search, #po_filter").on("keyup",function(e){
			if (e.keyCode == 13) {
				SEARCH = $("#part_search").val();
				inventory_history();
			}
		});

		$(document).on('click', '.revisions', function() {
			$('.serial_listing').hide();
			$('.parts-list').hide();
			var element = $(this).val();
			if(element != '') {
				$('.revisions').find(':selected').each(function(i, selected){
					var part = $(this).val();
					if (part != ''){
						$('.' + part).show();
					}
					else{
						$('.parts-list').show();
						// $('.serial_listing').show();
						return false;
					}
				});
			}
			else {
				// alert('here');
				$('.parts-list').show();
				// $('.serial_listing').hide();
			}
		});
	
	})(jQuery);

</script>

</body>
</html>
