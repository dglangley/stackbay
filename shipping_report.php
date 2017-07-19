<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/keywords.php';
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

	//Query Sales items that also contains repair items
	$query = "SELECT datetime, companyid, partid, ref_1, ref_1_label, ref_2, ref_2_label, delivery_date, si.so_number as order_number, created, order_type, tracking_no, cust_ref FROM packages p, sales_items si, sales_orders so WHERE order_type = 'Sale' AND  p.order_number = si.so_number AND so.so_number = p.order_number AND si.price > 0 AND tracking_no IS NOT NULL
		
		ORDER BY created DESC;";
		// UNION
		// SELECT datetime, companyid, partid, ref_1, ref_1_label, ref_2, ref_2_label, receive_date as delivery_date, pi.po_number as order_number, created, order_type, tracking_no FROM packages p, purchase_items pi, purchase_orders po WHERE order_type = 'Purchase' AND  p.order_number = pi.po_number AND po.po_number = p.order_number AND pi.price > 0 
	$result = qdb($query) OR die(qe());

	function getRepairOrder($repair_item_id) {
		$ro_number;

		$query = "SELECT ro_number FROM repair_items WHERE id = ".(prep($repair_item_id)).";";
		$results = qdb($query);

		if (mysqli_num_rows($results)>0) {
			$results = mysqli_fetch_assoc($results);
			$ro_number = $results['ro_number'];
		}

		return $ro_number;
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
	<title>Shipping Report</title>
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

<body class="sub-nav accounts-body">
	
	<?php include 'inc/navbar.php'; ?>
	<div class="table-header" id = 'filter_bar' style="width: 100%; min-height: 48px;">
		<div class="row" style="padding: 8px;" id = "filterBar">
			<div class="col-md-4">
				<div class="row">
				
				</div>
			</div>

			<div class="col-md-4 text-center remove-pad">
            	<h2 class="minimal" id="filter-title">Shipping Report</h2>
			</div>
			
			<div class="col-md-4 remove-pad">
			</div>

		</div>
	</div>
	<div id="pad-wrapper">
		<div class="row">
			<table class="table heighthover heightstriped table-condensed p_table">
				<thead>
					<tr>
						<th class="col-md-2">Customer</th>
						<th class="col-md-1">Order#</th>
						<th class="col-md-1">Customer PO#</th>
						<th class="col-md-4">Item</th>
						<th class="col-md-1">Due Date</th>
						<th class="col-md-1">Shipped</th>
						<th class="col-md-2">Tracking#</th>

					
					</tr>
				</thead>
				<tbody>
					<?php foreach($itemList as $part): ?>
						<tr>
							<td><?= getCompany($part['companyid']); ?></td>
							<td>
								<?php 
									$order;
									
									if($part['ref_1_label'] == 'repair_item_id') {
										$order = getRepairOrder($part['ref_1']);
										echo "Repair# " . $order;
									} else if($part['ref_2_label'] == 'repair_item_id') {
										$order = getRepairOrder($part['ref_2']);
										echo "Repair# " . $order;
									} else {
										$order = $part['order_number'];
										echo $part['order_type']."# " . $order;
									}
								?>	
							</td>
							<td>
								<?php
									
										echo $part['cust_ref'];
									//} 
								?>
							</td>
							<td><?=display_part($part['partid'], true); ?></td>

							
							<td><?= format_date($part['delivery_date']); ?></td>
							<td><span class="<?=(($part['datetime'] <= $part['delivery_date'])?'alert-success':'alert-danger');?>"><?= format_date($part['datetime']); ?></span></td>
							<td style="overflow-x: hidden; max-width: 400px;"><?=$part['tracking_no'];?></td>

							
						</tr>
					<?php endforeach; ?>
				</tbody>
	        </table>
		</div>
	</div>

	<?php include_once 'inc/footer.php'; ?>

    <script type="text/javascript">
    </script>

</body>
</html>
