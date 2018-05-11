
<div class="modal modal-alert fade" id="modal-ruleset" tabindex="-1" role="dialog" aria-labelledby="modalAlertTitle">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form action="ruleset_edit.php" method="POST" enctype="multipart/form-data">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
          <h4 class="modal-title" id="modalAlertTitle">Save Ruleset</h4>
        </div>
        <div class="modal-body" id="ruleset-modal-body">
          <input type="hidden" name="rulesetid" value="<?= $rulesetid; ?>">
          <div class="row-fluid">
            <div class="row">
              <div class="col-md-12">
                <input type="text" class="input-sm form-control" name="name" value="<?= $RULESET_FILTERS['name']; ?>" placeholder="Name" />
              </div>
              <div id="ruleset_inputs">

              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer text-center">
          <button type="button" class="btn btn-default btn-sm btn-dismiss" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn <?=($rulesetid ? 'btn-success' : 'btn-primary'); ?> btn-sm" idata-dismiss="modal" data-form="" data-callback="" data-element=""><?=($rulesetid ? 'Update' : 'Save'); ?></button>
        </div>
      </div>
    </form>
  </div>
</div>
