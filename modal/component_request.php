<style>
    #search_row {
        background: transparent !important;
    }
</style>

<div class="modal modal-alert fade" id="modal-component" tabindex="-1" role="dialog" aria-labelledby="modalcomponentTitle">
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
                    <li class="active"><a href="#request" data-toggle="tab"><i class="fa fa-qrcode"></i> Component Request</a></li>
                    <li class="" data-toggle="tab"><a href="#stock"><i class="fa fa-list"></i> Available Components</a></li>
                    <li class="" data-toggle="tab"><a href="#item_stock"><i class="fa fa-truck"></i> Pull Components</a></li>
                </ul><!-- nav-tabs -->
                
                <div class="tab-content">

                    <!-- Materials pane -->
                    <div class="tab-pane active" id="request">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped table-condensed" id="items_table" style="margin-top:1.5%;">
                                <thead>  
                                    <th class='col-md-10'>Item Information</th>
                                    <th class='col-md-2'>Qty</th>
                                    <th></th>
                                    <th></th>
                                </thead>
                                <tbody id="right_side_main" class="table_components" style = "font-size:13px;">
                                </tbody>
                                <tfoot id = "search_input">                                             
                                    <tr id ='search_row' style = 'padding:50px;background-color:#eff0f6;'>
                                        <td id = 'search'>
                                            <div class='input-group'>
                                                <input type='text' class='form-control input-sm' id = 'go_find_me' placeholder='SEARCH FOR COMPONENT...'>
                                                <span class='input-group-btn'>
                                                    <button class='btn btn-sm btn-primary li_search_button'><i class='fa fa-search'></i></button>              
                                                </span>
                                            </div>
                                        </td>
                                        <td><input class='form-control input-sm' readonly='readonly' tabIndex='-1' type='text' name='ni_qty' id = 'new_item_qty' placeholder='QTY' value = ''></td>
                                        <!-- <td></td> -->
                                        <td colspan="2" id="check_collumn"> 
                                            <a class="btn-sm btn-flat success pull-right multipart_sub">
                                            <i class="fa fa-save fa-4" aria-hidden="true"></i></a>
                                        </td>
                                    </tr>
                                    <!-- Adding load bar feature here -->
                                    <tr class='search_loading'><td colspan='12'><span style='text-align:center; display: none; padding-top: 10px;'>Loading...</span></td></tr>
                            
                                    <!-- dummy line for nothing found -->
                                    <tr class='nothing_found' style='display: none;'><td colspan='12'><span style='text-align:center; display: block; padding-top: 10px; font-weight: bold;'>Nothing Found</span></td></tr>
                                </tfoot>
                            </table>
                        </div>

                        <button class="btn btn-primary btn-sm pull-right stock_check" data-form="" data-callback="" data-element="">Next</button>
                    </div>
                    
                    <div class="tab-pane" id="stock">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped table-condensed" id="items_table" style="margin-top:1.5%;">
                                <thead>  
                                    <th class='col-md-6'>Item Information</th>
                                    <th class='col-md-2'>Requested</th>
                                    <th class='col-md-2'>Available</th>
                                    <!-- <th class='col-md-2'>Pulled</th> -->
                                </thead>
                                <tbody class="stock_component" style = "font-size:13px;">
                                </tbody>
                            </table>
                            <textarea class="form-control" id="comment" placeholder="Notes..."></textarea>
                            <br>
                        </div>
                        <div class="pull-right">
                            <!-- <button class="btn btn-success btn-sm component_request_pull add_component" data-component="fulfill">Fulfill From Stock</button> -->
                            <button class="btn btn-success btn-sm component_request_submit_pull add_component" data-component="pull">Fulfill From Stock</button>
                            <button type="submit" class="btn btn-primary btn-sm component_request_submit" data-dismiss="modal">Request Purchase Order</button>
                        </div>
                    </div>

                    <div class="tab-pane" id="item_stock">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped table-condensed" id="items_table" style="margin-top:1.5%;">
                                <thead>  
                                    <th class='col-md-10'>Item Information</th>
                                    <!-- <th class='col-md-2'></th>
                                    <th class='col-md-2'></th> -->
                                    <th class='col-md-2'>Outstanding</th>
                                </thead>
                                <tbody id="stock_component" style = "font-size:13px;">
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm component_pull_submit" data-dismiss="modal">Submit</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer text-center">
                <div class="col-md-8">
                    <!-- <textarea rows="2" class="form-control" placeholder="Notes" name="notes"></textarea> -->
                </div>
                <div class="col-md-4">
                    <!-- <button type="submit" class="btn btn-primary btn-sm component_request_submit" data-dismiss="modal">Save</button> -->
                    <button type="button" class="btn btn-default btn-sm btn-dismiss" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>
