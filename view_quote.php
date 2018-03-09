<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_product.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getSearch.php';

	$metaid = 0;
	if (isset($_REQUEST['metaid'])) { $metaid = $_REQUEST['metaid']; }

	if (! $metaid) {
		header('Location: market.php');
		exit;
	}

	$query = "SELECT *, m.id metaid FROM search_meta m WHERE m.id = '".res($metaid)."'; ";
	$result = qedb($query);
	if (qnum($result)==0) {
		header('Location: market.php');
		exit;
	}
	$ORDER = qrow($result);
	$metaid = $ORDER['metaid'];

	$list_type = '';
	$text_rows = '';
	$rows = '';
	$ln = false;

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

		if ($r['searchid'] AND $r['line_number']<>$ln) {
			$search = getSearch($r['searchid']);

			$text_rows .= '<strong>'.$search.'</strong><br>'.chr(10);
		}
		$ln = $r['line_number'];

		$descr = format_product($r['partid']);
		$price = '';
		if ($r['response_price']>0) {
			$price = format_price($r['response_price']);
			if ($qty>1) { $price .= ' ea'; }
		} else {
			$price = ' please make offer';
		}

		$text_rows .= ' qty '.$qty.'- '.$descr.' '.$price.'<br>'.chr(10);

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

	$textB = $text_rows;
	if ($text_rows) {
		if ($list_type=='Demand') {
			$textB = 'If you get a lower quote elsewhere, I’ll beat it by 10% (as long as the warranty is 30+ days)...<br>'.chr(10).'<br>'.chr(10).$textB;
			$text_rows = 'We have the following available:<br>'.chr(10).'<br>'.chr(10).$text_rows;
		} else if ($list_type=='Supply') {
			$text_rows = "I'm interested in the following:<br>".chr(10)."<br>".chr(10).$text_rows;
		}
	} else {
		header('Location: market.php');
		exit;
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
		.col-freeform {
			text-align:left;
			margin-bottom:80px;
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
			<a target="_blank" href="/docs/EQ<?=$metaid;?>.pdf" class="btn btn-default btn-sm"><i class="fa fa-file-pdf-o"></i></a>
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
		<div class="col-sm-4 text-left">
			<div class="col-freeform"><?=$textB;?></div>
		</div>
		<div class="col-sm-4 text-left">
<!--
			<textarea class="freeform-text"><?=$text_rows;?></textarea>
-->
			<div class="col-freeform"><?=$text_rows;?></div>
		</div>
		<div class="col-sm-4"> </div>
	</div>

	<table class="table table-condensed table-hover table-striped">
		<thead>
			<tr>
				<th>Ln#</th>
				<th>Qty</th>
				<th>Description</th>
				<th>Price</th>
				<th>Ext Total</th>
			</tr>
		</thead>
		<tbody>
			<?=$rows;?>
		</tbody>
	</table>

</form>
</div><!-- pad-wrapper -->

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<script type="text/javascript">
	$(document).ready(function() {
		$(".col-freeform").on('click',function() {
			$(this).selectText();
		});

		jQuery.fn.selectText = function(){
			var doc = document;
			var element = this[0];
//			console.log(this, element);
			if (doc.body.createTextRange) {
				var range = document.body.createTextRange();
				range.moveToElementText(element);
				range.select();
			} else if (window.getSelection) {
				var selection = window.getSelection();        
				var range = document.createRange();
				range.selectNodeContents(element);
				selection.removeAllRanges();
				selection.addRange(range);
			}
		};
	});
</script>

</body>
</html>
