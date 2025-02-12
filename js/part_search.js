// Create a generic funtion to invoke the ajax call for part searches
function partSearch(search, filter, cid, order_type) {
	if (! cid) { var cid = ''; }
	if (! order_type) { var order_type = ''; }

	var type = $('body').data("order-type");

	if(search || cid) {
		// Show a load image if you have one
		$('#loader-message').html('Please wait while your request is being processed...');
		$("#loader").show();

		console.log(window.location.origin+"/json/part_search.php?search="+escape(search));

		$.ajax({
	        url: 'json/part_search.php',
	        type: 'get',
	        dataType: "json",
	        data: {'search': search, 'filter': filter, 'companyid': cid, 'order_type': order_type},
	        success: function(json) {
	        	$(".found_parts").remove();
				var ti = 2;//tabindex

				console.log(json);
				var rowHTML = '';
				$.each(json, function(key, row){
					// Key is the part id
					// Row is the detail of the part
					// Quick check to see if all elements exist

					var heci = '';
					var description = '';
					var stock = '';

					if(row.heci != null) {
						heci = row.heci;
					}

					if(row.description != null) {
						description = row.description;
					}

					if(row.stock > 0) {
						stock = '<span style="color: #3c763d; text-align: right;">'+row.stock+'</span>';
					} 

					if(type == 'quote') {
						rowHTML += '<tr class="found_parts found_parts_quote" style="overflow:hidden;">';
						rowHTML += '	<td class="part">\
											<div class="remove-pad col-md-1">\
												<div class="product-img"><img class="img" src="/img/parts/'+row.part+'.jpg" alt="pic" data-part="'+row.part+'"></div>\
											</div>\
											<div class="col-md-11">\
												<span class="descr-label">'+row.part+ ' ' +heci+' </span>\
												<div class="description desc_second_line descr-label" style="color:#aaa;"><span class="description-label">'+description+'</span></div>\
											</div>\
										</td>';
						rowHTML += '	<td>\
											<div class="col-md-4 remove-pad" style="padding-right: 5px;">\
												<input class="form-control input-sm part_qty" type="text" name="qty" data-partid="'+row.id+'" data-stock="'+row.stock+'" placeholder="QTY" value="">\
											</div>\
											<div class="col-md-8 remove-pad">\
												<div class="form-group" style="margin-bottom: 0;">\
													<div class="input-group">\
														<span class="input-group-addon">\
											                <i class="fa fa-usd" aria-hidden="true"></i>\
											            </span>\
											            <input class="form-control input-sm part_amount" type="text" name="amount" placeholder="0.00" value="">\
											        </div>\
												</div>\
											</div>\
										</td>';
						// rowHTML += '	<td></td>';
						rowHTML += '	<td style="background: #FFF;"><div class="table market-table" data-partids="'+row.id+'">\
											<div class="bg-availability">\
												<a href="javascript:void(0);" class="market-title modal-results" data-target="marketModal" data-title="Supply Results" data-type="supply">\
													Supply <i class="fa fa-window-restore"></i>\
												</a>\
												<a href="javascript:void(0);" class="market-download" data-toggle="tooltip" data-placement="top" title="" data-original-title="force re-download">\
													<i class="fa fa-download"></i>\
												</a>\
												<div class="market-results" id="'+row.id+'" data-ln="0" data-type="supply">\
												</div>\
											</div>\
										</div></td>';
						rowHTML += '	<td class="datetime">\
											<div class="col-md-2 remove-pad">\
												<input class="form-control input-sm date_number" type="text" name="leadtime" data-partid="'+row.id+'" data-stock="'+row.stock+'" placeholder="#" value="">\
											</div>\
											<div class="col-md-4">\
												<select class="form-control input-sm date_span">\
													<option value="days">Days</option>\
													<option value="weeks">Weeks</option>\
													<option value="months">Months</option>\
												</select>\
											</div>\
											<div class="col-md-6 remove-pad">\
												<div class="form-group" style="margin-bottom: 0; width: 100%;">\
													<div class="input-group datepicker-date date datetime-picker" style="min-width: 100%; width: 100%;" data-format="MM/DD/YYYY">\
											            <input type="text" name="delivery_date" class="form-control input-sm delivery_date" value="">\
											            <span class="input-group-addon">\
											                <span class="fa fa-calendar"></span>\
											            </span>\
											        </div>\
												</div>\
											</div>\
										</td>';
						rowHTML += '	<td><div class="form-group" style="margin-bottom: 0;">\
											<div class="input-group">\
									            <input type="text" class="form-control input-sm part_perc" value="" placeholder="0">\
									            <span class="input-group-addon">\
									                <i class="fa fa-percent" aria-hidden="true"></i>\
									            </span>\
									        </div>\
										</div></td>';
						rowHTML += '	<td><div class="form-group" style="margin-bottom: 0;">\
											<div class="input-group">\
												<span class="input-group-addon">\
									                <i class="fa fa-usd" aria-hidden="true"></i>\
									            </span>\
									            <input type="text" placeholder="0.00" class="form-control input-sm quote_amount" value="">\
									        </div>\
										</div></td>';

						// rowHTML += '	<td class="add_button">\
						// 					<button class="btn btn-success btn-sm pull-right quote_add">\
						// 	        			<i class="fa fa-plus"></i>\
						// 		        	</button>\
						// 		        </td>';
						
						rowHTML += '	<td class="hidden" style="cursor: pointer;">\
											<i class="fa fa-trash fa-4 remove_part pull-right" style="margin-right: 10px; margin-top: 4px;" aria-hidden="true"></i>\
										</td>';
										//<input type="checkbox" class="pull-right" name="quote_request_null[]" value="'+row.id+'">\

						rowHTML += '</tr>';
					} else {
						rowHTML += '<tr class="found_parts">';
						rowHTML += '	<td class="part"><span class="descr-label">'+row.part+ ' ' +heci+' </span><div class="description desc_second_line descr-label" style="color:#aaa;"><span class="description-label">'+description+'</span></div></td>';
						if ($("body").data("scope")!='') { rowHTML += '<td colspan="5"> </td>'; }
						rowHTML += '	<td>\
											<div class="input-group">\
												<input class="form-control input-sm part_qty" type="text" name="qty" data-partid="'+row.id+'" data-stock="'+row.stock+'" placeholder="QTY" value="" tabindex="'+ti+'">\
												<span class="input-group-btn"><button class="btn btn-default input-sm" disabled><strong>'+row.stock+'</strong></button></span>\
											</div>\
										</td>';
	//					rowHTML += '	<td class="stock">'+stock+'</td>';
						rowHTML += '</tr>';
					}
					ti++;
				});

				if(type == 'quote') {
					$('#quote_input').after(rowHTML);

					$('.quote_add').show();

					$(".market-results").each(function() {
						$(this).loadResults(0,1);
						// $(this).loadResults(1,1);
					});
				} else {
					$('#search_input').append(rowHTML);
				}
				$("#loader").hide();
	        },
	        error: function(xhr, desc, err) {
	            console.log("Details: " + desc + "\nError:" + err);
				$("#loader").hide();
	        }
	    }); // end ajax call
	}
}

