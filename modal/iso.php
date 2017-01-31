<div class="modal modal-alert fade" id="modal-iso" tabindex="-1" role="dialog" aria-labelledby="modalAlertTitle">
<div class="modal-dialog" role="document">
<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
			<h4 class="modal-title" id="package_title">ISO Quality Check SO # <?=$order_number;?></h4>
		</div>
	    <div class="modal-body" id="ISO-modal-body" data-modal-id = '' style='padding: 0px;'>
		    <ul class="nav nav-tabs nav-tabs-ar">
				<li class="active"><a href="#iso_quality" data-toggle="tab"><i class="fa fa-qrcode"></i> Parts Statement</a></li>
				<li class=""><a href="#iso_match"><i class="fa fa-truck"></i> Shipping Confirmation</a></li>
				<li class=""><a href="#packing_list"><i class="fa fa-list"></i> Packing List</a></li>
			</ul><!-- nav-tabs -->
			
			<div class="tab-content">

				<!-- Materials pane -->
				<div class="tab-pane active" id="iso_quality">
				    <form action="#" method="post" style="padding: 7px;">
						<b>Summary of Comments</b>
						<table class="table table-hover table-striped table-condensed">
							<thead>
								<tr>
									<th>Part</th>
									<th>Serial</th>
									<th>Comments</th>
								</tr>
							</thead>
							<tbody class='iso_broken_parts'>
								<tr><td><b>No Defects/Damage in Order</b></td><td></td><td></td></tr>
							</tbody>
						</table>
						
						<div class='row'>
							<div class="col-md-12">
								The list above accurately reflects the part number, HECI, cosmetic condition and component condition for <b>defective</b> parts for this shipment.<br><br>
								<span class='pull-right'><b>Approved by</b>: <?= $U['name']; ?></span><br><br>
								<button class="btn btn-primary btn-sm pull-right btn_iso_parts" data-form="" data-callback="" data-element="">Submit</button>
							</div>
						</div>
					</form>
				</div>
				
				<!-- Materials pane -->
				<div class="tab-pane" id="iso_match">
					<form action='#' method='post' style="padding: 7px;">
						<table class="table table-hover table-striped table-condensed">
							<thead>
								<tr>
									<th>Checkpoint</th>
									<th></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>
										All the customer PO Special Requirements met?
									</td>
									<td>
										<label class="radio-inline"><input type="radio" name="optradio">Yes</label>
										<label class="radio-inline"><input type="radio" name="optradio" checked>No</label>
									</td>
								</tr>
								<tr>
									<td>
										Is all ship to / contact info correct?
									</td>
									<td>
										<label class="radio-inline"><input type="radio" name="optradio2">Yes</label>
										<label class="radio-inline"><input type="radio" name="optradio2" checked>No</label>
									</td>
								</tr>
								<tr>
									<td>
										Appropiate transit time service level requirements met?
									</td>
									<td>
										<label class="radio-inline"><input type="radio" name="optradio5">Yes</label>
										<label class="radio-inline"><input type="radio" name="optradio5" checked>No</label>
									</td>
								</tr>
							</tbody>
						</table>
						<br>	
						<button class="btn btn-primary btn-sm pull-right btn_iso_shipping" data-form="" data-callback="" data-element="">Submit</button>
					</form>
				</div>
				
				<div class="tab-pane" id="packing_list">
				    <b>Packing List goes here</b>
				</div>
			</div>
		</div>
		
		<div class="modal-footer text-center" style="margin-top: 0; border: 0;">
			<button type="button" class="btn btn-default btn-sm btn-dismiss" data-dismiss="modal">Cancel</button>
		</div>
		
	</div>
</div>
</div>
