(function($){

	// Declare functions up here

	// This function toggles the red label when a company is selected
	function companySelection(mode, companyid) {
		var init = true;
		$('.market-row').each(function(){
			$(this).find(".market-company-"+ companyid).css({'font-weight': 'bold', 'font-size': '12px'});

			//Count occurances in each of the columns
			var aval = $(this).find('.bg-availability').find(".market-company-"+ companyid).length;
			var demand = $(this).find('.bg-demand').find(".market-company-"+ companyid).length;

			if(aval > 0 && demand > 0) {
				$(this).find(".market-company-"+ companyid).find('.market-company').css({'color': 'red'});
			}

			if(mode == 'Buy') {
				if(companyid) {
					$(".market-data").find(".market-company").css({"color": "#428bca"});

					$('.market-row').each(function(){
						$(this).find('.bg-demand').find(".market-company-"+ companyid).find('.market-company').css({'color': 'red'});
						$(this).find('.bg-sales').find(".market-company-"+ companyid).find('.market-company').css({'color': 'red'});
						if(($(this).find('.bg-demand').find(".market-company-"+ companyid).length || $(this).find('.bg-sales').find(".market-company-"+ companyid).find('.market-company').length) && init) {
							$('#save_sales').attr("data-error", "true");
							init = false;
						} else if(init) {
							$('#save_sales').attr("data-error", "");
						}
					});
				}
			} else {
				if(companyid) {
					$(".market-data").find(".market-company").css({"color": "#428bca"});
					
					$('.market-row').each(function(){
						$(this).find('.bg-availability').find(".market-company-"+ companyid).find('.market-company').css({'color': 'red'});
						$(this).find('.bg-purchases').find(".market-company-"+ companyid).find('.market-company').css({'color': 'red'});

						if(($(this).find('.bg-availability').find(".market-company-"+ companyid).length || $(this).find('.bg-purchases').find(".market-company-"+ companyid).find('.market-company').length) && init) {
							$('#save_sales').attr("data-error", "true");
							init = false;
						} else if(init) {
							$('#save_sales').attr("data-error", "");
						}
					});
				}
			}
		});
	}

	$(document).on("click", "#save_sales", function(e){
		e.preventDefault();

		if($(this).attr("data-error") != "true") {
			$('.results-form').submit();
		} else {
			modalAlertShow("Company Alert","Please note the conflicts of interest on this page. Do you really want to proceed?",true,'submitConflict','');
		}
	});

	$(document).on("keydown",".product-search",function(e){
		if (e.keyCode == 13) {
			e.preventDefault();

			var search_str = $(this).val();
			var container = $(this).closest('.part_info');
			var ln = $(this).data('ln');

			//Get the values currently set
			var filter ='equipment';
			var count = 0;
			var temp = '';
			$('.filter-group').find('.filter_equipment').each(function(){
				if($(this).hasClass('active')) {
					temp = $(this).data('type');
					count++;
				}
			});

			if(count == 2) {
				filter = 'both';
			} else if(count == 1) {
				filter = temp;
			}

			container.find('.part_loader').show();

			$.ajax({
		        url: 'json/sales.php',
		        type: 'get',
		        data: {'search_strs': search_str, 'equipment_filter': filter, 'ln': ln},
		        success: function(result) {
					//Clear out past HTML from this table
					container.empty();
					container.append(result);

					var marketTable = container.find('.market-table');
					var parentBody = marketTable.closest(".product-results").find(".parts-container").height();

					if(parentBody > 140) {
						marketTable.children("*").css("height" ,'100%');
						// marketTable.find('.market-results').css("height" ,'98%');
						// marketTable.find('.market-body').css("height" ,'98%');
					}

					marketTable.css("height" ,parentBody);

					container.find(".market-results").each(function() {
						$(this).loadResults(0);
					});

					setSlider(container.find(".slider-button"));

					container.find('.slider-button').click(function() {
						setSlider($(this));
					});

					container.find('.part_loader').hide();
		        },
		        error: function(xhr, desc, err) {
		            console.log("Details: " + desc + "\nError:" + err);
		        }
		    }); // end ajax call
		}
	});

	$(document).on("click", ".market-title", function(e){
		var type = $(this).data("type");
		var container = $(this).closest('.market-table');
		var partid_csv = $(this).data("partid_csv");
		var ln = $(this).closest(".market-row").data("ln");

		if(type == "repairs" || type == 'sales_summary'){

			container.closest('.part_info').find('.part_loader').show();

			e.preventDefault();

        	console.log(window.location.origin+"/json/sales.php?type="+escape(type)+"&partid_csv="+escape(partid_csv)+"&ln="+ln);
			$.ajax({
				url: 'json/sales.php',
		        type: 'get',
		        data: {'type': type, 'partid_csv': partid_csv, 'ln' : ln},
		        success: function(result) {
					//Clear out past HTML from only market-table
					container.empty();
					container.append(result);

					var marketTable = container.height();

					if(marketTable > 140) {
						container.children("*").css("height" ,'100%');
						//container.find('.market-results').css("height" ,'98%');
						//container.find('.market-body').css("height" ,'98%');
					}

					if(type == 'sales_summary'){
						container.find(".market-results").each(function() {
							$(this).loadResults(0);
						});
					}

					setSlider(container.find(".slider-button"));

					container.find('.slider-button').click(function() {
						setSlider($(this));
					});

					container.closest('.part_info').find('.part_loader').hide();
		        },
		        error: function(xhr, desc, err) {
		            console.log("Details: " + desc + "\nError:" + err);
		        }
			});
		}
	});

	$(document).on("click", ".date_filter", function(event){
		event.preventDefault();

		//Create ajax call to filter by the date
		var date_container = $(this).closest('.date_container');
		var start = date_container.find("input[name='START_DATE']").val();
		var end = date_container.find("input[name='END_DATE']").val();

		$('.market-table').each(function(){
			var container = $(this);

			var partid_csv = container.data("partids");
			var ln = container.closest('.market-row').data("ln");
			var type = 'date_filter';

        	console.log(window.location.origin+"/json/sales.php?type="+escape(type)+"&partid_csv="+escape(partid_csv)+"&ln="+ln+"&start="+start+"&end="+end);
			$.ajax({
				url: 'json/sales.php',
		        type: 'get',
		        data: {'type': type, 'partid_csv': partid_csv, 'ln' : ln, 'start' : start, 'end' : end},
		        success: function(result) {
					//Clear out past HTML from only market-table
					container.empty();
					container.append(result);

					var marketTable = container.height();

					if(marketTable > 140) {
						container.children("*").css("height" ,'100%');
					}

					//if(type == 'sales_summary'){
						container.find(".market-results").each(function() {
							$(this).loadResults(0);
						});
					//}

					setSlider(container.find(".slider-button"));

					container.find('.slider-button').click(function() {
						setSlider($(this));
					});
		        },
		        error: function(xhr, desc, err) {
		            console.log("Details: " + desc + "\nError:" + err);
		        }
			});
		});
		
	});

	// Rebuild the parts view based on what the user selects (components only, equipment only or all)
	$(document).on("click", ".filter_equipment", function(){
		var filter = 'equipment';
		// var equipment = false;
		$(this).toggleClass('active');
		$(this).toggleClass('btn-primary');
		$(this).toggleClass('btn-default');

		var container = $(this).closest('.filter-group');

		$('#loader-message').html('Please wait for Sales results to load...');
		$('#loader').show();

		//Get the values currently set
		var count = 0;
		var temp = '';
		container.find('.filter_equipment').each(function(){
			if($(this).hasClass('active')) {
				temp = $(this).data('type');
				count++;
			}
		});

		if(count == 2) {
			filter = 'both';
		} else if(count == 1) {
			filter = temp;
		}

		if(count != 0){
		
			$('.part_info').each(function(){
				//Change container to this current line item
				var container = $(this);
				var search_str = container.find('.product-search').val();
				var ln = container.find('.product-search').data('ln');

				$.ajax({
			        url: 'json/sales.php',
			        type: 'get',
			        data: {'search_strs': search_str, 'equipment_filter': filter, 'ln': ln},
			        success: function(result) {
						//Clear out past HTML from this table
						//console.log(result);
						container.empty();
						container.append(result);

						var marketTable = container.find('.market-table');
						var parentBody = marketTable.closest(".product-results").find(".parts-container").height();

						if(parentBody > 140) {
							marketTable.children("*").css("height" ,'100%');
							// marketTable.find('.market-results').css("height" ,'98%');
							// marketTable.find('.market-body').css("height" ,'98%');
						}

						marketTable.css("height" ,parentBody);

						container.find(".market-results").each(function() {
							$(this).loadResults(0);
						});

						setSlider(container.find(".slider-button"));

						container.find('.slider-button').click(function() {
							setSlider($(this));
						});

						$('#loader').hide();
			        },
			        error: function(xhr, desc, err) {
			            console.log("Details: " + desc + "\nError:" + err);
						$('#loader').hide();
			        }
			    }); // end ajax call
			});
		} else {
			//Nothing will be checked in this else statement to default the current selected one
			$(this).addClass('active');
			$(this).addClass('btn-primary');
			$(this).removeClass('btn-default');
		}
	});

	$(document).on("keypress", ".results-form .search-qty, .results-form .price-control",function(e) {
		if (e.keyCode == 13) {
			var cid = $("#companyid").val();
			if (! cid) {
				// set submitting form on the alert button so we can capture it on user click, and continue to submit that form if they desire
				$('#alert-continue').data('form',$(this).closest("form"));
				modalAlertShow("Company Alert","Your data will not be saved without a company selected! Do you really want to proceed?",true);

				return false;
			} else {
				$('.results-form').submit();
				//$("#submit_type").val($(this).data('type'));
				return true;
			}
		}
	});

	$(document).on("change", ".price-control", function() {
		var priceMaster = $(this);
		// confirm padlock isn't unlocked, which would make this a unique price change
		var priceLocked = false;
		priceMaster.closest(".sell").find(".fa").each(function() {
			if ($(this).hasClass("fa-lock")) { priceLocked = true; }
		});
		if (priceLocked===false) { return; }

		var parentBody = priceMaster.closest(".part_info");
		var controlPrice,controlLock;
		var allPrices = parentBody.find(".price-control").not(this).each(function() {
			controlPrice = $(this);
			controlLock = controlPrice.closest(".sell").find(".fa-lock").each(function() {
				controlPrice.val(priceMaster.val().trim());
			});
		});
	});

	function infiniteScroll(elem) {

	    var docViewTop = $(window).scrollTop();
	    var docViewBottom = docViewTop + $(window).height();

	    var elemTop = $(elem).offset().top;
	    var elemBottom = elemTop + $(elem).height();

	    var visible = ((docViewTop < elemTop) && (docViewBottom > elemBottom));
	    var ln = elem.attr("data-page") - 1;
	    var container = elem.closest('.part_info');
	    var listid =  elem.attr("data-list");

	    //Get the values currently set
		var filter = 'equipment';
		var count = 0;
		var temp = '';
		$('.filter-group').find('.filter_equipment').each(function(){
			if($(this).hasClass('active')) {
				temp = $(this).data('type');
				count++;
			}
		});

		if(count == 2) {
			filter = 'both';
		} else if(count == 1) {
			filter = temp;
		}

	    var sort = $('.filter_status.active').data('sort');

	    var type = 'list';

	    if(visible) {
	    	elem.removeClass('infinite_scroll');
			$('#loader-message').html('Please wait for Sales results to load...');
	    	$('#loader').show();

			var f = $("form.results-form");
	    	var record_start = f.find('input[name="startDate"]').val();
	    	var record_end = f.find('input[name="endDate"]').val();
	    	var sales_count = f.find('input[name="sales_count"]').val();
	    	var sales_min = f.find('input[name="sales_min"]').val();
	    	var sales_max = f.find('input[name="sales_max"]').val();
	    	var demand_min = f.find('input[name="demand_min"]').val();
	    	var demand_max = f.find('input[name="demand_max"]').val();
	    	var stock_min = f.find('input[name="stock_min"]').val();
	    	var stock_max = f.find('input[name="stock_max"]').val();
	    	var dq_count = f.find('input[name="dq_count"]').val();
	    	var favorites = f.find('input[name="favorites"]').val();

			$.ajax({
		        url: 'json/sales.php',
		        type: 'get',
		        data: {
		        	'listid': listid, 
		        	'ln': ln, 
		        	'equipment_filter': filter, 
		        	'type': type, 
		        	'sort': sort, 
		        	'record_start': record_start, 
		        	'record_end': record_end, 
		        	'sales_count': sales_count,
		        	'sales_min': sales_min, 
		        	'sales_max': sales_max, 
		        	'demand_min': demand_min, 
		        	'demand_max': demand_max, 
		        	'stock_min': stock_min, 
		        	'stock_max': stock_max,
		        	'favorites': favorites,
		        	'dq_count': dq_count,
		        },
		        success: function(result) {
		        	if(result) {			        	
			        	console.log(result);
			        	var numItems = $('.part_info').length;
			        	
			        	elem.remove();
			        	$('.part_info:last').after(result);


			        	$(".part_info:gt("+(numItems-1)+")").each(function(){
				        	var container = $(this);

				        	var marketTable = container.find('.market-table');
							var parentBody = marketTable.closest(".product-results").find(".parts-container").height();

							//alert(parentBody);

							if(parentBody > 140) {
								marketTable.children("*").css("height" ,'100%');
							}

							marketTable.css("height" ,parentBody);

							container.find(".market-results").each(function() {
								$(this).loadResults(0);
							});

							setSlider(container.find(".slider-button"));

							container.find('.slider-button').click(function() {
								setSlider($(this));
							});
						});
					}

					$('#loader').hide();
		        },
		        error: function(xhr, desc, err) {
		            console.log("Details: " + desc + "\nError:" + err);
					$('#loader').hide();
		        }
		    }); // end ajax call
	    }

	    return visible;
	}

	//Bring the infinite screoll back to list number later
	$(window).scroll(function(e) {
		if ($('.infinite_scroll').length) {
			var triggerLoad = infiniteScroll($('.infinite_scroll'));
		}
	});

	$(document).on("click", ".filter_status", function(e) {
		e.preventDefault();

		var sort = $(this).data('sort');
		var listid = $(this).data('listid');

		//Equipment or component filter
		var filter = $('.filter-group').find('.active').data('type');
		var type = 'list';

		$('.filter_status').removeClass('active');
		$('.filter_status').addClass('btn-default');

		$('.filter_status').removeClass('btn-warning');
		$('.filter_status').removeClass('btn-success');

		if(sort == 'line'){
			$(this).addClass('btn-warning');
		} else {
			$(this).addClass('btn-success');
		}

		$('#loader-message').html('Please wait for Sales results to load...');
		$('#loader').show();

		$.ajax({
	        url: 'json/sales.php',
	        type: 'get',
	        data: {'listid': listid, 'ln': '0', 'equipment_filter': filter, 'type': type, 'sort' : sort},
	        success: function(result) {
	        	if(result) {			        	
		        	console.log(result);

		        	$('.part_info').remove();
		        	$('.infinite_scroll').remove();

		        	$('#pad-wrapper').append(result);

		        	$(".part_info").each(function(){
			        	var container = $(this);

			        	var marketTable = container.find('.market-table');
						var parentBody = marketTable.closest(".product-results").find(".parts-container").height();

						//alert(parentBody);

						if(parentBody > 140) {
							marketTable.children("*").css("height" ,'100%');
						}

						marketTable.css("height" ,parentBody);

						container.find(".market-results").each(function() {
							$(this).loadResults(0);
						});

						setSlider(container.find(".slider-button"));

						container.find('.slider-button').click(function() {
							setSlider($(this));
						});

						$('#loader').hide();
					});
				}
	        },
	        error: function(xhr, desc, err) {
	            console.log("Details: " + desc + "\nError:" + err);

				$('#loader').hide();
	        }
	    }); // end ajax call
	});

	$(document).on("click", ".company-filter", function(e){
		e.preventDefault();

		var companyid = $('#companyid').val();
		var mode = $('.sales_mode:checked').val();

		$(".market-data").css({'font-weight': 'normal', 'font-size': '10px'});
		$(".market-data").find(".market-company").css({"color": "#428bca"});

		companySelection(mode, companyid);
	});

	$(document).on("change", "#companyid", function(e){
		var companyid = $(this).val();
		var mode = $('.sales_mode:checked').val();

		$(".market-data").css({'font-weight': 'normal', 'font-size': '10px'});
		$(".market-data").find(".market-company").css({"color": "#428bca"});

		companySelection(mode, companyid);
	});

	$(document).on('click',".line-number-toggle",function() {
		var chkbox = $(this);

		var rowbody = $(this).closest(".part_info").find(".product-results");

		if (chkbox.hasClass('toggle-up')) {
			rowbody.slideUp('fast');
			chkbox.removeClass('toggle-up');
			chkbox.find('.fa-sort-asc').addClass('fa-sort-desc').removeClass('fa-sort-asc');
		} else {
			rowbody.slideDown('fast');
			chkbox.addClass('toggle-up');
			chkbox.find('.fa-sort-desc').addClass('fa-sort-asc').removeClass('fa-sort-desc');
		}
	});

	$(document).on('change',".line-number",function() {
		var chkbox = $(this);
		var mode = $('.sales_mode:checked').val();
		var ln_toggle = chkbox.closest('.part_info').find('.line-number-toggle');

		var rowbody = $(this).closest(".part_info").find(".product-results");

		if (chkbox.is(':checked')) {
			//Purely for the toggle slide feature
			rowbody.hide();
			ln_toggle.removeClass('toggle-up');
			ln_toggle.find('.fa-sort-asc').addClass('fa-sort-desc').removeClass('fa-sort-asc');

			$(this).closest(".part_info").find("input").prop('disabled',true);
			$(this).closest(".part_info").find("button").prop('disabled',true);
		} else {
			rowbody.show();
			ln_toggle.addClass('toggle-up');
			ln_toggle.find('.fa-sort-desc').addClass('fa-sort-asc').removeClass('fa-sort-desc');

			$(this).closest(".part_info").find("input").prop('disabled',false);
			$(this).closest(".part_info").find("button").prop('disabled',false);

			if(mode == 'Buy') {
				$('.price-control').prop("disabled", false);

				$('.sell-price').prop("disabled", true);
				$('.sell-price').closest('.form-group').hide();

				$('.product-results .qty input').prop("disabled", true);
				//$('.first .qty input').prop("disabled", false);

				$('.bid_inputs').show();
				$('.seller_x').show();
			} else {
				$('.price-control').prop("disabled", true);

				$('.sell-price').prop("disabled", false);
				$('.sell-price').closest('.form-group').show();

				$('.product-results .qty input').prop("disabled", false);
				//$('.first .qty input').prop("disabled", true);

				$('.bid_inputs').hide();
				$('.seller_x').hide();
			}
		}

	});


	$(document).on("change", ".sales_mode", function(e){
		var total_list = 0;
		var initial = true;

		if($(this).is(':checked')) {
			var mode = $(this).val();
			//Check if a company is selected
			var companyid = $('#companyid').val();

			$('.part_info').each(function(){
				var container = $(this);
				var row_status = container.find('.line-number:checked').val();

				//2 modes being Buy or Sell
				if(mode == 'Buy') {

					container.find('.price-control').prop("disabled", false);
					container.find('.sell-price').prop("disabled", true);
					container.find('.sell-price').closest('.form-group').hide();
					container.find('.product-results .qty input').prop("disabled", true);
					container.find('.first .qty input').prop("disabled", false);
					container.find('.bid_inputs, .seller_qty, .seller_x, .seller_price').show();
					$('input[name="submit_type"]').val("availability");
	
					// if(companyid) {
					// 	container.find(".market-company").css({"color": "#428bca"});
					// 	container.find('.bg-demand').find(".market-company-"+ companyid).find('.market-company').css({'color': 'red'});
					// 	container.find('.bg-sales').find(".market-company-"+ companyid).find('.market-company').css({'color': 'red'});
					// }

					qty = container.closest('.part_info').find('.search-qty').val();
					price = container.closest('.part_info').find('.first .price-control').val();

					container.find('.seller_qty').html(qty);
					container.find('.seller_price').html(price);
					container.find('.total_price_text').html('$' + (qty * price).toFixed(2));

					if(initial) {
						$('.buy_text').each(function(){
							total_list += parseFloat($(this).attr('data-buy_total'));
							$('.list_total').html('$' + total_list.toFixed(2));
						});

						initial = false;
					}
				} else {
					container.find('.price-control').prop("disabled", true);
					container.find('.sell-price').prop("disabled", false);
					container.find('.sell-price').closest('.form-group').show();
					container.find('.product-results .qty input').prop("disabled", false);
					container.find('.bid_inputs, .seller_x, .seller_qty, .seller_price').hide();
					$('input[name="submit_type"]').val("demand");

					var total = 0;

					// if(companyid) {
					// 	container.find(".market-company").css({"color": "#428bca"});
					// 	container.find('.bg-availability').find(".market-company-"+ companyid).find('.market-company').css({'color': 'red'});
					// 	container.find('.bg-purchases').find(".market-company-"+ companyid).find('.market-company').css({'color': 'red'});
					// }

					container.find('.descr-row').each(function(){
						total += ($(this).find('.qty input').val()) * ($(this).find('.sell-price').val());
					});

					container.find('.total_price_text').html('$' + total.toFixed(2));

					if(initial) {
						$('.total_price_text').each(function(){

							total_list += parseFloat($(this).attr('data-total'));

							$('.list_total').html('$' + total_list.toFixed(2));
						});

						initial = false;
					}
				}

				companySelection(mode, companyid);

				if(row_status == 'Off') {
					container.find('input').prop("disabled", true);
					//alert('here');
				}
			});
		}
	});

	//Sell Mode price calculations
	$(document).on("change", ".sell-price, .parts-container .qty input", function(){
		var container = $(this).closest('.parts-container');
		var qty = 0;
		var total = 0;
		var total_list = 0;

		container.find('.descr-row').each(function(){
			total += ($(this).find('.qty input').val()) * ($(this).find('.sell-price').val());
		});

		container.closest('.part_info').find('.total_price_text').html('$' + total.toFixed(2));
		container.closest('.part_info').find('.total_price_text').attr("data-total", total);

		$('.total_price_text').each(function(){
			total_list += parseFloat($(this).attr('data-total'));
			$('.list_total').html('$' + total_list.toFixed(2));
		});
	});

	//Calculate user input bid qty and price for buy mode
	$(document).on("change", ".bid-input", function(e){
		var container = $(this).closest('.bid_inputs');
		var total = 1;
		var total_list = 0;

		container.find('.bid-input').each(function(){
			total *= $(this).val();
		});

		container.find('.buy_text').html('$' + total.toFixed(2));
		container.find('.buy_text').attr("data-buy_total", total);

		$('.buy_text').each(function(){
			total_list += parseFloat($(this).attr('data-buy_total'));
			$('.list_total').html('$' + total_list.toFixed(2));
		});
	});

	//Buy Mode price or qty changes calculations
	$(document).on("change", ".first .price-control, .search-qty", function(){
		var container = $(this).closest('.first');
		var qty = container.find('.search-qty').val();
		var price = container.find('.price-control').val();
		var total = 0;

		total += (qty * price);

		container.closest('.part_info').find('.seller_qty').html(qty);
		container.closest('.part_info').find('.seller_price').html(price);
		container.closest('.part_info').find('.total_price_text').html('$' + total.toFixed(2));

	});

	//Onload calculate the list total based on what is present as default is set to sell
	var total_list = 0;

	$('.total_price_text').each(function(){
		total_list += $(this).data('total');

		$('.list_total').html('$' + total_list.toFixed(2));
	});

	$(document).on("click", ".part_info .market-download", function() {
		var mr = $(this).closest(".bg-availability, .bg-demand").find(".market-results:first");

		mr.loadResults(2);
	});

	$(document).on("keydown",".bid-input",function(e){
		if (e.keyCode == 13) {
			e.preventDefault();
		}
	});


})(jQuery);		

