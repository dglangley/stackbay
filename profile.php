<?php
	include_once 'inc/dbconnect.php';
	include_once 'inc/format_date.php';
	include_once 'inc/format_price.php';
	include_once 'inc/getAddresses.php';
	include_once 'inc/getCompany.php';
	include_once 'inc/getContact.php';
	include_once 'inc/getContacts.php';
	include_once 'inc/getPart.php';

	//Includes one is broken
	function getFreight() {
		$freights = array();
		
		$select = "SELECT f.id as freightid, c.name, c.id as companyid FROM freight_carriers as f, companies as c WHERE c.id = f .companyid ORDER BY c.id DESC;";
        $results = qdb($select);
        
        while ($row = $results->fetch_assoc()) {
			$freights[] = $row;
		}
        
        return $freights;
	}
	
	if (isset($_REQUEST['tab']) AND $_REQUEST['tab']) { $tab = $_REQUEST['tab']; }
	if (isset($_REQUEST['s']) AND $_REQUEST['s']) {
		$company = $_REQUEST['s'];

		$companyid = getCompany($company, "name", "id");
	} else {
		$companyid = setCompany();//uses $_REQUEST['companyid'] if passed in
	}
	
	function getAddress($searchid,$search_type='addressid') {
		$A = array('name'=>'','street'=>'','city'=>'','state'=>'','postal_code'=>'','country'=>'','id'=>0);
		
		if ($search_type=='addressid') {
			$query = "SELECT * FROM `addresses` WHERE id = '".res($searchid)."'; ";
		} else if ($search_type=='companyid') {
			$query = "SELECT * FROM `companies`, `addresses` ";
			$query .= "WHERE companies.id = '".res($searchid)."' AND companies.corporateid = addresses.id; ";
		}
		
		$result = qdb($query);
		if (mysqli_num_rows($result)==0) { return ($A); }
		$A = mysqli_fetch_assoc($result);
		$A['address'] = $A['street'].', '.$A['city'].', '.$A['state'].' '.$A['postal_code'];
		return ($A);
	}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Company Profile</title>
	<?php
		include_once 'inc/scripts.php';
	?>
	<style>

		.terms-section .select2 {
			width: auto !important;
		}

		
		.pointer {
			cursor: pointer;
		}
	</style>
</head>
<body class="sub-nav profile-body">

	<?php include 'inc/navbar.php'; ?>

	<form class="form-inline" method="POST" action="/save-profile.php" enctype="multipart/form-data" name="profile_form">
	<input type="hidden" name="tab" value="<?= $tab; ?>">

    <table class="table table-header">
		<tr>
			<td class="col-md-2">
			</td>
			<td class="text-center col-md-6">
			</td>
			<td class="col-md-4">
				<div class="pull-right form-group">
					<select name="companyid" id="companyid" class="company-selector">
						<option value="">- Select a Company -</option>
						<?php 
							if ($companyid) { 
								echo '<option value="'.$companyid.'" selected>'.getCompany($companyid).'</option>'.chr(10); 
							} else { 
								echo '<option value="">- Select a Company -</option>'.chr(10); 
							} 
						?>
					</select>
					<input class="btn btn-primary btn-sm" type="submit" value="Go">
				</div>
			</td>
		</tr>
	</table>


    <div id="pad-wrapper" class="user-profile">

