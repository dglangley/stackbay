<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_product.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';

	$slid = 0;
	if (isset($_REQUEST['slid'])) { $slid = $_REQUEST['slid']; }

	$query = "SELECT *, m.id metaid FROM search_meta m, search_lists l WHERE l.id = '".res($slid)."' AND l.id = searchlistid; ";
	$result = qedb($query);
	if (mysqli_num_rows($result)==0) {
		header('Location: market.php');
		exit;
	}
	$ORDER = qrow($result);
	$metaid = $ORDER['metaid'];

	$list_type = '';
	$text_rows = '';
	$rows = '';

	$query = "SELECT partid, request_qty qty, request_price target, quote_qty response_qty, quote_price response_price, searchid, line_number ";
	$query .= "FROM demand WHERE metaid = '".$metaid."' AND (quote_qty > 0 OR quote_price > 0) ";
	$query .= "ORDER BY line_number ASC, id ASC; ";
	$result = qedb($query);
	if (qnum($result)>0) {
		$list_type = 'Demand';
		$TITLE = 'Sales Quote';
	} else {
		$query = "SELECT partid, avail_qty qty, avail_price target, offer_qty response_qty, offer_price response_price, searchid, line_number ";
		$query .= "FROM availability WHERE metaid = '".$metaid."' AND (offer_qty > 0 OR offer_price > 0) ";
		$query .= "ORDER BY line_number ASC, id ASC; ";
		$result = qedb($query);
		if (qnum($result)>0) {
			$list_type = 'Supply';
			$TITLE = 'Purchase Offer';
		}
	}
	while ($r = qrow($result)) {
		$qty = $r['response_qty'];
		if (! $qty) { $qty = 1; }

		if ($r['searchid']) {
			$search = getSearch($r['searchid']);

			$text_rows .= $search.chr(10);
		}

		$descr = format_product($r['partid']);
		$price = '';
		if ($r['response_price']>0) {
			$price = format_price($r['response_price']);
			if ($qty>1) { $price .= ' ea'; }
		} else {
			$price = ' please make offer';
		}

		$text_rows .= ' qty '.$qty.'- '.$descr.' '.$price.chr(10);

		$rows .= '
			<tr>
				<td class="col-sm-1 colm-sm-0-5">
					<span class="info">'.$r['line_number'].'.</span>
				</td>
				<td class="col-sm-1 colm-sm-0-5">
					'.$qty.'
				</td>
				<td class="col-sm-8">
					'.$descr.'
				</td>
				<td class="col-sm-1">
					'.$r['response_price'].'
				</td>
				<td class="col-sm-1">
					'.($qty*$r['response_price']).'
				</td>
			</tr>
		';
	}

	if ($text_rows) {
		$text_rows = 'We have the following available:'.chr(10).chr(10).$text_rows;
	}
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

<?php
	include_once $_SERVER["ROOT_DIR"].'/sidebar.php';
?>

<div id="pad-wrapper">
<form class="form-inline" method="get" action="" enctype="multipart/form-data" >

	<div class="row">
		<div class="col-sm-4"> </div>
		<div class="col-sm-4 text-center">
			<textarea class="freeform-text"><?=$text_rows;?></textarea>
		</div>
		<div class="col-sm-4"> </div>
	</div>

	<table class="table table-condensed table-hover table-striped">
			<?=$rows;?>
	</table>

</form>
</div><!-- pad-wrapper -->

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<script type="text/javascript">
	$(document).ready(function() {
	});
</script>

</body>
</html>
