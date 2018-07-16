<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	function getClasses() {
		global $classid;

		$classes = array();

		if (! $classid) { $classes[0] = array('class_name'=>'','travel_rate'=>''); }

		$query = "SELECT * FROM service_classes";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$classes[] = $r;
		}

		return $classes;
	}

	function buildClassRows() {
		global $classid;

		$htmlRows= '';
		$classes = getClasses();

/*
		$htmlRows .= '
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
		';
*/

		foreach($classes as $class) {
			$c1 = $class['class_name'];
			$c2 = $class['travel_rate'];
			$c3 = checkClassUsage($class['id']);

			// when editing/adding a class
			if ($class['id']==$classid) {
				$c1 = '<input type="text" class="form-control input-sm" name="class_name" value="'.$class['class_name'].'" placeholder="Class Name">';
				$c2 = '<input type="text" class="form-control input-sm" name="travel_rate" value="'.$class['travel_rate'].'" placeholder="11.00">';

				$c3 = '';
				if ($classid) {
					$c3 = '<a class="text-danger" href="class_management.php" title="Cancel" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-close fa-lg"></i></a> &nbsp; '.
						'<input type="hidden" name="classid" value="'.$classid.'">';
				}
				$c3 .= '<button class="btn btn-success btn-sm" name="" type="submit"><i class="fa fa-save" aria-hidden="true"></i></button>';
			}

			$htmlRows .= '
					<tr>
						<td>'.$c1.'</td>
						<td>'.$c2.'</td>
						<td class="text-center">'.$c3.'</td>
					</tr>
			';
		}
		
		return $htmlRows;
	}

	function checkClassUsage($classid) {
		if (! $classid) { return (''); }

		// Default set action row to the delete
		$htmlAction = '<a href="class_edit.php?delid='.$classid.'" onclick="return confirm(\'Are you sure you want to delete this class?\')"><i class="fa fa-trash fa-4" style="margin-right: 10px; margin-top: 4px;" aria-hidden="true"></i></a>';

		$query = "SELECT * FROM user_classes WHERE classid = ".res($classid).";";
		$result = qedb($query);

		if(mysqli_num_rows($result) > 0) {
			$htmlAction = '';
		} else {
			$query = "SELECT * FROM service_orders WHERE classid = ".res($classid).";";
			$result = qedb($query);

			if(mysqli_num_rows($result) > 0) {
				$htmlAction = '';
			} else {
				$query = "SELECT * FROM service_quotes WHERE classid = ".res($classid).";";
				$result = qedb($query);

				if(mysqli_num_rows($result) > 0) {
					$htmlAction = '';
				}
			}
		}

		$htmlAction .= ' <a href="class_management.php?classid='.$classid.'"><i class="fa fa-pencil"></i></a>';

		return ($htmlAction);
	}

	$classid = 0;
	if (isset($_REQUEST['classid']) AND is_numeric($_REQUEST['classid']) AND $_REQUEST['classid']>0) { $classid = trim($_REQUEST['classid']); }

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
		<div class="col-md-2">
            <?php include_once 'inc/user_dash_sidebar.php'; ?>
        </div>

        <div class="col-md-8">
			<table class="table heighthover heightstriped table-condensed">
				<thead>
					<tr>
						<th class="col-sm-7 text-center">Class</th>
						<th class="col-sm-4 text-center">Travel Rate</th>
						<th class="col-sm-1 text-center">Action</th>
					</tr>
				</thead>
				<tbody>
					<?=buildClassRows();?>		
				</tbody>
	        </table>
	    </div>
        <div class="col-md-2">
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
