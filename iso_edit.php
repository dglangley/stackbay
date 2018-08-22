<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setInventory.php';

	
	function isoComment($isoComment) {
		// print_r($isoComment);
		foreach($isoComment as $inventoryid => $comment) {
			$I = array('notes'=>$comment,'id'=>$inventoryid);
			$inventoryid = setInventory($I);
		}
	}

	$type = '';
	if (isset($_REQUEST['type'])) { $type = trim($_REQUEST['type']); }
	$order_number = 0;
	if (isset($_REQUEST['order_number'])) { $order_number = trim($_REQUEST['order_number']); }

	$iso_comment = '';
	if (isset($_REQUEST['iso_comment'])) { $iso_comment = $_REQUEST['iso_comment']; }

	// Line Item stands for the actual item id of the record being purchase_item_id / repair_item_id etc
	if($iso_comment) {
		isoComment($iso_comment);
	} 

	$link = '/shipping.php?order_type='.ucwords($type).($order_number ? '&order_number=' . $order_number : '&taskid=' . $line_item);


	// Redirect also contains the current scanned parameters to be passed back that way the user doesn't need to reselect
	//header('Location: /shipping.php?order_type='.ucwords($type).($order_number ? '&order_number=' . $order_number : '&taskid=' . $line_item) . ($locationid ? '&locationid=' . $locationid : '') . ($bin ? '&bin=' . $bin : '') . ($conditionid ? '&conditionid=' . $conditionid : '') . ($partid ? '&partid=' . $partid : ''));

	// exit;

	if ($DEBUG) { exit; }

	?>

	<!-- Rage towards Aaron for creating a buggy renderOrder that makes it so that header redirect does not work grrrrr... -->
	<script type="text/javascript">
		window.location.href = "<?=$link?>";
	</script>
