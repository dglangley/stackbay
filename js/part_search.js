(function($){
	// Create a generic funtion to invoke the ajax call for part searches
	function partSearch(search, filter) {
		var type = $('body').data("order-type");
		console.log(window.location.origin+"/json/part_search.php?search="+escape(search));
		$.ajax({
	        url: 'json/part_search.php',
	        type: 'get',
	        dataType: "json",
	        data: {'search': search, 'filter': filter},
	        success: function(json) {
	        	$(".found_parts").remove();

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
						rowHTML += '<tr class="found_parts">';
						rowHTML += '	<td class="part"><span class="descr-label">'+row.part+ ' ' +heci+' </span><div class="description desc_second_line descr-label" style="color:#aaa;"><span class="description-label">'+description+'</span></div></td>';
						rowHTML += '	<td><input class="form-control input-sm part_qty" type="text" name="qty" data-partid="'+row.id+'" data-stock="'+row.stock+'" placeholder="QTY" value=""></td>';
						rowHTML += '	<td><input class="form-control input-sm" type="text" name="amount" data-partid="'+row.id+'" data-stock="'+row.stock+'" placeholder="0.00" value=""></td>';
						rowHTML += '	<td>\
											<div class="col-md-4 remove-pad">\
												<input class="form-control input-sm" type="text" name="leadtime" data-partid="'+row.id+'" data-stock="'+row.stock+'" placeholder="#" value="">\
											</div>\
											<div class="col-md-4">\
												<select class="form-control input-sm">\
													<option value="day">Days</option>\
													<option value="week">Weeks</option>\
													<option value="month">Months</option>\
												</select>\
											</div>\
											<div class="col-md-4 remove-pad">\
												<div class="form-group" style="margin-bottom: 0; width: 100%;">\
													<div class="input-group datepicker-date date datetime-picker" style="min-width: 100%; width: 100%;" data-format="MM/DD/YYYY">\
											            <input type="text" name="delivery_date" class="form-control input-sm" value="">\
											            <span class="input-group-addon">\
											                <span class="fa fa-calendar"></span>\
											            </span>\
											        </div>\
												</div>\
											</div>\
										</td>';
						rowHTML += '	<td><div class="form-group" style="margin-bottom: 0;">\
											<div class="input-group">\
									            <input type="text" name="profit_perc" class="form-control input-sm" value="">\
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
									            <input type="text" name="profit_perc" class="form-control input-sm" value="">\
									        </div>\
										</div></td>';
						rowHTML += '<td></td>';
						rowHTML += '</tr>';
					} else {
						rowHTML += '<tr class="found_parts">';
						rowHTML += '	<td class="part"><span class="descr-label">'+row.part+ ' ' +heci+' </span><div class="description desc_second_line descr-label" style="color:#aaa;"><span class="description-label">'+description+'</span></div></td>';
						rowHTML += '	<td><input class="form-control input-sm part_qty" type="text" name="qty" data-partid="'+row.id+'" data-stock="'+row.stock+'" placeholder="QTY" value=""></td>';
						rowHTML += '	<td class="stock">'+stock+'</td>';
						rowHTML += '</tr>';
					}
				});

				if(type == 'quote') {
					$('#quote_input').after(rowHTML);
				} else {
					$('#search_input').append(rowHTML);
				}
	        },
	        error: function(xhr, desc, err) {
	            console.log("Details: " + desc + "\nError:" + err);
	        }
	    }); // end ajax call
   	}

   	// This function just clones what the user selected and makes a header in the main table
   	function createListings(){
		var hasElements = false;

		// Universal determine what type of screen the taskview is currently
		var type = $('body').data("order-type");

		$(".part_qty").each(function(){
			var qty = $(this).val();

			if(qty > 0) {
				if(type == 'quote') {
					$(this).closest(".found_parts").clone().removeClass("found_parts").addClass("part_listing").append('<td class="remove_part" style="cursor: pointer;"><i class="fa fa-trash fa-4" aria-hidden="true"></i></td>').prependTo("#quote_body");
				} else {
					$(this).closest(".found_parts").clone().removeClass("found_parts").addClass("part_listing").append('<td class="remove_part" style="cursor: pointer;"><i class="fa fa-trash fa-4" aria-hidden="true"></i></td>').prependTo("#search_input");
				}

				$(".part_listing").find(".stock").remove();
				if(type != 'quote') {
					$(".part_listing").find("input").prop("readonly", true);
				}

				hasElements = true;
			}
		});
	
		if(! hasElements) {
			alert("No Component or QTY found.");
		} else {
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

		var partid = $(this).data("partid");
		var itemid = $(this).data("itemid");
		var type = $(this).data("type");

		var partObject = $(this).closest(".list").find(".part_description").clone();
		var request = $(this).closest(".list").find(".part_description").data("request");

		pullComponents(partid, itemid, type, partObject, request);
	});

   	// Quick cleanup on new request
   	$(document).on("click", ".modal_request", function(){
   		$(".part_listing").remove();
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

	$(document).on("keydown",".part_qty, .found_parts input",function(e){
		if (e.keyCode == 13) {
			//alert('here');
			e.preventDefault();
			createListings();
		}
	});

	$(document).on("click", "#part_entry", function(){
		createListings();
	});

	$(document).on("click", ".remove_part", function(e){
		$(this).closest(".part_listing").remove();
	});

	$(document).on("click", ".toggle-save", function(e){
		e.preventDefault();

		$("#save_form").submit();
	});

	//Create middle modal to show the tech avaiable components and the option to partial / request or fulfill the request straight from the inventory
	$(document).on("click", ".stock_check", function(e) {
		var data = [];
		var hasElements = false;

		$(".part_listing").each(function(){
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