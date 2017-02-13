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
				escapeMarkup: function (markup) { return markup; },//let our custom formatter work
		        minimumInputLength: 0
		    });
		}
		

		$(document).ready(function() {
			

		// ======== Output the header clear for the padding on the page ========
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
			//get main header height
			
			$( window ).resize(function() {
		        headerOffset();
			});
			
			//headerOffset();

			$.when(headerOffset()).then(function(){
				$('.loading_element').css('visibility','visible').hide().fadeIn();
			});
			
		
		//======================== End the header clear ========================

//==============================================================================
//============================== BEGIN ORDER FORM ==============================
//==============================================================================

		//Adding in all slide sidebar options to pages that utilize the classes depicted below
		
		function toggleSidebar() {
		
			var $marginLefty = $('.left-sidebar');
					
			$(window).resize(function() {
				$marginLefty.animate({
					marginLeft: 0
				});
				
				$('.icon-button').addClass('fa-chevron-left');
				$('.icon-button').removeClass('fa-chevron-right');
				
				if($('.left-sidebar .sidebar-container').is(':hidden')){
		            $('.icon-button-mobile').removeClass('fa-chevron-up');
					$('.icon-button-mobile').addClass('fa-chevron-down');
					
					$('.left-sidebar .sidebar-container').fadeIn();
		        }
			});
			
			$('.click_me').on('click', function() {
				// alert('clicked');
				var check = parseInt($marginLefty.css('marginLeft'),10) == 0 ? 'collapsed' : 'not-collapsed';
				$marginLefty.animate({
					marginLeft: (check == 'collapsed') ? -$marginLefty.outerWidth() : 0
					},{
					complete: function() {
						if(check == 'collapsed') {
							//$('.company_meta .row').hide();
						}
					}
				});
				
				if(check == 'collapsed') {
					//$('.shipping-list').animate({width: '90%'}, 550);
					$('.icon-button').addClass('fa-chevron-right');
					$('.icon-button').removeClass('fa-chevron-left');
					//$('.company_meta .row').hide();
				} 
				
				if(check != 'collapsed') {
					//$('.shipping-list').animate({width: '83.33333333333334%'}, 300);
					$('.icon-button').addClass('fa-chevron-left');
					$('.icon-button').removeClass('fa-chevron-right');
					//$('.company_meta .row').show();
				}
			});

			$('.shoot_me').click(function() {
				$('.left-sidebar .sidebar-container').slideToggle(function(){
					if($('.left-sidebar .sidebar-container').is(':visible')){
			            $('.icon-button-mobile').addClass('fa-chevron-up');
						$('.icon-button-mobile').removeClass('fa-chevron-down');
			        }else{
			            $('.icon-button-mobile').addClass('fa-chevron-down');
						$('.icon-button-mobile').removeClass('fa-chevron-up');
			        }
				});
			});
		}
		//========================= Left side main page =========================
		//Load the meta information panel, initialize the clickable fields, and
		//populate whatever fields are prefilled. THIS WILL WORK ACROSS MULTIPLE PAGES!
			
			$(".left-side-main").ready(function(){
				var order_number = 'new';
				var order_type = 'Sale';
				var page = 'order';
				
				order_number = $("body").attr("data-order-number");
				order_type = $("body").attr("data-order-type");
				page = $(".left-side-main").attr("data-page");
				
				//Left Side Main output on load of the page
				$.ajax({
					type: "POST",
					url: '/json/operations_sidebar.php',
					data: {
						"number": order_number,
						"type": order_type,
						"page": page,
						},
					dataType: 'json',
					success: function(right) {
						$(".left-side-main").append(right);
						//If this is an edit page, limit all the appropriate dropdowns
						// alert("success");
						if (page == 'order'){
							var company = $("#companyid").val();
							if(order_type == "Purchase" || order_type == "P" || order_type == "Purchases" ){
								//For any purchase order, I expect that we want to ship and bill to ourselves
								var limit = "25";
							}
							else{
								//On a sales order, we want to find shipping address information.
								var limit = company;
							}

							//Initialize each of the select2 fields when the left side loads.
							$("#companyid").initSelect2("/json/companies.php", "Company");
							$("#bill_to").initSelect2("/json/address-picker.php","Bill to", limit);
							$("#account_select").initSelect2("/json/freight-account-search.php","Account",company);
							$("#ship_to").initSelect2("/json/address-picker.php","Ship to", limit);
							// alert(order_type+", "+company);
							if($("#ship_to").val() == $("#bill_to").val()){
								$("#mismo").prop("checked",true);
							}
							$("#contactid").initSelect2("/json/contacts.php",'Select a Contact',company);

						}
						else{
							// alert(order_type);
							$("#order_selector").initSelect2("/json/order-select.php","Select an Order",order_type);
						}
						console.log("JSON operations_sidebar.php: Success");
					},
					error: function(xhr, status, error) {
						alert(error+" | "+status+" | "+xhr);
						console.log("JSON operations_sidebar.php: Error");
					}
					
				});
				
				toggleSidebar();

				$(document).on("change load","#freight-carrier",function() {
					var carrier = ($("#freight-carrier :selected").attr('data-carrier-id'));
					$("#freight-services").val("Freight Services");
					$("#freight-services").children("option[data-carrier-id!='"+carrier+"']").hide();
					$("#freight-services").children("option[data-carrier-id='"+carrier+"']").show();
				});
			});
			
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
			var order_type = $("body").attr("data-order-type");
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
				var company = $(this).val();
				var	order_type = $("body").attr("data-order-type");
				var limit = company;

				var carrier = $("#carrier").val();
				// alert("Limit: "+company+" | Carrier "+carrier);
				
				// alert(id);
				$("#contactid").initSelect2("/json/contacts.php","",company);
				
				$("#bill_to").initSelect2("/json/address-picker.php",'',limit);
				$("#ship_to").initSelect2("/json/address-picker.php",'',limit);
				
				//Default selector for the addresses
				$.ajax({
					type: "POST",
					url: '/json/address-default.php',
					data: {
						"company": limit,
						"order" : order_type
						},
					dataType: 'json',
					success: function(right) {
						
							var bvalue = right['b_value'];
							
							$("#select2-bill_to-container").html("");
							if (bvalue){
								var bstring = right['b_street'];
								// alert(bstring);
								var useful = right['b_street']+'<br>'+right['b_city']+', '+right['b_state']+' '+right['b_postal_code'];
					    		$("#select2-bill_to-container").html(useful);
					    		$("#bill_to").append("<option selected value='"+bvalue+"'>"+bstring+"</option>");
							}
			    			console.log("bdisplay: "+bstring);
							var svalue = right['s_value'];
							$("#select2-ship_to-container").html("");
							if (svalue){
								var sstring = right['s_street'];
								// alert(sstring);
								var useful = right['s_street']+'<br>'+right['s_city']+', '+right['s_state']+' '+right['s_postal_code'];
					    		$("#select2-ship_to-container").html(useful);
					    		$("#ship_to").append("<option selected value='"+svalue+"'>"+sstring+"</option>");
							}

			    		console.log("JSON address-default.php: Success");
			    		console.log('/json/address-default.php?company='+limit+'&order='+order_type);
					},					
					error: function(xhr, status, error) {
						alert(error+" | "+status+" | "+xhr);
						console.log("JSON address-default.php: Error");
					}
				});
				
				if(order_type == "Purchase"){
					var comp = '25';
				}
				else{
					var comp = company;
				}
				//Account default picker on update of the company
				$.ajax({
					type: "POST",
					url: '/json/account-default.php',
					data: {
						"company": comp,
						"carrier": carrier,
						},
					dataType: 'json',
					success: function(right) {
						// alert(limit);
						var value = right['value'];
						var display = right['display'];
						var set_carrier = right['carrier'];
						// alert(value);
						$("#account_select").attr("data-carrier",set_carrier);
			    		$("#select2-account_select-container").html(display);
			    		$("#account_select").append("<option selected value='"+value+"'>"+display+"</option>");
			    		if (set_carrier){
			    			$("#carrier").val(set_carrier);
			    		}
						console.log("JSON account-default.php: Success");
						console.log("/json/account-default.php?"+"company="+limit+"&carrier="+carrier);
					},					
					error: function(xhr, status, error) {
						alert(error+" | "+status+" | "+xhr);
						console.log("JSON account-default.php: Error");
						console.log("/json/account-default.php?"+"company="+limit+"&carrier="+carrier);
					}
				}).done(function(right) {
				    var new_account = ($("#account_select").attr("data-carrier"));
					if (new_account){
					$.ajax({
						type: "POST",
						url: '/json/dropPop.php',
						data: {
							"field":"services",
							"limit": new_account,
							"size": "col-sm-4",
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
				}
				});
				
				//Default Global Warranty
				// $.ajax({
				// 	type: "POST",
				// 	url: '/json/warranty-default.php',
				// 	data: {
				// 		"company": limit,
				// 		},
				// 	dataType: 'json',
				// 	success: function(right) {
				// 		var bvalue = right['b_value'];
				// 		var bdisplay = right['b_display'];
			 //   		$("#select2-bill_to-container").html(bdisplay)
			 //   		$("#bill_to").append("<option selected value='"+bvalue+"'>"+bdisplay+"</option>");
						
				// 		var svalue = right['s_value'];
				// 		var sdisplay = right['s_display'];
			 //   		$("#select2-ship_to-container").html(sdisplay)
			 //   		$("#ship_to").append("<option selected value='"+svalue+"'>"+sdisplay+"</option>");
			 //   		console.log("JSON address-default.php: Success");
				// 	},					
				// 	error: function(xhr, status, error) {
				// 		alert(error+" | "+status+" | "+xhr);
				// 		console.log("JSON address-default.php: Error");
				// 	}
				// });
				
				$("#account_select").initSelect2("/json/freight-account-search.php","Please Choose a company",limit);
				// alert(new_account);
				
				//Reload the Addresses
				
				//Reload the contact
				// $("#contactid").initSelect2("/json/contacts.php","Select a contact",25)
				// alert(limit);
				// //Populate the terms with the company preferences
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
						$('#terms_div').replaceWith(result);
						console.log("JSON company terms dropPop.php: Success");
					},					
					error: function(xhr, status, error) {
						alert(error+" | "+status+" | "+xhr);
						console.log("JSON company terms dropPop.php: Error");
					}
				});
			});
			
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
			
			$(document).on("change","#carrier",function() {
				var limit = $(this).val();
            	console.log(window.location.origin+"/json/order-table-out.php?ajax=true&limit="+limit+"&field=services&label=Service&id=service&size=col-sm-6");
				//Account default picker on update of the company
				var company = $("#companyid").val();
				$.ajax({
					type: "POST",
					url: '/json/account-default.php',
					data: {
						"company": $("#companyid").val(),
						"carrier": limit,
						},
					dataType: 'json',
					success: function(right) {
						var value = right['value'];
						var display = right['display'];
			    		$("#select2-account_select-container").html(display);
			    		$("#account_select").append("<option selected value='"+value+"'>"+display+"</option>");
						console.log("JSON account-default.php: Success");
						console.log("/json/account-default.php?"+"company="+company+"&carrier="+limit);
					},					
					error: function(xhr, status, error) {
						alert(error+" | "+status+" | "+xhr);
						console.log("JSON account-default.php: Error");
						console.log("/json/account-default.php?"+"company="+company+"&carrier="+limit);
					}
				});
				
				$("#account_select").initSelect2("/json/freight-account-search.php","Please Choose a company",limit);
				$.ajax({
					type: "POST",
					url: '/json/dropPop.php',
					data: {
						"field":"services",
						"limit":limit,
						"size": "col-sm-5",
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
				}
            	// console.log(window.location.origin+"/json/order-table-out.php?ajax=true&limit="+limit+"&field=services&label=Service&id=service&size=col-sm-6");
				// $.ajax({
 
				// });
			});
		//======================== Right side page load ========================
		// This function outputs each of the items on the table, as well as the
		// old information from the database
		
			function linenumber(){
				var last = $("#right_side_main").find(".line_line:last").attr("data-line-number");
				if(last){
					return parseInt(last)+1
				}
				else{
					return 1;
				}
				// #right_side_main > tr.easy-output > td.line_line
			}
			$("#right_side_main").ready(function(){
				var order_number = $("#order_body").attr("data-order-number");
				var order_type = $("#order_body").attr("data-order-type");
				console.log("Order-number: "+order_number+" | Order-type: "+order_type);
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
						console.log("JSON | Initial table load | order-table-out.php: Success");
					},					
					error: function(xhr, status, error) {
						alert(error+" | "+status+" | "+xhr);
						console.log("JSON | Initial table load | order-table-out.php: Error");
					}

					
				});
				$.ajax({
					type: "POST",
					url: '/json/new_paradigm.php',
					data: {
						
					}, // serializes the form's elements.
					dataType: 'json',
					success: function(result) {
						if (result){
							$('#search_input').append(result);
							$(".datetime-picker-line").initDatetimePicker("MM/DD/YYYY");
							// var lineNumber = parseInt($(".multipart_sub").closest("tr").find("input[name=ni_line]").val());
							$(".multipart_sub").closest("tr").find("input[name=ni_line]").val(linenumber());
						}
						else{
							
						}
						console.log("JSON | NewPar Line Pop | new_paradigm.php: Success");
					},					
					error: function(xhr, status, error) {
						alert(error+" | "+status+" | "+xhr);
						console.log("JSON | NewPar Line Pop | new_paradigm.php: Error");
					}	
				});
			});
		
		//MultiPart Search Feature
			$(document).on("keyup","#go_find_me",function(e){
				if (e.keyCode == 13) {
					$(".search_loading").show();
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
							$(".search_loading").hide();
							$(".search_lines").html("").remove();
							if(result == "") {
								$('.nothing_found').show();
							} else {
								$('.nothing_found').hide();
							}
							$("#search_input").append(result)
						},
						error: function(xhr, status, error) {
						   	alert(error+" | "+status+" | "+xhr);
						},					
					});
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
			
	//========================== Usability Functions ==========================
	
	
		//Any time a field is clicked for editing, or double clicked at all, show
		//the easy output portion of the row, populated with the relevant updated pages.
			$(document).on("click",".forms_edit",function() {
				$(this).closest("tr").hide();
				$(this).closest("tr").next().show()
				.find("input[name='ni_date']").parent().initDatetimePicker('MM/DD/YYYY');
				$(this).closest("tr").next().show().find(".item_search").initSelect2("/json/part-search.php","Select a Part",$("body").attr("data-page"));
			});

			$(document).on("dblclick",".easy-output td",function() {
				$(this).closest("tr").hide();
				$(this).closest("tr").next().show()
				.find("input[name='ni_date']").parent().initDatetimePicker('MM/DD/YYYY');
				$(this).closest("tr").next().show().find(".item_search").initSelect2("/json/part-search.php","Select a Part",$("body").attr("data-page"));
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
			
			function price_format(ext){
				var display = ext.toFixed(2);
				display = display.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
			    display = '$'+(display);
			    return display;
			}
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
			
//Function to submit the individual line item edits
			function line_item_submit(){
				
				
				var new_search = $('.line_item_submit').closest("tr").find('.item-selected').find("option").last().val();
				var old_search = $('.line_item_submit').closest("tr").find('.item-selected').find("option").attr("data-search");
				var line_item_id = $('.line_item_submit').closest("tr").prev().data('record');

				//This line fixes the bug if the user exits the select2 prematurely   
				if(isNaN(new_search)){var search = old_search;}
				else{var search = new_search;}
			    var date = $('.line_item_submit').closest("tr").find("input[name=ni_date]").val();
	   		    var qty = $('.line_item_submit').closest("tr").find("input[name=ni_qty]").val();
			    var price = $('.line_item_submit').closest("tr").find("input[name=ni_price]").val();
	   		    var lineNumber = $('.line_item_submit').closest("tr").find("input[name=ni_line]").val();
	   		    var warranty = $('.line_item_submit').closest("tr").find(".warranty").val();
	   		    var editRow = ((parseInt($('.line_item_submit').closest("tr").index())));
	   		    var condition = $('.line_item_submit').closest("tr").find(".condition").val();

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
						"warranty":warranty,
						"condition":condition,
						"mode":'update'
						},
					dataType: 'json',
					success: function(row_out) {
						$("#right_side_main").find("tr:nth-child("+editRow+")").replaceWith(row_out);
						console.log("order-table-out.php : Success");
					},
					error: function(xhr, status, error) {
						console.log("order-table-out.php : Error");
					   	alert(error);
					},
					
				});
	
		    	$(this).closest(".lazy-entry").hide();
		    	$(this).closest("tr").prev(".easy-output").show();
			
				
			}
			
			$(document).on("click",".line_item_submit",function() {
				line_item_submit();
			});
			
			$(document).on("click",".line_item_unsubmit",function() {
				var defaultQty;
				
				$(this).closest("tr").find("input").each(function() {
					defaultQty = $(this).data('value');
					$(this).val(defaultQty);	
				});
				
		    	$(this).closest(".lazy-entry").hide();
		    	$(this).closest("tr").prev(".easy-output").show();
			});

