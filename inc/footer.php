
	<!-- scripts -->
	
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery.dataTables.js"></script>
    <script src="js/validation.js?id=<?php echo $V; ?>"></script>
<!--
	<script src="js/bootstrap-datepicker.js"></script>
-->
	<script src="js/jquery.cookie.js"></script>
	<!-- required for datetimepicker -->
	<script src="js/moment.min.js"></script>
	<script src="js/bootstrap-datetimepicker.js"></script>
    <script src="js/theme.js"></script>
    <script src="js/jquery-ui-1.10.2.custom.min.js"></script>

	<script src="js/DropdownHover.js"></script>
    <script src="js/select2.min.js"></script>

	<script src="js/dropzone.js"></script>
<!--
	<script src="https://api.trello.com/1/client.js?key=f7bebfb52058c4486f6cd4092fdb55a9"></script>
	<script src="js/trello.js"></script>
-->
	<script src="js/parts.js"></script>

	<script src="js/jquery.floatThead.min.js"></script>
	
	<!-- bxSlider Javascript file -->
	<script src="js/jquery.bxslider.min.js"></script>
	<script src="js/imageSlider.js"></script>

    <script src="js/price_format.js"></script>
    <script src="js/ventel.js?id=<?php echo $V; ?>"></script>
	
<?php
	// build errors output to be alerted into modal
	if (count($ALERTS)>0) {
		$alerts = '';
		// if there's just one error, output it as a standalone; if more than one, list them in bulleted format
		$num_alerts = count($ALERTS);
		if ($num_alerts>1) {
			$alerts = '<ul>Please be aware of the following errors:';
		}
		foreach ($ALERTS as $k => $err) {
			if ($k>0) { $alerts .= '<BR>'; }
			// add bulleted list if more than one error
			if ($num_alerts>1) { $alerts .= '<li>'; }
			$alerts .= $err;
		}
		// close bulleted list format
		if ($num_alerts>1) { $alerts .= '</ul>'; }

		echo '
<script type="text/javascript">
	modalAlertShow("<i class=\"fa fa-female\"></i> A message from Améa...","'.$alerts.'",false);
</script>
		';
	}
?>
