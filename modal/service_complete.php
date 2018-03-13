<?php
	$service_codes = array();
	$query = '';
	$open_materials = false;

	if (! isset($BUILD)) { $BUILD = false; }

	if(strtolower($type) == 'service') {
		if(in_array("4", $USER_ROLES)){ 
			$query = "SELECT * FROM status_codes;";
		} else {
			$query = "SELECT * FROM status_codes WHERE admin <> 1;";
		}
	} else {
		$query = "SELECT * FROM repair_codes ";
		if ($BUILD) { $query .= "WHERE description RLIKE 'Build' "; }
		$query .= "; ";
	}

	$result = qdb($query) or die(qe() . ' ' . $query);

	while ($row = $result->fetch_assoc()) {
		$service_codes[] = $row;
	}

	foreach($component_data as $material) {
		if($material['totalOrdered'] > $material['pulled'] AND $material['status'] != "Void") {
			$open_materials = true;
			break;
		}
	}
?>
<div class="modal modal-alert fade" id="modal-complete" tabindex="-1" role="dialog" aria-labelledby="modalAlertTitle">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
				<h4 class="modal-title"><?=($ticketStatus ? 'Update Status of ' : 'Complete'); ?> <?=($BUILD ? 'Build' : 'Order');?></h4>
			</div>

			<form action="tasks_log.php" method="post">
				<div class="modal-body">
					<div class="row">
						<?php if(empty(getDetails($item_id)) AND strtolower($type) == 'repair') { ?>
							<div id="alert_message" class="alert alert-danger fade in text-center alert-ship" style="width: 100%; z-index: 9999; top: 95px;">
								<a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
								<strong id="alert_title">Error</strong>: No Item(s) have been scanned for this order! 
							</div>
						<?php } ?>

						<?php if($open_materials) { ?>
							<div id="alert_message" class="alert alert-danger fade in text-center alert-ship" style="width: 100%; z-index: 9999; top: 95px;">
								<a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
								<strong id="alert_title">Error</strong>: Material(s) have not been fulfilled for this order! 
							</div>
						<?php } ?>
					</div><!-- row -->

					<div class="row">

						<input type="text" name="item_id" value="<?=$item_id;?>" class="hidden">
						<input type="text" name="order_number" value="<?=$order_number;?>" class="hidden">
						<input type="text" name="item_id_label" value="<?=$item_id_label;?>" class="hidden">

						<div class="col-md-12">
							<select class="form-control input-sm select2" name="service_code_id">
							<option value="null">- Select Status -</option>
								<?php 
									foreach($service_codes as $code):
									echo "<option value='".$code['id']."'".($code['id']==$item_details['status_code'] ? ' selected' : '').">".$code['description']."\t".$code['code']."</option>";
									endforeach;
								?>
							</select>
						</div>
					</div><!-- row -->
					<div class="row">
						<div class="col-md-12">
							<?php if($ticketStatus) {
									echo '<textarea class="form-control" name="notes" rows="3" placeholder="Notes... (Reason to update status)"></textarea>';
							} ?>
						</div>
					</div><!-- row -->
				</div>
				<div class="modal-footer text-center">
					<button type="button" class="btn btn-default btn-sm btn-dismiss" data-dismiss="modal">Cancel</button>
					<!-- Make it so you can't complete a repair ticket without scanning something in, but if it is a Service ticket disregard -->
					<?php if((! empty(getDetails($item_id)) OR strtolower($type) == 'service') AND ! $open_materials) { ?>
						<button class="btn-sm btn btn-success pull-right btn-update" type="submit" name="type" value="complete"><i class="fa fa-save"></i> Save</button>
					<?php } ?>
				</div>
			</form>
		</div>
	</div>
</div>
