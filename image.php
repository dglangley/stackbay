<!DOCTYPE html>
<html>
<head>
	<title>Stackbay</title>
	<?php
		include_once 'inc/scripts.php';
	?>
<link rel="stylesheet" href="css/dropzone.css">
<style type="text/css">
  .dropzone {
	border:2px dashed #357ebd;
  }
</style>
</head>
<body>

<BR><BR><BR><BR><BR><BR>
    <!-- The file upload form used as target for the file upload widget -->
<div id="imageDropzone" class="dropzone">
	<div class="fallback">
		<input name="file" type="file" multiple />
		<input type="submit" value="Upload" />
	</div>
</div>

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<script src="js/dropzone.js"></script>
<script type="text/javascript">
Dropzone.autoDiscover = false;
// Dropzone class:
var imageDropzone = new Dropzone ("div#imageDropzone",{
  url: "catchImage.php",
  paramName: "file", // The name that will be used to transfer the file
  maxFilesize: 2, // MB
  uploadMultiple: true,
  clickable: true,
  addRemoveLinks: true,
  dictRemoveFile: "Remove",
  acceptedFiles: ".png, .jpg, .jpeg, .gif",
  dictDefaultMessage: "<h4>Drop File(s) Here or Click to Upload</h4>",
  accept: function(file, done) {
    if (file.name == "justinbieber.jpg") {
      done("Naha, you don't.");
    }
    else { done(); }
  }
});
</script>

</body>
</html>
