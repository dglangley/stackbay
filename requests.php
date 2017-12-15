<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/display_part.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/getUser.php';
	include_once $rootdir.'/inc/form_handle.php';
	
	//$search = trim($_REQUEST['s'] ? $_REQUEST['s'] : $_REQUEST['search']);

	if(isset($_REQUEST['purchase_request_id'])) {
		$query = "UPDATE purchase_requests SET status = 'Void' WHERE id=".prep($_REQUEST['purchase_request_id']).";";
		qdb($query) OR die(qe() . ' ' . $query);
	}

	if(!in_array("5", $USER_ROLES) && !in_array("4", $USER_ROLES)) {
	 	header('Location: /operations.php');
	}

	$deletion;

	if(isset($_REQUEST['delete'])) {
		$deletion = voidRequest($_REQUEST['delete']);
//print_r($_REQUEST['delete']);
		//if(! $deletion) {
			header('Location: /purchase_requests.php');
		//}
	}

	//print '<BR><BR><BR><BR><pre>' . print_r($itemList, true) . '</pre>';

	function getRepairItemId($ro_number, $partid) {
		$repair_item_id;

		$query = "SELECT id as repair_item_id FROM repair_items WHERE ro_number = ".prep($ro_number)." LIMIT 1;";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		if (mysqli_num_rows($result)) {
			$result = mysqli_fetch_assoc($result);
			$repair_item_id = $result['repair_item_id'];
		}

		return $repair_item_id;
	}

	function getOrderNumber($item_id, $table = 'repair_items', $field = 'ro_number') {
		$order_number = 0;

		$query = "SELECT $field as order_number, line_number FROM $table WHERE id = ".res($item_id).";";
		$result = qdb($query) OR die(qe().' '.$query);

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);
			$order_number = $r['order_number'] . ($r['line_number'] ? '-' . $r['line_number']: '-1');
		}

		return $order_number;
	}

	function voidRequest($pr_id) {
		$results = false;

		// First check if the item has been voided yet or not
		$query = "SELECT * FROM purchase_requests WHERE id = ".res($pr_id)." AND (status <> 'Void' OR status IS NULL);";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		if (mysqli_num_rows($result)) {

			// If there exists this purchase request id with a status Active then disable it and 
			// mark the result as true to return that something has been updated
			// This is used in conjunction with the clean url method to remove uneeded url parameters 
			// and the have a 1 time success pop up when something is actually updated
			$query = "UPDATE purchase_requests SET status = 'Void' WHERE id = ".res($pr_id).";";
			qdb($query) OR die(qe() . ' ' . $query);

			$results = true;

		}

		return $results;
	}
	
?>

<!------------------------------------------------------------------------------------------->
<!-------------------------------------- HEADER OUTPUT -------------------------------------->
<!------------------------------------------------------------------------------------------>
<!DOCTYPE html>
<html>
<head>
	<title><?=$title?></title>
	<?php
		//Standard headers included in the function
		include_once $rootdir.'/inc/scripts.php';
		include_once $rootdir. '/modal/image.php';
	?>
	<style>
		.goog-te-banner-frame.skiptranslate {
		    display: none !important;
	    } 
		body {
		    top: 0px !important; 
	    }

	    .table-detailed td {
	    	background-color: #FFF !important;
	    }

	    .completed, .canceled {
	    	display: none;
	    }
	</style>
</head>

