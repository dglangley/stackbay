	$(document).ready(function() {
		if (typeof companyid === 'undefined' || typeof companyid === 'object') { companyid = 0; }
		if (typeof contactid === 'undefined' || typeof contactid === 'object') { contactid = 0; }
		if (typeof listid === 'undefined' || typeof listid === 'object') { listid = 0; }
		if (typeof list_label === 'undefined' || typeof list_label === 'object') { list_label = ''; }
		if (typeof lim === 'undefined' || typeof lim === 'object') { lim = 0; }
		if (typeof PR === 'undefined' || typeof PR === 'object') { PR = false; }
		if (typeof salesMin === 'undefined' || typeof salesMin === 'object') { salesMin = false; }
		if (typeof favorites === 'undefined' || typeof favorites === 'object') { favorites = false; }
		if (typeof startDate === 'undefined' || typeof startDate === 'object') { startDate = ''; }
		if (typeof endDate === 'undefined' || typeof endDate === 'object') { endDate = ''; }
		if (typeof demandMin === 'undefined' || typeof demandMin === 'object') { demandMin = false; }
		if (typeof demandMax === 'undefined' || typeof demandMin === 'object') { demandMax = false; }
		if (typeof line_number === 'undefined' || typeof line_number === 'object') { line_number = false; }
		if (typeof searchid === 'undefined' || typeof searchid === 'object') { searchid = false; }
		if (typeof import_quote === 'undefined' || typeof import_quote === 'object') { import_quote = false; }

		list_type = $("#list_type").val();
		pricing_default = 0;
		if (list_type=='Service') { pricing_default = 1; }

		var labels = [];
		var supply = [];
		var demand = [];
		mData = {
			labels: labels,
			datasets: [
				{
					label: 'demand',
					data: demand,
					color: {
						up: '#5cb85c',
						down: '#5cb85c',
						unchanged: '#000',
					},
					backgroundColor: '#5cb85c',
					borderColor: '#4cae4c',
					fill: true,
/*
					armLengthRatio: 0.5,
					// armLength hase priority over armLengthRatio
					// uncommenting the following line will override the length set by armLengthRatio
					// armLength: 8,
					lineWidth: 1,
*/
				},
				{
					label: 'supply',
					data: supply,
					color: {
						up: '#f0ad4e',
						down: '#f0ad4e',
						unchanged: '#000',
					},
					backgroundColor: '#f0ad4e',
					borderColor: '#f0ad4e',
					fill: true,
				},
			]
		};

		mOptions = {
			elements: { point: { radius: 2 } },
			showTooltips: true,
			tooltipCaretSize: 0,
			tooltips: {
				position: 'nearest',
				mode: 'index',
			},
			scales: {
				xAxes: [{
					display: true,
					type: "time",
					time: {
						unit: "month",
						displayFormats: { month: "MMM", }
					},
				}],
				yAxes: [{ display: true }]
			},
			legend: {
				display: false,
				position: 'top',
				labels: {
					boxWidth: 80,
					fontColor: '#555'
				}
			},
		};
		ctx = false;
		mChart = false;

		if ($("#mChart").length>0) {
			ctx = $("#mChart");
			mChart = new Chart(ctx, {
				type: 'candlestick',
				data: mData,
				options: mOptions,
			});
		}

		$('#loader-message').html('Gathering market information...');

		$("#results").partResults();

		// changing the selected menu option that alters the save button
		$(".save-menu li a").on('click', function() {
			var li = $(this);

			li.saveMenu();
			li.updateItemFields($("#list_type").val());
		});

		// submits the form
		$(".btn-save").on('click', function() {
			var form = $("#results-form");

			form.submit();
		});

		$("body").on('focus','.sync-fields input[type="text"]',function(e) {
			$(this).closest("tr").find(".sync-fields > *").each(function() {
				$(this).css('box-shadow','2px 3px 2px #888');
			});
		});

		$("body").on('blur','.sync-fields input[type="text"]',function(e) {
			$(this).closest("tr").find(".sync-fields > *").each(function() {
				$(this).css('box-shadow','none');
			});
		});
/*
		$("body").on('click','.btn-add', function() {
			var item = $("#add_item").val().trim();

			if (item=='') {
				modalAlertShow('Add Item','Uhh....did you forget something? Try again, but this time enter the item FIRST',false);
				return;
			}

			$("#alert-continue").html('<i class="fa fa-save"></i> Save and Continue');
			modalAlertShow('Add Item "'+item+'"','Your current changes will be saved first, then your new item "'+item+'" will be added. Are you ready to continue?',true,'addItem',item);
		});
*/

		$("select[name='companyid']").on('change', function() {
			companyid = $(this).val();

			$(".market-company").each(function() {
				if ($(this).data('cid')==companyid) {
					$(this).addClass('company-text').addClass('text-primary');
				} else {
					$(this).removeClass('company-text').removeClass('text-primary');
				}
			});

			$("#company_info").html('<i class="fa fa-circle-o-notch fa-spin"></i>');
			$.ajax({
				url: '/json/company_info.php',
				type: 'get',
				data: {
					'companyid': companyid,
					'list_type': list_type,
				},
				settings: {async:true},
				error: function(xhr, desc, err) {
//					$('#loader').hide();
//					modalAlertShow('Error',desc,false);
					$("#company_info").html('Error: '+desc);
				},
				success: function(json, status) {
					if (json.q>0) {
						var html = '<a href="miner.php?companyid='+companyid+'&START_DATE='+json.d+'&min_price=1" target="_miner">'+json.q+' quote(s)</a> '+
							'to <a href="sales.php?companyid='+companyid+'&START_DATE='+json.d+'&filter=all" target="_sales">'+json.s+' sale(s)</a> since '+json.d;
						$("#company_info").html(html);
					} else {
						$("#company_info").html('');
					}
				},
			});
		});

		// re-initialize event handler for tooltips
		$('body').tooltip({ selector: '[rel=tooltip]' });

		$('body').on('change', '.group-item', function() {
			$(this).syncLocks();
		});
		$('body').on('click', '.lock-toggle', function() {
			$(this).find(".fa").each(function() {
				if ($(this).hasClass('fa-lock')) { $(this).removeClass('fa-lock').addClass('fa-unlock'); }
				else { $(this).removeClass('fa-unlock').addClass('fa-lock'); }
			});
		});


		/***** PRICING MULTIPLIERS *****/
		$('body').on('change','.list-price',function() {
			var row = $(this).closest("tr");//.header-row");
			var ln = row.data('ln');

//			var items_row = $("#items_"+ln);
//			var rprice = items_row.find(".response-price").val().trim().replace(',','');
			var rprice = row.find(".response-price").val().trim().replace(',','');
			var lprice = $(this).val().trim();
//			var markup = items_row.find(".cost-markup").val().trim().replace(',','');
			var markup = row.find(".cost-markup").val().trim().replace(',','');
			if (rprice>0) {
				var pct = 100*((rprice/lprice)-1);
				markup = pct.formatMoney(2);
			} else {
				if (markup>0) {
					var pct = 100*((rprice/lprice)-1);
					markup = pct.formatMoney(2);
				} else {
					markup = '';
				}
			}
//			items_row.find(".cost-markup").val(markup);
			row.find(".cost-markup").val(markup);
		});

		$('body').on('change','.cost-markup',function() {
			var row = $(this).closest("tr");//.items-row");
			var ln = row.data('ln');

			var markup = $(this).val().trim().replace(',','');
//			var header_row = $("#row_"+ln);
//			var lprice = header_row.find(".list-price").val().trim().replace(',','');
			var row = $("#row_"+ln);
			var lprice = row.find(".list-price").val().trim().replace(',','');
			var rprice = '';
			if (lprice>0 && markup>0) {
				var amt = parseFloat(lprice)+parseFloat(lprice*(markup/100));//row.find(".response-price").val().trim();
				rprice = amt.formatMoney(2);
			}
			var price_field = row.find(".response-price");
			price_field.val(rprice);
			$("#items_"+ln).find(".item-price:first").syncLocks(rprice);

			price_field.updateRowTotal();
		});

		$('body').on('change','.response-price',function() {
			var row = $(this).closest("tr");//.items-row");
			var ln = row.data('ln');

			var qty = parseInt(row.find(".response-qty").val().trim());

			var rprice = $(this).val().trim().replace(',','');
			$("#items_"+ln).find(".item-price:first").syncLocks(rprice);

//			var header_row = $("#row_"+ln);
//			var lprice = header_row.find(".list-price").val().trim().replace(',','');
			var row = $("#row_"+ln);
			var lprice = row.find(".list-price").val().trim().replace(',','');

			var markup = '';
			if (lprice!='' && lprice>0) {
				var pct = 100*((rprice/lprice)-1);
				markup = pct.formatMoney(2);
			}
			row.find(".cost-markup").val(markup);
		});
		/***** END PRICING MULTIPLIERS *****/



		$("body").on('click','.checkItems',function() {
			var chk = $(this);
			var items_row = $(this).closest("tr").next();

			items_row.find(".table-items .item-check:checkbox, .table-items .item-check:radio").each(function() {
				$(this).prop('checked', chk.prop('checked'));
				$(this).setRow();
			});

			items_row.updateResults();
		});

		$("body").on('click','.item-check:checkbox, .item-check:radio',function(e) {
			if ($(this).is('[readonly]')) {
				e.preventDefault();
				modalAlertShow("Sorry, not sorry","This list is readonly and cannot be edited.");
				return false;
			}
			$(this).setRow();
			$(this).closest(".items-row").updateResults();//$(this).closest(".items-row"));
		});

		$("body").on('click','.btn-pricing',function() {
			var ln = $(this).closest("tr").data('ln');
			var pricing = $("#market_"+ln).data('pricing');
			if (pricing==1) { pricing = 0; } else { pricing = 1; }
			if (pricing) {
				$(this).find('.fa').removeClass('fa-compress').addClass('fa-expand');
				$(this).closest('.title-data').find('span').removeClass('text-brown').addClass('text-white');
			} else {
				$(this).find(".fa").removeClass('fa-expand').addClass('fa-compress');
				$(this).closest('.title-data').find('span').removeClass('text-white').addClass('text-brown');
			}
			$("#market_"+ln).data('pricing',pricing);
			$("#items_"+ln).find(".bg-market").each(function() { $(this).marketResults(1,pricing); });
		});

		$("body").on('click','.merge-parts',function() {
			var row = $(this).closest(".header-row");
			var ln = row.data('ln');
			var items = $("#items_"+ln);
			var chex = items.find(".item-check:checked");
			var nchex = chex.length;

			modalAlertShow("Merging Parts is Permanent","You cannot undo this action! Do you really want to proceed?",true,'mergeParts',items);
		});

		$("body").on('change','.product-search',function() {
			$("#results").partResults($(this).val(),$(this).closest(".header-row").data('ln'));
		});

		$('body').on('change','.response-qty, .response-price',function() {
			$(this).updateRowTotal();
		});

		$("body").on('click','.lk-download',function() {
			$(this).html('<i class="fa fa-circle-o-notch fa-spin"></i>');
			$(this).blur();
			$(this).closest(".bg-market").marketResults(2);
		});

		$("body").on('mouseover','.check-related',function() {
			$(this).closest(".table-items").find(".item-check").closest("div").addClass('bg-info')
		});
		$("body").on('mouseout','.check-related',function() {
			$(this).closest(".table-items").find(".item-check").closest("div").removeClass('bg-info');
		});

		$("body").on('click','.lk-exporter',function() {
			var ln = $(this).closest(".items-row").data('ln');
			var s = $("#row_"+ln).find(".product-search").val();
			window.open('inventory_exporter.php?s='+s);
		});

		$("body").on('click','.lk-inventory',function() {
			var items_row = $(this).closest(".items-row");
			var partids = getCheckedPartids(items_row.find(".table-items tr"));
			window.open('inventory.php?partids[]='+partids);
		});

		$("body").on('click','.lk-open',function() {
			var type = $(this).data('type');
			var order = $(this).data('order');
			if (type=='purchase_requests') {
				window.open('purchase_requests.php');
			} else if (type=='manage_quote') {
				window.open('manage_quote.php?metaid='+order);
			} else {
				window.open('order.php?order_type='+type+'&order_number='+order);
			}
		});

		$("body").on('change','.item-price',function() {
			$(this).calcItems();
		});

		$("body").on('change','.item-qty, .item-check',function() {
			$(this).calcItems();
		});

		$("body").on('click','.btn-response',function() {
			$(this).closest('.btn-group').find('.btn').each(function() {
				$(this).removeClass('active');
			});
			$(this).addClass('active');

			var row = $(this).closest(".header-row");
			var type = $(this).data('type');
			row.toggleView(type);
		});

		$("body").on('click','.btn-response-master',function() {
			$(this).closest('.btn-group').find('.btn').each(function() {
				$(this).removeClass('active');
			});
			$(this).addClass('active');

			var type = $(this).data('type');
			$(".header-row").each(function() {
				$(this).toggleView(type);
			});
		});

		$("body").on('click','.save-part',function() {
			var alias = $(this).closest(".alias");
			var partid = $(this).closest(".product-row").data('partid');
			var part_str = $(this).data('part');

			var user_conf = confirm("You are updating this part to:\n \n"+part_str+"\n \nAre you sure?");
			if (user_conf===false) { return; }

			$.ajax({
				url: 'json/save-parts.php',
				type: 'get',
				data: { 'field': 'part', 'partid': partid, 'new_value': escape(part_str) },
				settings: {async:true},
				error: function(xhr, desc, err) {
				},
				success: function(json, status) {
					if (json.message && json.message!='Success') {
						modalAlertShow('Error',json.message,false);

						return;
					}
					alias.remove();
					toggleLoader('Alias Removed Successfully');
				},
			});
		});

		// upon scrolling of window content, load any viewed rows with unloaded data
		$(window).scroll(function(e) {
			loadViews();
		});

		$("body").on('click','.view-results',function() {
			var title = $(this).data('title');
			var type = $(this).data('type');
			var modal_target = $(this).data('target');

			$("#"+modal_target).modal('hide');

			var items_row = $(this).closest(".items-row");
			var ln = items_row.data('ln');
			var first = $("#row_"+ln);
			var productSearch = first.find(".product-search").val().toUpperCase();

			var partids = getCheckedPartids(items_row.find(".table-items tr"));

			var results_mode = pricing_default;//global variable to define what type of results we want to see
			if ($("#row_"+ln+" .btn-pricing").length>0) { results_mode = $("#row_"+ln+" .btn-pricing").data('pricing'); }

			// set title of modal
			if (results_mode) { title += ' - Prices Only'; } else { title += ' - All'; }
			$("#"+modal_target+" .modal-title").html(title);
			$("#"+modal_target+" .message-subject").val(productSearch);
			$("#"+modal_target+" .message-body").val('Please quote:\n\n'+productSearch);

			// prepare modal body
			var modalBody = $("#"+modal_target+" .modal-body");
			modalBody.attr('data-ln',ln);

			// reset html so when it pops open, there's no old data
			modalBody.html('<div class="text-center"><i class="fa fa-circle-o-notch fa-spin fa-5x"></i></div>');

			// initialize html body with first row of company selector
			var html = '';//addResultsRow(type);

			if (type=='Supply') {
				html = '\
		<div class="row">\
			<div class="col-sm-1" style="background-color:white"> </div>\
			<div class="col-sm-8" style="background-color:white">\
				<select name="companyids[]" size="1" class="form-control companies-selector" data-placeholder="- Select Company for RFQ -"></select>\
			</div>\
			<div class="col-sm-3" style="background-color:white"> </div>\
			<input type="hidden" name="partids" value="'+partids+'">\
		</div>\
				';
			}

			$("#"+modal_target).modal('show');

			var res,cid,company,date,p,price,qty,rfq,searches,sources,props;
			$.ajax({
				url: 'json/availability.php',
				type: 'get',
				data: { 'attempt': '0', 'partids': partids, 'results_mode': results_mode, 'detail': '1', 'type': type },

				settings: { async:true },
				error: function(xhr, desc, err) {
					$("#"+modal_target).modal('hide');
				},
				success: function(json, status) {
					res = json.results;

					$.each(res, function(formatted_date, date_res) {
						html += '\
							<div class="row">\
								<div class="col-sm-1">\
									<input type="checkbox" class="checkTargetAll" data-target=".check-group"/>\
								</div>\
								<div class="col-sm-1">'+formatted_date+'</div>\
								<div class="col-sm-3">Company</div>\
								<div class="col-sm-1">Source</div>\
								<div class="col-sm-2">Search</div>\
								<div class="col-sm-1">Price</div>\
								<div class="col-sm-2">Lead-Time</div>\
								<div class="col-sm-1">Notes</div>\
							</div>\
						';

						// process each row of data
						$.each(date_res, function(date_cid, row) {
							rfq = '';
							if (row.rfq && row.rfq!='') {
								rfq = ' <i class="fa fa-paper-plane text-primary" title="'+row.rfq+'" data-toggle="tooltip" data-placement="bottom" rel="tooltip"></i>';
							}

							qty = '<input type="text" name="" class="form-control input-xs" value="'+row.qty+'" \>';

							sources = '';
							$.each(row.sources, function(i, src) {
								var source_lower = src.toLowerCase();
								var source_img = '';
								if (source_lower=='email') {
									source_img = '<i class="fa fa-envelope-o"></i>';//fa-email?
								} else if (source_lower=='ar') {
									source_img = '<i class="fa fa-cloud-download"></i>';
								} else if (source_lower=='import') {
									source_img = '<i class="fa fa-database"></i>';
								} else {
									source_img = '<img src="img/'+source_lower+'.png" class="bot-icon" />';
								}
								if (sources!='') { sources += ' '; }
								if (row.lns[source_lower]) {
									sources += '<a href="http://'+row.lns[source_lower]+'" target="_new">'+source_img+'</a>';
								} else {
									sources += source_img;
								}
							});
							if (sources=='') { sources = '&nbsp;'; }

							searches = '&nbsp;';
							if (row.search!='') { searches = '<span class="info">'+row.search+'</span>'; }

							props = '';
							p = '';
							if (row.cid!=34 && row.price!="") {
								p = '$ '+Number(row.price.replace(/[^0-9\.-]+/g,"")).toFixed(2);
							} else if (row.cid==34) { props += ' disabled'; }
/*
							price = '<input type="text" name="" class="form-control input-xs" value="'+p+'" \>';
									<div class="input-group input-xs">\
										<span class="input-group-addon input-xs"><i class="fa fa-dollar"></i></span>\
										'+price+'\
									</div>\
*/
							company = '<a href="company.php?companyid='+row.cid+'" target="_companies"><i class="fa fa-building"></i></a> '+row.company;

							html += '\
							<div class="row">\
								<div class="col-sm-1">\
									<input type="checkbox" class="item-check" name="companyids[]" value="'+row.cid+'" '+props+'/>'+rfq+'\
								</div>\
								<div class="col-sm-1"><strong>'+row.qty+'</strong>&nbsp;</div>\
								<div class="col-sm-3"><small>'+company+'</small></div>\
								<div class="col-sm-1">'+sources+'</div>\
								<div class="col-sm-2">'+searches+'</div>\
								<div class="col-sm-1 text-right">&nbsp;'+p+'</div>\
								<div class="col-sm-2">&nbsp;</div>\
								<div class="col-sm-1">&nbsp;</div>\
							</div>\
							';
						});
					});

					modalBody.html('<div class="check-group">'+html+'</div>');
					modalBody.find(".companies-selector").selectize('/json/companies.php');
				},
			});
		});
	});

	jQuery.fn.setRow = function() {
		if ($(this).prop('checked')===true) {
			$(this).closest("tr").removeClass('sub').addClass('primary');
		} else {
			$(this).closest("tr").removeClass('primary').addClass('sub');
		}
	};

	var LIST_TOTAL = 0;
	jQuery.fn.updateRowTotal = function() {
//		var row = $(this).closest(".response-calc");
		var row = $(this).closest("tr");
		var qty = 0;
		var price = 0;
		if ($(this).hasClass('response-qty')) {
			qty = $(this).val();
			price = row.find('.response-price').val().replace(',','');
		} else if ($(this).hasClass('response-price')) {
			price = $(this).val().replace(',','');
			qty = row.find('.response-qty').val();
		}
		var total = qty*price;

		var t = row.find(".row-total h5");
		if (t.length==0) { return; }
		var current_amt = parseFloat(t.html().replace('$ ','').replace(',',''));
		t.html('$ '+total.formatMoney(2));

		LIST_TOTAL += total-current_amt;

//		if (LIST_TOTAL.formatMoney(2)==LIST_TOTAL.formatMoney(4)) {
			$("#list_total").html('$ '+LIST_TOTAL.formatMoney(2));
//		} else {
//			$("#list_total").html('$ '+LIST_TOTAL.formatMoney(4));
//		}
	};

