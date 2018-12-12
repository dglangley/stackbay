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
                <input class="form-control address-name" name="na_name" id ="add_name" placeholder="Address Name" type="text">
              </div>
            </div>
            <br>
            <div class="row">
              <div class="col-md-12">
                <input class="form-control address-street" name="na_line_1" id ="add_line_1" placeholder="Line 1" type="text" autofocus>
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
              <input class="form-control address-city" name="na_city" id ="add_city" placeholder="City" type="text">
              </div>
              <div class="col-md-2">
                <input class="form-control address-state" name="state" id ="add_state" placeholder="State" type="text">
              </div>
              <div class="col-md-4">
                <input class="form-control address-postal_code" name="na" id ="add_zip" placeholder="Zip" type="text">
              </div>
            </div>

			<hr style="margin-top:8px; margin-bottom:8px">
			<h5>Company-Specific Address Details</h5>
            <div class="row">
              <div class="col-md-12">
				<input type="text" name="address_nickname" id="address_nickname" value="" class="form-control address-nickname" placeholder="Site Nickname">
              </div>
            </div>
			<br>
            <div class="row">
              <div class="col-md-12">
				<input type="text" name="address_alias" id="address_alias" value="" class="form-control address-alias" placeholder="Site Alias">
              </div>
            </div>
			<br>
            <div class="row">
              <div class="col-md-7">
				<select name="address_contactid" id="address_contactid" class="form-control contact-selector address-contactid" data-placeholder="- Site Contact -">
				</select>
              </div>
              <div class="col-md-5">
				<input type="text" name="address_code" id="address_code" value="" class="form-control address-code" placeholder="Site Code">
              </div>
            </div>
			<br/>
            <div class="row">
              <div class="col-md-12">
				<textarea class="form-control input-sm address-notes" id="address_notes" rows="3" placeholder="Site Notes"></textarea>
              </div>
            </div>

          </div>
        
      </div>
      <div class="modal-footer text-center">
     		<button type="button" class="btn btn-default btn-sm btn-dismiss" id="address-cancel" data-dismiss="modal">Cancel</button>
     		<button type="button" class="btn btn-success btn-sm" id="save-address" data-validation="address-modal"><i class="fa fa-save"></i> Save</button>
  	  </div>
	</div>
  </div>
</div>
