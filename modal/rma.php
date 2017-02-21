<div class="modal modal-alert fade" id="modal-rma" tabindex="-1" role="dialog" aria-labelledby="modalAlertTitle">
<div class="modal-dialog" role="document">
<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
			<h4 class="modal-title" id="package_title">RMA SO # <?=$order_number;?></h4>
		</div>
	    <div class="modal-body" id="rma-modal-body" data-modal-id = '' style='padding: 0px;'>
	    	
	    	<ul class="nav nav-tabs nav-tabs-ar">
				<li class="active"><a href="#iso_quality" data-toggle="tab"><i class="fa fa-qrcode"></i> RMA Items</a></li>
				<?php if($notes != ''): ?>
					<li class=""><a href="#iso_req"><i class="fa fa-exclamation-triangle"></i> Confirmation</a></li>
				<?php endif; ?>
				<li class=""><a href="#iso_match"><i class="fa fa-truck"></i> Print</a></li>
			</ul><!-- nav-tabs -->
			
			<div class="tab-content">

				<div class="tab-pane active" id="iso_quality">
				    <form action="#" method="post" style="padding: 7px;">
						<b class="rma_content_title"><i class="fa fa-truck" aria-hidden="true"></i> Select Serials for RMA</b><br><br>
						<table class="table table-hover table-striped table-condensed">
							<thead>
								<tr>
									<th>Part</th>
									<th>Serial</th>
									<th></th>
								</tr>
							</thead>
							<tbody class='rma_parts'>
								
							</tbody>
						</table>
						
						<div class='row'>
							<div class="col-md-12">
								<button class="btn-flat success btn-sm pull-right btn_rma_parts" data-form="" data-callback="" data-element="">Confirm</button>
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
