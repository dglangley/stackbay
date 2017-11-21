<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrder.php';
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
	include_once $_SERVER["ROOT_DIR"].'/inc/getSerial.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getDisposition.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getTerms.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getQty.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/display_part.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_address.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setInputSearch.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/detectDefaultType.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/buildDescrCol.php';

	function setRef($label,$ref,$id,$n) {
		$grp = array('btn'=>'Ref','field'=>'','hidden'=>'','attr'=>' data-toggle="dropdown"');

		//if (! strstr($id,'NEW')) {
		if ($id) {
			if (strstr($label,'item_id')) {
				$T2 = order_type($label);
				$ref_order = getOrderNumber($ref,$T2['items'],$T2['order']);

				$grp['attr'] = '';
				$grp['btn'] = $T2['abbrev'];
				$grp['field'] = '<input type="text" name="ref_'.$n.'_aux['.$id.']" class="form-control input-sm" value="'.$ref_order.'" readonly>';
				$grp['hidden'] = '<input type="hidden" name="ref_'.$n.'['.$id.']" value="'.$ref.'">';
			} else {
				// change default ref label, if set
				if ($label) { $grp['btn'] = $label; }

				$grp['field'] = '<input type="text" name="ref_'.$n.'['.$id.']" class="form-control input-sm" value="'.$ref.'">';
	
			}
		} else {
			$grp['field'] = '<input type="text" name="ref_'.$n.'['.$id.']" class="form-control input-sm" value="">';
		}

		return ($grp);
	}

	function buildLineCol($ln,$id=0) {
		global $EDIT;

		$col = '<div class="pull-left" style="width:7%">';
		if ($EDIT) {
			$col .= '<input type="text" name="ln['.$id.']" value="'.$ln.'" class="form-control input-sm line-number">';
		} else if ($ln) {
			//$col .= '<span class="info">'.$ln.'.</span>';
			$col .= '<a href="/'.strtolower($GLOBALS['order_type']).'.php?order_number='.$GLOBALS['order_number'].'-'.$ln.'" class="btn btn-default btn-xs">'.$ln.'.</a>';
		}
		$col .= '&nbsp;</div>';

		return ($col);
	}

	$labels = getRefLabels();
	$ref_labels = '';
	foreach ($labels as $label) {
		$ref_labels .= '<li><a href="javascript:void(0);">'.$label.'</a></li>'.chr(10);
	}
	function buildRefCol($grp,$label,$ref,$id,$n) {
		global $ref_labels,$EDIT;

		$col = '';
		if ($EDIT) {
			$col = '
			<div class="input-group dropdown">
				<span class="input-group-btn dropdown-toggle"'.$grp['attr'].'>
					<button class="btn btn-default btn-sm btn-narrow btn-dropdown" type="button">'.$grp['btn'].'</button>
					<input type="hidden" name="ref_'.$n.'_label['.$id.']" value="'.$label.'">
				</span>
				'.$grp['field'].'
				'.$grp['hidden'].'
				<!-- .dropdown-button takes the text value of the selected <li><a> tag, and sets it to the hidden form element within the above .dropdown-toggle and updates its text value -->
				<ul class="dropdown-menu dropdown-button">
					'.$ref_labels.'
				</ul>
			</div>
			';
		} else {
			if ($ref) {
				// if an id is used for reference, convert it to the corresponding Order
				if (strstr($label,'item_id')) {
					$T2 = order_type($label);
					$order = getOrderNumber($ref,$T2['items'],$T2['order']);

					$col = $T2['abbrev'].' '.$order;
				} else {
					$col = $label.' '.$ref;
				}
			}
		}

		return ($col);
	}

	$LN = 1;
	$WARRANTYID = array();//tries to assimilate new item warranties to match existing item warranties
	$SUBTOTAL = 0;
	function addItemRow($id,$T) {
		global $LN,$WARRANTYID,$SUBTOTAL,$EDIT;

		//randomize id (if no id) so we can repeatedly add new rows in-screen
		$new = false;
		if (! $id) {
			//$id = 'NEW'.rand(0,999999);
			$new = true;
		}

		$P = array();
		// used as a guide for the fields in the items table for this order/order type
		$items = getItems($T['item_label']);
		$def_type = detectDefaultType($items);

		if (! $new) {
			$row_cls = 'item-row';
			$query = "SELECT * FROM ".$T['items']." WHERE id = '".res($id)."'; ";
			$result = qdb($query) OR die(qe().'<BR>'.$query);

			if (mysqli_num_rows($result)==0) { return (''); }
			$r = mysqli_fetch_assoc($result);
			$r['qty_attr'] = '';
			$r['name'] = '';
			if ((array_key_exists('partid',$r) AND $r['partid']) OR (array_key_exists('item_id',$r) AND array_key_exists('item_label',$r) AND $r['item_label']=='partid'))  {
				$H = hecidb($r['partid'],'id');
				$P = $H[$r['partid']];
				$r['name'] = '<option value="'.$r['partid'].'" selected>'.$P['name'].'</option>'.chr(10);
			} else if (array_key_exists('item_id',$r) AND array_key_exists('item_label',$r) AND $r['item_label']=='addressid') {
				$P['id'] = $r['item_id'];
				if ($EDIT) {
					$P['name'] = format_address($r['item_id'],', ',true,'');
				} else {
					$P['name'] = format_address($r['item_id'],', ',true,'',$GLOBALS['ORDER']['companyid'],'<br/>');
				}
			}
			if (! isset($r['amount']) AND isset($r['price'])) { $r['amount'] = $r['price']; }
			$r['input-search'] = '';
			$r['save'] = '<input type="hidden" name="item_id['.$id.']" value="'.$id.'">';
			if ($T['record_type']=='quote') {
				$r['save'] = '<input type="checkbox" name="item_id['.$id.']" value="" checked>';
			}

			$ref1 = setRef($r['ref_1_label'],$r['ref_1'],$id,1);
			$ref2 = setRef($r['ref_2_label'],$r['ref_2'],$id,2);

			if ($T['warranty']) {
				if (! isset($WARRANTYID[$r[$T['warranty']]])) { $$WARRANTYID[$r[$T['warranty']]] = 0; }
				$WARRANTYID[$r[$T['warranty']]]++;
			}
			// increment so that new rows don't start at 1
			if ($r['line_number']>0) { $LN = ($r['line_number']+1); }

			$ext_amount = '$ '.number_format(($r['qty']*$r['amount']),2);
			$SUBTOTAL += ($r['qty']*$r['amount']);
		} else {
			// sort warranties of existing items in descending so we can get the most commonly-used, and default to that
			$warrantyid = $T['warrantyid'];
			krsort($WARRANTYID);
			foreach ($WARRANTYID as $wid => $n) { $warrantyid = $wid; }

			$row_cls = 'search-row';
			$ext_amount = '';


			$r = array(
				'line_number'=>$LN,
				'name'=>'',
				'input-search'=>setInputSearch($def_type),
				'delivery_date'=>format_date($GLOBALS['today'],'m/d/y',array('d'=>7)),
				'conditionid'=>2,
				$T['warranty']=>$warrantyid,
				'qty'=>'',
				'qty_attr'=>'readonly',
				'amount'=>'',
				'save'=>'
					<button type="button" class="btn btn-success btn-sm btn-saveitem"><i class="fa fa-save"></i></button>
					<input type="hidden" name="item_id[]" value="">
				',
			);

			if (array_key_exists('description',$items)) { $r['description'] = ''; }

			$ref1 = setRef('','',$id,1);
			$ref2 = setRef('','',$id,2);
		}
		if (round($r['amount'],2)==$r['amount']) { $amount = format_price($r['amount'],false,'',true); }
		else { $amount = $r['amount']; }

		$delivery_col = '';
		$condition_col = '';
		$warranty_col = '';
		$descr = false;
		if (array_key_exists('description',$r)) { $descr = $r['description']; }

		$descr_col = '';
		if ($EDIT) {
			if ($descr!==false) { $descr_col = '<br/><textarea name="description['.$id.']" rows="2" class="form-control input-sm">'.$descr.'</textarea>'; }

			if ($T["delivery_date"]) {
				$delivery_col = '
			<div class="input-group date datetime-picker" data-format="MM/DD/YY">
				<input type="text" name="delivery_date['.$id.']" class="form-control input-sm delivery-date" value="'.format_date($r[$T['delivery_date']],'m/d/y').'">
				<span class="input-group-addon">
					<span class="fa fa-calendar"></span>
				</span>
			</div>
				';
			}
			if ($T["condition"]) {
				$condition_col = '
			<select name="conditionid['.$id.']" size="1" class="form-control input-sm condition-selector" data-url="/json/conditions.php">
				<option value="'.$r['conditionid'].'" selected>'.getCondition($r['conditionid']).'</option>
			</select>
				';
			}
			if ($T["warranty"]) {
				$warranty_col = '
			<select name="warrantyid['.$id.']" size="1" class="form-control input-sm warranty-selector" data-url="/json/warranties.php">
				<option value="'.$r[$T['warranty']].'" selected>'.getWarranty($r[$T['warranty']],'warranty').'</option>
			</select>
				';
			}

			$complete_qty = getQty(0,$r['id'],$T['inventory_label']);
			if (! $complete_qty) { $complete_qty = 0; }
			$btn_cls = '';
			if ($complete_qty>0 AND $complete_qty==$r['qty']) { $btn_cls = 'text-success highlight-selected'; }
			else if ($complete_qty>0 AND $complete_qty>=$r['qty']) { $btn_cls = 'text-warning'; }

			$qty_col = '
			<span class="input-group">
				<input type="text" name="qty['.$id.']" value="'.$r['qty'].'" class="form-control input-sm item-qty" '.$r['qty_attr'].'>
				<span class="input-group-btn">
					<button class="btn btn-sm btn-default '.$btn_cls.'" type="button" title="Completed Qty" data-toggle="tooltip" data-placement="bottom">'.$complete_qty.'</button>
				</span>
			</span>
			';
			$amount_col = '<input type="text" name="amount['.$id.']" value="'.$amount.'" class="form-control input-sm item-amount" tabindex="100">';
		} else {
			if ($descr!==false) { $descr_col = '<br/><br/>'.$descr; }

			$delivery_col = format_date($r[$T['delivery_date']],'m/d/y');
			if ($T["condition"]) {
				$condition_col = getCondition($r['conditionid']);
			}
			$warranty_col = getWarranty($r[$T['warranty']],'warranty');
			$qty_col = $r['qty'];
			$amount_col = format_price($amount);

			if ($GLOBALS['order_type']=='Service') {
				$r['save'] .= '
			<span class="dropdown">
				<a class="dropdown-toggle" href="javascript:void(0);" data-toggle="dropdown"><i class="fa fa-caret-down"></i></a>
				<ul class="dropdown-menu dropdown-menu-right">
					<li><a href="javascript:void(0);" class="change-order" data-type="CCO" data-title="Customer" data-billing="BILLABLE" data-id="'.$id.'"><i class="fa fa-plus"></i> Add CCO (billable)</a></li>
					<li><a href="javascript:void(0);" class="change-order" data-type="ICO" data-title="Internal" data-billing="NON-BILLABLE" data-id="'.$id.'"><i class="fa fa-plus"></i> Add ICO (internal)</a></li>
				</ul>
			</span>
				';
			}
		}

		/****************************************************************************
		******************************* ITEM ROW ************************************
		****************************************************************************/

		$row = '
	<tr class="'.$row_cls.'">
		<td class="col-md-4 part-container">
			'.buildLineCol($r['line_number'],$id).'
			'.buildDescrCol($P,$id,$def_type,$items).'
			'.$r['input-search'].'
			'.$descr_col.'
		</td>
		<td class="col-md-1">
			'.buildRefCol($ref1,$r['ref_1_label'],$r['ref_1'],$id,1).'
        </td>
		<td class="col-md-1">
			'.buildRefCol($ref2,$r['ref_2_label'],$r['ref_2'],$id,2).'
		</td>
		<td class="col-md-1">
			'.$delivery_col.'
		</td>
		<td class="col-md-1">
			'.$condition_col.'
		</td>
		<td class="col-md-1">
			'.$warranty_col.'
		</td>
		<td class="col-md-1 text-center">
			'.$qty_col.'
		</td>
		<td class="col-md-1">
			'.$amount_col.'
		</td>
		<td class="col-md-1 text-right">
			<div class="ext-amount">'.$ext_amount.'</div>
			'.$r['save'].'
		</td>
	</tr>
		';

		return ($row);
	}
	$charge_options = array(
		'CC Proc Fee',
		'Sales Tax',
		'Freight',
		'Restocking Fee',
	);
	function addChargeRow($descr='',$qty=1,$price=0,$id=0) {
		global $charge_options,$SUBTOTAL;

		$options = '';
		$sel_match = false;
		foreach ($charge_options as $opt) {
			$s = '';
			if ($opt==$descr) { $s = ' selected'; $sel_match = true; }
			$options .= '<option value="'.$opt.'"'.$s.'>'.$opt.'</option>'.chr(10);
		}
		// add descr to options if not matched above
		if ($descr AND ! $sel_match) { $options .= '<option value="'.$descr.'" selected>'.$descr.'</option>'.chr(10); }

		if ($price==round($price,2)) { $price = number_format($price,2); }

		$row = '
		<tr class="item-row">
			<td class="col-md-10"> </td>
			<td class="col-md-1">
				<select name="charge_description['.$id.']" size="1" class="select2 form-control input-xs">
					<option value="">Add Charge...</option>
					'.$options.'
				</select>
				<input type="hidden" name="charge_qty['.$id.']" value="'.$qty.'" class="item-qty">
			</td>
			<td class="col-md-1">
				<span class="input-group">
					<span class="input-group-btn">
						<button class="btn btn-default btn-sm" type="button"><i class="fa fa-dollar"></i></button>
					</span>
					<input type="text" name="charge_amount['.$id.']" value="'.$price.'" class="form-control input-sm text-right item-amount" placeholder="0.00">
				</span>
			</td>
		</tr>
		';

		$SUBTOTAL += ($qty*$price);

		return ($row);
	}

	$order_number = 0;
	$order_type = '';
	if (! isset($EDIT)) { $EDIT = false; }

	$invoice = '';
	if (isset($_REQUEST['invoice']) AND trim($_REQUEST['invoice'])) { $invoice = trim($_REQUEST['invoice']); }

	if ($invoice) {
		$order_number = $invoice;
		$order_type = 'Invoice';
	} else {
		if (isset($_REQUEST['order_number']) AND trim($_REQUEST['order_number'])) { $order_number = trim($_REQUEST['order_number']); }
		else if (isset($_REQUEST['on']) AND trim($_REQUEST['on'])) { $order_number = trim($_REQUEST['on']); }//legacy support
		if (isset($_REQUEST['order_type']) AND trim($_REQUEST['order_type'])) { $order_type = trim($_REQUEST['order_type']); }
		else if (isset($_REQUEST['ps']) AND trim($_REQUEST['ps'])) {
			$order_type = strtolower(trim($_REQUEST['ps']));
		}
		if ($order_type=='s' OR $order_type=='sale' OR $order_type=='SO') { $order_type = 'Sale'; }
		else if ($order_type=='p' OR $order_type=='purchase' OR $order_type=='PO') { $order_type = 'Purchase'; }
		else if ($order_type=='r' OR $order_type=='repair' OR $order_type=='RO' OR $order_type=='ro' OR $order_type=='R') { $order_type = 'Repair'; }

		// user is creating a new order
		if ($order_type AND ! $EDIT AND ! $order_number) { $EDIT = true; }
	}

	$title_helper = '';
	$returns = array();
	if ($order_type=='Invoice') {
		$invoice = $order_number;
		$TITLE = 'Invoice '.$order_number;

		$ORDER = getOrder($order_number,'Invoice');
		$T = order_type($order_type);//$ORDER['order_type']);

		$title_helper = format_date($ORDER['date_invoiced'],'D n/j/y g:ia');
	} else {
		if (! isset($T)) { $T = order_type($order_type); }
		$TITLE = $T['abbrev'];
		if ($order_number) {
			$TITLE .= '# '.$order_number;
		} else {
			if ($order_type=='service_quote' AND isset($QUOTE)) {
				$TITLE = 'New '.$TITLE.' from Quote# '.$QUOTE['id'];
			} else {
				$TITLE = 'New '.$TITLE;
			}
		}

		if (! isset($ORDER)) {
			$ORDER = getOrder($order_number,$order_type);
			if ($ORDER===false) { die("Invalid Order"); }
		}
		$ORDER['bill_to_id'] = $ORDER['addressid'];
		$ORDER['datetime'] = $ORDER['dt'];
		if (! $ORDER['status']) { $ORDER['status'] = 'Active'; }

		$title_helper = format_date($ORDER['datetime'],'D n/j/y g:ia');


		$returns = getReturns($order_number,$order_type);

		/***** Handle RMA Support options *****/
		$support = '';
		if (! $EDIT AND getTerms($ORDER['termsid'],'id','type')) {//billable type as opposed to null type
			$support = '
				<div class ="btn-group">
					<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
						<i class="fa fa-question-circle-o"></i> Support
						<span class="caret"></span>
					</button>
					<ul class="dropdown-menu text-left">
			';
			$dupes = array();//avoid duplicates in the following loop because this shows each individual item for every RMA
			foreach ($returns as $r) {
				if (isset($dupes[$r['rma_number']])) { continue; }
				$dupes[$r['rma_number']] = true;

				$support .= '
						<li>
							<a href="/rma.php?rma='.$r['rma_number'].'">RMA '.$r['rma_number'].' ('.format_date($r['created'],'n/j/y').')</a>
						</li>
				';
			}
			$support .= '
						<li>
							<a href="/rma.php?on='.$order_number.($order_type=='Repair' ? '&repair=true' : ($order_type=='Builds' ? '&repair=true' : '')).'">
								<i class ="fa fa-plus"></i> Create RMA
							</a>
						</li>
					</ul>
				</div>
			';
		}
	}

	/***** COLLECTIONS: Invoices / Bills *****/
	$coll_dropdown = '';
	// An associated order is an indicator that collections happens ON this order; if, however, there IS an order number
	// associated, this is the collections record (Invoice/Bill), so therefore we shouldn't have addl options here
	if ($order_number AND ! $ORDER['order_number']) {
		$coll_dropdown = '
			<span class="dropdown">
				<a href="javascript:void(0);" class="dropdown-toggle" id="titleMenu" data-toggle="dropdown"><i class="fa fa-caret-down"></i></a>
				<ul class="dropdown-menu text-left">
		';

		$records = array();
		if ($T['collection']=='invoices') {
			$records = getInvoices($order_number,$order_type);
		} else if ($T['collection']=='bills') {
			$records = getBills($order_number,$order_type);
		}
		if (count($records)>0) {
			$coll_dropdown .= '
					<li class="dropdown-header">
						<i class="fa fa-file-pdf-o"></i> '.ucfirst($T['collection']).'
					</li>
			';
			foreach ($records as $rec) {
				if ($T['collection']=='invoices') { $ln = '/docs/INV'.$rec[$T['collection_no']].'.pdf" target="_new'; }
				else if ($T['collection']=='bills') { $ln = '/bill.php?bill='.$rec[$T['collection_no']]; }
				$coll_dropdown .= '
					<li>
						<a href="'.$ln.'">
							'.$rec[$T['collection_no']].' ('.format_date($rec['datetime'],'n/j/y').')
						</a>
					</li>
				';
			}
		}
		if ($T['collection']=='invoices') {
			$coll_dropdown .= '
					<li>
						<a target="_blank" href="/invoice.php?on='.$order_number.'"><i class="fa fa-plus"></i> Proforma Invoice</a>
					</li>
			';
		} else if ($T['collection']=='bills') {
			$coll_dropdown .= '
					<li>
						<a href="/bill.php?on='.$order_number.'&bill="><i class="fa fa-plus"></i> Add New Bill</a>
					</li>
			';
		}
		$coll_dropdown .= '
				</ul>
			</span>
		';
	}
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
		#footer {
			/*position: fixed;*/
			/*bottom: 0;*/
			width: 100%;
			padding-left:321px;
			min-height:250px;
			z-index:2;
		}
		#order_status {
			max-width:120px;
		}
		.table tr td {
			vertical-align:top !important;
		}
	</style>
