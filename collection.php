<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrder.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/display_part.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';

	$order_number = 0;
	$order_type = '';
	if (isset($_REQUEST['order_number'])) { $order_number = trim($_REQUEST['order_number']); }
	if (isset($_REQUEST['order_type'])) { $order_type = trim($_REQUEST['order_type']); }

	$T = order_type($order_type);
	$ORDER = getOrder($order_number,$order_type);

	foreach ($ORDER['items'] as $item) {
		$descr = '';
		if (array_key_exists('partid',$item) OR (array_key_exists('item_id',$item) AND array_key_exists('item_label',$item) AND $item['item_label']=='partid')) {
			// convert item_id/item_label pair to partid
			if (! array_key_exists('partid',$item)) { $item['partid'] = $item['item_id']; }

			$descr = display_part(current(hecidb($item['partid'], 'id')));
		} else if (array_key_exists('item_id',$item) AND array_key_exists('item_label',$item) AND $item['item_label']=='addressid') {
			$descr = 'addressid '.$item['item_id'];
		}

		$rows .= '
			<tr class="valign-top">
				<td>'.$item['line_number'].'</td>
				<td>'.$descr.'</td>
				<td>'.$item['qty'].'</td>
				<td>'.format_price($item[$T['amount']]).'</td>
			</tr>
		';

		$C = order_type($T['collection']);
		$query = "SELECT * FROM ".$T['collection']." WHERE order_number = '".res($order_number)."' AND order_type = '".res($order_type)."'; ";
		$result = qedb($query);
		while ($r = qrow($result)) {
			$query2 = "SELECT * FROM ".$C['items']." WHERE invoice_no = '".$r['invoice_no']."' AND taskid = '".$item['id']."' AND task_label = '".$T['item_label']."'; ";
			$result2 = qedb($query2);
			while ($r2 = qrow($result2)) {
				$qty = 0;

				$query3 = "SELECT * FROM ".strtolower($T['collection_title'])."_shipments s, packages p ";
				$query3 .= "WHERE ".$C['item_label']." = '".$r2['id']."' AND s.packageid = p.id; ";
				$result3 = qedb($query3);
				while ($r3 = qrow($result3)) {
					$query4 = "SELECT * FROM package_contents pc, inventory i ";
					$query4 .= "WHERE pc.packageid = '".$r3['packageid']."' AND pc.serialid = i.id; ";
					$result4 = qedb($query4);
					while ($r4 = qrow($result4)) {
						$qty = $r4['qty'];
					}

					$rows .= '
			<tr>
				<td colspan=4>
					<div class="row">
						<div class="col-sm-2"> </div>
						<div class="col-sm-4">'.$r2['invoice_no'].' '.$r2['qty'].' '.$r2['amount'].'</div>
						<div class="col-sm-4">package '.$r3['packageid'].' '.$r3['datetime'].' </div>
						<div class="col-sm-4">'.$qty.'</div>
					</div>
				</td>
			</tr>
					';
				}
			}
		}
	}

	$TITLE = 'New '.$T['collection_title'].' for '.$T['abbrev'].' '.$order_number;
	$SUBTITLE = '';
?>
<!DOCTYPE html>
<html>
<head>
	<title><?=$TITLE;?></title>
	<?php
		/*** includes all required css includes ***/
		include_once $_SERVER["ROOT_DIR"].'/inc/scripts.php';
	?>

	<!-- any page-specific customizations -->
	<style type="text/css">
	</style>
</head>
<body>

<?php include_once $_SERVER["ROOT_DIR"].'/inc/navbar.php'; ?>

<!-- FILTER BAR -->
<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px; max-height:60px;">
	<form class="form-inline" method="get" action="" enctype="multipart/form-data" id="filters-form">

	<div class="row" style="padding:8px">
		<div class="col-sm-1">
		</div>
		<div class="col-sm-1">
		</div>
		<div class="col-sm-1">
		</div>
		<div class="col-sm-1">
		</div>
		<div class="col-sm-4 text-center">
			<h2 class="minimal"><?=$TITLE;?></h2>
			<span class="info"><?=$SUBTITLE;?></span>
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

<?php include_once $_SERVER["ROOT_DIR"].'/sidebar.php'; ?>

<div id="pad-wrapper">
<form class="form-inline" method="get" action="" enctype="multipart/form-data" >

<div class="table-responsive">
	<table class="table table-condensed table-striped table-hover">
		<thead>
			<tr>
				<th class="col-sm-1">Ln</th>
				<th class="col-sm-9">Description of Charges</th>
				<th class="col-sm-1">Qty</th>
				<th class="col-sm-1">Price</th>
			</tr>
		</thead>
		<tbody>
			<?=$rows;?>
		</tbody>
	</table>
</div>

</form>
</div><!-- pad-wrapper -->

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<script type="text/javascript">
	$(document).ready(function() {
	});
</script>

</body>
</html>
