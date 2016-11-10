		jQuery.fn.initDatetimePicker = function (format,maxDate) {
		  	if (!format){
		  		format = false;
		  	}
		  	if (!maxDate){
		  		var maxDate = false;
		  	}

		           $(this).datetimepicker({
		               /* use font awesome icons instead of glyphicons. because i said so. */
		               icons: {
		                   time: 'fa fa-clock-o',
		                   date: 'fa fa-calendar',
		                   up: 'fa fa-chevron-up',
		                   down: 'fa fa-chevron-down',
		                   previous: 'fa fa-chevron-left',
		                   next: 'fa fa-chevron-right',
		                   today: 'fa fa-screenshot',
		                   clear: 'fa fa-trash',
		                   close: 'fa fa-close'
		               },
		               format: format,
		               maxDate: maxDate,
		           });
		   }
		jQuery.fn.initSelect2 = function(load_url,holder,limiter,active){ 
			$(this).select2({
		        ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
		            url: load_url,
		            dataType: 'json',
					placeholder: holder,
					/*delay: 250,*/
		            data: function (params) {
		                return {
		                    q: params.term,//search term
							page: params.page,
							limit: limiter
		                };
		            },
			        processResults: function (data, params) { // parse the results into the format expected by Select2.
			            // since we are using custom formatting functions we do not need to alter remote JSON data
						// except to indicate that infinite scrolling can be used
						params.page = params.page || 1;
			            return {
							results: $.map(data, function(obj) {
								//alert(obj.text);
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
					allowClear: true,
					cache: true
		        },
				escapeMarkup: function (markup) { return markup; },//let our custom formatter work
		        minimumInputLength: 0
		    });
		}
		$(document).ready(function() {
			

		// ======== Output the header clear for the padding on the page ========
			//get main header height
	        var height = $('header.navbar').height();
	        //get possible filter bar height
	        var heightOPT = 0;
	        if ($('.table-header').css("display")!='none'){
	        	heightOPT = $('.table-header').height();
	        }
	        var offset = height + heightOPT;
			
			
			$('body').css('padding-top', offset);
		
		//======================== End the header clear ========================

//==============================================================================
//============================== BEGIN ORDER FORM ==============================
//==============================================================================

		//========================= Left side main page =========================
		//Load the meta information panel, initialize the clickable fields, and
		//populate whatever fields are prefilled.
		
			$("#left-side-main").ready(function(){
				var order_number = $("#order_body").attr("data-order-number");
				var order_type = $("#order_body").attr("data-order-type");
				var company = "0";
				
				$(document).on("change","#ship_to",function() {
					$(this).parent().find("div").first().html($(this).find("select2-ship_to-container").attr("title"));
					// $(this).parent().find("#ship_to").find("option").text();
				});
				
				$(document).on("change","#companyid",function() {
					company = $(this).val();
					$("#account_select").initSelect2("/json/freight-account-search.php","Please Choose a company",company);
					$("#bill_to").initSelect2("/json/address-picker.php");
					$("#ship_to").initSelect2("/json/address-picker.php");
					$("#contactid").initSelect2("/json/contacts.php","Select a contact",company)
				});
				//Left Side Main output on load of the page
				$.ajax({
					type: "POST",
					url: '/json/order-creation.php',
					data: {
						"type": order_type,
						"number": order_number,
						"mode":'load'
						},
					dataType: 'json',
					success: function(right) {
						$("#left-side-main").append(right);
						$("#companyid").initSelect2("/json/companies.php");
						$("#account_select").initSelect2("/json/freight-account-search.php",company);
						$("#bill_to").initSelect2("/json/address-picker.php");
						$("#ship_to").initSelect2("/json/address-picker.php");
						$("#contactid").initSelect2("/json/contacts.php");

					}
				});
				$(document).on("change load","#freight-carrier",function() {
					var carrier = ($("#freight-carrier :selected").attr('data-carrier-id'));
					$("#freight-services").val("Freight Services");
					$("#freight-services").children("option[data-carrier-id!='"+carrier+"']").hide();
					$("#freight-services").children("option[data-carrier-id='"+carrier+"']").show();
	
				});
			});
			
		//======================== Right side page load ========================
		// This function outputs each of the items on the table, as well as the
		// old information from the database
		
			$("#right_side_main").ready(function(){
				var order_number = $("#order_body").attr("data-order-number");
				var order_type = $("#order_body").attr("data-order-type");
				
				$.ajax({
					type: "POST",
					url: '/json/order-table-out.php',
					data: {
						"type": order_type,
						"number": order_number,
		   		    	"mode":'load'
						}, // serializes the form's elements.
					dataType: 'json',
					success: function(result) {
						$('#right_side_main').append(result);
					}
				});
			});
		
		//Any time a field is clicked for editing, or double clicked at all, show
		//the easy output portion of the row, populated with the relevant updated pages.
			$(document).on("click",".forms_edit",function() {
				$(this).closest("tr").hide();
				$(this).closest("tr").next().show()
				.find("input[name='ni_date']").parent().initDatetimePicker('MM/DD/YYYY');
				$(this).closest("tr").next().show().find(".item_search").initSelect2("/json/part-search.php");
			});
			$(document).on("dblclick",".easy-output td",function() {
				$(this).closest("tr").hide();
				$(this).closest("tr").next().show()
				.find("input[name='ni_date']").parent().initDatetimePicker('MM/DD/YYYY');
				$(this).closest("tr").next().show().find(".item_search").initSelect2("/json/part-search.php");
			});
			
		    $(".item_search").initSelect2("/json/part-search.php");
	
		    //This function runs the method append and adds a row to the end of the table
			$(document).on("click","#NewSalesOrder",function() {
			    $(this).parent().siblings().children().show();
			    $(this).parent().hide();
			    $(this).parent().next().show();
			    var newLine = $(this).closest("table").find(".easy-output").last("td").text();
				if(!newLine){newLine = 0;}
			    $(this).parent().next().children().val(parseInt(newLine)+1);
			    $(this).closest("body").find(".lazy-entry").hide();
			    $(this).closest("body").find(".easy-output").show();
			});
			
			//Total Price output
			$(document).on("keyup","input[name=ni_qty], input[name=ni_price]",function(){
				var qty = ($(this).closest("tr").find("input[name=ni_qty]").val());
			    var price = ($(this).closest("tr").find("input[name=ni_price]").val());
			    var ext = qty*price;
			    var display = ext.toFixed(2);
				display = display.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
			    display = '$'+(display);
			    if (qty && price){
					$(this).closest("tr").find("input[name=ni_ext]").val(display);
			    }
			    else{
					$(this).closest("tr").find("input[name=ni_ext]").val("");
			    }
			});
			
			//Function to submit the individual line item edits
			$(document).on("click",".line_item_submit",function() {
				
				var new_search = $(this).closest("tr").find('.item-selected').find("option").last().val();
				var old_search = $(this).closest("tr").find('.item-selected').find("option").attr("data-search");
				var line_item_id = $(this).closest("tr").prev().data('record');

				//This line fixes the bug if the user exits the select2 prematurely   
				if(isNaN(new_search)){var search = old_search;}
				else{var search = new_search;}
			    var date = $(this).closest("tr").find("input[name=ni_date]").val();
	   		    var qty = $(this).closest("tr").find("input[name=ni_qty]").val();
			    var price = $(this).closest("tr").find("input[name=ni_price]").val();
	   		    var lineNumber = $(this).closest("tr").find("input[name=ni_line]").val();
	   		    var editRow = ((parseInt($(this).closest("tr").index())));
				$.ajax({
					type: "POST",
					url: '/json/order-table-out.php',
					data: {
		   		    	"line":lineNumber,
		   		    	"search":search,
		   		    	"date":date,
		   		    	"qty":qty,
		   		    	"unitPrice":price,
		   		    	"id":line_item_id,
		   		    	"mode":'update'
						},
					dataType: 'json',
					success: function(row_out) {
						$("#right_side_main").find("tr:nth-child("+editRow+")").replaceWith(row_out);
					}
				});
	
		    	$(this).closest(".lazy-entry").hide();
		    	$(this).closest("tr").prev(".easy-output").show();
			});
			
			//This function submits a new row
			$('#forms_submit').on("click", function() {
				var company = $("#").find('.item-selected').find("option").last().val();
			    var search = $(this).closest("tr").find('#item-selected').find("option").val();
			    var date = $(this).closest("tr").find("input[name=ni_date]").val();
	   		    var qty = $(this).closest("tr").find("input[name=ni_qty]").val();
			    var price = $(this).closest("tr").find("input[name=ni_price]").val();
	   		    var lineNumber = $(this).closest("tr").find("input[name=ni_line]").val();
				
	
				$.ajax({
					
					type: "POST",
					url: '/json/order-table-out.php',
					data: {
		   		    	"line":lineNumber,
		   		    	"search":search,
		   		    	"date":date,
		   		    	"qty":qty,
		   		    	"unitPrice":price,
		   		    	"id": 'new',
		   		    	"mode":'append'
						}, // serializes the form's elements.
					dataType: 'json',
					success: function(row_out) {
						$("#right_side_main").append(row_out);
					}
				});
	
				$(this).closest("tr").children("td:first-child").show();
				$(this).closest("tr").children("td").slice(1).children()
				.val("")
				.toggle();
				$(this).closest("tr").find("select").html("\
								<select class='item_search'>\
								</select>\
				")
			});
			
			//Delete Button
			$(document).on("click",".forms_trash",function() {
				if(confirm("Are you sure you want to delete this row?")){
					var id = $(this).closest("tr").data('record');
					var order_type = $("#order_body").attr("data-order-type");
					$(this).closest("tr").remove();
					$(this).closest("tr").next().remove();
					$.ajax({
						type: "POST",
						url: '/json/row_delete.php',
						data: {
							"id" : id,
							"order": order_type
							}, // serializes the form's elements.
						dataType: 'json',
					});
				}
			});
			

			$(document).on("change","#ship_to",function() {
				if($(this).val().indexOf("Add") > -1){
					$("#modal-address").modal('show');
				}
			});
			
			$(document).on("click", "#address-continue", function() {
			    var address = [];
			    $("#address-modal-body").find('input').each(function(){
			    	if($(this).val()){ 
			    		address.push($(this).val());
			    	}
			    	else{
			    		address.push('');
			    	}
			    });
			    $.post("/json/addressSubmit.php", {'test[]' : address} ,alert('GreatSuccess!'));
			    alert (address[0]);
			});
			
			$(document).on("click","#mismo",function() {
				if ( $(this).prop( "checked" )){
					var ship = $('#ship_to').val();
					alert(ship);
					$('#bill_to').val(ship);
					$('#bill_to').select2('disable')
				}
				else{
					$('#bill_to').select2('enable')
				}
			});
			
			//-------------------------- Page Save Button --------------------------
			$('#save_button').click(function() {
				//alert('pressed');
				//Get page macro information
				var order_type = $(this).closest("body").attr("data-order-type"); //Where there is 
				var order_number = $(this).closest("body").attr("data-order-number");
				
				//Get General order information
				var userid = $("#sales-rep option:selected").attr("data-rep-id");
				var company = $("#companyid").val();
				var contact = $("#contactid").val();
				var ship_to = $('#ship_to').val();
				var bill_to = $('#bill_to').val();
				var carrier = $('#carrier').val();
				var freight = $('#terms').val();
				var account = $('#account_select').val();
				var pri_notes = $('#private_notes').val();
				var pub_notes = $('#public_notes').val();
				
				//-------------------------- Right hand side --------------------------
				//Get Line items from the right half of the page
				var i = 0;
				var submit = [];
				var row = [];
				
				//This loop runs through the right-hand side and parses out the general values from the page
				$(this).closest("body").find("#right_side_main").children(".easy-output").each(function(){


					//For each element in a row
					$(this).children("td").each(function(){
						if (i != 1 && i < 6){
							//If it is one of the first six collumns, and not the "item" field, grab the text
							row.push($(this).text());
						}
						else if (i==1){
							//If it is the item field, grab the attribute, not the text.
							row.push($(this).attr("data-search"));
							row.push($(this).attr("data-record"));
						}
						else{
							//Move on to the next row once you get to the end, and clear out the row buffer.
							submit.push(row);
							row = [];
						}
						i++;
						i %= 8;
					});
				});


				//Submit All of it and simplify the shit out of your life.
				$.ajax({
					type: "POST",
					url: '/json/order-form-submit.php',
					data: {
						"sales-rep":userid,
						"companyid":company,
						"order_type":order_type,
		   		    	"order_number":order_number,
						"contact": contact,
						"ship_to": ship_to,
						"bill_to": bill_to,
						"carrier": carrier,
						"freight": freight,
						"account": account,
						"pri_notes": pri_notes,
						"pub_notes": pub_notes,
						"table_rows":submit,
						}, // serializes the form's elements.
					dataType: 'json',
					success: function(form) {
						var on = form["order"];
						var ps = form["type"];
						alert(form['stupid']);
						window.location = "/order_form.php?ps="+ps+"&on="+on;
					}
				});
			});
			//Cancel button?
  /*=============================================================================*/
 /*============================ Aaron - END ORDER FORM =========================*/
/*=============================================================================*/



  /*===========================================================================*/
 /*=========================== BEGIN SHIPPING HOME ===========================*/
/*===========================================================================*/

			$(".shipping_section_foot a").click(function() {
				if ($(this).text() == "Show more"){
					$('.col-lg-6').hide();
					$(this).closest("body").children(".table-header").show();
					$(this).closest(".col-lg-6").addClass("shipping-dash-full");
					
					$(this).closest(".shipping-dash").addClass("shipping-dash-remove");
					$(this).closest(".shipping-dash").removeClass("shipping-dash");
					
					$(".shipping-dash-full").fadeIn('fast');
					$('body').scrollTop('fast');
					
					var title = $(".shipping-dash-full .shipping_section_head").data('title');
					$(".shipping_section_head").hide();
					
					$("#view-head-text").text(title);
					
					$(this).closest("table").find(".overview").show();
					$(this).text("Show Less");
					$(this).closest("body").children("#view-head").show();
					// if ($(this).closest(".shipping-dash").hasClass("sd-sales")){
					// 	$("#view-head-text").text(title);
					// 	$(this).closest("body").find("button[data-value='Sales']").addClass("active");
					// 	$(this).closest("body").find("button[data-value='Purchases']").removeClass("active");
					// }
					// else{
					// 	$("#view-head-text").text('Purchase Orders');
					// 	$(this).closest("body").find("button[data-value='Purchases']").addClass("active");
					// 	$(this).closest("body").find("button[data-value='Sales']").removeClass("active");
					// }
				}
				else{
					$(this).closest("body").children(".table-header").hide();
					$(".shipping-dash-full").removeClass("shipping-dash-full");
					$(this).closest("table").find(".overview").hide();
					$(this).parents("body").find(".shipping_section_head").fadeIn("fast");
					$('.col-lg-6').show();
					
					$(this).closest(".shipping-dash-remove").addClass("shipping-dash");
					$(this).closest(".shipping-dash-remove").removeClass("shipping-dash-remove");
					
					//$(this).closest("div").siblings(".shipping-dash").fadeIn("slow");
					$(this).parents("body").find(".overview").hide();
					$(this).parents("body").children("#view-head").hide();
					$(this).parents("body").find(".shipping_section_foot a").text("Show more");
				}
			});
			
			$("#sales").click(function() {
				//If the current view is purchases...
				if( $(this).closest("body").find(".shipping-dash-full").hasClass("sd-purchases")){
					
					$(this).siblings("button").removeClass("active");
					$(this).addClass("active");
					$("#view-head-text").text("Sales Orders");
					//Determine if we should show the defauls to outstanding or recently closed
					var outstanding;
					
					if ($(this).closest("body").find(".shipping-dash-full").hasClass("sd-outstanding")){
						outstanding = true;
					}
					else{
						outstanding = false;
					}
	
					//Find the currently active full shipping dash and hide it, while toggling it back to standard view for later
					$(this).closest("body").find(".shipping-dash-full").hide()
					.removeClass("shipping-dash-full")
					.addClass("shipping-dash");
					
					//Depending on which preset was selected...
					if (outstanding){
						//Show the outstanding sales
						$(".sd-outstanding.sd-sales")
						.closest(".shipping-dash").show()
						.addClass("shipping-dash")
						.addClass("shipping-dash-full")
						.find(".shipping_section_head").hide();
						$(".sd-outstanding.sd-sales")
						.find("tfoot").find("a").text("Show Less");
						$(".sd-outstanding.sd-sales").find(".overview").show();
	
					}
					else{
						$(".sd-completed.sd-sales")
						.closest(".shipping-dash").show()
						.addClass("shipping-dash")
						.addClass("shipping-dash-full")
						.find(".shipping_section_head").hide();
						$(".sd-completed.sd-sales")
						.find("tfoot").find("a").text("Show Less");
						$(".sd-completed.sd-sales").find(".overview").show();				
						
					}
				}	
			});
			$("#purchases").click(function() {
				//If the current view is purchases...
				if( $(this).closest("body").find(".shipping-dash-full").hasClass("sd-sales")){
					
					$(this).siblings("button").removeClass("active");
					$(this).addClass("active");
					$("#view-head-text").text("Purchase Orders");
					//Determine if we should show the defauls to outstanding or recently closed
					var outstanding;
					
					if ($(this).closest("body").find(".shipping-dash-full").hasClass("sd-outstanding")){
						outstanding = true;
					}
					else{
						outstanding = false;
					}
	
					//Find the currently active full shipping dash and hide it, while toggling it back to standard view for later
					$(this).closest("body").find(".shipping-dash-full").hide()
					.removeClass("shipping-dash-full")
					.addClass("shipping-dash");
					
					//Depending on which preset was selected...
					if (outstanding){
						//Show the outstanding sales
						$(".sd-outstanding.sd-purchases")
						.closest(".shipping-dash").show()
						.addClass("shipping-dash")
						.addClass("shipping-dash-full")
						.find(".shipping_section_head").hide();
						$(".sd-outstanding.sd-purchases")
						.find("tfoot").find("a").text("Show Less");
						$(".sd-outstanding.sd-purchases").find(".overview").show();
	
					}
					else{
						$(".sd-completed.sd-purchases")
						.closest(".shipping-dash").show()
						.addClass("shipping-dash")
						.addClass("shipping-dash-full")
						.find(".shipping_section_head").hide();
						$(".sd-completed.sd-purchases")
						.find("tfoot").find("a").text("Show Less");
						$(".sd-completed.sd-purchases").find(".overview").show();				
						
					}
				}	
			});
//=========== END OF FUNCTION FOR THE SHIPPING HOME PAGE =======================


		
		
	});