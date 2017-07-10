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
        <div class="col-md-12">
        <?php if(!$build): ?>
          <select class="form-control" name="repair_code">
            <?php 
              foreach($repair_codes as $code):
                echo "<option value='".$code['id']."'>".$code['description']."\t".$code['code']."</option>";
              endforeach;
            ?>
          </select>
        <?php endif; ?>
        </div>

        <!-- <div class="col-md-6">
          <select class="form-control" name="status">
            <option value='Completed'>Repaired</option>
            <option value='NTF'>NTF</option>
            <option value='Not Reparable'>Not Reparable</option>
          </select>
        </div> -->
      </div>
      <div class="row">
      <div class="col-md-12">
        <button  style="margin-top: 10px" class="btn-sm btn btn-primary pull-right btn-update" type="submit" name="type" value="<?=($build ? 'complete_build':'complete_ticket');?>" data-datestamp = "<?= getDateStamp($order_number); ?>" <?=($ticketStatus == "Completed" ? 'disabled' : '');?>>Complete Ticket</button>
        </div>
        </div>
      </div>
      <div class="modal-footer text-center">
   		<button type="button" class="btn btn-default btn-sm btn-dismiss" data-dismiss="modal">Cancel</button>
   		<!-- <button type="button" class="btn btn-primary btn-sm" id="alert-continue" data-dismiss="modal" data-form="" data-callback="" data-element="">Continue</button> -->
	  </div>
	</div>
  </div>
</div>
