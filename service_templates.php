<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getUser.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';

	function buildHeader() {
		$headerHTML .= '
	        <th class="">
	            <span class="line"></span>
	            Date
	        </th>
	        <th class="">
	            <span class="line"></span>
	            Template#
	        </th>
	        <th class="">
	        	<span class="line"></span>
	            Name
	        </th>
	        <th class="">
	        	<span class="line"></span>
	            Created By
	        </th>
	        <th class="text-right">
	        	<span class="line"></span>
	            Action
	        </th>
        ';  

        return $headerHTML;
	}

	function buildRows() {
		$htmlRows = '';

		$templates = getTemplates();

		if($templates) {
			foreach ($templates as $row) {
				$htmlRows .= '<tr>';
				$htmlRows .= '	<td>'.format_date($row['created']).'</td>';
				$htmlRows .= '	<td>'.$row['template_no'].'</td>';
				$htmlRows .= '	<td>'.$row['name'].'</td>';
				$htmlRows .= '	<td>'.getUser($row['created_by']).'</td>';
				$htmlRows .= '	<td class="text-right">';
				$htmlRows .= '			<a href="/service_template.php?template_no='.$row['template_no'].'" title="Edit" data-toggle="tooltip" data-placement="bottom"><i style="margin-right: 5px;" class="fa fa-pencil" aria-hidden="true"></i></a>';	
				$htmlRows .= '			<a id="deleteTemplate" href="#" title="Delete" data-toggle="tooltip" data-placement="bottom"><i style="margin-right: 5px;" class="fa fa-trash" aria-hidden="true"></i></a>';	
				$htmlRows .= '	</td>';
				$htmlRows .= '</tr>';
			}
		}

		return $htmlRows;
	}

	function getTemplates() {
		$templates = array();

		$query = "SELECT * FROM templates t ORDER BY created DESC;"; // , template_items i WHERE t.template_no = i.template_no
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$templates[] = $r;
		}

		return $templates;
	}

	$TITLE = 'Service Templates';
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
			<!-- <button class="btn btn-danger btn-sm expenses_edit pull-right" type="submit" style="margin-right: 10px;" title="Delete" data-toggle="tooltip" data-placement="bottom">
				<i class="fa fa-minus-circle" aria-hidden="true"></i>
			</button> -->
		</div>
	</div>

	</form>
</div>

<div id="pad-wrapper">
<form class="form-inline" method="get" action="" enctype="multipart/form-data" >
	<table class="table table-hover table-striped table-condensed">
		<thead>
            <tr>
                <?=buildHeader();?>
            </tr>
        </thead>

        <tbody>
        	<?=buildRows();?>
        </tbody>
	</table>
</form>
</div><!-- pad-wrapper -->

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<script type="text/javascript">
	function deleteTemplate() {

	}

	$(document).ready(function() {
		$("#deleteTemplate").click(function(e){
			e.preventDefault();

			modalAlertShow('<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Warning','Please Confirm You Want to Delete this Record.',true,'deleteTemplate');
		});
	});
</script>

</body>
</html>
