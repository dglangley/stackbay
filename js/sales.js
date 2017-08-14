(function($){
	$(document).on("keydown",".product-search",function(e){
		if (e.keyCode == 13) {
			var search_str = $(this).val();
			var container = $(this).closest('.part_info');

			$.ajax({
		        url: 'json/sales.php',
		        type: 'get',
		        data: {'search_strs': search_str},
		        success: function(result) {
					// if (json.err!='') {
					// 	alert(json.err);
					// 	return;
					// }

					//Clear out past HTML from this table
					container.empty();
					container.append(result);

					var marketTable = container.find('.market-table');
					var parentBody = marketTable.closest(".product-results").find(".parts-container").height();

					if(parentBody > 140) {
						marketTable.children("*").css("height" ,'100%');
						marketTable.find('.market-results').css("height" ,'98%');
						marketTable.find('.market-body').css("height" ,'98%');
					}

					marketTable.css("height" ,parentBody);

					container.find(".market-results").each(function() {
						$(this).loadResults(0);
					});

					setSlider(container.find(".slider-button"));

					container.find('.slider-button').click(function() {
						setSlider($(this));
					});
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
			e.preventDefault();

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
		        },
		        error: function(xhr, desc, err) {
		            console.log("Details: " + desc + "\nError:" + err);
		        }
			});
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
	    var page = elem.attr("data-page");
	    var container = elem.closest('.part_info');
	    var search_str = container.find('.product-search').val();

	    if(visible) {
	    	elem.remove();
	    	//Load more thru the generic sales ajax call
			$.ajax({
		        url: 'json/sales.php',
		        type: 'get',
		        data: {'search_strs': search_str, 'page': page},
		        success: function(result) {
		        	if(result) {			        	
			        	console.log(result);
			        	container.find('.parts-container').append(result);

			        	var marketTable = container.find('.market-table');
						var parentBody = marketTable.closest(".product-results").find(".parts-container").height();

						if(parentBody > 140) {
							marketTable.children("*").css("height" ,'100%');
							marketTable.find('.market-results').css("height" ,'98%');
							marketTable.find('.market-body').css("height" ,'98%');
						}

						marketTable.css("height" ,parentBody);
					}
		        },
		        error: function(xhr, desc, err) {
		            console.log("Details: " + desc + "\nError:" + err);
		        }
		    }); // end ajax call
	    }

	    return visible;
	}

	// $(window).scroll(function(e) {
	// 	if ($('.inifite_scroll').length) {
	// 		var triggerLoad = infiniteScroll($('.inifite_scroll'));
	// 	}
	// });

	$(document).on("change", "#companyid", function(e){
		var companyid = $(this).val();
		var mode = $('.sales_mode:checked').val();

		$(".market-data").css({'font-weight': 'normal', 'font-size': '10px'});
		$(".market-data").find(".market-company").css({"color": "#428bca"});

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
					});
				}
			} else {
				if(companyid) {
					$(".market-data").find(".market-company").css({"color": "#428bca"});
					
					$('.market-row').each(function(){
						$(this).find('.bg-availability').find(".market-company-"+ companyid).find('.market-company').css({'color': 'red'});
						$(this).find('.bg-purchases').find(".market-company-"+ companyid).find('.market-company').css({'color': 'red'});
					});
				}
			}
		});
	});


	$(document).on("change", ".sales_mode", function(e){
		if($(this).is(':checked')) {
			var mode = $(this).val();

			//Check if a company is selected
			var companyid = $('#companyid').val();
			
			//2 modes being Buy or Sell
			if(mode == 'Buy') {
				$('.price-control').prop("disabled", false);

				$('.sell-price').prop("disabled", true);
				$('.sell-price').closest('.form-group').hide();

				$('.product-results .qty input').prop("disabled", true);
				$('.first .qty input').prop("disabled", false);

				$('.bid_inputs, .seller_qty, .seller_x, .seller_price').show();

				$('input[name="submit_type"]').val("availability");
				$('.market-row').each(function(){
					if(companyid) {
						$(this).find(".market-company").css({"color": "#428bca"});
						$(this).find('.bg-demand').find(".market-company-"+ companyid).find('.market-company').css({'color': 'red'});
						$(this).find('.bg-sales').find(".market-company-"+ companyid).find('.market-company').css({'color': 'red'});
					}

					qty = $(this).closest('.part_info').find('.search-qty').val();
					price = $(this).closest('.part_info').find('.first .price-control').val();

					$(this).find('.seller_qty').html(qty);
					$(this).find('.seller_price').html(price);
					$(this).find('.total_price_text').html('$' + (qty * price).toFixed(2));
				});


			} else {
				$('.price-control').prop("disabled", true);

				$('.sell-price').prop("disabled", false);
				$('.sell-price').closest('.form-group').show();

				$('.product-results .qty input').prop("disabled", false);
				//$('.first .qty input').prop("disabled", true);

				$('.bid_inputs, .seller_x, .seller_qty, .seller_price').hide();

				$('input[name="submit_type"]').val("demand");
				$('.part_info').each(function(){
					var total = 0;

					if(companyid) {
						$(this).find(".market-company").css({"color": "#428bca"});
						$(this).find('.bg-availability').find(".market-company-"+ companyid).find('.market-company').css({'color': 'red'});
						$(this).find('.bg-purchases').find(".market-company-"+ companyid).find('.market-company').css({'color': 'red'});
					}

					$(this).find('.descr-row').each(function(){
						total += ($(this).find('.qty input').val()) * ($(this).find('.sell-price').val());
					});

					$(this).find('.total_price_text').html('$' + total.toFixed(2));
				});
			}

		}
	});

	//Sell Mode price calculations
	$(document).on("change", ".sell-price, .parts-container .qty input", function(){
		var container = $(this).closest('.parts-container');
		var qty = 0;
		var total = 0;

		container.find('.descr-row').each(function(){
			total += ($(this).find('.qty input').val()) * ($(this).find('.sell-price').val());
		});


		container.closest('.part_info').find('.total_price_text').html('$' + total.toFixed(2));

	});

	//Calculate user input bid qty and price for buy mode
	$(document).on("change", ".bid-input", function(e){
		var container = $(this).closest('.bid_inputs');
		var total = 1;

		container.find('.bid-input').each(function(){
			total *= $(this).val();
		});

		container.find('.buy_text').html('$' + total.toFixed(2));
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
						//container.find('.market-results').css("height" ,'98%');
						//container.find('.market-body').css("height" ,'98%');
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

})(jQuery);		