//	jQuery.fn.isScrolledIntoView = function(e) {
	function isScrolledIntoView(e,docViewTop,height) {
		var docViewBottom = docViewTop + height;

		var elemTop = $(e).offset().top;
		var elemBottom = elemTop + $(e).height();

		return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
	};

	jQuery.fn.toggleView = function(type) {
		var row = $(this);
		var ln = row.data('ln');

		$("#row_"+ln+", #items_"+ln).find("input, textarea, select").each(function() {
			// if disabling, if minimizing, if saving with no reply, make sure we're not unchecking checkboxes and list qty and product search, but all else is disabled
			if (type=='disable' || type=='minimize' || (type=='noreply' && ! $(this).is(':checkbox') && ! $(this).hasClass('product-search') && ! $(this).hasClass('list-qty'))) {
				if (type=='minimize') {//no disabling, just minimizing
					$(this).attr('disabled',false);
				} else {
					$(this).attr('disabled',true);
				}
				if (type=='disable' || type=='minimize') {// collapse the items when disabling or minimizing
					$("#items_"+ln).addClass('hidden');
				} else {
					$("#items_"+ln).removeClass('hidden');
				}
			} else {
				$(this).attr('disabled',false);
				$("#items_"+ln).removeClass('hidden');
			}
		});
	};

	jQuery.fn.calcItems = function() {
		var ln = $(this).closest(".items-row").data('ln');
		var sum_qty = 0;
		var sum_price = 0;
		var partid,qty,price;
		$(this).closest(".table-items").find(".item-check:checked").each(function() {
			partid = $(this).val();
			qty = parseInt($("#"+partid+"-"+ln).find(".item-qty").val().trim());
			if (! qty) { return; }

			price = parseFloat($("#"+partid+"-"+ln).find(".item-price").val().trim().replace(',',''));

			sum_qty += qty;
			if (price>0) {
				sum_price += (qty*price);
			}
		});
		$("#row_"+ln).find(".response-qty").val(sum_qty);
		$("#row_"+ln).find(".response-price").val((sum_price/sum_qty).formatMoney(2));

		var td = $("#row_"+ln).find(".response-qty").closest("td");
		td.addClass('bg-warning');
		setTimeout(function() { td.removeClass('bg-warning'); }, 100);

		$("#row_"+ln).find(".response-qty").updateRowTotal();
	};

	jQuery.fn.updateItemFields = function(v) {
		if (! v) {
			var e = $(this);
			if (e.prop('checked')===false) { return; }
			var v = e.val();
		}

		if (v=='Sale' || v=='Demand'){
//			$('.items-row').find('.table-items input[type=text]').removeClass('hidden');
			$('.items-row').find('.table-items .sell').removeClass('hidden');
			$('.items-row').find('.table-items input[type=text]').attr('disabled',false);
		} else {
//			$('.items-row').find('.table-items input[type=text]').addClass('hidden');
			$('.items-row').find('.table-items .sell').addClass('hidden');
			$('.items-row').find('.table-items input[type=text]').attr('disabled',true);
		}
	};

	jQuery.fn.saveMenu = function() {
		var li = $(this);

		// update list type as List/WTB
		list_type = li.data('handler');
		$("#list_type").val(list_type);

		// reset Active class to selected option
		li.closest(".dropdown-menu").find("li a").each(function() {
			if ($(this).data('handler')==list_type) {
				$(".btn-save").addClass($(this).data('btn'));
				if ($(this).data('bg')) {
					$("#task_bar").addClass($(this).data('bg'));
				}
//				$(this).removeClass('text-white').removeClass('btn-success').addClass('text-white').addClass('btn-success');
			} else {
				$(".btn-save").removeClass($(this).data('btn'));
				if ($(this).data('bg')) {
					$("#task_bar").removeClass($(this).data('bg'));
				}
//				$(this).removeClass('text-white').removeClass('btn-success');
			}
		});

		li.closest(".btn-group").find(".btn-save").html($(this).html());
	};

	jQuery.fn.syncLocks = function(v) {
			var master_lock = $(this);
			var lock_class = $(this).data('class');

			// confirm padlock isn't UNlocked, which would make this a unique input change
			var isLocked = false;
			var locks = master_lock.closest(".input-group").find(".fa");
			locks.each(function() {
				if ($(this).hasClass("fa-lock")) { isLocked = true; }
			});

			if (! v) {
				var v = master_lock.val().trim().replace(',','');

				// a master lock is allowed to NOT have an associated padlock, but any case where it DOES, it needs to be locked
				if (locks.length>0 && isLocked===false) { return; }
			} else if (isLocked) {//set to master in case we're enforcing an exterior value
				master_lock.val(v);
			}

			var control,lock;
			$("."+lock_class).not(this).each(function() {
				control = $(this);

				lock = control.closest(".input-group").find(".fa-lock").each(function() {
					control.val(v);
				});

				if (control.hasClass('response-qty')) { control.updateRowTotal(); }
			});
	};


	TI = 100;//tabindex to start after other fixed html objects have their turn
	jQuery.fn.partResults = function(search,replaceNode) {
		if (! search) {
			var search = '';
		}

		if (! replaceNode && replaceNode!==0) { var replaceNode = false; }
		var filter_LN = false;

		// show loader on initial load
		if (lim==0 || (search || replaceNode!==false)) { $('#loader').show(); }

		// if replaceNode is passed in then we're updating just that existing row; otherwise, global variable
		// line_number is a filter that is intending to just show that one line item from a given list - no
		// existing row, just building a new row of a single line item
		if (replaceNode!==false) {
			filter_LN = replaceNode;
		} else if (line_number!==false && line_number !=='') {
			filter_LN = line_number;
		}

		var filter_searchid = false;
		if (searchid!==false && searchid!=='') {
			filter_searchid = searchid;
		}

		var table = $(this);

		var labels = [];
		var supply = [];
		var demand = [];

		var rows,header_row,items_row,n,s,clonedChart,rspan,avg_cost,dis,add_lk,merge_lk,ph,prop,qty_prop,btn_prop;

		$.ajax({
			url: '/json/m2.php',
			type: 'get',
			data: {
				'listid': listid,
				'list_label': list_label,
				'companyid': companyid,
				'list_type': list_type,
				'lim': lim,
				'search': search,
				'PR': PR,
				'salesMin': salesMin,
				'favorites': favorites,
				'startDate': startDate,
				'endDate': endDate,
				'demandMin': demandMin,
				'demandMax': demandMax,
				'ln': filter_LN,
				'searchid': filter_searchid,
				'import_quote': import_quote,
			},
			settings: {async:true},
			error: function(xhr, desc, err) {
				$('#loader').hide();
				$("#results").html('<tr><td class="text-center">'+desc+'</td></tr>');
			},
			success: function(json, status) {
				$('#loader').hide();
				if (json.message && json.message!='') {
					$("#results").html('<tr><td class="text-center">'+json.message+'</td></tr>');

					return;
				}

				$.each(json.results, function(ln, row) {
					n = Object.keys(row.results).length;//row.results.length;
					s = '';
					if (n!=1) { s = 's'; }

					prop = '';
					if (row.prop) { prop = row.prop; }
					qty_prop = '';
					btn_prop = '';
					if (list_type=='Sale' || list_type=='Demand') {
						qty_prop = ' readonly';
						btn_prop = ' disabled';
					}

					add_lk = '';
					merge_lk = '';
					if (n==0) {
						if (row.s!='') {
							add_lk = '<a href="javascript:void(0);" class="add-part" data-partid="" data-ln="'+ln+'" title="add new part" data-toggle="tooltip" data-placement="bottom" rel="tooltip"><i class="fa fa-plus"></i></a>';
						}
					} else {
						merge_lk = '<a href="javascript:void(0);" class="merge-parts" title="merge selected parts" data-toggle="tooltip" data-placement="bottom" rel="tooltip"><i class="fa fa-chain"></i></a>';
					}

					rspan = 2;//n+1;

					buttons = '<div class="btn-group">\
                            <button class="btn btn-xs btn-default btn-response left" data-type="disable" type="button" title="disable & collapse" data-toggle="tooltip" data-placement="top" rel="tooltip"><i class="fa fa-close"></i></button>\
                            <button class="btn btn-xs btn-default btn-response middle" data-type="minimize" type="button" title="save, minimize" data-toggle="tooltip" data-placement="top" rel="tooltip"><i class="fa fa-window-minimize"></i></button>\
                            <button class="btn btn-xs btn-default btn-response middle" data-type="noreply" type="button" title="save, no reply" data-toggle="tooltip" data-placement="top" rel="tooltip"><i class="fa fa-square-o"></i></button>\
                            <button class="btn btn-xs btn-default btn-response right active" data-type="reply" type="button" title="save & reply" data-toggle="tooltip" data-placement="top" rel="tooltip"><i class="fa fa-check-square-o"></i></button>\
                        </div>';

					ph = row.s;
					if (ph=='') { ph = 'Add item...'; }

					header_row = '\
						<tr id="row_'+ln+'" class="header-row first" data-ln="'+ln+'" data-id="'+row.id+'" data-label="'+row.lbl+'">\
							<td class="col-sm-5 nopadding-left nopadding-right">\
								<div class="col-sm-1 colm-sm-0-5">\
									<input type="checkbox" name="check['+ln+']" class="checkItems" value="'+ln+'" checked tabindex="-1" '+prop+'>\
									<input type="hidden" name="rows['+ln+']" value="'+ln+'" tabindex="-1" '+prop+'><br/>\
									'+merge_lk+'\
								</div>\
								<div class="col-sm-7 search">\
									<input type="text" name="searches['+ln+']" class="form-control input-xs input-camo product-search" value="'+row.s+'" placeholder="'+ph+'" tabindex="-1" '+prop+'/><br/>\
									<span class="info text-brown">'+n+' result'+s+'</span>'+add_lk+' &nbsp;\
									<span class="info"><small>'+row.str+'</small></span>\
								</div>\
								<div class="col-sm-2 nopadding-left nopadding-right">\
									<div class="pull-right">\
										<input type="text" name="list_qtys['+ln+']" class="form-control input-xs required list-qty qty-lock-'+ln+'" data-class="qty-lock-'+ln+'" value="'+row.q+'" placeholder="Qty" title="required qty?" data-toggle="tooltip" data-placement="top" rel="tooltip" tabindex="'+(TI++)+'" '+prop+'><br/>\
										<span class="info">req qty</span>\
									</div>\
								</div>\
								<div class="col-sm-2 colm-sm-2-5 nopadding-left nopadding-right">\
									<div class="pull-right">\
										<div class="input-group border-group sync-fields">\
											<span class="input-group-addon"><i class="fa fa-dollar"></i></span>\
											<input type="text" name="list_prices['+ln+']" class="form-control right input-xs list-price" value="'+row.p+'" placeholder="0.00" title="" data-toggle="tooltip" data-placement="top" rel="tooltip" tabindex="'+(TI++)+'" '+prop+'>\
										</div>\
										<span class="info pull-right">price ea (optional)</span>\
									</div>\
								</div>\
							</td>\
							<td class="col-sm-1 colm-sm-1-5">\
								<div class="text-center">\
									<div class="col-sm-3 nopadding">\
										<i class="fa fa-times fa-lg valign-bottom" aria-hidden="true"></i>\
									</div>\
									<div class="col-sm-6 nopadding valign-top">\
										<div class="input-group border-group sync-fields">\
											<input class="form-control right input-xs text-center text-muted cost-markup" name="markup['+ln+']" value="'+row.m+'" placeholder="0" type="text" title="price x profit = bid $$" data-toggle="tooltip" data-placement="top" rel="tooltip" tabindex="'+(TI++)+'" '+prop+'>\
											<span class="input-group-addon"><i class="fa fa-percent" aria-hidden="true"></i></span>\
										</div>\
									</div>\
									<div class="col-sm-3 nopadding">\
										<i class="fa fa-pause fa-rotate-90 valign-bottom" aria-hidden="true"></i>\
									</div>\
								</div>\
								<div class="text-center">\
									<span class="info">profit/markup</span>\
								</div>\
							</td>\
							<td class="col-sm-1 colm-sm-1-5 text-center">\
								<div class="form-group" style="display:inline-block; width:50px">\
									<div class="input-group infinity">\
										<span class="input-group-btn">\
											<button class="btn btn-default input-xs lock-toggle" type="button" tabindex="-1" data-toggle="tooltip" data-placement="left" title="lock with req qty" rel="tooltip" '+btn_prop+'><i class="fa fa-lock"></i></button>\
										</span>\
										<input type="text" class="form-control input-xs response-qty group-item qty-lock-'+ln+'" data-class="qty-lock-'+ln+'" name="response_qtys['+ln+']" value="'+row.q+'" placeholder="0" title="bid qty" data-toggle="tooltip" data-placement="top" rel="tooltip" tabindex="-1" '+prop+qty_prop+'>\
									</div>\
								</div>\
								<i class="fa fa-times fa-lg"></i>&nbsp;\
								<div class="form-group" style="width:100px">\
									<div class="input-group border-group sync-fields">\
										<span class="input-group-addon" aria-hidden="true"><i class="fa fa-usd"></i></span>\
										<input type="text" class="form-control right input-xs response-price text-right" name="response_prices['+ln+']" value="'+row.qt+'" placeholder="0.00" title="bid price" data-toggle="tooltip" data-placement="top" rel="tooltip" tabindex="'+(TI++)+'" '+prop+'>\
									</div>\
								</div><br/>\
								<span class="info">our bid</span>\
							</td>\
							<td class="col-sm-1 colm-sm-1-5 text-center">\
								<div class="select-xs">\
									<input class="form-control input-xs date_number" type="text" name="leadtime['+ln+']" placeholder="#" value="'+row.lt+'" tabindex="-1" style="max-width:50px">\
									<select class="form-control select2" name="leadtime_span['+ln+']" style="max-width:75px">\
										<option value="Days"'+((row.lts=='Days') ? ' selected' : '')+'>Days</option>\
										<option value="Weeks"'+((row.lts=='Weeks') ? ' selected' : '')+'>Weeks</option>\
										<option value="Months"'+((row.lts=='Months') ? ' selected' : '')+'>Months</option>\
									</select>\
<!--\
									<span class="info" style="padding-left:8px; padding-right:8px">or</span>\
									<div class="form-group" style="max-width:200px;">\
										<div class="input-group datepicker-date date datetime-picker" style="min-width: 100%; width: 100%;" data-format="MM/DD/YYYY">\
											<input type="text" name="delivery_date['+ln+']" class="form-control input-xs delivery_date" value="" tabindex="-1" placeholder="mm/dd/yyyy">\
											<span class="input-group-addon">\
												<span class="fa fa-calendar"></span>\
											</span>\
										</div>\
									</div>\
-->\
								</div>\
								<span class="info">delivery</span>\
							</td>\
							<td class="col-sm-2 colm-sm-2-5">\
								<div class="row-total text-right sync-fields" title="row total" data-toggle="tooltip" data-placement="top" rel="tooltip"><h5>$ 0.00</h5></div>\
								<div class="pull-right">\
									'+buttons+' &nbsp; <strong>'+(row.ln)+'.</strong>\
								</div>\
							</td>\
						</tr>\
					';

					rows = buildItemRows(row,ln,json.manfs,json.systems);

					items_row = '\
						<tr id="items_'+ln+'" class="items-row" data-ln="'+ln+'" data-loaded="">\
					';
					if (rows!='') {
						items_row += '\
							<td>\
								<div class="mh">\
								<table class="table table-condensed table-striped table-hover table-items">\
									<thead>\
										<tr>\
											<th class="col-sm-1 colm-sm-0-5"> </th>\
											<th class="col-sm-9">\
												Description\
											</th>\
											<th class="col-sm-1">\
												<a class="btn btn-xs btn-default text-bold check-related lk-inventory" href="javascript:void(0);">'+
													'<i class="fa fa-qrcode"></i> Stock</a>\
											</th>\
											<th class="col-sm-1 colm-sm-1-5 text-right">Price</th>\
										</tr>\
									</thead>\
									<tbody>'+rows+'</tbody>\
								</table>\
								</div>\
							</td>\
							<td class="bg-market" data-type="Supply" data-pricing="'+pricing_default+'" id="market_'+ln+'"></td>\
							<td class="bg-purchases" data-type="Purchase" data-pricing="1"></td>\
							<td class="bg-sales" data-type="Sale" data-pricing="1"></td>\
							<td class="bg-demand" data-type="Demand" data-pricing="0"></td>\
							<td class="">\
							</td>\
						';
					}
					items_row += '\
						</tr>\
					';

					if (replaceNode!==false) {// && $("#row_"+replaceNode).length>0) {
						$("#chart_"+replaceNode).remove();
						$("#row_"+replaceNode).replaceWith(header_row);
						$("#items_"+replaceNode).replaceWith(items_row);
					} else {
						table.append(header_row);
						table.append(items_row);
					}

					//$("#items_"+ln).find(".response-price").updateRowTotal();
					$("#row_"+ln).find(".response-price").updateRowTotal();
					if (! search && ! replaceNode) { lim = ln; }
				});

				if (! search && ! replaceNode) { lim++; }
				if (json.n && json.n>lim) {
					$("#pad-wrapper").append('<div class="infinite_scroll text-center margin-bottom-220" data-lim="'+lim+'" style="width:100%"><h2><i class="fa fa-circle-o-notch fa-spin"></i></h2></div>');
				}

				if (replaceNode!==false) {// && $("#items_"+replaceNode).length>0) {
					$("#items_"+replaceNode).updateResults();//$("#items_"+replaceNode));
				} else {
//					table.find(".items-row").updateResults();//table.find(".items-row"));
				}
			},
			complete: function(result) {
				table.find(".select2").select2();
//				$('.slider-frame input[type=radio]:checked').each(function() { $(this).updateItemFields(); });
				table.updateItemFields($("#list_type").val());
				loadViews();

				// this is the infinite_scroll tag but we have to refer to it by the latent_scroll class
				// since by this point, infinite_scroll has been removed from it
				$(".latent_scroll").remove();

/*
				if (replaceNode!==false) { return; }

				var header_row = '\
						<tr id="row_add" class="header-row first" data-ln="add" data-id="" data-label="">\
							<td class="col-sm-1 colm-sm-0-5" style="padding:2px">\
								<div class="row" style="margin:0px">\
									<div class="col-sm-4 text-center nopadding">\
									</div>\
									<div class="col-sm-8 text-center nopadding">\
									</div>\
								</div>\
							</td>\
							<td class="col-sm-3 colm-sm-3-5">\
								<div class="form-group">\
									<div class="input-group">\
										<input type="text" class="form-control input-sm" name="searches[add]" id="add_item" value="" placeholder="Add item...">\
										<span class="input-group-btn">\
											<button class="btn btn-default btn-sm btn-add" type="button"><i class="fa fa-plus"></i></button>\
										</span>\
									</div>\
								</div>\
								<div class="price text-center">\
								</div>\
							</td>\
						</tr>\
				';

				$("#results").append(header_row);
*/
			},
		});
	};/*end partResults*/

