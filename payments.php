<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getUser.php';

	$payment_filter =  isset($_REQUEST['keyword']) ? $_REQUEST['keyword'] : '';
	// $types =  isset($_REQUEST['order_type']) ? $_REQUEST['order_type'] : 'Sale';
	$START_DATE = (isset($_REQUEST['START_DATE']) ? format_date($_REQUEST['START_DATE'],'m/d/Y') : format_date($GLOBALS['today'],'m/d/Y',array('d'=>-60)));
	$startDate = format_date($START_DATE,'Y-m-d');
	$END_DATE = (isset($_REQUEST['END_DATE']) AND ! empty($_REQUEST['END_DATE'])) ? format_date($_REQUEST['END_DATE'],'m/d/Y') : format_date($GLOBALS['today'],'m/d/Y');
	$endDate = format_date($END_DATE,'Y-m-d');
	$company_filter = isset($_REQUEST['companyid']) ? ucwords($_REQUEST['companyid']) : '';
	$view = isset($_REQUEST['view']) ? $_REQUEST['view'] : '';

	function getAllPayments() {
		global $payment_filter,  $startDate, $endDate, $company_filter;

		$payments = array();

		$query = "SELECT * FROM payments WHERE 1=1 ";
		if($payment_filter) {
			// Can make this search more or maybe payment number in the future
			$query .= "AND id = ".fres($payment_filter)." ";
		}
		if($company_filter) {
			// Can make this search more or maybe payment number in the future
			$query .= "AND companyid = ".fres($company_filter)." ";
		}
		// if($types) {
		// 	// Can make this search more or maybe payment number in the future
		// 	$query .= "AND type = ".fres($payment_filter)." ";
		// }
		if($startDate AND $endDate) {
			// Can make this search more or maybe payment number in the future
			$query .= "AND date between CAST('".$startDate."' AS DATE) and CAST('".$endDate."' AS DATE) ";
		}
		$query .= "ORDER BY DATE DESC LIMIT 250;";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$payments[] = $r;
		}

		return $payments;
	}

	function buildSubRows($details, $header) {
		$html_rows = '';
		$init = true;
		if(! empty($details)) {
			foreach($details as $sub) {
				$html_rows .= '<tr>';

				if($init) {
					$html_rows .= '
						<td colspan="12" class="">
							<table class="table table-condensed commission-table">
								<tbody>';
				}

				if($header AND $init) {
					$html_rows .= '
									<tr>
										<th class="col-md-4">Description</th>
										<th class="col-md-1">Qty</th>
										<th class="col-md-1">Filled</th>
										<th class="col-md-1 text-right">Price (ea)</th>
										<th class="col-md-1 text-right">Ext Price</th>
										<th class="col-md-1 text-right">Credits</th>
										<th class="col-md-3 text-right"></th>
									</tr>';
				}
				$html_rows .= '
									<tr>
										<td class="col-md-4">'.display_part($sub['partid'], true).'</td>
										<td class="col-md-1">'.$sub['qty'].'</td>
										<td class="col-md-1">'.$sub['complete_qty'].'</td>
										<td class="col-md-1 text-right"><span class="info">'.format_price(($sub['price']) ?: 0).'</span></td>
										<td class="col-md-1 text-right"><span class="info">'.format_price(($sub['price'] * $sub['qty']) ?: 0).'</span></td>
										<td class="col-md-1 text-right"><span class="info">'.format_price(($sub['credit']) ?: 0).'</span></td>
										<td class="col-md-3"></td>
									</tr>
				';

				$init = false;
			}

			$html_rows .= '
								</tbody>
							</table>
						</td>
					</tr>';
		}

		return $html_rows;
	}

	function buildRows($PAYMENTS) {
		// Global Filters
		global $company_filter, $master_report_type, $filter, $view;

		$html_rows = '';
		$init = true;

		foreach($PAYMENTS as $id => $details) {
			$html_rows .= '<tr class="payment_row">';
			$html_rows .= '	<td>
								<div class="payment_details">'.format_date($details['date']).'</div>
								<div class="payment_input input-group datepicker-date date datetime-picker hidden" data-format="MM/DD/YYYY">
									<input type="text" name="payment_date" class="form-control input-sm" value="'.format_date($details['date']).'" disabled>
									<span class="input-group-addon">
										<span class="fa fa-calendar"></span>
									</span>
								</div>
							</td>';

			if(!$company_filter) {
				$html_rows .= '	<td><a href="/profile.php?companyid='.$part['companyid'].' target="_blank"><i class="fa fa-building" aria-hidden="true"></i></a> '.getCompany($details['companyid']).'</td>';
			}
			$html_rows .= '	<td><div class="payment_details">'.$details['payment_type'].'</div>
								<div class="payment_input hidden">
									<select name="payment_type" class="form-control select2" disabled>
										<option value="ACH" '.($details['payment_type'] == 'ACH' ? 'selected': '').'>ACH</option>
										<option value="Check" '.($details['payment_type'] == 'Check' ? 'selected': '').'>Check</option>
										<option value="Credit Card" '.($details['payment_type'] == 'Credit Card' ? 'selected': '').'>Credit Card</option>
										<option value="Paypal" '.($details['payment_type'] == 'Paypal' ? 'selected': '').'>Paypal</option>
										<option value="Other" '.($details['payment_type'] == 'Other' ? 'selected': '').'>Other</option>
									</select>
								</div>
							</td>';
			$html_rows .= '	<td><div class="payment_details">'.$details['number'].'</div><input class="form-control input-sm hidden payment_input" name="payment_number" value="'.$details['number'].'" disabled></td>';
			if($details['payment_type'] == 'Check') {
				$html_rows .= '	<td class="text-right">'.format_price($details['amount']).' <a target="_blank" href="/print_check.php?payment='.$details['id'].'"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></a></td>';
				// <a target="_blank" href="/docs/Payment'.$details['id'].'.pdf">
			} else {
				$html_rows .= '	<td class="text-right">'.format_price($details['amount']).'</td>';
			}
			$html_rows .= '	<td><div class="payment_details">'.$details['notes'].'</div><input class="form-control input-sm hidden payment_input" name="notes" value="'.$details['notes'].'" disabled></td>';
			$html_rows .= '	<td>'.getUser($details['userid']).'</td>';
			$html_rows .= '	<td class="text-right"><span class="info">'.$details['id'].'</span></td>';
			$html_rows .= '	<td class="text-right">
								<a href="javascript:void(0);" class="edit_payment_row"><i class="fa fa-pencil fa-4" aria-hidden="true"></i></a>
								<button class="btn btn-sm btn-success hidden payment_submit" type="submit" name="paymentid" value="'.$details['id'].'"><i class="fa fa-floppy-o" aria-hidden="true"></i></button>
							</td>';
			$html_rows .= '</tr>';
		}

		return $html_rows;
	}

	$PAYMENTS = getAllPayments();

	// print "<pre>" . print_r($PAYMENTS, true) . "</pre>";

	$TITLE = ($company_filter ? getCompany($company_filter) :"Payments");
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
	</style>
