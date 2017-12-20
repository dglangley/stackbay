
<div class="modal modal-parts fade" id="modal-parts" role="dialog" aria-labelledby="modalPartsTitle">
	<!-- REMEMBER TO REMOVE TAB INDEX 0 FROM ANY FUTURE MODALS WITH SELECT2's -->
	<div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
        <h4 class="modal-title" id="modalPartsTitle"></h4>
	  </div>
      <div class="modal-body" id="modalPartsBody" data-partid='295333'>
				<div class ="row" style = "padding-bottom:10px;">
					<div class='col-md-12'>
						<input id='pm-name' type="text" value="" class="form-control pm-field" data-field="part" placeholder="Part Number">
					</div>
				</div>
				<div class ="row" style = "padding-bottom:10px;">	
					<div class='col-md-8'>
						<input id='pm-heci' type="text" value="" class="form-control pm-field" data-field="heci" placeholder="HECI/CLEI"/>
					</div>
					<div class="col-md-4">
						<select id='pm-class' name="class[]" class="form-control pm-field" data-field="classid">
							<option value='equipment'>Equipment</option>
							<option value='component'>Component</option>
							<option value='material'>Material</option>
						</select>
					</div>
				</div>
				<div class ="row" style = "padding-bottom:10px;">	
					<div class='col-md-12'>
						<input id='pm-desc' type="text" name="descr[]" value="" class="form-control pm-field" data-field="description" placeholder="Description">
					</div>
				</div>
				<div class='row' style = 'padding-bottom:10px;'>
					<div class="col-md-6">
						<select id='pm-manf' name="manfid[]" class="form-control pm-field" data-field="manfid" style='width:100%;'></select>
					</div>
					<div class="col-md-6">
						<select id='pm-system' name="systemid[]" class="form-control pm-field" data-field="systemid" style='width:100%;'></select>
					</div>

				</div>
    	</div>
      <div class="modal-footer text-center">
   		<button type="button" class="btn btn-default btn-sm btn-dismiss" data-dismiss="modal">Cancel</button>
   		<button type="button" class="btn btn-primary btn-sm" id="parts-continue" data-dismiss="modal" data-form="" data-callback="" data-element="">Save</button>
	  </div>
	</div>
  </div>
</div>
