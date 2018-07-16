<?php	
	function getRepairOrder($repair_item_id) {
		$ro_number;

		$query = "SELECT ro_number FROM repair_items WHERE id = ".(fres($repair_item_id)).";";
		$results = qedb($query);

		if (qnum($results)>0) {
			$results = qrow($results);
			$ro_number = $results['ro_number'];
		}

		return $ro_number;
	}

?>

<!------------------------------------------------------------------------------------------->
<!-------------------------------------- HEADER OUTPUT -------------------------------------->
<!------------------------------------------------------------------------------------------>
<!DOCTYPE html>
<html>
<head>
	<title><?=$TITLE;?></title>
	<?php
		//Standard headers included in the function
		include_once $_SERVER['ROOT_DIR'].'/inc/scripts.php';
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
				        <button data-toggle="tooltip" data-placement="right" title="" data-original-title="<?=($page == 'shipping' ? 'Sales' : 'Purchases');?>	" class="btn btn-sm left filter_status btn-default" data-filter="<?=($page == 'shipping' ? 'sale' : 'purchase');?>	" disabled>
				        	<?=($page == 'shipping' ? 'Sale' : 'Purchase');?>	
				        </button>
				        <button data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Repair" class="btn btn-sm middle filter_status btn-default" data-filter="repair" disabled>
				        	Repair	
				        </button>
						<button data-toggle="tooltip" data-placement="bottom" title="" data-original-title="All" class="btn btn-sm right filter_status active btn-info" data-filter="all">
				        	All
				        </button>
				    </div>

					<div class="col-sm-3 remove-pad">
						<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY" data-hposition="right">
				            <input type="text" name="START_DATE" class="form-control input-sm" value="<?=$start_date;?>" style="min-width:50px;">
				            <span class="input-group-addon">
				                <span class="fa fa-calendar"></span>
				            </span>
				        </div>
					</div>
					<div class="col-sm-3 remove-pad">
						<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY" data-hposition="right">
				            <input type="text" name="END_DATE" class="form-control input-sm" value="<?=$end_date;?>" style="min-width:50px;">
				            <span class="input-group-addon">
				                <span class="fa fa-calendar"></span>
				            </span>
				    	</div>
					</div>
				</div>

				<div class="col-md-4 text-center remove-pad">
	            	<h2 class="minimal" id="filter-title"><?=$TITLE;?></h2>
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
						<th class="col-md-2">Customer</th>
						<th class="col-md-1">Order#</th>
						<th class="col-md-1 <?=($page == 'receiving' ? 'hidden' : '')?>">Customer PO#</th>
						<th class="col-md-4">Item</th>
						<th class="col-md-1">Due Date</th>
						<th class="col-md-1"><?=($page == 'receiving' ? 'Received' : 'Shipped')?></th>
						<th class="col-md-2">Tracking#</th>
					</tr>
				</thead>
				<tbody>
					<?php 
						foreach($itemList as $part): 

							$T = array();

							$order = 0;
							$order_det = '';

							$type = 'sale_item';

							if($page == 'shipping') {
								$T = order_type("Sale");
							} else {
								$T = order_type("Purchase");
							}
									
							if($part['ref_1_label'] == 'repair_item_id') {
								$T = order_type($part['ref_1_label']);

								$order = getRepairOrder($part['ref_1']);
								$order_det = $T['abbrev'] . "# " . $order . " <a href='/".$T['abbrev'].$order."'><i class='fa fa-arrow-right' aria-hidden='true'></i></a>";
								$type = 'repair_item';
							} else if($part['ref_2_label'] == 'repair_item_id') {
								$T = order_type($part['ref_1_label']);

								$order = getRepairOrder($part['ref_2']);
								$order_det = $T['abbrev'] . "# " . $order . " <a href='/".$T['abbrev'].$order."'><i class='fa fa-arrow-right' aria-hidden='true'></i></a>";
								$type = 'repair_item';
							} else {
								$order = $part['order_number'];
								$order_det = $T['abbrev'] . "# " . $order . " <a href='".$T['abbrev'].$order."'><i class='fa fa-arrow-right' aria-hidden='true'></i></a>";
							}
					?>
						<tr class="<?=$type;?> filter_item">
							<td><a href="/profile.php?companyid=<?=$part['companyid'];?>" target="_blank"><i class="fa fa-building" aria-hidden="true"></i></a> <?= getCompany($part['companyid']); ?></td>
							<td>
								<?php 
									echo $order_det;
								?>	
							</td>
							<td class="<?=($page == 'receiving' ? 'hidden' : '')?>">
								<?php
									echo $part['cust_ref'];
								?>
							</td>
							<td><?=display_part($part['partid'], true); ?></td>

							
							<td class=""><?= format_date($part['delivery_date']); ?></td>
							<td><span class="<?=((($part['datetime'] <= $part['delivery_date'])) ?'alert-success':'alert-danger');?>"><?= format_date($part['datetime']); ?></span></td>
							<td style="overflow-x: hidden; max-width: 400px;">
								<?php if($part['tracking_no']) {
									echo $part['tracking_no'] . " <a href='".($page == 'shipping' ? 'shipping.php' : 'receiving.php')."?order_type=".$T['type']."&order_number=".$order."'><i class='fa fa-arrow-right' aria-hidden='true'></i></a>";

								} ?>
							</td>

							
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
