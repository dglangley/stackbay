<?php
	include_once $_SERVER["ROOT_DIR"] . '/inc/dbconnect.php';

?>

<!------------------------------------------------------------------------------------------->
<!-------------------------------------- HEADER OUTPUT -------------------------------------->
<!------------------------------------------------------------------------------------------>
<!DOCTYPE html>
<html>
<head>
	<title><?=$title?></title>
	<?php
		// Standard headers included in the function
		// include_once $_SERVER["ROOT_DIR"] .'/inc/scripts.php';
		// include_once $_SERVER["ROOT_DIR"] . '/modal/image.php';
	?>
	<style>
	</style>
</head>

<body class="sub-nav accounts-body">
	
	<?php include 'inc/navbar.php'; ?>

	<form action="" method="POST">

		<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px;">
			<div class="row" style="padding: 8px;" id="filterBar">
				<div class="col-md-4 mobile-hide" style="max-height: 30px;">
					<div class="col-md-3">
						<div class="btn-group medium">
					        <button data-toggle="tooltip" name="filter" type="submit" data-value="active" data-placement="bottom" title="" data-filter="active_radio" data-original-title="Active" class="btn btn-default btn-sm left filter_status active btn-warning">
					        	<i class="fa fa-sort-numeric-desc"></i>	
					        </button>

					        <button data-toggle="tooltip" name="filter" type="submit" data-value="completed" data-placement="bottom" title="" data-filter="complete_radio" data-original-title="Completed" class="btn btn-default btn-sm middle filter_status ">
					        	<i class="fa fa-history"></i>	
					        </button>

							<button data-toggle="tooltip" name="filter" type="submit" data-value="all" data-placement="bottom" title="" data-filter="all_radio" data-original-title="All" class="btn btn-default btn-sm right filter_status ">
					        	All
					        </button>
					    </div>
					</div>

					<div class="col-md-9 date_container mobile-hid remove-pad">
						
					</div>
				</div>

				<div class="text-center col-md-4 remove-pad">
					<h2 class="minimal" id="filter-title"><?=$title?></h2>
				</div>

				<div class="col-md-4" style="padding-left: 0; padding-right: 10px;">
					<div class="col-md-2 col-sm-2 phone-hide" style="padding: 5px;">

					</div>
					<div class="col-md-2 col-sm-2 col-xs-3">

					</div>

					<div class="col-md-8 col-sm-8 col-xs-9 remove-pad">
						<button class="btn btn-success btn-sm save_sales pull-right" type="submit" data-validation="left-side-main" style="padding: 5px 25px;">
							CREATE					
						</button>
					</div>
				</div>
			</div>
		</div>
		<div id="pad-wrapper">

			<div class="row">
				<table class="table heighthover heightstriped table-condensed p_table">
					<thead>
						<tr>

						</tr>
					</thead>
					<tbody>
						
					</tbody>
		        </table>
			</div>
		</div>
	</form>

	<?php include_once 'inc/footer.php'; ?>

    <script type="text/javascript">
    </script>

</body>
</html>
