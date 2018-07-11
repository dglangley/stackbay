	$(document).ready(function() {
		if (typeof companyid === 'undefined' || typeof companyid === 'object') { companyid = 0; }
		if (typeof contactid === 'undefined' || typeof contactid === 'object') { contactid = 0; }
		if (typeof listid === 'undefined' || typeof listid === 'object') { listid = 0; }
		if (typeof list_type === 'undefined' || typeof list_type === 'object') { list_type = ''; }
		if (typeof PR === 'undefined' || typeof PR === 'object') { PR = false; }
		if (typeof salesMin === 'undefined' || typeof salesMin === 'object') { salesMin = false; }
		if (typeof favorites === 'undefined' || typeof favorites === 'object') { favorites = false; }
		if (typeof startDate === 'undefined' || typeof startDate === 'object') { startDate = ''; }
		if (typeof endDate === 'undefined' || typeof endDate === 'object') { endDate = ''; }
		if (typeof demandMin === 'undefined' || typeof demandMin === 'object') { demandMin = false; }
		if (typeof demandMax === 'undefined' || typeof demandMin === 'object') { demandMax = false; }
		if (typeof line_number === 'undefined' || typeof line_number === 'object') { line_number = false; }
		if (typeof searchid === 'undefined' || typeof searchid === 'object') { searchid = false; }

		category = setCategory();
		pricing_default = 0;
		if (category=='Service') { pricing_default = 1; }

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

		$(".save-menu li a").on('click', function() {
			var li = $(this);

			// update handler as List/WTB
			var handler = $(this).data('handler');
			$("#handler").val(handler);

			// reset Active class to selected option
			$(this).closest(".dropdown-menu").find("li a").each(function() {
				if ($(this).data('handler')==handler) {
					$(".btn-save").addClass($(this).data('btn'));
//					$(this).removeClass('text-white').removeClass('btn-success').addClass('text-white').addClass('btn-success');
				} else {
					$(".btn-save").removeClass($(this).data('btn'));
//					$(this).removeClass('text-white').removeClass('btn-success');
				}
			});

			$(this).closest(".btn-group").find(".btn-save").html($(this).html());
		});

		$(".btn-save").on('click', function() {
			var form = $("#results-form");

			form.submit();
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
		});

		// re-initialize event handler for tooltips
		$('body').tooltip({ selector: '[rel=tooltip]' });

		$('body').on('change', '.group-item', function() {
			var master_lock = $(this);
			var lock_class = $(this).data('class');

			// confirm padlock isn't UNlocked, which would make this a unique input change
			var isLocked = false;
			var locks = master_lock.closest(".input-group").find(".fa");
			locks.each(function() {
				if ($(this).hasClass("fa-lock")) { isLocked = true; }
			});
			// a master lock is allowed to NOT have an associated padlock, but any case where it DOES, it needs to be locked
			if (locks.length>0 && isLocked===false) { return; }

			var control,lock;
			$("."+lock_class).not(this).each(function() {
				control = $(this);

				lock = control.closest(".input-group").find(".fa-lock").each(function() {
					control.val(master_lock.val().trim());
				});

				if (control.hasClass('response-qty')) { control.updateRowTotal(); }
			});
		});
		$('body').on('click', '.lock-toggle', function() {
			$(this).find(".fa").each(function() {
				if ($(this).hasClass('fa-lock')) { $(this).removeClass('fa-lock').addClass('fa-unlock'); }
				else { $(this).removeClass('fa-unlock').addClass('fa-lock'); }
			});
		});

		$('body').on('change','.list-price',function() {
			var row = $(this).closest(".header-row");
			var ln = row.data('ln');

			var items_row = $("#items_"+ln);
			var rprice = items_row.find(".response-price").val().trim().replace(',','');
			var lprice = $(this).val().trim();
			var markup = items_row.find(".cost-markup").val().trim().replace(',','');
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
			items_row.find(".cost-markup").val(markup);
		});
		$('body').on('change','.cost-markup',function() {
			var row = $(this).closest(".items-row");
			var ln = row.data('ln');

			var markup = $(this).val().trim().replace(',','');
			var header_row = $("#row_"+ln);
			var lprice = header_row.find(".list-price").val().trim().replace(',','');
			var rprice = '';
			if (lprice>0 && markup>0) {
				var amt = parseFloat(lprice)+parseFloat(lprice*(markup/100));//row.find(".response-price").val().trim();
				rprice = amt.formatMoney(2);
			}
			var price_field = row.find(".response-price");
			price_field.val(rprice);
			price_field.updateRowTotal();
		});

		$('body').on('change','.response-price',function() {
			var row = $(this).closest(".items-row");
			var ln = row.data('ln');

			var rprice = $(this).val().trim().replace(',','');
			var header_row = $("#row_"+ln);
			var lprice = header_row.find(".list-price").val().trim().replace(',','');

			var markup = '';
			if (lprice!='' && lprice>0) {
				var pct = 100*((rprice/lprice)-1);
				markup = pct.formatMoney(2);
			}
			row.find(".cost-markup").val(markup);
		});
/*
		$('body').on('change','.iqty',function() {
			var chk = $(this).closest('tr').find('.item-check').prop('checked');
			if (! chk) { return; }

			var qty = $(this).val();
alert(qty);
		});
*/

		$('.slider-frame input[type=radio]').on('change',function() {
			$(this).updateItemFields();
		});

		$(".btn-category").on('click',function() {
			category = setCategory($(this).text());
			$("#category").val(category);

			$(".items-row").each(function() {
				updateResults($(this));
			});
		});

		$("body").on('click','.checkItems',function() {
			var chk = $(this);
			var items_row = $(this).closest("tr").next();

			items_row.find(".table-items .item-check:checkbox").each(function() {
				$(this).prop('checked', chk.prop('checked'));
				$(this).setRow();
			});

			updateResults(items_row);
		});


		$("body").on('click','.item-check:checkbox',function(e) {
			if ($(this).is('[readonly]')) {
				e.preventDefault();
				modalAlertShow("Sorry, not sorry","This list is readonly and cannot be edited.");
				return false;
			}
			$(this).setRow();
			updateResults($(this).closest(".items-row"));
		});

		$("body").on('click','.btn-pricing',function() {
//			$(this).toggleClass('btn-primary active','btn-default').blur();//.toggleClass('btn-primary','btn-default').blur();

			var ln = $(this).closest("tr").data('ln');
			var pricing = $("#market_"+ln).data('pricing');
			if (pricing==1) { pricing = 0; } else { pricing = 1; }
			if (pricing) {
				$(this).removeClass('btn-default').addClass('btn-primary active').blur();
			} else {
				$(this).removeClass('btn-primary active').addClass('btn-default').blur();
			}
			$("#market_"+ln).data('pricing',pricing);

			var ln = $(this).closest(".header-row").data('ln');
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

		$("body").on('click','.btn-response',function() {
			$(this).closest('.btn-group').find('.btn').each(function() {
				$(this).removeClass('active');
			});
			$(this).addClass('active');

			var row = $(this).closest(".header-row");
			var ln = row.data('ln');

			var type = $(this).data('type');
			$("#row_"+ln+", #items_"+ln).find("input, textarea, select").each(function() {
				if (type=='disable' || (type=='noreply' && ! $(this).is(':checkbox') && ! $(this).hasClass('list-qty'))) {
					$(this).attr('disabled',true);
					if (type=='disable') {
						$("#items_"+ln).addClass('hidden');
					} else {
						$("#items_"+ln).removeClass('hidden');
					}
				} else {
					$(this).attr('disabled',false);
					$("#items_"+ln).removeClass('hidden');
				}
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
							company = '<a href="profile.php?companyid='+row.cid+'" target="_new"><i class="fa fa-building"></i></a> '+row.company;

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
		var row = $(this).closest(".response-calc");
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
		var current_amt = parseFloat(t.html().replace('$ ','').replace(',',''));
		t.html('$ '+total.formatMoney(2));

		LIST_TOTAL += total-current_amt;

		$("#list_total").html('$ '+LIST_TOTAL.formatMoney(2));
	};

	jQuery.fn.updateItemFields = function() {
		var e = $(this);
		if (e.prop('checked')===false) { return; }

		if (e.val()=='Sell'){ // && $(this).hasClass('hidden')) {
//			$('.items-row').find('.table-items input[type=text]').removeClass('hidden');
			$('.items-row').find('.table-items .sell').removeClass('hidden');
			$('.items-row').find('.table-items input[type=text]').attr('disabled',false);
		} else {
//			$('.items-row').find('.table-items input[type=text]').addClass('hidden');
			$('.items-row').find('.table-items .sell').addClass('hidden');
			$('.items-row').find('.table-items input[type=text]').attr('disabled',true);
		}
	};

	jQuery.fn.partResults = function(search,replaceNode) {
		$('#loader').show();

		if (! search) {
			var search = '';
		}
		if (! replaceNode && replaceNode!==0) { var replaceNode = false; }
		var filter_LN = false;

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

		var rows,header_row,items_row,n,s,clonedChart,rspan,range,avg_cost,shelflife,dis,add_lk,merge_lk,ph;

		$.ajax({
			url: '/json/market.php',
			type: 'get',
			data: {
				'listid': listid,
				'list_type': list_type,
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
			},
			settings: {async:true},
			error: function(xhr, desc, err) {
				$('#loader').hide();
				modalAlertShow('Error',desc,false);
			},
			success: function(json, status) {
				$('#loader').hide();
				if (json.message && json.message!='') {
//					modalAlertShow('Error',json.message,false);
					$("#results").html('<tr><td class="text-center">'+json.message+'</td></tr>');

					return;
				}

				$.each(json.results, function(ln, row) {
					n = Object.keys(row.results).length;//row.results.length;
					s = '';
					if (n!=1) { s = 's'; }

					add_lk = '';
					merge_lk = '';
					// if (n==0) {
					// 	if (row.search!='') {
					// 		add_lk = '<a href="javascript:void(0);" class="add-part" data-partid="" data-ln="'+ln+'" title="add new part" data-toggle="tooltip" data-placement="bottom" rel="tooltip"><i class="fa fa-plus"></i></a>';
					// 	}
					// } else {
					// 	merge_lk = '<a href="javascript:void(0);" class="merge-parts" title="merge selected parts" data-toggle="tooltip" data-placement="bottom" rel="tooltip"><i class="fa fa-chain"></i></a>';
					// }

					rspan = 2;//n+1;

					range = '$';
					if (row.range.min>0) {
						range += row.range.min;
						if (row.range.max && row.range.min!=row.range.max) { range += ' - $'+row.range.max; }
					}


					shelflife = '<i class="fa fa-qrcode"></i>';
					if (row.shelflife) { shelflife += ' '+row.shelflife; }

					buttons = '<div class="btn-group">\
                            <button class="btn btn-xs btn-default btn-response left" data-type="disable" type="button" title="disable & collapse" data-toggle="tooltip" data-placement="top" rel="tooltip"><i class="fa fa-close"></i></button>\
                            <button class="btn btn-xs btn-default btn-response middle" data-type="noreply" type="button" title="save, no reply" data-toggle="tooltip" data-placement="top" rel="tooltip"><i class="fa fa-square-o"></i></button>\
                            <button class="btn btn-xs btn-default btn-response right active" data-type="reply" type="button" title="save & reply" data-toggle="tooltip" data-placement="top" rel="tooltip"><i class="fa fa-check-square-o"></i></button>\
                        </div>';

					rows = buildItemRows(row.results,ln);
					ph = row.search;
					if (ph=='') { ph = 'Add item...'; }

					items_row = '\
						<div id="items_'+ln+'" class="items-row" data-ln="'+ln+'">\
							<div class="mh">\
							<table class="table table-condensed table-striped table-hover table-items">\
								'+rows+'\
							</table>\
							</div>\
						</div>\
					';

					$('#parts_summary').append(items_row);

					// $("#items_"+ln).find(".response-price").updateRowTotal();
				});

				var market_row = '\
					<div class="bg-market" data-type="Supply" data-pricing="'+pricing_default+'" id="market_0"></div>\
				';
				var supply_row = '\
					<div class="bg-purchases" data-type="Purchase" data-pricing="1"></div>\
				';
				var sales_row = '\
					<div class="bg-sales" data-type="Sale" data-pricing="1"></div>\
				';
				var demand_row = '\
					<div class="bg-demand" data-type="Demand" data-pricing="0"></div>\
				';

				$('#market_summary').append(market_row);
				$('#purchase_summary').append(supply_row);
				$('#sales_summary').append(sales_row);
				$('#demand_summary').append(demand_row);

				updateResults($('#market_summary'));
				updateResults($('#purchase_summary'));
				updateResults($('#sales_summary'));
				updateResults($('#demand_summary'));
			},
			complete: function(result) {
				table.find(".select2").select2();
				$('.slider-frame input[type=radio]:checked').each(function() { $(this).updateItemFields(); });
			},
		});
	};/*end partResults*/

	function updateResults(row) {
		row.find(".bg-market").each(function() { $(this).marketResults(0); });
		row.find(".bg-purchases").each(function() { $(this).marketResults(0); });
		row.find(".bg-outsourced").each(function() { $(this).marketResults(0); });
		row.find(".bg-sales").each(function() { $(this).marketResults(0); });
		row.find(".bg-services").each(function() { $(this).marketResults(0); });
		row.find(".bg-repairs").each(function() { $(this).marketResults(0); });
		row.find(".bg-demand").each(function() { $(this).marketResults(0); });
	}
	function buildItemRows(results,ln) {
		var rows = '';
//					partids = '';

		var notes,aliases,alias_str,edit,descr,part,mpart,prop,cls,item_class,vqty;

		$.each(results, function(pid, item) {
			item_class = 'sub';
			prop = '';
			//if (item.class=='primary') { prop = ' checked'; }
			if (item.prop.checked) {
				prop += ' checked';
				item_class = 'primary';
			}

			if (item.prop.disabled) { prop += ' disabled'; }
			if (item.prop.readonly) { prop += ' readonly'; }

			cls = 'product-row row-'+item.id+' '+item_class;
			if (item.qty>0) { cls += ' in-stock'; }

			partid = item.id;
			part = '<span class="part_text">'+item.primary_part;
			if (item.heci) { part += ' '+item.heci; }
			part += '</span>';

			aliases = '';
			alias_str = '';

			descr = '';
			if (item.manf) descr += item.manf;
			if (item.system) { if (descr!='') { descr += ' '; } descr += item.system; }
			if (item.description) { if (descr!='') { descr += ' '; } descr += item.description; }
			$.each(item.aliases, function(a, alias) {
//							if (alias_str!='') alias_str += ' ';
				mpart = item.part.replace(' '+alias,'');
				alias_str += '<span class="alias">'+alias+'<a href="javascript:void(0);" data-part="'+mpart+'" class="save-part"><i class="fa fa-times-circle text-danger"></i></a></span>';
			});
			if (alias_str!='') { aliases = ' &nbsp; <div class="show-hover"><small>'+alias_str+'</small></div>'; }

			notes = '<span class="item-notes"><i class="fa fa-sticky-note-o"></i></span>';
/*
			$.each(item.notes, function(n2, note) {
			});
*/
			if (item.notes_flag) {
				notes = item.notes_flag;
			}

			edit = '<a href="javascript:void(0);" class="edit-part" data-partid="'+partid+'" data-ln="'+ln+'"><i class="fa fa-pencil"></i></a>';
			vqty = '';

			if (item.vqty>0 || item.qty>0) { vqty = '<span class="info"><i class="fa fa-eye"></i> '+item.vqty+'</span>'; }

			// <div class="col-sm-1 text-center">\
			// 			<input type="text" name="item_qtys['+ln+']['+item.id+']" class="form-control input-xs" value="'+item.qty+'" placeholder="'+item.stk+'" title="Stock Qty" data-toggle="tooltip" data-placement="bottom" rel="tooltip"><br/>\
			// 			'+vqty+'\
			// 		</div>\

			if (item.prop.checked) {
				rows += '\
					<BR>\
					<div class="'+cls+' row" data-partid="'+partid+'" id="'+item.id+'-'+ln+'" style="margin: 0;">\
						<div class="col-xs-2">\
							<div class="product-img">\
								<img src="/img/parts/'+item.primary_part+'.jpg" alt="pic" class="img" data-part="'+item.primary_part+'" />\
							</div>\
						</div>\
						<div class="col-xs-10 product-details">\
							'+part+aliases+'<br/><span class="info"><small>'+descr+'</small></span>\
						</div>\
					</div>\
				';
			}
		});

		return rows;
	}

	jQuery.fn.marketResults = function(attempt,pricing) {
		if (! pricing && pricing!==0) { var pricing = $(this).data('pricing'); }

		if (! attempt) { var attempt = 0; }
		if (attempt==0) { $(this).html(''); }

		var tr = $("#parts_summary");
		
		// var partids = getCheckedPartids(tr.find(".product-row"));
		var partids = '';

		$(tr.find(".product-row")).each(function(i, obj) {
			if (partids!='') { partids += ','; }
			partids += $(this).data("partid");

			// alert($(this).data("partid"));
		});

		// alert(partids);

		if (partids=='') { return; }

		if (category=='Repair') {
			if ($(this).hasClass('bg-purchases')) {
				$(this).removeClass('bg-purchases').addClass('bg-outsourced');
				$(this).data('type','Outsourced');
			} else if ($(this).hasClass('bg-sales')) {
				$(this).removeClass('bg-sales').addClass('bg-repairs');
				$(this).data('type',category);
			}
		} else if (category=='Sale') {
			if ($(this).hasClass('bg-outsourced')) {
				$(this).removeClass('bg-outsourced').addClass('bg-purchases');
				$(this).data('type','Purchase');
			} else if ($(this).hasClass('bg-repairs')) {
				$(this).removeClass('bg-repairs').addClass('bg-sales');
				$(this).data('type',category);
			} else if ($(this).hasClass('bg-services')) {
				$(this).removeClass('bg-services').addClass('bg-sales');
				$(this).data('type',category);
			}
		} else if (category=='Service') {
			if ($(this).hasClass('bg-sales')) {
				$(this).removeClass('bg-sales').addClass('bg-services');
				$(this).data('type',category);
			}
		}

		var col = $(this);
		var otype = col.data('type');
		var ln = tr.data('ln');
		var max_ln = 2;//don't attempt to search remotes for new downloads beyond this line number

		if (attempt==0) { col.html('<i class="fa fa-circle-o-notch fa-spin"></i>'); }

//		tr.closest("table").find("#row_"+ln+" .market-header").html('<i class="fa fa-circle-o-notch fa-spin"></i>');

		var html,last_date,price,price_ln,cls,sources,src,avg_cost;
		$.ajax({
			url: 'json/results.php',
			type: 'get',
			data: { 'category': category, 'partids': partids, 'type': otype, 'pricing': pricing, 'ln': ln, 'attempt': attempt },
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

				if (otype=='Purchase' && json.avg_cost) {
					avg_cost = '';
					if (json.avg_cost) {
						avg_cost = json.avg_cost;
						$("#avg-cost-"+ln).val(avg_cost);
//						$("#avg-cost-"+ln).prop('readonly',true);
					}
				}

				dwnld = '';
				if ((category=='Sale' || category=='Service') && otype=='Supply') {
					dwnld = ' <a href="javascript:void(0);" class="lk-download"><i class="fa fa-circle-o-notch fa-spin"></i></a>';
				} else if (category=='Sale' && otype=='Purchase') {
//					dwnld = ' <a href="javascript:void(0);" class="text-primary"><i class="fa fa-share-square text-primary"></i></a>';
				}

				html = '\
				<div class="col-results">\
					<a href="javascript:void(0);" class="market-title view-results" data-target="marketModal" title="'+otype+' Results" data-toggle="tooltip" data-placement="top" rel="tooltip" data-title="'+otype+' Results" data-type="'+otype+'">\
						'+otype+' <i class="fa fa-window-restore"></i>'+dwnld+'\
					</a>\
				';

				if (json.results && json.results.length>0) {
					last_date = '';
					$.each(json.results, function(rowkey, row) {

						if (row.date!=last_date && (last_date == '')) { //  || (otype=='Sale' || otype=='Purchase')
							html += '<'+row.format+'>'+row.date+'</'+row.format+'>';

							last_date = row.date;
						} else if (row.date!=last_date ) { // && otype!='Sale' && otype!='Purchase'
							return;
						}

						cls = '';
						if (row.format=='h4') { cls = ' info'; }
						else if (row.format=='h6') { cls = ' primary'; }

						if (row.status && (row.status=='Void' || row.qty==0)) { cls += ' strikeout'; }

						if (! row.companyid) {
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
								price_ln = ' <a href="order.php?order_type='+otype+'&order_number='+row.order_number+'" target="_new"><i class="fa fa-arrow-right"></i></a>';
							} else if (otype=='Purchase') {
								price_ln = ' <a href="purchase_requests.php" target="_new"><i class="fa fa-arrow-right"></i></a>';
							}
						} else if (row.order_number) {
							price_ln = ' <a href="manage_quote.php?metaid='+row.order_number+'"><i class="fa fa-arrow-right"></i></a> '+
										'<a href="market.php?metaid='+row.order_number+'&searchid='+row.searchid+'&ln='+row.ln+'"><i class="fa fa-pencil"></i></a>';
						}
						html += '<div class="show-hover'+cls+'">'+
							row.qty+' <div class="market-company"><a href="profile.php?companyid='+row.companyid+'" target="_new"><i class="fa fa-building"></i></a> '+row.name+'</div>'+sources+price+price_ln+
							'</div>';
					});
				}

				html += '</div>';
				
				col.html(html);

				if (col.hasClass('bg-market')) {
					if ((category=='Sale' || category=='Service') && (ln<=max_ln || attempt>0)) {
						if (! json.done && attempt==0) {
							setTimeout("$('#"+col.prop('id')+"').marketResults("+(attempt+1)+")",1000);
						} else if (json.done==1 && attempt>0) {

							tr.find(".lk-download").html('<i class="fa fa-download"></i>');
//							tr.closest("table").find("#row_"+ln+" .market-header").html('market');
						}
					} else if (json.done==1 || ln>max_ln) {
						tr.find(".lk-download").html('<i class="fa fa-download"></i>');
//						tr.closest("table").find("#row_"+ln+" .market-header").html('market');
					}
				}

				// alert the user when there are errors with any/all remotes by unhiding alert buttons
				$.each(json.err, function(i, remote) {
					$("#remote-"+remote).removeClass('hidden');
				});
			},
		});
	};
