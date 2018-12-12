<?php	
	include_once $_SERVER['ROOT_DIR'].'/inc/dbconnect.php';

	function getRepairOrder($repair_item_id) {
		$ro_number;

		$query = "SELECT ro_number FROM repair_items WHERE id = ".(fres($repair_item_id)).";";
		$results = qedb($query);

		if (qnum($results)>0) {
			$results = qrow($results);
			$ro_number = $results['ro_number'];
		}

		return $ro_number;
	}

	function getInvoiceByOrder($order_number,$order_type,$partid=0,$price=0) {
		$invoice = '';

		$query = "SELECT i.invoice_no FROM invoices i, invoice_items ii ";
		$query .= "WHERE i.order_number = '".$order_number."' AND i.order_type = '".$order_type."' AND i.invoice_no = ii.invoice_no ";
		if ($partid) { $query .= "AND ii.item_id = '".$partid."' "; }
		if ($price) { $query .= "AND amount = '".$price."' "; }
		$query .= "; ";
		$result = qedb($query);
		if (qnum($result)) {
			$r2 = qrow($result);
			$invoice = $r2['invoice_no'];
		}

		return ($invoice);
	}

	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/keywords.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/filter.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/display_part.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/format_date.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/format_price.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getCompany.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getPart.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getContact.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/calcQuarters.php';

	if (empty($companyid)) {
		$companyid = setCompany();//uses $_REQUEST['companyid'] if passed in
	}
	if (! is_array($companyid)) {
		if ($companyid) { $companyid = array($companyid); }
		else { $companyid = array(); }
	}
	$company_csv = '';
	foreach ($companyid as $id) {
		if ($company_csv) { $company_csv .= ','; }
		$company_csv .= $id;
	}

	$start_date = format_date($today,'m/d/Y',array('d'=>-90));
	if (isset($_REQUEST['START_DATE'])) {// AND $_REQUEST['START_DATE']) {
		$start_date = $_REQUEST['START_DATE'];
	}
	$end_date = '';
	if (isset($_REQUEST['END_DATE']) AND $_REQUEST['END_DATE']) {
		$end_date = $_REQUEST['END_DATE'];
	}

	//Query items tables
	$types = array('Sale'=>'shipping','Repair'=>'shipping','Purchase'=>'receiving');
	$order_type = '';
	if (isset($_REQUEST['order_type']) AND ($_REQUEST['order_type']=='Sale' OR $_REQUEST['order_type']=='Repair' OR $_REQUEST['order_type']=='Purchase')) {
		$order_type = $_REQUEST['order_type'];
	}

	$itemList = array();
	foreach ($types as $type => $pg) {
		if ($order_type AND $order_type<>$type) { continue; }
		else if ($pg<>$PAGE) { continue; }
		$T = order_type($type);

		$query = "SELECT datetime, companyid, contactid, partid, ref_1, ref_1_label, ref_2, ref_2_label, ".$T['delivery_date']." delivery_date, ";
		$query .= "i.".$T['order']." order_number, ".$T['datetime']." created, order_type, tracking_no, ".($T['cust_ref'] ? $T['cust_ref'] : "''")." cust_ref, i.qty, i.price ";
		$query .= "FROM packages p, ".$T['items']." i, ".$T['orders']." o ";
		$query .= "WHERE order_type = '".$type."' ";
		$query .= "AND p.order_number = i.".$T['order']." AND o.".$T['order']." = p.order_number ";
		$query .= ($company_csv ? ' AND companyid IN (' .$company_csv. ')': '')." ".dFilter('created', $start_date, $end_date)." ";
		$query .= "AND status <> 'Void' ";
		$query .= "ORDER BY ".$T['datetime']." DESC;";
		$result = qedb($query);
		while ($row = qrow($result)) {
			$itemList[] = $row;
		}
	}
?>

<!------------------------------------------------------------------------------------------->
<!-------------------------------------- HEADER OUTPUT -------------------------------------->
<!------------------------------------------------------------------------------------------>
<!DOCTYPE html>
<html>
<head>
	<title><?=$TITLE;?></title>
	<?php
		//Standard headers included in the function
		include_once $_SERVER['ROOT_DIR'].'/inc/scripts.php';
	?>
	<style>
	</style>
