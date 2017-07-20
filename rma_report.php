<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/filter.php';
	include_once $rootdir.'/inc/display_part.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/getPipeIds.php';
	include_once $rootdir.'/inc/calcQuarters.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/terms.php';
	include_once $rootdir.'/inc/order_parameters.php';
	
	$itemList = array();

	if(!$companyid)
		$companyid = setCompany();//uses $_REQUEST['companyid'] if passed in

	$start_date = $_REQUEST['START_DATE'];
	$end_date = $_REQUEST['END_DATE'];

	echo $start_date . ' ' . $end_date;

	//Query Sales items that also contains repair items
	$query = "SELECT r.created, r.rma_number, r.companyid, ri.partid, r.order_number, ri.inventoryid as invid, d.disposition, r.order_type, ri.id as returns_item_id FROM returns r, return_items ri, dispositions d WHERE r.rma_number = ri.rma_number AND ri.dispositionid = d.id ".($companyid ? ' AND companyid = "' .$companyid. '"': '')." ".dFilter('created', $start_date, $end_date)." ORDER BY created DESC;";
	$result = qdb($query) OR die(qe());

	function getTracking($order_number) {
		$tracking_no;

		$query = "SELECT tracking_no FROM packages WHERE order_number = ".(prep($order_number)).";";
		//echo $query;
		$results = qdb($query);

		if (mysqli_num_rows($results)>0) {
			$results = mysqli_fetch_assoc($results);
			$tracking_no = $results['tracking_no'];
		}

		return $tracking_no;
	}

	function getInventorySerial($invid) {
		$serial_no;

		$query = "SELECT serial_no FROM inventory WHERE id = ".(prep($invid)).";";
		//echo $query;
		$results = qdb($query);

		if (mysqli_num_rows($results)>0) {
			$results = mysqli_fetch_assoc($results);
			$serial_no = $results['serial_no'];
		}

		return $serial_no;
	}

	function getTicketStatus($invid) {
		$status;

		$query = "SELECT status FROM inventory WHERE id = ".(prep($invid)).";";
		//echo $query;
		$results = qdb($query);

		if (mysqli_num_rows($results)>0) {
			$results = mysqli_fetch_assoc($results);
			$status = $results['status'];
		}

		return $status;
	}

	function getCustomerRef($type, $order_number) {
		$cust_ref;

		$query = "SELECT cust_ref FROM ".($type == 'Repair' ? 'repair_orders' : 'sales_orders')." WHERE ".($type == 'Repair' ? 'ro_number' : 'so_number')." = ".(prep($order_number)).";";
		//echo $query;
		$results = qdb($query);

		if (mysqli_num_rows($results)>0) {
			$results = mysqli_fetch_assoc($results);
			$cust_ref = $results['cust_ref'];
		}

		return $cust_ref;
	}

	function getReceiveDate($invid, $return_item_id) {
		$date;

		$query = "SELECT date_changed FROM inventory_history WHERE invid = ".prep($invid)." AND field_changed = 'returns_item_id' AND value=".prep($return_item_id).";";
		//echo $query;
		$results = qdb($query);

		if (mysqli_num_rows($results)>0) {
			$results = mysqli_fetch_assoc($results);
			$date = $results['date_changed'];
		}

		return format_date($date);
	}
		
	while ($row = $result->fetch_assoc()) {
		$itemList[] = $row;
	}

?>

<!------------------------------------------------------------------------------------------->
<!-------------------------------------- HEADER OUTPUT -------------------------------------->
<!------------------------------------------------------------------------------------------>
<!DOCTYPE html>
<html>
<head>
	<title>RMA Report</title>
	<?php
		//Standard headers included in the function
		include_once $rootdir.'/inc/scripts.php';
	?>
	<style>
		.goog-te-banner-frame.skiptranslate {
		    display: none !important;
	    } 
		body {
		    top: 0px !important; 
	    }

/*	    .complete {
	    	color: rgb(129, 189, 130) !important;
	    }*/
	</style>
</head>