//	function updateResults(row) {
	jQuery.fn.updateResults = function() {
		var row = $(this);
		row.find(".bg-market").each(function() { $(this).marketResults(0); });
		row.find(".bg-purchases").each(function() { $(this).marketResults(0); });
		row.find(".bg-outsourced").each(function() { $(this).marketResults(0); });
		row.find(".bg-sales").each(function() { $(this).marketResults(0); });
		row.find(".bg-services").each(function() { $(this).marketResults(0); });
		row.find(".bg-repairs").each(function() { $(this).marketResults(0); });
		row.find(".bg-demand").each(function() { $(this).marketResults(0); });
		row.data('loaded','1');
	};

	function loadViews() {
		var docViewTop = $(window).scrollTop();
		var height = $(window).height();
		var ln,view,items_row;

		$(".infinite_scroll").each(function() {
			view = isScrolledIntoView($(this), docViewTop, height);

			if (view) {
				// need to remove the class so the user can't speed scroll to the end while we're still
				// processing the below request, which would repeat the same request back onto the table
				$(this).addClass('latent_scroll').removeClass('infinite_scroll');
				$("#results").partResults();
				return;
			}
		});

		$(".header-row").each(function() {
			ln = $(this).data('ln');
			items_row = $("#items_"+ln);
			if (items_row.data('loaded')!='' || items_row.hasClass('hidden')) { return; }

			view = isScrolledIntoView($(this), docViewTop, height);
			if (view) {
				items_row.updateResults();
			}
		});
	}

	function buildItemRows(row,ln,manfs,systems) {
					var results = row.results;
					var rows = '';
//					partids = '';

					var notes,aliases,alias_str,edit,descr,part,mpart,prop,cls,item_class,vqty,input_type,manfid,fav;

					$.each(results, function(pid, item) {
						item_class = 'sub';
						prop = '';
						input_type = 'checkbox';
						//if (item.class=='primary') { prop = ' checked'; }
						if (item.prop.checked) {
							prop += ' checked';
							item_class = 'primary';
//						} else {
//							item_class = item.class;
						}
						if (item.prop.disabled) { prop += ' disabled'; }
						if (item.prop.readonly) { prop += ' readonly'; }
						if (item.prop.type) { input_type = item.prop.type; }

						cls = 'product-row row-'+item.id+' '+item_class;
						if (item.stk>0) { cls += ' in-stock'; }

						partid = item.id;
/*
						if (parseInt(partid)>0) {
							if (partids!='') { partids += ','; }
							partids += partid;
						}
*/
						part = '<span class="part_text">'+item.part;
						if (item.heci) { part += ' '+item.heci; }
						part += '</span>';

						aliases = '';
						alias_str = '';

						descr = '';
						if (item.manfid) {
							descr += manfs[item.manfid];
						}
						if (item.systemid) {
							if (descr!='') { descr += ' '; }
							descr += systems[item.systemid];
						}
						if (item.descr) { if (descr!='') { descr += ' '; } descr += item.descr; }
						$.each(item.aliases, function(a, alias) {
							if (alias_str!='') alias_str += ' ';
							mpart = item.part.replace(' '+alias,'');
							alias_str += '<span class="alias">'+alias+'<a href="javascript:void(0);" data-part="'+mpart+'" class="save-part"><i class="fa fa-times-circle text-danger"></i></a></span>';
						});
						if (alias_str!='') { aliases = ' &nbsp; <div class="show-hover"><small>'+alias_str+'</small></div>'; }

						notes = '<span class="item-notes"><i class="fa fa-sticky-note-o"></i></span>';
/*
						$.each(item.notes, function(n2, note) {
						});
*/

						fav_icon = 'fa-star-o';
						if (item.fav && item.fav>0) {
							if (item.fav==2) {
								fav_icon = 'fa-star text-danger';
							} else {
								fav_icon = 'fa-star-half-o text-danger';
							}
						}

						if (item.notes && item.notes>0) {
							if (item.notes==2) {
								notes = '<span class="item-notes text-danger"><i class="fa fa-sticky-note"></i></span>';
							} else if (item.notes==1) {
								notes = '<span class="item-notes text-warning"><i class="fa fa-sticky-note"></i></span>';
							}
						}

						edit = '<a href="javascript:void(0);" class="edit-part" data-partid="'+partid+'" data-ln="'+ln+'"><i class="fa fa-pencil"></i></a>';
						vqty = '';
						if (item.vqty!='' || item.qty!='') {
							vqty = '<a href="javascript:void(0);" class="info lk-exporter" title="advertised qty" data-toggle="tooltip" data-placement="right" rel="tooltip"><i class="fa fa-eye"></i> '+
								(item.vqty!='' ? item.vqty : '<i class="fa fa-ban"></i>')+'</span>';
						}

						rows += '\
									<tr class="'+cls+'" data-partid="'+partid+'" id="'+item.id+'-'+ln+'">\
										<td class="col-sm-1 colm-sm-0-5 text-center">\
											<div>\
												<input type="'+input_type+'" name="items['+ln+']['+item.id+']" class="item-check" value="'+item.id+'" tabindex="-1" '+prop+'>\
												<a href="javascript:void(0);" class="fa '+fav_icon+' fav-icon" data-toggle="tooltip" data-placement="right" title="Add/Remove as a Favorite" rel="tooltip"></a>\
											</div>\
										</td>\
										<td class="col-sm-9">\
											<div class="row" style="margin:0">\
												<div class="col-sm-1 nopadding" style="margin:0 3px 0 0">\
													<div class="product-img">\
														<img src="/img/parts/'+item.part+'.jpg" alt="pic" class="img" data-part="'+item.part+'" />\
													</div>\
												</div>\
												<div class="col-sm-10 nopadding product-details" style="font-size:11px; padding-left:5px 10px !important">\
													'+part+aliases+notes+edit+'<br/><span class="info"><small>'+descr+'</small></span>\
												</div>\
											</div>\
										</td>\
										<td class="col-sm-1 text-center">\
											<input type="text" name="item_qtys['+ln+']['+item.id+']" class="form-control input-xs item-qty" value="'+item.qty+'" placeholder="'+item.stk+'" title="Stock Qty" data-toggle="tooltip" data-placement="bottom" rel="tooltip" tabindex="'+(TI++)+'"><br/>\
											'+vqty+'\
										</td>\
										<td class="col-sm-1 colm-sm-1-5">\
											<div class="price">\
												<div class="form-group">\
													<div class="input-group sell infinity">\
														<span class="input-group-btn">\
															<button class="btn btn-default input-xs lock-toggle" type="button" tabindex="-1" data-toggle="tooltip" data-placement="left" title="lock prices together" rel="tooltip"><i class="fa fa-lock"></i></button>\
														</span>\
														<input type="text" name="item_prices['+ln+']['+item.id+']" class="form-control input-xs group-item item-price price-lock-'+ln+'" data-class="price-lock-'+ln+'" value="'+item.price+'" placeholder="0.00"/ tabindex="'+(TI++)+'">\
													</div>\
		                                        </div>\
											</div>\
										</td>\
									</tr>\
						';
					});

		return rows;
	}

