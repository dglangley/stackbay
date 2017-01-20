<!DOCTYPE html>
<html>
<head>
	<title>Stackbay</title>
	<?php
		include_once 'inc/scripts.php';
	?>
<style type="text/css">
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

</body>
</html>
