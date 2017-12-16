<div class="modal fade" id="changeOrderModal" role="dialog" aria-labelledby="modalCOTitle">
  <form method="POST" action="change_order.php" class="form-inline">
  <input type="hidden" name="order_number" value="">
  <input type="hidden" name="order_type" value="">
  <input type="hidden" name="line_item_id" value="">

  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
        <h4 class="modal-title" id="modalCOTitle">Change Order</h4>
	  </div>
      <div class="modal-body" id="modalCOBody">
		<label>Description / Scope</label>
		<textarea class="form-control input-sm" name="description" rows="4"></textarea>
      </div>
      <div class="modal-footer text-right">
		<div class="pull-left form-group">
			<div class="pull-right" style="padding-left:6px"><label>Charge</label></div>
			<div class="input-group">
				<div class="input-group-addon"><i class="fa fa-usd"></i></div>
				<input type="text" name="charge" id="co_charge" class="form-control input-sm" placeholder="0.00">
			</div>
		</div>
<!--
   		<button type="button" name="new_order" value="1" class="btn btn-default btn-sm pull-right btn-COorder">New Service Order</button>
-->
		<button type="button" class="btn btn-default btn-sm btn-dismiss" data-dismiss="modal">Cancel</button>
   		<button type="submit" name="new_item" value="1" class="btn btn-primary btn-sm btn-COitem" data-form="" data-callback="" data-element="">Create CO</button>
	  </div>
	</div>
  </div>

  </form>
</div>
<script type="text/javascript">
	$(document).ready(function() {
		$(".btn-COitem").click(function() {
		});
	});
</script>