</head>
<body data-scope="<?php echo $order_type; ?>">

<?php include_once 'inc/navbar.php'; ?>

<form class="form-inline" method="POST" action="save-order.php" enctype="multipart/form-data" >
<input type="hidden" name="order_number" value="<?php echo $order_number; ?>">
<input type="hidden" name="order_type" value="<?php echo $order_type; ?>">

<?php if (array_key_exists('repair_code_id',$ORDER)) { ?>
	<input type="hidden" name="repair_code_id" value="<?php echo $ORDER['repair_code_id']; ?>">
<?php } ?>

<!-- FILTER BAR -->
<div class="table-header table-<?=$order_type;?>" id="filter_bar" style="width: 100%; min-height: 48px; max-height:60px;">
	<div class="row" style="padding:8px">
		<div class="col-sm-2">
<?php if ($EDIT) { ?>
			<select name="status" id="order_status" size="1" class="form-control input-sm select2">
				<option value="Active"<?=($ORDER['status']=='Active' ? ' selected' : '');?>>Active</option>
				<option value="Void"<?=($ORDER['status']=='Void' ? ' selected' : '');?>>Void</option>
	<?php
		if ($ORDER['status'] AND $ORDER['status']<>'Active' AND $ORDER['status']<>'Void') { echo '<option value="'.$ORDER['status'].'" selected>'.$ORDER['status'].'</option>'; }
	?>
			</select>
<?php } else if ($T['record_type']<>'quote') { ?>
			<a href="/edit_order.php?order_number=<?=$order_number;?>&order_type=<?=$order_type;?>" class="btn btn-default btn-sm"><i class="fa fa-pencil"></i> Edit</a>
	<?php if ($order_type=='Repair') { ?>
			<a href="/repair_add.php?on=<?=$order_number;?>" class="btn btn-default btn-sm text-warning"><i class="fa fa-qrcode"></i> Receive</a>
			<a href="/repair.php?on=<?=$order_number;?>" class="btn btn-primary btn-sm"><i class="fa fa-wrench"></i> Tech View</a>
	<?php } else if ($order_type=='Purchase') { ?>
			<a href="/inventory_add.php?on=<?=$order_number;?>" class="btn btn-default btn-sm text-warning"><i class="fa fa-qrcode"></i> Receive</a>
			<a target="_blank" href="/docs/<?=$T['abbrev'].$order_number;?>.pdf" class="btn btn-brown btn-sm"><i class="fa fa-file-pdf-o"></i></a>
	<?php } else if ($order_type=='Sale') { ?>
			<a class="btn btn-primary btn-sm" href="/shipping.php?on=<?=$order_number;?>"><i class="fa fa-truck"></i> Ship</a>
	<?php } else if ($order_type=='Invoice') { ?>
			<a target="_blank" href="/docs/<?=$T['abbrev'].$order_number;?>.pdf" class="btn btn-default btn-sm"><i class="fa fa-file-pdf-o"></i></a>
	<?php } else if ($order_type=='Outsourced') { ?>
			<a target="_blank" href="/docs/OS<?=$order_number;?>.pdf" class="btn btn-default btn-sm"><i class="fa fa-file-pdf-o"></i></a>
	<?php } ?>
<?php } ?>
		</div>
		<div class="col-sm-1">
