<style>
    #search_row {
        background: transparent !important;
    }
</style>

<div class="modal modal-alert fade" id="modal-component-available" tabindex="-1" role="dialog" aria-labelledby="modalcomponentTitle">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                <h4 class="modal-title" id="modalcomponentTitle"><i class="fa fa-shopping-cart" aria-hidden="true"></i> Component Request</h4>
            </div>
            <div class="modal-body component-modal" id="component-modal-body" data-origin ="component_info" data-oldid = "false">
				<div class="row">
                    
				</div>
                <ul class="nav nav-tabs nav-tabs-ar">
                    <li class="active"><a href="#item_stock_avail"><i class="fa fa-list"></i> Available Components</a></li>
                </ul><!-- nav-tabs -->
                
                <div class="tab-content">
                    
                    <div class="tab-pane active" id="item_stock_avail">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped table-condensed" id="items_table" style="margin-top:1.5%;">
                                <thead>  
                                    <th class='col-md-10'>Item Information</th>
                                    <!-- <th class='col-md-2'></th>
                                    <th class='col-md-2'></th> -->
                                    <th class='col-md-2'>Requested</th>
                                </thead>
                                <tbody id="stock_component_avail" style = "font-size:13px;">
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm component_pull_submit_avail" data-dismiss="modal">Save</button>
                    </div>

                </div>
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
