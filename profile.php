<?php
	include_once 'inc/dbconnect.php';
	include_once 'inc/format_date.php';
	include_once 'inc/format_price.php';
	include_once 'inc/getCompany.php';
	include_once 'inc/getContacts.php';
	include_once 'inc/getContact.php';
	include_once 'inc/getPart.php';

	function getAddress($searchid,$search_type='addressid') {
		$A = array('name'=>'','street'=>'','city'=>'','state'=>'','postal_code'=>'','country'=>'','id'=>0);
		if ($search_type=='addressid') {
			$query = "SELECT * FROM addresses WHERE id = '".res($searchid)."'; ";
		} else if ($search_type=='companyid') {
			$query = "SELECT * FROM companies, addresses ";
			$query .= "WHERE companies.id = '".res($searchid)."' AND companies.corporateid = addresses.id; ";
		}
		$result = qdb($query);
		if (mysqli_num_rows($result)==0) { return ($A); }
		$A = mysqli_fetch_assoc($result);
		$A['address'] = $A['street'].', '.$A['city'].', '.$A['state'].' '.$A['postal_code'];
		return ($A);
	}

	$companyid = setCompany();//uses $_REQUEST['companyid'] if passed in
?>
<!DOCTYPE html>
<html>
<head>
	<title>Company Profile</title>
	<?php
		include_once 'inc/scripts.php';
	?>
	<style>
		.select2 {
			width: auto !important;
		}
		
		.pointer {
			cursor: pointer;
		}
	</style>
</head>
<body class="sub-nav profile-body">

	<?php include 'inc/navbar.php'; ?>

	<form class="form-inline" method="get" action="/save-profile.php">
<!--
	<input type="hidden" name="companyid" value="<?= $companyid; ?>">
