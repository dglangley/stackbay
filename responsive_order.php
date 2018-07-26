<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	include_once $_SERVER["ROOT_DIR"] . '/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/dictionary.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/getOrder.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getItems.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getUsers.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getRefLabels.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCondition.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getWarranty.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getRep.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrderNumber.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getInvoices.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getBills.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getReturns.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getHistory.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getFreightAmount.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getFreightAccount.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCarrier.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getSerial.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getDisposition.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getTerms.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getQty.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getClass.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getMaterialsBOM.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getMaterialsQuote.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/format_address.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';

	// Builder for Responsive
	include_once $_SERVER["ROOT_DIR"].'/responsive/responsive_builder.php';

	if (isset($_REQUEST['order_number']) AND trim($_REQUEST['order_number'])) { $order_number = trim($_REQUEST['order_number']); }
	else if (isset($_REQUEST['on']) AND trim($_REQUEST['on'])) { $order_number = trim($_REQUEST['on']); }//legacy support
	if (isset($_REQUEST['order_type']) AND trim($_REQUEST['order_type'])) { $order_type = trim($_REQUEST['order_type']); }
	else if (isset($_REQUEST['ps']) AND trim($_REQUEST['ps'])) {
		$order_type = strtolower(trim($_REQUEST['ps']));
	}
	if ($order_type=='s' OR $order_type=='sale' OR $order_type=='SO') { $order_type = 'Sale'; }
	else if ($order_type=='p' OR $order_type=='purchase' OR $order_type=='PO') { $order_type = 'Purchase'; }
	else if ($order_type=='r' OR $order_type=='repair' OR $order_type=='RO' OR $order_type=='ro' OR $order_type=='R') { $order_type = 'Repair'; }
	// strip out ln# in case it's passed in
	$splits = explode('-',$order_number);
	$order_number = $splits[0];

	$TITLE = '';

	if (isset($_REQUEST['ref_1']) AND trim($_REQUEST['ref_1'])) { $REF_1 = trim($_REQUEST['ref_1']); }
	if (isset($_REQUEST['ref_1_label']) AND trim($_REQUEST['ref_1_label'])) { $REF_1_LABEL = trim($_REQUEST['ref_1_label']); }
	if (isset($_REQUEST['ref_2']) AND trim($_REQUEST['ref_2'])) { $REF_2 = trim($_REQUEST['ref_2']); }
	if (isset($_REQUEST['ref_2_label']) AND trim($_REQUEST['ref_2_label'])) { $REF_2_LABEL = trim($_REQUEST['ref_2_label']); }

	if (! isset($T)) { $T = order_type($order_type); }
	$TITLE = $T['abbrev'];

	if ($order_number) {
		$TITLE .= '# '.$order_number;
	} else {
		$class = $TITLE;
		if (array_key_exists('classid',$QUOTE) AND $QUOTE['classid']) { $class = getClass($QUOTE['classid']); }
		if ($order_type=='service_quote' AND isset($QUOTE)) {
			if ($EDIT) {
				$TITLE = 'New '.$class.' from Quote# '.$QUOTE['quoteid'];
			} else {
				$TITLE = $class.' Quote# '.$QUOTE['quoteid'];
			}
		} else if ($EDIT) {
			$TITLE = 'New '.$TITLE;
		}
	}

	if (! isset($ORDER)) {
		$ORDER = getOrder($order_number,$order_type);
		if ($ORDER===false) { die("Invalid Order"); }
	}
	if (array_key_exists('classid',$ORDER) AND $order_number) { $TITLE = getClass($ORDER['classid']).' '.$order_number; }

	$ORDER['bill_to_id'] = $ORDER['addressid'];
	$ORDER['datetime'] = $ORDER['dt'];
	if (! $ORDER['status']) { $ORDER['status'] = 'Active'; }

	$title_helper = format_date($ORDER['datetime'],'D n/j/y g:ia');

	$returns = getReturns($order_number,$order_type);

	$billing_array = array();
	$billing_array[] = array("companyid" => $ORDER['companyid'], "description" => '<h4 class="section-header"><i class="fa fa-bank"></i> Billing Address</h4>'.format_address($ORDER['bill_to_id'], '<br/>', true, '', $r['companyid']) . '<BR><BR><h4 class="section-header">Terms</h4>' . getTerms($ORDER['termsid'], 'id', 'terms'), "cust_ref" => $ORDER['cust_ref'], "contactid" => $ORDER['contactid']);

	$shipping_array = array();
	$shipping_array[] = array("companyid" => $ORDER['companyid'], "item_id" => $ORDER['ship_to_id']); //"description" => '<h4 class="section-header"><i class="fa fa-bank"></i> Shipping Address</h4>'.format_address($ORDER['ship_to_id'], '<br/>', true, '', $r['companyid'])

	$notes_array = array();
	$notes_array[] = array("description" => $ORDER['public_notes']);

	$items_array = array();

	foreach($ORDER['items'] as $r) {
		$items_array[$r['id']] = $r;
	}

	function partDescription($partid, $desc = true, $part = true){
		$r = reset(hecidb($partid, 'id'));
		$parts = explode(' ',$r['part']);

		$display = "";

		if($part)
	   		$display .= "<span class = 'descr-label'>".$parts[0]." &nbsp; ".$r['heci']."</span>";
	    if($desc)
    		$display .= '<div class="description desc_second_line descr-label" style = "color:#aaa;">'.dictionary($r['manf']).' &nbsp; '.dictionary($r['system']).
				'</span> <span class="description-label">'.dictionary($r['description']).'</span></div>';

	    return $display;
	}

	// $items_array = $ORDER['items'];

	// print_r($ORDER);
