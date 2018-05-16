<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getUser.php';

	function getDocs() {
		$docs = array();

		$query = "SELECT d.*, COUNT(l.id) n FROM documentation d ";
		$query .= "LEFT JOIN doclog l ON d.id = l.docid ";
		$query .= "GROUP BY d.id ORDER BY n DESC, title ASC; ";
		$result = qedb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$r['file'] = str_replace($GLOBALS['TEMP_DIR'],'uploads/',$r['file']);
			$docs[] = $r;
		}

		return ($docs);
	}

	$TITLE = 'Company Info';
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
		.table-docs {
			max-width:1290px;
			margin-left:auto;
			margin-right:auto;
		}
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
<form class="form-inline" method="POST" action="save-docs.php" enctype="multipart/form-data" >

	<table class="table table-condensed table-hover table-striped table-responsive table-docs">
		<thead>
			<tr>
				<th class="col-sm-4">Title</th>
				<th class="col-sm-2">Uploaded</th>
				<th class="col-sm-2">By</th>
				<th class="col-sm-3">Filename</th>
				<th class="col-sm-1"> </th>
			</tr>
		</thead>
		<tbody>
<?php
		$docs = getDocs();
		foreach ($docs as $doc) {
			echo '
			<tr>
				<td>'.$doc['title'].'</td>
				<td>'.format_date($doc['datetime'], 'n/j/y g:ia').'</td>
				<td>'.getUser($doc['userid']).'</td>
				<td>
					'.$doc['file'].'
				</td>
				<td class="text-right">
					<a href="'.$doc['file'].'" class="btn btn-xs btn-primary pull-left"><i class="fa fa-download"></i></a>
					<a href="javascript:void(0);" class="doc-delete" data-id="'.$doc['id'].'"><i class="fa fa-trash text-danger"></i></a>
				</td>
			</tr>
			';
		}
?>
			<tr>
				<td>
					<input type="text" class="form-control input-sm" name="title" placeholder="Title">
				</td>
				<td>
				</td>
				<td>
					<?=$U['name'];?>
				</td>
				<td class="file_container">
					<span class="file_name" style="margin-right: 5px;"></span>
					<input type="file" class="upload" name="files" accept="image/*,application/pdf,application/vnd.ms-excel,application/msword,text/plain,*.htm,*.html,*.xml" value="">
					<a href="javascript:void(0);" class="upload_link btn btn-default btn-sm">
						<i class="fa fa-folder-open-o" aria-hidden="true"></i> Browse...
					</a>
				</td>
				<td class="text-center">
					<button type="submit" class="btn btn-success btn-md"><i class="fa fa-upload"></i></button>
				</td>
			</tr>
		</tbody>
	</table>

</form>
</div><!-- pad-wrapper -->

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<script type="text/javascript">
	$(document).ready(function() {
		$(".doc-delete").on('click',function() {
			var id = $(this).data('id');
			if (! id) { return; }

			modalAlertShow("Permadelete","Permadelete stands for PERMANENT DELETE. Are you sure about this??",true,"deleteDoc",id);
		});
	});
	function deleteDoc(id) {
		document.location.href = 'save-docs.php?deleteid='+id;
	}
</script>

</body>
</html>
