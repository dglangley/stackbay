<div class="modal modal-inventory fade" id="modal-inventory" role="dialog" aria-labelledby="modalInventoryTitle">
  <form method="POST" action="/save-inventory.php">
  <input type="hidden" name="inventoryid" id="inventory-inventoryid" value="">

  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
        <h4 class="modal-title" id="modalInventoryTitle"></h4>
	  </div>
      <div class="modal-body" id="modalInventoryBody">
		<div class="table-responsive">
			<table class="table table-condensed">
				<tr>
					<td colspan="3">
						<select name="inventory-partid" id="inventory-partid" data-partid="" size="1" class="form-control parts-selector">
						</select>
					</td>
				</tr>
				<tr>
					<td class="col-sm-3">
						<strong>Serial No.</strong>
					</td>
					<td colspan="2">
						<input type="text" name="inventory-serial" id="inventory-serial" value="" class="form-control input-sm" />
					</td>
				</tr>
				<tr>
					<td class="col-sm-3">
						<strong>Location</strong>
					</td>
					<td class="col-sm-5">
						<select name="inventory-locationid" id="inventory-locationid" size="1" class="location-selector" data-noreset="1">
						</select>
					</td>
					<td class="col-sm-4">
					</td>
				</tr>
				<tr>
					<td class="col-sm-3">
						<strong>Condition</strong>
					</td>
					<td class="col-sm-5">
						<select name="inventory-conditionid" id="inventory-conditionid" size="1" class="condition-selector">
						</select>
					</td>
					<td class="col-sm-4">
					</td>
				</tr>
				<tr>
					<td class="col-sm-3">
						<strong>Notes</strong>
					</td>
					<td colspan="2">
						<input type="text" name="inventory-notes" id="inventory-notes" value="" class="form-control input-sm" />
					</td>
				</tr>
				<tr>
					<td class="col-sm-3">
						<strong>Status</strong>
					</td>
					<td class="col-sm-5">
						<div id="inventory-status"></div>
					</td>
					<td class="col-sm-4">
					</td>
				</tr>
			</table>
		</div>
      </div>
      <div class="modal-footer text-center">
   		<button type="button" class="btn btn-default btn-sm btn-dismiss" data-dismiss="modal">Cancel</button>
   		<button type="button" class="btn btn-success btn-md" id="inventory-save" data-form="" data-callback="" data-element=""><i class="fa fa-save"></i> Save</button>
	  </div>
	</div>
  </div>

  </form>
</div>
