<div class="modal modal-alert fade" id="modal-contact" tabindex="-1" role="dialog" aria-labelledby="modalContactTitle">
  <div class="modal-dialog" role="document">
	<form class="form-horizontal" onSubmit="return false;">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
        <h4 class="modal-title" id="modalContactTitle">Contact Editor</h4>
	  </div>
      <div class="modal-body contact-modal" id="contact-modal-body">
            <div class="row">
			  <div class="form-group">
                <label class="col-md-2 control-label">
				  Name
                </label>
                <div class="col-md-9">
                  <input class="form-control input-sm contact-name required" name="name" id="contact_name" placeholder="First and Last Names" type="text">
                </div>
                <div class="col-md-1">
                </div>
			  </div>
            </div>
            <div class="row">
			  <div class="form-group">
                <label class="col-md-2 control-label">
				  Title
                </label>
                <div class="col-md-9">
                  <input class="form-control input-sm contact-title" name="title" id="contact_title" placeholder="Title (optional)" type="text">
                </div>
                <div class="col-md-1">
                </div>
			  </div>
            </div>
            <div class="row">
			  <div class="form-group">
                <label class="col-md-2 control-label">
				  Email
                </label>
                <div class="col-md-9">
                  <input class="form-control input-sm contact-email" name="email" id="contact_email" placeholder="Email (optional)" type="email">
                </div>
                <div class="col-md-1">
                </div>
			  </div>
            </div>
            <div class="row">
			  <div class="form-group">
                <label class="col-md-2 control-label">
				  Notes
                </label>
                <div class="col-md-9">
                  <input class="form-control input-sm contact-notes" name="notes" id="contact_notes" placeholder="Notes (optional)" type="text">
                </div>
                <div class="col-md-1">
                </div>
			  </div>
            </div>
      </div>
      <div class="modal-footer text-center">
			<input type="hidden" name="id" class="contact-id" value="">
     		<button type="button" class="btn btn-default btn-sm btn-dismiss" data-dismiss="modal">Cancel</button>
			<button type="button" class="btn btn-primary btn-sm" id="save-contact">Save</button>
  	  </div>
	</div>
	</form>
  </div>
</div>
