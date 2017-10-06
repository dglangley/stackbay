<style type="text/css">
	#pad-wrapper {
		margin-left: 310px;
	}
</style>

<div class="sidebar">
<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCarrier.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getFreightAccount.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getFreightService.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getTerms.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_address.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	$cust_ref_placeholder = 'PO# / Ref#';
	if (! isset($ORDER)) {
		$ORDER = array(
			'order_number' => 0,
			'order_type' => '',
			'companyid' => 0,
			'contactid' => 0,
			'bill_to_id' => 0,
			'ship_to_id' => 0,
			'freight_carrier_id' => 0,
			'cust_ref' => '',
			'termsid' => 12,
		);
	}

	$carriers_list = '';
	if (isset($ORDER['freight_carrier_id'])) {
		getCarrier();
		foreach ($CARRIERS as $id => $name) {
			$s = '';
			if ($id==$ORDER['freight_carrier_id']) { $s = ' selected'; }
			$carriers_list .= '<option value="'.$id.'"'.$s.'>'.$name.'</option>'.chr(10);
		}
	}

	$termsid = 0;
	$terms_list = '<option value="">- Select -</option>'.chr(10);
	if (isset($_REQUEST['termsid'])) { $termsid = $_REQUEST['termsid']; }
	if ($termsid) {
		$terms_list = '<option value="'.$termsid.'" selected>'.getTerms($termsid,'id','terms').'</option>'.chr(10);
	}

	if (! isset($EDIT)) { $EDIT = false; }
//$EDIT = true;
?>

	<div class="sidebar-section">
<?php
	$T = order_type($ORDER['order_type']);
	if ($ORDER['order_number'] AND $ORDER['order_type']) {
		echo '<div class="alert alert-'.$T['alert'].'"><h4>'.$T['abbrev'].$ORDER['order_number'].' <a href="'.$T['abbrev'].$ORDER['order_number'].'"><i class="fa fa-arrow-right"></i></a></h4></div>';
	}
?>
	</div>

	<div class="sidebar-section">
		<h4 class="section-header"><i class="fa fa-book"></i> Company</h4>

		<input type="hidden" name="order_number" value="<?=$ORDER['order_number'];?>">
		<input type="hidden" name="order_type" value="<?=$ORDER['order_type'];?>">

<?php if ($EDIT) { ?>
		<select name="companyid" id="sidebar-companyid" class="form-control input-xs company-selector required" data-noreset="true">
			<option value="">- Select a Company -</option>
			<?php if ($ORDER['companyid']) { echo '<option value="'.$ORDER['companyid'].'" selected>'.getCompany($ORDER['companyid']).'</option>'; } ?>
		</select>
<?php } else { ?>
		<p class="companyid" data-companyid="25"><span class="company-text"><?=getCompany($ORDER['companyid']);?></span></p>
<?php } ?>
	</div>

	<div class="sidebar-section">
		<h4 class="section-header">Contact<?php if ($EDIT) { echo ' <a href="javascript:void(0);"><i class="fa fa-pencil"></i></a>'; } ?></h4>

<?php if ($EDIT) { ?>
		<select name="contactid" class="form-control input-xs contact-selector required">
			<option value="">- Select a Contact -</option>
			<?php if ($ORDER['contactid']) { echo '<option value="'.$ORDER['contactid'].'" selected>'.getContact($ORDER['contactid']).'</option>'; } ?>
		</select>
<?php } else { ?>
		<?php echo getContact($ORDER['contactid']); ?>
		<?php if (getContact($ORDER['contactid'],'id','email')) { echo '<a href="mailto:'.getContact($ORDER['contactid'],'id','email').'"><i class="fa fa-envelope"></i></a>'; } ?>
<?php } ?>
	</div>

	<div class="sidebar-section">
		<h4 class="section-header"><i class="fa fa-building"></i> Billing Address<?php if ($EDIT) { echo ' <a href="javascript:void(0);"><i class="fa fa-pencil"></i></a>'; } ?></h4>

