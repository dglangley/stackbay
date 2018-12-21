<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$TITLE = '';
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
		<div class="col-sm-2">
		</div>
		<div class="col-sm-2 text-center">
			<h2 class="minimal"><?=$TITLE;?></h2>
			<span class="info"><?=$SUBTITLE;?></span>
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

</form>
</div><!-- pad-wrapper -->

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<script type="text/javascript">
	$(document).ready(function() {
	});
</script>

</body>
</html>