//New Multi-line insertion 			
			$(document).on("click",".multipart_sub",function(e) {
				var isValid = nonFormCase($(this), e);
				
				if(isValid) {
		   		    $(".search_lines").each(function() {
					    var date = $(".multipart_sub").closest("tr").find("input[name=ni_date]").val();
					    var price = $(".multipart_sub").closest("tr").find("input[name=ni_price]").val();
			   		    var lineNumber = $(".multipart_sub").closest("tr").find("input[name=ni_line]").val();
			   		    var warranty = $(".multipart_sub").closest("tr").find(".warranty").val();
		   		    	var partid = $(this).attr("data-line-id");
		   		        var qty = $(this).find("input[name=ni_qty]").val();
						var condition = $(".multipart_sub").closest("tr").find(".condition").val();
						
					
	   		        if(qty){
	       					$.ajax({
								type: "POST",
								url: '/json/order-table-out.php',
								data: {
					   		    	"line":lineNumber,
					   		    	"search":partid,
					   		    	"date":date,
					   		    	"qty":qty,
					   		    	"unitPrice":price,
									"warranty":warranty,
									"condition":condition,
					   		    	"id": 'new',
					   		    	"mode":'append'
									}, // serializes the form's elements.
								dataType: 'json',
								success: function(row_out) {
									$("#right_side_main").append(row_out);
									$(".search_lines").html("").remove();
									$(".multipart_sub").closest("tr").find("input[name=ni_line]").val(linenumber());
	 							}
							});
		   		        }
		   		    });
		   		    
					var lineNumber = parseInt($(".multipart_sub").closest("tr").find("input[name=ni_line]").val());
					if (!lineNumber){lineNumber = 0;}
					$("#go_find_me").val("");
	    			$(".multipart_sub").closest("tr").find("input[name=ni_price]").val("");
	       			$("#search_input > tr.search_row > td:nth-child(7) > input").val("");
	       			$("#search_input > tr.search_row > td:nth-child(8) > input").val("");
				}
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

//Address Suite of functions
			function updateShipTo(){
				if ( $("#mismo").prop( "checked" )){
					
					var display = $("#select2-bill_to-container").html()
					var value = $("#bill_to").val();
					var option = $('<option></option>').
					prop('selected', true).
					text(display).
					val(value);
					/* insert the option (which is already 'selected'!) into the select */
					option.appendTo($("#ship_to"));
					/* Let select2 do whatever it likes with this */
					$("#ship_to").trigger('change');
					
					
		    		
				}
			}
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
					}
					else{
						//$("#bill_display").hr("<div //id='bill_display'>"+right+"</div>");	
						$("#mismo").prop("checked",false);
					}
				}
			});

			$(document).on("click", "#address-continue", function(e) {
			
				//Non-form case uses data-validation tag on the button which points to the container of all inputs to be validated by a required class
				//('object initiating the validation', the event, 'type of item being validated aka modal')
				var isValid = nonFormCase($(this), e, 'modal');
				
				if(isValid) {

				    var field = '';
				    field = $("#address-modal-body").attr("data-origin");
				    
				    var name = $("#add_name").val();
					var line_1 = $('#add_line_1').val();
					var line2 = $('#add_line2').val();
					var city = $('#add_city').val();
					var state = $('#add_state').val();
					var zip = $('#add_zip').val();
					var id = $("#address-modal-body").attr("data-oldid");
					// alert(ad['id']);
					var text = name;
					
					$("#address-modal-body").attr("data-oldid",'');
					
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
				    	
				    	console.log("Return from Address Submission: "+data);
				    	
				    	if (!id){
				    		//If it didn't have an update, it is a new field
					    	if (field == "ship_to"){
					    		// $("#select2-ship_to-container").html(line_1);
					    		// $("#ship_to").append("<option selected value='"+data+"'>"+line_1+"</option>");
					    		// $("#ship_to").val(data);
			    					var option = $('<option></option>').
										prop('selected', true).
										text(line_1).
										val(data);
										/* insert the option (which is already 'selected'!) into the select */
										option.appendTo($("#ship_to"));
										/* Let select2 do whatever it likes with this */
										$("#ship_to").trigger('change');
					    		
					    		// updateShipTo();
					    		//$("#ship_display").html();	
					    	}
					    	else{
					    		// $("#select2-bill_to-container").html(text);
			    					var option = $('<option></option>').
										prop('selected', true).
										text(line_1).
										val(data);
										/* insert the option (which is already 'selected'!) into the select */
										option.appendTo($("#bill_to"));
										/* Let select2 do whatever it likes with this */
										$("#bill_to").trigger('change');
					    	}
				    	}
				    	else{
				    		//Otherwise, this is an old field
				    		if (field == "ship_to"){
				    			$("#select2-ship_to-container").text(line_1);
				    			if ($("#mismo").prop("checked")){
				    				$("#select2-bill_to-container").text(line_1);
				    			}
				    		}
				    		else{
				    			$("#select2-bill_to-container").text(line_1);
			    				if ($("#mismo").prop("checked")){
				    				$("#select2-ship_to-container").text(line_1);
				    			}
				    		}

				    	}
				    	
				    	
				    	$('.modal').modal('hide');
				    });
				}
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
				var add_id = drop.last('option').val();
				console.log(add_id);
					$.ajax({
						type: "POST",
						url: '/json/address-pull.php',
						data: {
							'address' : add_id,
						},
						dataType: 'json',
						success: function(address) {
							console.log(address);
							$("#address-modal-body").attr("data-oldid",add_id);
							$("#add_name").val('').val(address.name);
							$('#add_line_1').val('').val(address.street);
							$('#add_city').val('').val(address.city);
							$('#add_state').val('').val(address.state);
							$('#add_zip').val('').val(address.postal_code);
							
							$("#modal-address").modal('show');
							
							
							console.log("Address Grab - address-grab.php: Success");
						},
						error: function(xhr, status, error) {
						   	alert(error);
						   	console.log("Address grab: Error");
						},
					});
				});
			
			$(document).on("click","#mismo",function() {
				updateShipTo();
			});
				

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
		
			    $.post("/json/accountSubmit.php", {'test[]' : data},function(data){
					$("#select2-account_select-container").html(account);
					$("#carrier").val(carrier);
					$("#account_select").append("<option selected value='"+data+"'>"+account+"</option>");	
			    	$("#account-modal-body").find('#modal_carrier').val('');
			    	$("#account-modal-body").find("input[name='na_account']").val('');
			    	$("#account-modal-body").find('input[name="associate"]').prop("checked",false);
			    });
				$("#account_select").initSelect2("/json/freight-account-search.php","Account",company);
			});