</head>

	<?php include 'inc/navbar.php'; ?>

	<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px; max-height:60px;">
		<form class="form-inline" method="get" action="" enctype="multipart/form-data" id="filter_form">

			<div class="row" style="padding:8px" id="filterBar">
				<div class="col-md-4">
				    <div class="btn-group medium col-sm-6 remove-pad" data-toggle="buttons">
				        <button data-toggle="tooltip" data-placement="bottom" title="" data-original-title="<?=($PAGE == 'shipping' ? 'Sale' : 'Purchase');?>" class="btn btn-sm left filter_status btn-default" data-filter="<?=($PAGE == 'shipping' ? 'sale' : 'purchase');?>">
				        	<?=($PAGE == 'shipping' ? 'Sale' : 'Purchase');?>
				        </button>
<?php if ($PAGE=='shipping') { ?>
				        <button data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Repair" class="btn btn-sm middle filter_status btn-default" data-filter="repair">
				        	Repair
				        </button>
<?php } ?>
						<button data-toggle="tooltip" data-placement="bottom" title="" data-original-title="All" class="btn btn-sm right filter_status active btn-info" data-filter="all">
				        	All
				        </button>
				    </div>

					<div class="col-sm-3 remove-pad">
						<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY" data-hposition="right">
				            <input type="text" name="START_DATE" class="form-control input-sm" value="<?=$start_date;?>" style="min-width:50px;">
				            <span class="input-group-addon">
				                <span class="fa fa-calendar"></span>
				            </span>
				        </div>
					</div>
					<div class="col-sm-3 remove-pad">
						<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY" data-hposition="right">
				            <input type="text" name="END_DATE" class="form-control input-sm" value="<?=$end_date;?>" style="min-width:50px;">
				            <span class="input-group-addon">
				                <span class="fa fa-calendar"></span>
				            </span>
				    	</div>
					</div>
				</div>

				<div class="col-md-4 text-center remove-pad">
	            	<h2 class="minimal"><?=$TITLE;?></h2>
				</div>
				
				<div class="col-md-4">
					<div class="pull-right form-group" style="margin-bottom: 0;">
						<select name="companyid[]" id="companyid" class="company-selector">
							<option value="">- Select a Company -</option>
							<?php 
								foreach ($companyid as $id) {
									echo '<option value="'.$id.'" selected>'.getCompany($id).'</option>'.chr(10);
								}
