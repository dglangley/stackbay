<div class="modal modal-assignments fade" id="modal-assignments" role="dialog" aria-labelledby="modalAssignmentsTitle">
  <form method="POST" action="/save-inventory.php">
  <input type="hidden" name="inventoryid" id="assignments-inventoryid" value="">
  <input type="hidden" name="assignments" value="1">

  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
        <h4 class="modal-title" id="modalAssignmentsTitle">Don't Be a Tool</h4>
	  </div>
      <div class="modal-body" id="modalAssignmentsBody">
		<div class="table-responsive">
			<table class="table table-condensed">
				<tr>
					<td class="col-sm-3">
						<strong>Assign to</strong>
					</td>
					<td class="col-sm-9">
						<select name="assignmentid" size="1" class="form-control user-selector">
						</select>
					</td>
				</tr>
				<tr>
					<td class="col-sm-3">
						<strong>Notes</strong>
					</td>
					<td class="col-sm-9">
						<textarea name="assignments-notes" class="form-control" style="width:100%"></textarea>
					</td>
				</tr>
				<tr>
					<td class="col-sm-3">
						<strong>History</strong>
					</td>
					<td class="col-sm-9">
						<div id="assignments-history"></div>
					</td>
				</tr>
				<tr>
					<td class="col-sm-3">
						<strong>Status</strong>
					</td>
					<td class="col-sm-9">
						<div id="assignments-status"></div>
					</td>
				</tr>
			</table>
		</div>
      </div>
      <div class="modal-footer text-center">
   		<button type="button" class="btn btn-primary btn-sm pull-left assignments-save" id="btn-unassign">Unassign</button>
   		<button type="button" class="btn btn-default btn-sm btn-dismiss" data-dismiss="modal">Cancel</button>
   		<button type="button" class="btn btn-danger btn-md assignments-save" data-form="" data-callback="" data-element=""><i class="fa fa-tag"></i> Assign</button>
	  </div>
	</div>
  </div>

  </form>
</div>