//Global Warranty function
			$(document).on("change","#warranty_global",function() {
				var value = $(this).val();
				var text = $("#warranty_global option:selected").text();
				
				console.log(window.location.origin+"/json/dropPop.php?ajax=true&limit="+value+"&field=services&label=Service&id=service&size=col-sm-6");
				if (value != "no"){
					$(".line_war").text(text)
					.attr("data-war",value);
					$.ajax({
						type: "POST",
						url: '/json/dropPop.php',
						data: {
							"field": "warranty",
							"selected": value,
							"limit": '',
							"size": "warranty",
							"id":"new_row_warranty"
							},
						dataType: 'json',
						success: function(result) {
							// alert(result);
							$("#new_row_warranty").replaceWith(result);
							$('#new_warranty').parent().replaceWith(result)
							.parent().removeClass('col-md-12');
							console.log("Warranty - dropPop.php: Success");
						}
					});
				}
			});

//Conditional Global change
			$(document).on("change","#condition_global",function() {
				var value = $(this).val();
				var text = $("#condition_global option:selected").text();
				
				console.log(window.location.origin+"/json/dropPop.php?ajax=true&limit="+value+"&field=services&label=Service&id=service&size=col-sm-6");
				if (value != "no"){
					$(".line_cond").text(text)
					.attr("data-cond",value);
					$.ajax({
						type: "POST",
						url: '/json/dropPop.php',
						data: {
							"field": "condition",
							"selected": value,
							"limit": '',
							"size": "condition",
							"id":"condition"
							},
						dataType: 'json',
						success: function(result) {
							// alert(result);
							$("#condition").replaceWith(result);
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
				if($(".search_lines").length > 0){
					line_item_submit();
				}
				if(isValid && $('.lazy-entry:hidden').length > 0) {
					//Get page macro information
					var order_type = $(this).closest("body").attr("data-order-type"); //Where there is 
					var order_number = $(this).closest("body").attr("data-order-number");
	
					//Get General order information
					var created_by = $("#sales-rep").attr('data-creator');
					var repid = $("#sales-rep option:selected").attr("data-rep-id");

					var company = $("#companyid").val();
					
					var contact = $("#contactid").val();
					if (contact.includes("new")){
						contact = $("#select2-contactid-container").text();
						//Get rid of the 'Add' portion of the text
						contact = contact.slice(4);
					}
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
					if (($('#account_select').last('option').val())){
						var account = $('#account_select').last('option').val();
					}
					else{
						var account = '';
					}
					var pri_notes = $('#private_notes').val();
					var pub_notes = $('#public_notes').val();
					//var warranty = $('.warranty').val();

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
										console.log(data.message);
									}
								}
							},
							error: function(data, textStatus, errorThrown) {
							},
						});
					}
	
	
					//-------------------------- Right hand side --------------------------
					//Get Line items from the right half of the page
					var submit = [];

					//This loop runs through the right-hand side and parses out the general values from the page
					$(this).closest("body").find("#right_side_main").children(".easy-output").each(function(){
						var row = {
							"line_number" : $(this).find(".line_line").attr("data-line-number"),
							"part" : $(this).find(".line_part").attr("data-search"),
							"id" : $(this).find(".line_part").attr("data-record"),
							"date" : $(this).find(".line_date").attr("data-date"),
							"condition" : $(this).find(".line_cond").attr("data-cond"),
							"warranty" : $(this).find(".line_war").attr("data-war"),
							"price" : $(this).find(".line_price").text(),
							"qty" : $(this).find(".line_qty").attr("data-qty"),
						};

							// alert("line_number "+row["line_number"]);
							// alert("part "+row["part"]);
							// alert("id "+row["id"]);
							// alert("date "+row["date"]);
							// alert("condition "+row["condition"]);
							// alert("warranty "+row["warranty"]);
							// alert("price "+row["price"]);
							// alert("qty "+row["qty"]);
							
							// "line_number"+line_number+"part"+part+"id"+id+"date"+date+"condition"+condition+"warranty"+warranty+"price"+price+"qty"+qty;

						submit.push(row);
					});
	
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
							}, // serializes the form's elements.
						dataType: 'json',
						success: function(form) {
							var on = form["order"];
							var ps = form["type"];
							console.log("SAVED"+on+" | Order"+ps);
							console.log(form['insert']);
							console.log(form["error"]);
							console.log(form["update"]);
							console.log(form["update"]);
							console.log(form['trek']);
							console.log(form['update']);
							console.log(form['input']);
							window.location = "/order_form.php?ps="+ps+"&on="+on;
						},
						error: function(xhr, status, error) {
						   	console.log("Order-form-submission Error:");
						   	console.log(error);
//						   	"&userid="+userid+"&company="+company+"&order_type="+order_type+"&order_number="+order_number+"&contact="+contact+"&assoc="+assoc+"&tracking="+tracking+"&ship_to="+ship_to+"&bill_to="+bill_to+"&carrier="+carrier+"&account="+account+"&terms="+terms+"&service="+service+"&pri_notes="+pri_notes+"&pub_notes="+pub_notes;
							
						},
					});
				} else if($('.lazy-entry:visible').length > 0) {
					alert("Please save all changes before updating.");
				} else {
					$(window).scrollTop();
				}
			});
			
