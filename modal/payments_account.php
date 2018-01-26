<?php 
    //include_once $_SERVER["ROOT_DIR"] . '/inc/getFinancialAccounts.php';

    // $finance_html = '';
    // foreach(getFinancialAccounts() as $r) {
    //     $finance_html .= '<option value="'.$r['id'].'">';
    //     $finance_html .= $r['nickname'];
    //     $finance_html .= '</option>';
    // }
?>

<div class="modal modal-alert fade" id="modal-payment" role="dialog" aria-labelledby="modalpaymentTitle">
    <div class="modal-dialog" role="document">
        <form action="/update-payments.php" method="post" style="padding: 7px;">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="col-md-3" style="padding: 0 5px">
                                <select class="form-control input-sm payment-type select2" name="payment_type" id="payment_type">
                                    <option value="Check" selected>Check</option>
                                    <option value="Credit Card">Credit Card</option>
                                    <option value="Wire Transfer">Wire Transfer</option>
                                    <option value="ACH">ACH</option>
                                    <option value="Paypal">Paypal</option>
                                    <option value="Other">Other</option>
                                </select>


<!--                                 <select class="form-control input-sm payment-type select2" name="financial_account">
                                    <?=$finance_html;?>
                                </select> -->
                            </div>
                            <div class="col-md-3" style="padding: 0 5px">
                                <input class="form-control input-sm payment-placeholder" type="text" name="payment_number" placeholder="Check #">
                            </div>
                            <div class="col-md-3" style="padding: 0 5px">
                                <div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY">
                                    <input type="text" name="payment_date" class="form-control input-sm" data-date="<?=date("m/d/Y")?>" value="<?=date("m/d/Y")?>" style="min-width:50px;">
                                    <span class="input-group-addon">
                                        <span class="fa fa-calendar"></span>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-3" style="padding: 0 5px">
                                <input class="form-control input-sm total_amount" type="text" name="payment_amount" placeholder="Total" style="text-align: right;" readonly>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-body payment-modal" id="payment-modal-body" data-origin ="payment_info" data-oldid = "false">
					<br>
                    <div class="payment-data">
                        
                    </div>

                    <!-- Hidden values for the search filter preservation -->
					
                </div>
                <div class="modal-footer text-center">
                    <input type="hidden" value="<?=$_REQUEST['report_type'];?>" name="summary" class="form-control input-sm">
                    <input type="hidden" value="<?=$_REQUEST['START_DATE'];?>" name="start" class="form-control input-sm">
                    <input type="hidden" value="<?=$_REQUEST['END_DATE'];?>" name="end" class="form-control input-sm">
                    <input type="hidden" value="<?=$_REQUEST['orders_table'];?>" name="table" class="form-control input-sm">
                    <input type="hidden" value="<?=$_REQUEST['order'];?>" name="order" class="form-control input-sm">
                    <input type="hidden" value="<?=$_REQUEST['companyid'];?>" name="companyid" class="form-control input-sm">
                    <input type="hidden" value="<?=$_REQUEST['filter'];?>" name="filter" class="form-control input-sm">
                    
                    <?php 
                        if(! empty($_REQUEST['order_type'])) { 
                            foreach($_REQUEST['order_type'] as $type) {
                                echo '<input type="hidden" value="'.$type.'" name="order_type[]" class="form-control input-sm">';
                            }
                        } 
                    ?>

                    <div class="col-md-8">
                        <textarea rows="2" class="form-control notes" placeholder="Notes" name="notes"></textarea>
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
