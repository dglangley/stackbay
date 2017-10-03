<style type="text/css">
	#pad-wrapper {
		margin-left: 310px;
	}

	.sidebar {
		width: 300px;
		position: fixed;
		padding-left: 10px;
		padding-right: 10px;
		overflow-y: auto;
		top: 0;
		bottom: 0;
		padding-bottom: 150px;
	}

	.sidebar-footer {
		width: 300px !important;
	}
</style>

<div class="sidebar" data-page="addition">
	<h4 class="section-header">Information</h4>

<?php if (isset($order_number)) { ?>
	<input type="hidden" name="order" value="<?=$order_number;?>">
<?php } ?>
<?php if (isset($type)) { ?>
	<input type="hidden" name="type" value="<?=$type;?>">
<?php } ?>

	<?php if (isset($edit) AND $edit) { ?>
		<select name="companyid" class="form-control input-xs company-selector required"><option value="<?=$order_details['companyid'];?>"><?=getCompany($order_details['companyid']);?></option></select>

		<input name="bid" class="form-control input-sm" class="bid" type="text" placeholder="<?=(($type != 'repair' AND ($task != 'repair' AND ! empty($task))) ? 'Bid No.' : 'Customer Ref#');?>" value="<?=$order_details['cust_ref'];?>" style="margin-bottom: 10px;">

		<select name="contactid" class="form-control input-xs contact-selector required"><option value="<?=$order_details['contactid'];?>"><?=getContact($order_details['contactid']);?></option></select>

		<select name="addressid" class="form-control input-xs address-selector required"><option value="<?=$order_details['bill_to_id'];?>"><?=address_out($order_details['bill_to_id']);?></option></select>

		<br>

		<?php if($type == 'service') { ?>
			<p class="section-header">Scope</p>
			<textarea id="scope" class="form-control" name="scope" rows="3" style="margin-bottom: 10px;" placeholder="Scope">Here is a scope of everything that is being done for the job.</textarea>
		<?php } ?>

		<p class="section-header">Task Details</p>

		<?php if(($type == 'repair' OR $type == 'build')  OR ($task == 'repair' AND ! empty($task))) { ?>
			<span class="descr-label">ERB5 &nbsp; T3PQAGCAAC</span>
			<div class="description desc_second_line descr-label" style="color:#aaa;">ALCATEL-LUCENT &nbsp;  <span class="description-label"><abbr title="DIGITAL ACCESS AND CROSS-CONNECT SYSTEM">DACS</abbr> IV PRIMARY NON-VOLATILE M</span></div>
			<br>

			<p class="section-header">Serial(s):</p>
			<?php foreach($serials as $serial): ?>
				<p><?=$serial;?></p>
			<?php endforeach; ?>
		<?php } ?>

		<?php if($type != 'repair'  AND ($task != 'repair' AND ! empty($task))) { ?>
			<select name="site_contactid" class="form-control input-xs contact-selector required">
				<option value="David Langley">David Langley</option>
			</select>

			<select name="site_addressid" class="form-control input-xs contact-selector required"><option value="25">3037 Golf Course Drive, Suite 2</option></select>
		<?php } ?>

		<?php if($type != 'repair'  AND ($task != 'repair' AND ! empty($task))) { ?>
			<div class="input-group" style="margin-bottom: 10px;">
					<span class="input-group-addon">$</span>
				<input class="form-control input-sm" name="quote" class="total_charge" type="text" placeholder="Price" value="800.00">
			</div>
		<?php } ?>

		<br>

		<p class="section-header">Public Notes</p>
		<textarea id="public_notes" class="form-control" name="public_notes" rows="3" style="margin-bottom: 10px;" placeholder=""><?=$order_details['public_notes'];?></textarea>

	<?php } else {/* ! $edit */ ?>

		<p class="companyid" data-companyid="25"><span class="company-text"><?=getCompany($order_details['companyid']);?></span></p>

		<p class="bid"><?=($order_details['cust_ref']);?></p>
	
		<p class="company_contact" data-contactid=""><?=getContact($order_details['contactid']);?></p>

		<p class="company_address" data-addressid=""><?=address_out($order_details['bill_to_id']);?></p>

		<?php if($type == 'service') { ?>
			<br>
			<p class="section-header">Scope</p>
			<p class="scope">Here is a scope of everything that is being done for the job.</p>
			<br>
		<?php } ?>

		<p class="section-header">Task Details</p>

		<?php if($type == 'repair' OR $type == 'build') { ?>
			
			<?=format($order_details['partid']);?>
			<br>

			<p class="section-header">Serial(s):</p>
			<?php foreach($serials as $serial): ?>
				<p><?=$serial;?></p>
			<?php endforeach; ?>
		<?php } ?>

		<?php if($type == 'service') { ?>
			<p class="total_charge">$800.00</p>
			<p class="site_contact">David Langley</p>

			<p class="site_address"><span class="line_1">3037 Golf Course Drive, Suite 2</span><br>
			Ventura, CA 93003<br></p>

			<p class="total_charge">$800.00</p>
		<?php } ?>

		<br>

		<p class="section-header">Public Notes</p>
		<p class="public_notes"><?=$order_details['public_notes'];?></p>
	<?php } ?>

	<br>
	<div class="sidebar-footer">
		<p class="section-header">Internal Use Only</p>
		<?php if ($edit) { ?>
			<textarea id="private_notes" class="form-control textarea-info" name="private_notes" rows="3" style="margin-bottom: 10px;" placeholder=""><?=$order_details['private_notes'];?></textarea>
		<?php } else { ?>
			<p class="private_notes"><?=$order_details['private_notes'];?></p>
		<?php } ?>
	</div>
</div>					