/*
	function addItem(item) {
		if (item.trim()=='') { return; }

		var form = $("#results-form");

		form.submit();
	}
*/

	jQuery.fn.marketResults = function(attempt,pricing) {
		if (! pricing && pricing!==0) { var pricing = $(this).data('pricing'); }

		if (! attempt) { var attempt = 0; }
		if (attempt==0) { $(this).html(''); }

		var tr = $(this).closest(".items-row");
		var partids = getCheckedPartids(tr.find(".table-items tr"));

		if (partids=='') { return; }

		if (list_type=='Repair') {
			if ($(this).hasClass('bg-purchases')) {
//				$(this).removeClass('bg-purchases').addClass('bg-outsourced');
//				$(this).data('type','Outsourced');
			} else if ($(this).hasClass('bg-sales')) {
				$(this).removeClass('bg-sales').addClass('bg-repairs');
				$(this).data('type',list_type);
			}
		} else if (list_type=='Sale' || list_type=='Demand') {
			if ($(this).hasClass('bg-outsourced')) {
				$(this).removeClass('bg-outsourced').addClass('bg-purchases');
				$(this).data('type','Purchase');
			} else if ($(this).hasClass('bg-repairs')) {
				$(this).removeClass('bg-repairs').addClass('bg-sales');
				$(this).data('type',list_type);
			} else if ($(this).hasClass('bg-services')) {
				$(this).removeClass('bg-services').addClass('bg-sales');
				$(this).data('type',list_type);
			}
		} else if (list_type=='Service') {
			if ($(this).hasClass('bg-sales')) {
				$(this).removeClass('bg-sales').addClass('bg-services');
				$(this).data('type',list_type);
			}
		}

		var col = $(this);
		var otype = col.data('type');
		var ln = tr.data('ln');
		var max_ln = 10;//don't attempt to search remotes for new downloads beyond this line number

		if (attempt==0) { col.html('<i class="fa fa-circle-o-notch fa-spin"></i>'); }

//		tr.closest("table").find("#row_"+ln+" .market-header").html('<i class="fa fa-circle-o-notch fa-spin"></i>');

		var html,last_date,price,price_ln,cls,sources,src,avg_cost,subtitle,dl,sl,edit_cost,company,comp_cls;
		$.ajax({
			url: 'json/r2.php',
			type: 'get',
			data: { 'list_type': list_type, 'partids': partids, 'type': otype, 'pricing': pricing, 'ln': ln, 'attempt': attempt, 'listid': listid, 'list_label': list_label },
			settings: {async:true},
			error: function(xhr, desc, err) {
				col.html('');
			},
			success: function(json, status) {
				if (json.message && json.message!='') {
					modalAlertShow('Error',json.message,false);
					col.html('');
					return;
				}

				subtitle = '';
				if ((list_type=='Sale' || list_type=='Demand' || list_type=='Service') && otype=='Supply') {
					subtitle += ' <a href="javascript:void(0);" class="lk-download" title="download results" data-toggle="tooltip" data-placement="bottom" rel="tooltip"><i class="fa fa-circle-o-notch fa-spin fa-lg"></i></a>';
				} else if ((list_type=='Sale' || list_type=='Demand') && otype=='Purchase') {
//					subtitle += ' <a href="javascript:void(0);" class="text-primary"><i class="fa fa-share-square text-primary"></i></a>';
				}

				if (otype=='Supply') {
					if (json.range.min>0) {
						subtitle += ' <h5 class="title-data show-hover'+(pricing ? ' bg-primary' : '')+'"><span class="'+(pricing ? 'text-white' : 'text-brown')+'">$'+json.range.min;
						if (json.range.max && json.range.min!=json.range.max) { subtitle += ' - $'+json.range.max; }
						subtitle += '</span> <a href="javascript:void(0);" class="btn-pricing'+(pricing ? ' text-white' : '')+'" title="toggle priced results" data-toggle="tooltip" data-placement="bottom" rel="tooltip"><i class="fa fa-'+(pricing ? 'expand' : 'compress')+'"></i></a></h5>';
					}
				} else if (otype=='Purchase' && json.avg_cost) {
//					avg_cost = json.avg_cost;
//					$("#avg-cost-"+ln).val(avg_cost);
//					$("#avg-cost-"+ln).prop('readonly',true);

					if (json.avg_cost!='') {
						edit_cost = '';
						if (json.edit_cost) {
							edit_cost =  ' <a href="javascript:void(0);" class="modal-avgcost-tag" data-url="json/average_costs.php" '+
								'title="edit" data-toggle="tooltip" data-placement="bottom" rel="tooltip"><i class="fa fa-pencil"></i></a>';
						}

						subtitle += ' \
						<input type="hidden" name="avg_cost['+ln+']" id="avg-cost-'+ln+'" class="form-control input-xs text-center" value="'+json.avg_cost+'" readonly/>\
						<h5 class="title-data show-hover"><span class="text-brown">$'+json.avg_cost+'</span> avg cost'+edit_cost+'</h5>\
						';
					}
				} else if (otype=='Sale') {
					sl = json.sl;
					if (! sl) { sl = 'n/a'; }
					subtitle += '<h5 class="title-data"><span class="text-brown">'+sl+'</span>'+(json.sl!='' ? ' day' : '')+' shelflife</h5>';
				} else if (otype=='Demand' && json.pr) {
					subtitle += '<h5 class="title-data"><span class="text-brown">'+json.pr+'</span> usage level</h5>';
				}

				html = '\
				<div class="col-results">\
					<a href="javascript:void(0);" class="btn btn-default btn-xs view-results" data-target="marketModal" title="'+otype+' Results" data-toggle="tooltip" data-placement="top" rel="tooltip" data-title="'+otype+' Results" data-type="'+otype+'">\
						'+otype+' <i class="fa fa-window-restore"></i>\
					</a>'+subtitle+'\
				';

				if (json.results && json.results.length>0) {
					last_date = '';
					$.each(json.results, function(rowkey, row) {
						if (row.date!=last_date) {
							html += '<'+row.format+'>'+row.date+'</'+row.format+'>';

							last_date = row.date;
						}

						cls = '';
						if (row.format=='h4') { cls = ' info'; }
						else if (row.format=='h6') { cls = ' primary'; }

						if (row.ref_1==listid && row.ref_type==list_label) { cls += ' h5'; }

						if (row.status && (row.status=='Void' || row.qty==0)) { cls += ' strikeout'; }

						if (! row.cid) {
							// required for spacing
//							html += '<div class="item-result '+cls+'"> &nbsp; </div>';
							return;
						}

						sources = '';
						$.each(row.sources, function (source, url) {
							src = '';
							if (source=='email') { src = '<i class="fa fa-email"></i>'; }
							else if (source=='ar') { src = '<i class="fa fa-cloud-download"></i>'; }
							else if (source!='import') { src = '<img src="img/'+source.toLowerCase()+'.png" class="bot-icon" />'; }

							sources += ' '+src;
						});

						price = '';
						price_ln = '';
						if (row.price>0) {
							if (row.past_price=='1') { price = '<span class="info"> $'+row.price+'</span>'; }
							else { price = ' $'+row.price; }
						}
						if (otype=='Sale' || otype=='Purchase' || otype=='Service' || otype=='Repair' || otype=='Outsourced') {
							if (row.order_number!='') {
								price_ln = ' <a href="javascript:void(0);" class="lk-open" data-type="'+otype+'" data-order="'+row.order_number+'"><i class="fa fa-arrow-right"></i></a>';
							} else if (otype=='Purchase') {
								price_ln = ' <a href="javascript:void(0);" class="lk-open" data-type="purchase_requests" data-order=""><i class="fa fa-arrow-right"></i></a>';
							}
						} else if (row.order_number) {
							price_ln = ' <a href="javascript:void(0);" class="lk-open" data-type="manage_quote" data-order="'+row.order_number+'"><i class="fa fa-arrow-right"></i></a> '+
										'<a href="market.php?metaid='+row.order_number+'&searchid='+row.searchid+'&ln='+row.ln+'" target="_new"><i class="fa fa-pencil"></i></a>';
						}

						company = '';
						comp_cls = '';
						if (json.companies && json.companies[row.cid]) { company = json.companies[row.cid]; }
						if (companyid==row.cid) {
							comp_cls = ' company-text text-primary';
						}

						html += '<div class="show-hover'+cls+'">'+
							row.qty+' <div class="market-company'+comp_cls+'" data-cid="'+row.cid+'"><a href="company.php?companyid='+row.cid+'" target="_companies"><i class="fa fa-building"></i></a> '+company+'</div>'+sources+price+price_ln+
							'</div>';
					});
				}

/*
				if (otype=='Purchase') {
					sl = json.sl;
					if (! sl) { sl = ''; }
					col.closest(".items-row").find(".shelflife").html(sl+' '+(json.sl>0 ? 'day shelflife' : ''));
				}
*/

				html += '</div>';
				if (col.hasClass('bg-purchases')) {
					html += '<button class="btn btn-default btn-sm text-primary purchase-request" type="button" style="width:100%; position:absolute; bottom:0; left:0"><i class="fa fa-share-square"></i> Request</button>';
				}
				col.html(html);

				if (col.hasClass('bg-market')) {
					if ((list_type=='Sale' || list_type=='Demand' || list_type=='Service') && (ln<=max_ln || attempt>0)) {
						if (! json.done && attempt==0) {
							setTimeout("$('#"+col.prop('id')+"').marketResults("+(attempt+1)+")",1000);
						} else if (json.done==1 && attempt>0) {

							tr.find(".lk-download").html('<i class="fa fa-download fa-lg"></i>');
//							tr.closest("table").find("#row_"+ln+" .market-header").html('market');
						}
					} else if (json.done==1 || ln>max_ln) {
						tr.find(".lk-download").html('<i class="fa fa-download fa-lg"></i>');
//						tr.closest("table").find("#row_"+ln+" .market-header").html('market');
					}
				}

				// alert the user when there are errors with any/all remotes by unhiding alert buttons
				if (json.err && json.err.length>0) {
					$.each(json.err, function(i, remote) {
						$("#remote-"+remote).removeClass('hidden');
					});
				}
			},
		});
	};
