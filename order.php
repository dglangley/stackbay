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
	include_once $_SERVER["ROOT_DIR"].'/inc/display_part.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';

	function setRef($label,$ref,$id,$n) {
		$grp = array('btn'=>'Ref','field'=>'','hidden'=>'','attr'=>' data-toggle="dropdown"');

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
		} else if (! $id) {
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
			$col .= '<span class="info">'.$ln.'.</span>';
		}
		$col .= '&nbsp;</div>';

		return ($col);
	}

	function buildDescrCol($P,$memo,$id,$def_type='Part') {
		global $EDIT;

		if ($id) {
			$col = '<div class="pull-left" style="width:93%">';
		} else {
			$col = '
			<div class="pull-left dropdown" style="width:8%">
				<span class="dropdown-toggle" data-toggle="dropdown">
					<button class="btn btn-default btn-sm btn-narrow btn-dropdown" type="button">'.$def_type.'</button>
					<input type="hidden" name="search_type" id="search-type" value="'.$def_type.'">
				</span>
				<!-- .dropdown-button takes the text value of the selected <li><a> tag, and sets it to the hidden form element within the above .dropdown-toggle and updates its text value -->
				<ul class="dropdown-menu dropdown-button dropdown-searchtype">
					<li><a href="javascript:void(0);">Site</a></li>
					<li><a href="javascript:void(0);">Part</a></li>
				</ul>
			</div>
			<div class="pull-left" style="width:85%">
			';
		}

		if ($EDIT) {
			if ($def_type=='Site') {
				$cls = '';
				$fieldname = 'addressid';
				$selname = 'address-selector';
				$dataurl = '/json/addresses.php';
				$dataplacer = '- Select an Address -';
			} else if ($def_type=='Part') {
				if ($id) { $cls = 'select2'; } else { $cls = 'hidden'; }
				$fieldname = 'partid';
				$selname = 'part-selector';
				$dataurl = '/json/parts-dropdown.php';
				$dataplacer = '';
			}

			$col .= '
					<select name="'.$fieldname.'['.$id.']" size="1" class="'.$selname.' '.$cls.'" data-url="'.$dataurl.'" data-placeholder="'.$dataplacer.'">
						<option value="'.$P['id'].'" selected>'.$P['name'].'</option>
					</select>
			';
			if ($memo!==false) { $col .= '<br/><textarea name="memo['.$id.']" rows="2" class="form-control input-sm">'.$memo.'</textarea>'; }
		} else {
			$col .= $P['name'];
			if ($memo!==false) { $col .= '<br/>'.$memo; }
		}

		$col .= '</div>';

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

		$H = array();
		// used as a guide for the fields in the items table for this order/order type
		$items = getItems($T['item_label']);
		if (array_key_exists('partid',$items) OR (array_key_exists('item_label',$items) AND $items['item_label']=='partid')) {
			$def_type = 'Part';
			$search_cls = '';
		} else {
			$def_type = 'Site';
			$search_cls = 'hidden';
		}

		if ($id) {
			$row_cls = 'item-row';
			$query = "SELECT * FROM ".$T['items']." WHERE id = '".res($id)."'; ";
			$result = qdb($query) OR die(qe().'<BR>'.$query);

			if (mysqli_num_rows($result)==0) { return (''); }
			$r = mysqli_fetch_assoc($result);
			$r['qty_attr'] = '';
			$r['name'] = '';
			if (array_key_exists('partid',$r) AND $r['partid']) {
				$H = hecidb($r['partid'],'id');
				$r['name'] = '<option value="'.$r['partid'].'" selected>'.$H[$r['partid']]['name'].'</option>'.chr(10);
			}
			if (! isset($r['amount']) AND isset($r['price'])) { $r['amount'] = $r['price']; }
			$r['input-search'] = '';
			$r['save'] = '';

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
				'input-search'=>'
			<div class="input-group input-shadow input-search '.$search_cls.'">
				<input type="text" name="" value="" id="item-search" class="form-control input-sm" placeholder="Search..." tabindex="1">
				<span class="input-group-btn">
					<button class="btn btn-primary btn-sm" type="button" id="btn-search"><i class="fa fa-search"></i></button>
				</span>
			</div>
				',
				'delivery_date'=>format_date($GLOBALS['today'],'m/d/y',array('d'=>7)),
				'conditionid'=>2,
				'warranty'=>$warrantyid,
				'qty'=>'',
				'qty_attr'=>'readonly',
				'amount'=>'',
				'save'=>'<button type="button" class="btn btn-success btn-sm btn-saveitem"><i class="fa fa-save"></i></button>',
			);

			if (array_key_exists('memo',$items)) { $r['memo'] = ''; }

			$ref1 = setRef('','',0,1);
			$ref2 = setRef('','',0,2);
		}
		if (round($r['amount'],2)==$r['amount']) { $amount = format_price($r['amount'],false,'',true); }
		else { $amount = $r['amount']; }

		$delivery_col = '';
		$condition_col = '';
		$memo = false;
		if (array_key_exists('memo',$r)) { $memo = $r['memo']; }

		if ($EDIT) {
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
			$warranty_col = '
			<select name="warrantyid['.$id.']" size="1" class="form-control input-sm warranty-selector" data-url="/json/warranties.php">
				<option value="'.$r[$T['warranty']].'" selected>'.getWarranty($r[$T['warranty']],'warranty').'</option>
			</select>
			';
			$qty_col = '<input type="text" name="qty['.$id.']" value="'.$r['qty'].'" class="form-control input-sm item-qty" '.$r['qty_attr'].'>';
			$amount_col = '<input type="text" name="amount['.$id.']" value="'.$amount.'" class="form-control input-sm item-amount" tabindex="100">';
		} else {
			$delivery_col = format_date($r[$T['delivery_date']],'m/d/y');
			if ($T["condition"]) {
				$condition_col = getCondition($r['conditionid']);
			}
			$warranty_col = getWarranty($r[$T['warranty']],'warranty');
			$qty_col = $r['qty'];
			$amount_col = format_price($amount);
		}

		$row = '
	<tr class="'.$row_cls.'">
		<td class="col-md-4 part-container">
			'.buildLineCol($r['line_number'],$id).'
			'.buildDescrCol($H[$r['partid']],$memo,$id,$def_type).'
			'.$r['input-search'].'
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
			<input type="hidden" name="item_id['.$id.']" value="'.$id.'">
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
		$T = order_type($order_type);
		$TITLE = $T['abbrev'];
		if ($order_number) { $TITLE .= '# '.$order_number; }
		else { $TITLE = 'New '.$TITLE; }

		$ORDER = getOrder($order_number,$order_type);
		if ($ORDER===false) { die("Invalid Order"); }
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
		#filter_bar .btn {
			margin-left:3px;
			margin-right:3px;
		}
		.input-shadow input:focus {
			box-shadow: 2px 2px 3px #888888;
		}
		.part-container .select2 {
			width:90% !important;
		}
		.item-amount {
			text-align:right;
		}
		.ext-amount {
			display:inline-block;
			text-align:right;
			font-weight:bold;
		}
		#total, #subtotal {
			border:1px inset gray;
			background-color:#fff;
			padding:3px 3px;
			border-radius:3px;
		}
		#subtotal {
			margin-top:0px;
		}
		#total {
			margin-top:4px;
		}
		h2 a.small {
			font-size:70%;
		}
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
<?php } else { ?>
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
		<div class="col-sm-2">
		</div>
		<div class="col-sm-2 text-center">
			<h2 class="minimal"><?php echo $TITLE; ?><?=$coll_dropdown;?></h2>
			<span class="info"><?php echo $title_helper; ?></span>
		</div>
		<div class="col-sm-2">
		</div>
		<div class="col-sm-1">
			<?php echo $support; ?>
		</div>
		<div class="col-sm-2 text-right">
<?php if ($EDIT) { ?>
			<a href="/order.php?order_number=<?=$order_number;?>&order_type=<?=$order_type;?>" class="btn btn-default btn-sm"><i class="fa fa-times"></i> Cancel</a>
			&nbsp; &nbsp;
			<button type="button" class="btn btn-success btn-submit"><i class="fa fa-save"></i> Save</button>
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
			Delivery
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
	if ($EDIT AND ! $ORDER['order_number']) { echo addItemRow(false,$T); }
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


<script src="js/part_search.js?id=<?php echo $V; ?>"></script>
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

		$(".item-qty, .item-amount, .input-freight").on('change keyup',function() {
			updateTotals();
		});
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
		$(".contact-editor").on('click', function() {
			var contactid = $("#contactid").val();

			$("#modal-contact").populateContact(contactid);
		});
		$(".contact-selector").on('change', function() {
			var contactid = $("#contactid").val();

			var str = $(this).find("option:selected").text();
			if (str.indexOf('Add')==-1) { return; }
			str = str.replace('Add ','').replace('...','');

			$("#modal-contact").populateContact(contactid,str);
		});
		$(".address-editor").on('click', function() {
			var idname = $(this).data('name');
			if (! idname) { return; }

			var addressid = $("#"+idname).val();

			$("#modal-address").populateAddress(addressid,idname);
		});
		$(".address-selector").on('change', function() {
			var idname = $(this).prop('id');
			var str = $(this).find("option:selected").text();
			if (str.indexOf('Add')==-1) { return; }
			str = str.replace('Add ','').replace('...','');

			$("#modal-address").populateAddress(0,idname,str);
		});
		$("#address-save").on('click', function() {
			var address = $(".modal");
			var addressid = address.find(".address-modal").data('oldid');
			var idname = address.find(".address-modal").data('idname');
			var name = address.find(".address-name").val().trim();
			var street = address.find(".address-street").val().trim();
			var addr2 = address.find(".address-addr2").val().trim();
			var city = address.find(".address-city").val().trim();
			var state = address.find(".address-state").val().trim();
			var postal_code = address.find(".address-postal_code").val().trim();

			console.log(window.location.origin+"/json/save-address.php?addressid="+addressid+"&name="+escape(name)+"&street="+escape(street)+"&addr2="+escape(addr2)+"&city="+escape(city)+"&state="+escape(state)+"&postal_code="+escape(postal_code));
			$.ajax({
				url: 'json/save-address.php',
				type: 'get',
				data: {
					'addressid': addressid,
					'name': name,
					'street': street,
					'addr2': addr2,
					'city': city,
					'state': state,
					'postal_code': postal_code,
					'companyid': companyid,
				},
				dataType: 'json',
				success: function(json, status) {
					if (json.message) { alert(json.message); return; }

					$("#"+idname).populateSelected(json.id,json.text);
					address.modal('hide');
					toggleLoader("Address successfully saved");
				},
				error: function(xhr, desc, err) {
//					console.log(xhr);
					console.log("Details: " + desc + "\nError:" + err);
				}
			}); // end ajax call
		});
		$("#save-contact").on('click', function() {
			var contact = $(".modal");
//			var contactid = contact.find(".contact-id").val();
			var contactid = $("#contactid").val();
			var name = contact.find(".contact-name").val().trim();
			var title = contact.find(".contact-title").val().trim();
			var phone = contact.find(".contact-phone").val().trim();
			var email = contact.find(".contact-email").val().trim();
			var notes = contact.find(".contact-notes").val().trim();

			console.log(window.location.origin+"/json/save-contact.php?contactid="+contactid+"&name="+escape(name)+"&title="+escape(title)+"&phone="+escape(phone)+"&email="+escape(email)+"&notes="+escape(notes)+"&companyid="+companyid);
			$.ajax({
				url: 'json/save-contact.php',
				type: 'get',
				data: {
					'contactid': contactid,
					'name': name,
					'title': title,
					'phone': phone,
					'email': email,
					'notes': notes,
					'companyid': companyid,
				},
				dataType: 'json',
				success: function(json, status) {
					if (json.message && json.message!='Success') { alert(json.message); return; }

					$("#contactid").populateSelected(json.contactid,json.name);
					contact.modal('hide');
					toggleLoader("Contact successfully saved");
				},
				error: function(xhr, desc, err) {
//					console.log(xhr);
					console.log("Details: " + desc + "\nError:" + err);
				}
			}); // end ajax call
		});
		$(".item-row .part-selector").selectize();
		$(".btn-saveitem").on('click', function() {
			var row = $(this).closest("tr");
			$(this).closest("tbody").find(".found_parts").each(function() {
				row.saveItem($(this));
			});

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

			if (v=='Site') {
				$(".input-search").removeClass('hidden').addClass('hidden');
				$(this).closest(".part-container").find(".address-selector").selectize();
				$(this).closest(".part-container").find(".address-selector").removeClass('hidden').addClass('select2');
				// remove previously-found parts, if any
				$(this).closest("tbody").find(".found_parts").remove();
			} else if (v=='Part') {
				$(".input-search").removeClass('hidden');
				$(this).closest(".part-container").find(".address-selector").select2("destroy");
				$(this).closest(".part-container").find(".address-selector").removeClass('select2').addClass('hidden');
			}
		});

		jQuery.fn.search = function(e) {
			if (! $("#search-type") || $("#search-type").val()=='Part') {
				partSearch($("#item-search").val());
			} else {
				addressSearch($("#item-search").val());
			}
		};
		jQuery.fn.saveItem = function(e) {
			var qty_field = e.find(".part_qty");
			var qty = qty_field.val().trim();
			if (qty == '' || qty == '0') { return; }

			var original_row = $(this);

			var orig_cond = original_row.find(".condition-selector");
			var cond_id = orig_cond.val();
			var cond_text = orig_cond.text();

			var orig_warr = original_row.find(".warranty-selector");
			var warr_id = orig_warr.val();
			var warr_text = orig_warr.text();

			original_row.find("select.form-control").select2("destroy");

			var cloned_row = original_row.clone(true);//'true' carries event triggers over to cloned row

			original_row.find(".condition-selector").selectize();
			original_row.find(".warranty-selector").selectize();

			// set qty of new row to qty of user-specified qty on revision found
			cloned_row.find(".item-qty").val(qty);
			var part = cloned_row.find(".part-selector");
			var partid = qty_field.data('partid');
			var descr = e.find(".part").find(".descr-label").html();
			part.populateSelected(partid, descr);
			part.selectize();
			//part.select2();
			part.show();

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
		jQuery.fn.populateContact = function(contactid,str) {
			var contact = $(this);
			if (! contactid) { var contactid = 0; }
			if (! str) { var str = ''; }

			/* defaults */
			contact.find(".contact-name").val(str);
			contact.find(".contact-title").val('');
			contact.find(".contact-email").val('');
			contact.find(".contact-notes").val('');
//			contact.find(".contact-id").val(contactid);

			if (contactid>0) {
				console.log(window.location.origin+"/json/contact.php?contactid="+contactid);
				$.ajax({
					url: 'json/contact.php',
					type: 'get',
					data: {'contactid': contactid},
					dataType: 'json',
					success: function(json, status) {
						if (json.message && json.message!='Success') { alert(json.message); return; }

						contact.find(".contact-name").val(json.name);
						contact.find(".contact-title").val(json.title);
						contact.find(".contact-email").val(json.email);
						contact.find(".contact-notes").val(json.notes);

						contact.modal('show');
					},
					error: function(xhr, desc, err) {
//						console.log(xhr);
						console.log("Details: " + desc + "\nError:" + err);
					}
				}); // end ajax call
			} else {
				contact.modal('show');
			}
		};
		jQuery.fn.populateAddress = function(addressid,idname,str) {
			var address = $(this);
			if (! addressid) { var addressid = 0; }
			if (! str) { var str = ''; }

			/* defaults */
			address.find(".modal-title").text("Add New Address");
			address.find(".address-name").val('');
			address.find(".address-street").val(str);
			address.find(".address-addr2").val('');
			address.find(".address-city").val('');
			address.find(".address-state").val('');
			address.find(".address-postal_code").val('');
			address.find(".address-modal").data('oldid',addressid);
			address.find(".address-modal").data('idname',idname);

			if (addressid>0) {
				console.log(window.location.origin+"/json/address.php?addressid="+addressid);
				$.ajax({
					url: 'json/address.php',
					type: 'get',
					data: {'addressid': addressid},
					dataType: 'json',
					success: function(json, status) {
						if (json.message) { alert(json.message); return; }

						address.find(".modal-title").text(json.title);
						address.find(".address-name").val(json.name);
						address.find(".address-street").val(json.street);
						address.find(".address-addr2").val(json.addr2);
						address.find(".address-city").val(json.city);
						address.find(".address-state").val(json.state);
						address.find(".address-postal_code").val(json.postal_code);

						address.modal('show');
					},
					error: function(xhr, desc, err) {
//						console.log(xhr);
						console.log("Details: " + desc + "\nError:" + err);
					}
				}); // end ajax call
			} else {
				address.modal('show');
			}
		};
	});
	function updateTotals() {
		var total = 0;
		$(".item-row").each(function() {
			var row_total = $(this).calcRowTotal();
			total += row_total;
		});
		$("#subtotal").text('$ '+total.formatMoney());

		// add freight to Total but not Subtotal above
		$(".input-freight").each(function() {
			var freight = parseFloat($(this).val().trim());
			total += freight;
		});
		$("#total").text('$ '+total.formatMoney());
	}
	function addressSearch(search) {
		console.log(window.location.origin+"/json/addresses.php?q="+search);
		$.ajax({
			url: 'json/addresses.php',
			type: 'get',
			data: {'q': search},
			dataType: 'json',
			success: function(json, status) {
				if (json.message && json.message!='Success') { alert(json.message); return; }

				var html = '';
				$.each(json, function(key, row) {
					html += '<tr><td colspan="6">'+row.text+'</td></tr>';
				});

				$('#search_input').append(html);
			},
			error: function(xhr, desc, err) {
//				console.log(xhr);
				console.log("Details: " + desc + "\nError:" + err);
			},
		}); // end ajax call
	}
</script>

</body>
</html>
