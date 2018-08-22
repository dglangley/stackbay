<?php
	include_once $_SERVER['ROOT_DIR'].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	include_once $_SERVER['ROOT_DIR'].'/inc/keywords.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/filter.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/display_part.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/format_date.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/format_price.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getCompany.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getPart.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/calcQuarters.php';
	
	$TITLE = 'Receiving Report';

	$page = 'receiving';
	
	$itemList = array();

	if(!$companyid) {
		$companyid = setCompany();//uses $_REQUEST['companyid'] if passed in
	}

	$start_date = format_date($today,'m/d/Y',array('d'=>-90));
	if (isset($_REQUEST['START_DATE']) AND $_REQUEST['START_DATE']) {
		$start_date = $_REQUEST['START_DATE'];
	}
	$end_date = '';
	if (isset($_REQUEST['END_DATE']) AND $_REQUEST['END_DATE']) {
		$end_date = $_REQUEST['END_DATE'];
	}

	//   AND si.price > 0   AND CAST(created AS DATE) >= CAST('2018-04-17' AS DATE)  AND status <> 'Void' ORDER BY created DESC;

	//Query Sales items that also contains repair items
	$query = "SELECT datetime, companyid, partid, ref_1, ref_1_label, ref_2, ref_2_label, receive_date as delivery_date, si.po_number as order_number, created, order_type, tracking_no ";
	$query .= "FROM packages p, purchase_items si, purchase_orders so WHERE order_type = 'Purchase' AND  p.order_number = si.po_number AND so.po_number = p.order_number ";
	$query .= "AND si.price > 0 ".($companyid ? ' AND companyid = "' .$companyid. '"': '')." ".dFilter('created', $start_date, $end_date)." ";
	$query .= "AND status <> 'Void' ORDER BY created DESC;";
	 
	$result = qedb($query);
		
	while ($row = qrow($result)) {
		$itemList[] = $row;
	}
	
	include 'report.php';
	
	exit;