<body class="sub-nav accounts-body">
	
	<?php include 'inc/navbar.php'; ?>

	<?php if(! $sourcing) { ?>
		<form action="/manage_request.php?order_type=purchase_request" method="POST">
	<?php } else { ?>
		<form action="/sourcing_request.php" class="sourcing_submit" method="POST">
	<?php } ?>

		<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px;">
			<div class="row" style="padding: 8px;" id="filterBar">
				<div class="col-md-4 mobile-hide" style="max-height: 30px;">
					<div class="col-md-3">
						<div class="btn-group medium">
					        <button data-toggle="tooltip" name="filter" type="submit" data-value="active" data-placement="bottom" title="" data-filter="active_radio" data-original-title="Active" class="btn btn-default btn-sm left filter_status active btn-warning">
					        	<i class="fa fa-sort-numeric-desc"></i>	
					        </button>

					        <button data-toggle="tooltip" name="filter" type="submit" data-value="completed" data-placement="bottom" title="" data-filter="complete_radio" data-original-title="Completed" class="btn btn-default btn-sm middle filter_status ">
					        	<i class="fa fa-history"></i>	
					        </button>

							<button data-toggle="tooltip" name="filter" type="submit" data-value="all" data-placement="bottom" title="" data-filter="all_radio" data-original-title="All" class="btn btn-default btn-sm right filter_status ">
					        	All
					        </button>
					    </div>
					</div>

					<div class="col-md-9 date_container mobile-hid remove-pad">
						
					</div>
				</div>

				<div class="text-center col-md-4 remove-pad">
					<h2 class="minimal" id="filter-title"><?=$title?></h2>
				</div>

				<div class="col-md-4" style="padding-left: 0; padding-right: 10px;">
					<div class="col-md-2 col-sm-2 phone-hide" style="padding: 5px;">

					</div>
					<div class="col-md-2 col-sm-2 col-xs-3">

					</div>

					<div class="col-md-8 col-sm-8 col-xs-9 remove-pad">
						<?php if(! $sourcing) { ?> 
							<button class="btn btn-success btn-sm save_sales pull-right" type="submit" data-validation="left-side-main" style="padding: 5px 25px;">
								CREATE					
							</button>
						<?php } else { ?>
							<div class="dropdown pull-right">
								<button class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
									<i class="fa fa-chevron-down"></i>
								</button>

								<ul class="dropdown-menu pull-right text-left" role="menu" data-inventoryids="12342,12343,12344">
									<li><a href="javascript:void(0);" class="btn-download"><i class="fa fa-share-square-o"></i> Export to CSV</a></li>
									<li><a href="javascript:void(0);" class="open_sales"><i class="fa fa-cubes"></i> Open in Sales</a></li>
								</ul>
							</div>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>
		<div id="pad-wrapper">

			<div class="row">
				<table class="table heighthover heightstriped table-condensed p_table">
					<thead>
						<tr>
							<th class="col-md-1"></th>
							<th class="col-md-4">PART DESCRIPTION</th>
							<th class="col-md-2">CLASSIFICATION</th>
							<th class="col-md-1">ID</th>
							<th class="col-md-2">QTY</th>
							<th class="col-md-2 text-right">Action</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach($itemList as $key => $part): 
							$parts = explode(' ',$part[0]['part']);
							$part_name = $parts[0];

							$status = '';
							$active_qty = 0;
							$completed_qty = 0;

							$initial = true;

							// Determine and sum the status and the amount ordered
							foreach($part as $details) {
								if($details['po_number']) {
									if($status != 'active') {
										$status = 'completed';
									}
									$completed_qty += $details['qty'];
								} else if(! $details['po_number']) {
									if($details['status'] != 'Void') {
										$status = 'active';
									} else {
										$status = 'canceled';
									}
									$active_qty += $details['qty'];
								}
							}

							// Determine if the meta level row should exists for the purchase request
							foreach($part as $details) {
								if($initial) {
									$rowHTML= '<tr class="'.$status.' row-'.$details['partid'].' part_details" style="display:none;">
												<td colspan="6">
													<table class="table table-condensed table-detailed">
														<tbody>
								
															<tr>
																<th class="col-md-3">Details</th>
																<th class="col-md-3">Qty</th>
																<th class="col-md-3">Status</th>
																<th class="col-md-3 text-right"></th>
															</tr>';
									$initial = false;
								}

								$statusValue = 'Pending';
								$statusColor = '#8a6d3b';

								if($details['status'] == 'Void') {
									$statusValue = 'Canceled';
									$statusColor = 'red';
								}

								$table = 'repair_items';
								$field = 'ro_number';
								$title = 'Repair';
								$link = "/service.php?order_type=repair&order_number=";

								if($details['item_id_label'] == 'service_item_id') {
									$table = 'service_items';
									$field = 'so_number';
									$title = 'Service';
									$link = "/service.php?order_type=Service&order_number=";
								} else if($details['item_id_label'] == 'quote_item_id') {
									$table = 'service_quote_items';
									$field = 'quoteid';
									$title = 'Quote';
									$link = "/quote.php?order_number=";
								}

								$order_number = ($details['item_id'] ? getOrderNumber($details['item_id'], $table, $field) : $details['ro_number']);
								$itemid = ($details['item_id'] ? $details['item_id'] : getRepairItemId($details['ro_number'], $details['partid']));

								$item_label = ($details['item_id_label']?:'repair_item_id');

								$link .= $order_number;

								$part_strs = explode(' ',getPart($details['partid']));
								$search_str = $part_strs[0].chr(10);

								$rowHTML .= '
												<tr class="'.($details['po_number'] ? 'completed' : 'active').'">
													<td>'.$title.'# '.$order_number.' <a target="_blank" href="'.$link.'"><i class="fa fa-arrow-right"></i></a></td>
													<td>'.$details['qty'].'</td>
													<td>'.(! $details['po_number'] ? '<span style="color: '.$statusColor.';">'.$statusValue.'</span>' : $details['po_number'] . ' <a target="_blank" href="/PO'.$details['po_number'].'"><i class="fa fa-arrow-right"></i></a>').'</td>
													<td>
														<input type="checkbox" name="purchase_request[]" value="'.$details['id'].'" data-qty="'.$details['qty'].'" data-part="'.trim($search_str).'" class="pull-right detailed_check" style="margin-right: 5px;" '.($details['po_number'] ? 'disabled' : '').'>
															'.($status == 'active' ? '<a href="/purchase_requests.php?delete='.$details['id'].'" class="disable_trash pull-right" style="margin-right: 15px;"><i class="fa fa-trash" aria-hidden="true"></i></a>' : '') .'
													</td>
												</tr>';
							}

							$rowHTML .= '	</tbody>
										</table>
									</td>
								</tr>';
						?>
							<tr class="<?=$status;?>">
								<td><div class="product-img"><img class="img" src="/img/parts/<?php echo $part_name; ?>.jpg" alt="pic" data-part="<?php echo $part_name; ?>"></div></td>
								<td><?=(display_part($part[0]['partid'], true) ? display_part($part[0]['partid'], true) : $part['part']);?></td>
								<td><?=ucwords($part[0]['classification']);?></td>
								<td><?=$part[0]['partid'];?></td>
								<td class="total_qty"><?=($status == 'active' ? $active_qty : $completed_qty);?></td>
								<td><input type="checkbox" class="toggle-children pull-right" data-partid="<?=$part[0]['partid'];?>" style="margin-right: 10px;"></td>
							</tr>

							<?=$rowHTML;?>
						<?php endforeach; ?>
					</tbody>
		        </table>
			</div>
		</div>
	</form>

	<?php if($sourcing) { ?>
		<form class="form-inline form-search" method="POST" action="/sales.php">
			<textarea name="s2" class="form-control hidden s2_update" rows="5"></textarea>
		</form>
	<?php } ?>

	<?php include_once 'inc/footer.php'; ?>

    <script type="text/javascript">
    	(function($){
    		$(document).on('click', '.disable_trash', function(e) {
    			e.preventDefault();
    			if(confirm('Are you sure you want to cancel this purchase request?')) {
            		window.location = $(this).attr('href');
    			}
    		});

    		$(document).on("change", ".toggle-children", function(e){
    			var data = $(this).data("partid");

    			if($(this).is(':checked')) {
    				$(".row-"+data).show();
    				$(".row-"+data).find('input').prop("checked", true);
    			} else {
    				$(".row-"+data).hide();
    				$(".row-"+data).find('input').prop("checked", false);
    			}
    		});

    		$(document).on("change", ".detailed_check", function(e){
    			var container = $(this).closest(".table-detailed");
    			var qty = 0;

    			container.find(".active .detailed_check").each(function(){
    				if($(this).is(":checked")){
    					qty += parseInt($(this).data("qty"));
    				}
    			});

    			container.closest('.part_details').prev().find(".total_qty").html(qty);
    		});

    		<?php if($sourcing) { ?>
    			$(document).on("change", "#pad-wrapper input[type=checkbox]", function(e) {
    				e.preventDefault();

    				var search_str = '';
    				$('.detailed_check').each(function() {
    					if(this.checked) {
    						search_str += $(this).data("part") + ",";
    					}
    				});

    				// Remove duplcate entries for when multiple same partids are selected
					arr =  $.unique(search_str.split(','));
					search_str = arr.join("\n");

    				// alert(search_str);
    				$('.s2_update').text(search_str);
    			});

    			$(document).on("click", ".open_sales", function(e) {
    				e.preventDefault();

    				$(".form-search").submit();
    			});

    			$(".btn-download").on("click",function() {
					$(".sourcing_submit").prop('action','downloads/materials-export-<?=$today;?>.csv');
					$(".sourcing_submit").submit();
				});
    		<?php } ?>

    		$(document).on("click", ".filter_status", function(e){
    			e.preventDefault();

				var value = $(this).data('value');

				//Equipment or component filter
				var filter = $('.filter-group').find('.active').data('type');
				var type = 'list';

				$('.filter_status').removeClass('active');
				$('.filter_status').addClass('btn-default');

				$('.filter_status').removeClass('btn-warning');
				$('.filter_status').removeClass('btn-success');
				$('.filter_status').removeClass('btn-info');

				if(value == 'active'){
					$(this).addClass('btn-warning');
					$('.active').show();
					$('.completed').hide();
					$('.canceled').hide();
					$('.part_details').hide();
				} else if(value == 'completed') {
					$(this).addClass('btn-success');
					$('.completed').show();
					$('.active').hide();
					$('.canceled').hide();
					$('.part_details').hide();
					$('.completed.part_details').show();
				} else {
					$('.active').show()
					$('.completed').show()
					$('.canceled').show();
					$(this).addClass('btn-info');
					$('.part_details').show();
				}

				$(this).addClass('active');
    		});
    	})(jQuery);
    </script>

</body>
</html>
