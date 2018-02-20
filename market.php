<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getField.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/logSearch.php';

	$companyid = 0;
	if (isset($_REQUEST['companyid']) AND is_numeric($_REQUEST['companyid'])) { $companyid = $_REQUEST['companyid']; }
	$contactid = 0;
	if (isset($_REQUEST['contactid']) AND is_numeric($_REQUEST['contactid'])) { $contactid = $_REQUEST['contactid']; }

	//default field handling variables
	$col_search = 1;
	if (isset($_REQUEST['search_field'])) { $col_search = $_REQUEST['search_field']; }
	$sfe = false;//search from end
	$col_qty = 2;
	if (isset($_REQUEST['qty_field']) AND $_REQUEST['qty_field']<>'') { $col_qty = $_REQUEST['qty_field']; }
	$qfe = false;//qty from end
	$col_price = false;
	if (isset($_REQUEST['price_field']) AND $_REQUEST['price_field']<>'') { $col_price = $_REQUEST['price_field']; }
	$pfe = false;//price from end

	$slid = 0;
	$lines = array();
	if (isset($_REQUEST['s']) AND trim($_REQUEST['s'])) {
		$lines = array(trim($_REQUEST['s']));

		$slid = logSearch($_REQUEST['s'],$col_search,$sfe,$col_qty,$qfe,$col_price,$pfe);
	} else if (isset($_REQUEST['s2']) AND trim($_REQUEST['s2'])) {
		$lines = explode(chr(10),$_REQUEST['s2']);

		$slid = logSearch($_REQUEST['s2'],$col_search,$sfe,$col_qty,$qfe,$col_price,$pfe);
	} else if (isset($_REQUEST['slid'])) {
		$slid = $_REQUEST['slid'];

		$query = "SELECT * FROM search_lists WHERE id = '".res($slid)."'; ";
		$result = qedb($query);
		$list = qfetch($result,'Could not find list');

		$lines = explode(chr(10),$list['search_text']);
		$fields = $list['fields'];
		$col_search = substr($fields,0,1);
		$col_qty = substr($fields,1,1);
		$col_price = substr($fields,2,1);
		if (strlen($list['fields'])>3) {
			$sfe = substr($fields,3,1);
			$qfe = substr($fields,4,1);
			$pfe = substr($fields,5,1);
		}
	}

	foreach ($lines as $ln => $line) {
		$F = preg_split('/[[:space:]]+/',$line);

		$search = getField($F,$col_search,$sfe);
		if ($search===false) { continue; }

		$qty = getField($F,$col_qty,$qfe);
		if (! $qty OR ! is_numeric($qty)) { $qty = 1; }

		$price = getField($F,$col_price,$pfe);
		if ($price===false) { $price = ''; }
	}

	$chartW = 180;
	$chartH = 120;

	$category = "Sale";

	$TITLE = 'Market';
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo $TITLE; ?></title>
	<?php
		/*** includes all required css includes ***/
		include_once 'inc/scripts.php';
	?>

	<!-- any page-specific customizations -->
	<style type="text/css">
		.table td {
			vertical-align:top !important;
		}
		.header-row input[type=text] {
			font-weight:bold;
		}
		.input-camo {
			font-weight:bold;
			border:0px;
			background:none;
			color:#666;
		}
		.search {
			float:left;
			display:inline-block;
		}
		.price {
			float:right;
			display:inline-block;
		}
		.list-qty {
			width:35px;
		}
		.input-group .form-control.list-price {
			width:70px;
		}
		.list-qty,
		.list-price {
			border:0px !important;
			background-color:#e1e1e1;
		}
		.product-search,
		.list-qty,
		.list-price {
			font-size:14px !important;
		}
		.form-couple .input-group:first-child {
			width:60%;
		}
		.form-couple .input-group:last-child {
			width:40%;
		}
		.form-couple .text-muted {
			color:#999999 !important;
		}
		.item-notes {
			margin-left:10px;
		}
		.row-total {
			display:inline-block;
			width:70px;
			border:1px solid gray;
			vertical-align:top;
			margin-top:1px;
		}
		.row-total h5 {
			padding:3px;
		}

		.col-chart {
			width:<?=$chartW;?>px;
/*
			height:<?=$chartW;?>px;
*/
		}


		.header-row {
			border-top:1px solid #ccc;
		}
		.header-row > td {
			padding-top:2px;
		}
		.mh,
		.col-results {
			height:100%;
			min-height:140px;
			max-height:190px;
			overflow:auto;
		}
		.items-row {
			margin-bottom:80px;
			border-bottom:1px solid #ccc;
		}
		.items-row > td {
			padding-top:4px;
		}
		tr.sub td,
		tr.sub td * {
			color:#ccc;
		}
		.col-results {
			line-height:1.3;
			font-size:10px;
			position:relative;
		}
		#pad-wrapper .col-results h4 {
			color:#999;
		}
		.col-results h5 {
			font-weight:bold;
		}
		.col-results h4,
		.col-results h5,
		.col-results h6 {
			font-size:11px;
		}
		.col-results h4,
		.col-results h5,
		.col-results h6 {
			margin-top:0px;
			margin-bottom:0px;
		}
		.col-results h4:not(:first-child),
		.col-results h5:not(:first-child),
		.col-results h6:not(:first-child) {
			margin-top:1px;
			padding-top:0px;
		}
		.col-results .market-company {
			display:inline-block;
			min-width:70px;
			max-width:105px;
			padding-left:2px;
			padding-right:2px;
			vertical-align:bottom;
		}
		.show-hover {
			display:inline-block;
		}
		.show-hover a {
			padding-left:1px;
			padding-right:3px;
		}
		.show-hover a .fa {
			visibility:hidden;
		}
		.show-hover:hover a .fa {
			visibility:visible;
		}
		.col-cost .input-group {
			width: 100px;
			margin-left: auto;
			margin-right: auto;
		}
		.bg-market, .bg-demand {
			background-color:white;
		}
		.bg-purchases, .bg-sales {
			padding-left:4px;
			padding-right:4px;
		}
		.form-control[disabled], .form-control[readonly],
		input[disabled], input[readonly] {
			color:#333333;
			background-color: white;
		}
		input[type="radio"], input[type="checkbox"] {
			margin-top:0px;
		}
		.bot-icon {
			height:8px;
			margin-left:-1px;
			margin-right:-1px;
		}
		.slider-frame.success {
			background-color:#5cb85c;
		}
		#filter_bar .col-company .select2 {
			float:right;
		}
	</style>