<?php if (array_key_exists('sales_rep_id',$ORDER)) { ?>
	<?php if ($EDIT) { ?>
			<select name="sales_rep_id" size="1" class="form-control input-sm select2">
		<?php
			$users = getUsers(array(4,5));
			foreach ($users as $uid => $uname) {
				$s = '';
				if (($ORDER['sales_rep_id'] AND $uid==$ORDER['sales_rep_id']) OR $U['id']==$uid) { $s = ' selected'; }
				echo '<option value="'.$uid.'"'.$s.'>'.$uname.'</option>'.chr(10);
			}
		?>
			</select>
	<?php } else { ?>
			<h4><?=getRep($ORDER['sales_rep_id']);?></h4>
	<?php } ?>
<?php } ?>
		</div>
		<div class="col-sm-1">
		</div>
		<div class="col-sm-4 text-center">
			<h2 class="minimal"><?php echo $TITLE; ?><?=$coll_dropdown;?></h2>
			<span class="info"><?php echo $title_helper; ?></span>
		</div>
		<div class="col-sm-1">
		</div>
		<div class="col-sm-1">
			<?php echo $support; ?>
		</div>
		<div class="col-sm-2 text-right">
<?php if ($EDIT) { ?>
	<?php if ($T['record_type']=='quote') { ?>
			<button type="button" class="btn btn-success btn-submit"><i class="fa fa-save"></i> Convert to Order</button>
	<?php } else { ?>
		<?php if ($order_number) { ?>
			<a href="/order.php?order_number=<?=$order_number;?>&order_type=<?=$order_type;?>" class="btn btn-default btn-sm"><i class="fa fa-times"></i> Cancel</a>
			&nbsp; &nbsp;
		<?php } ?>
			<button type="button" class="btn btn-success btn-submit"><i class="fa fa-save"></i> Save</button>
	<?php } ?>
<?php } ?>
		</div>
	</div>

