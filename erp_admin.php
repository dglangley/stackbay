<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';

	require_once $_SERVER["ROOT_DIR"].'/inc/dbsync.php';

	function getDatabases() {
		$databases = array();
		// Need to create a way to track all the databases
		$query = "SELECT * FROM erp ORDER BY id DESC;";
		$result = qedb($query);

		while($r = qrow($result)) {
			$databases[] = $r;
		}

		return $databases;
	}

	function getDatabase($databaseid) {
		$database = array();
		// Need to create a way to track all the databases
		$query = "SELECT * FROM erp WHERE id = ".res($databaseid).";";
		$result = qedb($query);

		while($r = qrow($result)) {
			$database[] = $r;
		}

		return $database;
	}

	function buildHTML($databases, $databaseid) {
		$htmlRows = '';

		if($databaseid) {
			// Load data for this specific database
			$database = getDatabase($databaseid);

			// Within the database what options should we allow?
			$htmlRows .= '
				<div></div>
			';
		} else {
			// Load data for all data
			$htmlRows .= '
				<table class="table heighthover heightstriped table-striped table-hover">
					<thead>
						<tr>
							<th>Namespace</th>
							<th>Company</th>
							<th class="text-right">Action</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>
								<input type="text" class="form-control input-sm" name="database" placeholder="http://(name).stackbay.com">
							</td>
							<td>
								<input type="text" class="form-control input-sm" name="company" placeholder="Company Name">
							</td>
							<td>
								<button class="btn btn-success btn-sm pull-right button_save" name="type" value="add_expense"><i class="fa fa-save" aria-hidden="true"></i></button>
							</td>
						</tr>
			';

			// DAVID LOOK HERE TO SET YOUR LOCAL HOST VARIABLE CORRECTLY
			foreach($databases as $r) {
				$htmlRows .= '
					<tr>
						<td>
							'.$r['namespace'].'
						</td>
						<td>
							'.$r['company'].'
						</td>
						<td>
							<div class="dropdown pull-right">
								<a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-chevron-down"></i></a>
								<ul class="dropdown-menu text-left" role="menu" data-inventoryids="41711">
									<li><a target="_blank" href="http://'.$r['namespace'].'.'.$_SERVER["SERVER_NAME"].($GLOBALS['DEV_ENV'] ? ':8888':'').'"><i class="fa fa-arrow-right" aria-hidden="true"></i> Go to Site</a></li>
									<li><a href="#" class="reset_db" data-dbid="'.$r['namespace'].'" data-company="'.$r['company'].'" style="margin-right: 10px;"><i class="fa fa-refresh" aria-hidden="true"></i> Reset</a></li>
									<li><a href="#" class="a_edit" style="margin-right: 10px;"><i class="fa fa-pencil" aria-hidden="true"></i> Edit</a></li>
								</ul>
							</div>
							
						</td>
					</tr>
				';
			}

			$htmlRows .= '	
					</tbody>
				</table>
			';
		}

		return $htmlRows;
	}

	$databaseid = (isset($_REQUEST['databaseid']) ? : '');
	$databases = array();

	$database = array();

	if(! $databaseid) {
		$databases = getDatabases();
	} else {
		$database = getDatabase($databaseid);
	}

	$htmlRows = buildHTML($databases, $databaseid);

	$TITLE = 'ERP Administration';
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
	<div class="col-md-12">
		<div class="content-wrap">
			<h3 class="pb-20"><?=($databaseid ? getCompany(reset($database)['companyid']) . ' Dashboard': '');?></h3>
			<form action="/erp_edit.php" id="erp_submit" method="get">
				<?=$htmlRows;?>
			</form>
		</div>
	</div>
</div><!-- pad-wrapper -->

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<script type="text/javascript">
	$(document).ready(function() {
		$('#loader').hide();

		$('.button_save').click(function(e){
			e.preventDefault();

			// Check for missing stuff
			if(! $('input[name="database"]').val() || ! $('input[name="company"]').val()) {
				modalAlertShow("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning", "Namespace and company required. <br><br>If this message appears to be in error, please contact an Admin.");
			} else {
				$('#loader-message').html('Please wait while AMEA generates the new database...<BR><BR> While we wait please checked if the subdomain needs to added as an alias. <BR><BR> Make yourself a hot cup of coffee and enjoy a funny conversation.');
				$('#loader').show();

				$('#erp_submit').submit();
			}
		});

		$('.a_edit').click(function(e){
			e.preventDefault();

			modalAlertShow("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> It's a Rainy Day Here", "Bummer you found a missing feature. This still needs some TLC to complete, but sometimes a little patience leads to excellence.");
		});

		$('.reset_db').click(function(e){
			e.preventDefault();

			var namespace = $(this).data("dbid");
			var value = $(this).data("company");

			modalAlertShow("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning","Company: "+value+" / DB:"+namespace+"<BR><BR>Please confirm that you want to reset this DB. <BR><BR> Remember this is can not be undone!",true,'resetDB',$(this));

			// modalAlertShow("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> It's a Rainy Day Here", "Bummer you found a missing feature. This still needs some TLC to complete, but sometimes a little patience leads to excellence.");
		});
	});

	function resetDB(e){
		var namespace = e.data("dbid");
		var input = $("<input>").attr("type", "hidden").attr("name", "reset").val(namespace);

		$("#erp_submit").append(input);

		var value = e.data("company");
		$('input[name="company"]').val(value);

		$('#loader-message').html('Please wait while AMEA resets the database...<BR><BR> Don\'t blink this process is blazing fast!');
		$('#loader').show();

		$('#erp_submit').submit();
	}
</script>

</body>
</html>
