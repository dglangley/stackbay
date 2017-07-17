<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/getUser.php';
?>
<div class="modal modal-trello fade" id="modal-trello" tabindex="-1" role="dialog" aria-labelledby="modalTrelloTitle">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
        <h4 class="modal-title" id="modalTrelloTitle">Please leave your feedback here:</h4>
	  </div>
      <div class="modal-body" id="modalTrelloBody" data-user='<?=getUser($U['id'])?>'>
          <div class="row-fluid">
            <textarea id='tfeedback'name="trello-feedback" class ='form-control'/></textarea>
          </div>
      </div>
      <div class="modal-footer text-center">
   		<button type="button" class="btn btn-default btn-sm btn-dismiss" data-dismiss="modal">Cancel</button>
   		<button type="button" class="btn btn-primary btn-sm" id="trello-continue" data-dismiss="modal" data-form="" data-callback="" data-element="">Continue</button>
	  </div>
	</div>
  </div>
</div>

<div class="" id ="leave_feedback" style='position:fixed;bottom:10px;right:10px;cursor:pointer;'><a>Report a Problem [+]</a></div>