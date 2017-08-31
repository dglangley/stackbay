(function($){

	$(document).on("change", ".payment-type", function() {
		var placeholder = '';
		
		if($(this).val() == "Check") {
			placeholder = "Check #";
		} else if($(this).val() == "Wire Transfer") {
			placeholder = "Ref #";
		} else if($(this).val() == "Credit Card") {
			placeholder = "Appr Code";
		} else if($(this).val() == "Paypal") {
			placeholder = "Transaction #";
		} else {
			placeholder = "Other";
		}
		
        $('.payment-placeholder').attr('placeholder', placeholder);
    });

	// On new payment module
    $(document).on("click", ".payment_module", function(e){
    	e.preventDefault();

    	$('.payment-modal').empty();

    	var data = [];
    	var orders;

    	$(".payment_orders:checked").each(function() {
    		orders = {};

            orders.order_number = $(this).data("order");
            orders.type = $(this).data("type");

            data.push(orders);
        });

        // Load in the new payments module content dynamically
        $.ajax({
        	url: 'json/payments.php',
        	type: 'get',
            data: {'data': data},
            success: function(json, status) {
            	var rowHTML = '';
            	console.log(json);
            	$.each(json, function(order, data) {
            		rowHTML += '<h4>'+order+' <a style="font-size: 12px;" href="/SO110172"><i class="fa fa-arrow-right" aria-hidden="true"></i></a> </h4>';
            		rowHTML += "<table class='table table-hover table-striped table-condensed'><tbody>";


            		$.each(data, function(key, row) {
            			//console.log(row);
            			var row_amount = parseFloat(row.amount * row.qty);

            			rowHTML += "<tr class='payment_info'>\
										<td style='padding: 0px 10px;' class='col-md-3'>"+row.type+" "+row.invoice_no+"\
											<a target='_blank' href='/docs/INV"+row.invoice_no+".pdf'><i class='fa fa-file-pdf-o'></i></a>\
										</td>\
										<td style='padding: 0px 10px;' class='col-md-4'><span class='pull-right'>$ "+Number(row_amount).toLocaleString("en", {minimumFractionDigits: 2})+"</span></td>\
										<td style='padding: 0px 10px;' class='col-md-4'>\
											<input type='text' class='payment_amount form-control input-sm pull-right' value='"+row_amount.toFixed(2)+"' name='payment_orders["+row.order_type+"."+order+"]["+row.type+"."+row.invoice_no+"][amount]' style='max-width: 124px; text-align: right;'>\
										</td>\
										<td style='padding: 0px 10px;' class='col-md-1'><div class='checkbox pull-right'><input type='checkbox' name='payment_orders["+row.order_type+"."+order+"]["+row.type+"."+row.invoice_no+"][check]' class='payment_check' checked></div></td>\
									</tr>";
					});

					rowHTML += "</tbody></table>";
				});

				$('.payment-modal').append(rowHTML);

				// Append the total value instantly to the top on load of the modal
				var total = 0;
				$('.payment_amount').each(function(){
					total += parseFloat($(this).val());
				});

				$('#modal-payment .total_amount').val(total.toFixed(2));
            },
	        error: function(xhr, desc, err) {
	            console.log("Details: " + desc + "\nError:" + err);
	        }
        });

        //console.log(data);
        $('#modal-payment').modal('show');

    });

    // On edit payment module
    $(document).on("click", ".paid-data", function(e){
    	e.preventDefault();

    	var paymentid = $(this).data("paymentid");

    	$('.payment-modal').empty();

    	$.ajax({
        	url: 'json/payments.php',
        	type: 'get',
            data: {'paymentid': paymentid},
            success: function(json, status) {
            	var rowHTML = '';
            	var payment_type;
            	var payment_id;
            	var payment_date;
            	var payment_total;
            	var payment_notes = '';

            	console.log(json);
            	$.each(json, function(order, data) {
            		rowHTML += '<h4>'+order+' <a style="font-size: 12px;" href="/SO110172"><i class="fa fa-arrow-right" aria-hidden="true"></i></a> </h4>';
            		rowHTML += "<table class='table table-hover table-striped table-condensed'><tbody>";


            		$.each(data, function(key, row) {
            			//console.log(row);
            			var row_amount = parseFloat(row.amount);

            			payment_type = row.payment_type;
            			payment_id = row.number;
            			payment_date = new Date(row.date);
            			payment_total = row.total;
            			payment_notes = row.notes;

            			rowHTML += "<tr class='payment_info'>\
										<td style='padding: 0px 10px;' class='col-md-3'>"+row.ref_type+" "+row.ref_number+"\
											<a target='_blank' href='/docs/INV"+row.ref_number+".pdf'><i class='fa fa-file-pdf-o'></i></a>\
										</td>\
										<td style='padding: 0px 10px;' class='col-md-4'><span class='pull-right'>$ "+Number(row.invoice_amount).toLocaleString("en", {minimumFractionDigits: 2})+"</span></td>\
										<td style='padding: 0px 10px;' class='col-md-4'>\
											<input type='text' class='payment_amount form-control input-sm pull-right' value='"+row_amount.toFixed(2)+"' name='payment_orders["+row.order_type+"."+order+"]["+row.ref_type+"."+row.ref_number+"][amount]' style='max-width: 124px; text-align: right;'>\
											<input type='hidden' name='paymentid' value='"+row.paymentid+"'>\
										</td>\
										<td style='padding: 0px 10px;' class='col-md-1'><div class='checkbox pull-right'><input type='checkbox' name='payment_orders["+row.order_type+"."+order+"]["+row.ref_type+"."+row.ref_number+"][check]' class='payment_check' checked></div></td>\
									</tr>";
					});

					rowHTML += "</tbody></table>";
				});

				$('.payment-modal').append(rowHTML);

				// Append the total value instantly to the top on load of the modal
				var total = 0;
				$('.payment_amount').each(function(){
					total += parseFloat($(this).val());
				});

				$('#modal-payment .total_amount').val(total.toFixed(2));
				$('#modal-payment .payment-type').val(payment_type);
				$('#modal-payment .total_amount').val(payment_total);
				$('#modal-payment .payment-placeholder').val(payment_id);
				$('#modal-payment .notes').val(payment_notes);
				$('#modal-payment .datetime-picker-line input').val((payment_date.getMonth() + 1) + '/' + (payment_date.getDate() + 1) + '/' +  payment_date.getFullYear());
            },
	        error: function(xhr, desc, err) {
	            console.log("Details: " + desc + "\nError:" + err);
	        }
        });

    	$('#modal-payment').modal('show');
    });

    $(document).on("click", ".payment_check", function(){
    	var total = 0;
    	$('.payment_check:checked').each(function(){
    		total += parseFloat($(this).closest('.payment_info').find('.payment_amount').val());
    	});

    	$('#modal-payment .total_amount').val(parseFloat(total).toFixed(2));
    });

    $(document).on("change", ".payment_amount", function(){
    	var total = 0;
    	$('.payment_check:checked').each(function(){
    		total += parseFloat($(this).closest('.payment_info').find('.payment_amount').val());
    	});

    	$('#modal-payment .total_amount').val(parseFloat(total).toFixed(2));
    });
	
})(jQuery);