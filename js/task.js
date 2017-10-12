function calculateCost(object){
	var container = object.closest(".found_parts_quote");

	var qty = 0;
	var amount = 0;
	var tax = 0;

	var quote = 0;

	if(container.find(".part_qty").val()) {
		qty = parseFloat(container.find(".part_qty").val());	
	}

	if(container.find(".part_amount").val()) {
		amount = parseFloat(container.find(".part_amount").val());	
	}

	if(container.find(".part_perc").val()) {
		tax = parseFloat(container.find(".part_perc").val());	
	}

	quote = (qty * amount) + (qty * amount * (tax / 100));

	container.find(".quote_amount").val(quote.toFixed(2));
}

function priceFormat(data) {
    var number = data.toString().split(".");

    number[0] = number[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");

    return number.join(".");
}

function addMonths(after = 1, now = new Date()) {
    var current;

    if (now.getMonth() == 11) {
        current = new Date(now.getFullYear() + 1, 0, now.getDate());
    } else {
        current = new Date(now.getFullYear(), now.getMonth() + 1, now.getDate());            
    }

    return (after == 1) ? current : addMonths(after - 1, new Date(now.getFullYear(), now.getMonth() + 1, now.getDate()))
}


function calculateTax(object){
	var container = object.closest(".part_listing");

	var qty = 0;
	var amount = 0;
	var tax = 0;

	var quote = 0;

	if(container.find(".part_qty").val()) {
		qty = parseFloat(container.find(".part_qty").val());	
	}

	if(container.find(".part_amount").val()) {
		amount = parseFloat(container.find(".part_amount").val());	
	}

	if(container.find(".quote_amount").val()) {
		quote = parseFloat(container.find(".quote_amount").val());	
	}

	// alert(qty + ' ' + amount + ' ' +tax);

	tax = ((quote - (qty * amount)) / (qty * amount)) * 100;

	container.find(".part_perc").val(tax);
}

(function($) {
	$(".datetime").each(function() {
		var container = $(this);

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
	});

	$(document).on("click", ".toggle-save", function(e){
		e.preventDefault();

		$("#save_form").submit();
	});

	$(document).on("click", ".quote_order, .create_order, .save_quote_order", function(e){
		e.preventDefault();

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
		});

		$("#save_form").submit();
	});

	$(document).on("click", ".create_order", function(e){
		e.preventDefault();

		var input = $("<input>")
		               .attr("type", "hidden")
		               .attr("name", "create").val("create");
		$('#save_form').append($(input));

		$("#save_form").submit();
	});

	$(document).on("change", ".part_amount, .part_qty, .part_perc", function(){
		calculateCost($(this));

		var total = 0;

		$('.quote_amount').each(function(){
			if($(this).val()) {
				total += parseFloat($(this).val());
			}
		});

		$('.materials_cost').html('$' + priceFormat(parseFloat(total).toFixed(2)));
	});

	$(document).on("change", ".quote_amount", function(){
		calculateTax($(this));

		var total = 0;

		$('.quote_amount').each(function(){
			total += parseFloat($(this).val());
		});

		$('.materials_cost').html('$' + priceFormat(parseFloat(total).toFixed(2)));
	});

	$(document).on("change", ".labor_hours, .labor_rate", function(){
		var total = 0.00;

		var hours = $('.labor_hours').val();
		var rate = $('.labor_rate').val();

		if(hours && rate) {
			total = hours * rate;
		}

		$('.labor_cost').html('$' + priceFormat(parseFloat(total).toFixed(2)));
	});

	$(document).on("click", ".os_expense_add", function(e){
		e.preventDefault();

		var container = $(this).closest(".outsourced_row");
		var line_number = container.data("line");
		line_number++;

		var object = container.clone().find("input").val("").end();
		object.attr("data-line", line_number);
		container.find(".os_expense_add").remove();
		container.find(".os_action").append('<i class="fa fa-trash fa-4 remove_outsourced pull-right" style="cursor: pointer; margin-top: 10px;" aria-hidden="true"></i>');

		object.find("input").each(function(){
       		$(this).attr("name", $(this).attr("name").replace(/\d+/, line_number) );
	    });

	    // Recreate the SELECT2 option
	    object.find(".select2_os").empty();
	    object.find(".select2_os").append('<select name="outsourced['+line_number+'][companyid]" class="form-control input-xs company-selector required"></select>');

	    object.find(".company-selector").select2({
	    	placeholder: '- Select a Company -',
	        ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
	            url: "/json/companies.php",
	            dataType: 'json',
				/*delay: 250,*/
	            data: function (params) {
	                return {
	                    scope: 'sale',
	                    add_custom: 1,
	                    q: params.term,//search term
						page: params.page
	                };
	            },
				allowClear: true,
		        processResults: function (data, params) { // parse the results into the format expected by Select2.
		            // since we are using custom formatting functions we do not need to alter remote JSON data
					// except to indicate that infinite scrolling can be used
					params.page = params.page || 1;
		            return {
						results: $.map(data, function(obj) {
							return { id: obj.id, text: obj.text };
						})
/*
						results: data.results,
						pagination: {
							more: (params.page * 30) < data.total_count
						}
*/
					};
				},
				cache: true
	        },
			escapeMarkup: function (markup) { return markup; },//let our custom formatter work
	        minimumInputLength: 0
	    });

		container.after(object);
	});

	$(document).on("click", ".remove_outsourced", function(){
		$(this).closest(".outsourced_row").remove();
	});
})(jQuery);