<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';

	$payment_filter =  isset($_REQUEST['keyword']) ? $_REQUEST['keyword'] : '';
	// $types =  isset($_REQUEST['order_type']) ? $_REQUEST['order_type'] : 'Sale';
	$startDate = isset($_REQUEST['START_DATE']) ? $_REQUEST['START_DATE'] : format_date($GLOBALS['now'],'m/d/Y',array('d'=>-60));
	$endDate = (isset($_REQUEST['END_DATE']) AND ! empty($_REQUEST['END_DATE'])) ? $_REQUEST['END_DATE'] : format_date($GLOBALS['now'],'m/d/Y');
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
			$html_rows .= '<tr>';
			$html_rows .= '	<td>'.format_date($details['date']).'</td>';

			if(!$company_filter) {
				$html_rows .= '	<td>'.getCompany($details['companyid']).'</td>';
			}

			$html_rows .= '	<td>'.$details['id'].'</td>';
			$html_rows .= '	<td>'.$details['payment_type'].'</td>';
			$html_rows .= '	<td>'.$details['number'].'</td>';
			if($details['payment_type'] == 'Check') {
				$html_rows .= '	<td class="text-right">'.format_price($details['amount']).' <a target="_blank" href="/docs/Payment'.$details['id'].'.pdf"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></a></td>';
			} else {
				$html_rows .= '	<td></td>';
			}
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
				<!-- <div class="btn-group">
			        <button class="glow left large btn-radio <?=($master_report_type == 'summary' ? 'active':'')?>" type="submit" data-value="summary" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="summary">
			        	<i class="fa fa-ticket"></i>	
			        </button>
					<input type="radio" name="report_type" value="summary" class="hidden" <?=($master_report_type == 'summary' ? 'checked':'')?>>

			        <button class="glow right large btn-radio <?=($master_report_type == 'detail' ? 'active':'')?>" type="submit" data-value="detail" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="details">
			        	<i class="fa fa-list"></i>	
		        	</button>
					<input type="radio" name="report_type" value="detail" class="hidden" <?=($master_report_type == 'detail' ? 'checked':'')?>>
			    </div> -->
			</div>
			<div class="col-sm-1">
			</div>
			<div class="col-sm-3">
				<div class="form-group">
					<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY">
			            <input type="text" name="START_DATE" class="form-control input-sm" value="<?=$startDate?>">
			            <span class="input-group-addon">
			                <span class="fa fa-calendar"></span>
			            </span>
			        </div>
				</div>
				<div class="form-group">
					<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY" data-maxdate="">
			            <input type="text" name="END_DATE" class="form-control input-sm" value="<?=$endDate?>">
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
				<!-- <button data-toggle="tooltip" name="view" value="print" data-placement="bottom" title="" data-original-title="Print View" class="btn btn-default btn-sm filter-types pull-right">
			        <i class="fa fa-print" aria-hidden="true"></i>
		        </button> -->
			</div>
		</div>
	</form>
</div>

<div id="pad-wrapper">
<form class="form-inline" method="get" action="" enctype="multipart/form-data" >
	<table class="table table-hover table-striped table-condensed">
		<thead>
            <tr>
                <th class="col-md-2">
                    Date 
                </th>

                <?php if(! $company_filter) { 
	                echo '<th class="col-md-2">
	                    <span class="line"></span>
	                    Company
	                </th>';
	            } ?>

	            <th class="col-md-2">
                    <span class="line"></span>
                    Payment
                </th>

                <th class="col-md-2">
                    <span class="line"></span>
                    Type
                </th>
                <th class="col-md-2">
                    <span class="line"></span>
                    Number
                </th>
                <th class="col-md-2 text-right">
                	<span class="line"></span>
                    Amount
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
	});
</script>

</body>
</html>
