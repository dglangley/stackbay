
<div class="modal fade" id="image-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<p class="pull-right">
			<input type="checkbox" name="watermark" id="watermark" value="1" checked><label for="watermark"> Watermark uploaded image(s)</label> &nbsp; &nbsp;
		</p>
        <h4 class="modal-title" id="service-image-title"></h4>
      </div>
      <div class="modal-body">
		<div class="imageSlider">
			<div class="imageSliderBody">
				<ul class="bxslider">
				</ul>
			</div>

			<div id="imagePager">
			</div>
		</div><!-- .imageSlider -->
      </div>

      <div class="modal-footer">
		<div id="imageServiceDropzone" class="dropzone" data-id="">
			<div class="fallback">
				<input name="file" type="file" multiple />
				<input type="submit" value="Upload" />
			</div>
		</div>
	  </div><!-- /.modal-footer -->

    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