<?php if (! $companyid) { ?>

    <table class="table">
		<tr>
			<td class="col-md-12 text-center">
				Use the Select Company menu above to search for a company...
			</td>
		</tr>
	</table>

<?php } else { ?>

        <!-- header -->
        <div class="row header">
            <div class="col-md-9">
				<div class="business-icon"><i class="fa fa-book fa-4x"></i></div>
                <h2 class="name"><?= getCompany($companyid); ?></h2>
            </div>
            <div class="col-md-3 text-right">
	            <a class="btn btn-default icon pull-right" data-toggle="tooltip" title="Delete" data-placement="top"><i class="fa fa-trash text-danger"></i></a>
	            <a class="btn btn-default icon pull-right" data-toggle="tooltip" title="Edit" data-placement="top"><i class="fa fa-pencil"></i></a>
	            <a href="/accounts.php?companyid=<?= $companyid; ?>" class="btn btn-default icon pull-right" data-toggle="tooltip" title="Accounts" data-placement="top"><i class="fa fa-building-o"></i></a>
			</div>
        </div>
        
         <ul class="nav nav-tabs nav-tabs-ar">
			<li class="<?php if (! $tab OR $tab=='contacts') { echo 'active'; } ?>"><a href="#contacts_tab" data-toggle="tab"><i class="fa fa-users" aria-hidden="true"></i> People/Contacts</a></li>
			<li class="<?php if (! $tab OR $tab=='addresses') { echo 'active'; } ?>"><a href="#addresses_tab" data-toggle="tab"><i class="fa fa-building-o"></i> Addresses</a></li>
         	<li class="<?php if (! $tab OR $tab=='orders') { echo 'active'; } ?>"><a href="#orders" data-toggle="tab"><i class="fa fa-usd" aria-hidden="true"></i> Orders</a></li>
<?php if (in_array("4", $USER_ROLES)) { ?>
			<li class="<?php if (! $tab OR $tab=='terms') { echo 'active'; } ?>"><a href="#terms_tab" data-toggle="tab"><i class="fa fa-file-text-o" aria-hidden="true"></i> Terms</a></li>
<?php } ?>
			<li class="<?php if (! $tab OR $tab=='freight') { echo 'active'; } ?>"><a href="#freight_tab" data-toggle="tab"><i class="fa fa-truck" aria-hidden="true"></i> Freight Accounts</a></li>
		</ul>
		
		<div class="tab-content">

			<!-- Materials pane -->
			<div class="tab-pane <?php if (! $tab OR $tab=='addresses') { echo 'active'; } ?> id="addresses_tab">
				<!-- recent orders table -->
                <table class="table table-hover table-striped table-condensed">
                    <thead>
                        <tr>
                            <th class="col-md-1">
                                Company
                            </th>
                            <th class="col-md-1">
                                <span class="line"></span>
                                Street
                            </th>
                            <th class="col-md-1">
                                <span class="line"></span>
                                Addr2
                            </th>
                            <th class="col-md-1">
                                <span class="line"></span>
                                City
                            </th>
                            <th class="col-md-1">
                                <span class="line"></span>
                                State
                            </th>
                            <th class="col-md-1">
                                <span class="line"></span>
                                Postal Code
                            </th>
                            <th class="col-md-1">
                                <span class="line"></span>
                                Country
                            </th>
                            <th class="col-md-1">
                                <span class="line"></span>
                                Site Nickname
                            </th>
                            <th class="col-md-1">
                                <span class="line"></span>
                                Site Alias
                            </th>
                            <th class="col-md-1">
                                <span class="line"></span>
                                Site Contact (Attn)
                            </th>
                            <th class="col-md-1">
                                <span class="line"></span>
                                Site Code
                            </th>
                            <th class="col-md-1">
                                <span class="line"></span>
                                Site Notes
                            </th>
                        </tr>
                    </thead>
                    <tbody>
<?php
	$addresses = array();

	//Get the addressids associated with the companyid
	$query = "SELECT * FROM company_addresses c, addresses a ";
	$query .= "WHERE companyid = $companyid AND c.addressid = a.id ";
	$query .= "ORDER BY addressid ASC;";
	$result = qdb($query) OR die(qe().' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$addresses[] = $r;//['addressid'];
	}

	foreach ($addresses as $r) {//$addressid) {
		$addressid = $r['id'];
		//$address_info = getAddresses($addressid);
?>
                        <tr>
							<td><input type="text" class="form-control input-xs inline static-form" name="address_name[<?= $addressid; ?>]" value="<?=$r['name']?>" placeholder="Company"></td>
							<td><input type="text" class="form-control input-xs inline static-form" name="address_street[<?= $addressid; ?>]" value="<?=$r['street']?>" placeholder="Street"></td>
							<td><input type="text" class="form-control input-xs inline static-form" name="address_addr2[<?= $addressid; ?>]" value="<?=$r['addr2']?>" placeholder="Addr2"></td>
							<td><input type="text" class="form-control input-xs inline static-form" name="address_city[<?= $addressid; ?>]" value="<?=$r['city']?>" placeholder="City"></td>
							<td><input type="text" class="form-control input-xs inline static-form" name="address_state[<?= $addressid; ?>]" value="<?=$r['state']?>" placeholder="State"></td>
							<td><input type="text" class="form-control input-xs inline static-form" name="address_postal[<?= $addressid; ?>]" value="<?=$r['postal_code']?>" placeholder="Postal Code"></td>
							<td><input type="text" class="form-control input-xs inline static-form" name="address_country[<?= $addressid; ?>]" value="<?=$r['country']?>" placeholder="Country" readonly></td>
							<td><input type="text" class="form-control input-xs inline static-form" name="address_nickname[<?= $addressid; ?>]" value="<?=$r['nickname']?>" placeholder="Nickname"></td>
							<td><input type="text" class="form-control input-xs inline static-form" name="address_alias[<?= $addressid; ?>]" value="<?=$r['alias']?>" placeholder="Alias"></td>
							<td>
								<select name="address_contactid[<?= $addressid; ?>]" size="1" class="form-control input-sm contact-selector">
									<option value="<?= $r['contactid']; ?>" selected><?= getContact($r['contactid']); ?></option>
								</select>
							</td>
							<td><input type="text" class="form-control input-xs inline static-form" name="address_code[<?= $addressid; ?>]" value="<?=$r['code']?>" placeholder="Code"></td>
							<td><input type="text" class="form-control input-xs inline static-form" name="address_notes[<?= $addressid; ?>]" value="<?=$r['notes']?>" placeholder="Notes"></td>
                        </tr>
<?php
}
?>
						<tr>
							<td><input type="text" class="form-control input-xs inline static-form" name="address_name[0]" value="" placeholder="Add Address..."></td>
							<td><input type="text" class="form-control input-xs inline static-form" name="address_street[0]" value="" placeholder="Street"></td>
							<td><input type="text" class="form-control input-xs inline static-form" name="address_addr2[0]" value="" placeholder="Addr2"></td>
							<td><input type="text" class="form-control input-xs inline static-form" name="address_city[0]" value="" placeholder="City"></td>
							<td><input type="text" class="form-control input-xs inline static-form" name="address_state[0]" value="" placeholder="State"></td>
							<td><input type="text" class="form-control input-xs inline static-form" name="address_postal[0]" value="" placeholder="Postal"></td>
							<td><input type="text" class="form-control input-xs inline static-form" name="address_country[0]" value="US" placeholder="Country" readonly></td>
							<td><input type="text" class="form-control input-xs inline static-form" name="address_nickname[0]" value="" placeholder="Nickname"></td>
							<td><input type="text" class="form-control input-xs inline static-form" name="address_alias[0]" value="" placeholder="Alias"></td>
							<td>
								<select name="address_contactid[0]" size="1" class="form-control input-sm contact-selector">
								</select>
							</td>
							<td><input type="text" class="form-control input-xs inline static-form" name="address_code[0]" value="" placeholder="Code"></td>
							<td><input type="text" class="form-control input-xs inline static-form" name="address_notes[0]" value="" placeholder="Notes"></td>
                        </tr>
						<tr>
							<td colspan="11">
								<button class="btn btn-default btn-sm btn-file" type="button" data-id="file-upload"><i class="fa fa-paperclip"></i></button>
								<input id="file-upload" class="file-upload" name="file_upload" accept=".csv, application/vnd.ms-excel, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" value="" type="file">
								<button class="btn btn-sm btn-default file-submit" type="button"><i class="fa fa-upload"></i></button>
							</td>
							<td class="text-right">
								<button type="submit" name="submit" value="address" class="btn btn-success btn-submit btn-sm">Save</button>
							</td>
						</tr>
                    </tbody>
                </table>
			</div>
			
			<div class="tab-pane <?php if (! $tab OR $tab=='contacts') { echo 'active'; } ?>" id="contacts_tab">
			    <!--<div class="col-md-12 bio">-->
	                <div class="profile-box">
	
	                    <!-- recent orders table -->
	                    <table class="table table-hover table-striped table-condensed">
	                        <thead>
	                            <tr>
	                                <th class="col-md-3">
	                                    Contact Name
	                                </th>
	                                <th class="col-md-3">
	                                    <span class="line"></span>
	                                    Email(s)
	                                </th>
	                                <th class="col-md-3">
	                                    <span class="line"></span>
	                                    Phone Number(s)
	                                </th>
	                                <th class="col-md-1">
	                                    <span class="line"></span>
	                                    IM
	                                </th>
	                                <th class="col-md-2">
	                                    <span class="line"></span>
	                                    Notes
	                                </th>
	                            </tr>
	                        </thead>
	                        <tbody>
	<?php
		$contacts = getContacts($companyid);
		foreach ($contacts as $contactid => $contact) {
			$cls = ' static-form';
			if ($contactid) { $cls = ' active-form'; }
			$name_plh = 'Full Name (Last Name optional)';
			if ($contactid==0) { $name_plh = 'Add New Contact Name...'; }
	
			$emails = '';
			foreach($contact['emails'] as $e) {
				$email_plh = $e['email'];
				if (! $email_plh) { $email_plh = 'this@that.com'; }
				$emails .= '<input type="text" class="form-control input-sm inline'.$cls.'" name="emails['.$contactid.']['.$e['id'].']" value="'.$e['email'].'" data-field="email" data-id="'.$e['id'].'" placeholder="'.$email_plh.'">'.chr(10);
			}
			$phones = '';
			foreach($contact['phones'] as $p) {
				$phone_plh = $p['phone'];
				if (! $phone_plh) { $phone_plh = '(000) 000-0000 or 000-000-0000'; }
				$phones .= '<input type="text" class="form-control input-sm inline'.$cls.'" name="phones['.$contactid.']['.$p['id'].']" value="'.$p['phone'].'" data-field="phone" data-id="'.$p['id'].'" placeholder="'.$phone_plh.'">'.chr(10);
			}
	?>
	                            <tr>
	                                <td>
	                                    <input type="text" class="form-control input-sm inline<?= $cls; ?>" name="name[<?= $contactid; ?>]" value="<?= $contact['name']; ?>" placeholder="<?= $name_plh; ?>">
	                                </td>
	                                <td>
										<?= $emails; ?>
	                                </td>
	                                <td>
										<?= $phones; ?>
	                                </td>
	                                <td>
	                                    <input type="text" class="form-control input-sm inline<?= $cls; ?>" name="im[<?= $contactid; ?>]" value="<?= $contact['ebayid']; ?>">
	                                </td>
	                                <td>
	                                    <input type="text" class="form-control input-sm inline<?= $cls; ?>" name="notes[<?= $contactid; ?>]" value="<?= $contact['notes']; ?>">
	                                </td>
	                            </tr>
	<?php
		}
	?>
								<tr>
									<td colspan="5">
										<button type="submit" name="submit" value="contact" class="btn btn-success btn-submit btn-sm">Save</button>
									</td>
								</tr>
	                        </tbody>
	                    </table>
	                </div>
	            <!--</div>-->
			</div>
			
			<div class="tab-pane <?php if (! $tab OR $tab=='freight') { echo 'active'; } ?> id="freight_tab">
				<!-- recent orders table -->
                <table class="table table-hover table-striped table-condensed">
                    <thead>
                        <tr>
                            <th class="col-md-6">
                                Account Number
                            </th>
                            <th class="col-md-6">
                                <span class="line"></span>
                                Carrier
                            </th>
                        </tr>
                    </thead>
                    <tbody>
<?php
$freights = array();

//Get the addressids associated with the companyid
$query = "SELECT * FROM freight_accounts ";
$query .= "WHERE companyid = $companyid ";
$query .= "ORDER BY id ASC;";

$result = qdb($query) OR die(qe().' '.$query);
						
while ($r = mysqli_fetch_assoc($result)) {
	$freights[] = $r;
}

$freight_info = getFreight();
foreach ($freights as $freight) {
?>
                        <tr>
							<td><input type="text" class="form-control input-sm inline static-form" name="account_number[<?= $freight['id']; ?>]" value="<?=$freight['account_no'];?>" placeholder="Account Number"></td>
							<td>
								<select name="carrier[<?= $freight['id']; ?>]">
									<?php 
										foreach($freight_info as $freightc) { 
											echo '<option value="'.$freightc['freightid'].'" '.($freight['carrierid'] == $freightc['freightid'] ? 'selected' : '').'>'.$freightc['name'].'</option>';
										}
									?>
								</select>
							</td>
                        </tr>
<?php
}
?>
						<tr>
							<td><input type="text" class="form-control input-sm inline static-form" name="account_number[0]" value="" placeholder="Account Number"></td>
							<td>
								<select name="carrier[0]">
									<?php 
										foreach($freight_info as $freight) { 
											echo '<option value="'.$freight['freightid'].'">'.$freight['name'].'</option>';
										}
									?>
								</select>
							</td>
                        </tr>
						<tr>
							<td colspan="5">
								<button type="submit" name="submit" value="freight" class="btn btn-default btn-submit btn-sm">Save</button>
							</td>
						</tr>
                    </tbody>
                </table>
			</div>
			
			<!-- Materials pane -->
			<div class="tab-pane <?php if (! $tab OR $tab=='terms') { echo 'active'; } ?> id="terms_tab">
				<?php
					$selPP = ' selected';
					$selC = ' selected';

					$terms = array();
					$ar_list = '';
					$ap_list = '';
/*
					$query = "SELECT * FROM terms LEFT JOIN company_terms ON company_terms.termsid = terms.id ";
					$query .= "WHERE (companyid IS NULL OR companyid = '".$companyid."') ";
					$query .= "ORDER BY days ASC, terms ASC; ";
*/
					$query = "SELECT * FROM terms ORDER BY days ASC, terms ASC; ";
					$result = qdb($query) OR die(qe().' '.$query);
					$num_terms = mysqli_num_rows($result);
					// first iterate to find if any company-selections are made; if not, default to select all
					$ar_selections = false;
					$ap_selections = false;
					while ($r = mysqli_fetch_assoc($result)) {
						$terms[] = $r;
					}
					// check for company terms, and if ANY are set, restrict accordingly
					$company_AR = array();
					$company_AP = array();
					$query = "SELECT * FROM company_terms WHERE companyid = '".res($companyid)."'; ";
					$result = qdb($query) OR die(qe().' '.$query);
					while ($r = mysqli_fetch_assoc($result)) {
						if ($r['category']=='AR') {
							$company_AR[$r['termsid']] = true;
						}
						if ($r['category']=='AP') {
							$company_AP[$r['termsid']] = true;
						}
					}

					$nAR = count($company_AR);
					$nAP = count($company_AP);
					foreach ($terms as $r) {
						if (array_key_exists($r['id'],$company_AR)) { $ar_selections = true; }
						if (array_key_exists($r['id'],$company_AP)) { $ap_selections = true; }

						$sel = '';
						if ($nAR==0 OR array_key_exists($r['id'],$company_AR)) { $sel = ' selected'; }
						$ar_list .= '<option value="'.$r['id'].'" data-type="'.$r['type'].'"'.$sel.'>'.$r['terms'].'</option>'.chr(10);
				
						$sel = '';
						if ($nAP==0 OR array_key_exists($r['id'],$company_AP)) { $sel = ' selected'; }
						$ap_list .= '<option value="'.$r['id'].'" data-type="'.$r['type'].'"'.$sel.'>'.$r['terms'].'</option>'.chr(10);
					}
				?>
				
				<div class="row terms-section bg-sales">
					<div class="col-sm-2">
						<h4><i class="fa fa-arrow-circle-o-left"></i> Receivable Terms</h4>
					</div>
					<div class="col-sm-2">
						Type:<br/>
						<select class="form-control terms-select2 terms-type" multiple>
							<option value="Prepaid"<?= $selPP; ?>>Prepaid</option>
							<option value="Credit"<?= $selC; ?>>Credit</option>
						</select>
					</div>
					<div class="col-sm-8">
						Approved Terms:<br/>
						<select name="ar_termsids" class="form-control terms-select2 terms-selections" data-category="AR" data-companyid="<?= $companyid; ?>" multiple>
							<?= $ar_list; ?>
						</select>
					</div>
				</div>
				<div class="row terms-section bg-purchases">
					<div class="col-sm-2">
						<h4><i class="fa fa-arrow-circle-o-right"></i> Payable Terms</h4>
					</div>
					<div class="col-sm-2">
						Type:<br/>
						<select class="form-control terms-select2 terms-type" multiple>
							<option value="Prepaid"<?= $selPP; ?>>Prepaid</option>
							<option value="Credit"<?= $selC; ?>>Credit</option>
						</select>
					</div>
					<div class="col-sm-8">
						Approved Terms:<br/>
						<select name="ap_termsids" class="form-control terms-select2 terms-selections" data-category="AP" data-companyid="<?= $companyid; ?>" multiple>
							<?= $ap_list; ?>
						</select>
					</div>
				</div>
			</div>
			
			<div class="tab-pane <?php if (! $tab OR $tab=='orders') { echo 'active'; } ?> id="orders">
				<?php
					$p_orders = array();
					$s_orders = array();
					$rma_orders = array();
					
					//RO coming soon
					$ro_orders = array();
					
					$list_order = '';
					
					function getOrders($companyid, $table) {
						$orders = array();
						
						//Pull items based on the company id and all the orders they have associated with them
						$query = "SELECT * FROM $table ";
						$query .= "WHERE companyid = $companyid ";
						$query .= "ORDER BY created DESC;";
						$result = qdb($query) OR die(qe().' '.$query);
						
						while ($r = mysqli_fetch_assoc($result)) {
							$orders[] = $r;
						}
						
						return $orders;
					}
					
					$p_orders = getOrders($companyid, 'purchase_orders');
					$s_orders = getOrders($companyid, 'sales_orders');
					$rma_orders = getOrders($companyid, 'returns');
					
					foreach($p_orders as $r) {
						$list_order .= "<tr class='pointer purchase_orders' onclick=\"location.href='order_form.php?ps=Purchase&on=".$r['po_number']."'\">";
							$list_order .= "<td>".date("m/d/Y", strtotime($r['created']))."</td>";
							$list_order .= "<td>".$r['po_number']."</td>";
							$list_order .= "<td>PO</td>";
							$list_order .= "<td>".getContact($r['contactid'])."</td>";
							$list_order .= "<td>".$r['public_notes']."</td>";
						$list_order .= "</tr>";
					}
					
					foreach($s_orders as $r) {
						$list_order .= "<tr class='pointer sales_orders' onclick=\"location.href='order_form.php?on=".$r['so_number']."'\">";
							$list_order .= "<td>".date("m/d/Y", strtotime($r['created']))."</td>";
							$list_order .= "<td>".$r['so_number']."</td>";
							$list_order .= "<td>SO</td>";
							$list_order .= "<td>".getContact($r['contactid'])."</td>";
							$list_order .= "<td>".$r['public_notes']."</td>";
						$list_order .= "</tr>";
					}
					
					foreach($rma_orders as $r) {
						$list_order .= "<tr class='pointer rma_orders' onclick=\"location.href='rma.php?rma=".$r['rma_number']."'\">";
							$list_order .= "<td>".date("m/d/Y", strtotime($r['created']))."</td>";
							$list_order .= "<td>".$r['rma_number']."</td>";
							$list_order .= "<td>RMA</td>";
							$list_order .= "<td>".getContact($r['contactid'])."</td>";
							$list_order .= "<td>".$r['public_notes']."</td>";
						$list_order .= "</tr>";
					}
				?>
				
				<div class="btn-group" data-toggle="buttons" style="margin-bottom: 15px;">
			        <button class="glow left large btn-report filter_status " type="submit" data-filter="active">
			        	<i class="fa fa-sort-numeric-desc"></i>	
			        </button>
					<!--<input type="radio" name="report_type" value="summary" class="hidden">-->
			        <button class="glow center large btn-report filter_status " type="submit" data-filter="complete">
			        	<i class="fa fa-history"></i>	
			        </button>
			        <!--<input type="radio" name="report_type" value="detail" class="hidden">-->
					<button class="glow right large btn-report filter_status active" type="submit" data-filter="all" checked="">
			        	All<!--<i class="fa fa-history"></i>	-->
			        </button>
			        <!--<input type="radio" name="report_type" value="detail" class="hidden">-->
			    </div>
			    
				
				<table class="table table-hover table-striped table-condensed">
                    <thead>
                        <tr>
                            <th class="col-md-3">
                                DATE
                            </th>
                            <th class="col-md-2">
                                <span class="line"></span>
                                ORDER#
                            </th>
                            <th class="col-md-1">
                                <span class="line"></span>
                                ORDER TYPE
                            </th>
                            <th class="col-md-3">
                                <span class="line"></span>
                                CONTACT
                            </th>
                            <th class="col-md-3">
                                <span class="line"></span>
                                NOTES
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                    	<?= $list_order; ?>
					</tbody>
				</table>
			</div>
			
		</div>
<?php } /* end (!$companyid) */ ?>
	</div>
    <!-- end main container -->

	</form>

