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
                <h4 class="modal-title" id="modalcomponentTitle"><i class="fa fa-shopping-cart" aria-hidden="true"></i> <?=((empty($order_number)) ? 'Materials Quote' : 'Materials Request');?></h4>
            </div>
            <div class="modal-body component-modal" id="component-modal-body" data-origin ="component_info" data-oldid = "false">
				<div class="row">
                    
				</div>
                <ul class="nav nav-tabs nav-tabs-ar">
                    <li class="active"><a href="#request" data-toggle="tab"><i class="fa fa-qrcode"></i> Materials Request</a></li>
                    <?php if(! empty($order_number)) { ?>
                        <li class="" data-toggle="tab"><a href="#stock"><i class="fa fa-list"></i> Available Materials</a></li>
                        <li class="" data-toggle="tab"><a href="#item_stock"><i class="fa fa-truck"></i> Pull Materials</a></li>
                    <?php } ?>
                </ul><!-- nav-tabs -->
                
                <div class="tab-content">
                    <?php if(empty($order_number)) { ?>
                        <form action="/materials_handler.php" method="post">
                            <input type="hidden" name="type" value="<?=$_REQUEST['type'];?>">
                            <input type="hidden" name="task" value="<?=$_REQUEST['task'];?>">
                    <?php } ?>
                        <!-- Materials pane -->
                        <div class="tab-pane active" id="request">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped table-condensed" id="items_table" style="margin-top:1.5%;">
                                    <thead>  
                                        <th class='col-md-<?=((empty($order_number)) ? '8' : '10');?>'>Item Information</th>
                                        <th class='col-md-2'>Qty</th>
                                        <?php if(empty($order_number)){
                                            echo '<th class="col-md-2">PRICE</th>';
                                            echo '<th></th>';
                                        } else {
                                            echo '<th></th>';
                                        } ?>
                                    </thead>
                                    <tbody id="search_input">                                             
                                        <tr id='search_row' style = 'padding:50px;background-color:#eff0f6;'>
                                            <td id='search' colspan="<?=((empty($order_number)) ? '3' : '2');?>">
                                                <div class='input-group' style="width: 100%;">
                                                    <input type='text' class='form-control input-sm' id='partSearch' placeholder='SEARCH FOR MATERIAL...'>
                                                    <span class='input-group-btn'>
                                                        <button class='btn btn-sm btn-primary li_search_button'><i class='fa fa-search'></i></button>              
                                                    </span>
                                                </div>
                                            </td>
                                         <!--    <td>
                                                <input class='form-control input-sm' readonly='readonly' tabIndex='-1' type='text' name='ni_qty' id = 'new_item_qty' placeholder='QTY' value = ''>
                                            </td>
     -->
                                            <td colspan="1" id="check_collumn"> 
                                                <a class="btn-sm btn btn-success pull-right" id="part_entry">
                                                <i class="fa fa-save fa-4" aria-hidden="true"></i></a>
                                            </td>
                                        </tr>

                                        <!-- Nothing Found trigger -->
                                        <tr class='nothing_found' style='display: none;'>
                                            <td colspan='12'>
                                                <span style='text-align:center; display: block; padding-top: 10px; font-weight: bold;'>Nothing Found</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <?php if(! empty($order_number)) { ?>
                                <button class="btn btn-primary btn-sm pull-right stock_check" data-form="" data-callback="" data-element="">Next</button>
                            <?php } else { ?>
                                <button class="btn btn-primary btn-sm pull-right quote_materials" data-form="" data-callback="" data-element="" type="submit">Quote</button>
                            <?php } ?>
                        </div>
                    <?php if(empty($order_number)) { ?>
                        </form>
                    <?php } ?>
                    <div class="tab-pane" id="stock">
                        <form action="/materials_handler.php" method="post" class="list">
                            <input type="hidden" name="item_id" value="<?=$item_id;?>">
                            <input type="hidden" name="order_number" value="<?=$_REQUEST['order'];?>">
                            <input type="hidden" name="type" value="<?=$_REQUEST['type'];?>">
                            <input type="hidden" name="action" value="request">
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
                                <textarea class="form-control" name="comment" id="comment" placeholder="Notes..."></textarea>
                                <br>
                            </div>
                            <div class="pull-right">
                                <!-- <button class="btn btn-success btn-sm component_request_pull add_component" data-component="fulfill">Fulfill From Stock</button> -->
                                <button data-type="<?= $_REQUEST['type'];?>" class="btn btn-success btn-sm add_component" style='display: none;'>Fulfill From Stock</button>
                                <button type="submit" class="btn btn-primary btn-sm">Request Purchase Order</button>
                            </div>
                        </form>
                    </div>

                    <div class="tab-pane" id="item_stock">
                        <form action="/materials_handler.php" method="post">
                            <input type="hidden" name="order_number" value="<?=$_REQUEST['order'];?>">
                            <input type="hidden" name="type" value="<?=$_REQUEST['type'];?>">
                            <input type="hidden" name="action" value="pull">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped table-condensed" id="items_table" style="margin-top:1.5%;">
                                    <thead>  
                                        <th class='col-md-10'>Item Information</th>
                                        <th class='col-md-2'>Outstanding</th>
                                    </thead>
                                    <tbody id="stock_component" style = "font-size:13px;">
                                    </tbody>
                                </table>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Submit</button>
                        </form>
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
