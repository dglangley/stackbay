<div class="modal modal-alert fade" id="modal-account" tabindex="-1" role="dialog" aria-labelledby="modalAlertTitle">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
        <h4 class="modal-title" id="modalAlertTitle">Add New Account</h4>
	    </div>
      <div class="modal-body" id="account-modal-body">
        <div class="row-fluid">
          <div class="row-fluid">
            <div class="col-md-12">
              <input class="form-control" name="na_account" placeholder="Account Number" type="text">
            </div>
          </div>
          <div class="row-fluid">
            <?php 
              $rootdir = $_SERVER['ROOT_DIR'];
              include_once $rootdir.'/inc/dropPop.php';
              echo "<div class='row-fluid'>";
              echo dropdown('carrier','','','col-md-12','Carrier:',"modal_carrier");
              //echo dropdown('services','','','col-md-6','Service:',"modal_service");
              echo "</div>";
            ?>
            <label for="associate">Associate with company?</label>
            <input type="checkbox" name="associate"/>
          </div>
        </div>
      </div>
      <div class="modal-footer text-center">
     		<button type="button" class="btn btn-default btn-sm btn-dismiss" data-dismiss="modal">Cancel</button>
     		<button type="button" class="btn btn-primary btn-sm" id="account-continue" data-dismiss="modal" data-form="" data-callback="" data-element="">Save</button>
  	  </div>
	</div>
  </div>
</div>
