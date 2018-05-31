<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getField.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/logSearch.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';

	$TITLE = 'Market';
	$list_date = $now;

	$companyid = 0;
	if (isset($_REQUEST['companyid']) AND is_numeric($_REQUEST['companyid'])) { $companyid = $_REQUEST['companyid']; }
	$contactid = 0;
	if (isset($_REQUEST['contactid']) AND is_numeric($_REQUEST['contactid'])) { $contactid = $_REQUEST['contactid']; }
	$PR = 'false';
	if (isset($_REQUEST['dq_count']) AND is_numeric(trim($_REQUEST['dq_count'])) AND trim($_REQUEST['dq_count'])<>'') { $PR = trim($_REQUEST['dq_count']); }
	$salesMin = 'false';
	if (isset($_REQUEST['sales_count']) AND is_numeric(trim($_REQUEST['sales_count'])) AND trim($_REQUEST['sales_count'])<>'') { $salesMin = trim($_REQUEST['sales_count']); }
	$favorites = 'false';
	if (isset($_REQUEST['favorites']) AND is_numeric(trim($_REQUEST['favorites']))) { $favorites = $_REQUEST['favorites']; }
	$startDate = '';
	if (isset($_REQUEST['startDate']) AND $_REQUEST['startDate']) { $startDate = $_REQUEST['startDate']; }
	$endDate = '';
	if (isset($_REQUEST['endDate']) AND $_REQUEST['endDate']) { $endDate = $_REQUEST['endDate']; }
	$demandMin = 'false';
	if (isset($_REQUEST['demand_min']) AND is_numeric(trim($_REQUEST['demand_min'])) AND trim($_REQUEST['demand_min'])<>'') { $demandMin = trim($_REQUEST['demand_min']); }
	$demandMax = 'false';
	if (isset($_REQUEST['demand_max']) AND is_numeric(trim($_REQUEST['demand_max'])) AND trim($_REQUEST['demand_max'])<>'') { $demandMax = trim($_REQUEST['demand_max']); }

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
	$metaid = 0;
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
	} else if ((isset($_REQUEST['metaid']) AND $_REQUEST['metaid']) OR (isset($_REQUEST['upload_listid']) AND $_REQUEST['upload_listid']) OR (isset($_REQUEST['listid']) OR $_REQUEST['listid'])) {
		$processed = true;
		$list_type = '';
		if (! isset($_REQUEST['metaid']) AND (isset($_REQUEST['upload_listid']) OR isset($_REQUEST['listid']))) {
			$upload_listid = ($_REQUEST['upload_listid'] ? $_REQUEST['upload_listid'] : $_REQUEST['listid']);

			$query = "SELECT filename, metaid, type, processed FROM uploads WHERE id = '".res($upload_listid)."'; ";
			$result = qedb($query);
			$r = qrow($result);
			$metaid = $r['metaid'];
			$TITLE = $r['filename'];
			$list_type = $r['type'];
			if (! $r['processed']) { $processed = false; }
		} else {
			$metaid = $_REQUEST['metaid'];

			$query = "SELECT filename, type, processed FROM uploads WHERE metaid = '".res($metaid)."'; ";
			$result = qedb($query);
			if (qnum($result)>0) {
				$r = qrow($result);
				$TITLE = $r['filename'];
				$list_type = $r['type'];
				if (! $r['processed']) { $processed = false; }
			}
		}

		if (! $processed) {
			$ALERTS[] = "Please wait while I process your list. If you do not have an email from me within 10 or 15 minutes, ".
						"you may have unorganized data in your list that I cannot handle.";
		}

		$query = "SELECT * FROM search_meta WHERE id = '".res($metaid)."'; ";
		$result = qedb($query);
		$r = qrow($result);
		$list_date = $r['datetime'];
	}
	$title_info = format_date($list_date,'M j, Y g:i:sa');

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
		.search {
			float:left;
			display:inline-block;
		}
		.price {
			float:right;
			display:inline-block;
		}
		.first input[type="text"],
		.first .input-group input[type="text"],
		.first .input-group .input-group-addon {
			border:1px solid brown;
		}
		.first .input-group .input-group-addon:first-child {
			border-right:0 !important;
		}
		.first .input-group .input-group-addon:last-child {
			border-left:0 !important;
		}
		.list-qty {
			width:35px;
		}
		.input-group .form-control.list-price {
			width:70px;
		}
		.list-qty,
		.list-price {
/*
			border:0px !important;
			background-color:#e1e1e1;
*/
		}
		.product-search,
		.list-qty,
		.list-price {
			font-size:15px !important;
		}
		.select-xs .select2-selection {
			height:24px;
		}
		.select-xs .select2-selection .select2-selection__rendered {
			line-height:20px;
		}
		.select-xs .select2-selection .select2-selection__arrow {
			height:20px;
		}
		.response-calc .row {
			margin-left:2px;
		}
		.nav-tabs.nav-tabs-ar {
			border-top:3px solid transparent;
			border-right:0px;
			background-color:transparent;
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
		.input-camo {
			font-weight:bold;
			border:0px !important;
			background:none;
			color:#666;
		}
		.item-notes, .edit-part, .add-part {
			margin-left:5px;
		}
		.row-total {
			display:inline-block;
			width:90px;
			border:1px solid gray;
			vertical-align:top;
			margin-top:1px;
		}
		.row-total h5 {
			padding:2px 3px;
		}

		.save-menu {
			min-width:100px;
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
			max-width:100px;
			padding-left:2px;
			padding-right:2px;
			vertical-align:bottom;
		}
		.show-hover {
			display:inline-block;
		}
		.show-hover a {
/*
			padding-left:1px;
			padding-right:3px;
*/
		}
		.show-hover a .fa,
		.merge-parts,
		.add-part,
		.edit-part {
			visibility:hidden;
		}
		.product-details:hover .edit-part,
		.header-row:hover .add-part,
		.header-row:hover .merge-parts,
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

		.modal-body .row div {
			padding-left:3px !important;
			padding-right:3px !important;
		}
		.modal-body .row div .input-group.input-xs {
			padding:0px !important;
			line-height:1.2 !important;
		}
		@media screen and (min-width: 768px) {
			.modal-dialog {
				width:800px;
			}
		}
	</style>
</head>
<body>

<?php include_once 'inc/navbar.php'; ?>

<form class="form-inline" method="POST" action="save-market.php" id="results-form">
<input type="hidden" name="slid" value="<?=$slid;?>">
<input type="hidden" name="metaid" value="<?=$metaid;?>">
<input type="hidden" name="category" id="category" value="<?=$category;?>">
<input type="hidden" name="handler" id="handler" value="List">

<!-- FILTER BAR -->
<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px; max-height:60px;">

	<div class="row" style="padding:8px">
		<div class="col-sm-1">
<!--
			<button class="btn btn-sm btn-default" type="button">RFQs</button>
-->
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
			<span class="info"><?php echo $title_info; ?></span>
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
			<div class="btn-group settings">
				<button type="button" class="btn btn-md btn-success btn-save"><i class="fa fa-save"></i> Save</button>
				<button type="button" class="btn btn-md btn-success dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
				<ul class="dropdown-menu dropdown-menu-right text-left save-menu">
					<li><a href="javascript:void(0);" class="text-white btn-success" data-handler="List"><i class="fa fa-save"></i> Save</a></li>
					<li><a href="javascript:void(0);" data-handler="WTB"><i class="fa fa-paper-plane"></i> WTB</a></li>
					<li><a href="javascript:void(0);" data-handler="PR"><i class="fa fa-share-square"></i> Request</a></li>
				</ul>
			</div>
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
<?php include_once 'modal/parts.php'; ?>
<?php include_once 'modal/custom.php'; ?>
<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<div class="hidden">
<canvas id="mChart" width="<?=$chartW;?>" height="<?=$chartH;?>"></canvas>
</div>

<script type="text/javascript">
	$(document).ready(function() {
		companyid = '<?=$companyid;?>';
		contactid = '<?=$contactid;?>';
		slid = '<?=$slid;?>';
		metaid = '<?=$metaid;?>';
		category = setCategory();
		PR = <?=$PR;?>;
		salesMin = <?=$salesMin;?>;
		favorites = <?=$favorites;?>;
		startDate = '<?=$startDate;?>';
		endDate = '<?=$endDate;?>';
		demandMin = '<?=$demand_min;?>';
		demandMax = '<?=$demand_max;?>';
		pricing = 0;

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
		ctx = $("#mChart");
		mChart = new Chart(ctx, {
			type: 'candlestick',
			data: mData,
			options: mOptions,
		});

		$('#loader-message').html('Gathering market information...');
		$('#loader').show();

		$("#results").partResults();


		$(".save-menu li a").on('click', function() {
			var li = $(this);

			// update handler as List/WTB
			var handler = $(this).data('handler');
			$("#handler").val(handler);

			// reset Active class to selected option
			$(this).closest(".dropdown-menu").find("li a").each(function() {
				if ($(this).data('handler')==handler) {
					$(this).removeClass('text-white').removeClass('btn-success').addClass('text-white').addClass('btn-success');
				} else {
					$(this).removeClass('text-white').removeClass('btn-success');
				}
			});

			$(this).closest(".btn-group").find(".btn-save").html($(this).html());
		});

		$(".btn-save").on('click', function() {
			var form = $("#results-form");

			form.submit();
		});

		$("select[name='companyid']").on('change', function() {
			companyid = $(this).val();
		});

		// re-initialize event handler for tooltips
		$('body').tooltip({ selector: '[rel=tooltip]' });

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


		$("body").on('click','.item-check:checkbox',function() {
			$(this).setRow();
			updateResults($(this).closest(".items-row"));
		});

		$("body").on('click','.btn-pricing',function() {
			$(this).toggleClass('active','').toggleClass('btn-primary','btn-default').blur();
			pricing = !pricing;

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

/*
		$("body").on('change','.response-calc input',function() {
			var row = $(this).closest(".response-calc");
			var ln = row.closest(".items-row").data('ln');
			var qty = row.find(".response-qty").val();
			var price = row.find(".response-price").val();
			var total = qty*price;

			//$("#row_"+ln).find(".row-total h5").html('$'+total.formatMoney(2));
			row.find(".row-total h5").html('$'+total.formatMoney(2));
		});
*/

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

			var results_mode = pricing;//global variable to define what type of results we want to see

			// set title of modal
			if (pricing) { title += ' - Prices Only'; } else { title += ' - All'; }
			$("#"+modal_target+" .modal-title").html(title);

			// prepare modal body
			var modalBody = $("#"+modal_target+" .modal-body");
			modalBody.attr('data-ln',ln);

			// reset html so when it pops open, there's no old data
			modalBody.html('<div class="text-center"><i class="fa fa-circle-o-notch fa-spin fa-5x"></i></div>');

			// initialize html body with first row of company selector
			var html = '';//addResultsRow(type);

			$("#"+modal_target).modal('show');

			var res,cid,company,date,p,price,qty,rfq,search,sources;
			$.ajax({
				url: 'json/availability.php',
				type: 'get',
				data: { 'attempt': '0', 'partids': partids, 'results_mode': pricing, 'detail': '1', 'type': type },

				settings: { async:true },
				error: function(xhr, desc, err) {
					$("#"+modal_target).modal('hide');
				},
				success: function(json, status) {
					res = json.results;

					$.each(res, function(formatted_date, date_res) {
						html += '\
							<div class="row">\
								<div class="col-sm-1">&nbsp;</div>\
								<div class="col-sm-1">'+formatted_date+'</div>\
								<div class="col-sm-3">Company</div>\
								<div class="col-sm-1">Source</div>\
								<div class="col-sm-1">Search</div>\
								<div class="col-sm-2">Price</div>\
								<div class="col-sm-2">Lead-Time</div>\
								<div class="col-sm-1">Notes</div>\
							</div>\
						';

						// process each row of data
						$.each(date_res, function(date_cid, row) {
							qty = '<input type="text" name="" class="form-control input-xs" value="'+row.qty+'" \>';

							p = '';
							if (row.cid!=34 && row.price!="") {
								p = Number(row.price.replace(/[^0-9\.-]+/g,"")).toFixed(2);
							}
							price = '<input type="text" name="" class="form-control input-xs" value="'+p+'" \>';
							company = '<a href="profile.php?companyid='+row.cid+'" target="_new"><i class="fa fa-building"></i></a> '+row.company;

							html += '\
							<div class="row">\
								<div class="col-sm-1"><input type="checkbox" name="" value="'+row.cid+'" checked /></div>\
								<div class="col-sm-1">'+qty+'</div>\
								<div class="col-sm-3"><small>'+company+'</small></div>\
								<div class="col-sm-1">&nbsp;</div>\
								<div class="col-sm-1">&nbsp;</div>\
								<div class="col-sm-2">\
									<div class="input-group input-xs">\
										<span class="input-group-addon input-xs"><i class="fa fa-dollar"></i></span>\
										'+price+'\
									</div>\
								</div>\
								<div class="col-sm-2">&nbsp;</div>\
								<div class="col-sm-1">&nbsp;</div>\
							</div>\
							';
						});
					});

					modalBody.html(html);
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

	jQuery.fn.partResults = function(search,replaceNode) {
		if (! search) {
			var search = '';
		}
		if (! replaceNode && replaceNode!==0) {
			var replaceNode = false;
		}

		var table = $(this);

		var labels = [];
		var supply = [];
		var demand = [];

		var rows,header_row,items_row,n,s,clonedChart,rspan,range,avg_cost,shelflife,dis,add_lk,merge_lk;

		$.ajax({
			url: 'json/market.php',
			type: 'get',
			data: {
				'slid': slid,
				'metaid': metaid,
				'search': search,
				'PR': PR,
				'salesMin': salesMin,
				'favorites': favorites,
				'startDate': startDate,
				'endDate': endDate,
				'demandMin': demandMin,
				'demandMax': demandMax,
				'ln': replaceNode
			},
			settings: {async:true},
			error: function(xhr, desc, err) {
				$('#loader').hide();
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
					if (n==0) {
						add_lk = '<a href="javascript:void(0);" class="add-part" data-partid="" data-ln="'+ln+'" title="add new part" data-toggle="tooltip" data-placement="bottom" rel="tooltip"><i class="fa fa-plus"></i></a>';
					} else {
						merge_lk = '<a href="javascript:void(0);" class="merge-parts" title="merge selected parts" data-toggle="tooltip" data-placement="bottom" rel="tooltip"><i class="fa fa-chain"></i></a>';
					}

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
                            <button class="btn btn-xs btn-default btn-response left" data-type="disable" type="button" title="disable & collapse" data-toggle="tooltip" data-placement="top" rel="tooltip"><i class="fa fa-close"></i></button>\
                            <button class="btn btn-xs btn-default btn-response middle" data-type="noreply" type="button" title="save, no reply" data-toggle="tooltip" data-placement="top" rel="tooltip"><i class="fa fa-square-o"></i></button>\
                            <button class="btn btn-xs btn-default btn-response right active" data-type="reply" type="button" title="save & reply" data-toggle="tooltip" data-placement="top" rel="tooltip"><i class="fa fa-check-square-o"></i></button>\
                        </div>';

					rows = buildItemRows(row.results,ln);

					header_row = '\
						<tr id="row_'+ln+'" class="header-row first" data-ln="'+ln+'">\
							<td class="col-sm-1 colm-sm-0-5">\
								<div class="pull-left">\
									<input type="checkbox" name="check['+ln+']" class="checkItems pull-left" value="'+ln+'" checked>\
									<input type="hidden" name="rows['+ln+']" value="'+ln+'"><br/>\
									'+merge_lk+'\
								</div>\
								<div class="pull-right text-center">\
									<input type="text" name="list_qtys['+ln+']" class="form-control input-xs list-qty pull-right" value="'+row.qty+'" placeholder="Qty" title="their qty" data-toggle="tooltip" data-placement="top" rel="tooltip"><br/>\
									<span class="info">qty</span>\
								</div>\
							</td>\
							<td class="col-sm-3 colm-sm-3-5">\
								<div class="search">\
									<input type="text" name="searches['+ln+']" class="form-control input-xs input-camo product-search" value="'+row.search+'"/><br/>\
									<span class="info text-brown">'+n+' result'+s+'</span>'+add_lk+' &nbsp;\
									<span class="info"><small>'+row.line+'</small></span>\
								</div>\
								<div class="price text-center">\
									<div class="form-group">\
										<div class="input-group">\
											<span class="input-group-addon"><i class="fa fa-dollar"></i></span>\
											<input type="text" name="list_prices['+ln+']" class="form-control input-xs list-price" value="'+row.price+'" placeholder="0.00" title="their price" data-toggle="tooltip" data-placement="top" rel="tooltip">\
										</div>\
									</div><br/>\
									<span class="info" style="font-size:12px">target/cost basis</span>\
								</div>\
							</td>\
							<td class="col-sm-1 colm-sm-1-2 text-center">\
								<a class="btn btn-xs btn-default text-bold btn-pricing" href="javascript:void(0);" title="toggle priced results" data-toggle="tooltip" data-placement="top" rel="tooltip">'+range+'</a><br/><span class="info market-header">market</span>\
							</td>\
							<td class="col-sm-1 colm-sm-1-2 text-center">\
								<div class="input-group"><span class="input-group-addon" aria-hidden="true"><i class="fa fa-usd"></i></span>\
									<input type="text" name="avg_cost['+ln+']" id="avg-cost-'+ln+'" class="form-control input-xs text-center" title="avg cost" data-toggle="tooltip" data-placement="top" rel="tooltip" value="" readonly/>\
									<span class="input-group-addon" aria-hidden="true"><a href="javascript:void(0);" class="text-gray modal-avgcost-tag" data-url="json/average_costs.php"><i class="fa fa-pencil"></i></a></span>\
								</div>\
								<span class="info">average cost</span>\
							</td>\
							<td class="col-sm-1 colm-sm-1-2 text-center">\
								<a class="btn btn-xs btn-default text-bold" href="inventory.php?s='+row.search+'" target="_new" title="view inventory" data-toggle="tooltip" data-placement="top" rel="tooltip">'+shelflife+'</a><br/>\
								<span class="info">shelflife</span>\
							</td>\
							<td class="col-sm-1 colm-sm-1-2 text-bold text-center">'+row.pr+'<br/><span class="info">proj req</span></td>\
							<td class="col-sm-2 colm-sm-2-2">\
								<ul class="nav nav-tabs nav-tabs-ar">\
						        	<li class="active"><a href="#calc" data-toggle="tab"><span class="hidden-xs hidden-sm"><i class="fa fa-money fa-lg"></i> Pricing</span></a></li>\
						        	<li class=""><a href="#materials" data-toggle="tab"><span class="hidden-xs hidden-sm"><i class="fa fa-microchip fa-lg"></i> Materials</span></a></li>\
						        	<li class=""><a href="#chart" data-toggle="tab"><span class="hidden-xs hidden-sm"><i class="fa fa-area-chart fa-lg"></i> Chart</span></a></li>\
								</ul>\
							</td>\
							<td class="col-sm-1">\
								<div class="pull-right">\
									'+buttons+' &nbsp; <strong>'+(parseInt(row.ln)+1)+'.</strong>\
								</div>\
							</td>\
						</tr>\
					';
					items_row = '\
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
							<td class="response-calc" colspan=2>\
<!--\
								<div class="col-chart"></div>\
-->\
								<div class="row">\
									<div class="col-sm-4 remove-pad">\
										<div class="input-group" style="max-width:90px">\
											<input class="form-control input-xs text-center text-muted" value="" placeholder="0" type="text" title="use cost basis" data-toggle="tooltip" data-placement="top" rel="tooltip">\
											<span class="input-group-addon"><i class="fa fa-percent" aria-hidden="true"></i></span>\
										</div>\
									</div>\
									<div class="col-sm-8 remove-pad text-right">\
										<div class="form-group" style="display:inline-block; width:50px">\
											<input type="text" class="form-control input-xs response-qty" name="response_qtys['+ln+']" value="" placeholder="0" title="qty" data-toggle="tooltip" data-placement="top" rel="tooltip">\
										</div>\
										<i class="fa fa-times fa-lg"></i>&nbsp;\
										<div class="form-group" style="width:125px">\
											<div class="input-group">\
												<span class="input-group-addon" aria-hidden="true"><i class="fa fa-usd"></i></span>\
												<input type="text" class="form-control input-xs response-price" name="response_prices['+ln+']" value="" placeholder="0.00" title="price" data-toggle="tooltip" data-placement="top" rel="tooltip">\
											</div>\
										</div>\
									</div>\
								</div>\
								<div class="row" style="margin-bottom:12px">\
									<div class="col-sm-3 remove-pad">\
										<span class="info">profit/markup</span>\
									</div>\
									<div class="col-sm-9 remove-pad text-right">\
										<span class="info">our quote/response</span>\
									</div>\
								</div>\
								<div class="row">\
									<div class="col-md-8 remove-pad select-xs">\
										<input class="form-control input-xs date_number" type="text" name="leadtime['+ln+']" placeholder="#" value="" style="max-width:50px">\
										<select class="form-control select2" name="leadtime_span['+ln+']" style="max-width:75px">\
											<option value="days">Days</option>\
											<option value="weeks">Weeks</option>\
											<option value="months">Months</option>\
										</select>\
										<span class="info" style="padding-left:8px; padding-right:8px">or</span>\
										<div class="form-group" style="max-width:200px;">\
											<div class="input-group datepicker-date date datetime-picker" style="min-width: 100%; width: 100%;" data-format="MM/DD/YYYY">\
												<input type="text" name="delivery_date['+ln+']" class="form-control input-xs delivery_date" value="" placeholder="mm/dd/yyyy">\
												<span class="input-group-addon">\
													<span class="fa fa-calendar"></span>\
												</span>\
											</div>\
										</div>\
									</div>\
									<div class="col-md-4 remove-pad">\
										<div class="row-total text-right pull-right" title="row total" data-toggle="tooltip" data-placement="top" rel="tooltip"><h5>$ 0.00</h5></div>\
									</div>\
								</div>\
								<div class="row" style="margin-bottom:12px">\
									<span class="info">delivery</span>\
								</div>\
							</td>\
						</tr>\
					';

					if (replaceNode!==false) {
						$("#chart_"+replaceNode).remove();
						$("#row_"+replaceNode).replaceWith(header_row);
						$("#items_"+replaceNode).replaceWith(items_row);
					} else {
						table.append(header_row);
						table.append(items_row);
					}

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
									up: '#5cb85c',
									down: '#5cb85c',
									unchanged: '#000',
								},
								backgroundColor: '#5cb85c',
								borderColor: '#4cae4c',
								fill: true,
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

				if (replaceNode!==false) {
					updateResults($("#items_"+replaceNode));
				} else {
					updateResults(table.find(".items-row"));
				}
			},
			complete: function(result) {
				table.find(".select2").select2();
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
	function buildItemRows(results,ln) {
					var rows = '';
//					partids = '';

					var notes,aliases,alias_str,edit,descr,part,mpart,chk,cls;

					$.each(results, function(pid, item) {
						cls = 'product-row row-'+item.id+' '+item.class;
						if (item.qty>0) { cls += ' in-stock'; }

						chk = '';
						if (item.class=='primary') { chk = ' checked'; }

						partid = item.id;
/*
						if (parseInt(partid)>0) {
							if (partids!='') { partids += ','; }
							partids += partid;
						}
*/
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

						rows += '\
									<tr class="'+cls+'" data-partid="'+partid+'" id="'+item.id+'-'+ln+'">\
										<td class="col-sm-1 colm-sm-0-5 text-center">\
											<input type="checkbox" name="items['+ln+']['+item.id+']" class="item-check" value="'+item.id+'"'+chk+'>\
											<a href="javascript:void(0);" class="fa '+item.fav+' fav-icon" data-toggle="tooltip" data-placement="right" title="Add/Remove as a Favorite" rel="tooltip"></a>\
										</td>\
										<td class="col-sm-1 text-center">\
											<input type="text" name="item_qtys['+ln+']['+item.id+']" class="form-control input-xs" value="'+item.qty+'" placeholder="Qty" title="Stock Qty" data-toggle="tooltip" data-placement="bottom" rel="tooltip"><br/>\
											<span class="info">'+item.vqty+'</span>\
										</td>\
										<td class="col-sm-9">\
											<div class="product-img">\
												<img src="/img/parts/'+item.primary_part+'.jpg" alt="pic" class="img" data-part="'+item.primary_part+'" />\
											</div>\
											<div class="product-details" style="display:inline-block; width:80%; font-size:11px">\
												'+part+aliases+notes+edit+'<br/><span class="info"><small>'+descr+'</small></span>\
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

		return rows;
	}

	jQuery.fn.marketResults = function(attempt,pricing) {
		if (! pricing && pricing!==0) { var pricing = $(this).data('pricing'); }
		var col = $(this);

		if (! attempt) { var attempt = 0; }
		if (attempt==0) { col.html(''); }

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
						avg_cost = json.avg_cost;
						$("#avg-cost-"+ln).val(avg_cost);
//						$("#avg-cost-"+ln).prop('readonly',true);
					}
				}

				dwnld = '';
				if (category=='Sale' && otype=='Supply') {
					dwnld = ' <a href="javascript:void(0);" class="lk-download"><i class="fa fa-circle-o-notch fa-spin"></i></a>';
				} else if (category=='Sale' && otype=='Purchase') {
					dwnld = ' <a href="javascript:void(0);" class=""><i class="fa fa-share-square"></i></a>';
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
						if (row.date!=last_date) {
							html += '<'+row.format+'>'+row.date+'</'+row.format+'>';

							last_date = row.date;
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
						if (otype=='Sale' || otype=='Purchase' || otype=='Repair' || otype=='Outsourced') {
							if (row.order_number!='') {
								price_ln = ' <a href="order.php?order_type='+otype+'&order_number='+row.order_number+'" target="_new"><i class="fa fa-arrow-right"></i></a>';
							} else if (otype=='Purchase') {
								price_ln = ' <a href="purchase_requests.php" target="_new"><i class="fa fa-arrow-right"></i></a>';
							}
						} else if (row.order_number) {
							price_ln = ' <a href="manage_quote.php?metaid='+row.order_number+'"><i class="fa fa-arrow-right"></i></a>';
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

							tr.find(".lk-download").html('<i class="fa fa-download"></i>');
//							tr.closest("table").find("#row_"+ln+" .market-header").html('market');
						}
					} else if (json.done==1 || ln>max_ln) {
						tr.find(".lk-download").html('<i class="fa fa-download"></i>');
//						tr.closest("table").find("#row_"+ln+" .market-header").html('market');
					}
				}
			},
		});
	};
</script>
<script src="js/contacts.js?id=<?php echo $V; ?>"></script>

</body>
</html>