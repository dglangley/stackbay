<div class="modal modal-alert fade" id="modal-address" tabindex="-1" role="dialog" aria-labelledby="modalAddressTitle">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
        <h4 class="modal-title" id="modalAddressTitle">Add New Address</h4>
	  </div>
      <div class="modal-body address-modal" id="address-modal-body" data-origin = "ship_to" data-oldid = "false" data-idname="">
          <div class="row-fluid">
            <div class="row">
              <div class="col-md-12">
                <input class="form-control required address-name" name="na_name" id ="add_name" placeholder="Address Name" type="text" autofocus>
              </div>
            </div>
            <br>
            <div class="row">
              <div class="col-md-12">
                <input class="form-control required address-street" name="na_line_1" id ="add_line_1" placeholder="Line 1" type="text">
              </div>
            </div>
            <br>
            <div class="row">
              <div class="col-md-12">
              <input class="form-control address-addr2" name="na_line2" id ="add_line2" placeholder="Line 2" type="text">
              </div>
            </div>
            <br>
            <div class="row">
              <div class="col-md-6">
              <input class="form-control required address-city" name="na_city" id ="add_city" placeholder="City" type="text">
              </div>
              <div class="col-md-2">
                <input class="form-control required address-state" name="state" id ="add_state" placeholder="State" type="text">
              </div>
              <div class="col-md-4">
                <input class="form-control required address-postal_code" name="na" id ="add_zip" placeholder="Zip" type="text">
              </div>
            </div>
          </div>

        
      </div>
      <div class="modal-footer text-center">
     		<button type="button" class="btn btn-default btn-sm btn-dismiss" id="address-cancel" data-dismiss="modal">Cancel</button>
     		<button type="button" class="btn btn-primary btn-sm" id="address-continue" data-validation="address-modal">Save</button>
  	  </div>
	</div>
  </div>
</div>