?>

<!DOCTYPE html>
<html>
<head>
	<title><?php echo $TITLE; ?></title>
	<?php
		/*** includes all required css includes ***/
		include_once 'inc/scripts.php';
	?>

	<!-- any page-specific customizations -->
	<style type="text/css">
		.container-border{
			border: 1px solid #CCC;
			border-radius: 2px;  
		}

		.block_title {
			padding: 5px 10px;
			font-size: 16px;
			border-bottom: 1px solid #CCC;
		}

		section {
			margin-bottom: 20px;
			overflow: hidden;
		}

		.card-header, .card-content {
			border-bottom: 1px solid #CCC;
		}

		.row_striped {
			background-color: rgba(0,0,0,.05);
		}

		.col_pad_min {
			padding: 0 2px;;
		}

		.col_pad_remove {
			padding: 0;
		}

		.title_link {
			color: #428bca;
			cursor: pointer;
		}

		.btn-link, .block_title {
			text-align: left;
		}

		.fa-plus-circle {
			font-size: 20px;
		}

		.detail_block, .form_block {
			display: none;
		}

		.form_block {
			overflow: visible !important;
		}

		<?php if($lines_searched > 1) {
			echo '.summary_block { display: none; }';
		} ?>

		.fa-pencil, .bot-icon {
			display: none;
		}

		.summary_details .row {
			margin-left: 0;
			margin-right: 0;
		}

		.col-results h5 {
			font-weight: bold;
		}

		.col-results h4, .col-results h5, .col-results h6 {
			font-size: 11px;
		}

		#detail_market .items-row, #detail_purchase .items-row, #detail_sale .items-row, #detail_market .items-row {
			margin: 10px 0;
		}

		.title_labels {
      		font-size: 12px;
		}

		#detail_notes .notes_container {
			margin: 0;
		}

		#detail_notes .container hr {
			margin-top: 5px !important;
			margin-bottom: 5px !important;
		}

		#lines hr {
			margin: 0;
		}

		@media (max-width: 500px) {
			#pad-wrapper {
				margin-top: 60px;
			}

			.datepicker-date {
				width: 100% !important;
				max-width: 100% !important;
			}

			.select2 {
				width: 100% !important;
			}
		}
	</style>
</head>
<body data-order-type="<?=$T['type']?>">

	<?php include_once 'inc/navbar.php'; ?>

	<div id="pad-wrapper">
		<h3 class="text-center"><?=$TITLE;?></h3>
		<BR>

		<?php 
			echo buildBlock(getCompany($ORDER['companyid']), $billing_array,'', '');
			echo buildBlock(getCarrier($ORDER['freight_carrier_id']) . ' #'. getFreightAccount($ORDER['freight_account_id']), $shipping_array,'', 'notes_summary');
			echo buildBlock('Notes', $notes_array,'', '');
			echo buildBlock('Lines', $items_array,'', 'order_lines');
		?>
	</div>

	<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>
	<script src="js/mobile_task.js?id=<?php echo $V; ?>"></script>

	<script type="text/javascript">
		$(document).ready(function() {
			companyid = '<?=$companyid;?>';
			contactid = '<?=$contactid;?>';
			listid = '<?=$listid;?>';
			list_type = '<?=$list_type;?>';
			category = setCategory();
			PR = '<?=$PR;?>';
			salesMin = '<?=$salesMin;?>';
			favorites = '<?=$favorites;?>';
			startDate = '<?=$startDate;?>';
			endDate = '<?=$endDate;?>';
			demandMin = '<?=$demand_min;?>';
			demandMax = '<?=$demand_max;?>';
			line_number = '<?=$ln;?>';
			searchid = '<?=$searchid;?>';
		});
	</script>

	<script src="js/contacts.js?id=<?php echo $V; ?>"></script>

</body>
</html>
