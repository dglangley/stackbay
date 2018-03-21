<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrder.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getAddresses.php';

	$order_type =  isset($_REQUEST['order_type']) ? $_REQUEST['order_type'] : '';
	$order_number =  isset($_REQUEST['order_number']) ? $_REQUEST['order_number'] : '';

	$T = order_type($order_type);

	$ORDER = getOrder($order_number, $order_type);

	// print '<pre>' . print_r($ORDER, true) . '</pre>';

	$selectHTML = '
		<select class="select2 form-control order_selector" data-type="'.$T['type'].'" data-companyid="'.$ORDER['companyid'].'" data-url="/json/order-dropdown.php">
			<option value="">'.$order_number.'</option>
		</select>
	';

	function getSiteName($companyid, $addressid) {
		$sitename = '';

		$query = "SELECT * FROM company_addresses WHERE companyid = ".fres($companyid)." AND addressid = ".fres($addressid).";";
		$result = qedb($query);

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);

			$sitename = $r['nickname'] . '<br>';
		}

		return $sitename;
	}

	function buildRows($ORDER) {
		$rowsHTML = '';

		foreach($ORDER['items'] as $line_number => $r) {
			$rowsHTML .= '
				<tr>
					<td>
						<div class="pull-left padding-right20">'.$r['line_number'].'</div>
						<div class="scope">
							'.$r['description'].'
						</div>
					</td>
					<td>'.$r['ref_1_label']. ' ' .$r['ref_1'].'</td>
					<td>'.$r['ref_2_label']. ' ' .$r['ref_2'].'</td>
					<td>'.format_date($r['due_date']).'</td>
					<td><input type="checkbox" name="line_number[]" value="'.$line_number.'" class="pull-right"></td>
				</tr>
			';
		}

		return $rowsHTML;
	}

	$TITLE = $T['type'] . '# ' . $selectHTML;
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
		.scope {
		    display: inline-block;
		    display: -webkit-box;
		    -webkit-line-clamp: 2;
		    -webkit-box-orient: vertical;
		    overflow: hidden;
		}

		h2 .select2 {
			width: 200px !important;
		}
	</style>
</head>
<body data-scope="<?=$T['type'];?>" data-order-type="<?=$T['type'];?>">

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
			<!-- <span class="info"><?= $selectHTML; ?></span> -->
		</div>
		<div class="col-sm-2">
		</div>
		<div class="col-sm-2">
			<button class="btn btn-success btn-sm pull-right save_template" type="submit" style="margin-right: 10px;">
				Save
			</button>
		</div>
	</div>

	</form>
</div>

<div id="pad-wrapper">
<form class="form-inline" method="get" action="" enctype="multipart/form-data">
	<div class="row" style="margin: 20px 0;">					
		<!-- <div class="col-md-7" style="padding-left: 0px !important;"> -->
		<div class="col-md-1">
				
		</div>
		<div class="col-md-3 location">
			<div class="col-md-8" style="padding-right: 0;">
				<select name="techid" class="form-control input-sm tech-selector"></select>
			</div>

			<div class="col-md-4" style="padding-left: 5px; padding-right: 0;">
				
			</div>
		</div>

		<div class="col-md-1" style="padding-left: 0;">
			
		</div>
		
		<div class="col-md-3">
			<div class="row">
				
			</div>
        </div>

        <div class="col-md-1">
        	<button class="btn btn-success btn-sm" type="submit"><i class="fa fa-save"></i></button>
        </div>
	</div>

	<div class="row">
		<table class="table table-responsive table-condensed table-striped" id="search_input">
			<thead>
				<tr>
					<th class="col-md-4"><div class="pull-left padding-right20">Ln</div> Description</th>
					<th class="col-md-2">Ref 1</th>
					<th class="col-md-2">Ref 2</th>
					<th class="col-md-2">
						Date Due
					</th>
					<th></th>
				</tr>
			</thead>

			<tbody>
				<?= buildRows($ORDER); ?>
			</tbody>

		</table>
	</div>

</form>
</div><!-- pad-wrapper -->

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<script type="text/javascript">
	$(document).ready(function() {
		$('.order_selector').selectize();	
	});
</script>

</body>
</html>
