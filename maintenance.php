<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrder.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getAddresses.php';

	$order_type =  isset($_REQUEST['order_type']) ? $_REQUEST['order_type'] : '';
	$order_number =  isset($_REQUEST['order_number']) ? $_REQUEST['order_number'] : '';

	$success =  isset($_REQUEST['success']) ? $_REQUEST['success'] : '';
	// get the error code
	$ERR =  isset($_REQUEST['ERR']) ? $_REQUEST['ERR'] : '';

	$manager_access = array_intersect($USER_ROLES,array(1,4));

	if(! $manager_access) {
		die("You Don't Have Access to This Page");
	}

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

	function buildRows($ORDER, $T) {
		$rowsHTML = '';

		foreach($ORDER['items'] as $line_number => $r) {
			$status = true;

			// Check if the line_number has ever been invoiced being either bill or anything else
			if($T['collection']) {
				$Ti = order_type($T['collection']);

				$query = "SELECT * FROM ".$Ti['items']." WHERE taskid = ".res($line_number)." AND task_label = ".fres($T['item_label']).";";
				$result = qedb($query);

				if(mysqli_num_rows($result)>0) {
					$status = false;
				}
			}

			$rowsHTML .= '
				<tr class="'.($status?'':'row_complete').'">
					<td>
						<div class="pull-left padding-right20">'.$r['line_number'].'</div>
						<div class="scope">
							'.$r['description'].'
						</div>
					</td>
					<td>'.$r['ref_1_label']. ' ' .$r['ref_1'].'</td>
					<td>'.$r['ref_2_label']. ' ' .$r['ref_2'].'</td>
					<td>'.format_date($r['due_date']).'</td>';
			if($status) {
				$rowsHTML .= '
					<td><input type="checkbox" name="item_ids[]" value="'.$line_number.'" class="pull-right"></td>';
			} else {
				$rowsHTML .= '
					<td><input type="checkbox" class="pull-right" disabled checked></td>';
			}

			$rowsHTML .= '
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
	<title><?php echo $T['type'] . '# ' . $order_number; ?></title>
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

		.row_complete td {
			background: #EEE !important;
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
			<a href="/order.php?order_type=<?=$order_type;?>&amp;order_number=<?=$order_number;?>" class="btn btn-default btn-sm"><i class="fa fa-file-text-o" aria-hidden="true"></i> View</a>
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
			<button class="btn btn-success btn-md pull-right save_maintenance" type="submit" style="margin-right: 10px;">
				Save
			</button>

			<a href="/order.php?order_type=<?=$order_type;?>&amp;order_number=<?=$order_number;?>" class="btn btn-default btn-sm pull-right" style="margin-right: 10px;"><i class="fa fa-times"></i> Cancel</a>
		</div>
	</div>

	</form>
</div>

<div id="pad-wrapper">
<?php 
	if($success) {
		echo '	<div class="alert alert-success text-center">';
		if($success == 'assign') {
			echo 'User Successfully Assigned';
		} else if($success == 'swap') {
			echo 'Task Successfully Swapped';
		}
		echo '	</div>';
	} else if($ERR) {
		echo '	<div class="alert alert-danger text-center">';
		if($ERR == 1) {
			echo 'Task Selected Already Invoiced. Swapping Not Allowed.';
		}
		echo '	</div>';
	}
?>

<form id="maintenance_form" class="form-inline" method="get" action="/maintenance_edit.php" enctype="multipart/form-data">
	<input type="hidden" name="order_type" value="<?=$order_type;?>">
	<input type="hidden" name="order_number" value="<?=$order_number;?>">
	<div class="row" style="margin: 20px 0;">					
		<!-- <div class="col-md-7" style="padding-left: 0px !important;"> -->
		<div class="col-md-4">
				
		</div>
		<!-- <div class="col-md-3"> -->
		<div class="col-md-2" style="padding-right: 0;">
			<select name="techid" class="form-control input-sm tech-selector"></select>
		</div>
		<!-- </div> -->

		<div class="col-md-3" style="padding-left: 5px; padding-right: 0;">
			<div class="input-group datepicker-datetime date datetime-picker pull-left" data-hposition="right" style="margin-right: 10px;">
		         <input type="text" name="start_datetime" class="form-control input-sm" value="">
       			 <span class="input-group-addon">
	                 <span class="fa fa-calendar"></span>
		         </span>
			</div>

			<div class="input-group datepicker-datetime date datetime-picker" data-hposition="right">
		         <input type="text" name="end_datetime" class="form-control input-sm" value="">
       			 <span class="input-group-addon">
	                 <span class="fa fa-calendar"></span>
		         </span>
			</div>
		</div>

		<div class="col-md-1" style="padding-left: 0;">
			
		</div>
		
		<div class="col-md-3">
			<div class="row">
				
			</div>
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
					<th><input type="checkbox" class="check_all pull-right"></th>
				</tr>
			</thead>

			<tbody>
				<?= buildRows($ORDER, $T); ?>
			</tbody>

		</table>
	</div>

</form>
</div><!-- pad-wrapper -->

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<script type="text/javascript">
	$(document).ready(function() {
		$('.order_selector').selectize();	

		$('.save_maintenance').click(function(e) {
			e.preventDefault();

			var new_order = $('.order_selector').val();
			// Check if a checkbox has been checked
			if($("[name='item_ids[]']:checked").length > 0) {
				if(new_order) {
					// Something is checked now begin the process of transferring it over to the new order
					input = $("<input>").attr("type", "hidden").attr("name", "new_order").val(new_order);
					$('#maintenance_form').append($(input));
				}

				$('#maintenance_form').submit();
			} else {
				modalAlertShow("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning", "No Lines are Selected. <br><br>If this message appears to be in error, please contact an Admin.");

				$('.order_selector').val('').trigger('change');
			}
		});

		$('.check_all').change(function() {
			if($(this.checked)) {
				//alert('checked');
			} else {
				//alert('unchecked');
			}
			
		});
	});
</script>

</body>
</html>
