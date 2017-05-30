<div class="modal modal-alert fade" id="modal-component" tabindex="-1" role="dialog" aria-labelledby="modalcomponentTitle">
    <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                    <h4 class="modal-title" id="modalcomponentTitle"><i class="fa fa-shopping-cart" aria-hidden="true"></i> Component Request</h4>
                </div>
                <div class="modal-body component-modal" id="component-modal-body" data-origin ="component_info" data-oldid = "false">
					<div class="row">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped table-condensed" id="items_table" style="margin-top:1.5%;">
                                <thead>  
                                    <th class='col-md-10'>Item Information</th>
                                    <th class='col-md-2'>Qty</th>
                                    <th></th>
                                    <th></th>
                                </thead>
                                <tbody id="right_side_main" style = "font-size:13px;">
                                </tbody>
                                <tfoot id = "search_input">                                             
                                    <tr id ='search_row' style = 'padding:50px;background-color:#eff0f6;'>
                                        <td id = 'search'>
                                            <div class='input-group'>
                                              <input type='text' class='form-control input-sm' id = 'go_find_me' placeholder='SEARCH FOR...'>
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
					</div>
                </div>
                <div class="modal-footer text-center">
                    <div class="col-md-8">
                        <!-- <textarea rows="2" class="form-control" placeholder="Notes" name="notes"></textarea> -->
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-default btn-sm btn-dismiss" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm component_request_submit" data-dismiss="modal">Save</button>
                    </div>
                </div>
            </div>
    </div>
</div>
