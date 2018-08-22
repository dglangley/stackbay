<style type="text/css">
.toggle_sidebar {
	z-index: 9999;
	position: fixed;
	bottom: 0;
	width: 100%;

	display: none;
}
.toggle_sidebar .btn {
	width: 50px;
	margin: 0 auto;
	position: relative;
	display: block;
}
@media (min-width: 992px) {
	#pad-wrapper {
		margin-left: 320px;
	}
}
@media (max-width: 991px) {
	.sidebar {
		height:100px;
		left:auto;
		top:unset;
		width:100%;
	}
}
	.sidebar label {
		margin:0px;
	}
</style>
<!-- This is the mobile collapse button -->
<div class="row toggle_sidebar">
	<button type="button" class="btn btn-sm btn-default" id="toggle_sidebar" style="">
		<i class="fa fa-list" aria-hidden="true"></i>
	</button>
</div>

<div class="sidebar">

	<!-- This is the mobile collapse button -->
<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCarrier.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getClass.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getFreightAccount.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getFreightService.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getTerms.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_address.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getItemOrder.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getSiteName.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getQuotetoService.php';

	if (! isset($BUILD)) { $BUILD = false; }
	$cust_ref_placeholder = 'PO / Ref / Invoice';
/*
	if (! isset($ORDER)) {
		$ORDER = array(
			'order_number' => 0,
			'order_type' => '',
			'companyid' => 0,
			'contactid' => 0,
			$T['addressid'] => 0,
			'termsid' => 12,
		);
	}
*/

	$carriers_list = '';
	if (array_key_exists('freight_carrier_id',$ORDER)) {
		getCarrier();
		foreach ($CARRIERS as $id => $name) {
			$s = '';
			if ($id==$ORDER['freight_carrier_id']) { $s = ' selected'; }
			$carriers_list .= '<option value="'.$id.'"'.$s.'>'.$name.'</option>'.chr(10);
		}
	}

	$terms_list = '<option value="">- Select -</option>'.chr(10);
	if (array_key_exists('termsid',$ORDER)) {
		$terms_list = '<option value="'.$ORDER['termsid'].'" selected>'.getTerms($ORDER['termsid'],'id','terms').'</option>'.chr(10);
	}

	if (! isset($EDIT)) { $EDIT = false; }
//$EDIT = true;
?>

	<div class="sidebar-section">
<?php
	if (array_key_exists('order_number',$ORDER) AND $ORDER['order_number'] AND array_key_exists('order_type',$ORDER) AND $ORDER['order_type'] AND ! $BUILD) {
		$T2 = order_type($ORDER['order_type']);

		$addl_info = '';
		if (array_key_exists('shipmentid',$ORDER) AND $ORDER['shipmentid']) {
			$addl_info = '
			<a href="/docs/PS'.$ORDER['order_number'].'D'.$ORDER['shipmentid'].'.pdf" target="_blank"><i class="fa fa-list-alt"></i> Shipment Contents</a>
			<input type="hidden" name="shipmentid" value="'.$ORDER['shipmentid'].'">
			';
		}

		// $item_id pulled from the main header... task_view.php this contains the job id or task id
		$quote_info = '';
		if (array_key_exists('quote_item_id',$ORDER['items'][$item_id]) AND $ORDER['items'][$item_id]['quote_item_id']) {
			$quote_info = '
			<a href="/service_quote.php?taskid='.$ORDER['items'][$item_id]['quote_item_id'].'"><i class="fa fa-list-alt"></i> Quote# '.getItemOrder($ORDER['items'][$item_id]['quote_item_id'], 'service_quote_items').'</a>
			';
		}


		$class = $T2['abbrev'];
		if (array_key_exists('classid',$ORDER) AND $ORDER['classid']) { $class = getClass($ORDER['classid']).' '; }

		$order_url = 'order.php';
		if ((isset($quote) AND $quote===true) OR $QUOTE_TYPE) {
			
			$order_url = 'manage_quote.php';

			if($item_id) {
				$service_info = getQuotetoService($item_id);
			}
			$quote_info = '';

			if ($service_info) {
				$quote_info = '
				<a href="/service.php?taskid='.$service_info['id'].'"><i class="fa fa-list-alt"></i> '.$class.' '.$service_info['so_number'].'-'.$service_info['line_number'].'</a>
				';
			}

			$class .= 'Quote ';
		}

		echo '
		<div class="alert alert-'.$T2['alert'].'">
			<h4>'.$class.$ORDER['order_number'].' <a href="'.$order_url.'?order_type='.$ORDER['order_type'].'&order_number='.$ORDER['order_number'].'"><i class="fa fa-arrow-right"></i></a></h4>
			'.$addl_info.'
			'.$quote_info.'
		</div>
		';
	}

	// replace temp dir location (if exists) to uploads reader script
	$ORDER['upload_ln'] = str_replace($TEMP_DIR,'uploads/',$ORDER['ref_ln']);