//								echo '<option value="'.$companyid.'" selected>'.getCompany($companyid).'</option>'.chr(10); 
							?>
						</select>
						<input class="btn btn-primary btn-sm" type="submit" value="Go">
					</div>
				</div>

			</div>
		</form>
	</div>

	<div id="pad-wrapper">
		<div class="row">
			<table class="table heighthover heightstriped table-condensed p_table">
				<thead>
					<tr>
						<?=(empty($companyid) ? '<th class="col-sm-2">Customer</th>' : '<th class="col-sm-1">Contact</th>');?>
						<th class="col-sm-1 colm-sm-0-5">Order#</th>
						<th class="col-sm-1 colm-sm-0-5">Invoice</th>
						<th class="col-sm-1">Customer Ref</th>
						<th class="col-sm-1">Manf</th>
						<th class="col-sm-<?=(empty($companyid) ? '1' : '2');?>">Item</th>
						<th class="col-sm-2">Description</th>
						<th class="col-sm-1 colm-sm-0-5">Qty</th>
						<th class="col-sm-1 colm-sm-0-5">Price</th>
						<th class="col-sm-1 colm-sm-0-5">Due Date</th>
						<th class="col-sm-1 colm-sm-0-5">Queue Time</th>
						<th class="col-sm-1"><?=($PAGE == 'receiving' ? 'Received' : 'Shipped')?></th>
						<th class="col-sm-1">Tracking</th>
					</tr>
				</thead>
				<tbody>
					<?php 
						$lapses = array();
						foreach($itemList as $r): 

							$ref = '';
							if ($r['ref_1'] AND $r['ref_1_label'] == 'repair_item_id') {
								$ref = 'ref_1';
							} else if ($r['ref_2'] AND $r['ref_2_label'] == 'repair_item_id') {
								$ref = 'ref_2';
							}

							$order = $r['order_number'];
							$type = 'sale_item';
							$final_type = $r['order_type'];
							if ($r['order_type']=='Sale' AND $ref) {
								$T = order_type($r['order_type']);//$r[$ref.'_label']);

								$order = getRepairOrder($r[$ref]);
								$type = 'repair_item';
								$final_type = 'Repair';
							}
							//$order_det = $T['abbrev'] . "# " . $order . " <a href='/".$T['abbrev'].$order."'><i class='fa fa-arrow-right' aria-hidden='true'></i></a>";
							$order_det = $order.' <a href="/'.$T['abbrev'].$order.'" target="_ropen"><i class="fa fa-arrow-right" aria-hidden="true"></i></a>';

							$inv = '';
							$invoice = getInvoiceByOrder($order,$final_type,$r['partid'],$r['price']);
							if ($invoice) {
								$inv = $invoice.' <a href="invoice.php?invoice='.$invoice.'" target="_ropen"><i class="fa fa-file-pdf-o"></i></a>';
							}

							$lapse = '';
							$created = new DateTime($r['created']);
							$shipped = new DateTime($r['datetime']);
							$diff = $created->diff($shipped);
							if ($diff->d>0) { $lapse = $diff->d.' d, '; }
							$lapse .= $diff->h.':'.str_pad($diff->i,2,"0",STR_PAD_LEFT);
							$hm = $diff->h.'.'.(($diff->i/60)*100);
							$lapses[] = $hm;

							$company_info = '';
							if (empty($companyid)) {
								$company_info = '<td><a href="/profile.php?companyid='.$r['companyid'].'" target="_ropen"><i class="fa fa-building" aria-hidden="true"></i></a> '.
									getCompany($r['companyid']).'</td>';
							} else {
								$email = getContact($r['contactid'],'id','email');
								$company_info = '<td>'.($email ? '<a href="mailto:'.$email.'"><i class="fa fa-envelope"></i></a> ' : '').getContact($r['contactid']).'</td>';
							}

							$H = hecidb($r['partid'],'id')[$r['partid']];
					?>
						<tr class="<?=$type;?> filter_item valign-top">
							<?=$company_info;?>
							<td><?=$order_det;?></td>
							<td><?=$inv;?></td>
							<td class=""><?=$r['cust_ref'];?></td>
							<td><?=$H['manf'];?></td>
							<td><?=$H['primary_part'].' '.$H['heci7'];?></td>
							<td><?=$H['description'];?></td>
							<td><?=$r['qty'];?></td>
							<td class="text-right" style="padding-right:20px">$<?=number_format($r['price'],2,'.',',');?></td>
							<td class=""><?= format_date($r['delivery_date']); ?></td>
							<td><?=$lapse;?></td>
							<td><span class="<?=((($r['datetime'] <= $r['delivery_date'])) ?'alert-success':'alert-danger');?>"><?= format_date($r['datetime']); ?></span></td>
							<td style="overflow-x: hidden; max-width: 400px;">
								<?php if($r['tracking_no']) {
									echo $r['tracking_no'] . ' <a href="'.$PAGE.'.php?order_type='.$T['type'].'&order_number='.$order.'" target="_ropen"><i class="fa fa-arrow-right" aria-hidden="true"></i></a>';

								} ?>
							</td>
						</tr>
					<?php endforeach; ?>

					<tr>
						<td> </td>
						<td> </td>
						<td> </td>
						<td> </td>
						<td> </td>
						<td> </td>
						<td> </td>
						<td>
							<?php
								$hm = array_sum($lapses)/count($lapses);
								$h = floor($hm);
								$rem = ($hm-$h);
								$m = round($rem*60);

								echo $h.':'.str_pad($m,2,"0",STR_PAD_LEFT);
							?>
						</td>
					</tr>
				</tbody>
	        </table>
		</div>
	</div>

	<?php include_once 'inc/footer.php'; ?>

    <script type="text/javascript">

    	(function($){

    		$(document).on("click onload", ".filter_status", function(e){
    			var type = $(this).data('filter');
				$('.filter_item').hide();
				$('.filter_status').removeClass('active');
				$('.filter_status').removeClass('btn-warning');
				$('.filter_status').removeClass('btn-success');
				$('.filter_status').removeClass('btn-info');
				$('.filter_status').removeClass('btn-flat info');
				$('.filter_status').removeClass('btn-flat gray');
				$('.filter_status').removeClass('btn-flat danger');
				$('.filter_status').removeClass('btn-danger');
				$('.filter_status').addClass('btn-default');

				var btn,type2;
				if (type=='repair') {
					btn = 'success';
					type2 = type;
					$('.repair_item').hide();
				} else if (type=='sale') {
					btn = 'warning';
					type2 = type;
					$('.sale_item').hide();
				} else {
					type = 'all';
					type2 = 'filter';
					btn = 'info';
				}

				$('.filter_status[data-filter="'+type+'"]').addClass('btn-'+btn);
				$('.'+type2+'_item').show();
    		});

		})(jQuery);
    </script>

</body>
</html>
