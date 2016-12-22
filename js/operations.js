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
				alert('clicked');
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
							if($("#bill_to").val() == $("#ship_to").val()){
								$("#mismo").prop("checked",true);
							}
							$("#contactid").initSelect2("/json/contacts.php",'Select a Contact',company);
						}
						else{
							$("#order_selector").initSelect2("/json/order-select.php","Select an Order",page);
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
					}
				});
			});
			$(document).on("change","#services",function() {
				var limit = $(this).val();
            	// console.log(window.location.origin+"/json/order-table-out.php?ajax=true&limit="+limit+"&field=services&label=Service:&id=service&size=col-sm-6");
				$.ajax({
 
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
							var lineNumber = parseInt($(".multipart_sub").closest("tr").find("input[name=ni_line]").val());
							if (!lineNumber){lineNumber = 0;}
							$(".multipart_sub").closest("tr").find("input[name=ni_line]").val(lineNumber + 1);
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
		    //(".item_search").initSelect2("/json/part-search.php","Select a Part",$("body").attr("data-page"));
	
	
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
								
 							}
						});
	   		        }
	   		    });
	   		    
				var lineNumber = parseInt($(".multipart_sub").closest("tr").find("input[name=ni_line]").val());
				if (!lineNumber){lineNumber = 0;}
				$(".multipart_sub").closest("tr").find("input[name=ni_line]").val(lineNumber + 1);
				$("#go_find_me").val("");
    			$(".multipart_sub").closest("tr").find("input[name=ni_price]").val("");
       			
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
			function updateBillTo(){
				if ( $("#mismo").prop( "checked" )){
					var display = $("#select2-ship_to-container").html()
					var value = $("#ship_to").val();
		    		$("#select2-bill_to-container").html(display)
		    		$("#bill_to").append("<option selected value='"+value+"'>"+display+"</option>");
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
					if (origin == "ship_to"){
						//$("#ship_display").replaceWith(right);	
						updateBillTo();
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
			    		updateBillTo();
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
				updateBillTo();
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
			

//================================ PAGE SUBMIT ================================
			
			$('#save_button').click(function() {
				
				//var isValid = nonFormCase($(this), e);
				
					//Get page macro information
					var order_type = $(this).closest("body").attr("data-order-type"); //Where there is 
					var order_number = $(this).closest("body").attr("data-order-number");
	
					//Get General order information
					var userid = $("#sales-rep option:selected").attr("data-rep-id");
					var company = $("#companyid").val();
					var contact = $("#contactid").val();
					if (contact == "new"){
						contact = $("#select2-contactid-container").text();
						contact = contact.slice(9);
					}
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
							alert("SAVED"+on+" | Order"+ps);
							alert(form['insert']);
							// alert(form["error"]);
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
		$(document).on("click",".toggle_group",function() {
		    $(this).toggleClass('active');
		    $(this).siblings().removeClass('active');
		});
		
		$(document).ready(function() {
			//$('.inventory_lines').find('add-delete-group').show();
			$('.inventory_lines:last').find('.add-delete-group').find('.btn-add').show();
		});
		
		$(document).on("change","input[name='serialize']",function() {
		    if(this.checked) {
		         $(this).closest('.addRecord').find('#inv_add_record').html('<i class="fa fa-list-ul" aria-hidden="true"></i>');
		         $(this).closest('.addRecord').find('#inv_add_record').addClass('serialize_data');
		         $(this).closest('.addRecord').find('.add-delete-group').find('.btn-add').show();
		         $(this).closest('.addRecord').find('#inv_add_record').removeClass('add_data');
		         $(this).closest('.addRecord').find('#inv_add_record').addClass('btn-warning');
		    } else {
		    	 $(this).closest('.addRecord').find('#inv_add_record').html('<i class="fa fa-plus" aria-hidden="true"></i>');
		    	 $(this).closest('.addRecord').find('#inv_add_record').addClass('add_data');
		    	 
		    	 $(this).closest('.addRecord').find('.add-delete-group').find('.btn-add').hide();
		    	 $('.inventory_lines:last').find('.add-delete-group').find('.btn-add').show();
		    	 
		    	 $(this).closest('.addRecord').find('#inv_add_record').removeClass('btn-warning');
		    	 $(this).closest('.addRecord').find('#inv_add_record').removeClass('serialize_data');
		    }
		});
		
		var lastValue = '';
		
		$(document).on("keyup change mouseup","#new_qty",function() {
			if ($(this).val() != lastValue) {
        		lastValue = $(this).val();
				//$('table:last').find('input[name="serialize"]').prop('checked', false);
			    if($(this).closest('.addRecord').find("input[name='serialize']").is(":checked")) {
			        $(this).closest('.addRecord').find('#inv_add_record').html('<i class="fa fa-list-ul" aria-hidden="true"></i>');
			        $(this).closest('.addRecord').find('.add-delete-group').find('.btn-add').show();
			        $(this).closest('.addRecord').find('#inv_add_record').addClass('serialize_data');
			        $(this).closest('.addRecord').find('#inv_add_record').addClass('btn-warning');
			        $(this).closest('.addRecord').find('#inv_add_record').removeClass('add_data');
			    } else {
			    	$(this).closest('.addRecord').find('#inv_add_record').html('<i class="fa fa-plus" aria-hidden="true"></i>');
			    	$(this).closest('.addRecord').find('#inv_add_record').addClass('add_data');
			    	
			    	$(this).closest('.addRecord').find('.add-delete-group').find('.btn-add').hide();
			    	$('.inventory_lines:last').find('.add-delete-group').find('.btn-add').show();
			    	
			    	$(this).closest('.addRecord').find('#inv_add_record').removeClass('serialize_data');
			    	$(this).closest('.addRecord').find('#inv_add_record').removeClass('btn-warning');
			    }
			}
		});
		
		$(document).on("click",".show_link",function() {
			var data = $(this).is(':visible') ? 'Show Less' : 'Show More';
    		$(this).text(data);
		    $(this).closest('.table').find('#serial_each_table').fadeToggle();
		});
		
		$(document).on('click',"#inv_delete_record",function() {
		    if (confirm("Please confirm you want to delete inventory add for selected part" + ".")) {
		        $(this).closest('.inventory_lines').remove();
		        $('.inventory_lines:last').find('.add-delete-group').find('.btn-add').show();
		    }
		});
		
		$(document).on('click',"#inv_add_record",function() {
	
			var qty = $(this).closest('.table').find("#new_qty").val();
			var location = $(this).closest('.table').find("#new_location option:selected").text();
			var info;
			var item = $(this).closest('.table').find("#search_collumn").find(".item_search").val();
			
			var $element = $(this).closest('.table').find('#serial_each_table');
			
			if($(this).hasClass('serialize_data')) {
				$element.children('.added_serial').remove();
			} else if($(this).hasClass('add_data')) {
				$element.children('.added_serial').remove();
				$(this).closest('.table').find('#serial input').prop('disabled', false);
				$element.closest('.table').find('#new_location').prop('disabled', false);
				$element.closest('.table').find('.condition_field').prop('disabled', false);
			}
			
			var $conditions = $element.closest('.table').find('.condition_field').clone();
			var $status = $element.closest('.table').find('.status').clone();
			
			if($element.closest('.table').find("input[name='serialize']").prop("checked") && qty > 1 && $(this).hasClass('serialize_data')) {
				for(var i = 1; i <= qty; i++) {
					$(this).closest('.table').find('#serial input').prop('disabled', true);
					info = '<tr class="added_serial"><td><strong>' + i;
					info += '.</strong> Enter Serial for Part #' + item;
					info += '</td><td><input class="form-control input-sm" type="text" name="NewSerial" placeholder="Serial"></td><td></td>';
					info += '<td><select class="location_clone form-control"></select></td><td class="add_status"></td><td class="add_condition">';
					info += '</td></tr>';
					$element.append(info);
				}
		
				$element.closest('.table').find('.condition_field').prop('disabled', true);
				
				$element.closest('.table').find('#serial_each_table').show();
				$element.closest('.table').find('.show_link').fadeIn();
				
				$element.find('.add_condition').append($conditions);
				$element.find('.add_status').append($status);
				$element.closest('.table').find('.add_condition select').prop('disabled', false);
				$element.closest('.table').find('.add_status select').prop('disabled', false);
				$element.closest('.table').find('#new_location').find('option').clone().appendTo($element.find('.location_clone'));
				$element.find('.location_clone').val(location);
				$element.closest('.table').find('#new_location').prop('disabled', true);
				$(this).closest('.addRecord').find('#inv_add_record').html('<i class="fa fa-plus" aria-hidden="true"></i>');
				$(this).closest('.addRecord').find('#inv_add_record').removeClass('serialize_data');
				$(this).closest('.addRecord').find('#inv_add_record').removeClass('btn-warning');
				
				$(this).closest('.addRecord').find('.add-delete-group').find('.btn-add').hide();
		    	$('.inventory_lines:last').find('.add-delete-group').find('.btn-add').show();
		    	
		    	//On Return focus onto next item
				$('input[name="NewSerial"]').on('keypress', function(e) {
					var $serial = $(this);
				    if(e.which == 13) {
						$serial.closest('.added_serial').next('.added_serial').find('input[name="NewSerial"]').focus();
				    }
				});
			} else {
				var $newTr = $('#items_table:last').closest('.inventory_lines').clone();
				$.when($(this).closest('.inventory_lines').parent().find('.inventory_lines:last').after($newTr)).then(function() {
					$('table:last').find('#serial_each_table').children('.added_serial').remove();
				});
				$element.closest('.table').find('#serial_each_table').fadeOut('fast');
				
				//$(this).closest("tbody").find("tr").length === 0
				if($element.closest('.table').find('#serial_each_table').find("tr").length > 0) {
					$element.closest('.table').find('.show_link').fadeIn();
				}
				//Clear all past values
				$('table:last').find('.item_search').val('');
				$('table:last').find('#new_qty').val('');
				$('table:last').find('input[name="NewSerial"]').val('');
				$('table:last').find('input[name="serialize"]').prop('checked', false);
				$('table:last').find('#serial input').prop('disabled', false);
				$('table:last').find('#new_location').prop('disabled', false);
				$('table:last').find('.condition_field').prop('disabled', false);
				$('table:last').find('.select2').remove();
				$(".item_search").initSelect2("/json/part-search.php","Part Search",$("body").attr('data-page'));
				
				$('.inventory_lines').find('.add-delete-group').find('.btn-add').hide();
				$('.inventory_lines:last').find('.add-delete-group').find('.btn-add').show();
			}
		});
		
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
		};
		
		$(document).on('click',"#save_button_inventory",function() {
			
			//items = ['partid', 'serial or array of serials', 'qty', 'location or array', 'status or array', 'condition or array']
			var items = [];
			var po_number = getUrlParameter('on');

			$('table .addRecord').each( function() {
				var productid = $(this).closest('.table').find("#search_collumn").find(".item_search").val();
				//Push the part ID first
				items.push(productid);
				
				//If the item is serialized then push an array of all the serials
				if($(this).closest('.addRecord').find("input[name='serialize']").is(":checked")) {
					var serialList = [];
					var locationList = [];
					var statusList = [];
					var conditionList = [];
					$(this).closest('.table').find('#serial_each_table').find('tr').each( function() {
						serialList.push($(this).find('input[name="NewSerial"]').val());
						locationList.push($(this).find('.location_clone').val().replace("W: ",""));
						statusList.push($(this).find('.status').val());
						conditionList.push($(this).find('.condition_field ').val());
					});
					//alert('Serialized: ' + productid);
					items.push(serialList);
					items.push('1');
					items.push(locationList);
					items.push(statusList);
					items.push(conditionList);
				} else {
					items.push($(this).closest('.table').find('.addRecord').find('input[name="NewSerial"]').val());
					items.push($(this).closest('.table').find('.addRecord').find('#new_qty').val());
					items.push($(this).closest('.table').find('.addRecord').find('#new_location').val().replace("W: ",""));
					items.push($(this).closest('.table').find('.addRecord').find('.status').val());
					items.push($(this).closest('.table').find('.addRecord').find('.condition_field ').val());
					//else push a just the single serial
					//items.push($(this).closest('.table').find('.addRecord').find('input[name="NewSerial"]').val());
				}
			});
			//console.log(po_number);
			// alert(new_record);
			$.ajax({
				type: "POST",
				url: '/json/inventory-add.php',
				data: {
					 'productid' : items, 'po_number' : po_number
				},
				dataType: 'json',
				success: function(lines) {
					//console.log(lines);
					window.location = "/shipping_home.php?po=true";
				}
			});
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