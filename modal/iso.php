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
				<?php if($notes != ''): ?>
					<li class=""><a href="#iso_req"><i class="fa fa-exclamation-triangle"></i> Special Requirements</a></li>
				<?php endif; ?>
				<li class=""><a href="#iso_match"><i class="fa fa-truck"></i> Shipping Confirmation</a></li>
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
								The list above accurately reflects the part number, HECI, cosmetic condition and component condition for this shipment.<br><br>
								<span class='pull-right'><b>Approved by</b>: <?= $U['name']; ?></span><br><br>
								<button class="btn-flat primary btn-sm pull-right btn_iso_parts" data-form="" data-callback="" data-element="">Confirm</button>
							</div>
						</div>
					</form>
				</div>
				
				<div class="tab-pane" id="iso_req">
				    <form action="#" method="post" style="padding: 7px;">
				    	<b>Notes</b><br>
						<?= $notes; ?> 
						
						<br><br>
						
						<div class='row'>
							<div class="col-md-12">
								<span class='pull-right'>The conditions above have been met.</span><br><br>
								<button class="btn-flat primary btn-sm pull-right btn_iso_req" data-form="" data-callback="" data-element="">Confirm</button>
							</div>
						</div>
					</form>
				</div>
				
				<!-- Materials pane -->
				<div class="tab-pane" id="iso_match">
					<form action="#" method="post" style="padding: 7px;">
						<b>Shipping Address:</b><br>
						<?= address_out($shipid); ?><br><br>
						
						<b >CARRIER INFORMATION:</b><br>
						<?= getFreight('carrier',$selected_carrier,'','name'); ?>
						
						<div class='row'>
							<div class="col-md-12">
								<button class="btn-flat primary btn-sm pull-right btn_iso_parts" disabled data-form="" data-callback="" data-element="">Save & Print</button>
								<button class="btn-flat success btn-sm pull-right btn_update" id='btn_update' data-form="" data-callback="" data-element="" style='margin-right: 10px;'>Save</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		
		<div class="modal-footer text-center" style="margin-top: 0; border: 0;">
			<button type="button" class="btn btn-default btn-sm btn-dismiss" data-dismiss="modal">Cancel</button>
		</div>
		
	</div>
</div>
</div>
