<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/logRemotes.php';

	$remoteid = false;
	if (isset($_REQUEST['remoteid'])) { $remoteid = trim($_REQUEST['remoteid']); }

	$remote_rows = '';
	foreach ($REMOTES as $abbrev => $r) {
		$contents = '';
		$query2 = "SELECT contents FROM remote_sessions WHERE remoteid = '".$r['id']."'; ";
		$result2 = qedb($query2);
		if (qnum($result2)>0) {
			$r2 = qrow($result2);
			$contents = $r2['contents'];
		}

		$setting = '';
		if ($r['setting']=='Y') { $setting = '<span class="label label-success">ON</span>'; }
		else { $setting = '<span class="label label-danger">OFF</span>'; }

		if ($remoteid===$r['id']) {//editing this row
			$remote_rows .= '
			<tr class="valign-top">
				<td><input type="text" name="remote" class="form-control input-sm" value="'.$abbrev.'"></td>
				<td><input type="text" name="name" class="form-control input-sm" value="'.$r['name'].'"></td>
				<td><textarea rows="5" name="contents" style="width:100%">'.$contents.'</textarea></td>
				<td>'.$r['id'].'<input type="hidden" name="remoteid" value="'.$r['id'].'"></td>
				<td>'.$setting.'</td>
				<td class="text-center">
					<a href="remotes.php" class="info"><i class="fa fa-close fa-2x"></i></a>
					<button class="btn btn-success btn-sm" type="submit" style="margin-left:10px"><i class="fa fa-save"></i></button>
				</td>
			</tr>
			';
		} else {
			$remote_rows .= '
			<tr class="valign-top">
				<td>'.$abbrev.'</td>
				<td>'.$r['name'].'</td>
				<td>'.str_replace(chr(10),'<BR>',$contents).'</td>
				<td>'.$r['id'].'</td>
				<td>'.$setting.'</td>
				<td class="text-right">'.(($remoteid===false) ? '<a href="remotes.php?remoteid='.$r['id'].'"><i class="fa fa-pencil"></i></a>' : '').'</td>
			</tr>
			';
		}
	}

	$TITLE = 'Remotes';
	$SUBTITLE = 'API Settings';
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
<form class="form-inline" method="POST" action="save-remotes.php" enctype="multipart/form-data" >

	<table class="table table-hover table-striped table-condensed">
		<thead>
			<tr>
				<th class="col-sm-1">Abbrev</th>
				<th class="col-sm-2">Remote</th>
				<th class="col-sm-6">Session Content</th>
				<th class="col-sm-1">ID</th>
				<th class="col-sm-1">Setting</th>
				<th class="col-sm-1"> </th>
			</tr>
		</thead>
		<tbody>
			<?=$remote_rows;?>
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