//========================== END COMPLETE PAGE SUBMIT =========================
			//Cancel button?
			

//Order Form Calendar Toggle Dates
			$('.toggle-cal-options').click(function(e){
				e.preventDefault();
				if ($(this).data('name') == 'show') {
		            $('.date-options').animate({
		                width: '295px'
		            });
		            $(this).data('name', 'hide')
		        } else {
		            $('.date-options').animate({
		                width: '100%'
		            }, function() {
		            	$('.cal-buttons').attr( "style", "" );;
		            });
		            $(this).data('name', 'show')
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
					$(this).closest("body").children(".table-header").show();
					$(this).closest("body").children(".initial-header").hide();
					$(this).closest(".col-lg-6").addClass("shipping-dash-full");
					
					//Show everything
					$(this).closest('.shipping-dash').children('.table-responsive').find('.show_more').show();
					
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
					$(this).closest("body").children(".initial-header").show();
					$(".shipping-dash-full").removeClass("shipping-dash-full");
					$(this).closest("table").find(".overview").hide();
					$(this).parents("body").find(".shipping_section_head").fadeIn("fast");
					$('.col-lg-6').show();
					
					$(this).closest(".shipping-dash-remove").addClass("shipping-dash");
					
					//Hide all elements over the count of 10
					$(this).closest('.shipping-dash').children('.table-responsive').find('.show_more').hide();
					
					$(this).closest(".shipping-dash-remove").removeClass("shipping-dash-remove");
					
					//$(this).closest("div").siblings(".shipping-dash").fadeIn("slow");
					$(this).parents("body").find(".overview").hide();
					$(this).parents("body").children("#view-head").hide();
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
//==============================================================================
//================================ SHIPPING PAGE ===============================
//==============================================================================
		
		$('body').on('change keyup paste', 'input[name="NewSerial"]', function(e) {
		     if( $( this ).val() != '' )
		         window.onbeforeunload = function() { return "You have unsaved changes."; }
		});
		
		function callback(obj) {
			//Grab each of the parameters from the top line
			var $serial = obj;
			
			//po_number gets any parameter set to on, E.G. Sales Number is also pulled
			var po_number = getUrlParameter('on');
			var savedSerial = $serial.attr('data-saved');
			var qty = parseInt($serial.closest('.infiniteSerials').siblings('.remaining_qty').children('input').val());
			var page = getPageName();
			var serial = $serial.val();
			var partid = $serial.closest('tr').find('.part_id').data('partid');
			var condition = $serial.closest('tr').find('.condition_field').val();
			var part = $serial.closest('tr').find('.part_id').data('part');
			//Package number will be only used on the shipping order page
			var package_no = $serial.closest('tr').find(".active_box_selector").val();
			
			if(condition == '') {
				condition = $serial.closest('tr').find('.condition_field').data('condition');
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
							 'condition' : condition,
							 'serial' : serial,
							 'po_number' : po_number,
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
								    	alert('Serials Exceed Amount of Items Purchased in the Purchase Order. Please update Purchase Order. Item will be added to Inventory');
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
								
								$serial.closest('tr').find('.infiniteCondition').children('select:first').val(condition);
								
								$serial.closest('tr').find('.infiniteLocations').prepend($locationClone);
								$serial.closest('.infiniteSerials').find('input:first').focus();
								
								if(qty == 0) {
							    	//$serial.closest('.infiniteSerials').find('input:first').attr('readonly', true);
							    	//alert('Part: ' + part + ' has been received.');
									modalAlertShow('Part Received!','Part "'+part+'" has now been received in full!<br/><br/>If you continue receiving, the units will be received as non-billable overages.',false);
							    }
							    
							    $serial.attr("data-saved", serial);

							} else if(result['saved']) {
								$serial.attr("data-saved", serial);
								alert('Item has been updated.');
							} else {
								alert('Serial already exists for this item.');
							}
							window.onbeforeunload = null;
							
						},
						error: function(xhr, status, error) {
							alert(error+" | "+status+" | "+xhr);
							console.log("Inventory-add-dynamic.php: ERROR");
						},				
						
					});
	    		} else {
	    			alert("Location can not be empty.");
	    		}
		    } else if(serial != '' && page == 'shipping') {
				//console.log('/json/shipping-update-dynamic.php?'+'partid='+partid+'&serial='+serial+'&so_number='+po_number+'&condition='+condition+'&package_no='+package_no);
				//Submit the data from the live scanned boxes
				qty = parseInt($serial.closest('.infiniteSerials').siblings('.remaining_qty').text());

				if(package_no != null) {
			    	$.ajax({
						type: "POST",
						url: '/json/shipping-update-dynamic.php',
						data: {
							 'partid' : partid,
							 'serial' : serial,
							 'so_number' : po_number,
							 'condition' : condition,
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
							    	alert('Part: ' + part + ' has been shipped.');
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
							console.log('/json/shipping-update-dynamic.php?partid='+partid+'&serial='+serial+'&so_number='+po_number+'&condition='+condition);
							},	
					});
				} else {
					alert('A Box required.');
				}
		    } else if(serial == '') {
		    	alert('Serial Missing');
		    } 
		
		}
