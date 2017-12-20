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

function reCalcTotal(){
	var currency = 0;
	var total = 0;

	var labor = 0;
	var material = 0;
	var outside = 0;
	var expense = 0;

	if($('.labor_cost')) {
		currency = $('.labor_cost').text()
		labor = Number(currency.replace(/[^0-9\.-]+/g,""));
	}
	if($('.materials_cost')) {
		currency = $('.materials_cost').text();
		material = Number(currency.replace(/[^0-9\.-]+/g,""));
	}
	if($('.outside_cost')) {
		currency = $('.outside_cost').text();
		outside = Number(currency.replace(/[^0-9\.-]+/g,""));
	}
	if($('.expenses_cost')) {
		currency = $('.expenses_cost').text();
		expense = Number(currency.replace(/[^0-9\.-]+/g,""));
	}

	total = labor + material + outside + expense;

	// alert(labor + ' ' + $('.materials_cost').text() + ' ' + outside + ' ' + expense);
}

// Collision detection between 2 DIVs
function collision($div1, $div2) {
	var x1 = $div1.offset().left;
	var y1 = $div1.offset().top;
	var h1 = $div1.outerHeight(true);
	var w1 = $div1.outerWidth(true);
	var b1 = y1 + h1;
	var r1 = x1 + w1;
	var x2 = $div2.offset().left;
	var y2 = $div2.offset().top + 100;
	var h2 = $div2.outerHeight(true);
	var w2 = $div2.outerWidth(true);
	var b2 = y2 + h2;
	var r2 = x2 + w2;

	if (b1 < y2 || y1 > b2 || r1 < x2 || x1 > r2) return false;
	return true;
}

function calculateTax(object){
	var container = object.closest("tr");

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

		var repair_code = $('#repair_code_select').val();

		if(repair_code) {
			var input = $("<input>").attr("type", "hidden").attr("name", "repair_code_id").val(repair_code);
			//console.log(input);
			$('#save_form').append($(input));
		}

		$('#save_form').submit();
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

	// $(document).on("click", ".create_order", function(e){
	// 	e.preventDefault();

	// 	var input = $("<input>")
	// 	               .attr("type", "hidden")
	// 	               .attr("name", "create").val("create");
	// 	$('#save_form').append($(input));

	// 	$("#save_form").submit();
	// });

	// Calculate the cost and profit on the OS
	$(document).on("change", ".os_amount, .os_amount_profit", function(){
		var container = $(this).closest('.outsourced_row');
		
		var amount = 0;
		var tax = 0;

		var total = 0;

		if(container.find(".os_amount").val()) {
			amount = parseFloat(container.find(".os_amount").val());	
		}

		if(parseFloat(container.find(".os_amount_profit").val())) {
			tax = parseFloat(parseFloat(container.find(".os_amount_profit").val()));	
		}

		total = (amount) + (amount * (tax / 100));

		container.find(".os_amount_total").val(parseFloat(total).toFixed(2));
	});

	// Calcuate the cost and the profit / qty on the materials view for quotes
	$(document).on("change", ".part_amount, .part_qty, .part_perc", function(){
		calculateCost($(this));

		var total = 0;

		$('.quote_amount').each(function(){
			if($(this).val()) {
				total += parseFloat($(this).val());
			}
		});

		$('.materials_cost').html('$' + priceFormat(parseFloat(total).toFixed(2)));
		reCalcTotal();
	});

	$(document).on("change", ".quote_amount", function(){
		calculateTax($(this));

		var total = 0;

		$('.quote_amount').each(function(){
			if($(this).val()) {
				total += parseFloat($(this).val());
			}
		});

		$('.materials_cost').html('$' + priceFormat(parseFloat(total).toFixed(2)));
		reCalcTotal();
	});

	$(document).on("change", ".labor_hours, .labor_rate", function(){
		var total = 0.00;

		var hours = $('.labor_hours').val();
		var rate = $('.labor_rate').val();

		if(hours && rate) {
			total = hours * rate;
		}

		$('.labor_cost').html('$' + priceFormat(parseFloat(total).toFixed(2)));
		reCalcTotal();
	});

	$(document).on("click", ".os_expense_add", function(e){
		e.preventDefault();

		var container = $(this).closest(".outsourced_row");
		var line_number = container.data("line");
		line_number++;

		var object = container.clone().find("input").val("").end();
		object.attr("data-line", line_number);
//dl 12-18-17 with andrew, found this to be blocking the submit of the Save button
//		container.find(".os_expense_add").remove();
		container.find(".os_action").append('<i class="fa fa-trash fa-4 remove_outsourced pull-right" style="cursor: pointer; margin-top: 10px;" aria-hidden="true"></i>');

		object.find("input").each(function(){
       		$(this).attr("name", $(this).attr("name").replace(/\d+/, line_number) );
	    });

	    // Recreate the SELECT2 option
	    object.find(".select2_os").empty();
	    object.find(".select2_os").append('<select name="outsourced['+line_number+'][companyid]" class="form-control input-xs company-selector required"></select>');

		object.find(".company-selector").selectize("/json/companies.php","- Select a Company -");

		container.after(object);
		$(this).addClass('hidden');
	});

	$(document).on("click", ".remove_outsourced", function(){
		$(this).closest(".outsourced_row").remove();
	});

	$(document).on('click', ".upload_link", function(e){
        e.preventDefault();

        $(this).closest(".file_container").find(".upload").trigger("click");
        // $("#upload:hidden").trigger('click');
    });

	$(document).on("change", ".upload", function(){
		var f_file =  $(this).val();
	    var fileName = f_file.match(/[^\/\\]+$/);

		$(this).closest(".file_container").find(".file_name").text(fileName);
	});
	
	$(document).on("click", ".forward_activity", function() {
		var activityid = $(this).data('activityid');

		if(activityid) {
			var input = $("<input>").attr("type", "hidden").attr("name", "activity_notification").val(activityid);
			//console.log(input);
			$('#save_form').append($(input));
		}

		$('#save_form').submit();
	});

	// window.setInterval(function() {
	//     $('#result').text(collision($('#pad-wrapper'), $('#sticky-footer')));
	// }, 500);

})(jQuery);
