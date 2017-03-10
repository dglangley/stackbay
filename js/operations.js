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
		jQuery.fn.initSelect2 = function(load_url,holder,args,active){ 
			console.log("init initSelect2: "+load_url);
			$(this).select2({
				placeholder: holder,
		        minimumInputLength: 0,
		        ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
		            url: load_url,
		            dataType: 'json',
					/*delay: 250,*/
		            data: function (params) {
						var q = '',page = '';
						if (params.term) { q = params.term; }
						if (params.page) { page = params.page; }
						var log_url = load_url+"?q="+q+"&page="+page;

						// The following section updated by David 2/13/17 to accommodate multiple arguments being
						// passed in under 'args' (formerly 'limit'); we're honoring 'limit' as the default variable
						// sent by this function's data parameter, but if multiple arguments are sent in then it's
						// handled as an object of key/value pairs.
						var formObject = {
							q: q,
							page: page,
						}

						// if 'args' is passed in as a single variable/element, add to 'formObject'; if already an
						// object, append all elements (respecting key names) to 'formObject'
						if (typeof args === 'object') {
							for (var key in args) {
								formObject[key] = args[key];
								log_url += "&"+key+"="+args[key];
							}
						} else if (args) {
							formObject['limit'] = args;//single element, intended to be sent as 'limit' variable
							log_url += "&limit="+args;
						}
						console.log("initSelect2: "+log_url);

						// append addl args to form data
						return formObject;
/*
		                return {
		                    q: params.term,//search term
							page: params.page,
							limit: limiter
		                };
*/
		            },
			        processResults: function (data, params) { // parse the results into the format expected by Select2.
			            // since we are using custom formatting functions we do not need to alter remote JSON data
						// except to indicate that infinite scrolling can be used
						params.page = params.page || 1;
			            return {
							results: $.map(data, function(obj) {
								//alert(obj.text);
								return { 
									id: obj.id, 
									text: obj.text
								};
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
				escapeMarkup: function (markup) { return markup; }//let our custom formatter work
		    });
		}
		jQuery.fn.setDefault = function (string,id){
			var option = $('<option></option>').
				prop('selected',true).
				text(string).
				val(id);
			// alert($(this).html());
			$(this).html(option);//insert pre-selected option into select menu
			// initialize the change so it takes effect
			$(this).trigger("change");
		}


		$(document).ready(function() {

			// This is our global for all functions, all day baby
			var order_type = '';
			order_type = $("body").attr("data-order-type");
			if(order_type == "Purchase"){
				var receiver_companyid = '25';//ventel id
			}
			else{
				var receiver_companyid = $("#companyid").val();
			}

			if ($("#right_side_main").length > 0) {
				var order_number = $("#order_body").data("order-number");
				var rtv_array = $("#right_side_main").data("rtvarray");
				var mode = '';
				
				if(!$.isEmptyObject(rtv_array)) {
					mode = 'rtv';
				} else {
					mode = 'load';
				}

				console.log(window.location.origin+"/json/order-table-out.php?mode=load&number="+order_number+"&type="+order_type);

				$.ajax({
					type: "POST",
					url: '/json/order-table-out.php',
					data: {
						"type": order_type,
						"number": order_number,
						"rtv_array": rtv_array,
		   		    	"mode": mode
						}, // serializes the form's elements.
					dataType: 'json',
					success: function(result) {
						$('#right_side_main').append(result);
						rows = $(".easy-output").length;
						$(".datetime-picker-line").initDatetimePicker("MM/DD/YYYY");
						// var lineNumber = parseInt($(".multipart_sub").closest("tr").find("input[name=ni_line]").val());
						// auto-populate next line number to the search row line
						$(".search_row").find('input[name="ni_line"]').val(line_number());
						if($(".easy-output").length > 0){
							$('#totals_row').find("input[name='np_total']").val(updateTotal());
							$('#totals_row').show();
						}
					},					
					error: function(xhr, status, error) {
						console.log("JSON | Initial table load | order table out.php: "+error);
					}
				});
			}

			// ======== Output the header clear for the padding on the page ========
			//get main header height
			
			$( window ).resize(function() {
		        headerOffset();
			});

			$.when(headerOffset()).then(function(){
				$('.loading_element').css('visibility','visible').hide().fadeIn();
			});
			//======================== End the header clear ========================

			//==============================================================================
			//============================== BEGIN ORDER FORM ==============================
			//==============================================================================

			//========================= Left side main page =========================
			//Load the meta information panel, initialize the clickable fields, and
			//populate whatever fields are prefilled. THIS WILL WORK ACROSS MULTIPLE PAGES!
			
			$(".left-side-main").each(function(){
				var order_number = 'new';
				var page = 'order';
				
				order_number = $("body").attr("data-order-number");
				page = $(".left-side-main").attr("data-page");
				
				console.log(window.location.origin+"/json/operations_sidebar.php?number="+order_number+"&type="+order_type+"&page="+page);

				if (page == 'order'){
					
					
					var company = $("#companyid").val();


					//Initialize each of the select2 fields when the left side loads.
					$("#companyid").initSelect2("/json/companies.php", "Select Company",{"scope":order_type});
					$("#bill_to").initSelect2("/json/address-picker.php","Select Address", receiver_companyid);
					$("#account_select").initSelect2("/json/freight-account-search.php","PREPAID",{"limit":receiver_companyid,"carrierid":$("#carrier").val()});
					$("#ship_to").initSelect2("/json/address-picker.php","Select Address", receiver_companyid);
					if($("#ship_to").val() == $("#bill_to").val()){
						$("#mismo").prop("checked",true);

					}
					$(".contact-selector").initSelect2("/json/contacts.php",'Select Contact',company);

				}
				else{
					$("#order_selector").initSelect2("/json/order-select.php","Select Order",order_type);
				}
			});/* END .left-side-main */
			
			//Auto Focus Correct for the Public Notes
			if (order_type == 'Purchase'){
				$("#public_notes").keyup(function(e){
				    if (e == '9') {
				    	e.preventDefault();
    					;
    				}	
				});
			}
			// This checks for a change in the company select2 on the sidebar and adds in the respective contacts to match the company
			$(document).on("keyup","#search_input > tr > td > input, #search_input > tr.search_row > td:nth-child(7) > div > input",function() {
				var qty = 0;
				$.each($(".search_lines"),function(){
					var s_qty = ($(this).find("input[name=ni_qty]").val());
					if (s_qty){
						qty += (parseInt($(this).find("input[name=ni_qty]").val()));
					}
				});
				var price = parseFloat($(".search_row").find("input[name=ni_price]").val());
				if (!isNaN(price) && !isNaN(qty)){
					var display = price_format(qty * price);
				}
				else{
					var display = '0.00';
				}
				$("tfoot").find("input[name=ni_ext]").val(display);
				$("#search_input > tr.search_row > td:nth-child(6) > input").val(qty);
			});
			$(document).on("change","#order_selector",function() {
				// alert(order_type);
				var change = ($(this).val());
				if(order_type == 'Purchase'){
					window.location = "/inventory_add.php?on="+change;
				}
				else{
					window.location = "/shipping.php?on="+change;
				}
			});	

			/* David, Flame Broiler is at stake */
			$(document).on("click",".btn-order-upload",function() {
				$("#order-upload").click();
			});
			var orderUploadFiles;
			$(document).on("change","input#order-upload",function(e) {
				orderUploadFiles = e.target.files;

				// get new upload file name
				var upload_file = $(this).val().replace("C:\\fakepath\\","");

				// change "Customer Order:" label with name of upload file, and color with primary text
				var order_label = $("#customer_order").find("label[for='assoc']");
				order_label.html(upload_file);
				order_label.prop('class','text-info');

				// change icon on upload button as additional indicator of successful selection
				$(".btn-order-upload").html('<i class="fa fa-file-text"></i>');
			});
	
		
			//If the company information changes, run
			$(document).on("change","#companyid",function() {
				//Check to see if an order number exists or is this a new order
				var po_number = getUrlParameter('on');
				var company = $(this).val();
				// update global
				if(order_type == "Purchase"){
					var receiver_companyid = '25';//ventel id
				}
				else{
					var receiver_companyid = company;
				}

				var carrier = $("#carrier").val();
				// alert("Limit: "+company+" | Carrier "+carrier);
				
				// alert(id);
				$(".contact-selector").initSelect2("/json/contacts.php","Select Contact",company);
				
				$("#bill_to").initSelect2("/json/address-picker.php",'Select Address',company);
				$("#ship_to").initSelect2("/json/address-picker.php",'Select Address',company);

				
				//Default selector for the addresses
	    		console.log('/json/address-default.php?company='+company+'&order='+order_type);
				$.ajax({
					type: "POST",
					url: '/json/address-default.php',
					data: {
						"company": company,
						"order" : order_type
						},
					dataType: 'json',
					success: function(right) {
						
							var bvalue = right['b_value'];
							
//							$("#select2-bill_to-container").html("");
							if (bvalue){
								var bstring = right['b_street'];
//								var useful = right['b_street']+'<br>'+right['b_city']+', '+right['b_state']+' '+right['b_postal_code'];
//					    		$("#select2-bill_to-container").html(useful);
//					    		$("#bill_to").append("<option selected value='"+bvalue+"'>"+bstring+"</option>");
								var option = $('<option></option>').
									prop('selected',true).
									text(bstring).
									val(bvalue);
								option.appendTo($("#bill_to"));//insert pre-selected option into select menu
								// initialize the change so it takes effect
								$("#bill_to").trigger("change");
							}
			    			console.log("bdisplay: "+bstring);
							var svalue = right['s_value'];
//							$("#select2-ship_to-container").html("");
							if (svalue){
								var sstring = right['s_street'];
//								var useful = right['s_street']+'<br>'+right['s_city']+', '+right['s_state']+' '+right['s_postal_code'];
//					    		$("#select2-ship_to-container").html(useful);
//					    		$("#ship_to").append("<option selected value='"+svalue+"'>"+sstring+"</option>");
								var option = $('<option></option>').
									prop('selected',true).
									text(sstring).
									val(svalue);
								option.appendTo($("#ship_to"));//insert pre-selected option into select menu
								// initialize the change so it takes effect
								$("#ship_to").trigger("change");
							}

			    		console.log("JSON address-default.php: Success");
					},					
					error: function(xhr, status, error) {
						alert(error+" | "+status+" | "+xhr);
						console.log("JSON address-default.php: Error");
					},
					complete: function(jqXHR,textStatus) {
						$("#account_select").initSelect2("/json/freight-account-search.php","PREPAID",{"limit":receiver_companyid,"carrierid":carrier});
					},
				});


				//Account default picker on update of the company

				console.log(window.location.origin+"/json/account-default.php?"+"company="+receiver_companyid+"&carrier="+carrier);
				$.ajax({
					type: "POST",
					url: '/json/account-default.php',
					data: {
						"company": receiver_companyid,
						"carrier": carrier,
						},
					dataType: 'json',
					success: function(right) {
						// alert(company);
						var value = right['value'];
						var display = right['display'];
						var set_carrier = right['carrier'];
			    		if (set_carrier){
			    			$("#carrier").val(set_carrier);
			    		}

						var option = $('<option></option>').
							prop('selected',true).
							text(display).
							val(value);
						option.appendTo($("#account_select"));//insert pre-selected option into select menu
						// initialize the change so it takes effect
						$("#account_select").trigger("change");
						console.log("JSON account-default.php: Success");
					},					
					error: function(xhr, status, error) {
						alert(error+" | "+status+" | "+xhr);
						console.log("JSON account-default.php: Error");
					}
				}).done(function(right) {
					$.ajax({
						type: "POST",
						url: '/json/dropPop.php',
						data: {
							"field":"services",
							"limit": $("#carrier").val(),/*new_account,*/
							"size": "col-sm-8",
							"label": "Service",
							"id" : "service"
							}, // serializes the form's elements.
						dataType: 'json',
						success: function(result) {
							var initial_result = $("#service").val();
							var initial_days = $("#service").find("[value='"+initial_result+"']").attr("data-days");
							$('#service_div').replaceWith(result);
							var new_div_val = $('#service').find("[data-days='"+initial_days+"']").val();
							if (new_div_val){
								$("#service").val(new_div_val);
							}
							console.log("== CARRIER CHANGE VALUES ==");
							console.log("Initial ID: "+initial_result);
							console.log("Initial Days: "+initial_days);
							console.log("New ID: "+new_div_val);
							var days = parseInt($("#service :selected").attr("data-days"));
							if(!isNaN(days)){
								$("input[name=ni_date]").val(freight_date(days));
							}
							console.log("JSON Services limited dropPop.php: Success");
							},					
							error: function(xhr, status, error) {
								alert(error+" | "+status+" | "+xhr);
								console.log("JSON Services limited dropPop.php: Error");
							}
						});
				});

				$.ajax({
					type: "POST",
					url: '/json/dropPop.php',
					data: {
						"field":"terms",
						"limit":company,
						"size": "col-sm-5",
						"label": "Terms"
						}, // serializes the form's elements.
					dataType: 'json',
					success: function(result) {
						//Run this if this is a new PO otherwise the items are preset and we don't want to change terms
						if(po_number == null)
							$('#terms_div').replaceWith(result);
						console.log("JSON company terms dropPop.php: Success");
					},					
					error: function(xhr, status, error) {
						alert(error+" | "+status+" | "+xhr);
						console.log("JSON company terms dropPop.php: Error");
					}
				});
			});

			$(document).on("change","#carrier",function() {
				var limit = $(this).val();

				$("#account_select").initSelect2("/json/freight-account-search.php","PREPAID",{"limit":receiver_companyid,"carrierid":limit});

				console.log(window.location.origin+"/json/account-default.php?"+"company="+receiver_companyid+"&carrier="+limit);
				$.ajax({
					type: "POST",
					url: '/json/account-default.php',
					data: {
						"company": receiver_companyid,
						"carrier": limit,
						},
					dataType: 'json',
					success: function(right) {
						var value = right['value'];
						var display = right['display'];
						var set_carrier = right['carrier'];
			    		if (set_carrier){
			    			$("#carrier").val(set_carrier);
			    		}

						var option = $('<option></option>').
							prop('selected',true).
							text(display).
							val(value);
						option.appendTo($("#account_select"));//insert pre-selected option into select menu
						// initialize the change so it takes effect
						$("#account_select").trigger("change");
						console.log("JSON account-default.php: Success");
					},					
					error: function(xhr, status, error) {
						alert(error+" | "+status+" | "+xhr);
						console.log("JSON account-default.php: Error");
					}
				});
				
				$.ajax({
					type: "POST",
					url: '/json/dropPop.php',
					data: {
						"field":"services",
						"limit":limit,
						"size": "col-sm-8",
						"label": "Service",
						"id" : "service"
						}, // serializes the form's elements.
					dataType: 'json',
					success: function(result) {
						var initial_result = $("#service").val();
						var initial_days = $("#service").find("[value='"+initial_result+"']").attr("data-days");
						$('#service_div').replaceWith(result);
						var new_div_val = $('#service').find("[data-days='"+initial_days+"']").val();
						if (new_div_val){
							$("#service").val(new_div_val);
						}
						console.log("== CARRIER CHANGE VALUES ==");
						console.log("Initial ID: "+initial_result);
						console.log("Initial Days: "+initial_days);
						console.log("New ID: "+new_div_val);
						var days = parseInt($("#service :selected").attr("data-days"));
						if(!isNaN(days)){
							$("input[name=ni_date]").val(freight_date(days));
						}
						console.log("JSON Services limited dropPop.php: Success");
					},					
					error: function(xhr, status, error) {
						alert(error+" | "+status+" | "+xhr);
						console.log("JSON Services limited dropPop.php: Error");
					}
				});
			});
			$(document).on("change","#service",function() {
				var days = parseInt($("#service :selected").attr("data-days"));
				if (!isNaN(days)){
					$("input[name=ni_date]").val(freight_date(days));
					$('.line_date').text(freight_date(days));
					$('.line_date').attr("data-date",freight_date(days));
				}
			});

		
			//MultiPart Search Feature
			$(document).on("keyup","#go_find_me",function(e){
				if (e.keyCode == 13) {
					$(".search_loading").show();
					var search = $("#go_find_me").val();
					var order_type = $("#order_body").attr("data-order-type");
					//Ajax Call the new paradigm
					console.log(window.location.origin+"/json/new_paradigm.php?mode=search&item="+search+"&page="+order_type);
					$.ajax({
						type: "POST",
						url: '/json/new_paradigm.php',
						data: {
							"mode" : "search",
							"item": search,
							"page" : order_type,
						}, // serializes the form's elements.
						dataType: 'json',
						success: function(result) {
							$(".search_loading").hide();
							$(".search_lines").html("").remove();
							$(".nothing_found").html("").remove();
							if(result == "") {
								$('.nothing_found').show();
							} else {
								$('.nothing_found').hide();
							}
							$("#search_input").append(result);
							$(".search_lines input[name='ni_qty']:first").focus();
						},
						error: function(xhr, status, error) {
						   	alert(error+" | "+status+" | "+xhr);
						},					
					});
				}
			});
			
			//This allows you to use the up and down arrows on the Search lines in PO/SO
			//Also on tab of qty it will go straight to the price
			$(document).on("keydown","input[name='ni_qty']",function(e){
				if (e.keyCode == 9) {
					e.preventDefault();
					$('input[name="ni_price"]').focus();
				} else if (e.keyCode == 38) {
					//Up Arrow
					$(this).closest('.search_lines').prev().find("input[name='ni_qty']").focus();
					//alert('down');
				} else if(e.keyCode == 40) {
					//Down Arrow
					$(this).closest('.search_lines').next().find("input[name='ni_qty']").focus();
				}
			});
			
			$(document).on("keydown","input[name='ni_price']",function(e){
				if (e.keyCode == 9) {
					e.preventDefault();
				} else if (e.keyCode == 13) {
					var isValid = nonFormCase($(this), e);
					
					if(isValid) {
						var qty = 0;
						console.log($(".search_lines"));
   		    			$(".search_lines").each(function() {
							qty += populateSearchResults($(".multipart_sub"),$(this).attr("data-line-id"),$(this).find("input[name=ni_qty]").val());
						});
						if (qty == 0){
							modalAlertShow("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning", "Qty is missing or invalid. <br><br>If this message appears to be in error, please contact an Admin.");
						}else{
							$(".search_lines").html("").remove();
							$(".items_label").html("").remove();
							$("#totals_row").show();
							//sub_row.find("input[name=ni_line]").val(line_number());
							$('#totals_row').find("input[name='np_total']").val(updateTotal());
							$('#go_find_me').focus();
						}
					} 
				}
			});
			
			$(document).on("click",".li_search_button",function() {
				var search = $("#go_find_me").val();
				//Ajax Call the new paradigm
				$.ajax({
					type: "POST",
					url: '/json/new_paradigm.php',
					data: {
						"mode" : "search",
						"item": search,
						"page" : $("#order_body").attr("data-order-type"),
					}, // serializes the form's elements.
					dataType: 'json',
					success: function(result) {
						$(".search_lines").html("").remove();
						$("#search_input").append(result)
					},
					error: function(xhr, status, error) {
					   	alert(error+" | "+status+" | "+xhr);
					},					
				});

			});
			$(document).on("click","#show_more",function() {
				var search = $("#go_find_me").val();
				//Ajax Call the new paradigm
				$.ajax({
					type: "POST",
					url: '/json/new_paradigm.php',
					data: {
						"mode" : "search",
						"item": search,
						"show": true,
						"page" : $("#order_body").attr("data-order-type"),
					}, // serializes the form's elements.
					dataType: 'json',
					success: function(result) {
						$(".search_lines").html("").remove();
						$("#search_input").append(result)
					},
					error: function(xhr, status, error) {
					   	alert(error+" | "+status+" | "+xhr);
					},					
				});

			});
			
			$(document).on('focusout', '.datetime-picker-line input', function() {
				var days = $('#service').find(':selected').attr('data-days');
				
				if($(this).val() == '') {
					$("input[name=ni_date]").val(freight_date(days));
				}
			});
			
	
			//========================== Usability Functions ==========================
	
			//Any time a field is clicked for editing, or double clicked at all, show
			//the easy output portion of the row, populated with the relevant updated pages.
			$(document).on("click",".forms_edit",function() {
				var click_row = $(this).closest("tr");
				var lazy_row = click_row.next();
				lazy_row.find("input[name='ni_date']").parent().initDatetimePicker('MM/DD/YYYY');
				lazy_row.find(".item_search").initSelect2("/json/part-search.php","Select a Part",$("body").attr("data-page"));
				var ext = click_row.find(".line_linext").text();
				lazy_row.find("input[name='ni_ext']").val(ext);
				click_row.hide();
				lazy_row.show();
			});

			$(document).on("dblclick",".easy-output td",function() {
				var click_row = $(this).closest("tr");
				click_row.hide();
				var lazy_row = click_row.next();
				lazy_row.show();
				lazy_row.find("input[name='ni_date']").parent().initDatetimePicker('MM/DD/YYYY');
				lazy_row.find(".item_search").initSelect2("/json/part-search.php","Select a Part",$("body").attr("data-page"));
				var ext = click_row.find(".line_linext").text();
				lazy_row.find("input[name='ni_ext']").val(ext);
			});
	
//No idea what this does, but when something breaks, uncomment this and it will magically fix it, probably.
		    //$(".item_search").initSelect2("/json/part-search.php","Select a Part",$("body").attr("data-page"));
	
	
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
			    var display = price_format(ext);
			    if (qty && price){
					$(this).closest("tr").find("input[name=ni_ext]").val(display);
			    }
			    else{
					$(this).closest("tr").find("input[name=ni_ext]").val("");
			    }
			});
			
			$(document).on("click",".line_item_submit",function() {
				var qty = 0;
				qty += populateSearchResults($(this),'',$(this).closest("tr").find("input[name=ni_qty]").val());
				if (qty == 0){
					modalAlertShow("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning", "Qty is missing or invalid. <br><br>If this message appears to be in error, please contact an Admin.");
				}else{
							$(".search_lines").html("").remove();
							$(".items_label").html("").remove();
							$("#totals_row").show();
							//sub_row.find("input[name=ni_line]").val(line_number());
							$('#totals_row').find("input[name='np_total']").val(updateTotal());
							$('#go_find_me').focus();
						}
			});
			
			$(document).on("click",".line_item_unsubmit",function() {
				var defaultQty;
				
				$(this).closest("tr").find("input").each(function() {
					defaultQty = $(this).attr('data-value');
					$(this).val(defaultQty);	
				});
				
		    	$(this).closest(".lazy-entry").hide();
		    	$(this).closest("tr").prev(".easy-output").show();
			});

			//New Multi-line insertion 			
			$(document).on("click",".multipart_sub",function(e) {
				var isValid = nonFormCase($(this), e);
				
				if(isValid) {
					var qty = 0;
	    			$(".search_lines").each(function() {
						populateSearchResults($(".multipart_sub"),$(this).attr("data-line-id"),$(this).find("input[name=ni_qty]").val());
						qty += $(this).find("input[name=ni_qty]").val();
					});
					if (qty == 0){
						modalAlertShow("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning", "Qty is missing or invalid. <br><br>If this message appears to be in error, please contact an Admin.");
					}else{
							$(".search_lines").html("").remove();
							$(".items_label").html("").remove();
							$("#totals_row").show();
							//sub_row.find("input[name=ni_line]").val(line_number());
							$('#totals_row').find("input[name='np_total']").val(updateTotal());
							$('#go_find_me').focus();
						}
				} 
			});
			
			//Delete Button
			$(document).on("click",".forms_trash",function() {
				if(confirm("Are you sure you want to delete this row?")){
					var id = $(this).closest("tr").attr('data-record');
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

			$(document).on("change","#ship_to, #bill_to",function() {
				//Get the identifier of the initial textbox
				var origin = ($(this).parent().find('select').attr('id'));
				var right = $(this).text();
				
				
				if($(this).val().indexOf("Add") > -1){
					//Gather the address from the select2 field
					var addy = ($(this).val().slice(4));
					//Otherwise, if it is a number, assume they were searching by the address itself
					$("#address-modal-body").find("input").val('');
					$("#address-modal-body").find("input[name='na_line_1']").val(addy);
					var company = ($("#select2-companyid-container").attr("title"));
					$("#address-modal-body").find("input[name='na_name']").val(company);
					$("#address-modal-body").attr('data-origin',origin);
					$("#modal-address").modal('show');
				}
				else{
					if (origin == "bill_to"){
						//$("#ship_display").replaceWith(right);	
						updateShipTo();
						// $("#select2-ship_to-container").html(right);
					}
					else{
						if($(this).val() != $('#bill_to').val()) {
							$("#mismo").prop("checked",false);
						} else {
							$("#mismo").prop("checked",true);
						}
						console.log($(this).val() + ' vs ' + $('#bill_to').val());
						//$("#bill_display").hr("<div //id='bill_display'>"+right+"</div>");	
						//$("#mismo").prop("checked",false);
					}
				}
			});
			
			// $(document).on('change', '#ship_to', function(){
			// });
			
			$('#modal-address').on('shown.bs.modal', function () {
			    $("#address-modal-body").find('input[name="na_city"]').focus();
			}); 

			$(document).on("click", "#address-continue", function(e) {
			
				//Non-form case uses data-validation tag on the button which points to the container of all inputs to be validated by a required class
				//('object initiating the validation', the event, 'type of item being validated aka modal')
				var isValid = nonFormCase($(this), e, 'modal');
				if(! isValid) { return false; }
			    var field = '';
			    field = $("#address-modal-body").attr("data-origin");
				    
			    var name = $("#add_name").val();
				var line_1 = $('#add_line_1').val();
				var line2 = $('#add_line2').val();
				var city = $('#add_city').val();
				var state = $('#add_state').val();
				var zip = $('#add_zip').val();
				var id = $("#address-modal-body").attr("data-oldid");
				var text = name;
				
				$("#address-modal-body").attr("data-oldid",'false');
				
				console.log("/json/addressSubmit.php?"+"name="+name+"&line_1="+line_1+"&line2="+line2+"&city="+city+"&state="+state+"&zip="+zip+"&id="+id);
			    $.post("/json/addressSubmit.php", {
			    	"name" : name,
					"line_1" : line_1,
					"line2" : line2,
					"city" : city,
					"state" : state,
					"zip" : zip,
					"id" : id
			    },function(data){
			    	console.log("Logging the ID (this should be false if creating new): "+id);
			    	console.log("Return from Address Submission: "+data);
			    	
			    	if (!isNaN(id)){
			    		data = id;
			    	}
		    		//If it didn't have an update, it is a new field
			    	if (field == "ship_to"){
			    		// $("#select2-ship_to-container").html(line_1);
			    		// $("#ship_to").append("<option selected value='"+data+"'>"+line_1+"</option>");
			    		// $("#ship_to").val(data);
	    					var option = $('<option></option>').prop('selected', true).text(line_1).val(data);
							/* insert the option (which is already 'selected'!) into the select */
							option.appendTo($("#ship_to"));
							/* Let select2 do whatever it likes with this */
							$("#ship_to").trigger('change');
				    	}
				    	else{
	    					var option = $('<option></option>').prop('selected', true).text(line_1).val(data);
							/* insert the option (which is already 'selected'!) into the select */
							option.appendTo($("#bill_to"));
							/* Let select2 do whatever it likes with this */
							$("#bill_to").trigger('change');
				    	}
			    	$('.modal').modal('hide');
			    });
			});
			
			$(document).on("click", "#address-cancel", function(e) {
			    var field = $("#address-modal-body").attr("data-origin");
			   	
			   	
			   	//verify that the field is adding if you cancel the value
			   	if($("#"+field).val().indexOf("Add") > -1){
			    	if (field == "ship_to"){
			    		$("#select2-ship_to-container").html('');
			    		$("#ship_to").append("<option selected value='"+null+"'>"+''+"</option>");
			    	}
			    	else{
			    		$("#select2-bill_to-container").html('');
			    		$("#bill_to").append("<option selected value='"+null+"'>"+''+"</option>");
			    	}
				    	
			    	$('.modal').modal('hide');
			   	}
			});
			
			$(document).on("click",".address_edit",function() {
				var drop = $(this).closest("div").find('select');
				var origin = drop.attr('id');
				//alert(origin);
				var add_id = drop.last('option').val();
				console.log(window.location.origin+"/json/address-pull.php?address="+add_id);
				if(! add_id) { return; }

				$.ajax({
					type: "POST",
					url: '/json/address-pull.php',
					data: {
						'address' : add_id,
					},
					dataType: 'json',
					success: function(address) {
						console.log(address);
						$("#address-modal-body").attr("data-origin",origin);
						$("#address-modal-body").attr("data-oldid",add_id);
						$("#add_name").val('').val(address.name);
						$('#add_line_1').val('').val(address.street);
						$('#add_city').val('').val(address.city);
						$('#add_state').val('').val(address.state);
						$('#add_zip').val('').val(address.postal_code);
						
						$("#modal-address").modal('show');
					},
					error: function(xhr, status, error) {
					   	alert(error);
					},
				});
			});
			
			$(document).on("click","#mismo",function() {
				updateShipTo();
			});



			/***********************************/
			/***** CONTACT ADD/EDIT MODULE *****/
			/***********************************/

			$(document).on("click",".contact-edit",function() {
				var contacts = $(this).closest("div").find('select');
				var contactid = contacts.val();
				if (! contactid) { return; }

				// re-text the title
				$("#modalContactTitle").text(contacts.select2('data')[0]['text']);
				// erase previous values, just in case there's an error in setting the new values below
				$("#modal-contact").find("input[type=text],input[type=email]").each(function() {
					$(this).val('');
				});

				console.log(window.location.origin+"/json/contact.php?contactid="+contactid);
				$.ajax({
					type: "POST",
					url: '/json/contact.php',
					data: { 'contactid' : contactid, },
					dataType: 'json',
					success: function(json, status) {
						if (json.message=='Success') {
							$("#contact_name").val(json.name);
							$("#contact_title").val(json.title);
							$("#contact_email").val(json.email);
							$("#contact_notes").val(json.notes);
						} else {
							alert(json.message);
						}
					},
					error: function(xhr, desc, err) {
                    	console.log("Details: " + desc + "\nError:" + err);
					},
					complete: function() {
						// open modal
						$("#modal-contact").modal('show');
					}
				});
			});
			$(document).on("shown.bs.modal","#modal-contact",function(e) {
				var first_field = $(this).find("input[type=text]")[0];
				first_field.select();
				first_field.focus();
			});

			/* save contact info and close modal */
			$(document).on("click","#save-contact",function() {
				var companyid = $("#companyid").val();
				var contactid = $("#contactid").val();
				var contact_name = $("#contact_name").val();
				var contact_title = $("#contact_title").val();
				var contact_email = $("#contact_email").val();
				var contact_notes = $("#contact_notes").val();

				var params = "?contactid="+escape(contactid)+"&companyid="+companyid+"&name="+escape(contact_name)+
					"&title="+escape(contact_title)+"&email="+escape(contact_email)+"&notes="+escape(contact_notes);
				console.log(window.location.origin+"/json/save-contact.php"+params);
				$.ajax({
					type: "POST",
					url: '/json/save-contact.php',
					data: {
						'contactid' : contactid,
						'companyid' : companyid,
						'name' : contact_name,
						'title' : contact_title,
						'email' : contact_email,
						'notes' : contact_notes
					},
					dataType: 'json',
					success: function(json, status) {
						if (json.message=='Success') {
							if (contactid.indexOf("Add")>-1) {
								// re-populate dropdown with newly-created contact
								var option = $('<option></option>').
									prop('selected',true).
									text(json.name).
									val(json.contactid);
								option.appendTo($("#contactid"));//insert pre-selected option into select menu
								// initialize the change so it takes effect
								$("#contactid").trigger("change");
							}

							$("#modal-contact").modal('hide');
						} else {
							alert(json.message);
						}
					},
					error: function(xhr, desc, err) {
                    	console.log("Details: " + desc + "\nError:" + err);
					},
					complete: function() {
					}
				});
			});

			$(document).on("change","#contactid",function() {
				var contactid = $(this).val();

				if (contactid.indexOf("Add") == -1) { return; }

				// open modal
				$("#modalContactTitle").text('Add New Contact');
				// reset fields
				$("#modal-contact").find("input[type=text],input[type=email]").each(function() {
					$(this).val('');
				});
				$("#contact_name").val(contactid.replace('Add ',''));
				$("#modal-contact").modal('show');
			});

			/***************************************/
			/***** END CONTACT ADD/EDIT MODULE *****/
			/***************************************/
				

//Account Modal Popup Instigation
			$(document).on("change","#account_select",function() {
				if($(this).val().indexOf("Add") > -1){
					
					//Gather the address from the select2 field
					var acct = ($(this).val().slice(5));
					
					//If the first number is the address, assume the user is searching by an address name
					$("#account-modal-body").find("input[name='na_account']").val(acct);
					$("#modal-account").modal('show');
				}
			});
			
//Account modal handling function
			$(document).on("click", "#account-continue", function() {
			    var company = '';
			    var carrier = $("#account-modal-body").find('#modal_carrier').val();
			    var account = $("#account-modal-body").find("input[name='na_account']").val();
			    var comp_check = $("#account-modal-body").find('input[name="associate"]').prop("checked");
				if (comp_check){
					company = $("#companyid").val();
				}
			    var data = [account, carrier, company];
				var assoc = {
					account: account, 
					carrier: carrier,
					company: company
				}
			    $.post("/json/accountSubmit.php", {'test[]' : data},function(data){
					$("#select2-account_select-container").html(account);
					$("#carrier").val(carrier);
					$("#account_select").append("<option selected value='"+data+"'>"+account+"</option>");	
			    	$("#account-modal-body").find('#modal_carrier').val('');
			    	$("#account-modal-body").find("input[name='na_account']").val('');
			    	$("#account-modal-body").find('input[name="associate"]').prop("checked",false);
					$("#account_select").initSelect2("/json/freight-account-search.php","PREPAID",{"limit":receiver_companyid,"carrierid":$("#carrier").val()});
		
					});
				$.ajax({
					type: "POST",
					url: '/json/dropPop.php',
					data: {
						"field":"services",
						"limit":carrier,
						"size": "col-sm-8",
						"label": "Service",
						"id" : "service"
						}, // serializes the form's elements.
					dataType: 'json',
					success: function(result) {
						var initial_result = $("#service").val();
						var initial_days = $("#service").find("[value='"+initial_result+"']").attr("data-days");
						$('#service_div').replaceWith(result);
						var new_div_val = $('#service').find("[data-days='"+initial_days+"']").val();
						if (new_div_val){
							$("#service").val(new_div_val);
						}
						console.log("== CARRIER CHANGE VALUES ==");
						console.log("Initial ID: "+initial_result);
						console.log("Initial Days: "+initial_days);
						console.log("New ID: "+new_div_val);
						var days = parseInt($("#service :selected").attr("data-days"));
						if(!isNaN(days)){
							$("input[name=ni_date]").val(freight_date(days));
						}
						console.log("JSON Services limited dropPop.php: Success");
					},					
					error: function(xhr, status, error) {
						alert(error+" | "+status+" | "+xhr);
						console.log("JSON Services limited dropPop.php: Error");
					}
				});

			});

//Global Warranty function
			$(document).on("change","#warranty_global",function() {
				var value = $(this).val();
				var text = $("#warranty_global option:selected").text();
				if (value != ""){
					$("#new_warranty").val(value);
					$(".line_war").each(function() {
						$(this).text(text).attr("data-war",value);
					});
				}
			});

//Conditional Global change
			$(document).on("change","#condition_global",function() {
				var value = $(this).val();
				var text = $("#condition_global option:selected").text();
				
				console.log(window.location.origin+"/json/dropPop.php?ajax=true&limit="+value+"&field=services&label=Service&id=service&size=col-sm-6");
				if (value != ""){
					$(".line_cond").text(text)
					.attr("data-cond",value);
					$.ajax({
						type: "POST",
						url: '/json/dropPop.php',
						data: {
							"field": "conditionid",
							"selected": value,
							"limit": '',
							"size": "col-md-6",
							"id":"conditionid"
							},
						dataType: 'json',
						success: function(result) {
							//alert(result);
							$(".conditionid").not( document.getElementById( "condition_global" ) ).replaceWith(result);
							console.log("Condition Set - dropPop.php: Success");
							// $('#new_warranty').parent().replaceWith(result)
							// .parent().removeClass('col-md-12');
						}
					});
				}
			});
			
			$(document).on("click","#associate_clip",function() {
				// var assoc = $("#assoc_order").val();
				// alert(assoc);
			});
//================================ PAGE SUBMIT ================================
			
			$('#save_button').click(function(e) {
				

				var isValid = nonFormCase($(this), e);
				//if($(".search_lines").length > 0){
					//line_item_submit();
				//}

				// save any pending rows before proceeding
				$(".search_lines").each(function() {
					// require callback variable so this doesn't release asynchronously before the entire process is complete
					var completed_qty = 0;					
					completed_qty = populateSearchResults($(".multipart_sub"),$(this).attr("data-line-id"),$(this).find("input[name=ni_qty]").val());
					if (completed_qty == 0){
						modalAlertShow("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning", "Qty is missing or invalid. <br><br>If this message appears to be in error, please contact an Admin.");
					}
					else{
							$(".search_lines").html("").remove();
							$(".items_label").html("").remove();
							$("#totals_row").show();
							//sub_row.find("input[name=ni_line]").val(line_number());
							$('#totals_row').find("input[name='np_total']").val(updateTotal());
							$('#go_find_me').focus();
						}
				});
				
				if(isValid && $('.lazy-entry:hidden').length > 0) {
					//Get page macro information
					//var order_type = $(this).closest("body").attr("data-order-type"); //Where there is 
					var order_number = $(this).closest("body").attr("data-order-number");
	
					//Get General order information
					var created_by = $("#sales-rep").attr('data-creator');
					var repid = $("#sales-rep option:selected").attr("data-rep-id");

					var company = $("#companyid").val();
					
					var contact = $("#contactid").val();
/*
					if (contact.includes("new")){
						contact = $("#select2-contactid-container").text();
						//Get rid of the 'Add' portion of the text
						contact = contact.slice(4);
					}
*/
					var assoc = $("#assoc_order").val();
					
					if (order_type == 'Purchase'){
						var tracking = $('#tracking').val();
						// alert(tracking);
					}
					var terms = $("#terms").val();
					var ship_to = $('#ship_to').last('option').val();
					var bill_to = $('#bill_to').last('option').val();
					var carrier = $('#carrier').val();
					var freight = $('#terms').val();
					var service = $('#service').val();
					var account = $('#account_select').val();
					// if (($('#account_select').last('option').val())){
					// 	var account = $('#account_select').last('option').val();
					// }
					// else{
					// 	var account = '';
					// }
					var pri_notes = $("#private_notes").val();
					var pub_notes = $("#public_notes").val();
					var email_to = $("#email_to").val();
					var email_confirmation = '';
					if ($("#email_confirmation").is(':checked')) { email_confirmation = $("#email_confirmation").val(); }

					var filename;
					/* David's file uploader */
					if ($('#order-upload').length) {
						var files = new FormData();
						$.each(orderUploadFiles, function(key, value) {
							files.append(key, value);
						});

						// need to process the uploaded file in a separate ajax request first, thank you to this guy for the help:
						// https://abandon.ie/notebook/simple-file-uploads-using-jquery-ajax
						$.ajax({
							url: '/json/order-form-submit.php',
							type: 'POST',
							cache: false,
							dataType: 'json',
							processData: false, //Don't process the files
							contentType: false, //Set content type to false as jQuery will tell the server its a query string request
							async: false, //We want to force the upload first before continuing to complete form post below
							data: files,
							success: function(data, textStatus, jqXHR) {
								if (typeof data.error==='undefined') {
									if (data.filename!='') {
										filename = data.filename;
									} else if (data.message) {
										alert(data.message);
										return;
									}
								}
								console.log("Order-upload:Success");
							},
							error: function(data, textStatus, errorThrown) {
								console.log("Order-upload: Failure");
								alert(errorThrown);
								return;
							},
						});
					}
	
	
					//-------------------------- Right hand side --------------------------
					//Get Line items from the right half of the page
					var submit = [];

					//This loop runs through the right-hand side and parses out the general values from the page
					$(this).closest("body").find("#right_side_main").children(".easy-output").each(function(){
							var line_ref_1 = '';
							var line_ref_1_label = '';
							if(order_type == "RTV"){
								line_ref_1 = $(this).find(".line_ref").text();
								line_ref_1_label = 'line_item_id';
								order_number = 'New';
							}
							
						var row = {
							"line_number" : $(this).find(".line_line").attr("data-line-number"),
							"part" : $(this).find(".line_part").attr("data-search"),
							"id" : $(this).find(".line_part").attr("data-record"),
							"date" : $(this).find(".line_date").attr("data-date"),
							"conditionid" : $(this).find(".line_cond").attr("data-cond"),
							"warranty" : $(this).find(".line_war").attr("data-war"),
							"price" : $(this).find(".line_price").text(),
							"qty" : $(this).find(".line_qty").attr("data-qty"),
							"ref_1" : line_ref_1,
							"ref_1_label" : line_ref_1_label
						};

							// alert("line_number "+row["line_number"]);
							// alert("part "+row["part"]);
							// alert("id "+row["id"]);
							// alert("date "+row["date"]);
							// alert("condition "+row["condition"]);
							// alert("warranty "+row["warranty"]);
							// alert("price "+row["price"]);
							// alert("qty "+row["qty"]);
							
							// "line_number"+line_number+"part"+part+"id"+id+"date"+date+"conditionid"+conditionid+"warranty"+warranty+"price"+price+"qty"+qty;

						submit.push(row);
					});
					console.log(submit);
					console.log(order_number+" | "+order_type);
					console.log("/json/order-form-submit.php?"+
							"sales-rep="+ repid+"&"+
							"created_by="+ created_by+"&"+
							"companyid="+company+"&"+
							"order_type="+order_type+"&"+
			   		    	"order_number="+order_number+"&"+
							"contact="+ contact+"&"+
							"assoc="+ assoc+"&"+
							"tracking="+ tracking+"&"+
							"ship_to="+ ship_to+"&"+
							"bill_to="+ bill_to+"&"+
							"carrier="+ carrier+"&"+
							"account="+ account+"&"+
							"terms=" + terms+"&"+
							"service=" + service+"&"+
							"pri_notes="+ pri_notes+"&"+
							"pub_notes="+ pub_notes+"&"+
							"table_rows"+ submit);
					//Submit all rows and meta data for unpacking later
					// alert(account);
					$.ajax({
						type: "POST",
						url: '/json/order-form-submit.php',
						data: {
							"sales-rep": repid,
							"created_by": created_by,
							"companyid":company,
							"order_type":order_type,
			   		    	"order_number":order_number,
							"contact": contact,
							"assoc": assoc,
							"tracking": tracking,
							"ship_to": ship_to,
							"bill_to": bill_to,
							"carrier": carrier,
							"account": account,
							"terms" : terms,
							"service" : service,
							"pri_notes": pri_notes,
							"pub_notes": pub_notes,
							"table_rows":submit,
							"filename":filename,
							"email_confirmation":email_confirmation,
							"email_to":email_to,
						}, // serializes the form's elements.
						dataType: 'json',
						success: function(form) {
							var on = form["order"];
							var ps = form["type"];
							if (form['message']=='Success') {
								console.log("SAVED"+on+" | Order"+ps);
								console.log("Last Inserted: "+form['insert']);
								console.log("Last Line Inserted: "+form['line_insert']);
								console.log("Error from the last query: "+form["error"]);
								console.log("Update form: "+form['update']);
								console.log(form['input']);
								console.log(form['update_result']);
								if (ps == 'RTV'){
									ps = 's';
								}
							
								
								window.location = "/order_form.php?ps="+ps+"&on="+on;
							}
							else{
								console.log("SAVED"+on+" | Order"+ps);
								console.log("Last Inserted: "+form['insert']);
								console.log("Last Line Inserted: "+form['line_insert']);
								console.log("Error from the last query: "+form["error"]);
								console.log("Update form: "+form['update']);
								console.log(form['input']);
								console.log(form['update_result']);

							modalAlertShow(
								"<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning",
								form['message'],
								false);
							}
						},
						error: function(xhr, status, error) {
						   	console.log("Order-form-submission Error:");
						   	console.log(error);
//						   	"&userid="+userid+"&company="+company+"&order_type="+order_type+"&order_number="+order_number+"&contact="+contact+"&assoc="+assoc+"&tracking="+tracking+"&ship_to="+ship_to+"&bill_to="+bill_to+"&carrier="+carrier+"&account="+account+"&terms="+terms+"&service="+service+"&pri_notes="+pri_notes+"&pub_notes="+pub_notes;
							
						},
					});
				} else if($('.lazy-entry:visible').length > 0) {
					modalAlertShow("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning","Please save all changes before updating.", false);
				} else {
					if(isValid)
						modalAlertShow("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning","PO can not be created without any items.<br><br> Please add items before creating the PO.", false);
					$(window).scrollTop();
				}
			});
			
//========================== END COMPLETE PAGE SUBMIT =========================
			//Cancel button?
			

//Order Form Calendar Toggle Dates
			$('.toggle-cal-options').click(function(e){
				e.preventDefault();
				if ($(this).attr('data-name') == 'show') {
		            $('.date-options').animate({
		                width: '295px'
		            });
		            $(this).attr('data-name', 'hide')
		        } else {
		            $('.date-options').animate({
		                width: '100%'
		            }, function() {
		            	$('.cal-buttons').attr( "style", "" );;
		            });
		            $(this).attr('data-name', 'show')
		        }
			});
  /*=============================================================================*/
 /*============================ Aaron - END ORDER FORM =========================*/
/*=============================================================================*/



  /*===========================================================================*/
 /*=========================== BEGIN SHIPPING HOME ===========================*/
/*===========================================================================*/
			
			$('.date').initDatetimePicker("MM/DD/YYYY");
			
			$(".shipping_section_foot a").click(function(e) {
				e.preventDefault();
				if ($(this).text() == "Show more"){
					$('.col-lg-6').hide();
					//$(this).closest("body").children(".table-header").show();
					//$(this).closest("body").children(".initial-header").hide();
					$(this).closest(".col-lg-6").addClass("shipping-dash-full");
					
					//Show everything
					$(this).closest('.shipping-dash').children('.table-responsive').find('.show_more').show();
					
					$(this).closest(".shipping-dash").addClass("shipping-dash-remove");
					$(this).closest(".shipping-dash").removeClass("shipping-dash");
					
					$(".shipping-dash-full").fadeIn('fast');
					$('body').scrollTop('fast');
					
					var title = $(".shipping-dash-full .shipping_section_head").attr('data-title');
					$(".shipping_section_head").hide();
					
					//$("#view-head-text").text(title);
					$("#filter-title").text(title);
					
					$(this).closest("table").find(".overview").show();
					$(this).text("Show Less");
					$(this).parent().removeClass("shipping_section_foot_lock");
					//$(this).closest("body").children("#view-head").show();

				}
				else{
					//$(this).closest("body").children(".table-header").hide();
					//$(this).closest("body").children(".initial-header").show();
					$(".shipping-dash-full").removeClass("shipping-dash-full");
					$(this).closest("table").find(".overview").hide();
					$(this).parents("body").find(".shipping_section_head").fadeIn("fast");
					$('.col-lg-6').show();
					
					$(this).closest(".shipping-dash-remove").addClass("shipping-dash");
					
					//Hide all elements over the count of 10
					$(this).closest('.shipping-dash').children('.table-responsive').find('.show_more').hide();
					
					$(this).closest(".shipping-dash-remove").removeClass("shipping-dash-remove");
					$("#filter-title").text('Operations Dashboard');
					
					//$(this).closest("div").siblings(".shipping-dash").fadeIn("slow");
					$(this).parents("body").find(".overview").hide();
					//$(this).parents("body").children("#view-head").hide();
					$(this).parent().addClass("shipping_section_foot_lock");
					$(this).parents("body").find(".shipping_section_foot a").text("Show more");
				}
				headerOffset();
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
//=========== END OF FUNCTION FOR THE SHIPPING DASHBOARD =======================

		
		$('body').on('change keyup paste', 'input[name="NewSerial"]', function(e) {
		     if( $( this ).val() != '' )
		         window.onbeforeunload = function() { return "You have unsaved changes."; }
		});
//This function also handles the functionality for the shipping page
		$('body').on('keypress', 'input[name="NewSerial"]', function(e) {
			if(e.which == 13) {
				e.preventDefault();
				callback($(this));
			}
		});
		
		$('body').on('click', '.updateSerialRow', function(e) {
			callback($(this).closest('tr').find('input[name="NewSerial"]:first'));
		});
		
		$(document).on('click', '.serial-expand', function() {
			var data = $(this).attr('data-serial');
			
			$('.' + data).toggle();
		});
		
//If the lot inventory is checked then update the look and feel of the form
		$(document).on('change', '.lot_inventory', function() {
			var qty;
			if(this.checked) {
				qty = $(this).closest('tr').find('.remaining_qty').children('input').val();
				$(this).closest('tr').find('.infiniteSerials').find('input').attr('readonly', true);
				$(this).closest('tr').find('.remaining_qty').children('input').attr('readonly', false);
				$(this).closest('tr').find('.remaining_qty').children('input').attr('data-qty', qty);
				$(this).closest('tr').find('.remaining_qty').children('input').val('');
				//$(this).closest('tr').find('.infiniteSerials').find('input').val('');
				$(this).closest('tr').find('.remaining_qty').children('input').focus();
			} else {
				qty = $(this).closest('tr').find('.remaining_qty').children('input').attr('data-qty');
				$(this).closest('tr').find('.infiniteSerials').find('input').attr('readonly', false);
				$(this).closest('tr').find('.remaining_qty').children('input').attr('readonly', true);
				$(this).closest('tr').find('.remaining_qty').children('input').val(qty);
			}
		});
		
		$(document).on('click',"#save_button_inventory",function() {
			//Save to reactivate button if needed
			$click = $(this);
			//Prevent Button Spamming
			$click.removeAttr('id');
			
			//items = ['partid', 'Already saved serial','serial or array of serials', 'condition or array', 'lot', 'qty']
			//Include location in the near future
			var items = [];
			var po_number = getUrlParameter('on');
			
			//check if anything at all was changed on the page, including a scanned / entered item
			var checkSaved = false;
			
			//Get everything from the form and place it into its own array
			$('.inventory_add').children('tbody').children('tr').each(function() {
				var partid = $(this).find('.part_id').attr('data-partid');
				var serials = [];
				var savedSerials = [];
				var ids = [];
				var place = [];
				var instance = [];
				var conditions = [];
				var lot = false;
				var qty;
				
				$(this).find('.infiniteLocations').children('.row-fluid:first').find('select:first').each(function() {
					place.push($(this).val());
				});
				
				$(this).find('.infiniteLocations').children('.row-fluid:first').find('select:last').each(function() {
					instance.push(($(this).val() != '' ? $(this).val() : ''));
				});
				
				$(this).find('.infiniteCondition').children('select').each(function() {
					conditions.push($(this).val());
				});
				
				$(this).find('.infiniteSerials').find('input').each(function() {
					// added by david 2-28-17
					ids.push($(this).attr('data-item-id'));
					serials.push($(this).val());
					savedSerials.push($(this).attr('data-saved'));
					
					//If an item was saved previously then mark the page as soemthing was edited
					if($(this).attr('data-saved') != '') {
						checkSaved = true;
					}
					
					//For purpose of conflicts only add a saved serial when there is nothing in the item, else ajax save generates a serial to match data
					//if($(this).attr('data-saved') == '')
					//$(this).attr("data-saved", $(this).val());
				});
				
				//Check if the lot is checked or not
				if($(this).find('.lot_inventory').prop('checked') == true) {
					lot = true;
				} else {
					lot = false;
				}
				qty = $(this).find('.remaining_qty').children('input').val();
				
				items.push(partid);
				items.push(savedSerials);
				items.push(serials);
				items.push(conditions);
				items.push(lot);
				items.push(qty);
				items.push(place);
				items.push(instance);
				items.push(ids);
			});
			
			console.log(items);
			//console.log(po_number);
			
			$.ajax({
				type: "POST",
				url: '/json/inventory-add.php',
				data: {
					 'productItems' : items, 'po_number' : po_number
				},
				dataType: 'json',
				success: function(result) {
					console.log(result);
					
					//Error handler or success handler
					if(result['query'] || checkSaved) {
						//In case a warning is triggered but data is still saved successfully
						if(result['error'] != undefined)
							alert(result['error']);
						window.onbeforeunload = null;
						window.location = "/operations.php?po=true";
					//Error occured enough to stop the page from continuing
					} else if(result['error'] != undefined) {
						alert(result['error']);
						$click.attr('id','save_button_inventory');
					//Nothing was change
					} else {
						//alert('No changes have been made.');
						$click.attr('id','save_button_inventory');
						window.location = "/operations.php?po=true";
					}
				},
				error: function(xhr, status, error) {
					//alert(error+" | "+status+" | "+xhr);
					window.location = "/operations.php?po=true";
					console.log("inventory-add-complete.php: ERROR");
				},	
			});
		});
		
		
//Handle if the user deletes a serial from inventory add or shipping (Undoes a line item)
		$(document).on('click', '.deleteSerialRow', function() {
			var page = getPageName();
			var po_number = getUrlParameter('on');
			var $row = $(this).closest('.input-group');
			var qty = parseInt($row.closest('.infiniteSerials').siblings('.remaining_qty').children('input').val());
			//Grab the serial being deleted for futher usage to delete the item from the system
			var serial = $row.find('input').attr('data-saved');
			var invid = $row.find('input').attr('data-inv-id');

			var pack = $row.find('input').attr('data-package');
			
			//Grab all the required data to be passed into the delete ajax
			var partid = $row.closest('tr').find('.part_id').attr('data-partid');
			
			if (confirm("Are you sure you want to delete serial: '"+ serial +"'?")) {
				$.ajax({
					type: 'POST',
					url: '/json/inventory-delete.php',
					data: {'po_number' : po_number, 'partid' : partid,'serial' : serial, 'page' : page},
					dataType: 'json',
					success: function(data) {
						console.log(data);
						qty++; 
						
						if(page != 'shipping') {
							$row.closest('tr').find('.infiniteCondition').siblings('.remaining_qty').find('input').val(qty);
							$row.closest('tr').find('.infiniteCondition').find('select[data-serial="'+ serial +'"]').remove();
							$row.closest('tr').find('.infiniteLocations').find('.locations_tracker[data-serial="'+ serial +'"]').remove();
						} else {
							qty = parseInt($row.closest('.infiniteSerials').siblings('.remaining_qty').text());
							qty++; 
							
							$row.closest('tr').find('.infiniteSerials').siblings('.remaining_qty').text(qty);
							$row.closest('tr').find('.infiniteSerials').siblings('.ship-date').text('');
							
							$row.closest('tr').find('.infiniteBox').find('select[data-serial="'+ serial +'"]').remove();
							$row.closest('tr').find('.infiniteComments').find('input[data-serial="'+ serial +'"]').remove();
						}
						$row.closest('.infiniteSerials').children('.input-group:first').find('input').attr('readonly', false);
						
						//Settings to 2 because the row has not been deleted yet and will be after this execution
						//If 1 row then there are no serials, so re-enable lot
						if($row.closest('.infiniteSerials').children('.input-group').length <= 2) {
							$row.closest('tr').find('.lot_inventory').attr('disabled', false);
						}
					
						$row.remove();
						console.log("inventory-delete.php: Success");
					},
					error: function(xhr, status, error) {
						alert(error+" | "+status+" | "+xhr);
						console.log("inventory-delete.php: ERROR");
					},	
				});
				package_delete(pack,invid);
			}
		});


		//Shipping update button, mainly used for lot and serial redirection
		$('.btn_update').click(function(e){
			e.preventDefault();
			//Save to reactivate button if needed
			$click = $(this);
			//Prevent Button Spamming
			$click.removeAttr('id');
			
			var so_number = getUrlParameter('on');
			var items = [];
			var damage = false;
			var serialid = [];
			var serialComments = [];
			var print = '';
			
			if ($(this).attr('data-print') != '') {
				print = $(this).attr('data-print');
			}
			
			$('.shipping_update').children('tbody').children('tr').each(function() {
				$(this).find('.iso_comment').each(function() {
					//isoCheck.push($(this).attr('data-serial'));
					if($(this).val() != '') {
						serialid.push($(this).attr('data-invid'));
						serialComments.push($(this).val());
					}
				});
			});
		
			$.ajax({
				type: 'POST',
				url: '/json/iso.php',
				data: {
					'special_req' : 'yes', 
					'contact_info' : 'yes', 
					'transit_time' : 'yes', 
					'so_number': so_number, 
					'type' : 'special',
					'invid' : serialid, 
					'comments' : serialComments
				},
				dataType: 'json',
				success: function(data) {
					console.log(data + ' iso_match');
					$('.nav-tabs a[href="#iso_match"]').tab('show');
				},
			});
			
			var checkChanges = false;
			
			
			//Get everything from the form and place it into its own array
			$('.shipping_update').children('tbody').children('tr').each(function() {
				//Overlook all the rows that are complete in the order and grab all the others
				//if(!$(this).hasClass('order-complete')) {
				var partid = $(this).find('.part_id').attr('data-partid');
				var serials = [];
				var savedSerials = [];
				var boxes = [];
				var lot = false;
				var qty, conditionid;
				

				//Grab the conidtion value set by the sales order
				conditionid = $(this).find('.condition_field').attr('data-condition');
				
				$('.box_group').find('.box_selector').each(function() {
					boxes.push($(this).data('row-id'));
				});
				
				$(this).find('.infiniteSerials').find('input').each(function() {
					serials.push($(this).val());
					savedSerials.push($(this).data('saved'));
					
					//If an item was saved previously then mark the page as something was edited
					if($(this).data('saved') != '') {
						checkChanges = true;
					}
					
				});
				
				//Check if the lot is checked or not
				if($(this).find('.lot_inventory').prop('checked') == true) {
					lot = true;
				} else {
					lot = false
				}
				
				qty = $(this).find('.remaining_qty').data('qty');
				
				items.push(partid);
				items.push(savedSerials);
				items.push(serials);
				items.push(conditionid);
				items.push(lot);
				items.push(qty);
				items.push(boxes);
				//}
			});
			
			//Testing purposes
			console.log(items);
			
			$.ajax({
				type: 'POST',
				url: '/json/shipping-update.php',
				data: {'so_number' : so_number, 'items' : items},
				dataType: 'json',
				success: function(data) {
					console.log('test ' + data['timestamp']);
					
					if((data['query'] || checkChanges) && data['error'] == undefined) {
						//In case a warning is triggered but data is still saved successfully
						window.onbeforeunload = null;
						if(print != '' && data['timestamp'] != null) {
							var newWin = window.open('/packing-slip.php?on='+data['on']+'&date='+data['timestamp']+'', '_blank');
							if (newWin) {
							    //Browser has allowed it to be opened
							    newWin.focus();
							    window.location.href = window.location.href + "&success=true";
							} else {
							    //Browser has blocked it
							    modalAlertShow("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Pop-up Blocked",'Please allow popups for this website', false);
							}
						} else {
							window.location.href = window.location.href + "&success=true";
						}
					//Error occured enough to stop the page from continuing
					} else if(data['error'] != undefined) {
						alert(data['error']);
						$click.attr('id','btn_update');
					//Nothing was change
					} else {
						$click.attr('id','btn_update');
						window.location.href = window.location.href + "&success=true";
					}
				},
				error: function(xhr, status, error) {
					console.log("JSON shipping-update.php: ERROR " + error);
				},	
			});
		});


//==============================================================================
//================================== ISO Quality ===============================
//==============================================================================

	//Configure the modal and also work on the printable page
	$(document).on("click","#iso_report", function() {
		if($('.check-save').length >0){
			var isoCheck = [];
			var init = true;
			var damaged = '';
			
			var completed = $(this).attr('data-datestamp');
			
			$('.shipping_update').children('tbody').children('tr').each(function() {
				if(init) {
					$('.iso_broken_parts').empty();
					init = false;
				}

				//If an edit is present then grab only the present items
				if($('.iso_comment:enabled').length > 0) {
					$(this).find('.iso_comment:enabled').each(function() {
						if($(this).val() != ''){
							damaged = 'damaged';
						} else {
							damaged = '';
						}
						
						$('.iso_content_title').html('<i class="fa fa-dropbox" aria-hidden="true"></i> Pending for Shipment');
						
						var element = "<tr class='"+ damaged +"'>\
										<td>"+$(this).closest('tr').find('.infiniteBox').find('select[data-serial="'+$(this).attr('data-serial')+'"]').find(':selected').attr('data-boxno')+"</td>\
										<td>"+$(this).attr('data-part')+"</td>\
										<td>"+$(this).attr('data-serial')+"</td>\
										<td class='comment-data' data-invid='"+$(this).attr('data-inv-id')+"' data-comment ='"+$(this).val()+"' data-part = '"+$(this).attr('data-part')+"' data-serial = '"+$(this).attr('data-serial')+"'>"+$(this).val()+"</td>\
									</tr>";
						$('.iso_broken_parts').append(element);
						
						$('.btn_update').show();
						$('.btn_iso_parts').show();
						$('.btn_iso_parts_continue').hide();
					});
				//Else Grab everything currently on the order
				} else {
					$(this).find('.iso_comment').each(function() {
						if($(this).val() != ''){
							damaged = 'damaged';
						} else {
							damaged = '';
						}
						
						$('.iso_content_title').html('<i class="fa fa-list" aria-hidden="true"></i> Shipped Contents');
						
						var element = "<tr class='"+ damaged +"'>\
										<td>"+$(this).attr('data-package')+"</td>\
										<td>"+$(this).attr('data-part')+"</td>\
										<td>"+$(this).attr('data-serial')+"</td>\
										<td class='comment-data' data-invid='"+$(this).attr('data-inv-id')+"' data-comment ='"+$(this).val()+"' data-part = '"+$(this).attr('data-part')+"' data-serial = '"+$(this).attr('data-serial')+"'>"+$(this).val()+"</td>\
									</tr>";
						$('.iso_broken_parts').append(element);
					});
					$('.btn_update').hide();
					$('.btn_iso_parts').hide()
					$('.btn_iso_parts_continue').hide();
				}
			});
			
			$("#modal-iso").modal("show");
			
			if(completed == '') {
				$('.nav-tabs a[href="#iso_quality"]').tab('show');
			} else {
				//$('.nav-tabs a[href="#iso_match"]').tab('show');
				$('.nav-tabs a[href="#iso_quality"]').tab('show');
				$('.nav-tabs a').attr("data-toggle","tab");
			}
		} else {
			modalAlertShow("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning",'No items queued to be shipped.', false);
		}
	});
	
	
	//This function auto opens the next locations drop down when the first one is changed
	$(document).on('change', '.infiniteLocations .instance:first select', function() {
		$(this).closest('tr').find('.infiniteSerials').find('input:first').focus();
	});
	
	$(document).on('click','.btn_iso_parts_continue', function(e) {
		e.preventDefault();
		if($('.nav-tabs a[href="#iso_req"]').length > 0) {
			$('.nav-tabs a[href="#iso_req"]').tab('show');	
		} else {
			$('.nav-tabs a[href="#iso_match"]').tab('show');
		}
	});
	
	$(document).on('click','.btn_iso_parts', function(e) {
		e.preventDefault();
		var damage = false;
		var so_number, partName;
		var serialid = [];
		var serialComments = [];
		
		so_number = $('.shipping_header').attr('data-so');
		
		if($('.iso_broken_parts').find('.damaged').length) {
			damage = true;
		}
	
		if(damage) {
			$('.iso_broken_parts').children('tr.damaged').each(function() {
				
				var invid = $(this).find('.comment-data').attr('data-invid');
				var serial = $(this).find('.comment-data').attr('data-serial');
				var issue = $(this).find('.comment-data').attr('data-comment');
				
				serialid.push(invid);
				serialComments.push(issue);
			
			});
		}
		
		console.log(serialid + ' ' + serialComments + ' ' + so_number + ' ' + damage);
		
		$.ajax({
			type: 'POST',
			url: '/json/iso.php',
			data: {
				'part_no' : 'yes', 
				'heci' : 'yes',
				'damage' : damage, 
				'so_number' : so_number, 
				'invid' : serialid, 
				'comments' : serialComments,
				'type' : 'part',
			},
			dataType: 'json',
			success: function(data) {
				console.log(data + ' test');
				if($('.nav-tabs a[href="#iso_req"]').length > 0) {
					$('.nav-tabs a[href="#iso_req"]').tab('show');	
				} else {
					$('.nav-tabs a[href="#iso_match"]').tab('show');
				}
			},
			error: function(xhr, status, error) {
				alert(error+" | "+status+" | "+xhr);
				console.log("JSON iso.php: ERROR");
			},
		});
	});
	
	$(document).on('click','.btn_iso_req', function(e) {
		e.preventDefault();
		var so_number;
		
		so_number = $('.shipping_header').attr('data-so');

		$.ajax({
			type: 'POST',
			url: '/json/iso.php',
			data: {'special_req' : 'yes', 'contact_info' : 'n/a', 'transit_time' : 'n/a', 'so_number': so_number, 'type' : 'special'},
			dataType: 'json',
			success: function(data) {
				console.log(data + ' iso_match');
				$('.nav-tabs a[href="#iso_match"]').tab('show');
			},
			error: function(xhr, status, error) {
				alert(error+" | "+status+" | "+xhr);
				console.log("JSON iso.php: ERROR");
			},
		});
	});


//==============================================================================
//================================== PACKAGES ==================================
//==============================================================================

//Open Modal
	$(document).on("click",".box_edit", function(){
		var package_number = $(".box_selector.active").text();
		var order_number = $("body").attr('data-order-number');
		if (package_number){
			$("#package_title").text("Editing Box #"+package_number);
			$("#alert_title").text("Box #"+package_number);
			$("#modal-width").val($(".box_selector.active").attr("data-width"));
			$("#modal-height").val($(".box_selector.active").attr("data-h"));
			$("#modal-length").val($(".box_selector.active").attr("data-l"));
			$("#modal-weight").val($(".box_selector.active").attr("data-weight"));
			$("#modal-tracking").val($(".box_selector.active").attr("data-tracking"));
			$("#modal-freight").val($(".box_selector.active").attr("data-row-freight"));
			$("#package-modal-body").attr("data-modal-id",$(".box_selector.active").attr("data-row-id"));
			
			var status = $(".box_selector.active").attr('data-box-shipped');
			
			if(status == 'completed') {
				$("#alert_message").show();
			} else {
				$("#alert_message").hide();
			}
			//alert("ON: "+order_number+" | Package #: "+package_number);
			$.ajax({
				type: "POST",
				url: '/json/package_contents.php',
				data: {
					"order_number": order_number,
					"package_number": package_number
				},
				dataType: 'json',
				success: function(data) {
					console.log('/json/package_contents.php?order_number='+order_number+"&package_number="+package_number);
					console.log(data);
					$('.modal-packing').empty();
					if (data){
						$.each( data, function( i, val ) {
							$.each(val, function(it,serial){
									var element = "<tr>\
											<td>"+ i +"</td>\
											<td>"+ serial +"</td>\
										</tr>";
									$('.modal-packing').append( element );
								});
							});
							// for(var k = 0; k < val.length; k++) {
					}
						
						//After the edit modal has been set with the proper data, show it
						$("#modal-package").modal("show");
				},
				error: function(xhr, status, error) {
					alert(error+" | "+status+" | "+xhr);
					console.log("JSON packages_contents.php: Error");
					console.log('/json/package_contents.php?order_number='+order_number+"&package_number="+package_number);
				},				
				
			});
		}
		else{
			alert('Please select a box before editing');
		}
	});
//Submit Modal
	$(document).on("click","#package-continue", function(){
			
		//Set redundant-ish variables for easier access
		var width = $("#modal-width").val();
		var height = $("#modal-height").val();
		var length = $("#modal-length").val();
		var weight = $("#modal-weight").val();
		var tracking = $("#modal-tracking").val();
		var freight = $("#modal-freight").val();
		var id = $("#package-modal-body").attr("data-modal-id");
		
		//Update the Data tags on the page
		$(".box_selector.active").attr("data-width",width);
		$(".box_selector.active").attr("data-h",height);
		$(".box_selector.active").attr("data-l",length);
		$(".box_selector.active").attr("data-weight",weight);
		$(".box_selector.active").attr("data-tracking",tracking);
		$(".box_selector.active").attr("data-row-freight",freight);

		
		$.ajax({
			type: "POST",
			url: '/json/packages.php',
			data: {
				"action": "update",
				"width": width,
				"height": height,
				"length": length,
				"weight": weight,
				"tracking": tracking,
				"freight": freight,
				"id": id,
			},
			dataType: 'json',
			success: function(update) {
				console.log("JSON packages.php: Success");
				console.log(update);
			},
			error: function(xhr, status, error) {
				alert(error+" | "+status+" | "+xhr);
				console.log("JSON packages.php: Error");
			},				
			
		});
	
	});
			
//Add New Box
		$(document).on("click",".box_addition", function(){
			//Automatically build the name for the button
				var $button = $(this);
				$button.prop('disabled', true);
				var final = $(this).siblings(".box_selector").last();
				var autoinc = parseInt(final.text());
				autoinc++;
				// var updatedtext = final.text();
				// updatedtext = updatedtext.slice(0,-2)+" "+autoinc;
				var order_number = $("body").attr("data-order-number");
				console.log("Order Number: "+ order_number);
				// console.log("Updated Text: "+ updatedtext);

			//Submit this new name as a record in the database
			$.ajax({
				type: "POST",
				url: '/json/packages.php',
				data: {
					action: "addition",
					order: order_number,
					name: autoinc
				},
				dataType: 'json',
				success: function(id) {
					$(".box_selector").removeClass("active");
				//Finally, output the button
					// alert(final);
					final.clone().text(autoinc).insertAfter(final)
					.attr("data-row-id",id).attr("data-box-shipped", '')
					.addClass("active").removeClass('btn-grey').addClass('btn-secondary');
					$(".box_drop").children("option").last().after("<option data-boxno="+autoinc+" value='"+id+"'>Box "+autoinc+"</option>");
					$(".active_box_selector").each(function(){
						$(this).children("option").last().after("<option data-boxno="+autoinc+" value='"+id+"'>Box "+autoinc+"</option>");		
					});
					$(".box_selector").each(function(){
						$(this).children("option").last().after("<option data-boxno="+autoinc+" value='"+id+"'>Box "+autoinc+"</option>");		
					});
					$(".active_box_selector").val(id);
					
					$button.prop('disabled', false);
					
					console.log("JSON package addition packages.php: Success");
				},
				error: function(xhr, status, error) {
					alert(error+" | "+status+" | "+xhr);
					console.log("JSON package addition packages.php: Error");
				}
			});
			
		});
		
//Change Selected Box
		$(document).on("click",".box_selector",function() {
			$(this).siblings(".box_selector").removeClass("active");
			$(this).addClass("active");
			var num = $(this).attr("data-row-id");
			if ($(".active_box_selector").find("option[value="+num+"]").is(':enabled')){
				$(".active_box_selector").val(num);
			}
		});
		
//Change of a dropdown
		$(document).on("change",".box_drop",function() {
		    var assoc = $(this).attr("data-associated");
		    var pack = $(this).val();
				$.ajax({
					type: "POST",
					url: '/json/packages.php',
					data: {
						"action" : "change",
						"assoc" : assoc,
						"package" : pack
					},
					dataType: 'json',
					success: function(id) {
						console.log("JSON package change packages.php: Success");
						console.log("Package "+assoc+" Set to box id "+pack)
					},
					error: function(xhr, status, error) {
						alert(error+" | "+status+" | "+xhr);
						console.log("JSON package change packages.php: Error");
					}
				});
		});
		
//==============================================================================		
//================================= LOCATIONS ==================================
//==============================================================================
		$(document).on("change", ".warehouse",function() {
			var home = this;
			var limit = $(this).val();
			location_changer('aisle','',home,limit);
			location_changer('shelf','',home,limit);
			
		});
		$(document).on("change", ".place",function() {
			var place = $(this).val();
			$(this).closest(".row").find(".instance option[data-place!='"+place+"']").hide();
			$(this).closest(".row").find(".instance option[data-place='"+place+"']").show();
			$(this).closest(".row").find(".instance option[data-place='"+place+"']:first").prop("selected", true);
		});

//==============================================================================
//===================================== RM =====================================
//==============================================================================
	
	$(document).on("click",".rm_button",function(){
		var partid = $(this).closest("tr").attr("data-part");
		var text = $(this).closest("tr").attr("data-name");
		var invid = $(this).closest("tr").attr("data-invid");
		$("#modalRMBody").find(".item_search").initSelect2("/json/part-search.php","Select a Part","purchase");
		$("#modalRMBody").find(".item_search").setDefault(text,partid);
		$("#modalRMBody").attr("data-partid",partid);
		$("#modalRMBody").attr("data-invid",invid);
		$("#modal-RM").modal("show");
	});
	$(document).on("click","#RM-continue",function() {
		var init_part = $("#modalRMBody").attr("data-partid");
		var new_part = $("#modalRMBody").find(".item_search").val();
		// console.log("OG PART: "+init_part+)
		if (new_part != init_part){
			var invid = $("#modalRMBody").attr("data-invid");
			$.ajax({
				type: "POST",
				url: '/json/rm-processor.php',
				data: {
					"invid" : invid,
					"part" : new_part
				},
				dataType: 'json',
				success: function(result) {
					console.log("JSON RM PROCESSOR | Success | /json/rm-processor.php?invid="+invid+"&part="+new_part);
					location.reload();
					console.log(result);
				},
				error: function(xhr, status, error) {
					alert(error+" | "+status+" | "+xhr);
					console.log("JSON RM PROCESSOR | Failure | /json/rm-processor.php?invid="+invid+"&part="+new_part);
				}
			});

		}
	});
//=================================== End RM ===================================


	$(document).on("click",".repair_button",function(){
		var status = $(this).data("status");
		var invid = $(this).data("invid");
		
		$.ajax({
			type: "POST",
			url: '/json/repair.php',
			data: {
				"invid" : invid,
				"status" : status
			},
			dataType: 'json',
			success: function(result) {
				console.log("JSON RM PROCESSOR | Success | /json/repair.php?invid="+invid+"&status="+status);
				location.reload();
				console.log(result);
			},
			error: function(xhr, status, error) {
				alert(error+" | "+status+" | "+xhr);
				console.log("JSON RM PROCESSOR | Failure | /json/repair.php?invid="+invid+"&status="+status);
			}
		});
	});


//==============================================================================
//=================================== HISTORY ================================== 
//==============================================================================

		$(document).on("click",".serial_original",function() {
			
			var invid = $(this).attr('data-id');
			//Call the AJAX
			$.ajax({
					type: "POST",
					url: '/json/item_history.php',
					data: {
						'inventory' : invid,
						'mode' : 'display'
					},
					dataType: 'json',
					success: function(lines) {
						//Clear the modal
						$(".history_line").remove();
						console.log(lines);
						//Populate the modal
						$.each(lines, function(i, phrase){
							$("#history_items").append("<li class = 'history_line'>"+phrase+"</li>");
						});
						//Show the modal
						$("#modal-history").modal("show");
						
						console.log("JSON history_modal | Success | /json/item_history.php?inventory="+invid+"&mode=display");
					},
					error: function(xhr, status, error) {
						alert(error+" | "+status+" | "+xhr);
						console.log("JSON history_modal | Failure | /json/item_history.php?inventory="+invid+"&mode=display");
					}
			});
		});
//=================================== END HISTORY ================================== 

//==============================================================================
//==================================== RMA =====================================
//==============================================================================

	$(".rma-macro").each(function(){
		var order_number = $("body").data("associated");
		var page = 'rma';
		var order_type = "RMA"
		
	// 	//Left Side Main output on load of the page
	// 	$.ajax({
	// 		type: "POST",
	// 		url: '/json/operations_sidebar.php',
	// 		data: {
	// 			"number": order_number,
	// 			"type": order_type,
	// 			"page": page,
	// 			},
	// 		dataType: 'json',
	// 		success: function(right) {
	// 			$(".rma-macro").append(right);
	// 		},
	// 		complete: function() {
	// 			console.log("JSON operations_sidebar.php?number="+order_number+"&type="+order_type+"&page="+page+" | Success");
	// 		},
	// 		error: function(xhr, status, error) {
	// 			alert(error+" | "+status+" | "+xhr);
	// 			console.error("JSON operations_sidebar.php?number="+order_number+"&type="+order_type+"&page="+page+" | Error");
	// 		}
			
	// 	});
	});



//================================== END RMA ===================================
		

}); //END OF THE GENERAL DOCUMENT READY TAG











			function package_delete(pack, serialid){
				$.ajax({
					type: "POST",
					url: '/json/packages.php',
					data: {
						"action" : "delete",
						"assoc" : serialid,
						"package" : pack
					},
					dataType: 'json',
					success: function(id) {
						console.log("JSON Package Delete | packages.php: Success");
					},
					error: function(xhr, status, error) {
						alert(error+" | "+status+" | "+xhr);
						console.log("JSON Package Delete | packages.php: Error");
					}
				});
			}


			
			function getWorkingDays(startDate, endDate){
				var result = 0;
				var currentDate = startDate;
				while (currentDate <= endDate)  {  
				
				var weekDay = currentDate.getDay();
				if(weekDay != 0 && weekDay != 6)
					result++;
					currentDate.setDate(currentDate.getDate()+1); 
				}
				return result;
			}
			//Adding in all slide sidebar options to pages that utilize the classes depicted below
		
			function freight_date(days){
				var today = new Date();
				var dayOfTheWeek = today.getDay();
				var calendarDays = days;
				var deliveryDay = dayOfTheWeek + days;
				if (deliveryDay >= 6) {
					//deduct this-week days
					days -= 6 - dayOfTheWeek;
					//count this coming weekend
					calendarDays += 2;
					//how many whole weeks?
					var deliveryWeeks = Math.floor(days / 5);
					//two days per weekend per week
					calendarDays += deliveryWeeks * 2;
				}
				
				today.setTime(today.getTime() + calendarDays * 24 * 60 * 60 * 1000);
				
				today = (today.getMonth() + 1) + '/' + today.getDate() + '/' +  today.getFullYear();
				return today;
			}
			function headerOffset() {
				var height = $('header.navbar').height();
		        //get possible filter bar height
		        var heightOPT = 0;
		        var heightError = 0;
		        if ($('.table-header').css("display")!='none'){
		        	heightOPT = $('.table-header').height();
		        }
		        //heightError = $('.general-form-error').height();
		        
		        var offset = height + heightOPT + heightError;
				
				
				$('body').css('padding-top', offset);
			}
			
			//======================== Right side page load ========================
			// This function outputs each of the items on the table, as well as the
			// old information from the database
		
			function line_number(){
				var last = $("#right_side_main").find(".line_line:last").attr("data-line-number");
				if(last){
					return parseInt(last)+1
				}
				else{
					return 1;
				}
				// #right_side_main > tr.easy-output > td.line_line
			}
			function updateTotal(){
				var total = 0.00;
				// #right_side_main > tr.lazy-entry > td:nth-child(8) > input
				$(".easy-output").each(function() {
				    var qty = $(this).find(".line_qty").attr('data-qty');
				    var cost = $(this).find(".line_price").text();
				    if (cost){
				    	cost = Number(cost.replace(/[^0-9\.]+/g,""));
				    }
				    else{
				    	cost = 0.00;
				    }
				    total += cost * qty;
				});
				//Get all the 
				return price_format(total);
			}
			
/*
			$("#service").ready(function() {
				var days = parseInt($('#service option:selected').attr('data-days'));
				if (days != 4){
					$("input[name=ni_date]").val(freight_date(days));
				}
			});
*/
			
			function price_format(ext){
				var display = ext.toFixed(2);
				display = display.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
			    display = '$'+(display);
			    return display;
			}

//Address Suite of functions
			function updateShipTo(){
				if ( $("#mismo").prop( "checked" )){
					var display = $("#bill_to").text().trim();
					var value = $("#bill_to").val();
					console.log("Display: "+display+" | Value: "+value);
					$("#ship_to").setDefault(display,value);
				}
			}


//============================= Inventory Addition =============================
		//Get the url argument parameter
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
		
		
		//This function gets the pages title without the extension aka .php
		function getPageName(url = window.location.href) {
		    var index = url.lastIndexOf("/") + 1;
		    var filenameWithExtension = url.substr(index);
		    var filename = filenameWithExtension.split(".")[0]; 
		    return filename;                                    
		}
	
		//This function checks to see if month is less than 10, after add 0 in front if so
		function getFormattedPartTime(partTime){
	        if (partTime<10)
	           return "0"+partTime;
	        return partTime;
	    }

		function populateSearchResults(e,search,qty) {
			//always must be a valid qty passed in
  		    if (! qty) {
	   			// modalAlertShow("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning", "Qty is missing or invalid. <br><br>If this message appears to be in error, please contact an Admin.");
				return;
			}

			var sub_row = e.closest("tr");
			var editRow = '';
			var line_item_id = 'new';
			var mode = 'append';
			// editing an existing row as opposed to sending new data for appending to items list
			if (! search) {
				var search = '';
   		    	var editRow = parseInt(sub_row.index());
				var line_item_id = sub_row.prev().attr('data-record');
				var mode = 'update';

				// from line_item_submit:
				var search_option = sub_row.find('.item-selected').find('option');
				var new_search = search_option.last().val();
				var old_search = search_option.first().attr('data-search');
				//This line fixes the bug if the user exits the select2 prematurely
				if(isNaN(new_search)){var search = old_search;}
				else{var search = new_search;}
			}

		    var date = sub_row.find("input[name=ni_date]").val();
		    var price = sub_row.find("input[name=ni_price]").val();
   		    var lineNumber = sub_row.find("input[name=ni_line]").val();
   		    var warranty = sub_row.find(".warranty").val();
			var conditionid = sub_row.find(".conditionid").val();
//	    	var partid = row.attr("data-line-id");
//			var qty = row.find("input[name=ni_qty]").val();

			console.log(window.location.origin+"/json/order-table-out.php?line="+lineNumber+"&search="+search+"&date="+date+"&qty="+qty+"&unitPrice="+price+"&warranty="+warranty+"&conditionid="+conditionid+"&id="+line_item_id+"&mode="+mode);

   			$.ajax({
				type: "POST",
				url: '/json/order-table-out.php',
				async: false,
				data: {
			       	"line":lineNumber,
			       	"search":search,
			       	"date":date,
			       	"qty":qty,
			       	"unitPrice":price,
					"warranty":warranty,
					"conditionid":conditionid,
			       	"id":line_item_id,
			       	"mode":mode
				}, // serializes the form's elements.
				dataType: 'json',
				success: function(row_out) {
					if (mode=='update') {
						$("#right_side_main").find("tr:nth-child("+editRow+")").replaceWith(row_out);
						$('#totals_row').find("input[name='np_total']").val(updateTotal());
					} else if (mode=='append') {
						$("#right_side_main").append(row_out);
						
					}
				},
				error: function(xhr, status, error) {
					console.log("Error populating search results: "+error);
				},
				complete: function() {
//					$(".line_war").each(function() {
//						$(this).attr("data-war","test");
//					});

					if (mode=='update') {
						var lazy_row = e.closest(".lazy-entry");
						lazy_row.hide();
						lazy_row.prev(".easy-output").show();
					} else if (mode=='append') {
						var lineNumber = parseInt(sub_row.find("input[name=ni_line]").val());
						if (!lineNumber){lineNumber = 0;}
						$("#go_find_me").val("");
			   			// sub_row.find("input[name=ni_price]").val("");
			   			// $("#search_input > tr.search_row > td:nth-child(7) > input").val("");
			   			// $("#search_input > tr.search_row > td:nth-child(8) > input").val("");
					}
		      		// return qty;
	   				// modalAlertShow("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning", "No entries found. <br><br> Please enter an item and try again.");
				},
			});
        } 


		function location_changer(type,limit,home,warehouse){
			var finder = "."+type;
			$.ajax({
					type: "POST",
					url: '/json/loc_drop.php',
					data: {
						"type": type,
						"selected" : "" ,
						"limit" : limit ,
						"warehouse" : warehouse,
					},
					dataType: 'json',
					success: function(right) {
						$(home).parent().parent().parent().find(finder).parent().html(right);
						console.log("JSON location_changer loc_drop.php: Success");
					},
					error: function(xhr, status, error) {
						alert(error+" | "+status+" | "+xhr);
						console.log("JSON location_changer loc_drop.php: Error");
					}
			});
		}



//==============================================================================
//================================ SHIPPING PAGE ===============================
//==============================================================================
		
		function callback(obj) {
			//Grab each of the parameters from the top line
			var $serial = obj;
			
			//order_num gets any parameter set to on, E.G. Sales Number is also pulled
			var order_num = getUrlParameter('on');
			var savedSerial = $serial.attr('data-saved');
			var item_id = $serial.attr('data-item-id');
			var qty = parseInt($serial.closest('.infiniteSerials').siblings('.remaining_qty').children('input').val());
			var page = getPageName();
			var serial = $serial.val();
			var partid = $serial.closest('tr').find('.part_id').attr('data-partid');
			var conditionid = $serial.closest('tr').find('.condition_field').val();
			var part = $serial.closest('tr').find('.part_id').attr('data-part');
			//Package number will be only used on the shipping order page
			var package_no = $serial.closest('tr').find(".active_box_selector").val();
			
			if(conditionid == '') {
				conditionid = $serial.closest('tr').find('.condition_field').attr('data-condition');
			}
			
			//Clone the serial field for usage when appending more fields
			var $serialClone = $serial.parent().clone();
			
			//alert(getPageName());
		
			//Only if there is some quantity and there is some serial on the inventory addition page
	    	if(((serial != '') || savedSerial != '') && page != 'shipping') {
	    		var $conditionClone = $serial.closest('tr').find('.infiniteCondition').children('select:first').clone();
	    		var $locationClone = $serial.closest('tr').find('.infiniteLocations').children('.row-fluid:first').clone();
	    		var place = $serial.closest('tr').find('.infiniteLocations').children('.row-fluid:first').find('select:first').val();
	    		var instance = $serial.closest('tr').find('.infiniteLocations').children('.row-fluid:first').find('select:last').val();
	    		// alert(place+"-"+instance);
	    		if(place != 'null' && instance != 'null'){
		    		$.ajax({
						type: "POST",
						url: '/json/inventory-add-dynamic.php',
						data: {
							 'partid' : partid,
							 'conditionid' : conditionid,
							 'serial' : serial,
							 'po_number' : order_num,
							 'item_id' : item_id,
							 'savedSerial' : savedSerial,
							 'place' : place,
							 'instance' : instance
						},
						dataType: 'json',
						success: function(result) {
							console.log(result);
							//Once an item has a serial and is generated disable the ability to lot the item for the rest of the editing for users current view
							$serial.closest('tr').find('.lot_inventory').attr('disabled', true);

							if(result['query'] && !result['saved']) {
								//Decrement the qty by 1 after success and no errors detected
								qty--;
								
								//Update the value of the qty left avoiding if it hits below 0
								if(qty >= 0) {
									$serial.closest('.infiniteSerials').siblings('.remaining_qty').children('input').val(qty);
								} else if(qty <= 0) {
									if(localStorage.getItem(result['partid']) != 'shown'){
								    	modalAlertShow('Item Already Received!','Item "'+part+'" has already been RECEIVED in full!<br/><br/>If you continue receiving, the units will be received as non-billable overages.',false);
								    	localStorage.setItem(result['partid'],'shown')	
									}
							    	// $serial.closest('.infiniteSerials').children('input:first').attr('readonly', true);
							    }
								
								//Set matching condition field to the serial saved
								$serial.closest('tr').find('.infiniteCondition').children('select:first').attr("data-serial", serial);
								$serial.closest('tr').find('.locations_tracker:first').attr("data-serial", serial);
								
								//Set Default Values here, remember clone doesn't save select values otherwise it will
								$serialClone.find('input').val("");
								
								$locationClone.find('select:first').val(place);
								$locationClone.find('select:last').val(instance);
								
								$serial.closest('.infiniteSerials').find('button.deleteSerialRow').attr('disabled', false);
								$serial.closest('.infiniteSerials').find('button.updateSerialRow').attr('disabled', true);
								
								$serial.closest('.infiniteSerials').find('button.updateSerialRow').hide();
								$serial.closest('.infiniteSerials').find('button.deleteSerialRow').show();
								
								//Switch buttons from update to delete and enable the disable button
								$serialClone.find('button.updateSerialRow').show();
								$serialClone.find('button.deleteSerialRow').hide();
								$serialClone.find('button.deleteSerialRow').attr('disabled', true);
								
								$serial.closest('.infiniteSerials').prepend($serialClone);
								$serial.closest('tr').find('.infiniteCondition').prepend($conditionClone);
								
								$serial.closest('tr').find('.infiniteCondition').children('select:first').val(conditionid);
								
								$serial.closest('tr').find('.infiniteLocations').prepend($locationClone);
								$serial.closest('.infiniteSerials').find('input:first').focus();
								
								if(qty == 0) {
							    	//$serial.closest('.infiniteSerials').find('input:first').attr('readonly', true);
							    	//alert('Part: ' + part + ' has been received.');
									modalAlertShow('Item Received!','Item "'+part+'" has now been RECEIVED in full!<br/><br/>If you continue receiving, the units will be received as non-billable overages.',false);
							    }
							    
							    $serial.attr("data-saved", serial);

							} else if(result['saved']) {
								$serial.attr("data-saved", serial);
								modalAlertShow('Success', 'Item has been updated.', false);
							} else {
								modalAlertShow('<i class="fa fa-times-circle" aria-hidden="true"></i> Serial Exists', 'Item already exists in inventory. Please enter another serial.', false);
								if(savedSerial != '') {
									$('input[data-saved ="'+savedSerial+'"]').val(savedSerial);
								}
							}
							window.onbeforeunload = null;
							
						},
						error: function(xhr, status, error) {
							alert(error+" | "+status+" | "+xhr);
							console.log("Inventory-add-dynamic.php: ERROR");
						},
						
					});
	    		} else {
	    			modalAlertShow('<i class="fa fa-times-circle" aria-hidden="true"></i> Missing Fields', "Location can not be empty.", false);
	    		}
		    } else if(serial != '' && page == 'shipping') {
				//console.log('/json/shipping-update-dynamic.php?'+'partid='+partid+'&serial='+serial+'&so_number='+order_num+'&conditionid='+conditionid+'&package_no='+package_no);
				//Submit the data from the live scanned boxes
				qty = parseInt($serial.closest('.infiniteSerials').siblings('.remaining_qty').text());

				if(package_no != null) {
			    	$.ajax({
						type: "POST",
						url: '/json/shipping-update-dynamic.php',
						data: {
							 'partid' : partid,
							 'serial' : serial,
							 'so_number' : order_num,
							 'item_id' : item_id,
							 'conditionid' : conditionid,
							 'package_no' : package_no
						},
						dataType: 'json',
						success: function(result) {
							console.log(result);
							
							//Once an item has a serial and is generated disable the ability to lot the item for the rest of the editing for users current view
							// console.log(result);
							if(result['query']) {
								$serial.closest('tr').find('.lot_inventory').attr('disabled', true);
								//Decrement the qty by 1 after success and no errors detected
								qty--;

								//Area to duplicate the box field
								$serial.closest('tr').find(".active_box_selector").first().clone()
								.insertAfter($serial.closest('tr').find(".active_box_selector").first())
								.removeClass("active_box_selector")
								.addClass("drop_box")
								.val($serial.closest('tr').find(".active_box_selector").first().val())
								.attr("data-associated",serial);

								if(qty >= 0) {
									$serial.closest('.infiniteSerials').siblings('.remaining_qty').text(qty);
								}
								$serialClone.find('input').val("");
								
								$serial.closest('.infiniteSerials').find('button').attr('disabled', false);
								$serialClone.find('button').attr('disabled', true);
								
								$serial.closest('.infiniteSerials').prepend($serialClone);
								$serial.closest('tr').find('.infiniteCondition').prepend($conditionClone);
								$serial.closest('.infiniteSerials').find('input:first').focus();
								
								var element = "<input class='form-control input-sm iso_comment check-save' data-savable='true' style='margin-bottom: 10px;' type='textbox' data-part='"+part+"' data-serial='"+serial+"' data-invid='"+result['invid']+"' placeholder='Comments'>";
								
								$serial.closest('tr').find('.infiniteComments').prepend(element);
								
								if(qty == 0) {
							    	$serial.closest('.infiniteSerials').find('input:first').attr('readonly', true);
							    	var date = new Date();
							    	var str = (getFormattedPartTime(date.getMonth() + 1)) + "/" + getFormattedPartTime(date.getDate()) + "/" + date.getFullYear();
							    	
							    	$serial.closest('.infiniteSerials').siblings('.ship-date').text(str);
									modalAlertShow('Item Shipped!','Item "'+part+'" has now been SHIPPED in full!',false);
							    }
							    
							    $serial.attr("data-saved", serial);
							} else {
								alert(result['error']);
							}
							window.onbeforeunload = null;
						
							console.log("Shipping-add-dynamic.php: Success");
						
							
						},
						error: function(xhr, status, error) {
							alert(error+" | "+status+" | "+xhr);
							console.log("Shipping-add-dynamic.php: ERROR");
							console.log('/json/shipping-update-dynamic.php?partid='+partid+'&serial='+serial+'&so_number='+order_num+'&conditionid='+conditionid);
							},	
					});
				} else {
					modalAlertShow('<i class="fa fa-times-circle" aria-hidden="true"></i> Error', 'A Box is required for each item being shipped. <br><br> Please create a box or add the item to an available box.', false);
				}
		    } else if(serial == '') {
		    	modalAlertShow('<i class="fa fa-times-circle" aria-hidden="true"></i> Error', 'Serial is missing.', false);
		    } 
		
		}