?>
	</div>

	<div class="sidebar-section">
		<h4 class="section-header"><i class="fa fa-building"></i> Company</h4>

<?php if ($EDIT) { ?>
		<select name="companyid" id="sidebar-companyid" class="form-control input-xs company-selector required" data-noreset="true">
			<option value="">- Select a Company -</option>
			<?php if ($ORDER['companyid']) { echo '<option value="'.$ORDER['companyid'].'" selected>'.getCompany($ORDER['companyid']).'</option>'; } ?>
		</select>
<?php } else { ?>
		<p class="companyid" data-companyid="25"><span class="company-text"><?=getCompany($ORDER['companyid']);?></span> <a href="/profile.php?companyid=<?=$ORDER['companyid'];?>"><i class="fa fa-building"></i></a></p>
<?php } ?>
	</div>

<?php if (array_key_exists('contactid',$ORDER)) { ?>
	<div class="sidebar-section">
		<h4 class="section-header"><i class="fa fa-user"></i> Contact<?php if ($EDIT) { echo ' <a href="javascript:void(0);" class="contact-editor"><i class="fa fa-pencil"></i></a>'; } ?></h4>

	<?php if ($EDIT) { ?>
		<select name="contactid" id="contactid" class="form-control input-xs contact-selector required">
			<?php if ($ORDER['contactid']) { echo '<option value="'.$ORDER['contactid'].'" selected>'.getContact($ORDER['contactid']).'</option>'; } ?>
		</select>
	<?php } else { ?>
		<?php echo getContact($ORDER['contactid']); ?>
		<?php if (getContact($ORDER['contactid'],'id','email')) { echo '<a href="mailto:'.getContact($ORDER['contactid'],'id','email').'"><i class="fa fa-envelope"></i></a>'; } ?>
	<?php } ?>
	</div>
<?php } ?>

<?php if (array_key_exists('classid',$ORDER)) { ?>
	<div class="sidebar-section">
		<div class="row">
			<div class="col-xs-6">
				<h4 class="section-header"><i class="fa fa-industry"></i> Class</h4>

			<?php if ($EDIT) { ?>
				<select name="classid" id="classid" class="form-control input-xs class-selector required">
					<?php if ($ORDER['classid']) { echo '<option value="'.$ORDER['classid'].'" selected>'.getClass($ORDER['classid']).'</option>'; } ?>
				</select>
			<?php } else { ?>
				<?php echo getClass($ORDER['classid']); ?>
			<?php } ?>
			</div>
			<div class="col-xs-6">
				<?php if($ORDER['items'][$item_id]['item_label'] == 'addressid') { ?>
					<?php $sitename = trim(getSiteName($ORDER['companyid'], $ORDER['items'][$item_id]['item_id'])); ?>

					<?php if($sitename) { ?>
						<h4 class="section-header"><i class="fa fa-map-marker"></i> Site</h4>
						<?php echo getSiteName($ORDER['companyid'], $ORDER['items'][$item_id]['item_id']); ?>
					<?php } ?>
				<?php } ?>
			</div>
		</div>
	</div>
<?php } ?>

<?php if ($T['addressid'] AND array_key_exists($T['addressid'],$ORDER)) {?>
	<div class="sidebar-section">
		<h4 class="section-header"><i class="fa fa-bank"></i> <?=$T['collection_term'];?> Address<?php if ($EDIT) { echo ' <a href="javascript:void(0);" class="address-editor" data-name="bill_to_id"><i class="fa fa-pencil"></i></a>'; } ?></h4>

	<?php if ($EDIT) { ?>
		<select name="bill_to_id" id="bill_to_id" class="form-control input-xs address-selector required" data-url="/json/addresses.php">
		<?php if ($ORDER[$T['addressid']]) { ?>
			<option value="<?=$ORDER[$T['addressid']];?>" selected><?=format_address($ORDER[$T['addressid']], ', ', false);?></option>
		<?php } else { ?>
			<option value="">- Select an Address -</option>
		<?php } ?>
		</select>
	<?php } else { ?>
		<p class="company_address info" data-addressid=""><?=format_address($ORDER[$T['addressid']]);?></p>
	<?php } ?>
	</div>
<?php } ?>


	<div class="sidebar-section">
		<div class="row">
			<div class="col-xs-7">