</head>
<body>

<?php include_once 'inc/navbar.php'; ?>

<!-- FILTER BAR -->
<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px; max-height:60px;">
	<form class="form-inline" method="get" action="" enctype="multipart/form-data" id="filters-form" >
		<div class="row" style="padding:8px">
			<div class="col-sm-1">
			</div>
			<div class="col-sm-1">
				<a href="financial.php" class="btn btn-default btn-sm"><i class="fa fa-building-o"></i> Financial Accounts</a>
			</div>
			<div class="col-sm-3">
				<div class="form-group">
					<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY">
			            <input type="text" name="START_DATE" class="form-control input-sm" value="<?=$START_DATE;?>">
			            <span class="input-group-addon">
			                <span class="fa fa-calendar"></span>
			            </span>
			        </div>
				</div>
				<div class="form-group">
					<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY" data-maxdate="">
			            <input type="text" name="END_DATE" class="form-control input-sm" value="<?=$END_DATE;?>">
			            <span class="input-group-addon">
			                <span class="fa fa-calendar"></span>
			            </span>
				    </div>
				</div>
				<div class="form-group">
					<button class="btn btn-primary btn-sm btn-filter" type="submit"><i class="fa fa-filter" aria-hidden="true"></i></button>
					<div class="btn-group" id="dateRanges">
						<div id="btn-range-options">
							<button class="btn btn-default btn-sm">&gt;</button>
							<div class="animated fadeIn hidden" id="date-ranges">
						        <button class="btn btn-sm btn-default left large btn-report" type="button" data-start="<?php echo date("m/01/Y"); ?>" data-end="<?php echo date("m/d/Y"); ?>">MTD</button>
						        <?=$html_quarters;?>
							</div><!-- animated fadeIn -->
						</div><!-- btn-range-options -->
					</div><!-- btn-group -->
				</div><!-- form-group -->
			</div>
			<div class="col-sm-2 text-center">
				<h2 class="minimal"><?php echo $TITLE; ?></h2>
				<span class="info"></span>
			</div>
			<div class="col-sm-1">
				<div class="input-group">
					<input type="text" name="keyword" class="form-control input-sm upper-case auto-select" value="<?=$payment_filter;?>" placeholder="Payment#" autofocus="">
					<span class="input-group-btn">
						<button class="btn btn-primary btn-sm" type="submit"><i class="fa fa-filter" aria-hidden="true"></i></button>
					</span>
				</div>
			</div>
			<div class="col-sm-2">
				<div class="pull-right form-group">
					<select name="companyid" id="companyid" class="company-selector">
						<option value="">- Select a Company -</option>
						<?php 
							if ($company_filter) {echo '<option value="'.$company_filter.'" selected>'.(getCompany($company_filter)).'</option>'.chr(10);} 
							else {echo '<option value="">- Select a Company -</option>'.chr(10);} 
						?>
					</select>
					<button class="btn btn-primary btn-sm btn-filter" type="submit">
						<i class="fa fa-filter" aria-hidden="true"></i>
					</button>
				</div>
			</div>
			<div class="col-sm-2">
			</div>
		</div>
	</form>