<?php if ($EDIT) { ?>
		<select name="bill_to_id" id="bill_to_id" class="form-control input-xs address-selector required">
			<option value="">- Select an Address -</option>
			<option value="<?=$ORDER['bill_to_id'];?>"><?=format_address($ORDER['bill_to_id'], ',');?></option>
		</select>
<?php } else { ?>
		<p class="company_address info" data-addressid=""><?=format_address($ORDER['bill_to_id']);?></p>
<?php } ?>
	</div>

	<div class="sidebar-section">
		<div class="row">
			<div class="col-sm-7">
				<h4 class="section-header">Customer Order</h4>
<?php if ($EDIT) { ?>
				<div class="input-group">
					<input name="cust_ref" class="form-control input-sm" type="text" placeholder="<?=$cust_ref_placeholder;?>" value="<?=$ORDER['cust_ref'];?>">
					<span class="input-group-btn" style="vertical-align:top !important">
						<button class="btn btn-info btn-sm" type="button" for="order_upload"><i class="fa fa-paperclip"></i></button>
					</span>
				</div>
				<input id="order_upload" class="file-upload" name="order_upload" accept="image/*,application/pdf,application/vnd.ms-excel,application/msword,text/plain,*.htm,*.html,*.xml" value="" type="file">
<?php } else { ?>
				<?php echo $ORDER['cust_ref']; ?> <a href="<?php echo $ORDER['ref_ln']; ?>" target="_new"><i class="fa fa-file"></i></a>
<?php } ?>
			</div>
			<div class="col-sm-5 nopadding-left">
				<h4 class="section-header">Terms</h4>

<?php if ($EDIT) { ?>
				<select name="termsid" id="termsid" size="1" class="form-control input-sm select2">
					<?php echo $terms_list; ?>
				</select>
<?php } else { ?>
				<?php echo getTerms($ORDER['termsid'],'id','terms'); ?>
<?php } ?>
			</div>
		</div>
	</div>

<?php if (isset($ORDER['ship_to_id'])) { ?>
	<div class="sidebar-section">
<?php if ($EDIT) { ?>
		<div class="pull-right"><input type="checkbox" name="sync_addresses" value="1" id="sync_addresses"><label for="sync addresses"> Same as Billing</label></div>
<?php } ?>
		<h4 class="section-header"><i class="fa fa-truck"></i> Shipping Address<?php if ($EDIT) { echo ' <a href="javascript:void(0);"><i class="fa fa-pencil"></i></a>'; } ?></h4>

<?php if ($EDIT) { ?>
		<select name="ship_to_id" id="ship_to_id" class="form-control input-xs address-selector required">
			<option value="">- Select an Address -</option>
			<option value="<?=$ORDER['ship_to_id'];?>"><?=format_address($ORDER['ship_to_id'], ',');?></option>
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
				<select name="carrierid" id="carrierid" size="1" class="select2 form-control input-sm">
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
					<option value="">PREPAID</option>
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
		<select name="freight_service_id" id="freight_service_id" size="1" class="form-control input-sm">
		</select>
<?php } else { ?>
		<?php echo getFreightService($ORDER['freight_service_id']); ?>
<?php } ?>
	</div>

<?php } /* ship_to_id */ ?>

<?php if($ORDER['order_type'] == 'Service') { ?>
	<div class="sidebar-section">
		<h4 class="section-header">Scope</h4>
		<?php if ($EDIT) { ?>
			<textarea id="scope" class="form-control" name="scope" rows="3" placeholder="Scope">Here is a scope of everything that is being done for the job.</textarea>
		<?php } else { ?>
			<p>Here is a scope of everything that is being done for the job.</p>
		<?php } ?>
	</div>
<?php } ?>

	<div class="sidebar-section">
		<p class="section-header">Public Notes</p>
<?php if ($EDIT) { ?>
		<textarea id="public_notes" class="form-control" name="public_notes" rows="3" placeholder=""><?=$ORDER['public_notes'];?></textarea>
<?php } else { ?>
		<p><?php echo str_replace(chr(10),'<BR>',$ORDER['public_notes']); ?></p>
<?php } ?>
	</div>

	<div class="sidebar-footer">
		<p class="section-header">Internal Use Only</p>
<?php if ($EDIT) { ?>
		<textarea id="private_notes" class="form-control textarea-info" name="private_notes" rows="3" placeholder="Private Notes"><?=$ORDER['private_notes'];?></textarea>
<?php } else { ?>
		<p><?php echo str_replace(chr(10),'<BR>',$ORDER['private_notes']);?></p>
<?php } ?>
	</div>
</div>
