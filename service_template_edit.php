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
			} else if(! $template_item_id AND $parts[0]) {
				$query = "INSERT INTO template_items (template_no, partid, qty, amount, profit_pct, charge, type) VALUES (".res($template_no).", ".res($parts[0]).", ".res($r['qty']).", ".res($r['amount']).", ".res($r['profit_pct']).", ".res($r['charge']).", 'Material');";
			}

			if($query)
				qedb($query);
		}

	}

	function editTemplates($templates) {

		foreach($templates as $template_no => $r) {
			$query = '';

			// echo $template_no;

			if($template_no) {
				$query = "UPDATE templates SET name = ".fres($r['name'])." WHERE template_no = ".res($template_no).";";
			} else if(! $template_no AND $r['name']) {
				$query = "INSERT INTO templates (name, created, created_by) VALUES (".fres($r['name']).", ".fres($r['date']).", ".res($r['userid']).");";
			}
			
			if($query)
				qedb($query);
		}

	}

	function deleteTemplateLine($item_id, $type) {

		if($type != "templates") {
			$query = "DELETE FROM template_items WHERE id = ".$item_id.";";
			qedb($query);
		} else {
			// Delete Line Items
			$query = "DELETE FROM template_items WHERE template_no = ".$item_id.";";
			qedb($query);

			// Delete Template No
			$query = "DELETE FROM templates WHERE template_no = ".$item_id.";";
			qedb($query);
		}
	}

	$type = '';
	if (isset($_REQUEST['type'])) { $type = trim($_REQUEST['type']); }

	$template_no = '';
	if (isset($_REQUEST['template_no'])) { $template_no = trim($_REQUEST['template_no']); }
	$materials = array();
	if (isset($_REQUEST['materials'])) { $materials = $_REQUEST['materials']; }
	$parts = array();
	if (isset($_REQUEST['fieldid'])) { $parts = $_REQUEST['fieldid']; }

	$templates = array();
	if (isset($_REQUEST['templates'])) { $templates = $_REQUEST['templates']; }

	$template_delete = '';
	if (isset($_REQUEST['template_delete'])) { $template_delete = $_REQUEST['template_delete']; }

	// print_r($_REQUEST);

	if($template_delete) {
		deleteTemplateLine($template_delete, $type);
	} else if($type == 'template_items') {
		editTemplate($template_no, $materials, $parts);
	} else if($type == 'templates') {
		editTemplates($templates);
	}

	$link = '/receiving.php?order_type='.ucwords($type).($order_number ? '&order_number=' . $order_number : '&taskid=' . $line_item) . ($locationid ? '&locationid=' . $locationid : '') . ($bin ? '&bin=' . $bin : '') . ($conditionid ? '&conditionid=' . $conditionid : '') . ($partid ? '&partid=' . $partid : '');
	if($COMPLETE) {
		//header('Location: /receiving.php?order_type='.ucwords($type).($order_number ? '&order_number=' . $order_number : '&taskid=' . $line_item) . '&status=complete');
		$link = '/receiving.php?order_type='.ucwords($type).($order_number ? '&order_number=' . $order_number : '&taskid=' . $line_item) . '&status=complete';
		// exit;
	}

	// Redirect also contains the current scanned parameters to be passed back that way the user doesn't need to reselect
	if($type == 'templates') {
		header('Location: /service_templates.php');
		exit;
	}

	header('Location: /service_template.php?template_no=' . $template_no);
	exit;