<?php include_once 'inc/footer.php'; ?>

    <script type="text/javascript">
        $(document).ready(function() {
			companyid = '<?= $companyid; ?>';
			$(".file-submit").on("click", function(e) {
				var uploader = $("#file-upload").val();
				if (uploader=='') {
					modalAlertShow("Upload Error","Click the paperclip button to upload a file first, then click this Upload button");
					return;
				}

				$(this).closest("form").find("button[type=submit]").click();
			});
			$(".active-form").on("keypress",function(e) {
				if (e.keyCode == 13) {
					e.preventDefault();
				}
			});
			$(".static-form").change(function() {
				if ($(".btn-submit").prop('disabled',true)) {
					$(".btn-submit").prop('disabled',false);
					$(".btn-submit").toggleClass('btn-default btn-primary');
				} else {
					$(".btn-submit").toggleClass('btn-primary btn-default');
				}
			});
			$(".terms-selections").change(function() {
	            console.log(window.location.origin+"/json/save-terms.php?companyid="+$(this).data('companyid')+"&termsids="+$(this).val()+"&category="+$(this).data('category'));
	            $.ajax({
					url: 'json/save-terms.php',
					type: 'get',
					data: {'companyid': $(this).data('companyid'), 'termsids': $(this).val(), 'category': $(this).data('category')},
					dataType: 'json',
					success: function(json, status) {
						if (json.message=='Success') {
							toggleLoader('Save successful');
						} else {
							alert(json.message);
						}
					},
					error: function(xhr, desc, err) {
						console.log(xhr);
						console.log("Details: " + desc + "\nError:" + err);
					}
				}); // end ajax call

				return;
			});
        });
    </script>

</body>
</html>