<body class="sub-nav">
	<?php include 'inc/navbar.php'; ?>

	<form id="filter_form" method="POST">
		<div class="table-header" id = 'filter_bar' style="width: 100%; min-height: 48px;">
			<div class="row" style="padding: 8px;" id = "filterBar">
				<div class="col-md-4">
				    <div class="btn-group medium col-sm-6 remove-pad" data-toggle="buttons">
				        <button data-toggle="tooltip" data-placement="right" title="" data-original-title="Sales" class="btn btn-sm left filter_status btn-default" data-filter="sale">
				        	Sales	
				        </button>
				        <button data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Repair" class="btn btn-sm middle filter_status btn-default" data-filter="repair">
				        	Repair	
				        </button>
						<button data-toggle="tooltip" data-placement="bottom" title="" data-original-title="All" class="btn btn-sm right filter_status active btn-info" data-filter="all">
				        	All
				        </button>
				    </div>

					<div class="col-sm-3 remove-pad">
						<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY" data-hposition="right">
				            <input type="text" name="START_DATE" class="form-control input-sm" value="<?=($start_date ? $start_date : '')?>" style="min-width:50px;">
				            <span class="input-group-addon">
				                <span class="fa fa-calendar"></span>
				            </span>
				        </div>
					</div>
					<div class="col-sm-3 remove-pad">
						<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY" data-hposition="right">
				            <input type="text" name="END_DATE" class="form-control input-sm" value="<?=($end_date ? $end_date : '07/19/2017');?>" style="min-width:50px;">
				            <span class="input-group-addon">
				                <span class="fa fa-calendar"></span>
				            </span>
				    	</div>
					</div>
				</div>

				<div class="col-md-4 text-center remove-pad">
	            	<h2 class="minimal" id="filter-title">RMA Report</h2>
				</div>
				
				<div class="col-md-4">
					<div class="pull-right form-group" style="margin-bottom: 0;">
						<select name="companyid" id="companyid" class="company-selector">
							<option value="">- Select a Company -</option>
							<?php 
								if ($companyid) { 
									echo '<option value="'.$companyid.'" selected>'.getCompany($companyid).'</option>'.chr(10); 
								} else { 
									echo '<option value="">- Select a Company -</option>'.chr(10); 
								} 
							?>
						</select>
						<input class="btn btn-primary btn-sm" type="submit" value="Go">
					</div>
				</div>

			</div>
		</div>
	</form>
	<div id="pad-wrapper">
		<div class="row">
			<table class="table heighthover heightstriped table-condensed p_table">
				<thead>
					<tr>
						<th class="col-md-1">Customer</th>
						<th class="col-md-1">RMA#</th>
						<th class="col-md-1">Customer PO#</th>
						<th class="col-md-4">Item</th>
						<th class="col-md-1">Serial</th>
						<th class="col-md-1">Disposition</th>
						<th class="col-md-1">Status</th>
						<th class="col-md-1">Received</th>
						<th class="col-md-1">Tracking#</th>
					</tr>
				</thead>
				<tbody>
					<?php 
						foreach($itemList as $part): 
							$order = 0;
							$order_det = '';
							$type = 'sale_item';

							$order_det = $part['rma_number'];

							if($part['order_type'] == 'Repair') {
								$type = 'repair_item';
							}
					?>
						<tr class="<?=$type;?> filter_item">
							<td><?= getCompany($part['companyid']); ?></td>
							<td>
								<?php 
									echo $order_det;
								?>	
							</td>
							<td>
								<?php
									echo getCustomerRef($part['order_type'], $part['order_number']);
								?>
							</td>
							<td><?=display_part($part['partid'], true); ?></td>
							<td><?=getInventorySerial($part['invid']); ?></td>
							<td><?=$part['disposition']?></td>
							<td><?=ucwords(getTicketStatus($part['invid'])); ?></td>
							<td><?=getReceiveDate($part['invid'],$part['returns_item_id'])?></td>
							<td style="overflow-x: hidden; max-width: 400px;"><?=getTracking($part['order_number']);?></td>

							
						</tr>
					<?php endforeach; ?>
				</tbody>
	        </table>
		</div>
	</div>

	<?php include_once 'inc/footer.php'; ?>
	<!-- <script src="js/operations.js?id=<?php if (isset($V)) { echo $V; } ?>"></script> -->

    <script type="text/javascript">

    	(function($){

    		$(document).on("click onload", ".filter_status", function(e){
    			var type = $(this).data('filter');
				$('.filter_item').hide();
				$('.filter_status').removeClass('active');
				$('.filter_status').removeClass('btn-warning');
				$('.filter_status').removeClass('btn-success');
				$('.filter_status').removeClass('btn-info');
				$('.filter_status').removeClass('btn-flat info');
				$('.filter_status').removeClass('btn-flat gray');
				$('.filter_status').removeClass('btn-flat danger');
				$('.filter_status').removeClass('btn-danger');
				$('.filter_status').addClass('btn-default');

				var btn,type2;
				if (type=='repair') {
					btn = 'success';
					type2 = type;
					$('.repair_item').hide();
				} else if (type=='sale') {
					btn = 'warning';
					type2 = type;
					$('.sale_item').hide();
				} else {
					type = 'all';
					type2 = 'filter';
					btn = 'info';
				}

				$('.filter_status[data-filter="'+type+'"]').addClass('btn-'+btn);
				$('.'+type2+'_item').show();
    		});

		})(jQuery);
    </script>

</body>
</html>
