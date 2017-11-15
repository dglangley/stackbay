<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$TITLE = 'Address Uploader';
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
		.table-horizontal {
			max-width:100%;
			overflow-x:scroll;
		}
		.table-spreadsheet tr th,
		.table-spreadsheet tr td {
			position:relative;
			min-width:100px;
			max-width:300px;
			vertical-align:top;
		}
		.table-spreadsheet tr td div {
			overflow:hidden;
			text-overflow:ellipsis;
			white-space:nowrap;
		}
/*
		.table-spreadsheet tr td:hover div {
			overflow-y:scroll;
			background-color:#fcfcfc;
			border:1px solid #fafafa;
			padding:3px;
		}
*/
	</style>
</head>
<body>

<?php include_once 'inc/navbar.php'; ?>

<form class="form-inline" method="POST" action="save-address.php" enctype="multipart/form-data" >

<!-- FILTER BAR -->
<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px; max-height:60px;">

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
		<div class="col-sm-2 text-right">
			<button type="submit" class="btn btn-md btn-success"><i class="fa fa-upload"></i> Complete Import</button>
		</div>
	</div>

</div>

<div id="pad-wrapper">

<?php
	$filename = '';
	if (isset($_REQUEST['filename'])) { $filename = $_REQUEST['filename']; }
	$companyid = 0;
	if (isset($_REQUEST['companyid'])) { $companyid = $_REQUEST['companyid']; }
?>
	<input type="hidden" name="filename" value="<?= $filename; ?>">
	<input type="hidden" name="companyid" value="<?= $companyid; ?>">

<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/getSpreadsheetRows.php';
	$lines = getSpreadsheetRows($TEMP_DIR.$filename);

	$rows = '';
	$options = '';
	foreach ($lines as $i => $line) {
		$cols = '';
		foreach ($line as $k => $col) {
			if ($i==0) {
				$options .= '<option value="'.$k.'">'.$col.'</option>'.chr(10);
				$cols .= '<th>'.$col.'</th>'.chr(10);
			} else {
				$cols .= '<td><div>'.$col.'</div></td>'.chr(10);
			}
		}
		$rows .= '
		<tr>
			'.$cols.'
		</tr>
		';

		if ($i==20) { break; }//we don't need more for purpose of sampling
	}
?>


	<table class="table table-striped table-hover table-condensed">
		<tr>
			<th class="col-md-1">
				<select name="company_name" size="1" class="form-control input-sm select2">
					<option value="">- Company Name -</option>
					<?= $options; ?>
				</select>
			</th>
			<th class="col-md-1">
				<select name="street" size="1" class="form-control input-sm select2">
					<option value="">- Street Address -</option>
					<?= $options; ?>
				</select>
			</th>
			<th class="col-md-1">
				<select name="addr2" size="1" class="form-control input-sm select2">
					<option value="">- Addr2 -</option>
					<?= $options; ?>
				</select>
			</th>
			<th class="col-md-1">
				<select name="city" size="1" class="form-control input-sm select2">
					<option value="">- City -</option>
					<?= $options; ?>
				</select>
			</th>
			<th class="col-md-1">
				<select name="state" size="1" class="form-control input-sm select2">
					<option value="">- State -</option>
					<?= $options; ?>
				</select>
			</th>
			<th class="col-md-1">
				<select name="postal_code" size="1" class="form-control input-sm select2">
					<option value="">- Postal Code -</option>
					<?= $options; ?>
				</select>
			</th>
			<th class="col-md-1">
				<select name="country" size="1" class="form-control input-sm select2">
					<option value="">- Country -</option>
					<?= $options; ?>
				</select>
			</th>
			<th class="col-md-1">
				<select name="nickname" size="1" class="form-control input-sm select2">
					<option value="">- Site Nickname -</option>
					<?= $options; ?>
				</select>
			</th>
			<th class="col-md-1">
				<select name="alias" size="1" class="form-control input-sm select2">
					<option value="">- Site Alias -</option>
					<?= $options; ?>
				</select>
			</th>
			<th class="col-md-1">
				<select name="contactid" size="1" class="form-control input-sm select2">
					<option value="">- Site Contact -</option>
					<?= $options; ?>
				</select>
			</th>
			<th class="col-md-1">
				<select name="code" size="1" class="form-control input-sm select2">
					<option value="">- Site Code -</option>
					<?= $options; ?>
				</select>
			</th>
			<th class="col-md-1">
				<select name="notes" size="1" class="form-control input-sm select2">
					<option value="">- Site Notes -</option>
					<?= $options; ?>
				</select>
			</th>
		</tr>
	</table>

	<div class="table-horizontal">
	<table class="table table-striped table-hover table-condensed table-spreadsheet">
		<?= $rows; ?>
	</table>
	</div>

</div><!-- pad-wrapper -->
</form>

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<script type="text/javascript">
	$(document).ready(function() {
	});
</script>

</body>
</html>