</div>

<?php
	if (! isset($EDIT)) { $EDIT = false; }

	include_once $_SERVER["ROOT_DIR"].'/sidebar.php';
?>

<div id="pad-wrapper">

<table class="table table-responsive table-condensed table-striped" id="search_input">
	<thead>
	<tr>
		<th class="col-md-4"><div class="pull-left padding-right20">Ln</div> Description of Charges</th>
		<th class="col-md-1">Ref 1</th>
		<th class="col-md-1">Ref 2</th>
		<th class="col-md-1">
<?php if ($T["delivery_date"]) { ?>
			Date Due
<?php } ?>
		</th>
		<th class="col-md-1">
<?php if ($EDIT AND $T["condition"]) { ?>
			<select name="conditionid_master" size="1" class="form-control input-sm condition-selector" data-placeholder="- Condition -">
			</select>
<?php } else if ($T["condition"]) { ?>
			Condition
<?php } ?>
		</th>
		<th class="col-md-1">
<?php if ($EDIT AND $T["warranty"]) { ?>
			<select name="warrantyid_master" size="1" class="form-control input-sm warranty-selector" data-placeholder="- Warranty -">
			</select>
<?php } else if ($T["warranty"]) { ?>
			Warranty
<?php } ?>
		</th>
		<th class="col-md-1">Qty</th>
		<th class="col-md-1">Amount</th>
		<th class="col-md-1">Ext Amount</th>
	</tr>
	</thead>

	<tbody>

