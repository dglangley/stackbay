<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getField.php';

	//default field handling variables
	$col_search = 0;
	$sfe = false;//search from end
	$col_qty = 1;
	$qfe = false;//qty from end
	$col_price = false;
	$pfe = false;//price from end

	$lines = array();
	if (isset($_REQUEST['s'])) {
		$lines = array(trim($_REQUEST['s']));
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

	$chartW = 300;
	$chartH = 175;

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
		.header-row {
			border-top:1px solid #ccc;
		}
		.header-row > td {
			padding-top:2px;
		}
		.items-row > td {
			padding-top:4px;
			padding-bottom:80px;
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
		<div class="col-sm-2">
		</div>
		<div class="col-sm-2 text-center">
			<h2 class="minimal"><?php echo $TITLE; ?></h2>
			<span class="info"></span>
		</div>
		<div class="col-sm-2">
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

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<div class="hidden">
<canvas id="mChart" width="<?=$chartW;?>" height="<?=$chartH;?>"></canvas>
</div>

<script type="text/javascript">
	$(document).ready(function() {
		$('#loader-message').html('Gathering market information...');
		$('#loader').show();

		$("#results").marketResults('<?=$slid;?>');
	});

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
		var html,n,s,mData,mChart,clonedChart,ctx,rspan,alias_str,aliases,descr,part,range,avg_cost;

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

					range = '';
					if (row.range.min>0) {
						range = '$'+row.range.min;
						if (row.range.max && row.range.min!=row.range.max) { range += ' - $'+row.range.max; }
					}

					avg_cost = '';
					if (row.avg_cost>0) {
						avg_cost = '$'+row.avg_cost;
					}

					buttons = '<div class="btn-group">\
                            <button class="btn btn-xs btn-default left" type="button"><i class="fa fa-close"></i></button>\
                            <button class="btn btn-xs btn-default middle" type="button"><i class="fa fa-square-o"></i></button>\
                            <button class="btn btn-xs btn-default right active" type="button"><i class="fa fa-check-square-o"></i></button>\
                        </div>';

					html = '\
						<tr id="row_'+ln+'" class="header-row first">\
							<td class="col-sm-1">'+row.qty+'</td>\
							<td class="col-sm-5 text-bold"><input type="text" class="form-control input-xs input-camo" value="'+row.search+'"/><br/><span class="info">'+n+' result'+s+'</span></td>\
							<td class="col-sm-1 text-bold text-center">'+range+'<br/><span class="info">market</span></td>\
							<td class="col-sm-1 text-bold text-center">'+avg_cost+'<br/><span class="info">avg cost</span></td>\
							<td class="col-sm-1 text-bold text-center">'+row.shelflife+'<br/><span class="info">shelflife</span></td>\
							<td class="col-sm-1 text-bold text-center">'+row.pr+'<br/><span class="info">proj req</span></td>\
							<td class="col-sm-1"></td>\
							<td class="col-sm-1 text-right">'+buttons+'<br/>'+row.ln+'</td>\
						</tr>\
						<tr id="items_'+ln+'" class="items-row">\
							<td colspan=2>\
								<table class="table table-condensed table-striped table-hover">\
					';
					$.each(row.results, function(partid, item) {
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

						html += '\
									<tr>\
										<td class="col-sm-1"><i class="fa fa-star"></i></td>\
										<td class="col-sm-1"><input type="text" class="form-control input-xs" value="1"/></td>\
										<td class="col-sm-9">'+part+aliases+'<br/><span class="info"><small>'+descr+'</small></span></td>\
										<td class="col-sm-1"><input type="text" class="form-control input-xs" value="" placeholder="0.00"/></td>\
									</tr>\
						';
					});
					html += '\
								</table>\
							</td>\
							<td></td>\
							<td></td>\
							<td></td>\
							<td></td>\
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
			},
		});
	};
</script>

</body>
</html>
