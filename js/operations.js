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
		jQuery.fn.initSelect2 = function(load_url,limiter = ''){m 
			$(this).select2({
		        ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
		            url: load_url,
		            dataType: 'json',
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
			$("#left-side-main").ready(function(){
				var order_number = $("#order_body").attr("data-order-number");
				var order_type = $("#order_body").attr("data-order-type");
				var company = "0";
				$(document).on("change","#companyid",function() {
					company = $(this).val();
					$("#account_select").initSelect2("/json/freight-account-search.php",company);
					$("#bill_to").initSelect2("/json/address-picker.php");
					$("#ship_to").initSelect2("/json/address-picker.php");
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
					}
				});
				$(document).on("change load","#freight-carrier",function() {
					var carrier = ($("#freight-carrier :selected").attr('data-carrier-id'));
					$("#freight-services").val("Freight Services");
					$("#freight-services").children("option[data-carrier-id!='"+carrier+"']").hide();
					$("#freight-services").children("option[data-carrier-id='"+carrier+"']").show();
	
				});
			});
			
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
					}
				});
			});
			
			$(document).on("click",".forms_edit",function() {
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
					$(this).closest("tr").remove();
					$(this).closest("tr").next().remove();
				}
			});
			
	//		$(document).on("change","#companyid",function() {
	//			alert($(this).val());
	//		});
			
			$(document).on("click",".add_new_dropdown",function() {
			   alert("Modal happens here!");
			});
			
			//-------------------------- Page Save Button --------------------------
			$('#save_button').click(function() {
				alert('pressed');
				//Get page macro information
				var order_type = $(this).closest("body").attr("data-order-type"); //Where there is 
				var order_number = $(this).closest("body").attr("data-order-number");
				
				//Get General order information
				var userid = $("#sales-rep option:selected").attr("data-rep-id");
				var company = $("#companyid").val();
				
				//Submit the left hand side
				$.ajax({
					type: "POST",
					url: '/json/order-form-submit.php',
					data: {
						"sales-rep":userid,
						"companyid":company,
						"order_type":order_type,
		   		    	"order_number":order_number
						}, // serializes the form's elements.
					dataType: 'json',
					success: function(form) {
						alert("Successfully input: "+form.type);
					}
				});
			
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
				
				//Submit the right hand side
	/*			$.ajax({
					
					type: "POST",
					url: '/json/order-table-lsubmit.php',
					data: {
		   		    	"table_rows":submit,
						"order_type":order_type,
		   		    	"order_number":order_number
						}, // serializes the form's elements.
					dataType: 'json',
					success: function(input) {
						alert("Successfully input: "+input);
						window.location = "https://aaronventel-aaronventel.c9users.io/order_form.php?ps=";
					}
				});*/
			});
			//Cancel button?
			
	/*========================== Aaron - END Sales Order =========================*/
	
	/*=========================== BEGIN SHIPPING HOME ===========================*/
			$(".shipping_section_foot a").click(function() {
				if ($(this).text() == "Show more"){
					$(this).closest("body").children(".table-header").show();
					$(this).closest("div").addClass("shipping-dash-full");
					$(this).closest("div").children(".shipping_section_head").hide();
					$(this).closest("div").siblings(".shipping-dash").hide();
					$(this).closest("table").find(".overview").show();
					$(this).text("Show Less");
					$(this).closest("body").children("#view-head").show();
					if ($(this).closest(".shipping-dash").hasClass("sd-sales")){
						$("#view-head-text").text('Sales Orders');
						$(this).closest("body").find("button[data-value='Sales']").addClass("active");
						$(this).closest("body").find("button[data-value='Purchases']").removeClass("active");
					}
					else{
						$("#view-head-text").text('Purchase Orders');
						$(this).closest("body").find("button[data-value='Purchases']").addClass("active");
						$(this).closest("body").find("button[data-value='Sales']").removeClass("active");
					}
				}
				else{
					$(this).closest("body").children(".table-header").hide();
					$(this).closest("div").removeClass("shipping-dash-full");
					$(this).closest("table").find(".overview").hide();
					$(this).parents("body").find(".shipping_section_head").fadeIn("slow");
					$(this).closest("div").siblings(".shipping-dash").fadeIn("slow");
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
	
		$('td[id*=Ranges]').children().click(function() {
			$(this).siblings('button[class*=active]').toggleClass("active");
		});
		/*
		$('#shortDateRanges').hover(function(){
			//$(this).parent('td').removeClass("col-md-1");
	    	$(this).removeClass("col-md-1 btn-group");
			$(this).addClass("col-md-2 btn-group");
			$(this).next().removeClass("col-md-2 text-center");
			$(this).next().addClass("col-md-1 text-center");
			$(this).children('button[class*=center]').show();
		},function() {
			$(this).children('button[class*=center]').hide();
			$(this).removeClass("col-md-2 btn-group");
			$(this).addClass("col-md-1 btn-group");
			$(this).next().removeClass("col-md-1 text-center");
			$(this).next().addClass("col-md-2 text-center");
		});
		
		$('#dateRanges').hover(function(){
			$(this).parent().children('button[class*=center]').toggle();
			$(this).parent().removeClass("col-md-2 btn-group");
			$(this).parent().addClass("col-md-1 btn-group");
		});
	*/
		$('#YTD').click(function() {
			var year = new Date().getFullYear();
			var month = new Date().getMonth();
			month++;
			var day = new Date().getDate(2);
			day = ("0" + day).slice(-2);
			month = ("0" + month).slice(-2);
			var today = ''.concat(month).concat('/').concat(day).concat('/').concat(year);
			//alert('Day '.concat(today));
		    $(this).button('toggle');
		    $("input[name='START_DATE']").val('01/01/'.concat(year));
			$("input[name='END_DATE']").val(today);
		});
		$('#MTD').click(function() {
			var year = new Date().getFullYear();
			var month = new Date().getMonth();
			month++;
			var day = new Date().getDate();
			day = ("0" + day).slice(-2);
			month = ("0" + month).slice(-2);
			var today = ''.concat(month).concat('/').concat(day).concat('/').concat(year);
			var begin = ''.concat(month).concat('/01/').concat(year);
			//alert('Day '.concat(today));
		    $(this).button('toggle');
		    $("input[name='START_DATE']").val(begin);
			$("input[name='END_DATE']").val(today);
		});
		$('#Q1').click(function() {
			var year = new Date().getFullYear();
		    $(this).button('toggle');
		    $("input[name='START_DATE']").val('01/01/'.concat(year));
	   	    $("input[name='END_DATE']").val('03/31/'.concat(year));
		});
		$('#Q2').click(function() {
			var year = new Date().getFullYear();
		    $(this).button('toggle');
		    $("input[name='START_DATE']").val('04/01/'.concat(year));
	   	    $("input[name='END_DATE']").val('06/30/'.concat(year));
		});
		$('#Q3').click(function() {
			var year = new Date().getFullYear();
		    $(this).button('toggle');
		    $("input[name='START_DATE']").val('07/01/'.concat(year));
	   	    $("input[name='END_DATE']").val('09/30/'.concat(year));
		});
		$('#Q4').click(function() {
			var year = new Date().getFullYear();
		    $(this).button('toggle');
		    $("input[name='START_DATE']").val('10/01/'.concat(year));
	   	    $("input[name='END_DATE']").val('12/31/'.concat(year));
		});
			
		/*Aaron: Function for inventory ghosting*/
		$(".ghost_delete").click(function() {
			$(this).parents("tr").hide();
			$(this).parents("tr").find(".ghost_percent").val(0);
			$(this).parents("#ghost").find("#save_changes").trigger("click");
		});
	});