<?php
	foreach ($ORDER['items'] as $r) {
		echo addItemRow($r['id'],$T);
	}
	if ($EDIT AND ! $ORDER['order_number']) {
		if (isset($QUOTE)) {
			echo '
		<tr>
			<td colspan="8"> </td>
			<td class="col-md-1 text-right">
				<a href="/quote.php?order_number='.$QUOTE['id'].'" class="btn btn-primary btn-sm" title="Add Line to Quote" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-plus"></i></a>
			</td>
		</tr>
			';
		} else {/*if ($order_type<>'service_quote') {*/
			echo addItemRow(false,$T);
		}
	}
?>
	</tbody>
</table>

<?php
	$charges = '';
	if ($T['charges']) {
		$query = "SELECT * FROM ".$T['charges']." WHERE ".$T['order']." = '".res($order_number)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$charges .= addChargeRow($r['memo'],$r['qty'],$r['price'],$r['id']);
		}
		if ($EDIT) { $charges .= addChargeRow(); }
	}

	$existing_freight = getFreightAmount($order_number,$order_type);
	$freight_prop = ' readonly';
	if (array_key_exists('freight',$ORDER)) {// AND $ORDER['freight']>0) {
		$existing_freight += $ORDER['freight'];
		if ($EDIT) { $freight_prop = ''; }
	}
	$TOTAL = ($SUBTOTAL+$existing_freight);
