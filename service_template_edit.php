<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	$DEBUG = 0;
	$ERR = '';

	$COMPLETE = false;
	setGoogleAccessToken(5);//5 is amea’s userid, this initializes her gmail session

	function editTemplate($template_no, $materials, $parts) {

		// print_r($materials);

		foreach($materials as $template_item_id => $r) {

			$query = '';

			if($template_item_id) {
				$query = "UPDATE template_items SET partid = ".res($parts[$template_item_id]).", qty = ".res($r['qty']).", amount = ".res($r['amount']).", profit_pct = ".res($r['profit_pct']).", charge = ".res($r['charge'])."  WHERE id = ".res($template_item_id).";";
			} else if(! $template_item_id) {
				// print_r($r);
				$query = "INSERT INTO template_items (partid, qty, amount, profit_pct, charge, type) VALUES (".res($parts[0]).", ".res($r['qty']).", ".res($r['amount']).", ".res($r['profit_pct']).", ".res($r['charge']).", 'Material');";
			}
//echo $query . '<BR>';
			qedb($query);
		}

	}

	function deleteTemplateLine($item_id) {
		$query = "DELETE FROM template_items WHERE id = ".$item_id.";";

		qedb($query);
	}

	$template_no = '';
	if (isset($_REQUEST['template_no'])) { $template_no = trim($_REQUEST['template_no']); }
	$materials = array();
	if (isset($_REQUEST['materials'])) { $materials = $_REQUEST['materials']; }
	$parts = array();
	if (isset($_REQUEST['fieldid'])) { $parts = $_REQUEST['fieldid']; }

	$template_delete = '';
	if (isset($_REQUEST['template_delete'])) { $template_delete = $_REQUEST['template_delete']; }

	//print_r($_REQUEST);

	if($template_delete) {
		deleteTemplateLine($template_delete);
	} else {
		editTemplate($template_no, $materials, $parts);
	}

	$link = '/receiving.php?order_type='.ucwords($type).($order_number ? '&order_number=' . $order_number : '&taskid=' . $line_item) . ($locationid ? '&locationid=' . $locationid : '') . ($bin ? '&bin=' . $bin : '') . ($conditionid ? '&conditionid=' . $conditionid : '') . ($partid ? '&partid=' . $partid : '');
	if($COMPLETE) {
		//header('Location: /receiving.php?order_type='.ucwords($type).($order_number ? '&order_number=' . $order_number : '&taskid=' . $line_item) . '&status=complete');
		$link = '/receiving.php?order_type='.ucwords($type).($order_number ? '&order_number=' . $order_number : '&taskid=' . $line_item) . '&status=complete';
		// exit;
	}

	// Redirect also contains the current scanned parameters to be passed back that way the user doesn't need to reselect
	header('Location: /service_template.php?template_no=' . $template_no);

	exit;