<?php if (array_key_exists('cust_ref',$ORDER)) { ?>
				<h4 class="section-header" id="order-label">Customer Order<?php if ($ORDER['upload_ln'] AND ($U['manager'] OR $order_type=='Sale')) { echo ' <a href="'.$ORDER['upload_ln'].'" target="_new"><i class="fa fa-download"></i></a>'; } ?></h4>
	<?php if ($EDIT) { ?>
				<div class="input-group">
					<input name="cust_ref" class="form-control input-sm required" type="text" placeholder="<?=$cust_ref_placeholder;?>" value="<?=$ORDER['cust_ref'];?>">
					<span class="input-group-btn" style="vertical-align:top !important">
						<button class="btn btn-info btn-sm btn-order-upload" type="button" for="order-upload"><i class="fa fa-paperclip"></i></button>
					</span>
				</div>
				<input id="order-upload" class="file-upload <?=(! $order_number ? 'required' : '');?>" name="order_upload" accept="image/*,application/pdf,application/vnd.ms-excel,application/msword,text/plain,text/html,text/xml,*.htm,*.html,*.xml" value="" type="file">
				<input type="hidden" name="ref_ln" value="<?php echo $ORDER['ref_ln']; ?>">
	<?php } else { ?>
				<?php echo $ORDER['cust_ref']; ?><!-- <a href="<?php echo $ORDER['upload_ln']; ?>" target="_new"><i class="fa fa-file"></i></a> -->
	<?php } ?>
<?php } ?>
			</div>
			<div class="col-xs-5 nopadding-left">
<?php if (array_key_exists('termsid',$ORDER)) { ?>
				<h4 class="section-header">Terms</h4>

	<?php if ($EDIT) { ?>
				<select name="termsid" id="termsid" size="1" class="form-control input-sm select2 required">
					<?php echo $terms_list; ?>
				</select>
	<?php } else { ?>
				<?php echo getTerms($ORDER['termsid'],'id','terms'); ?>
	<?php } ?>
<?php } ?>
			</div>
		</div>
	</div>


	<div class="sidebar-section">
		<div class="row">
			<div class="col-xs-7">
<?php if (array_key_exists('due_date',$ORDER)) { ?>
				<h4 class="section-header"><i class="fa fa-calendar"></i> Due Date</h4>
	<?php if ($EDIT) { ?>
				<div class="input-group date datetime-picker" data-format="MM/DD/YYYY">
					<input name="due_date" class="form-control input-sm required" type="text" value="<?=format_date($ORDER['due_date'],'m/d/Y');?>">
					<span class="input-group-addon">
						<span class="fa fa-calendar"></span>
					</span>
				</div>
	<?php } else { ?>
				<?= format_date($ORDER['due_date'],'m/d/Y'); ?>
				<input type="hidden" name="due_date" value="<?=format_date($ORDER['due_date'],'m/d/Y');?>">
	<?php } ?>
<?php } ?>
			</div>
			<div class="col-xs-5 nopadding-left">
			</div>
		</div>
	</div>


<?php if (array_key_exists('ship_to_id',$ORDER)) { ?>
	<div class="sidebar-section">
	<?php if ($EDIT) { ?>
		<div class="pull-right"><input type="checkbox" name="sync_addresses" value="1" id="sync_addresses"><label for="sync addresses"> Same as <?=$T['collection_term'];?></label></div>
	<?php } ?>
		<h4 class="section-header"><i class="fa fa-truck"></i> Shipping Address<?php if ($EDIT) { echo ' <a href="javascript:void(0);" class="address-editor" data-name="ship_to_id"><i class="fa fa-pencil"></i></a>'; } ?></h4>

	<?php if ($EDIT) { ?>
		<select name="ship_to_id" id="ship_to_id" class="form-control input-xs address-selector required" data-url="/json/addresses.php">
		<?php if ($ORDER['ship_to_id']) { ?>
			<option value="<?=$ORDER['ship_to_id'];?>" selected><?=format_address($ORDER['ship_to_id'], ', ', false);?></option>
		<?php } else { ?>
			<option value="">- Select an Address -</option>
		<?php } ?>
		</select>
	<?php } else { ?>
		<p class="company_address" data-addressid=""><?=format_address($ORDER['ship_to_id']);?></p>
	<?php } ?>
	</div>

	<div class="sidebar-section">
		<div class="row">
			<div class="col-sm-5 nopadding-right">
				<h4 class="section-header">Carrier</h4>

	<?php if ($EDIT) { ?>
				<select name="carrierid" id="carrierid" size="1" class="select2 form-control input-sm required">
					<?php echo $carriers_list; ?>
				</select>
	<?php } else { ?>
				<?php echo getCarrier($ORDER['freight_carrier_id']); ?>
	<?php } ?>
			</div>
			<div class="col-sm-7">
				<h4 class="section-header">Freight Terms</h4>

	<?php if ($EDIT) { ?>
				<select name="freight_account_id" id="freight_account_id" size="1" class="form-control input-sm select2">
		<?php if ($ORDER['freight_account_id']) { ?>
					<option value="<?php echo $ORDER['freight_account_id']; ?>" selected><?php echo getFreightAccount($ORDER['freight_account_id']); ?></option>
		<?php } else { ?>
					<option value="">PREPAID</option>
		<?php } ?>
				</select>
	<?php } else { ?>
				<?php echo getFreightAccount($ORDER['freight_account_id']); ?>
	<?php } ?>
			</div>
		</div><!-- row -->
	</div><!-- sidebar-section -->

	<div class="sidebar-section">
		<h4 class="section-header">Freight Service</h4>

	<?php if ($EDIT) { ?>
		<select name="freight_services_id" id="freight_services_id" size="1" class="form-control input-sm required">
		<?php if ($ORDER['freight_services_id']) { ?>
			<option value="<?php echo $ORDER['freight_services_id']; ?>" selected><?php echo getFreightService($ORDER['freight_services_id']); ?></option>
		<?php } ?>
		</select>
	<?php } else { ?>
		<?php echo getFreightService($ORDER['freight_services_id']); ?>
	<?php } ?>

	</div><!-- sidebar-section -->

<?php } /* ship_to_id */ ?>

