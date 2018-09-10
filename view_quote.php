<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_product.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_part.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getSearch.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPart.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getWarranty.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	$metaid = 0;
	if (isset($_REQUEST['metaid'])) { $metaid = $_REQUEST['metaid']; }

	if (! $metaid) {
		header('Location: market.php');
		exit;
	}

	if (! isset($VIEW)) { $VIEW = false; }

	$query = "SELECT *, m.id metaid FROM search_meta m WHERE m.id = '".res($metaid)."'; ";
	$result = qedb($query);
	if (qnum($result)==0) {
		header('Location: market.php');
		exit;
	}
	$ORDER = qrow($result);
	$metaid = $ORDER['metaid'];

	$warrantyid = getDefaultWarranty($ORDER['companyid']);

	$qtys = array();
	$search_text = '';
	$list_type = '';
	$text_rows = '';
	$rows = '';
	$ln = false;
	$num_lines = 0;

	if ($ORDER['searchlistid']) {
		$query2 = "SELECT search_text FROM search_lists WHERE id = '".res($ORDER['searchlistid'])."'; ";
		$result2 = qedb($query2);
		if (qnum($result2)>0) {
			$r2 = qrow($result2);
			$search_text = $r2['search_text'];
			$search_lines = explode(chr(10),$r2['search_text']);
			$num_lines = count($search_lines);
		}
	}

	$types = array('Demand'=>'Sales Quote','Supply'=>'Purchase Offer','Repair Quote'=>'Repair Quote','Repair Vendor'=>'Repair Service');
	foreach ($types as $type => $title) {
		$T = order_type($type);

		$T['amount'] = str_replace('avail_price','offer_price',$T['amount']);

		$query = "SELECT partid, ".$T['qty']." qty, ".$T['amount']." response_price, searchid, line_number, ";
		if ($type=='Demand') { $query .= "quote_qty "; } else if ($type=='Supply') { $query .= "offer_qty "; } else { $query .= "'' "; }
		$query .= "response_qty ";
		$query .= "FROM ".$T['items']." WHERE metaid = '".$metaid."' ";
		$query .= "AND (".$T['amount']." > 0 ";
		if ($type=='Demand') { $query .= "OR quote_qty > 0 "; } else if ($type=='Supply') { $query .= "OR offer_qty > 0 "; }
		$query .= ") ";
		$query .= "ORDER BY line_number ASC, id ASC; ";
		$result = qedb($query);
		if (qnum($result)>0) {
			$list_type = $type;
			$TITLE = $title.' '.$metaid;
			break;
		}
	}
/*
	if (qnum($result)>0) {
	} else {
		$query = "SELECT partid, avail_qty qty, avail_price target, offer_qty response_qty, offer_price response_price, searchid, line_number ";
		$query .= "FROM availability WHERE metaid = '".$metaid."' AND (offer_qty > 0 OR offer_price > 0) ";
		$query .= "ORDER BY line_number ASC, id ASC; ";
	}
*/
	// pre-process results so we can determine if qtys are all '1' (in which case we won't show them), or varied (in which case we will)
	$results = array();
	while ($r = qrow($result)) {
		$qtys[$r['qty']] = true;

		$results[] = $r;
	}

	foreach ($results as $r) {
		$qty = $r['response_qty'];
		if (! $qty) { $qty = 1; }

		$search_qty = '';
		if (count($qtys)>1) { $search_qty = $r['qty']; }

		if ($r['line_number']<>$ln) {
			if ($r['searchid']) {
				$search = getSearch($r['searchid']);

				$text_rows .= '<strong>'.$search.' &nbsp; &nbsp; &nbsp; '.$search_qty.'</strong><br>'.chr(10);
			} else {
				$text_rows .= '<strong>'.format_part(getPart($r['partid'],'part')).' &nbsp; &nbsp; &nbsp; '.$search_qty.'</strong><br>'.chr(10);
			}
		}
		$ln = $r['line_number'];

		$descr = format_product($r['partid']);
		$price = '';
		if ($r['response_price']>0) {
			$price = format_price($r['response_price']);
			if ($qty>1) { $price .= ' ea'; }
		} else if ($list_type=='Demand') {
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
				<td class="col-sm-1 text-right">
					'.$r['response_price'].'
				</td>
				<td class="col-sm-1 text-right">
					'.number_format(($qty*$r['response_price']),2,'.',',').'
				</td>
			</tr>
		';
	}

	$textB = $text_rows;
	$suffix = '';
	if ($text_rows) {
		$warranty = getWarranty($warrantyid,'warranty');
		$terms = 'Includes warranty of '.str_replace(' ','-',strtolower($warranty)).'. All items are in stock and ready to ship immediately unless otherwise noted. '.
			'Shipping cut-off time is 6:30pm EST. ';

		if ($list_type=='Demand') {
			$textB = 'If you get a lower quote elsewhere, I\'ll beat it by 10% (quote must be from a domestic supplier with genuine equipment, no lead-time, and 30+ days warranty)...<br>'.
				chr(10).'<br>'.chr(10).$textB.'<br>'.chr(10);
			$text_rows = 'We have the following available:<br>'.chr(10).'<br>'.chr(10).$text_rows.'<br>'.chr(10);
			$suffix = '*Indicates item is original OEM equivalent marked as quoted.';

			$text_rows .= $terms;
			$textB .= $terms;
		} else if ($list_type=='Supply') {
			$text_rows = "I'm interested in the following:<br>".chr(10)."<br>".chr(10).$text_rows;
		} else if ($list_type=='Repair Quote') {
			$text_rows = "We can repair the following:<br>".chr(10)."<br>".chr(10).$text_rows."<br>".chr(10)."Our repair warranty is 1-year, and our standard turn-around time is 30 days.";
		}
	} else if (! $VIEW) {
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
		.email-text {
			background-color:white;
		}
		.email-text div {
			font-size:13px;
			font-family: Helvetica, 'Open Sans', sans-serif;
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
		<div class="col-sm-2 text-right">
			<div class="dropdown pull-right">
				<button class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"><i class="fa fa-chevron-down"></i></button>
				<ul class="dropdown-menu pull-right text-left" role="menu">
					<li><a href="market.php?metaid=<?=$metaid;?>" class="btn-market"><i class="fa fa-cubes"></i> Open in Market</a></li>
				</ul>
			</div>
		</div>
	</div>

	</form>
</div>

<?php
	include_once $_SERVER["ROOT_DIR"].'/sidebar.php';
?>

<div id="pad-wrapper">
<form class="form-inline" method="get" action="" enctype="multipart/form-data" >

	<div class="row email-text">
		<div class="col-sm-4 text-left">
			<div class="col-freeform"><?=$textB;?></div>
		</div>
		<div class="col-sm-4 text-left">
<!--
			<textarea class="freeform-text"><?=$text_rows;?></textarea>
-->
			<div class="col-freeform"><?=$text_rows;?></div>
		</div>
		<div class="col-sm-4"><?= $suffix; ?></div>
	</div>

	<table class="table table-condensed table-hover table-striped">
		<thead>
			<tr>
				<th>Ln#</th>
				<th>Qty</th>
				<th>Description</th>
				<th class="text-center">Price</th>
				<th class="text-center">Ext Total</th>
			</tr>
		</thead>
		<tbody>
			<?=$rows;?>
		</tbody>
	</table>

	<br/><br/>
	<span class="info"><h4>Search text:</h4></span>
	<textarea rows="<?=$num_lines;?>" cols="300" style="width:100%"><?=$search_text;?></textarea>

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