(function($){
	

   	// This function just clones what the user selected and makes a header in the main table
   	function createListings(object = ''){
		var hasElements = false;

		// Universal determine what type of screen the taskview is currently
		var type = $('body').data("order-type");

		if(! object) {
			$(".part_qty").each(function(){
				var qty = $(this).val();
				if(qty > 0) {
					if(type == 'quote') {
						$(this).closest(".found_parts").clone().removeClass("found_parts").addClass("part_listing").prependTo("#quote_body");
						//.append('<td style="cursor: pointer;"><i class="fa fa-trash fa-4 remove_part pull-right" style="margin-right 10px; margin-top: 4px; aria-hidden="true"></i></td>')
						$('#quote_body').find(".hidden").removeClass('hidden');
					} else {
						$(this).closest(".found_parts").clone().removeClass("found_parts").addClass("part_listing").append('<td style="cursor: pointer;"><i class="fa fa-trash fa-4 remove_part pull-right" style="margin-right 10px; margin-top: 4px; aria-hidden="true"></i></td>').prependTo("#search_input");
					}

					$(".part_listing").find(".stock").remove();
					if(type != 'quote') {
						$(".part_listing").find("input").prop("readonly", true);
					}

					hasElements = true;
				}
			});
		} else {
			object.removeClass("found_parts").addClass("part_listing").addClass("hide_add").insertAfter(".material_pulls:last");
			$("#quote_body tr.part_listing:last").after('<tr class="material_pulls"><td colspan="7" class=""><table class="table table-condensed table-noborder table-striped"></table></td></tr>');
			$('#quote_body').find(".hidden").removeClass('hidden');
		}
	
		if(! hasElements && ! object) {
			alert("No Component or QTY found.");
		} else {
			$('.quote_add').hide();
			$(".found_parts").remove();
			$("#partSearch").val("").focus();
		}	
	}

	// This generates the rows in the summary tab and creates the required hidden inputs
	function availComponents(object) {
		console.log(object);
		$(".stock_component").empty();

		//Determine if fullfill from stock should exists
		var fulfill = false;
		var elements = 0;

		var partid = 0;
		var request = 0;

		$.each(object, function(key, row) {
			var rowHTML = '';

			if(row['stock'] > 0) {
				fulfill = true;
			}

			rowHTML += '<tr class="part_listing-'+key+'">';
			rowHTML += '<td class="part"><span class="part_description" data-request="'+row['requested']+'"></span></td>';
			rowHTML += '<td>'+row['requested']+' <input class="hidden" value="'+row['requested']+'" name="requested['+row['partid']+']"></td>';
			rowHTML += '<td>'+row['stock']+'</td>';
			rowHTML += '</tr>';
			$(".stock_component").append(rowHTML);

			$(row['part_info']).appendTo(".part_listing-"+key+" .part .part_description");

			partid = row['partid'];
			requested = row['requested'];

			elements++;
		});

		if(fulfill && elements == 1) {
			$(".add_component").show();
			$(".add_component").attr("data-partid", partid);
		} else {
			$(".add_component").hide();
		}
	}

	// This function creates the rows in the table of first the components belonging to the specific order then the floating free inventory items
	function pullComponents(partid, itemid, type, object, requested) {
		console.log(window.location.origin+"/json/part_pull.php?partid="+escape(partid)+"&itemid="+escape(itemid)+"&type="+escape(type));
		$.ajax({
	        url: 'json/part_pull.php',
	        type: 'get',
	        dataType: "json",
	        data: {'partid': partid, 'itemid': itemid, 'type': type},
	        success: function(json) {
				console.log(json);
				var rowHTML = '<tr>\
								<td class="stock_desc"></td>\
								<td>'+requested+'</td>\
							</tr>';
				rowHTML += '<tr>\
								<td colspan="2">\
									<table class="table table-hover table-striped table-condensed">\
										<thead>\
											<tr><th>Location</th><th>Condition</th><th>Stock</th><th>Pull</th></tr>\
										</thead>\
										<tbody>';
				$.each(json, function(key, row){
					// Key is the inventory id
					// Row is the detail of the part

					var request = '';
					var location = 'N/A';

					if(row.requested == 'true') {
						request = 'alert-success';
					}

					if(row.location != false) {
						location = row.location;
					}

					rowHTML += '<tr data-invid="'+key+'" data-partid="'+row.partid+'">';
					rowHTML += '	<td class="col-md-6 '+request+'">'+location+'</td>';
					rowHTML += '	<td class="col-md-3 '+request+'">'+row.condition+'</td>';
					rowHTML += '	<td class="col-md-1 '+request+'">'+row.qty+'</td>';
					rowHTML += '	<td class="col-md-2 '+request+'"><input type="text" name="pulled['+key+']" class="input-sm form-control inventory_pull" value=""></td>';
					rowHTML += '</tr>';
				});

				rowHTML += '			</tbody>\
									</table>\
								</td>\
							</tr>';

				$('#stock_component').empty();
				$('#stock_component').append(rowHTML);
				$('#stock_component .stock_desc').append(object);
				$("#modal-component").modal();
				$('.nav-tabs a[href="#item_stock"]').tab('show');
	        },
	        error: function(xhr, desc, err) {
	            console.log("Details: " + desc + "\nError:" + err);
	        }
	    }); // end ajax call
	}

	$(document).on("click", ".pull_part, .add_component", function(e){

		e.preventDefault();

		var partid = $(this).attr("data-partid");
		var itemid = $(this).attr("data-itemid");
		var type = $(this).attr("data-type");

		var partObject = $(this).closest(".list").find(".part_description").clone();
		var request = $(this).closest(".list").find(".part_description").data("request");

		if(! request) {
			// Using the new view with BOM to search coirrectly
			var partObject = $(this).closest(".material_pulls").prev().find(".part_description").clone();
			var request = $(this).closest(".material_pulls").prev().find(".part_description").data("request");
		}

		pullComponents(partid, itemid, type, partObject, request);
	});

   	// Quick cleanup on new request
   	$(document).on("click", ".modal_request", function(){
   		$("#modal-component .part_listing").remove();
		$("#partSearch").val("");
		$(".found_parts").remove();
		//$("#partSearch").val("").focus();
   	});

   	$('#modal-component').on('shown.bs.modal', function () {
	    $("#partSearch").val("").focus();
	})  

	// Call to populate the component request modal
	$(document).on("keydown", "#partSearch", function(e){
		var key = e.which;

		if(key == 13) {
			e.preventDefault();
			var search = $(this).val();
			partSearch(search, '');
		}
	});
	$(document).on("click", "#btn-partsearch", function(e) {
		e.preventDefault();

		var search = $("#partSearch").val();

		if(search) {
			partSearch(search, '');
		}
	});

	$(document).on("click", ".li_search_button", function(e){
		e.preventDefault();

		var search = $(this).closest("#quote_input").find("#partSearch").val();
		partSearch(search, '');
	});

	$(document).on("keydown",".part_qty, .found_parts input",function(e){
		if (e.keyCode == 13) {
			//alert('here');
			e.preventDefault();
			createListings();
		}
	});

	// $(document).on("click", ".quote_add", function(e) {
	// 	e.preventDefault();

	// 	createListings($(this).closest('.found_parts'));
	// });

	$(document).on("click", ".quote_add", function(e) {
		e.preventDefault();

		$(".found_parts").each(function() {
			var parts_row = $(this);
			var valid = false;
			if(! valid) {
				parts_row.find('.part_amount, .part_qty, .quote_amount, .part_perc').each(function(){
		            if($(this).val() != "" && ! valid) {
		            	createListings(parts_row);
		            	valid = true;
		            }
		        });
	        }
		});
	});

	$(document).on("click", "#part_entry", function(){
		createListings();
	});

	$(document).on("click", ".remove_part", function(e){
		var container = $(this);

		if(confirm("Please Confirm Deletion of Material from Order.")) {
			//container.closest(".part_listing").remove();
			container.closest(".part_listing").find('.part_qty').data('partid', null);

			var type = $(this).data('type');

			var input = $("<input>").attr("type", "hidden").attr("name", "create").val(type);
			$('#save_form').append($(input));

			var counter = 1;

			$('.part_listing').each(function(){

				var quoteid = $(this).data('quoteid');
				var partid = $(this).find('.part_qty').data('partid');
				var qty = $(this).find('.part_qty').val();
				var amount = $(this).find('.part_amount').val();
				var leadtime = $(this).find('.date_number').val();
				var lead_span = $(this).find('.date_span').val();
				var profit = $(this).find('.part_perc').val();
				var quote = $(this).find('.quote_amount').val();

				if(partid) {
					if(quoteid) {
						input = $("<input>").attr("type", "hidden").attr("name", "materials["+partid+"]["+counter+"][quoteid]").val(quoteid);
						$('#save_form').append($(input));
					}

					// Generate an input for all the quoted materials on the current quote
					input = $("<input>").attr("type", "hidden").attr("name", "materials["+partid+"]["+counter+"][qty]").val(qty);
					$('#save_form').append($(input));

					input = $("<input>").attr("type", "hidden").attr("name", "materials["+partid+"]["+counter+"][amount]").val(amount);
					$('#save_form').append($(input));

					input = $("<input>").attr("type", "hidden").attr("name", "materials["+partid+"]["+counter+"][leadtime]").val(leadtime);
					$('#save_form').append($(input));

					input = $("<input>").attr("type", "hidden").attr("name", "materials["+partid+"]["+counter+"][lead_span]").val(lead_span);
					$('#save_form').append($(input));

					input = $("<input>").attr("type", "hidden").attr("name", "materials["+partid+"]["+counter+"][profit]").val(profit);
					$('#save_form').append($(input));

					input = $("<input>").attr("type", "hidden").attr("name", "materials["+partid+"]["+counter+"][quote]").val(quote);
					$('#save_form').append($(input));

					counter++
				}
			});

			$('#save_form').submit();
		}
	});

	$(document).on("change", ".date_number, .date_span", function(){
		var container = $(this).closest(".datetime");

		var number = container.find(".date_number").val();
		var span = container.find(".date_span").val();
		var days = 0;

		var date = new Date();

		// Make sure both are set before trying to execute the calculations
		if(number && span) {
			if(span == "days") {
				days = parseFloat(number);
				date.setDate(date.getDate() + days); 
			} else if(span == "weeks") {
				days = parseFloat(number) * 7;

				date.setDate(date.getDate() + days); 
			} else if(span == "months") {
				date = addMonths(number, date);
			}

			var newDate = new Date(date);

			var formatDate = (newDate.getMonth()+1) + "/" + newDate.getDate() + "/" + newDate.getFullYear();
		}

		container.find(".delivery_date").val(formatDate);
		//alert(formatDate);
	});

	//Create middle modal to show the tech avaiable components and the option to partial / request or fulfill the request straight from the inventory
	$(document).on("click", ".stock_check", function(e) {
		var data = [];
		var hasElements = false;

		createListings();

		$("#modal-component .part_listing").each(function(){
			var description = $(this).find(".part").clone();
			var requested = {partid :$(this).find("input").data("partid"), stock: $(this).find("input").data("stock"), requested: $(this).find("input").val(), part_info: description};

			data.push(requested);

			hasElements = true;
		});

		if(hasElements) {
			availComponents(data);
			$('.nav-tabs a[href="#stock"]').tab('show');
		} else {
			alert("No Parts Entered");
		}
	});
})(jQuery);
