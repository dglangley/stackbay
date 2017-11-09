<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$TITLE = '';
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

<div id="pad-wrapper">
<form class="form-inline" method="get" action="" enctype="multipart/form-data" >

<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/buildDescrCol.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setInputSearch.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getItems.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/detectDefaultType.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	$T = order_type('service_quote');

	$EDIT = true;

	$P = array();
	$id = false;
	$items = getItems($T['item_label']);
	$def_type = detectDefaultType($items);
?>

	<div class="row">
		<div class="col-md-4 part-container">
			<?= buildDescrCol($P,$id,$def_type,$items); ?>
			<?= setInputSearch($def_type); ?>
		</div>
	</div>
</form>
</div><!-- pad-wrapper -->

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>


<?php include_once $_SERVER["ROOT_DIR"].'/modal/address.php'; ?>

<script src="js/part_search.js?id=<?php echo $V; ?>"></script>
<script src="js/addresses.js?id=<?php echo $V; ?>"></script>
<script src="js/item_search.js?id=<?php echo $V; ?>"></script>


<script type="text/javascript">
	$(document).ready(function() {
	});
</script>

</body>
</html>
