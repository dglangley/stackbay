<?php
    //This is just in case nothing was declared
    //Variables we will be using in this modal
    //$o['type'];
    
    $query = "";
    $invoice_items = array();
    $bill_items = array();
    $credit_items = array();
    
    if($o['type'] == 'Purchase') {
        $query = "SELECT * FROM bills i, bill_items t WHERE i.bill_no = t.bill_no AND i.po_number = ".res($order_number).";";
        
        $result = qdb($query) OR die(qe().' '.$query);
    
        if(mysqli_num_rows($result) > 0){
            $rows = mysqli_fetch_assoc($result);
    	    $bill_items[] = $rows;
    	}
    } else if($o['type'] == 'Sales') {
        $query = "SELECT * FROM invoices i, invoice_items t WHERE i.invoice_no = t.invoice_no AND i.order_number = ".res($order_number).";";
        
        $result = qdb($query) OR die(qe().' '.$query);
    
        if(mysqli_num_rows($result) > 0){
            $rows = mysqli_fetch_assoc($result);
    	    $invoice_items[] = $rows;
    	}
    	
    	$query = "SELECT * FROM sales_credits i, sales_credit_items t WHERE i.id = t.cid AND i.order_num = ".res($order_number).";";
        
        $result = qdb($query) OR die(qe().' '.$query);
    
        if(mysqli_num_rows($result) > 0){
            $rows = mysqli_fetch_assoc($result);
    	    $credit_items[] = $rows;
    	}
    } else {
        //Future space for Returns or other forms
    }
    
?>

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
					    <div class="col-md-3">
					        <select class="form-control input-sm" name="payment_type">
					            <option value="Wire Transfer">Wire Transfer</option>
					            <option value="Check">Check</option>
					            <option value="Credit Card">Credit Card</option>
					            <option value="Paypal">Paypal</option>
					            <option value="Other">Other</option>
					        </select>
					    </div>
					    <div class="col-md-3">
					        <input class="form-control input-sm" type="text" name="payment_ID" placeholder="Check #">
					    </div>
					    <div class="col-md-3">
					        <div class="input-group date datetime-picker-line">
                                <input type="text" name="payment_date" class="form-control input-sm" value="<?=date("m/d/Y")?>" style="min-width:50px;">
                                <span class="input-group-addon">
                                    <span class="fa fa-calendar"></span>
                                </span>
                            </div>
					    </div>
					    <div class="col-md-3">
					        <input class="form-control input-sm" type="text" name="payment_amount" placeholder="Amount">
					    </div>
					</div>
					<br>
					<div class="row">
					    <div class="col-md-12">
    					    <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Sel</th>
                                        <th>Type</th>
                                        <th>Number</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($invoice_items)) { ?>
                                        <?php foreach($invoice_items as $radio_item): ?>
                                        <tr>
                                            <td><input type="radio" name="reference_button" value="invoice <?=$radio_item['invoice_no'];?>"></td>
                                            <td>Invoice</td>
                                            <td><?=$radio_item['invoice_no'];?></td>
                                            <td><?=$radio_item['qty'] * $radio_item['price'];?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php } ?>
                                    
                                    <?php if(!empty($bill_items)) { ?>
                                        <?php foreach($bill_items as $radio_item): ?>
                                        <tr>
                                            <td><input type="radio" name="reference_button" value="bill <?=$radio_item['bill_no'];?>"></td>
                                            <td>Bill</td>
                                            <td><?=$radio_item['bill_no'];?></td>
                                            <td><?=$radio_item['qty'] * $radio_item['price'];?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php } ?>
                                    
                                    <?php if(!empty($credit_items)) { ?>
                                        <?php foreach($credit_items as $radio_item): ?>
                                        <tr>
                                            <td><input type="radio" name="reference_button" value="credit <?=$radio_item['order_num'];?>"></td>
                                            <td>Credit</td>
                                            <td><?=$radio_item['order_num'];?></td>
                                            <td>-<?=$radio_item['qty'] * $radio_item['amount'];?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php } ?>
    					        </tbody>
    					    </table>
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