</div>

<div id="pad-wrapper">
<form class="form-inline" method="get" action="update-payments.php" enctype="multipart/form-data" >
	<input type="hidden" name="page" value="payments.php">
	<table class="table table-hover table-striped table-condensed">
		<thead>
            <tr>
                <th class="col-md-1">
                    Date 
                </th>

                <?php if(! $company_filter) { 
	                echo '<th class="col-md-2">
	                    <span class="line"></span>
	                    Company
	                </th>';
	            } ?>
                <th class="col-md-1">
                    <span class="line"></span>
                    Type
                </th>
                <th class="col-md-1">
                    <span class="line"></span>
                    Number
                </th>
                <th class="col-md-2 text-right">
                	<span class="line"></span>
                    Amount
                </th>
				<th class="col-md-2">
                    <span class="line"></span>
                    Notes
                </th>
                <th class="col-md-1">
                    <span class="line"></span>
                    User
                </th>
				<th class="col-md-1 text-right">
                    <span class="line"></span>
                    ID
                </th>
				<th class="col-md-1 text-right">
                    Action 
                </th>
            </tr>
        </thead>

        <tbody>
        	<?=buildRows($PAYMENTS);?>
        </tbody>
	</table>
</form>
</div><!-- pad-wrapper -->

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<script type="text/javascript">
	$(document).ready(function() {
		$(".edit_payment_row").click(function(e){
			e.preventDefault();

			var row = $(this).closest(".payment_row");

			row.find(".payment_details").addClass('hidden');
			row.find(".payment_input").removeClass('hidden');

			row.find("input").prop("disabled", false);
			row.find(".select2").prop("disabled", false);

			row.find(".edit_payment_row").hide();
			row.find(".payment_submit").removeClass("hidden");
		});
	});
</script>

</body>
</html>
