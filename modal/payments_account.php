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
					    <div class="col-md-3" style="padding: 0 5px">
					        <select class="form-control input-sm payment-type" name="payment_type">
					            <option value="Check">Check</option>
					            <option value="Credit Card">Credit Card</option>
					            <option value="Wire Transfer">Wire Transfer</option>
					            <option value="ACH">ACH</option>
					            <option value="Paypal">Paypal</option>
					            <option value="Other">Other</option>
					        </select>
					    </div>
					    <div class="col-md-3" style="padding: 0 5px">
					        <input class="form-control input-sm payment-placeholder" type="text" name="payment_ID" placeholder="Ref #">
					    </div>
					    <div class="col-md-3" style="padding: 0 5px">
					        <div class="input-group date datetime-picker-line">
                                <input type="text" name="payment_date" class="form-control input-sm" data-date="<?=date("m/d/Y")?>" value="<?=date("m/d/Y")?>" style="min-width:50px;">
                                <span class="input-group-addon">
                                    <span class="fa fa-calendar"></span>
                                </span>
                            </div>
					    </div>
					    <div class="col-md-3" style="padding: 0 5px">
					        <input class="form-control input-sm" type="text" name="payment_amount" placeholder="Amount" style="text-align: right;">
					    </div>
					</div>
					<br>
                    <div class="payment-data"></div>
					
                </div>
                <div class="modal-footer text-center">
                    <div class="col-md-8">
                        <textarea rows="2" class="form-control" placeholder="Notes" name="notes"></textarea>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-default btn-sm btn-dismiss" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">Save</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