<?php if(array_key_exists('scope',$ORDER)) { ?>
	<div class="sidebar-section">
		<h4 class="section-header">Scope</h4>
		<?php if ($EDIT) { ?>
			<textarea id="scope" class="form-control" name="scope" rows="2" placeholder="Scope"></textarea>
		<?php } else { ?>
			<p></p>
		<?php } ?>
	</div>
<?php } ?>

	<div class="sidebar-section">
		<h4 class="section-header"><i class="fa fa-sticky-note"></i> Public Notes</h4>
<?php if ($EDIT) { ?>
		<textarea id="public_notes" class="form-control" name="public_notes" rows="2" placeholder=""><?=$ORDER['public_notes'];?></textarea>
<?php } else { ?>
		<p><?php echo str_replace(chr(10),'<BR>',$ORDER['public_notes']); ?></p>
<?php } ?>
	</div>

<?php
	$email_chk = '';
	if (! $order_number AND $T['confirmation']===true) { $email_chk = 'checked'; }
?>

<?php if (array_key_exists('cust_ref',$ORDER) AND $T['collection']=='invoices') { ?>
	<div class="sidebar-section">
		<p class="section-header">
			<?php if ($EDIT) { ?>
				<input type="checkbox" name="email_confirmation" id="email_confirmation" value="1" <?=$email_chk;?>/>
				<label for="email_confirmation"><i class="fa fa-paper-plane"></i> Send Order Confirmation</label>
			<?php } else if (array_key_exists('conf_contactid',$ORDER) AND $ORDER['conf_contactid']) { ?>
				<i class="fa fa-paper-plane"></i> Order Confirmation
			<?php } ?>
		</p>
		<?php if ($EDIT) { ?>
			<select name="email_to" id="email_to" class="form-control input-xs contact-selector">
				<?php if ($ORDER['conf_contactid']) { echo '<option value="'.$ORDER['conf_contactid'].'" selected>'.getContact($ORDER['conf_contactid']).'</option>'; } ?>
			</select>
		<?php } else if (array_key_exists('conf_contactid',$ORDER)) { ?>
			<?php echo getContact($ORDER['conf_contactid']); ?>
			<?php if (getContact($ORDER['conf_contactid'],'id','email')) { echo '<a href="mailto:'.getContact($ORDER['conf_contactid'],'id','email').'"><i class="fa fa-envelope"></i></a>'; } ?>
		<?php } ?>

		<?php if ($EDIT) { ?>
			<p style="margin-top:10px"><strong>CC</strong> <i class="fa fa-check-square-o"></i> shipping@ven-tel.com</p>
		<?php } ?>
	</div>
<?php } ?>

<?php if (array_key_exists('private_notes',$ORDER)) { ?>
	<div class="sidebar-footer" style="overflow-x:auto">
		<h4 class="section-header">Internal Use Only</h4>
	<?php if ($EDIT) { ?>
		<textarea id="private_notes" class="form-control textarea-info" name="private_notes" rows="3" placeholder="Private Notes"><?=$ORDER['private_notes'];?></textarea>
	<?php } else { ?>
		<p><?php echo str_replace(chr(10),'<BR>',$ORDER['private_notes']);?></p>
	<?php } ?>
	</div>
<?php } ?>
</div>
