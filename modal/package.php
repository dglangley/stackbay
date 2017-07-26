<div class="modal modal-alert fade" id="modal-package" tabindex="-1" role="dialog" aria-labelledby="modalAlertTitle">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
				<h4 class="modal-title" id="package_title">Package Edit</h4>
			</div>
			<div class="modal-body" id="package-modal-body" data-modal-id = ''>
				<div id="alert_message" class="alert alert-warning fade in text-center" style="display: none; width: 100%; z-index: 9999; top: 95px;">
					<a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
					<strong id="alert_title">Box #</strong> has been shipped.
				</div>
				<div class = 'row'>
					<div class="col-md-3"><b>Weight:</b></div>
					<div class="col-md-3"><b>Length:</b></div>
					<div class="col-md-3"><b>Width:</b></div>
					<div class="col-md-3"><b>Height:</b></div>
				</div>
					<div class = 'row'>
					<div class = 'input-group col-sm-3'>
						<input class = 'form-control' id = 'modal-weight' placeholder = 'Weight' title = 'Weight' value = ''>
						<span class='input-group-addon'>
						lbs.
						</span>
					</div>

					<div class = 'input-group col-sm-9'>
						<input class='form-control' id = 'modal-length' name='length' title = 'Length' type='text' placeholder = 'Length' value=''>
						<span class='input-group-addon'>
							<i class='fa fa-times' aria-hidden='true'></i>
						</span>
						<input class='form-control' id = 'modal-width' name='width' title = 'Width' type='text' placeholder = 'Width' value=''>
						<span class='input-group-addon'>
							<i class='fa fa-times' aria-hidden='true'></i>
						</span>
						<input class='form-control' id = 'modal-height' name='height' title = 'Height' type='text' placeholder = 'Height' value=''>
					</div>
				</div>

				<div class = 'row' style = 'padding-top:15px;'>
					<div class="col-md-3"><b>Freight:</b></div>
					<div class="col-md-9"><b>Tracking:</b></div>
				</div>
				<div class = 'row'>
					<div class = 'input-group col-sm-3'>
						<span class='input-group-addon'>$</span>
						<input class = 'form-control' id = 'modal-freight' style = 'padding-left:3px;padding-right:3px;' placeholder = 'Freight' title='Freight Cost' value = ''>
					</div>
					<div class = 'col-sm-9'  >
						<input class = 'form-control' id = 'modal-tracking' style = 'padding-left:3px' placeholder = 'Tracking #' title='Tracking #' value = ''>
					</div>
				</div>

				<div class="row" style = 'padding-top:15px;'>
					<div class="col-md-12">
						<table class='table serial-modal-listing table-hover table-condensed'>
							<thead>
								<tr>
									<th>Part</th>
									<th>Serial Number</th>
									<!--<th>qty</th>-->
								</tr>
							</thead>
							<tbody class='modal-packing'>
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<div class="modal-footer text-center">
				<a class="alert-danger pull-left delete_box" href="#" style="background: transparent; display: none;">Delete Box</a>
				<button type="button" class="btn btn-default btn-sm btn-dismiss" data-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-primary btn-sm" id="package-continue" data-dismiss="modal" data-form="" data-callback="" data-element="">Save</button>
			</div>
		</div>
	</div>
</div>
