<?php
  $repair_codes = array();
  $query = "SELECT * FROM repair_codes;";

  $result = qdb($query) or die(qe() . ' ' . $query);

  while ($row = $result->fetch_assoc()) {
    $repair_codes[] = $row;
  }
?>
<div class="modal modal-alert fade" id="modal-repair" tabindex="-1" role="dialog" aria-labelledby="modalAlertTitle">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
        <h4 class="modal-title" id="modalAlertTitle">Complete Order</h4>
	  </div>
      <div class="modal-body" id="modalAlertBody">
		<div class="row">
			<?php if(!$serial && !$build) { ?>
				<div id="alert_message" class="alert alert-danger fade in text-center alert-ship" style="width: 100%; z-index: 9999; top: 95px;">
					<a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
					<strong id="alert_title">Error</strong>: No Item(s) have been scanned for this order! 
				</div>
			<?php } ?>
		</div><!-- row -->
		<div class="row">
			<div class="col-md-12">
				<select class="form-control input-sm select2" name="repair_code">
					<option selected="" value="null">- Select Status -</option>
					<?php 
						foreach($repair_codes as $code):
							echo "<option value='".$code['id']."'>".$code['description']."\t".$code['code']."</option>";
						endforeach;
					?>
				</select>
			</div>
		</div><!-- row -->
		<div class="row">
			<div class="col-md-12">
			</div>
		</div><!-- row -->
	</div>
      <div class="modal-footer text-center">
   		<button type="button" class="btn btn-default btn-sm btn-dismiss" data-dismiss="modal">Cancel</button>
        <button class="btn-sm btn btn-success pull-right btn-update" type="submit" name="type" value="complete_ticket" data-datestamp = "<?= getDateStamp($order_number); ?>" <?=(($ticketStatus == "Completed" || !$serial) && !$build ? 'disabled' : '');?>><i class="fa fa-save"></i> Complete Ticket</button>
	  </div>
	</div>
  </div>
</div>
