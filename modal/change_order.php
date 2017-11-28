<div class="modal fade" id="changeOrderModal" role="dialog" aria-labelledby="modalCOTitle">
  <form method="POST" action="change_order.php">
  <input type="hidden" name="order_number" value="">
  <input type="hidden" name="order_type" value="">
  <input type="hidden" name="change_type" value="">
  <input type="hidden" name="line_item_id" value="">

  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
        <h4 class="modal-title" id="modalCOTitle">Change Order</h4>
	  </div>
      <div class="modal-body" id="modalCOBody"></div>
      <div class="modal-footer text-center">
   		<button type="submit" name="new_item" value="1" class="btn btn-default btn-sm pull-left btn-COitem" data-form="" data-callback="" data-element="">New Line Item</button>
   		<button type="submit" name="new_order" value="1" class="btn btn-default btn-sm pull-right btn-COorder">New Service Order</button>
	  </div>
	</div>
  </div>

  </form>
</div>