</head>
<body>

<?php include_once 'inc/navbar.php'; ?>

<form class="form-inline" method="POST" action="save-market.php" id="results-form">
<input type="hidden" name="slid" value="<?=$slid;?>">
<input type="hidden" name="category" id="category" value="<?=$category;?>">

<!-- FILTER BAR -->
<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px; max-height:60px;">

	<div class="row" style="padding:8px">
		<div class="col-sm-1">
		</div>
		<div class="col-sm-1">
		</div>
		<div class="col-sm-1">
		</div>
		<div class="col-sm-1">
		</div>
		<div class="col-sm-1">
			<div class="btn-group" style="right:0; top:0; position:absolute">
				<button class="btn btn-xs btn-default btn-category left active" type="button" title="equipment sales" data-toggle="tooltip" data-placement="bottom" rel="tooltip">Sale</button>
				<button class="btn btn-xs btn-default btn-category right" type="button" title="equipment repair" data-toggle="tooltip" data-placement="bottom" rel="tooltip">Repair</button>
			</div>
		</div>
		<div class="col-sm-2 text-center">
			<h2 class="minimal"><?php echo $TITLE; ?></h2>
			<span class="info"></span>
		</div>
		<div class="col-sm-1">
			<div class="slider-frame" style="left:0; top:0; position:absolute">
				<!-- include radio's inside slider-frame to set appropriate actions to them -->
				<input class="hidden" value="Buy" type="radio" name="mode">
				<input class="hidden" value="Sell" type="radio" name="mode" checked>
				<span data-off-text="Buy" data-on-text="Sell" class="slider-button slider-mode" id="mode-slider">Sell</span>
			</div>
		</div>
		<div class="col-sm-2 col-company">
			<select name="companyid" size="1" class="form-control company-selector">
			</select>
		</div>
		<div class="col-sm-1">
			<select name="contactid" size="1" class="form-control contact-selector" data-placeholder="- Contacts -">
			</select>
		</div>
		<div class="col-sm-1 text-right">
			<button type="button" class="btn btn-md btn-success btn-save"><i class="fa fa-save"></i> Save</button>
		</div>
	</div>

