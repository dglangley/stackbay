<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	function getClasses() {
		$classes = array();

		$query = "SELECT * FROM service_classes";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$classes[] = $r;
		}

		return $classes;
	}

	function buildClassRows() {
		$htmlRows= '';
		$classes = getClasses();

		foreach($classes as $class) {
			$htmlRows .= '<tr>
							<td>'.$class['class_name'].'</td>
							<td>
								'.checkClassUsage($class['id']).'
							</td>
						  </tr>';
		}
		
		return $htmlRows;
	}

	function checkClassUsage($classid) {
		// Default set action row to the delete
		$htmlAction = '<a href="class_edit.php?classid='.$classid.'" class="pull-right" onclick="return confirm(\'Are you sure you want to delete this class?\')"><i class="fa fa-trash fa-4" style="margin-right: 10px; margin-top: 4px;" aria-hidden="true"></i></a>';

		$query = "SELECT * FROM user_classes WHERE classid = ".res($classid).";";
		$result = qedb($query);

		if(mysqli_num_rows($result) > 0) {
			$htmlAction = '';
		}

		$query = "SELECT * FROM service_orders WHERE classid = ".res($classid).";";
		$result = qedb($query);

		if(mysqli_num_rows($result) > 0) {
			$htmlAction = '';
		}

		$query = "SELECT * FROM service_quotes WHERE classid = ".res($classid).";";
		$result = qedb($query);

		if(mysqli_num_rows($result) > 0) {
			$htmlAction = '';
		}

		return $htmlAction;
	}

	// print_r(getClasses());

	$TITLE = 'Class Management';
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
		<div class="col-sm-2">
		</div>
		<div class="col-sm-2">
		</div>
		<div class="col-sm-4 text-center">
			<h2 class="minimal"><?php echo $TITLE; ?></h2>
			<span class="info"></span>
		</div>
		<div class="col-sm-2">
		</div>
		<div class="col-sm-2">
		</div>
	</div>

	</form>
</div>

<div id="pad-wrapper">
	<form class="form-inline" method="get" action="class_edit.php" enctype="multipart/form-data" >
		<table class="table heighthover heightstriped table-condensed">
			<thead>
				<tr>
					<th>Class</th>
					<th class="text-right">Action</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>
						<div class="input-group pull-left">
							<input type="text" class="form-control input-sm" name="class_name" placeholder="Class Name">
							<span class="input-group-btn">
								<button class="btn btn-success btn-sm pull-right" name="type" value="add_expense"><i class="fa fa-save" aria-hidden="true"></i></button>
							</span>
						</div>
					</td>
				</tr>
				<?=buildClassRows();?>		
			</tbody>
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
