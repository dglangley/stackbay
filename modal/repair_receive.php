<style>
    #search_row {
        background: transparent !important;
    }
</style>

<div class="modal modal-alert fade" id="modal-repair-receive" tabindex="-1" role="dialog" aria-labelledby="modalcomponentTitle">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                <h4 class="modal-title" id="modalshipTitle"><i class="fa fa-list" aria-hidden="true"></i> <span></span></h4>
            </div>
            <div class="modal-body component-modal" id="component-modal-body" data-origin ="component_info" data-oldid = "false">
                <?php if((getCompany($ORDER['companyid']) != 'Ventura Telephone')) { ?>
                    <div id="alert_message" class="alert alert-warning fade in text-center alert-receive" style="display: none; width: 100%; z-index: 9999; top: 95px;">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
                        <strong id="alert_title">Warning</strong>: You are receiving a customer unit into our stock. Are you sure?!?
                    </div>
                <?php } ?>
                <?php if((getCompany($ORDER['companyid']) == 'Ventura Telephone')) { ?>
                    <div id="alert_message" class="alert alert-warning fade in text-center alert-ship" style="width: 100%; z-index: 9999; top: 95px;">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
                        <strong id="alert_title">Warning</strong>: Attempting to Ship an Internal Item. 
                    </div>
                <?php } ?>
                <form id="receive_form" action="repair_activities.php" method="post">
                    <div class="row ship_option" style="margin: 0;">
                        <!-- <div class="radio"> -->
                            <label class="radio-inline"><input type="radio" name="optradio" disabled>Billable</label>
                            <label class="radio-inline"><input type="radio" name="optradio" checked>Non-Billable / Relinquished</label>
                        <!-- </div> -->
                    </div>

                    <br>

    				<div class="row" style="margin: 0;">
                        <div class="col-md-4" style="padding-left: 0px !important;">
                            <?=loc_dropdowns('place')?>
                        </div>
                        
                        <div class="col-md-3">
                            <?=loc_dropdowns('instance')?>
                        </div>

                        <div class="col-md-5">
                            <?=dropdown('conditionid', '5', '', '',false)?>
                        </div>
    				</div>
                    <br>

                    <?php 
                        $repair_inventory = array();
                        $query = "SELECT * FROM inventory WHERE repair_item_id = ".prep($repair_item_id).";";
                        $result = qdb($query) or die(qe() . " $query");

                        while ($row = $result->fetch_assoc()) {
                            $repair_inventory[] = $row;
                        }

                        //print_r($repair_inventory);
                    ?>

                    <table class="table table-hover table-striped table-condensed">
                        <thead>
                            <tr>
                                <th>Part</th>
                                <th>Serial</th>
                            </tr>
                        </thead>
                        <tbody class=''>
                            <?php 
                                foreach($repair_inventory as $item) {
                                    echo "<tr>";
                                    echo "<td>".$item['partid']."</td>";
                                    echo "<td>".$item['serial_no']." <input class='hidden' name='serial_no[]' value='".$item['serial_no']."'></td>";
                                    echo "</tr>";
                                }
                            ?>
                        </tbody>
                    </table>
                    

                    <br>
                    <p class="modal_message"></p>
                    <div class="row" style="margin: 0;">
                        <div class="col-md-12">
                            <input class="hidden" name="ro_number" value="<?=$order_number?>">
                            <button style="margin-top: 10px" class="btn-sm btn btn-primary pull-right btn-update" type="submit" name="type" value="receive" data-datestamp="">Confirm</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer text-center">
                <div class="col-md-8">
                    <!-- <textarea rows="2" class="form-control" placeholder="Notes" name="notes"></textarea> -->
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-default btn-sm btn-dismiss" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>