?>

<table class="table table-responsive table-condensed table-striped" style="margin-bottom:150px">
	<tbody>
		<?php echo $charges; ?>
		<tr>
			<td class="col-md-10"> </td>
			<td class="col-md-1 text-right"><h5>SUBTOTAL</h5></td>
			<td class="col-md-1 text-right"><h6 id="subtotal">$ <?php echo number_format($SUBTOTAL,2); ?></h6></td>
		</tr>
		<tr>
			<td class="col-md-10"> </td>
			<td class="col-md-1 text-right"><h5>FREIGHT</h5></td>
			<td class="col-md-1">
				<span class="input-group">
					<span class="input-group-btn">
						<button class="btn btn-default btn-sm" type="button"><i class="fa fa-dollar"></i></button>
					</span>
					<input type="text" name="freight" value="<?php echo number_format($existing_freight,2); ?>" class="form-control input-sm input-freight text-right" placeholder="0.00"<?=$freight_prop;?>>
				</span>
			</td>
		</tr>
		<tr>
			<td class="col-md-10"> </td>
			<td class="col-md-1 text-right"><h3>TOTAL</h3></td>
			<td class="col-md-1 text-right"><h5 id="total">$ <?php echo number_format($TOTAL,2); ?></h5></td>
		</tr>
	</tbody>
</table>

</div><!-- pad-wrapper -->

</form>

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>
<?php include_once $_SERVER["ROOT_DIR"].'/modal/address.php'; ?>
<?php include_once $_SERVER["ROOT_DIR"].'/modal/contact.php'; ?>
<?php include_once $_SERVER["ROOT_DIR"].'/modal/change_order.php'; ?>



<?php
	if (count($returns)>0) {
?>

<div id="footer">
<h4 class="text-center">Returns and Credits</h4>
<table class="table table-responsive table-condensed table-striped">
	<thead>
	<tr class="bg-warning">
		<th>Date</th>
		<th>RMA #</th>
		<th>Description</th>
		<th>Disposition</th>
		<th>Serial</th>
		<th>Status</th>
		<th>Action</th>
	</tr>
	</thead>

	<tbody>
<?php
	foreach ($returns as $r) {
		$return_status = 'Pending';//default

		$history = '';
		if ($r['receive_date']) {
			$return_status = '<strong>'.format_date($r['receive_date'],'D n/d/y').'</strong> Received back<BR>';
			$return_status .= getHistory($r['inventoryid'],$order_number,$order_type,'item_id',$r['receive_date'],'returns_item_id',$r['id']);
		}

		$action = '';
		if ($r['dispositionid']==1) {
			// look for credits issued against Credit disposition, and if exists then link to it
			$query2 = "SELECT * FROM credits c, credit_items i ";
			$query2 .= "WHERE c.order_number = '".$order_number."' AND c.order_type = '".$order_type."' AND rma_number = '".$r['rma_number']."' ";
			$query2 .= "AND c.id = i.cid AND return_item_id = '".$r['id']."'; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$action = '<a href="/docs/CM'.$r2['cid'].'.pdf" target="_new"><i class="fa fa-file-pdf-o"></i></a>';
			} else if ($r['receive_date']) {//if already received back, eligible for credit
				$action = '<a href="/credit.php?rma_number='.$r['rma_number'].'&order_number='.$order_number.'&order_type='.$order_type.'" '.
					'class="btn btn-danger btn-xs" data-toggle="tooltip" data-placement="bottom" title="Issue Credit Memo">'.
					'<i class="fa fa-inbox"></i></a>';
			}
		}

		echo '
	<tr class="valign-top">
		<td>'.format_date($r['created']).'</td>
		<td>'.$r['rma_number'].' <a href="/rma.php?rma='.$r['rma_number'].'" target="_new"><i class="fa fa-arrow-right"></i></a></td>
		<td><small>'.display_part(current(hecidb($r['partid'], 'id'))).'</small></td>
		<td>'.getDisposition($r['dispositionid']).'</td>
		<td>
			'.getSerial($r['inventoryid']).'
			&nbsp;<a href="javascript:void(0);" data-id="'.$r['inventoryid'].'" class="btn-history" data-toggle="tooltip" data-placement="bottom" title="View Serial History"><i class="fa fa-history"></i></a><br/>
			<small class="info">'.$r['reason'].'</small>
		</td>
		<td>'.$return_status.'</td>
		<td>'.$action.'</td>
	</tr>
		';
	}
