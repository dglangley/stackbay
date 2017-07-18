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
                <form action="rma_freight.php" method="post">
                    <div class="input-group">
                        <span class="input-group-addon">$</span>
                        <input class="form-control input-xs" tabindex="-1" type="text" id="freight" name="np_freight" value="" placeholder="0.00" readonly="">
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
