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

	$chartW = 150;
	$chartH = 75;

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
			height:<?=$chartW;?>px;
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
<canvas id="mChart" width="<?=$chartW-10;?>" height="<?=$chartH-10;?>"></canvas>
</div>

<script type="text/javascript">
	$(document).ready(function() {
		$("#results").marketResults('<?=$slid;?>');
	});

	jQuery.fn.marketResults = function(slid) {
		var table = $(this);
		var html,n,s,mData,mChart,clonedChart,ctx,rspan;

		var mOptions = {
			elements: { point: { radius: 0 } },
			showTooltips:false,
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
			}
		};

		var labels = [];
		var supply = [];
		var demand = [];

		$.ajax({
			url: 'json/market.php',
			type: 'get',
			data: {'slid': slid},
			settings: {async:true},
			success: function(json, status) {
				if (json.message && json.message!='') {
					modalAlertShow('Error',json.message,false);
					return;
				}

				$.each(json.results, function(ln, row) {
					n = Object.keys(row.results).length;//row.results.length;
					s = '';
					if (n!=1) { s = 's'; }
					rspan = n+1;

					$.each(row.market, function(mo, m) {
						labels.push(mo);
						if (m.offer) { supply.push(m.offer); }
						if (m.quote) { demand.push(m.quote); }
					});

					html = '\
						<tr id="row_'+ln+'">\
							<td>'+row.qty+'</td>\
							<td class="text-bold"><input type="text" class="form-control input-xs input-camo" value="'+row.search+'"/><br/><span class="info">'+n+' result'+s+'</span></td>\
							<td class="col-chart" rowspan='+rspan+'></td>\
							<td rowspan='+rspan+'>'+row.ln+'</td>\
						</tr>\
						<tr>\
							<td colspan=2>\
								<table class="table table-condensed">\
					';
					$.each(row.results, function(partid, item) {
						html += '\
									<tr>\
										<td>'+partid+'</td>\
									</tr>\
						';
					});
					html += '\
								</table>\
							</td>\
						</tr>\
					';

					table.append(html);

					clonedChart = $("#mChart").clone();
					clonedChart.attr('id','chart_'+ln);
					clonedChart.appendTo($("#row_"+ln).find(".col-chart"));

					ctx = $("#chart_"+ln);
					mData = {
						labels: labels,
						datasets: [
							{
								label: 'supply',
								data: supply,
								borderColor: 'orange',
								fill: false,
							},
							{
								label: 'demand',
								data: demand,
								borderColor: 'green',
								fill: false,
							},
						]
					};
					mChart = new Chart(ctx, {
						type: 'line',
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