?>
	</tbody>
</table>

</div><!-- footer -->

<?php
	}/*end count($returns)>0*/
?>


<!-- VOID DISPLAY -->
<div class="modal modal-alert fade" id="modal-void" tabindex="-1" role="dialog" aria-labelledby="modalVoidTitle">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title" id="modalVoidTitle"><i class="fa fa-stop-circle fa-lg text-danger"></i> Voided!</h3>
	  </div>
      <div class="modal-body" id="modalVoidBody">
		This order is VOIDED and cannot be edited without Un-Voiding.
      </div>
      <div class="modal-footer text-center">
		<a class="btn btn-default btn-sm" href="/order.php?order_number=<?=$order_number;?>&order_type=<?=$order_type;?>">Close</a>
		<a class="btn btn-danger btn-sm" id="unvoid">Un-Void</a>
	  </div>
	</div>
  </div>
</div>
<!-- END VOID DISPLAY -->


<script type="text/javascript">
	/* placement above the file inclusions below */
	$(document).ready(function() {
		companyid = '<?= $ORDER['companyid']; ?>';
		order_number = '<?= $order_number; ?>';
	});
</script>
<script src="js/part_search.js?id=<?php echo $V; ?>"></script>
<script src="js/addresses.js?id=<?php echo $V; ?>"></script>
<script src="js/contacts.js?id=<?php echo $V; ?>"></script>
<script src="js/item_search.js?id=<?php echo $V; ?>"></script>

