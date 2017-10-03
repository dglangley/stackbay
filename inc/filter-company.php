<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';

	if (! isset($companyid)) { $companyid = 0; }
?>
			<div class="form-group pull-right">
				<select name="companyid" size="1" class="company-selector">
					<option value="">- Select Company -</option>
					<?php if ($companyid) { echo '<option value="'.$companyid.'" selected>'.getCompany($companyid).'</option>'.chr(10); } ?>
				</select>
				<button class="btn btn-sm btn-primary" type="submit"><i class="fa fa-filter"></i></button>
           	</div>
