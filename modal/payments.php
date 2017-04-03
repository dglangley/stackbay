<div class="modal modal-alert fade" id="modal-payment" tabindex="-1" role="dialog" aria-labelledby="modalpaymentTitle">
    <div class="modal-dialog" role="document">
        <form action="/save-payments.php" method="post" style="padding: 7px;">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                    <h4 class="modal-title" id="modalPaymentTitle"><i class="fa fa-usd" aria-hidden="true"></i> Payment Information</h4>
                </div>
                <div class="modal-body payment-modal" id="payment-modal-body" data-origin ="payment_info" data-oldid = "false">
					<div class="row">
					    <div class="col-md-6">
					        <input class="form-control input-sm" type="text" name="payment_ID" placeholder="Check #">
					    </div>
					    <div class="col-md-6">
					        <div class="input-group date datetime-picker-line">
                                <input type="text" name="payment_date" class="form-control input-sm" value="<?=date("m/d/Y")?>" style="min-width:50px;">
                                <span class="input-group-addon">
                                    <span class="fa fa-calendar"></span>
                                </span>
                            </div>
					    </div>
					</div>
					<br>
					<div class="row">
					    <div class="col-md-6">
					        <input class="form-control input-sm" type="text" name="payment_amount" placeholder="Amount">
					    </div>
					    <div class="col-md-6">
					        <!--<input class="form-control input-sm" type="text" name="companyid" value="">-->
					    </div>
					</div>
					<!--Hidden Required Fields-->
					<div class="row" style="display: none;">
					    <div class="col-md-6">
					        <!--<input class="form-control input-sm" type="text" name="companyid" value="">-->
					    </div>
					    <div class="col-md-6">
					        <input class="form-control input-sm" type="text" name="<?=($o['type'] == 'Purchase' ? "po" : "so");?>_order" value="<?=$order_number;?>">
					    </div>
					</div>
                </div>
                <div class="modal-footer text-center">
                    <button type="button" class="btn btn-default btn-sm btn-dismiss" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>
