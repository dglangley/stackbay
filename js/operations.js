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
							$("#companyid").initSelect2("/json/companies.php");
							$("#account_select").initSelect2("/json/freight-account-search.php","Account",company);
							$("#bill_to").initSelect2("/json/address-picker.php","Bill to", limit);
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
			$(document).on("keyup","#search_input > tr > td > input",function() {
				var qty = 0;
				$.each($(".search_lines"),function(){
					var s_qty = ($(this).find("input[name=ni_qty]").val());
					if (s_qty){
						qty += (parseInt($(this).find("input[name=ni_qty]").val()));
					}
				});
				var display = price_format(qty * parseFloat($(".search_row").find("input[name=ni_price]").val()));
				$("tfoot").find("input[name=ni_ext]").val(display);
				$("#search_input > tr.search_row > td:nth-child(7) > input").val(qty);
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
		
		//If the company information changes, run
			$(document).on("change","#companyid",function() {
				var company = $(this).val();
				var	order_type = $("body").attr("data-order-type");
				var limit = '';
				$("#account_select").initSelect2("/json/freight-account-search.php","Please Choose a company",company);
				if(order_type == "Purchase" || order_type == "P" || order_type == "Purchases" ){
					limit = "25";
				}
				else{
					limit = company;
				}
				var carrier = $("#carrier").val();
				//Default selector for the addresses
				$.ajax({
					type: "POST",
					url: '/json/address-default.php',
					data: {
						"company": limit,
						},
					dataType: 'json',
					success: function(right) {
						var bvalue = right['b_value'];
						var bdisplay = right['b_display'];
			    		$("#select2-bill_to-container").html(bdisplay)
			    		$("#bill_to").append("<option selected value='"+bvalue+"'>"+bdisplay+"</option>");
						
						var svalue = right['s_value'];
						var sdisplay = right['s_display'];
			    		$("#select2-ship_to-container").html(sdisplay)
			    		$("#ship_to").append("<option selected value='"+svalue+"'>"+sdisplay+"</option>");
					},
				});
				
				//Account default picker on update of the company
				$.ajax({
					type: "POST",
					url: '/json/account-default.php',
					data: {
						"company": limit,
						"carrier": carrier,
						},
					dataType: 'json',
					success: function(right) {
						var value = right['value'];
						var display = right['display'];
			    		$("#select2-account_select-container").html(display)
			    		$("#account_select").append("<option selected value='"+value+"'>"+display+"</option>");
					},
				});
				
				//Reload the Addresses
				$("#bill_to").initSelect2("/json/address-picker.php",'',limit);
				$("#ship_to").initSelect2("/json/address-picker.php",'',limit);
				
				//Reload the contact
				$("#contactid").initSelect2("/json/contacts.php","Select a contact",company)
				
				//Populate the terms with the company preferences
				$.ajax({
					type: "POST",
					url: '/json/dropPop.php',
					data: {
						"field":"terms",
						"limit":company,
						"size": "col-sm-6",
						"label": "Terms:"
						}, // serializes the form's elements.
					dataType: 'json',
					success: function(result) {
						$('#terms_div').replaceWith(result);
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
				today.setDate(today.getDate() + days);
				
				var formatted = ('0' + (today.getMonth()+1)).slice(-2) + '/' 
            		+ ('0' + today.getDate()).slice(-2) + '/'
            		+ today.getFullYear();
				
				return formatted;
			}
			
			$(document).on("change","#carrier",function() {
				var limit = $(this).val();
            	// console.log(window.location.origin+"/json/order-table-out.php?ajax=true&limit="+limit+"&field=services&label=Service:&id=service&size=col-sm-6");
				$.ajax({
					type: "POST",
					url: '/json/dropPop.php',
					data: {
						"field":"services",
						"limit":limit,
						"size": "col-sm-6",
						"label": "Service:",
						"id" : "service"
						}, // serializes the form's elements.
					dataType: 'json',
					success: function(result) {
						$('#service_div').replaceWith(result);
						var days = parseInt($("#service :selected").attr("data-days"));
						$("input[name=ni_date]").val(freight_date(days));
						
					}
				});
			});
			$(document).on("change","#service",function() {
				var days = parseInt($("#service :selected").attr("data-days"));
				$("input[name=ni_date]").val(freight_date(days));
				
            	// console.log(window.location.origin+"/json/order-table-out.php?ajax=true&limit="+limit+"&field=services&label=Service:&id=service&size=col-sm-6");
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
					},
					error: function(xhr, status, error) {
					   	alert(error+" | "+status+" | "+xhr);
					},					
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
	   		    var warranty = $(this).closest("tr").find(".warranty").val();
	   		    var editRow = ((parseInt($(this).closest("tr").index())));
	   		    var condition = $(this).closest("tr").find(".condition").val();

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
					},
					error: function(xhr, status, error) {
						   	alert(error);
					},
					
				});
	
		    	$(this).closest(".lazy-entry").hide();
		    	$(this).closest("tr").prev(".easy-output").show();
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
			$(document).on("click",".multipart_sub",function() {
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
		    		$("#select2-ship_to-container").html(display)
		    		$("#ship_to").append("<option selected value='"+value+"'>"+display+"</option>");
				}
			}
			$(document).on("change","#ship_to, #bill_to",function() {
				//Get the identifier of the initial textbox
				var origin = ($(this).parent().find('select').attr('id'));
				var right = $(this).text();
				
				
				if($(this).val().indexOf("Add") > -1){
					//Gather the address from the select2 field
					var addy = ($(this).val().slice(4));
					if (isNaN(addy.slice(0,1))){
						//If the first number is the address, assume the user is searching by an address name
						$("#address-modal-body").find("input[name='na_name']").val(addy);
					}
					else{
						//Otherwise, if it is a number, assume they were searching by the address itself
						$("#address-modal-body").find("input[name='na_line_1']").val(addy);
					}
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
			$(document).on("click", "#address-continue", function() {
			    var address = [];
			    var text = '';
			    var field = '';
			    field = $("#address-modal-body").attr("data-origin");
			    
			    $("#address-modal-body").find('input').each(function(){
			    	if($(this).val()){
			    		address.push($(this).val());
			    		text = text+($(this).val())+"<br>";
			    		$(this).val('');
			    	}
			    	else{
			    		address.push('');
			    	}
			    });
			    $.post("/json/addressSubmit.php", {'test[]' : address},function(data){
			    	if (field == "ship_to"){
			    		$("#select2-ship_to-container").html(text);
			    		$("#ship_to").append("<option selected value='"+data+"'>"+text+"</option>");
			    		updateShipTo();
			    		//$("#ship_display").html();	
			    	}
			    	else{
			    		$("#select2-bill_to-container").html(text);
			    		$("#bill_to").append("<option selected value='"+data+"'>"+text+"</option>");
						//$("#bill_display").replaceWith(("<div //id='bill_display'>"+$(this).text())+"</div>");	
						$("#mismo").prop("checked",false);
			    	}
			    });
			});
			$(document).on("click","#mismo",function() {
				updateShipTo();
			});
			
//Account Modal Popup Instigation
			$(document).on("change","#account_select",function() {
				if($(this).val().indexOf("Add") > -1){
					
					//Gather the address from the select2 field
					var acct = ($(this).val().slice(4));
					
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
			});

//Global Warranty function
			$(document).on("change","#warranty_global",function() {
				var value = $(this).val();
				var text = $("#warranty_global option:selected").text();
				
				console.log(window.location.origin+"/json/dropPop.php?ajax=true&limit="+value+"&field=services&label=Service:&id=service&size=col-sm-6");
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
						}
					});
				}
			});

//Conditional Global change
			$(document).on("change","#condition_global",function() {
				var value = $(this).val();
				var text = $("#condition_global option:selected").text();
				
				console.log(window.location.origin+"/json/dropPop.php?ajax=true&limit="+value+"&field=services&label=Service:&id=service&size=col-sm-6");
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
			
			$('#save_button').click(function() {
				
				//var isValid = nonFormCase($(this), e);
				
					//Get page macro information
					var order_type = $(this).closest("body").attr("data-order-type"); //Where there is 
					var order_number = $(this).closest("body").attr("data-order-number");
	
					//Get General order information
					var userid = $("#sales-rep option:selected").attr("data-rep-id");
					var company = $("#companyid").val();
					if (!company){
						alert("Must enter company before continuing");
						return;
					}
					
					var contact = $("#contactid").val();
					if (contact == "new"){
						contact = $("#select2-contactid-container").text();
						contact = contact.slice(9);
					}
					var assoc = $("#assoc_order").val();
					var terms = $("#terms").val();
					var ship_to = $('#ship_to').last('option').val();
					var bill_to = $('#bill_to').last('option').val();
					var carrier = $('#carrier').val();
					var freight = $('#terms').val();
					var service = $('#services').val();
					var account = $('#account_select').val();
					var pri_notes = $('#private_notes').val();
					var pub_notes = $('#public_notes').val();
					//var warranty = $('.warranty').val();
	
	
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
							
							// "line_number"+line_number+"part"+part+"id"+id+"date"+date+"condition"+condition+"warranty"+warranty+"price"+price+"qty"+qty

						submit.push(row);
					});
	
					//Submit all rows and meta data for unpacking later
					$.ajax({
						type: "POST",
						url: '/json/order-form-submit.php',
						data: {
							"sales-rep":userid,
							"companyid":company,
							"order_type":order_type,
			   		    	"order_number":order_number,
							"contact": contact,
							"assoc": assoc,
							"ship_to": ship_to,
							"bill_to": bill_to,
							"carrier": carrier,
							"account": account,
							"terms" : terms,
							"service" : service,
							"pri_notes": pri_notes,
							"pub_notes": pub_notes,
							"table_rows":submit,
							}, // serializes the form's elements.
						dataType: 'json',
						success: function(form) {
							var on = form["order"];
							var ps = form["type"];
							// alert("SAVED"+on+" | Order"+ps);
							// alert(form['insert']);
							// alert(form["error"]);
							// alert(form["update"]);

							window.location = "/order_form.php?ps="+ps+"&on="+on;
						},
						error: function(xhr, status, error) {
						   	alert(error);
						},
					});
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
//=========== END OF FUNCTION FOR THE SHIPPING HOME PAGE =======================


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
		
		$('body').on('change keyup paste', 'input[name="NewSerial"]', function(e) {
		     if( $( this ).val() != '' )
		         window.onbeforeunload = function() { return "You have unsaved changes."; }
		});
		
		//This function also handles the functionality for the shipping page
		$('body').on('keypress', 'input[name="NewSerial"]', function(e) {
			var $serial = $(this);
			//po_number gets any parameter set to on, E.G. Sales Number is also pulled
			var po_number = getUrlParameter('on');
			var savedSerial = $serial.attr('data-saved');
			var qty = parseInt($serial.closest('.infiniteSerials').siblings('.remaining_qty').children('input').val());
			var page = getPageName();
			var serial = $serial.val();
			var partid = $serial.closest('tr').find('.part_id').data('partid');
			var condition = $serial.closest('tr').find('.condition_field').val();
			var part = $serial.closest('tr').find('.part_id').data('part');
			
			if(condition == '') {
				condition = $serial.closest('tr').find('.condition_field').data('condition');
			}
			
			//Clone the serial field for usage when appending more fields
			var $serialClone = $serial.parent().clone();
			
			//alert(getPageName());
			if(e.which == 13) {
		    	if(((qty > 0 && serial != '') || savedSerial != '') && page != 'shipping') {
		    		var $conditionClone = $serial.closest('tr').find('.infiniteCondition').children('select:first').clone();
		    		var $locationClone = $serial.closest('tr').find('.infiniteLocations').children('.row-fluid:first').clone();
		    		var place = $serial.closest('tr').find('.infiniteLocations').children('.row-fluid:first').find('select:first').val();
		    		var instance = $serial.closest('tr').find('.infiniteLocations').children('.row-fluid:first').find('select:last').val();
		    		
		    		$.ajax({
						type: "POST",
						url: '/json/inventory-add-dynamic.php',
						data: {
							 'partid' : partid, 'condition' : condition, 'serial' : serial, 'po_number' : po_number, 'savedSerial' : savedSerial, 'place' : place, 'instance' : instance
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
								}
								
								//Set matching condition field to the serial saved
								$serial.closest('tr').find('.infiniteCondition').children('select:first').attr("data-serial", serial);
								$serial.closest('tr').find('.infiniteLocations').children('.row-fluid:first').find('select').attr("data-serial", serial);
								
								//Set Default Values here, remember clone doesn't save select values otherwise it will
								$serialClone.find('input').val("");
								$locationClone.find('select:first').val(place);
								$locationClone.find('select:last').val(instance);
								
								$serial.closest('.infiniteSerials').find('button').attr('disabled', false);
								$serialClone.find('button').attr('disabled', true);
								
								$serial.closest('.infiniteSerials').prepend($serialClone);
								$serial.closest('tr').find('.infiniteCondition').prepend($conditionClone);
								$serial.closest('tr').find('.infiniteLocations').prepend($locationClone);
								$serial.closest('.infiniteSerials').find('input:first').focus();
								
								if(qty == 0) {
							    	$serial.closest('.infiniteSerials').find('input:first').attr('readonly', true);
							    	alert('Part: ' + part + ' has been received.');
							    }
							    
							    $serial.attr("data-saved", serial);

							} else if(result['saved']) {
								$serial.attr("data-saved", serial);
								alert('Item has been updated.');
							} else {
								alert('Serial already exists for this item.');
							}
							window.onbeforeunload = null;
							
						}
					});
			    } else if(serial != '' && page == 'shipping') {
			  
			    	$.ajax({
						type: "POST",
						url: '/json/shipping-update-dynamic.php',
						data: {
							 'partid' : partid, 'serial' : serial, 'so_number' : po_number, 'condition' : condition
						},
						dataType: 'json',
						success: function(result) {
							
							//Once an item has a serial and is generated disable the ability to lot the item for the rest of the editing for users current view
							//$serial.closest('tr').find('.lot_inventory').attr('disabled', true);
								
							if(result['query']) {
								$serial.closest('tr').find('.lot_inventory').attr('disabled', true);
								//Decrement the qty by 1 after success and no errors detected
								qty--;
								
								if(qty >= 0) {
									$serial.closest('.infiniteSerials').siblings('.remaining_qty').children('input').val(qty);
								}
								$serialClone.find('input').val("");
								
								$serial.closest('.infiniteSerials').find('button').attr('disabled', false);
								$serialClone.find('button').attr('disabled', true);
								
								$serial.closest('.infiniteSerials').prepend($serialClone);
								$serial.closest('tr').find('.infiniteCondition').prepend($conditionClone);
								$serial.closest('.infiniteSerials').find('input:first').focus();
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
							
						}
					});
			    } else if(serial == '') {
			    	alert('Serial Missing');
			    } else if(qty <= 0) {
			    	alert('Serials Exceed Amount of Items Purchased in the Purchase Order. Please update Purchase Order in Order to Continue');
			    	$serial.closest('.infiniteSerials').children('input:first').attr('readonly', true);
			    }
			}
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
			
			//$(this).css('background-color','#eeeeee');
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
				
				$(this).find('.infiniteLocations').children('.row-fluid').each(function() {
					place.push($(this).find('select:first').val());
				});
				
				$(this).find('.infiniteLocations').children('.row-fluid').each(function() {
					instance.push(($(this).find('select:last').val() != '' ? $(this).find('select:last').val() : ''));
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
					lot = false
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
						alert('No changes have been made.');
						$click.attr('id','save_button_inventory');
					}
				}
			});
		});
		
		
		//Handle if the user deletes a serial from inventory add or shipping
		$(document).on('click', '.deleteSerialRow', function() {
			var page = getPageName();
			var po_number = getUrlParameter('on');
			$row = $(this).closest('.input-group');
			var qty = parseInt($row.closest('.infiniteSerials').siblings('.remaining_qty').children('input').val());
			//Grab the serial being deleted for futher usage to delete the item from the system
			var serial = $row.find('input').attr('data-saved');
			
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
							$row.closest('tr').find('.infiniteLocations').find('select[data-serial="'+ serial +'"]').remove();
						} else {
							$row.closest('tr').find('.infiniteSerials').siblings('.remaining_qty').find('input').val(qty);
							$row.closest('tr').find('.infiniteSerials').siblings('.ship-date').text('');
						}
						$row.closest('.infiniteSerials').children('.input-group:first').find('input').attr('readonly', false);
						
						//Settings to 2 because the row has not been deleted yet and will be after this execution
						//If 1 row then there are no serials, so re-enable lot
						if($row.closest('.infiniteSerials').children('.input-group').length <= 2) {
							$row.closest('tr').find('.lot_inventory').attr('disabled', false);
						}
					
						$row.remove();
					}
				});
			}
		});
		
		//Shipping update button, mainly used for lot and serial redirection
		$('#btn_update').click(function(){
			//Save to reactivate button if needed
			$click = $(this);
			//Prevent Button Spamming
			$click.removeAttr('id');
			
			var so_number = getUrlParameter('on');
			var items = [];
			
			var checkChanges = false;
			
			//Get everything from the form and place it into its own array
			$('.shipping_update').children('tbody').children('tr').each(function() {
				//Overlook all the rows that are complete in the order and grab all the others
				if(!$(this).hasClass('order-complete')) {
					var partid = $(this).find('.part_id').data('partid');
					var serials = [];
					var savedSerials = [];
					var condition;
					var lot = false;
					var qty;

					//Grab the conidtion value set by the sales order
					condition = $(this).find('.condition_field').data('condition');
					
					$(this).find('.infiniteSerials').find('input').each(function() {
						serials.push($(this).val());
						savedSerials.push($(this).attr('data-saved'));
						
						//If an item was saved previously then mark the page as something was edited
						if($(this).attr('data-saved') != '') {
							checkChanges = true;
						}
						
						//For purpose of conflicts only add a saved serial when there is nothing in the item, else ajax save generates a serial to match data
						//if($(this).attr('data-saved') == '')
						//$(this).attr("data-saved", $(this).val());
					});
					
					//Check if the lot is checked or not
					if($(this).find('.lot_inventory').prop('checked') == true) {
						lot = true;
					} else {
						lot = false
					}
					qty = $(this).find('.remaining_qty').children('input').val();
					
					items.push(partid);
					items.push(savedSerials);
					items.push(serials);
					items.push(condition);
					items.push(lot);
					items.push(qty);
				}
			});
			
			console.log(items);
			$.ajax({
				type: 'POST',
				url: '/json/shipping-update.php',
				data: {'so_number' : so_number, 'items' : items},
				dataType: 'json',
				success: function(data) {
					console.log(data);
					
					if(data['query'] || checkChanges) {
						//In case a warning is triggered but data is still saved successfully
						if(data['error'] != undefined)
							alert(data['error']);
						window.onbeforeunload = null;
						window.location = "/shipping_home.php?so=true";
					//Error occured enough to stop the page from continuing
					} else if(data['error'] != undefined) {
						alert(data['error']);
						$click.attr('id','btn_update');
					//Nothing was change
					} else {
						alert('No changes have been made.');
						$click.attr('id','btn_update');
					}
				}
			});
		});
		
		
		function location_changer(type,limit,home,warehouse){
			var finder = "."+type
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
					},
			});
		}
		
		$(document).on("change", ".warehouse",function() {
			var home = this;
			var limit = $(this).val();
			location_changer('aisle','',home,limit);
			location_changer('shelf','',home,limit);
			
		});
		$(document).on("change", ".place",function() {
			var home = this;
			var limit = $(this).val();
			location_changer('instance',limit,home,'');
		});
	});
//=========================== End Inventory Addition ===========================

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