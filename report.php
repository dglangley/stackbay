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
	include_once $_SERVER['ROOT_DIR'].'/inc/datepickers.php';

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

	$startDate = format_date($today,'m/d/Y',array('d'=>-90));
	if (isset($_REQUEST['START_DATE'])) {// AND $_REQUEST['START_DATE']) {
		$startDate = $_REQUEST['START_DATE'];
	}
	$endDate = '';
	if (isset($_REQUEST['END_DATE']) AND $_REQUEST['END_DATE']) {
		$endDate = $_REQUEST['END_DATE'];
	}

	$detail = 'Package';
	if (isset($_REQUEST['detail']) AND ($_REQUEST['detail']=='Order' OR $_REQUEST['detail']=='Package')) {
		$detail = $_REQUEST['detail'];
	}

	$package_checked = ' checked';
	$order_checked = '';
	$detail_cls = 'on';
	$slider_cls = 'info';
	if ($detail=='Order') {
		$package_checked = '';
		$order_checked = ' checked';
		$detail_cls = 'off';
		$slider_cls = 'default';
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

		$query = "SELECT companyid, contactid, partid, ref_1, ref_1_label, ref_2, ref_2_label, ".$T['delivery_date']." delivery_date, ";
		$query .= "i.".$T['order']." order_number, ".$T['datetime']." created, '".$type."' order_type, ".($T['cust_ref'] ? $T['cust_ref'] : "''")." cust_ref, i.qty, i.price, ";
		if ($detail=='Package') { $query .= "datetime, tracking_no "; } else { $query .= "'' datetime, '' tracking_no "; }

		$query .= "FROM ".$T['items']." i, ".$T['orders']." o ";
		if ($detail=='Package') { $query .= ", packages p "; }

		$query .= "WHERE status <> 'Void' AND i.".$T['order']." = o.".$T['order']." ";
		if ($detail=='Package') {
			$query .= "AND p.order_number = i.".$T['order']." AND o.".$T['order']." = p.order_number AND order_type = '".$type."' ";
		}
		$query .= ($company_csv ? ' AND companyid IN (' .$company_csv. ')': '')." ".dFilter('created', $startDate, $endDate)." ";
		$query .= "ORDER BY o.".$T['datetime']." DESC;";
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
		.table-header .slider-frame {
			width:85px;
		}
		.table-header .slider-button {
			width:60px;
		}
	</style>
</head>

	<?php include 'inc/navbar.php'; ?>

	<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px; max-height:60px;">
		<form class="form-inline" method="get" action="" enctype="multipart/form-data" id="filter_form">

			<div class="row" style="padding:8px">
				<div class="col-md-2">
				    <div class="btn-group remove-pad" data-toggle="buttons">
				        <button data-toggle="tooltip" data-placement="bottom" title="" data-original-title="<?=($PAGE == 'shipping' ? 'Sale' : 'Purchase');?>" class="btn btn-sm left filter_status btn-default" data-filter="<?=($PAGE == 'shipping' ? 'sale' : 'purchase');?>">
				        	<?=($PAGE == 'shipping' ? 'Sale' : 'Purchase');?>
				        </button>
<?php if ($PAGE=='shipping') { ?>
				        <button data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Repair" class="btn btn-sm middle filter_status btn-default" data-filter="repair">
				        	Repair
				        </button>
<?php } ?>
						<button data-toggle="tooltip" data-placement="bottom" title="" data-original-title="All" class="btn btn-sm btn-default right filter_status active" data-filter="all">
				        	All
				        </button>
				    </div>

			    </div>
				<div class="col-md-3">
					<?= datepickers($startDate,$endDate); ?>
				</div>

				<div class="col-md-2 text-center remove-pad">
	            	<h2 class="minimal"><?=$TITLE;?></h2>
				</div>
				
				<div class="col-md-2 text-center">
					<div class="slider-frame <?=$slider_cls;?>" data-onclass="info" data-offclass="default">
						<span data-on-text="Package" data-off-text="Order" class="slider-button <?=$detail_cls;?>"><?=$detail;?></span>
						<input class="hidden" value="Package" type="radio" name="detail" <?=$package_checked;?>>
						<input class="hidden" value="Order" type="radio" name="detail" <?=$order_checked;?>>
					</div><br/>
					<span class="info">Detail level</span>
				</div>
				<div class="col-md-3">
					<div class="pull-right form-group" style="margin-bottom: 0;">
						<select name="companyid[]" id="companyid" class="company-selector" multiple>
							<option value="">- Select a Company -</option>
							<?php 
								foreach ($companyid as $id) {
									echo '<option value="'.$id.'" selected>'.getCompany($id).'</option>'.chr(10);
								}
//								echo '<option value="'.$companyid.'" selected>'.getCompany($companyid).'</option>'.chr(10); 
							?>
						</select>
						<button class="btn btn-primary btn-sm" type="submit" ><i class="fa fa-filter" aria-hidden="true"></i></button>
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
						<?php if ($detail=='Package') { ?>
							<th class="col-sm-1 colm-sm-0-5">Queue Time</th>
							<th class="col-sm-1"><?=($PAGE == 'receiving' ? 'Received' : 'Shipped')?></th>
							<th class="col-sm-1">Tracking</th>
						<?php } ?>
					</tr>
				</thead>
				<tbody>
					<?php 
						$lapses = array();
						foreach($itemList as $r): 

							$company_info = '';
							if (empty($companyid)) {
								$company_info = '<td><a href="/profile.php?companyid='.$r['companyid'].'" target="_ropen"><i class="fa fa-building" aria-hidden="true"></i></a> '.
									getCompany($r['companyid']).'</td>';
							} else {
								$email = getContact($r['contactid'],'id','email');
								$company_info = '<td>'.($email ? '<a href="mailto:'.$email.'"><i class="fa fa-envelope"></i></a> ' : '').getContact($r['contactid']).'</td>';
							}

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
							if ($detail=='Package') {
								$created = new DateTime($r['created']);
								$shipped = new DateTime($r['datetime']);
								$diff = $created->diff($shipped);
								if ($diff->d>0) { $lapse = $diff->d.' d, '; }
								$lapse .= $diff->h.':'.str_pad($diff->i,2,"0",STR_PAD_LEFT);
								$hm = $diff->h.'.'.(($diff->i/60)*100);
								$lapses[] = $hm;
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
							<?php if ($detail=='Package') { ?>
								<td><?=$lapse;?></td>
								<td><span class="<?=((($r['datetime'] <= $r['delivery_date'])) ?'alert-success':'alert-danger');?>"><?= format_date($r['datetime']); ?></span></td>
								<td style="white-space:nowrap">
									<?=($r['tracking_no'] ? '<a href="'.$PAGE.'.php?order_type='.$T['type'].'&order_number='.$order.'" target="_ropen" class="pull-right"><i class="fa fa-arrow-right" aria-hidden="true"></i></a>'.$r['tracking_no'] : '');?>
								</td>
							<?php } ?>
						</tr>
					<?php endforeach; ?>

					<tr class="info">
						<td> </td>
						<td> </td>
						<td> </td>
						<td> </td>
						<td> </td>
						<td> </td>
						<td> </td>
						<td> </td>
						<td> </td>
						<td> </td>
						<?php if ($detail=='Package') { ?>
							<td>
								<?php
									$hm = array_sum($lapses)/count($lapses);
									$h = floor($hm);
									$rem = ($hm-$h);
									$m = round($rem*60);
	
									echo $h.':'.str_pad($m,2,"0",STR_PAD_LEFT);
								?>
							</td>
							<td> </td>
							<td> </td>
						<?php } ?>
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
					btn = 'info';
					type2 = type;
					$('.repair_item').hide();
				} else if (type=='sale') {
					btn = 'success';
					type2 = type;
					$('.sale_item').hide();
				} else if (type=='purchase') {
					btn = 'warning';
					type2 = type;
					$('.purchase_item').hide();
				} else {
					btn = 'default';
					type = 'all';
					type2 = 'filter';
				}

				$('.filter_status[data-filter="'+type+'"]').addClass('btn-'+btn);
				$('.'+type2+'_item').show();

    		});
			$(document).on('click','.slider-button',function() {
				var on = $(this).hasClass('on');
				var toggle = 'on';
				if (on===false) { toggle = 'off'; }

				var sel = $(this).data(toggle+'-text');
				var frame = $(this).closest('.slider-frame');
				frame.find('input[type=radio]').each(function() {
					if ($(this).val()==sel) { $(this).prop('checked',true); }
					else { $(this).prop('checked',false); }
				});

				$('#loader-message').html('Please wait while your results are loaded...');
				$('#loader').show();
				$(this).closest("form").submit();
			});

		})(jQuery);
    </script>

</body>
</html>