</div>

<div id="pad-wrapper">

	<div class="table-responsive">
		<table class="table table-condensed" id="results">
		</table>
	</div>

</div><!-- pad-wrapper -->

</form>

<?php include_once 'modal/image.php'; ?>
<?php include_once 'modal/results.php'; ?>
<?php include_once 'modal/notes.php'; ?>
<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<div class="hidden">
<canvas id="mChart" width="<?=$chartW;?>" height="<?=$chartH;?>"></canvas>
</div>

<script type="text/javascript">
	$(document).ready(function() {
		companyid = '<?=$companyid;?>';
		contactid = '<?=$contactid;?>';
		category = setCategory();
		$(".btn-category").on('click',function() {
			category = setCategory($(this).text());
			$("#category").val(category);

			$(".items-row").each(function() {
				updateResults($(this));
			});
		});

		$('#loader-message').html('Gathering market information...');
		$('#loader').show();

		$("#results").partResults('<?=$slid;?>');

		// re-initialize event handler for tooltips
		$('body').tooltip({ selector: '[rel=tooltip]' });

		$("body").on('click','.checkItems',function() {
			var chk = $(this);
			var items_row = $(this).closest("tr").next();

			items_row.find(".table-items .item-check:checkbox").each(function() {
				$(this).prop('checked', chk.prop('checked'));
				$(this).setRow();
			});

			updateResults(items_row);
		});

		$("body").on('click','.item-check:checkbox',function() {
			$(this).setRow();
			updateResults($(this).closest(".items-row"));
		});

		$(".btn-save").on('click',function() {
			var form = $("#results-form");

			form.submit();
		});

		$("select[name='companyid']").on('change', function() {
			companyid = $(this).val();
		});

		$("body").on('change','.response-calc input',function() {
			var row = $(this).closest(".response-calc");
			var qty = row.find(".response-qty").val();
			var price = row.find(".response-price").val();
			var total = qty*price;

			row.find(".row-total h5").html('$'+total.formatMoney(2));
		});

		$("body").on('click','.lk-download',function() {
			$(this).html('<i class="fa fa-circle-o-notch fa-spin"></i>');
			$(this).blur();
			$(this).closest(".bg-market").marketResults(2);
		});

		$("body").on('click','.edit-part',function() {
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
	});

	jQuery.fn.setRow = function() {
		if ($(this).prop('checked')===true) {
			$(this).closest("tr").removeClass('sub').addClass('primary');
		} else {
			$(this).closest("tr").removeClass('primary').addClass('sub');
		}
	};

	jQuery.fn.partResults = function(slid) {
		var table = $(this);

		var mOptions = {
			elements: { point: { radius: 2 } },
			showTooltips: true,
			tooltipCaretSize: 0,
			tooltips: {
				position: 'nearest',
				mode: 'index',
			},
			scales: {
				xAxes: [{ display: true }],
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

		var labels = [];
		var supply = [];
		var demand = [];

		var rows,html,n,s,mData,mChart,clonedChart,ctx,rspan,alias_str,aliases,notes,descr,part,range,avg_cost,shelflife,partids,dis,chk,cls,mpart;

		$.ajax({
			url: 'json/market.php',
			type: 'get',
			data: {'slid': slid},
			settings: {async:true},
			error: function(xhr, desc, err) {
				$('#loader').hide();
			},
			success: function(json, status) {
				$('#loader').hide();
				if (json.message && json.message!='') {
					modalAlertShow('Error',json.message,false);
					return;
				}

				$.each(json.results, function(ln, row) {
					n = Object.keys(row.results).length;//row.results.length;
					s = '';
					if (n!=1) { s = 's'; }
					rspan = 2;//n+1;

					range = '$';
					if (row.range.min>0) {
						range += row.range.min;
						if (row.range.max && row.range.min!=row.range.max) { range += ' - $'+row.range.max; }
					}

/*
					avg_cost = '';
					dis = '';
					if (row.avg_cost>0) {
						avg_cost = '$'+row.avg_cost;
						dis = ' readonly';
					}
*/

					shelflife = '<i class="fa fa-qrcode"></i>';
					if (row.shelflife) { shelflife += ' '+row.shelflife; }

					buttons = '<div class="btn-group">\
                            <button class="btn btn-xs btn-default left" type="button" title="disable & collapse" data-toggle="tooltip" data-placement="top" rel="tooltip"><i class="fa fa-close"></i></button>\
                            <button class="btn btn-xs btn-default middle" type="button" title="save, no reply" data-toggle="tooltip" data-placement="top" rel="tooltip"><i class="fa fa-square-o"></i></button>\
                            <button class="btn btn-xs btn-default right active" type="button" title="save & reply" data-toggle="tooltip" data-placement="top" rel="tooltip"><i class="fa fa-check-square-o"></i></button>\
                        </div>';

					rows = '';
					partids = '';
					$.each(row.results, function(pid, item) {
						cls = 'product-row '+item.class;
						if (item.qty>0) { cls += ' in-stock'; }

						chk = '';
						if (item.class=='primary') { chk = ' checked'; }

						partid = item.id;
						if (parseInt(partid)>0) {
							if (partids!='') { partids += ','; }
							partids += partid;
						}
						part = item.primary_part;
						if (item.heci) { part += ' '+item.heci; }

						aliases = '';
						alias_str = '';

						descr = '';
						if (item.manf) descr += item.manf;
						if (item.system) { if (descr!='') { descr += ' '; } descr += item.system; }
						if (item.description) { if (descr!='') { descr += ' '; } descr += item.description; }
						$.each(item.aliases, function(a, alias) {
//							if (alias_str!='') alias_str += ' ';
							mpart = item.part.replace(' '+alias,'');
							alias_str += '<span class="alias">'+alias+'<a href="javascript:void(0);" data-part="'+mpart+'" class="edit-part"><i class="fa fa-times-circle text-danger"></i></a></span>';
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

						rows += '\
									<tr class="'+cls+'" data-partid="'+partid+'" id="'+item.id+'-'+ln+'">\
										<td class="col-sm-1 colm-sm-0-5 text-center">\
											<input type="checkbox" name="items['+ln+']['+item.id+']" class="item-check" value="1"'+chk+'><i class="fa fa-star-o"></i>\
										</td>\
										<td class="col-sm-1">\
											<input type="text" name="item_qtys['+ln+']['+item.id+']" class="form-control input-xs" value="'+item.qty+'" placeholder="Qty" title="Stock Qty" data-toggle="tooltip" data-placement="bottom" rel="tooltip">\
										</td>\
										<td class="col-sm-9">\
											<div class="product-img">\
												<img src="/img/parts/'+item.primary_part+'.jpg" alt="pic" class="img" data-part="'+item.primary_part+'" />\
											</div>\
											<div class="product-details" style="display:inline-block; width:80%; font-size:11px">\
												'+part+aliases+notes+'<br/><span class="info"><small>'+descr+'</small></span>\
											</div>\
										</td>\
										<td class="col-sm-1 colm-sm-1-5 price">\
											<div class="form-group">\
												<div class="input-group sell">\
													<span class="input-group-btn">\
														<button class="btn btn-default input-xs price-toggle" type="button" tabindex="-1" data-toggle="tooltip" data-placement="left" title="toggle price group"><i class="fa fa-lock"></i></button>\
													</span>\
													<input type="text" name="item_prices['+ln+']['+item.id+']" class="form-control input-xs" value="" placeholder="0.00"/>\
												</div>\
	                                        </div>\
										</td>\
									</tr>\
						';
					});

					html = '\
						<tr id="row_'+ln+'" class="header-row first">\
							<td class="col-sm-1 colm-sm-0-5">\
								<input type="checkbox" name="rows['+ln+']" class="checkItems pull-left" value="'+ln+'" checked>\
								<input type="text" name="list_qtys['+ln+']" class="form-control input-xs list-qty pull-right" value="'+row.qty+'" placeholder="Qty" title="their qty" data-toggle="tooltip" data-placement="top" rel="tooltip">\
							</td>\
							<td class="col-sm-3 colm-sm-3-5">\
								<div class="search">\
									<input type="text" name="searches['+ln+']" class="form-control input-xs input-camo product-search" value="'+row.search+'"/><br/> &nbsp; <span class="info">'+n+' result'+s+'</span>\
								</div>\
								<div class="price">\
									<div class="form-group">\
										<div class="input-group">\
											<span class="input-group-addon"><i class="fa fa-dollar"></i></span>\
											<input type="text" name="list_prices['+ln+']" class="form-control input-xs list-price" value="'+row.price+'" placeholder="0.00" title="their price" data-toggle="tooltip" data-placement="top" rel="tooltip">\
										</div>\
									</div>\
								</div>\
							</td>\
							<td class="col-sm-1 colm-sm-1-2 text-center">\
								<a class="btn btn-xs btn-default text-bold" href="javascript:void(0);" title="toggle priced results" data-toggle="tooltip" data-placement="top" rel="tooltip">'+range+'</a><br/><span class="info market-header">market</span>\
							</td>\
							<td class="col-sm-1 colm-sm-1-2 text-center col-cost">\
								<div class="form-group form-couple" style="margin-bottom: 0;">\
									<div class="input-group pull-left"><span class="input-group-addon" aria-hidden="true"><i class="fa fa-usd"></i></span>\
										<input type="text" name="avg_cost['+ln+']" id="avg-cost-'+ln+'" class="form-control input-xs text-center" title="avg cost" data-toggle="tooltip" data-placement="top" rel="tooltip" value="" readonly/>\
									</div>\
									<div class="input-group pull-right">\
										<input class="form-control input-xs text-center text-muted" value="" placeholder="0" type="text" title="profit calc" data-toggle="tooltip" data-placement="top" rel="tooltip">\
										<span class="input-group-addon"><i class="fa fa-percent" aria-hidden="true"></i></span>\
									</div>\
								</div>\
								<span class="info">cost basis & markup</span>\
							</td>\
							<td class="col-sm-1 colm-sm-1-2 text-center">\
								<a class="btn btn-xs btn-default text-bold" href="inventory.php?s='+row.search+'" target="_new" title="view inventory" data-toggle="tooltip" data-placement="top" rel="tooltip">'+shelflife+'</a><br/><span class="info">shelflife</span>\
							</td>\
							<td class="col-sm-1 colm-sm-1-2 text-bold text-center">'+row.pr+'<br/><span class="info">proj req</span></td>\
							<td class="col-sm-2 colm-sm-3-2 response-calc">\
								<div class="pull-left">\
									<div class="form-group" style="display:inline-block; width:50px">\
										<input type="text" class="form-control input-sm response-qty" name="response_qtys['+ln+']" value="" placeholder="0" title="reqd qty" data-toggle="tooltip" data-placement="top" rel="tooltip">\
									</div>\
									<i class="fa fa-times fa-lg"></i>&nbsp;\
								</div>\
								<div class="pull-left price">\
									<div class="form-group" style="width:125px">\
										<div class="input-group">\
											<span class="input-group-addon" aria-hidden="true"><i class="fa fa-usd"></i></span>\
											<input type="text" class="form-control input-sm response-price" name="response_prices['+ln+']" value="" placeholder="0.00" title="our offer/quote" data-toggle="tooltip" data-placement="top" rel="tooltip">\
										</div>\
									</div>\
									<i class="fa fa-chevron-right fa-lg"></i>\
									<div class="row-total text-right" title="row total" data-toggle="tooltip" data-placement="top" rel="tooltip"><h5>$ 0.00</h5></div>\
								</div>\
								<div class="pull-right">\
									'+buttons+' &nbsp; <strong>'+(parseInt(row.ln)+1)+'.</strong>\
								</div>\
							</td>\
						</tr>\
						<tr id="items_'+ln+'" class="items-row" data-ln="'+ln+'">\
							<td colspan=2>\
								<div class="mh">\
								<table class="table table-condensed table-striped table-hover table-items">\
									'+rows+'\
								</table>\
								</div>\
							</td>\
							<td class="bg-market" data-type="Supply" data-pricing="0" id="market_'+ln+'"></td>\
							<td class="bg-purchases" data-type="Purchase" data-pricing="1"></td>\
							<td class="bg-sales" data-type="Sale" data-pricing="1"></td>\
							<td class="bg-demand" data-type="Demand" data-pricing="0"></td>\
							<td class="col-chart" colspan=2></td>\
						</tr>\
					';

					table.append(html);

					labels = [];
					supply = [];
					demand = [];

					$.each(row.chart, function(mo, m) {
//						console.log(m);
						labels.push(mo);
						if (m.offer) { supply.push(m.offer); }
						if (m.quote) { demand.push(m.quote); }
					});

					if (supply.length==0 && demand.length==0) { return; }

					clonedChart = $("#mChart").clone();
					clonedChart.attr('id','chart_'+ln);
					clonedChart.appendTo($("#items_"+ln).find(".col-chart"));
					clonedChart.prop('height','200');
					clonedChart.prop('width','300');

					// chlot: close, high, low, open, time
					ctx = $("#chart_"+ln);
					//random = getRandomData('April 01 2017', 10);
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
					mChart = new Chart(ctx, {
						type: 'candlestick',
						data: mData,
						options: mOptions,
					});

				});

				updateResults(table.find(".items-row"));
/*
				table.find(".items-row .bg-market").each(function() { $(this).results(); });
				table.find(".items-row .bg-purchases").each(function() { $(this).results(); });
				table.find(".items-row .bg-sales").each(function() { $(this).results(); });
				table.find(".items-row .bg-demand").each(function() { $(this).results(); });
*/
			},
		});
	};/*end partResults*/

	function updateResults(row) {
		row.find(".bg-market").each(function() { $(this).marketResults(0); });
		row.find(".bg-purchases").each(function() { $(this).marketResults(0); });
		row.find(".bg-sales").each(function() { $(this).marketResults(0); });
		row.find(".bg-repairs").each(function() { $(this).marketResults(0); });
		row.find(".bg-demand").each(function() { $(this).marketResults(0); });
	}
	function setCategory(category) {
		if (! category) { var category = ''; }

		$(".btn-category").each(function() {
			if (category!='') {//set selected value
				if ($(this).text()==category) { $(this).addClass('active'); }
				else { $(this).removeClass('active'); }
			} else if ($(this).hasClass('active')) {//get selected value
				category = $(this).text();
			}
		});

		return (category);
	}

	jQuery.fn.marketResults = function(attempt) {
		var col = $(this);

		if (! attempt) { var attempt = 0; }
		if (attempt==0) { col.html(''); }

		var pricing = $(this).data('pricing');
		var tr = $(this).closest(".items-row");
		var partids = getCheckedPartids(tr.find(".table-items tr"));

		if (partids=='') { return; }

		if (category=='Repair') {
			if (col.hasClass('bg-purchases')) {
				$(this).removeClass('bg-purchases').addClass('bg-outsourced');
				$(this).data('type','Outsourced');
			} else if (col.hasClass('bg-sales')) {
				$(this).removeClass('bg-sales').addClass('bg-repairs');
				$(this).data('type',category);
			}
		} else if (category=='Sale') {
			if (col.hasClass('bg-outsourced')) {
				$(this).removeClass('bg-outsourced').addClass('bg-purchases');
				$(this).data('type','Purchase');
			} else if (col.hasClass('bg-repairs')) {
				$(this).removeClass('bg-repairs').addClass('bg-sales');
				$(this).data('type',category);
			}
		}

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
						avg_cost = '$'+json.avg_cost;
						$("#avg-cost-"+ln).val(avg_cost);
//						$("#avg-cost-"+ln).prop('readonly',true);
					}
				}

				dwnld = '';
				if (category=='Sale' && otype=='Supply') {
					dwnld = ' <a href="javascript:void(0);" class="lk-download"><i class="fa fa-download"></i></a>';
				}

				html = '\
				<div class="col-results">\
					<a href="javascript:void(0);" class="market-title modal-results" data-target="marketModal" title="'+otype+' Results" data-toggle="tooltip" data-placement="top" rel="tooltip" data-title="'+otype+' Results" data-type="'+otype+'">\
						'+otype+' <i class="fa fa-window-restore"></i>'+dwnld+'\
					</a>\
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

						if (! row.companyid) {
							// required for spacing
//							html += '<div class="item-result '+cls+'"> &nbsp; </div>';
							return;
						}

						sources = '';
						$.each(row.sources, function (source, url) {
							src = '';
							if (source=='email') { src = '<i class="fa fa-email"></i>'; }
							else if (source!='import') { src = '<img src="img/'+source.toLowerCase()+'.png" class="bot-icon" />'; }

							sources += ' '+src;
						});

						price = '';
						price_ln = '';
						if (row.price>0) {
							if (row.past_price=='1') { price = '<span class="info"> $'+row.price+'</span>'; }
							else { price = ' $'+row.price; }
						}
						if (otype=='Sale' || otype=='Purchase' || otype=='Repair' || otype=='Outsourced') {
							price_ln = ' <a href="order.php?order_type='+otype+'&order_number='+row.order_number+'" target="_new"><i class="fa fa-arrow-right"></i></a>';
						} else if (row.order_number) {
							price_ln = ' <a href="view_quote.php?metaid='+row.order_number+'"><i class="fa fa-pencil"></i></a>';
						}
						html += '<div class="show-hover'+cls+'">'+
							row.qty+' <div class="market-company"><a href="profile.php?companyid='+row.companyid+'" target="_new"><i class="fa fa-building"></i></a> '+row.name+'</div>'+sources+price+price_ln+
							'</div>';
					});
				}

				html += '</div>';
				col.html(html);

				if (col.hasClass('bg-market')) {
					if (category=='Sale' && (ln<=max_ln || attempt>0)) {
						if (! json.done && attempt==0) {
							setTimeout("$('#"+col.prop('id')+"').marketResults("+(attempt+1)+")",1000);
						} else if (json.done==1 && attempt>0) {
//							tr.closest("table").find("#row_"+ln+" .market-header").html('market');
						}
					} else {
//						tr.closest("table").find("#row_"+ln+" .market-header").html('market');
					}
				}
			},
		});
	};
</script>

</body>
</html>