//This function also handles the functionality for the shipping page
		$('body').on('keypress', 'input[name="NewSerial"]', function(e) {
			if(e.which == 13) {
				callback($(this));
			}
		});
		
		$('body').on('click', '.updateSerialRow', function(e) {
			callback($(this).closest('tr').find('input[name="NewSerial"]:first'));
		});
		
		$(document).on('click', '.serial-expand', function() {
			var data = $(this).data('serial');
			
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
				$(this).closest('tr').find('.infiniteSerials').find('input').val('');
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
				var partid = $(this).find('.part_id').data('partid');
				var serials = [];
				var savedSerials = [];
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
						window.location = "/shipping_home.php?po=true";
					//Error occured enough to stop the page from continuing
					} else if(result['error'] != undefined) {
						alert(result['error']);
						$click.attr('id','save_button_inventory');
					//Nothing was change
					} else {
						//alert('No changes have been made.');
						$click.attr('id','save_button_inventory');
						window.location = "/shipping_home.php?po=true";
					}
				},
				error: function(xhr, status, error) {
					//alert(error+" | "+status+" | "+xhr);
					window.location = "/shipping_home.php?po=true";
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
		$('#btn_update').click(function(e){
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
			
			$('.shipping_update').children('tbody').children('tr').each(function() {
				$(this).find('.iso_comment').each(function() {
					//isoCheck.push($(this).data('serial'));
					if($(this).val() != '') {
						serialid.push($(this).data('invid'));
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
				if(!$(this).hasClass('order-complete')) {
					var partid = $(this).find('.part_id').data('partid');
					var serials = [];
					var savedSerials = [];
					var boxes = [];
					var condition;
					var lot = false;
					var qty;

					//Grab the conidtion value set by the sales order
					condition = $(this).find('.condition_field').data('condition');
					
					$('.box_group').find('.box_selector').each(function() {
						boxes.push($(this).data('row-id'));
					});
					
					$(this).find('.infiniteSerials').find('input').each(function() {
						serials.push($(this).val());
						savedSerials.push($(this).attr('data-saved'));
						
						//If an item was saved previously then mark the page as something was edited
						if($(this).attr('data-saved') != '') {
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
					items.push(condition);
					items.push(lot);
					items.push(qty);
					items.push(boxes);
				}
			});
			
			//Testing purposes
			console.log(items);
			
			$.ajax({
				type: 'POST',
				url: '/json/shipping-update.php',
				data: {'so_number' : so_number, 'items' : items},
				dataType: 'json',
				success: function(data) {
					console.log('Save Data ' + data['test']);
					
					if((data['query'] || checkChanges) && data['error'] == undefined) {
						//In case a warning is triggered but data is still saved successfully
						window.onbeforeunload = null;
						window.location.href = window.location.href + "&success=true";
					//Error occured enough to stop the page from continuing
					} else if(data['error'] != undefined) {
						alert(data['error']);
						$click.attr('id','btn_update');
					//Nothing was change
					} else {
						$click.attr('id','btn_update');
						//window.location.href = window.location.href + "&success=true";
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
			
			var completed = $(this).data('datestamp');
		
			$('.shipping_update').children('tbody').children('tr').each(function() {
				$(this).find('.iso_comment').each(function() {
					//isoCheck.push($(this).data('serial'));
					if($(this).val() != '') {
						if(init) {
							$('.iso_broken_parts').empty();
							init = false;
						}
						//($(this).data('serial'));
						var element = "<tr class='damaged'>\
										<td>"+$(this).data('part')+"</td>\
										<td>"+$(this).data('serial')+"</td>\
										<td class='comment-data' data-invid='"+$(this).data('invid')+"' data-comment ='"+$(this).val()+"' data-part = '"+$(this).data('part')+"' data-serial = '"+$(this).data('serial')+"'>"+$(this).val()+"</td>\
									</tr>";
						$('.iso_broken_parts').append(element);
					}
				});
			});
			
			if(init) {
				$('.iso_broken_parts').empty();
				
				var element = "<tr>\
								<td><b>No Defects/Damage in Order</b></td>\
								<td></td>\
								<td></td>\
							</tr>";
				$('.iso_broken_parts').append(element);
			}
			
			$("#modal-iso").modal("show");
			
			if(completed == '') {
				$('.nav-tabs a[href="#iso_quality"]').tab('show');
			} else {
				$('.nav-tabs a[href="#iso_match"]').tab('show');
				$('.nav-tabs a').attr("data-toggle","tab");
			}
		} else {
			alert('No items queued to be shipped.');
		}
	});
	
	
	//This function auto opens the next locations drop down when the first one is changed
	$(document).on('change', '.infiniteLocations .instance:first select', function() {
		$(this).closest('tr').find('.infiniteSerials').find('input:first').focus();
	});
	
	$(document).on('click','.btn_iso_parts', function(e) {
		e.preventDefault();
		var damage = false;
		var so_number, partName;
		var serialid = [];
		var serialComments = [];
		
		so_number = $('.shipping_header').data('so');
		
		if($('.iso_broken_parts').find('.damaged').length) {
			damage = true;
		}
	
		if(damage) {
			$('.iso_broken_parts').children('tr.damaged').each(function() {
				
				var invid = $(this).find('.comment-data').data('invid');
				var serial = $(this).find('.comment-data').data('serial');
				var issue = $(this).find('.comment-data').data('comment');
				
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
		
		so_number = $('.shipping_header').data('so');

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
				var order_number = $(".box_selector.active").data('order-number');
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
					
					var status = $(".box_selector.active").data('box-shipped');
					
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
							
							//After the edit modal has been set with the proper data, show it
							$("#modal-package").modal("show");
						}
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
					.addClass("active");
					$(".box_drop").children("option").last().after("<option value='"+id+"'>Box "+autoinc+"</option>");
					$(".active_box_selector").each(function(){
						$(this).children("option").last().after("<option value='"+id+"'>Box "+autoinc+"</option>");		
					});
					$(".active_box_selector").val(id);
					
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
		
//==============================================================================		
//================================= LOCATIONS ==================================
//==============================================================================
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
			$(this).closest(".row").find(".instance").val("");
		});
		
}); //END OF THE GENERAL DOCUMENT READY TAG
//=========================== End Inventory Addition ===========================

//==============================================================================
//=================================== HISTORY ================================== 
//==============================================================================

		$(document).on("click",".serial_original",function() {
			
			var invid = $(this).data('id');
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

//=============================== Inventory Edit ===============================
	// $(document).ready(function() {
	// 	var search = $("body").attr("data-search");
	// 	//Inventory
	// 	$.ajax({
	// 		type: "POST",
	// 		url: '/json/inventory-out.php',
	// 		data: {
	// 			"search": search,
	// 			},
	// 		dataType: 'json',
	// 		success: function(right) {
	// 			$(".loading_element").append(right);
	// 		}
	// 	});

		
	// 	$(document).on("click",".part_filter",function() {
			
	// 	});
	// });
	

		
//============================= END INVENTORY EDIT =============================


//************************* Legacy Code Worth Keeping *************************


//This function submited a new row on the order forms when the row was static.
	// $('#forms_submit').on("click", function() {
	// 	var company = $("#").find('.item-selected').find("option").last().val();
	//     var search = $(this).closest("tr").find('#item-selected').find("option").val();
	//     var date = $(this).closest("tr").find("input[name=ni_date]").val();
	// 		    var qty = $(this).closest("tr").find("input[name=ni_qty]").val();
	//     var price = $(this).closest("tr").find("input[name=ni_price]").val();
	// 		    var lineNumber = $(this).closest("tr").find("input[name=ni_line]").val();
	// 		    var warranty = $(this).closest("tr").find(".warranty").val();
		
	
	// 	$.ajax({
			
	// 		type: "POST",
	// 		url: '/json/order-table-out.php',
	// 		data: {
	//  		    	"line":lineNumber,
	//  		    	"search":search,
	//  		    	"date":date,
	//  		    	"qty":qty,
	//  		    	"unitPrice":price,
	// 			"warranty":warranty,
	//  		    	"id": 'new',
	//  		    	"mode":'append'
	// 			}, // serializes the form's elements.
	// 		dataType: 'json',
	// 		success: function(row_out) {
	// 			$("#right_side_main").append(row_out);
	// 		}
	// 	});
	
	// 	$(this).closest("tr").children("td:first-child").show();
	// 	$(this).closest("tr").children("td").slice(1).children()
	// 	.val("")
	// 	.toggle();
	// 	$(this).closest("tr").find(".item_search").html("\
	// 					<select class='item_search'>\
	// 					</select>\
	// 	")
	// 	$(this).closest("tr").find(".select2-selection__rendered").html('');
		
	// });
