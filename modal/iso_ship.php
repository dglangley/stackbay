<?php
	$iso_title = (empty($ISO) ? '<i class="fa fa-list" aria-hidden="true"></i> Shipped Contents' : '<i class="fa fa-dropbox" aria-hidden="true"></i> Pending for Shipment');	
?>

<div class="modal modal-alert fade" id="modal-iso" tabindex="-1" role="dialog" aria-labelledby="modalAlertTitle">
<div class="modal-dialog" role="document">
<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
			<h4 class="modal-title">ISO Quality Check SO # <?=$order_number;?></h4>
		</div>
	    <div class="modal-body" id="ISO-modal-body" data-modal-id = '' style='padding: 0px;'>
		    <ul class="nav nav-tabs nav-tabs-ar">
				<li class="active"><a href="#iso_quality" data-toggle="tab"><i class="fa fa-qrcode"></i> Parts Statement</a></li>
				<?php //if($ORDER['public_notes'] != ''): ?>
					<li class=""><a href="#iso_req" <?=(empty($ISO) ? 'data-toggle="tab"' : '');?>><i class="fa fa-exclamation-triangle"></i> Special Requirements</a></li>
				<?php //endif; ?>
				<li class=""><a href="#iso_match" <?=(empty($ISO) ? 'data-toggle="tab"' : '');?>><i class="fa fa-truck"></i> Shipping Confirmation</a></li>
			</ul><!-- nav-tabs -->
			
			<div class="tab-content">

				<!-- Materials pane -->
				<div class="tab-pane active" id="iso_quality">
				    <form action="#" method="post" style="padding: 7px;">
						<b class="iso_content_title"><?=$iso_title;?></b><br><br>
						<table class="table table-hover table-striped table-condensed">
							<thead>
								<tr>
									<th>Box</th>
									<th>Part</th>
									<th>Serial</th>
									<th>Comments</th>
								</tr>
							</thead>
							<tbody class='iso_broken_parts'>
								<?php 
									if(! empty($ISO)) {
										foreach($ISO as $packageid) {
											echo buildPackageRows($order_number, $order_type, true, $packageid);
										}
									} else {
										echo buildPackageRows($order_number, $order_type, true);
									}
								?>
							</tbody>
						</table>
						
						<div class='row'>
							<div class="col-md-12">
								The list above accurately reflects the part number, HECI, cosmetic condition and component condition for this shipment.<br><br>
								<span class='pull-right'><b>Approved by</b>: <?= $U['name']; ?></span><br><br>
								<?php if(! empty($ISO)) { ?>
									<button class="btn-flat success btn-sm pull-right btn_iso_parts" data-form="" data-callback="" data-element="">Approve</button>
								<?php } else { ?>
									<button class="btn-flat primary btn-sm pull-right btn_iso_parts_continue" data-form="" data-callback="" data-element="">Next</button>
								<?php } ?>
							</div>
						</div>
					</form>
				</div>
				
				<div class="tab-pane" id="iso_req">
				    <form action="#" method="post" style="padding: 7px;">
				    	<b>Notes</b><br>
						<?= ($ORDER['public_notes']?:'No Requirements'); ?> 
						
						<br><br>
						
						<div class='row'>
							<div class="col-md-12">
								<span class='pull-right'>The conditions above have been met.</span><br><br>
								<?php if(! empty($ISO)) { ?>
									<button class="btn-flat primary btn-sm pull-right btn_iso_req" data-special="<?=($ORDER['public_notes']?'yes':'n/a');;?>" data-form="" data-callback="" data-element="">Confirm</button>
								<?php } ?>
							</div>
						</div>
					</form>
				</div>
				
				<!-- Materials pane -->
				<div class="tab-pane" id="iso_match">
					<form method="get" action="shipping_edit.php" enctype="multipart/form-data" style="padding: 7px;">

						<input type="hidden" name="order_number" value="<?=$order_number;?>">
						<input type="hidden" name="type" value="<?=$order_type;?>">
						<input type="hidden" name="iso" value="submit">

						<b>Shipping Address:</b><br>
						<?= address_out($ORDER['ship_to_id']); ?><br><br>
						
						<b>Shipping Instructions:</b><br>
						<?= getFreight('carrier',$ORDER['freight_carrier_id'],'','name'); ?>
						<?= (isset($ORDER['freight_service_id']) ? getFreight('services','',$ORDER['freight_service_id'],'method') : '') ?>
						<br><br>
						<b>Account:</b><br>
						<?= ((isset($selected_account) AND $selected_account) ? getFreight('account','',$ORDER['freight_account_id'],'account_no'): 'Prepaid'); ?>

						<div class='row'>
							<div class="col-md-12">
								<?php if(! empty($ISO)) { ?>
									<button class="btn-flat primary btn-sm pull-right" type="submit" name="print" value="true">Save & Print</button>
									<button class="btn-flat success btn-sm pull-right" type="submit" style='margin-right: 10px;'>Save</button>
								<?php } ?>
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
