<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';

	require_once $_SERVER["ROOT_DIR"].'/inc/dbsync.php';

	$dbSync = new DBSync;

	// Set the DB for what will be used... For this Instance we will use vmmdb or the current one so its more universal
	// Eventually we need to convert it over to the corresponding host that will have the dummy data

	// Host, User, Pass, Name
	$dbSync->setDBOneConnection($_SERVER['RDS_HOSTNAME'],  $_SERVER['RDS_USERNAME'], $_SERVER['RDS_PASSWORD'], $_SERVER['DEFAULT_DB']);

	// Host user etc will be the same thanks to David
	// Only change is db name at the end...
	$dbSync->setDBTwoConnection($_SERVER['RDS_HOSTNAME'],  $_SERVER['RDS_USERNAME'], $_SERVER['RDS_PASSWORD'], 'test');

	$dbSync->matchTables();

	function getDatabases() {
		$databases = array();
		// Need to create a way to track all the databases
		$query = "SELECT * FROM erp;";
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
				<table class="table heighthover heightstriped table-condensed">
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
								<button class="btn btn-success btn-sm pull-right" name="type" value="add_expense"><i class="fa fa-save" aria-hidden="true"></i></button>
							</td>
						</tr>
			';

			foreach($databases as $r) {
				$htmlRows .= '
					<tr>
						<td>
							'.$r['namespace'].'
						</td>
						<td>
							'.getCompany($r['companyid']).'
						</td>
						<td>
							<a href="http://'.$r['namespace'].'.Stackbay.com" class="pull-right"><i class="fa fa-arrow-right" aria-hidden="true"></i></a>
							<a href="#" class="pull-right" style="margin-right: 10px;"><i class="fa fa-pencil" aria-hidden="true"></i></a>
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
			<h3 class="pb-20"><?=($databaseid ? getCompany(reset($database)['companyid']) . ' Dashboard': 'Company Databases');?></h3>
			<form action="/admin_edit.php" method="get">
				<?=$htmlRows;?>
			</form>
		</div>
	</div>
</div><!-- pad-wrapper -->

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<script type="text/javascript">
	$(document).ready(function() {
	});
</script>

</body>
</html>