<script type="text/javascript">
	$(document).ready(function() {
		// for some reason, this empty function causes the following two lines to be invoked, which resets the loader and submit elements
		window.onunload = function(){};
		$('#loader').hide();
		$(this).prop('disabled',false);

<?php if ($ORDER['status']=='Void' AND $EDIT) { ?>
		$('#modal-void').modal('show');
<?php } ?>
		$("#unvoid").on('click', function() {
			$("#order_status").val("Active");
			$("#order_status").closest("form").submit();
		});

/* moved to item_search.js
		$(".item-qty, .item-amount, .input-freight").on('change keyup',function() {
			updateTotals();
		});
*/

		/* submits entire form when user is ready to save page */
		$(".btn-submit").on('click', function() {
			var errs = false;
			$(this).closest("form").find(".required").each(function() {
				if (! $(this).val()) {
					var f = $(this);
					if ($(this).attr('type')=='file') {
						f = $("button[for="+$(this).attr('id')+"]");
					}
					f.addClass('has-error');
					f.closest("div").find(".select2-selection").addClass('has-error');
					errs = true;
				} else {
					var f = $(this);
					if ($(this).attr('type')=='file') {
						f = $("button[for="+$(this).attr('id')+"]");
					}
					f.removeClass('has-error');
					f.closest("div").find(".select2-selection").removeClass('has-error');
				}
			});
			if (errs===true) {
				modalAlertShow("Form Error","This form requires certain fields to be completed. You have not done your job.");
				return;
			}

			// save pending rows before continuing; if there's really not an eligible item added as a result, the ensuing check will find out
			if ($(".found_parts").length>0) {
				$(".search-row").find(".btn-saveitem").trigger('click');
			}

			if ($("#search_input").find(".item-row").length==0) {
				modalAlertShow("Items Error","This form requires at least one item. You have not done your job.");
				return;
			}

			$('#loader-message').html('Please wait while your order is being processed...');
			$('#loader').show();
			$(this).prop('disabled',true);
			$(this).closest("form").submit();
		});

		$(".change-order").on('click', function() {
			var M = $("#changeOrderModal");
			var type = $(this).data('type');
			M.find("input[name='order_number']").val(order_number);
			M.find("input[name='order_type']").val(scope);
			M.find("input[name='change_type']").val(type);
			M.find("input[name='line_item_id']").val($(this).data('id'));

			var title = $(this).data('title');
			var billing = $(this).data('billing');
			var instructions = '<ul>'+
				'<li> New Line Item will add the Change Order to the existing Service Order. Simpler, more common option.</li>'+
				'<li> New Service Order will create a completely separate order. Rare for ICO\'s, offers more flexibility, but can become more complicated.</li>'+
				'</ul>';

			M.find("#modalCOTitle").html("<i class='fa fa-columns'></i> "+title+" Change Order");
			M.find("#modalCOBody").html(title+" Change Orders ("+type+") are <strong>"+billing+"</strong>.<br/><br/>Please select the type of "+type+" below:"+instructions);
			M.modal('show');
		});

/* moved to item_search.js
		$(".item-row .part-selector").selectize();
		$(".btn-saveitem").on('click', function() {
			var row = $(this).closest("tr");
			var found_parts = $(this).closest("tbody").find(".found_parts");
			found_parts.each(function() {
				row.saveItem($(this));
			});
			if (found_parts.length==0 && row.find(".search-type").val()=='Site') {
				row.saveItem(row);
			}

			$(this).closest("tbody").find(".found_parts").remove();
			var ln = row.find(".line-number");
			var new_ln = parseInt(ln.val());
			//if (! new_ln) { new_ln = 0; }
			new_ln++;
			ln.val(new_ln);

			$(this).closest("tr").find("input[type=text]").not(".line-number,.delivery-date").val("");
		});
		$("#item-search").on('keyup',function(e) {
			e.preventDefault();
			var key = e.which;

			if (key == 13) {
				$(this).search();
			}
		});
		$("#btn-search").on('click',function() {
			$("#item-search").search();
		});
		$(".dropdown-searchtype li").on('click', function() {
			var v = $(this).text();
			var pc = $(this).closest(".part-container");

			if (v=='Site') {
				$(".input-search").removeClass('hidden').addClass('hidden');
				pc.find(".address-neighbor").removeClass('hidden');
				pc.find(".address-selector").selectize();
				pc.find(".address-selector").removeClass('hidden').addClass('select2');
				// remove previously-found parts, if any
				$(this).closest("tbody").find(".found_parts").remove();
			} else if (v=='Part') {
				$(".input-search").removeClass('hidden');
				pc.find(".address-neighbor").removeClass('hidden').addClass('hidden');
				pc.find(".address-selector").select2("destroy");
				pc.find(".address-selector").removeClass('select2').addClass('hidden');
			}
		});

		jQuery.fn.search = function(e) {
			var type = $(this).find(".search-type").val();
			if (! type || type.val()=='Part') {
				partSearch($("#item-search").val());
			} else {
				addressSearch($("#item-search").val());
			}
		};
		jQuery.fn.saveItem = function(e) {
			var qty_field = e.find(".part_qty");
			var qty = 1;
			if (qty_field.length>0) {
				qty = qty_field.val().trim();
				if (qty == '' || qty == '0') { return; }
			}

			var original_row = $(this);

			var orig_cond = original_row.find(".condition-selector");
			var cond_id = orig_cond.val();
			var cond_text = orig_cond.text();

			var orig_warr = original_row.find(".warranty-selector");
			var warr_id = orig_warr.val();
			var warr_text = orig_warr.text();

			original_row.find("select.form-control:not(.hidden)").each(function() {
				$(this).select2("destroy");
			});

			var cloned_row = original_row.clone(true);//'true' carries event triggers over to cloned row

			original_row.find(".address-selector").val(0);//reset selection before selectizing
			original_row.find(".address-selector").selectize();
			original_row.find(".condition-selector").selectize();
			original_row.find(".warranty-selector").selectize();

			original_row.find("textarea.form-control").val('');

			// set qty of new row to qty of user-specified qty on revision found
			cloned_row.find(".item-qty").val(qty);
			var part = cloned_row.find(".part-selector");
			var partid = qty_field.data('partid');
			var descr = e.find(".part").find(".descr-label").html();
			part.populateSelected(partid, descr);
			part.selectize();
			part.show();

			var addr = cloned_row.find(".address-selector");
			addr.selectize();

			var cloned_cond = cloned_row.find(".condition-selector");
			cloned_cond.selectize();
			cloned_cond.populateSelected(cond_id,cond_text);

			var cloned_warr = cloned_row.find(".warranty-selector");
			cloned_warr.selectize();
			cloned_warr.populateSelected(warr_id,warr_text);

			// do not want this new row confused with the original search row
			cloned_row.removeClass('search-row').addClass('item-row');
			// remove search field from new cloned row
			cloned_row.find(".input-search").remove();
			// remove save button
			cloned_row.find(".btn-saveitem").remove();
			// remove readonly status on qty field
			cloned_row.find(".item-qty").prop('readonly',false);

			cloned_row.find(".dropdown .dropdown-toggle.dropdown-searchtype").addClass('hidden');

			cloned_row.insertBefore(original_row);

			updateTotals();
//			var row_total = cloned_row.calcRowTotal();
		};
		jQuery.fn.calcRowTotal = function() {
			if ($(this).find(".item-qty:not([readonly])").length==0) {
				$(this).find(".ext-amount").text('');
				return;
			}
			var qty = $(this).find(".item-qty").val().trim();
			if (! qty) { qty = 0; }
			var amount = $(this).find(".item-amount").val().trim();
			if (! amount) { amount = 0; }
			var ext_amount = qty*amount;

			$(this).find(".ext-amount").text('$ '+ext_amount.formatMoney());
			return (ext_amount);
		};
*/
	});
</script>

</body>
</html>
