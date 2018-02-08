<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getField.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/logSearch.php';

	//default field handling variables
	$col_search = 1;
	$sfe = false;//search from end
	$col_qty = 2;
	$qfe = false;//qty from end
	$col_price = false;
	$pfe = false;//price from end

	$slid = 0;
	$lines = array();
	if (isset($_REQUEST['s'])) {
		$lines = array(trim($_REQUEST['s']));

		$slid = logSearch($_REQUEST['s'],$col_search,$sfe,$col_qty,$qfe,$col_price,$pfe);
	} else if (isset($_REQUEST['s2'])) {
		$lines = explode(chr(10),$_REQUEST['s2']);
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
		if (! $qty) { $qty = 1; }

		$price = getField($F,$col_price,$pfe);
		if ($price===false) { $price = ''; }
	}

	$chartW = 240;
	$chartH = 150;

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
		.input-camo {
			font-weight:bold;
			border:0px;
			background:none;
			color:#666;
		}
		.col-chart {
			width:<?=$chartW;?>px;
/*
			height:<?=$chartW;?>px;
*/
		}

.colm-sm-0-5,
.colm-sm-1-2,
.colm-sm-0-5,
.colm-sm-2-2,
.colm-sm-3-5,
.colm-sm-10-5 {
	padding-left: 10px;
	padding-right: 10px;
}
.colm-sm-0-5 {
	width: 4.166666666666667%;
}
.colm-sm-1 {
	width: 8.333333333333332%;
}
.colm-sm-1-2 {
	width: 10%;
}
.colm-sm-1-5 {
	width: 12.4995%;
}
.colm-sm-2 {
	width: 16.666666666666664%;
}
.colm-sm-2-2 {
	width: 18.333333333333332%;
}
.colm-sm-3 {
	width: 25%;
}
.colm-sm-3-5 {
	width: 29.166666666666667%;
}
.colm-sm-4 {
	width: 33.33333333333333%;
}
.colm-sm-5 {
	width: 41.66666666666667%;
}
.colm-sm-6 {
	width: 50%;
}
.colm-sm-7 {
	width: 58.333333333333336%;
}
.colm-sm-8 {
	width: 66.66666666666666%;
}
.colm-sm-9 {
	width: 75%;
}
.colm-sm-9 {
	width: 75%;
}
.colm-sm-10 {
	width: 83.33333333333334%;
}
.colm-sm-10-5 {
	width: 87.5%;
}
.colm-sm-11 {
	width: 91.66666666666666%;
}
.colm-sm-12 {
	width: 100%;
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
			max-height:290px;
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
			line-height:1.4;
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
			font-size:12px;
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
			margin-top:3px;
			padding-top:0px;
		}
		.col-results .market-company {
			display:inline-block;
			min-width:75px;
			max-width:85px;
			padding-left:2px;
			padding-right:2px;
			vertical-align:bottom;
		}
		.col-results .item-result a .fa {
			visibility:hidden;
		}
		.col-results .item-result:hover a .fa {
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
		.list-qty {
			width:35px;
		}
		.list-qty,
		.list-price {
			border:1px solid black;
		}
		.bot-icon {
			margin-left:-1px;
			margin-right:-1px;
		}
	</style>
</head>
<body>

<?php include_once 'inc/navbar.php'; ?>

<!-- FILTER BAR -->
<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px; max-height:60px;">
	<form class="form-inline" method="get" action="" enctype="multipart/form-data" id="filters-form" >

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
				<button class="btn btn-xs btn-default left active" type="button" title="equipment sales" data-toggle="tooltip" data-placement="bottom" rel="tooltip">Sales</button>
				<button class="btn btn-xs btn-default right" type="button" title="equipment repair" data-toggle="tooltip" data-placement="bottom" rel="tooltip">Repair</button>
			</div>
		</div>
		<div class="col-sm-2 text-center">
			<h2 class="minimal"><?php echo $TITLE; ?></h2>
			<span class="info"></span>
		</div>
		<div class="col-sm-1">
			<div class="slider-frame success" style="left:0; top:0; position:absolute">
				<!-- include radio's inside slider-frame to set appropriate actions to them -->
				<input class="sales_mode hidden" value="Buy" type="radio">
				<input class="sales_mode hidden" value="Sell" type="radio">
				<span data-on-text="Buy" data-off-text="Sell" class="slider-button upload-slider" id="upload-slider">Sell</span>
			</div>
		</div>
		<div class="col-sm-1">
		</div>
		<div class="col-sm-1">
		</div>
		<div class="col-sm-2">
		</div>
	</div>

	</form>
</div>

<div id="pad-wrapper">
<form class="form-inline" method="get" action="" enctype="multipart/form-data" >

	<div class="table-responsive">
		<table class="table table-condensed" id="results">
		</table>
	</div>

</form>
</div><!-- pad-wrapper -->

<?php include_once 'modal/image.php'; ?>
<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<div class="hidden">
<canvas id="mChart" width="<?=$chartW;?>" height="<?=$chartH;?>"></canvas>
</div>

<script type="text/javascript">
	$(document).ready(function() {
		$('#loader-message').html('Gathering market information...');
		$('#loader').show();

		$("#results").marketResults('<?=$slid;?>');

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
	});

	jQuery.fn.setRow = function() {
		if ($(this).prop('checked')===true) {
			$(this).closest("tr").removeClass('sub').addClass('primary');
		} else {
			$(this).closest("tr").removeClass('primary').addClass('sub');
		}
	};

	jQuery.fn.marketResults = function(slid) {
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
				xAxes: [{ display: false }],
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
		var rows,html,n,s,mData,mChart,clonedChart,ctx,rspan,alias_str,aliases,descr,part,range,avg_cost,shelflife,partids,dis,chk,cls;

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

					avg_cost = '';
					dis = '';
					if (row.avg_cost>0) {
						avg_cost = '$'+row.avg_cost;
						dis = ' readonly';
					}

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
						cls = item.class;
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
							if (alias_str!='') alias_str += ' ';
							alias_str += alias;
						});
						if (alias_str!='') { aliases = ' &nbsp; <small>'+alias_str+'</small>'; }

						rows += '\
									<tr class="'+cls+'" data-partid="'+partid+'">\
										<td class="col-sm-1 colm-sm-0-5 text-center">\
											<input type="checkbox" class="item-check" value="1"'+chk+'><i class="fa fa-star-o"></i>\
										</td>\
										<td class="col-sm-1"><input type="text" class="form-control input-xs" value="'+item.qty+'" placeholder="Qty" title="Stock Qty" data-toggle="tooltip" data-placement="bottom" rel="tooltip"></td>\
										<td class="col-sm-9">\
											<div class="product-img">\
												<img src="/img/parts/'+item.primary_part+'.jpg" alt="pic" class="img" data-part="'+item.primary_part+'" />\
											</div>\
											<div style="display:inline-block">\
												'+part+aliases+'<br/><span class="info"><small>'+descr+'</small></span>\
											</div>\
										</td>\
										<td class="col-sm-1 colm-sm-1-5 price">\
											<div class="form-group">\
												<div class="input-group sell">\
													<span class="input-group-btn">\
														<button class="btn btn-default input-xs price-toggle" type="button" tabindex="-1" data-toggle="tooltip" data-placement="left" title="toggle price group"><i class="fa fa-lock"></i></button>\
													</span>\
													<input type="text" class="form-control input-xs" value="" placeholder="0.00"/>\
												</div>\
	                                        </div>\
										</td>\
									</tr>\
						';
					});

					html = '\
						<tr id="row_'+ln+'" class="header-row first">\
							<td class="col-sm-1 colm-sm-0-5">\
								<input type="checkbox" class="checkItems pull-left" value="1" checked>\
								<input type="text" class="form-control input-xs list-qty pull-right" value="'+row.qty+'" placeholder="Qty" title="List Qty" data-toggle="tooltip" data-placement="bottom" rel="tooltip">\
							</td>\
							<td class="col-sm-3 colm-sm-3-5 text-bold"><input type="text" class="form-control input-xs input-camo" value="'+row.search+'"/><br/> &nbsp; <span class="info">'+n+' result'+s+'</span></td>\
							<td class="col-sm-1 colm-sm-1-2 text-center">\
								<a class="btn btn-xs btn-default text-bold" href="javascript:void(0);" title="toggle priced results" data-toggle="tooltip" data-placement="top" rel="tooltip">'+range+'</a><br/><span class="info">market</span>\
							</td>\
							<td class="col-sm-1 colm-sm-1-2 text-center col-cost">\
								<div class="input-group"><span class="input-group-addon" aria-hidden="true"><i class="fa fa-usd"></i></span>\
									<input type="text" class="form-control input-xs text-bold" title="avg cost" data-toggle="tooltip" data-placement="top" rel="tooltip" value="'+avg_cost+'"'+dis+'/>\
								</div>\
								<span class="info">cost basis</span>\
							</td>\
							<td class="col-sm-1 colm-sm-1-2 text-center">\
								<a class="btn btn-xs btn-default text-bold" href="inventory.php?s='+row.search+'" target="_new" title="view inventory" data-toggle="tooltip" data-placement="top" rel="tooltip">'+shelflife+'</a><br/><span class="info">shelflife</span>\
							</td>\
							<td class="col-sm-1 colm-sm-1-2 text-bold text-center">'+row.pr+'<br/><span class="info">proj req</span></td>\
							<td class="col-sm-1 colm-sm-2-2"></td>\
							<td class="col-sm-1 text-right">'+buttons+'<br/>'+row.ln+'</td>\
						</tr>\
						<tr id="items_'+ln+'" class="items-row">\
							<td colspan=2>\
								<div class="mh">\
								<table class="table table-condensed table-striped table-hover table-items">\
									'+rows+'\
								</table>\
								</div>\
							</td>\
							<td class="bg-market" data-type="Supply" data-pricing="0"></td>\
							<td class="bg-purchases" data-type="Purchase" data-pricing="1"></td>\
							<td class="bg-sales" data-type="Sale" data-pricing="1"></td>\
							<td class="bg-demand" data-type="Demand" data-pricing="0"></td>\
							<td class="col-chart"></td>\
							<td></td>\
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
									up: 'green',
									down: 'green',
									unchanged: '#000',
								},
								backgroundColor: 'green',
								borderColor: 'green',
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
									up: 'orange',
									down: 'orange',
									unchanged: '#000',
								},
								backgroundColor: 'orange',
								borderColor: 'orange',
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
	};/*end marketResults*/

	function updateResults(row) {
		row.find(".bg-market").each(function() { $(this).results(); });
		row.find(".bg-purchases").each(function() { $(this).results(); });
		row.find(".bg-sales").each(function() { $(this).results(); });
		row.find(".bg-demand").each(function() { $(this).results(); });
	}

	jQuery.fn.results = function() {
		var col = $(this);
		col.html('');

		var otype = col.data('type');
		var pricing = $(this).data('pricing');
		var partids = '';
		$(this).closest(".items-row").find(".table-items tr").each(function() {
//			if ($(this).hasClass('sub')) { return; }
			if ($(this).find(".item-check:checkbox").prop('checked')===false) { return; }

			if (partids!='') { partids += ','; }
			partids += $(this).data("partid");
		});

		if (partids=='') { return; }

		col.html('<i class="fa fa-circle-o-notch fa-spin"></i>');

		var html,last_date,price,price_ln,cls,sources,src;
		$.ajax({
			url: 'json/results.php',
			type: 'get',
			data: {'partids': partids, 'type': otype, 'pricing': pricing},
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

				html = '<div class="col-results">';
				last_date = '';
				$.each(json.results, function(ln, row) {
					cls = '';
					if (row.format=='h4') { cls = ' info'; }
					else if (row.format=='h6') { cls = ' primary'; }

					if (row.date!=last_date) {
						html += '<'+row.format+'>'+row.date+'</'+row.format+'>';

						last_date = row.date;
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
					if (otype=='Sale' || otype=='Purchase') {
						price_ln = ' <a href="order.php?order_type='+otype+'&order_number='+row.order_number+'"><i class="fa fa-arrow-right"></i></a>';
					} else {
						price_ln = ' <a href="javascript:void(0);"><i class="fa fa-pencil"></i></a>';
					}
					html += '<div class="item-result'+cls+'">'+
						row.qty+' <div class="market-company"><a href="profile.php?companyid='+row.companyid+'" target="_new"><i class="fa fa-building"></i></a> '+row.name+'</div>'+sources+price+price_ln+
						'</div>';
				});
				html += '</div>';
				col.html(html);
			},
		});
	};
</script>

</body>
</html>