-->

    <table class="table table-header">
		<tr>
			<td class="col-md-2">
			</td>
			<td class="text-center col-md-6">
			</td>
			<td class="col-md-3">
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
			<li class="active"><a href="#addresses_tab" data-toggle="tab"><i class="fa fa-building-o"></i> Addresses</a></li>
			<li class=""><a href="#contacts_tab" data-toggle="tab"><i class="fa fa-users" aria-hidden="true"></i> People/Contacts</a></li>
			<li class=""><a href="#terms_tab" data-toggle="tab"><i class="fa fa-file-text-o" aria-hidden="true"></i> Terms</a></li>
			<li class=""><a href="#orders" data-toggle="tab"><i class="fa fa-usd" aria-hidden="true"></i> Orders</a></li>
		</ul>
		
		<div class="tab-content">

			<!-- Materials pane -->
			<div class="tab-pane active" id="addresses_tab">
				<?php
					$A = getAddress($companyid,'companyid');
					$company_phone = getCompany($companyid,'id','phone');
				?>
				
		            <!-- side address column -->
	            <div class="col-md-4 col-xs-12 address pull-right">
				<?php if ($A['address']) { ?>
                	<iframe style="width:100%" height="200" scrolling="no" src="https://maps.google.com/maps?ie=UTF8&amp;t=m&amp;q=<?= urlencode($A['address']); ?>&amp;z=7&amp;output=embed"></iframe>
				<?php } ?>
	                <ul>
	                    <li><?= $A['street']; ?></li>
	                    <li><?= $A['city'].' '.$A['state'].' '.$A['postal_code']; ?></li>
				<?php if ($company_phone) { ?>
	                    <li class="ico-li">
	                        <i class="ico-phone"></i>
							<?= $company_phone; ?>
	                    </li>
				<?php } ?>
	                </ul>
	            </div>
            
				<div class="row">
					<div class="col-md-12">
						<input class="form-control required" name="na_name" id="add_name" placeholder="Address Name" type="text">
					</div>
				</div>
				<br>
				<div class="row">
					<div class="col-md-12">
						<input class="form-control required" name="na_line_1" id="add_line_1" placeholder="Line 1" type="text">
					</div>
				</div>
				<br>
				<div class="row">
					<div class="col-md-12">
						<input class="form-control" name="na_line2" id="add_line2" placeholder="Line 2" type="text">
					</div>
				</div>
				<br>
				<div class="row">
					<div class="col-md-6">
						<input class="form-control required" name="na_city" id="add_city" placeholder="City" type="text">
					</div>
					<div class="col-md-2">
						<input class="form-control required" name="state" id="add_state" placeholder="State" type="text">
					</div>
					<div class="col-md-4">
						<input class="form-control required" name="na" id="add_zip" placeholder="Zip" type="text">
					</div>
				</div>
				<br>
				<button type="submit" class="btn btn-default btn-submit btn-sm">Save</button>
			</div>
			
			<div class="tab-pane" id="contacts_tab">
			    <div class="col-md-8 bio">
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
	                                    <input type="text" class="form-control input-sm inline<?= $cls; ?>" name="im" value="<?= $contact['im']; ?>">
	                                </td>
	                                <td>
	                                    <input type="text" class="form-control input-sm inline<?= $cls; ?>" name="notes" value="<?= $contact['notes']; ?>">
	                                </td>
	                            </tr>
	<?php
		}
	?>
								<tr>
									<td colspan="5">
										<button type="submit" class="btn btn-default btn-submit btn-sm" disabled>Save</button>
									</td>
								</tr>
	                        </tbody>
	                    </table>
	                </div>
	            </div>
			</div>
			
			<!-- Materials pane -->
			<div class="tab-pane" id="terms_tab">
				<?php
					$selPP = ' selected';
					$selC = ' selected';
				
					$ar_list = '';
					$ap_list = '';
					$query = "SELECT * FROM terms LEFT JOIN company_terms ON company_terms.termsid = terms.id ";
					$query .= "WHERE (companyid IS NULL OR companyid = '".$companyid."') ";
					$query .= "ORDER BY days ASC, terms ASC; ";
					$result = qdb($query) OR die(qe().' '.$query);
					$num_terms = mysqli_num_rows($result);
					// first iterate to find if any company-selections are made; if not, default to select all
					$ar_selections = false;
					$ap_selections = false;
					while ($r = mysqli_fetch_assoc($result)) {
						$terms[] = $r;
						if ($r['companyid'] AND $r['category']=='AR') { $ar_selections = true; }
						if ($r['companyid'] AND $r['category']=='AP') { $ap_selections = true; }
					}
					foreach ($terms as $r) {
						$sel = '';
						if (($r['category']=='AR' AND $r['companyid']) OR $ar_selections===false) { $sel = ' selected'; }
						$ar_list .= '<option value="'.$r['id'].'" data-type="'.$r['type'].'"'.$sel.'>'.$r['terms'].'</option>'.chr(10);
				
						$sel = '';
						if (($r['category']=='AP' AND $r['companyid']) OR $ap_selections===false) { $sel = ' selected'; }
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
				<?php } /* end (!$companyid) */ ?>
			</div>
			
			<div class="tab-pane" id="orders">
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
						$list_order .= "<tr class='pointer' onclick=\"location.href='order_form.php?ps=Purchase&on=".$r['po_number']."'\">";
							$list_order .= "<td>".date("m/d/Y", strtotime($r['created']))."</td>";
							$list_order .= "<td>".$r['po_number']."</td>";
							$list_order .= "<td>PO</td>";
							$list_order .= "<td>".getContact($r['contactid'])."</td>";
							$list_order .= "<td>".$r['public_notes']."</td>";
						$list_order .= "</tr>";
					}
					
					foreach($s_orders as $r) {
						$list_order .= "<tr class='pointer' onclick=\"location.href='order_form.php?on=".$r['so_number']."'\">";
							$list_order .= "<td>".date("m/d/Y", strtotime($r['created']))."</td>";
							$list_order .= "<td>".$r['so_number']."</td>";
							$list_order .= "<td>SO</td>";
							$list_order .= "<td>".getContact($r['contactid'])."</td>";
							$list_order .= "<td>".$r['public_notes']."</td>";
						$list_order .= "</tr>";
					}
					
					foreach($rma_orders as $r) {
						$list_order .= "<tr class='pointer' onclick=\"location.href='rma.php?on=".$r['rma_number']."'\">";
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

        <div class="row">
            <!-- bio, new note & orders column -->
           
        </div><!-- row -->
	</div>
    <!-- end main container -->

<?php include_once 'inc/footer.php'; ?>

    <script type="text/javascript">
        $(document).ready(function() {
			$(".active-form").change(function() {
				var field = $(this);
				var action = field.closest("form").prop("action").replace('save-','json/save-');
				if (field.data('field')) { var k = field.data('field'); }
				else { var k = field.prop("name"); }
				var fieldid = 0;
				if (field.data('id')) { fieldid = field.data('id'); }
				var v = field.val();
				var contactid = field.closest('tr').data('contactid');
				console.log(action+'?contactid='+contactid+'&change_field='+k+'&change_value='+encodeURIComponent(v.trim())+'&fieldid='+fieldid);

				$.ajax({
					url: action,
					type: 'get',
					data: {'contactid': contactid, 'change_field': k, 'change_value': encodeURIComponent(v.trim()), id: fieldid},
					dataType: 'json',
					success: function(json, status) {
						if (json.message=='Success') {
							toggleLoader('Save successful');
							field.data('id',json.id);
							if (json.data && json.data!='') { field.val(json.data); }
						} else {
							alert(json.message);
						}
					},
					error: function(xhr, desc, err) {
						console.log(xhr);
						console.log("Details: " + desc + "\nError:" + err);
					}
				}); // end ajax call
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
