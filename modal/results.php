<!-- Modal -->
<div class="modal" id="marketModal" role="dialog" aria-labelledby="marketModalLabel">
  <div class="modal-dialog" role="document">
    <form class="modal-form" method="post" action="/json/send-rfq.php">
	<input type="hidden" name="suspend" value="">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="marketModalLabel">Results</h4>
      </div>
      <div class="modal-body modal-striped modal-hover"></div>
      <div class="modal-footer">
		<p class="text-left">
			Subject
			<input type="text" name="message_subject" value="" class="form-control message-subject">
		</p>
		<p class="text-left">
			Message
			<textarea name="message_body" class="message-body" rows="5"></textarea><br/>
		</p>
		<p>
        	<button type="button" class="btn btn-warning pull-left btn-suspend"><i class="fa fa-tags"></i> Tag for Later</button>
        	<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
	        <button type="submit" class="btn btn-primary" id="modal-submit"><i class="fa fa-paper-plane"></i> Send RFQ</button>
		</p>
      </div>
    </div>
    </form>
  </div>
</div>
