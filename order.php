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
	include_once $_SERVER["ROOT_DIR"].'/inc/getClass.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getMaterialsCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getMaterialsBOM.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getMaterialsQuote.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOutsideServicesQuote.php';
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
		//if ($id OR strstr($label,'item_id')) {
		if ($id OR strstr($label,'_id')) {
			if (strstr($label,'_id')) {
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

	function buildLineCol($r,$id=0) {
		global $EDIT;

		$ln = $r['line_number'];

		//$goto = '/service.php?order_type='.$GLOBALS['order_type'].'&order_number='.$GLOBALS['order_number'].'-'.$ln;
		$goto = '/service.php?order_type='.$GLOBALS['order_type'].'&taskid='.$id;
		if ($GLOBALS['order_type']=='Sale') { $goto = '/shipping.php?order_type=Sale&order_number='.$GLOBALS['order_number']; }
		else if ($GLOBALS['order_type']=='Invoice' AND $r['task_label']=='service_item_id') { $goto = 'service.php?order_type=Service&taskid='.$r['taskid']; }
		else if (isset($GLOBALS['QUOTE'])) { $goto = strtolower($GLOBALS['order_type']).'.php?taskid='.$id; }

		$col = '<div class="pull-left" style="width:12%">';
		if ($EDIT) {
			$col .= '<input type="text" name="ln['.$id.']" value="'.$ln.'" class="form-control input-sm line-number">';
		} else if ($id) {
			//$col .= '<span class="info">'.$ln.'.</span>';
			$btn_text = '';
			if ($ln) {
				$btn_text = $ln.'.';
			} else if ($r['task_name'] AND $r['ref_2_label']=='service_item_id') {
				if ($r['amount']>0) { $co_type = 'CCO'; } else { $co_type = 'ICO'; }
				$btn_text = $co_type.' '.$r['task_name'];
			} else {
				$btn_text = '<i class="fa fa-arrow-right"></i>';
			}

			$col .= '<a href="'.$goto.'" class="btn btn-default btn-xs">'.$btn_text.'</a>';
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

					//$col = $T2['abbrev'].' '.$order.'<a href="/'.strtolower($T2['type']).'.php?order_type='.$T2['type'].'&order_number='.$order.'"><i class="fa fa-arrow-right"></i></a>';
					$col = $T2['abbrev'].' '.$order.'<a href="/'.strtolower($T2['type']).'.php?order_type='.$T2['type'].'&taskid='.$ref.'"><i class="fa fa-arrow-right"></i></a>';
				} else {
					$col = $label.' '.$ref;
				}
			}
		}

		return ($col);
	}

	$num_edits = 0;//number of rows with checkboxes that can have actions against them for Save/Convert
	$LN = 1;
	$WARRANTYID = array();//tries to assimilate new item warranties to match existing item warranties
	$SUBTOTAL = 0;
	$ALL_ITEMS = array();
	$TAXABLE_MATERIALS = 0;//for tax purposes
	function addItemRow($id,$T) {
		global $LN,$WARRANTYID,$SUBTOTAL,$EDIT,$TAXABLE_MATERIALS,$ALL_ITEMS,$num_edits;

		//randomize id (if no id) so we can repeatedly add new rows in-screen
		$new = false;
		if (! $id) {
			//$id = 'NEW'.rand(0,999999);
			$new = true;
		}

		$P = array();
		// used as a guide for the fields in the items table for this order/order type
		$items = getItems($T['item_label']);

		if (! $new) {
			$row_cls = 'item-row';
			$query = "SELECT * FROM ".$T['items']." WHERE id = '".res($id)."'; ";
			$result = qedb($query);

			if (mysqli_num_rows($result)==0) { return (''); }
			$r = mysqli_fetch_assoc($result);

			$ALL_ITEMS[$id] = $r;

			$r['qty_attr'] = '';
			$r['name'] = '';
			$def_type = detectDefaultType($r,$GLOBALS['order_type']);

			// if converting a quote, prep the item qty and amount
			if ($T['items']=='service_quote_items') {
				$materials_quote = getMaterialsQuote($id);
				$outsourced_quote = getOutsideServicesQuote($id);

				$r['amount'] = ($r['labor_hours']*$r['labor_rate'])+$r['expenses']+$materials_quote+$outsourced_quote;
			}

			// If this is a purchase request, item_id variables need to be converted
//			if (isset($r['ref_1']) AND isset($r['ref_1_label'])) {
			if (array_key_exists('ref_1',$r) AND array_key_exists('ref_1_label',$r)) {
				$r['ref_1'] = $r['ref_1'];
				$r['ref_1_label'] = $r['ref_1_label'];
//			} else if (isset($r['item_id']) AND isset($r['item_id_label'])) {
			} else if (array_key_exists('item_id',$r) AND (array_key_exists('item_label',$r) OR array_key_exists('item_id_label',$r))) {
				$r['ref_1'] = $r['item_id'];
				if (array_key_exists('item_label',$r)) {
					$r['ref_1_label'] = $r['item_label'];
				} else {
					$r['ref_1_label'] = $r['item_id_label'];
				}
			}

			if ($T['type']=='Outsourced Quote') {
				$r['ref_1'] = $id;
				$r['ref_1_label'] = 'service_quote_outsourced_id';
				$r['ref_2'] = $GLOBALS['REF_2'];
				$r['ref_2_label'] = $GLOBALS['REF_2_LABEL'];
			}

			$partid = 0;
			if (array_key_exists('partid',$r) AND $r['partid']) { $partid = $r['partid']; }
			else if (array_key_exists('item_id',$r) AND array_key_exists('item_label',$r) AND $r['item_label']<>'addressid') { $partid = $r['item_id']; }

			if ($partid) {
				$H = hecidb($partid,'id');
				$P = $H[$partid];
				$r['name'] = '<option value="'.$partid.'" selected>'.$P['name'].'</option>'.chr(10);
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

			$taxable = 0;
			$val = $id;//allows us to maintain unique $id according to record in db, but not saving it as the value for each item, if converting records

//			if ($T['items']=='purchase_requests' OR $T['items']=='service_quote_items') {
			if ($T['record_type']=='quote') {
				$val = 0;// resets because we're converting records and the id value no longer has meaning when saving order
			} else {
				// get associated materials so we can charge sales tax
				$materials = getMaterialsBOM($id,$T['item_label']);
				$taxable += $materials['charge'];
			}

			$dis = '';
			$r['save'] = '<input type="hidden" name="items['.$id.']" value="'.$val.'">';
			if ($EDIT AND (($T['record_type']=='quote' AND $GLOBALS['order_type']<>'purchase_request') OR $GLOBALS['create_order'])) {
				$btn = '';

				// if this is a quote, disable checkbox if it has already been converted
				if ($T['record_type']=='quote') {

					$query2 = "SELECT si.*, so.classid FROM service_items si, service_orders so WHERE quote_item_id = '".$id."' AND si.so_number = so.so_number; ";
					$result2 = qedb($query2);

					// quote item has been converted to service item, so no further action should be allowed
					if (mysqli_num_rows($result2)>0) {
						// store item id for future reference
						$r2 = mysqli_fetch_assoc($result2);
						$ALL_ITEMS[$id]['itemid'] = $r2['id'];
						$item_class = '';
						if ($r2['task_name']) { $item_class = $r2['task_name'].' '; }
						else if ($r2['classid']) { $item_class = getClass($r2['classid']).' '; }
						$ALL_ITEMS[$id]['order'] = $item_class.$r2['so_number'];
						if ($r2['line_number']) { $ALL_ITEMS[$id]['order'] .= '-'.$r2['line_number']; }

						// $r['pdf'] = '<a target="_blank" href="/docs/FSQ'.$r2['so_number'].'.pdf" class="btn btn-default btn-sm" title="View PDF" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-file-pdf-o"></i></a>';

						// disable checkbox since it's already been converted to task
						$dis = ' disabled';
					} else if ($r['ref_2'] AND $r['ref_2_label']=='service_quote_item_id') {
						if (! $ALL_ITEMS[$r['ref_2']] OR ! $ALL_ITEMS[$r['ref_2']]['itemid']) { $dis = ' disabled'; }

						// this is a child to a parent quote item, give the option to convert to CO
						$btn = '<button class="btn btn-xs btn-default btn-co" type="button" data-itemid="'.$id.'" data-order="'.$ALL_ITEMS[$r['ref_2']]['order'].'" title="Convert to CO" data-toggle="tooltip" data-placement="bottom"'.$dis.'><i class="fa fa-random"></i></button>';
					}
				} else if ($GLOBALS['create_order']=='Invoice' OR $GLOBALS['create_order']=='Bill') {
					$T2 = order_type($GLOBALS['create_order']);

					// prevent re-invoicing same item more than once
					$query2 = "SELECT * FROM ".$T2['items']." WHERE taskid = '".res($id)."' AND task_label = '".res($T['item_label'])."'; ";
					$result2 = qedb($query2);
					if (mysqli_num_rows($result2)>0) {
						$dis = ' disabled';
					}
				}

				if ($btn) {
					$r['save'] = $btn;
				} else {
					if (! $dis) { $num_edits++; }

					$r['save'] = '<input type="checkbox" name="items['.$id.']" value="'.$val.'" class="order-item" data-taxable="'.$taxable.'" data-amount="'.($r['qty']*$r['amount']).'" checked'.$dis.'>'.
							'<input type="hidden" name="quote_item_id['.$id.']" value="'.$id.'">';
				}

				if (! $dis) {
					$TAXABLE_MATERIALS += $taxable;
				}
			} else if ($EDIT AND $T['collection']=='invoices') {
				// indicate when item has been invoiced, and where
				$query2 = "SELECT * FROM invoice_items WHERE taskid = '".res($id)."' AND task_label = '".res($T['item_label'])."'; ";
				$result2 = qedb($query2);
				if (mysqli_num_rows($result2)>0) {
					$r2 = qrow($result2);
					$r['save'] .= '<br/>'.$r2['invoice_no'].' <a href="invoice.php?invoice='.$r2['invoice_no'].'" target="_new"><i class="fa fa-file-pdf-o"></i></a>';
				}
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
			if (! $dis) { $SUBTOTAL += ($r['qty']*$r['amount']); }

//			if (array_key_exists($T['description'],$items)) { $r['description'] = ''; }//$items[$T['description']]; }
		} else {
			$def_type = detectDefaultType($items,$GLOBALS['order_type']);

			// sort warranties of existing items in descending so we can get the most commonly-used, and default to that
			$warrantyid = $T['warrantyid'];
			krsort($WARRANTYID);
			foreach ($WARRANTYID as $wid => $n) { $warrantyid = $wid; }

			$row_cls = 'search-row';
			$ext_amount = '';

			$r = array(
				'line_number'=>$LN,
				'name'=>'',
				'ref_1_label'=>$GLOBALS['REF_1_LABEL'],
				'ref_2_label'=>$GLOBALS['REF_2_LABEL'],
				'input-search'=>setInputSearch($def_type),
				'delivery_date'=>format_date($GLOBALS['today'],'m/d/y',array('d'=>7)),
				'conditionid'=>2,
				$T['warranty']=>$warrantyid,
				'qty'=>'',
				'qty_attr'=>'readonly',
				'amount'=>'',
				'save'=>'
					<button type="button" class="btn btn-success btn-sm btn-saveitem"><i class="fa fa-save"></i></button>
					<input type="hidden" name="items['.$id.']" value="">
				',
			);

			if (array_key_exists($T['description'],$items)) { $r['description'] = ''; }

			$ref1 = setRef($GLOBALS['REF_1_LABEL'],$GLOBALS['REF_1'],$id,1);
			$ref2 = setRef($GLOBALS['REF_2_LABEL'],$GLOBALS['REF_2'],$id,2);
		}
		if (round($r['amount'],2)==$r['amount']) { $amount = format_price($r['amount'],false,'',true); }
		else { $amount = $r['amount']; }

		if (array_key_exists('task_name',$r)) {
			$r['save'] .= '<input type="hidden" name="task_name['.$id.']" value="'.$r['task_name'].'">';
		}

		// If quote item id exists on this line item then we need to preserve it on the edit/save feature
		// Generate a hidden form element that contains and will submit the quote id
		if(! empty($r['quote_item_id'])) {
			$quote_html = '<input type="hidden" name="quote_item_id['.$id.']" class="form-control input-sm delivery-date" value="'.$r['quote_item_id'].'">';
		}

		$delivery_col = '';
		$condition_col = '';
		$warranty_col = '';
		$descr = false;
		if (array_key_exists('description',$r)) { $descr = str_replace(chr(10),'<BR>',$r['description']); }
		else if (array_key_exists($T['description'],$r)) { $descr = str_replace(chr(10),'<BR>',$r[$T['description']]); }

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

			// 2nd ref cannot be another item id because then it's already a change order
			if ($r['ref_2_label']<>$T['item_label'] AND ($GLOBALS['order_type']=='Service' OR $GLOBALS['order_type']=='service_quote')) {
				$r['save'] .= '
			<span class="dropdown">
				<a class="dropdown-toggle" href="javascript:void(0);" data-toggle="dropdown"><i class="fa fa-caret-down"></i></a>
				<ul class="dropdown-menu dropdown-menu-right">
					<li><a href="javascript:void(0);" class="change-order" data-type="ICO" data-title="Internal" data-billing="NON-BILLABLE" data-id="'.$id.'"><i class="fa fa-plus"></i> Add ICO (internal)</a></li>
					<li><a href="javascript:void(0);" class="change-order" data-type="CCO" data-title="Customer" data-billing="BILLABLE" data-id="'.$id.'"><i class="fa fa-plus"></i> Add CCO (billable)</a></li>
				</ul>
			</span>
				';
			}
		}

		$ext_col = '
			<div class="ext-amount">'.$ext_amount.'</div>
			'.$r['save'].'
		';
		if (! $GLOBALS['editor']) {
			$amount_col = '';
			$ext_col = '';
		}

		/****************************************************************************
		******************************* ITEM ROW ************************************
		****************************************************************************/

		$row = '
	<tr class="'.$row_cls.'">
		<td class="col-md-4 part-container">
			'.buildLineCol($r,$id).'
			'.buildDescrCol($P,$id,$def_type,$items).'
			'.$r['input-search'].'
			'.$descr_col.'
		</td>
		<td class="col-md-1">
			'.buildRefCol($ref1,$r['ref_1_label'],$r['ref_1'],$id,1).'
			'.$quote_html.'
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
			'.$ext_col.'
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
		'Cut Fee',
	);
	function addChargeRow($descr='',$qty=1,$price=0,$id=0) {
		global $charge_options,$SUBTOTAL;

		$options = '';
		$sel_match = false;
		foreach ($charge_options as $opt) {
			$s = '';
			if ($opt==$descr) { $s = ' selected'; $sel_match = true; }
else if ($opt=='Sales Tax') { continue; }
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
					<input type="text" name="charge_amount['.$id.']" value="'.$price.'" class="form-control input-sm text-right item-amount item-charge" placeholder="0.00">
				</span>
			</td>
		</tr>
		';

		$SUBTOTAL += ($qty*$price);

		return ($row);
	}

	$order_number = 0;
	$order_type = '';
	if (! isset($ref_no)) { $ref_no = ''; }
	if (! isset($EDIT)) { $EDIT = false; }
	if (! isset($taskid)) { $taskid = false; }
	$REF_1 = '';
	$REF_2 = '';
	$REF_1_LABEL = '';
	$REF_2_LABEL = '';
 
	if (! isset($create_order)) {
		if (! $ref_no) {
			if (isset($_REQUEST['invoice']) AND trim($_REQUEST['invoice'])) { $ref_no = trim($_REQUEST['invoice']); }
			else if (isset($_REQUEST['bill']) AND trim($_REQUEST['bill'])) { $ref_no = trim($_REQUEST['bill']); }
		}
//		$invoice = '';
//		if (isset($_REQUEST['invoice']) AND trim($_REQUEST['invoice'])) { $invoice = trim($_REQUEST['invoice']); }
		$create_order = false;//flag to set parameters for creating a manual invoice (or sub order such as outsourced) against an order
	}

	if ($ref_no AND ($create_order<>'Invoice' AND $create_order<>'Bill')) {
		$order_number = $ref_no;
		$order_type = (isset($_REQUEST['invoice']) ? 'Invoice' : 'Bill');
//	if ($invoice AND $create_order<>'Invoice') {
//		$order_number = $invoice;
//		$order_type = 'Invoice';
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
		// strip out ln# in case it's passed in
		$splits = explode('-',$order_number);
		$order_number = $splits[0];

		if (isset($_REQUEST['ref_1']) AND trim($_REQUEST['ref_1'])) { $REF_1 = trim($_REQUEST['ref_1']); }
		if (isset($_REQUEST['ref_1_label']) AND trim($_REQUEST['ref_1_label'])) { $REF_1_LABEL = trim($_REQUEST['ref_1_label']); }
		if (isset($_REQUEST['ref_2']) AND trim($_REQUEST['ref_2'])) { $REF_2 = trim($_REQUEST['ref_2']); }
		if (isset($_REQUEST['ref_2_label']) AND trim($_REQUEST['ref_2_label'])) { $REF_2_LABEL = trim($_REQUEST['ref_2_label']); }
		// user is creating a new order
		if ($order_type AND ! $EDIT AND ! $order_number AND ! isset($QUOTE)) { $EDIT = true; }
	}

	$approved = array_intersect($USER_ROLES, array(1,4,5,7,10));
	if (! $approved) {
		$tasker = array_intersect($USER_ROLES, array(3,8));
		if ($tasker) {
			header('Location: service.php?order_type='.$order_type.'&order_number='.$order_number);
			exit;
		}
		// shouldn't be here at all
		header('Location: /');
		exit;
	}
	$editor = array_intersect($USER_ROLES, array(1,4,5,7));

	$title_helper = '';
	$returns = array();
	if (($order_type=='Bill' OR $order_type=='Invoice') OR ($create_order=='Bill' OR $create_order=='Invoice')) {
		$T = order_type($order_type);//$ORDER['order_type']);

		if ($create_order=='Invoice' OR $create_order=='Bill') {
			$ORDER = getOrder($order_number,$order_type);

			unset($ORDER[$T['addressid']]);
			unset($ORDER['ship_to_id']);
			unset($ORDER['classid']);
			unset($ORDER['contactid']);
			unset($ORDER['private_notes']);
			if ($create_order=='Invoice') {
				unset($ORDER['cust_ref']);
			} else if ($create_order=='Bill') {
				$terms_days = getTerms($ORDER['termsid'],'id','days');
				$ORDER['cust_ref'] = '';
				$ORDER['due_date'] = format_date($today,'Y-m-d',array('d'=>$terms_days));
			}
			unset($ORDER['termsid']);

			$class = '';
			if (array_key_exists('classid',$QUOTE) AND $QUOTE['classid']) { $class = getClass($QUOTE['classid']).' '; }

			$TITLE = $class.'New '.$create_order.' for '.$order_number;
			$EDIT = true;
		} else {
			$ORDER = getOrder($order_number,$order_type);

			$ref_no = $order_number;
			$TITLE = $order_type.' '.$order_number;
			$title_helper = format_date($ORDER[$T['datetime']],'D n/j/y g:ia');
		}
	} else {
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

		/***** Handle RMA Support options *****/
		$support = '';
		if (! $EDIT AND getTerms($ORDER['termsid'],'id','type') AND $T['support']) {//billable type as opposed to null type
			if ($T['support']=='Maintenance') {
				$support = '
					<a href="maintenance.php?order_type='.$order_type.'&order_number='.$order_number.'" class="btn btn-default btn-sm"><i class="fa fa-question-circle-o"></i> '.$T['support'].'</a>
				';
			} else {
				$support = '
				<div class ="btn-group">
					<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
						<i class="fa fa-question-circle-o"></i> '.$T['support'].'
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
	}

	/***** COLLECTIONS: Invoices / Bills *****/
	$coll_dropdown = '';
	// An associated order is an indicator that collections happens ON this order; if, however, there IS an order number
	// associated, this is the collections record (Invoice/Bill), so therefore we shouldn't have addl options here
	if ($order_number AND ($order_type=='Outsourced' OR ! $ORDER['order_number']) AND ! $EDIT AND $ORDER['termsid']<>15) {
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
				if ($T['collection']=='invoices') { $ln = '/invoice.php?invoice='.$rec[$T['collection_no']]; }
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
		if ($T['collection']=='invoices' OR $T['collection']=='bills') {
			$coll = preg_replace('/s$/','',$T['collection']);
			$coll_dropdown .= '
					<li>
						<a target="_blank" href="/'.$coll.'.php?order_type='.$order_type.'&order_number='.$order_number.'"><i class="fa fa-plus"></i> Add New '.ucfirst($coll).'</a>
					</li>
			';
/*
		} else if ($T['collection']=='bills') {
			$coll_dropdown .= '
					<li>
						<a href="/bill.php?order_type='.$order='.$order_number.'&bill="><i class="fa fa-plus"></i> Add New Bill</a>
					</li>
			';
*/
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
<input type="hidden" name="create_order" value="<?php echo $create_order; ?>">
<input type="hidden" name="taskid" value="<?php echo $taskid; ?>">

<?php if (array_key_exists('repair_code_id',$ORDER)) { ?>
	<input type="hidden" name="repair_code_id" value="<?php echo $ORDER['repair_code_id']; ?>">
<?php } ?>

<?php
	if ($create_order) {
		$order_type = $create_order;
	}

	// print "<pre>" . print_r($ORDER['items'], true) . "</pre>";

	// placed here so that we can get rows information before showing filters bar
	$rows = '';
	foreach ($ORDER['items'] as $r) {
		$rows .= addItemRow($r['id'],$T);
	}
	if (isset($_REQUEST['os_quote_id'])) {
		foreach ($_REQUEST['os_quote_id'] as $id) {
			$rows .= addItemRow($id,order_type('Outsourced Quote'));
		}
	}
	if ($EDIT AND ($create_order<>'Invoice' AND $create_order<>'Bill') AND (! $ORDER['order_number'] OR count($ORDER['items'])==0)) {
		if (isset($QUOTE)) {
			$rows .= '
		<tr>
			<td colspan="8"> </td>
			<td class="col-md-1 text-right">
				<a href="/quote.php?order_number='.$QUOTE['quoteid'].'" class="btn btn-primary btn-sm" title="Add Line to Quote" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-plus"></i></a>
			</td>
		</tr>
			';
		} else {/*if ($order_type<>'service_quote') {*/
			$rows .= addItemRow(false,$T);
		}
	}
?>

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
<?php } else if ($T['record_type']=='quote') { ?>
			<a href="/edit_quote.php?order_type=<?=$order_type;?>&order_number=<?=$QUOTE['quoteid'];?>" class="btn btn-default btn-sm"><i class="fa fa-pencil"></i> Add Quote / Convert to Order</a>
			<a target="_blank" href="/docs/<?='FSQ'.$QUOTE['quoteid'];?>.pdf" class="btn btn-default btn-sm"><i class="fa fa-file-pdf-o"></i></a>
<?php } else { ?>
			<a href="/edit_order.php?order_type=<?=$order_type;?>&order_number=<?=$order_number;?>" class="btn btn-default btn-sm"><i class="fa fa-pencil"></i> Edit</a>
	<?php if ($order_type=='Repair') { ?>
			<a href="/receiving.php?order_type=<?=$order_type;?>&order_number=<?=$order_number;?>" class="btn btn-default btn-sm text-warning"><i class="fa fa-qrcode"></i> Receive</a>
<!--
			<a href="/repair.php?on=<?=$order_number;?>" class="btn btn-primary btn-sm"><i class="fa fa-wrench"></i> Tech View</a>
-->
		<?php if (count($ORDER['items'])<=1) { ?>
			<a href="/service.php?order_number=<?=$order_number;?>&order_type=Repair" class="btn btn-primary btn-sm"><i class="fa fa-wrench"></i> Tech View</a>
		<?php } ?>
	<?php } else if ($order_type=='Purchase') { ?>
			<a href="/receiving.php?order_type=<?=$order_type;?>&order_number=<?=$order_number;?>" class="btn btn-default btn-sm text-warning"><i class="fa fa-qrcode"></i> Receive</a>
			<a target="_blank" href="/docs/<?=$T['abbrev'].$order_number;?>.pdf" class="btn btn-brown btn-sm"><i class="fa fa-file-pdf-o"></i></a>
	<?php } else if ($order_type=='Sale') { ?>
			<a class="btn btn-primary btn-sm" href="/shipping.php?order_type=Sale&order_number=<?=$order_number;?>"><i class="fa fa-truck"></i> Ship</a>
	<?php } else if ($order_type=='Invoice' OR $order_type=='Bill') { ?>
		<?php if ($order_type=='Invoice') { ?>
				<a href="/send_invoice.php?invoice=<?=$order_number;?>" class="btn btn-default btn-sm" title="Send to Accounting" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-paper-plane"></i></a>
				<a target="_blank" href="/docs/<?=$T['abbrev'].$order_number;?>.pdf" class="btn btn-default btn-sm" title="View PDF" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-file-pdf-o"></i></a>
		<?php } ?>
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
			if (! $ORDER['sales_rep_id']) { echo '<option value="">- Select Rep -</option>'; }
			else { echo '<option value="'.$ORDER['sales_rep_id'].'" selected>'.getRep($ORDER['sales_rep_id']).'</option>'; }

			$users = getUsers(array(4,5));
			foreach ($users as $uid => $uname) {
				if ($ORDER['sales_rep_id'] AND $uid==$ORDER['sales_rep_id']) { continue; }

				$s = '';
//				if (($ORDER['sales_rep_id'] AND $uid==$ORDER['sales_rep_id']) OR (! $order_number AND $U['id']==$uid)) { $s = ' selected'; }
				if (! $order_number AND $U['id']==$uid) { $s = ' selected'; }
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
	<?php if ($order_number) { ?>
			<a href="/order.php?order_type=<?=$order_type;?>&order_number=<?=$order_number;?>" class="btn btn-default btn-sm"><i class="fa fa-times"></i> Cancel</a>
	<?php } else if (isset($QUOTE) AND $QUOTE['quoteid']) { ?>
			<a href="/manage_quote.php?order_number=<?=$QUOTE['quoteid'];?>" class="btn btn-default btn-sm"><i class="fa fa-times"></i> Cancel</a>
	<?php } ?>
			&nbsp; &nbsp;

	<?php if ($T['record_type']=='quote') {
		$dis = '';
		if (! $num_edits AND $GLOBALS['order_type']<>'purchase_request') { $dis = ' disabled'; }
	?>
			<button type="button" class="btn btn-success btn-submit"<?=$dis;?>><i class="fa fa-save"></i> Convert to Order</button>
	<?php } else { ?>
			<button type="button" class="btn btn-success btn-submit"><i class="fa fa-save"></i> Save</button>
	<?php } ?>
<?php } ?>
		</div>
	</div>

</div>

<?php
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
		<?= $rows; ?>
	</tbody>
</table>

<?php
	$charges = '';
	if ($T['charges']) {
		$query = "SELECT * FROM ".$T['charges']." WHERE ".$T['order']." = '".res($order_number)."'; ";
		$result = qedb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$charges .= addChargeRow($r['memo'],$r['qty'],$r['price'],$r['id']);
		}
		if ($EDIT) { $charges .= addChargeRow(); }
	}

	$tax_rate = 0;
	$sales_tax = 0;
	if (array_key_exists('sales_tax',$ORDER)) { $sales_tax = $ORDER['sales_tax']; }
	if (array_key_exists('tax_rate',$ORDER)) {
		$tax_rate = 0;
		if ($ORDER['tax_rate']>0) { $tax_rate = $ORDER['tax_rate']; }
		else if (! $order_number AND ($order_type=='Service' OR $order_type=='service_quote')) { $tax_rate = 7.75; }

		if (! array_key_exists('sales_tax',$ORDER)) { $sales_tax = ($TAXABLE_MATERIALS*($tax_rate/100)); }
	}

	$existing_freight = getFreightAmount($order_number,$order_type);
	if (array_key_exists('freight',$ORDER)) {// AND $ORDER['freight']>0) {
		$existing_freight += $ORDER['freight'];
	}
	$aux_prop = ' readonly';
	if ($EDIT AND (! $create_order OR ($order_type<>'Invoice' AND $order_type<>'Bill'))) { $aux_prop = ''; }
	$TOTAL = ($SUBTOTAL+$sales_tax+$existing_freight);
?>

<table class="table table-responsive table-condensed table-striped" style="margin-bottom:150px">
	<tbody>
		<?php echo $charges; ?>
<?php if ($editor) { ?>
		<tr>
			<td class="col-md-10"> </td>
			<td class="col-md-1 text-right"><h5>SUBTOTAL</h5></td>
			<td class="col-md-1 text-right"><h6 id="subtotal">$ <?php echo number_format($SUBTOTAL,2); ?></h6></td>
		</tr>
<?php if (array_key_exists('tax_rate',$ORDER)) { ?>
		<tr>
			<td class="col-md-10"> </td>
			<td class="col-md-1 text-right"><h5>TAX RATE</h5></td>
			<td class="col-md-1">
				<span class="input-group">
					<input type="text" name="tax_rate" value="<?php echo number_format($tax_rate,2); ?>" class="form-control input-sm text-right tax-rate" placeholder="0.00"<?=$aux_prop;?>>
					<span class="input-group-btn">
						<button class="btn btn-default btn-sm" type="button"><i class="fa fa-percent"></i></button>
					</span>
				</span>
			</td>
		</tr>
<?php } ?>
<?php if (array_key_exists('tax_rate',$ORDER) OR array_key_exists('sales_tax',$ORDER)) { ?>
		<tr>
			<td class="col-md-10"> </td>
			<td class="col-md-1 text-right"><h5>SALES TAX</h5></td>
			<td class="col-md-1">
				<span class="input-group">
					<span class="input-group-btn">
						<button class="btn btn-default btn-sm" type="button"><i class="fa fa-dollar"></i></button>
					</span>
					<input type="text" name="sales_tax" value="<?php echo number_format($sales_tax,2); ?>" class="form-control input-sm input-tax text-right" placeholder="0.00" readonly>
				</span>
			</td>
		</tr>
<?php } ?>

<?php if (array_key_exists('freight',$ORDER) OR $existing_freight>0) { ?>
		<tr>
			<td class="col-md-10"> </td>
			<td class="col-md-1 text-right"><h5>FREIGHT</h5></td>
			<td class="col-md-1">
				<span class="input-group">
					<span class="input-group-btn">
						<button class="btn btn-default btn-sm" type="button"><i class="fa fa-dollar"></i></button>
					</span>
					<input type="text" name="freight" value="<?php echo number_format($existing_freight,2); ?>" class="form-control input-sm input-freight text-right" placeholder="0.00"<?=$aux_prop;?>>
				</span>
			</td>
		</tr>
<?php } ?>
		<tr>
			<td class="col-md-10"> </td>
			<td class="col-md-1 text-right"><h3>TOTAL</h3></td>
			<td class="col-md-1 text-right"><h5 id="total">$ <?php echo number_format($TOTAL,2); ?></h5></td>
		</tr>
</table>
	</tbody>
<?php } ?>

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
			$result2 = qedb($query2);
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
		<?php if ($EDIT) { ?>
			<a class="btn btn-danger btn-sm" id="unvoid">Un-Void</a>
		<?php } else { ?>
			<a class="btn btn-default btn-sm" href="/edit_order.php?order_number=<?=$order_number;?>&order_type=<?=$order_type;?>"><i class="fa fa-pencil"></i> Edit</a>
		<?php } ?>
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

<?php if ($ORDER['status']=='Void') { ?>
		$('#modal-void').modal('show');
<?php } ?>
		$("#unvoid").on('click', function() {
			$("#order_status").val("Active");
			$("#order_status").closest("form").submit();
		});

		$(".btn-co").on('click', function() {
			var quoteitemid = $(this).data('itemid');
			var order = $(this).data('order');

			var body_msg = "You are converting this quote to a Change Order on <strong>"+order+"</strong>.<br/><br/>"+
				"After converting, you will still be required to import/build your materials list and outside services. Ready to go?";
			modalAlertShow("Convert Quote to Change Order", body_msg, true, 'convertCO', quoteitemid);
		});

		$(".item-charge").on('change keyup', function() {
			updateTotals();
		});
		$(".order-item").on('click', function() {
			updateTax();
			updateTotals();
		});
		$(".tax-rate").on('change keyup',function() {
			updateTax();
        });
		updateTax();

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
			if (title=='Customer') {
				M.find("#co_charge").attr('readonly',false);
			} else {
				M.find("#co_charge").attr('readonly',true);
			}
/*
			var billing = $(this).data('billing');
			var instructions = '<ul>'+
				'<li> New Line Item will add the Change Order to the existing Service Order. Simpler, more common option.</li>'+
				'<li> New Service Order will create a completely separate order. Rare for ICO\'s, offers more flexibility, but can become more complicated.</li>'+
				'</ul>';
			M.find("#modalCOBody").html(title+" Change Orders ("+type+") are <strong>"+billing+"</strong>.<br/><br/>Please select the type of "+type+" below:"+instructions);
*/

			M.find("#modalCOTitle").html("<i class='fa fa-columns'></i> "+title+" Change Order");
			M.modal('show');
		});
	});

	function convertCO(quote_item_id) {
		document.location.href = 'change_order.php?quote_item_id='+quote_item_id;
		return;
	}
	function updateTax() {
		<?php if (! $create_order AND ($order_type=='Invoice' OR $order_type=='Bill')) { echo 'return;//pretty much a big hack for Invoices/Bills to not re-calc tax'; } ?>

		var tax = 0.00;
		var tax_rate = 0.00;
		if ($(".tax-rate").length>0) {
			tax_rate = $(".tax-rate").val().trim();
		}
		var taxable,row,charge_amount,ext;
		$(".order-item").each(function() {
			taxable = parseFloat($(this).data('taxable'));
			row = $(this).closest(".item-row");
			//charge_amount = parseFloat(row.find(".item-amount").val().replace(',',''));
			ext = row.find(".ext-amount").text().replace('$ ','').replace(',','');
			charge_amount = parseFloat(ext);

			if (charge_amount>0 && taxable>0 && $(this).prop("checked") && ! $(this).prop("disabled")) {
				tax += taxable*(tax_rate/100);
			}
		});

		$(".input-tax").val(tax.toFixed(2));
	}
	function updateTotal() {
		var total = 0.00;
		var ext;
		$(".ext-amount").each(function() {
			if ($(this).closest("td").find(".order-item") && ($(this).closest("td").find(".order-item").prop("checked")===false || $(this).closest("td").find(".order-item").prop("disabled")===true)) {
				return;
			}
			ext = $(this).text().replace('$ ','').replace(',','');
			total += parseFloat(ext);
		});

		$("#total").text('$ '+total.formatMoney(2));
	}
</script>

</body>
</html>
