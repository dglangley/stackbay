<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getUser.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPart.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_part.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/display_part.php';

	$EDIT = true;

	include_once $_SERVER["ROOT_DIR"].'/inc/buildDescrCol.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setInputSearch.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getItems.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/detectDefaultType.php';

	$template_no =  isset($_REQUEST['template_no']) ? $_REQUEST['template_no'] : '';

	function partDescription($partid, $desc = true){
		$r = reset(hecidb($partid, 'id'));
		$parts = explode(' ',$r['part']);

	    $display = "<span class = 'descr-label'>".$parts[0]." &nbsp; ".$r['heci']."</span>";
	    if($desc)
    		$display .= '<div class="description desc_second_line descr-label" style = "color:#aaa;">'.dictionary($r['manf']).' &nbsp; '.dictionary($r['system']).
				'</span> <span class="description-label">'.dictionary($r['description']).'</span></div>';

	    return $display;
	}

	function buildHeader() {
		$headerHTML .= '
			<th class="col-md-3">Material</th>
			<th class="col-md-2">Qty & Cost (ea)</th>
			<th class="col-md-1">Sourcing</th>
			<th class="col-md-3">Leadtime & Due Date</th>
			<th>Markup</th>
			<th>Quoted Total</th>
			<th class="text-right">
				Action
			</th>
        ';  

        return $headerHTML;
	}

	function getTemplate($template_no) {
		$templates = array();

		$query = "SELECT * FROM templates t, template_items i WHERE t.template_no = i.template_no AND t.template_no = ".res($template_no)." ORDER BY created DESC;"; // , template_items i WHERE t.template_no = i.template_no
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$templates[] = $r;
		}

		return $templates;
	}

	function buildRows($templates) {
		$htmlRows = '';
		$id = false;

		// Build the first row here to accept new parts
		$htmlRows .= '<tr class="bom">
							<td clas="part-container">
								<div class="remove-pad col-md-1">
									<div class="product-img">
										<img class="img" src="/img/parts/'.$fpart.'.jpg" alt="pic" data-part="'.$fpart.'">
									</div>
								</div>
								<div class="col-md-11">
									<div class="row remove-pad">
										'.buildDescrCol($P,$template['id'],'Part',$items, true, true).'
									</div>
									<div class="row remove-pad">
										<span class="descr-label part_description">'.display_part($partid, true, true, false).'</span>
									</div>
								</div>
							</td>
							<td>
							<div class="col-md-4 remove-pad" style="padding-right: 5px;">
									<input class="form-control input-sm part_qty" type="text" name="materials[0][qty]" data-partid="'.$partid.'" placeholder="QTY" value="'.$template['qty'].'">
								</div>
								<div class="col-md-8 remove-pad">
									<div class="form-group" style="margin-bottom: 0;">
										<div class="input-group">
											<span class="input-group-addon">
												<i class="fa fa-usd" aria-hidden="true"></i>
											</span>
											<input class="form-control input-sm part_amount" type="text" name="materials[0][amount]" placeholder="0.00" value="'.number_format((float)$template['amount'], 2, '.', '').'">
										</div>
									</div>
								</div>
							</td>';
			$htmlRows .= '	<td style="background: #FFF;">
								<div class="table market-table" data-partids="'.$partid.'">
									<div class="bg-availability">
										<a href="javascript:void(0);" class="market-title modal-results" data-target="marketModal" data-title="Supply Results" data-type="supply">
											Supply <i class="fa fa-window-restore"></i>
										</a>
										<a href="javascript:void(0);" class="market-download" data-toggle="tooltip" data-placement="top" title="" data-original-title="force re-download">
											<i class="fa fa-download"></i>
										</a>
										<div class="market-results" id="'.$partid.'" data-ln="0" data-type="supply">
										</div>
									</div>
								</div>
							</td>';
			$htmlRows .= '	<td>
								<div class="col-md-2 remove-pad">											
									<input class="form-control input-sm" type="text" name="materials[0][leadtime]" data-stock="2" placeholder="#" value="'.$template['leadtime'].'">
								</div>
								<div class="col-md-4">
									<select class="form-control input-sm">
										<option value="days" '.($template['leadtime_span'] == 'Days' ? 'selected' : '').'>Days</option>
										<option value="weeks" '.($template['leadtime_span'] == 'Weeks' ? 'selected' : '').'>Weeks</option>
										<option value="months" '.($template['leadtime_span'] == 'Months' ? 'selected' : '').'>Months</option>
									</select>
								</div>										
								<div class="col-md-6 remove-pad">											
									<div class="form-group" style="margin-bottom: 0; width: 100%;">												
										<div class="input-group datepicker-date date datetime-picker" style="min-width: 100%; width: 100%;" data-format="MM/DD/YYYY">										            
											<input type="text" name="materials[0][delivery_date]" class="form-control input-sm" value="">										            
											<span class="input-group-addon">										                
												<span class="fa fa-calendar"></span>										            
											</span>										        
										</div>											
									</div>										
								</div>
							</td>
							<td>
								<div class="form-group" style="margin-bottom: 0;">
									<div class="input-group">
										<input type="text" class="form-control input-sm part_perc" name="materials[0][profit_pct]" value="'.number_format((float)$template['profit_pct'], 2, '.', '').'" placeholder="0">
										<span class="input-group-addon">
											<i class="fa fa-percent" aria-hidden="true"></i>
										</span>
									</div>
								</div>
							</td>';
			$htmlRows .= '	<td>
								<div class="form-group">										
									<div class="input-group">											
										<span class="input-group-addon">								                
											<i class="fa fa-usd" aria-hidden="true"></i>								           
										</span>								            
										<input type="text" placeholder="0.00" class="form-control input-sm total_amount" name="materials[0][charge]" value="'.number_format((float)$template['charge'], 2, '.', '').'">								        
									</div>									
								</div>
							</td>';
			$htmlRows .= '	<td class="text-right">
								
							</td>
						</tr>';

		if(! empty($templates))
		foreach($templates as $template) {
			$P = array();

			$partid = $template['partid'];
			$primary_part = getPart($partid,'part');
			$fpart = format_part($primary_part);

			$H = hecidb($partid,'id');
			$P = $H[$partid];
			$def_type = 'Part';

			$htmlRows .= '<tr class="bom">
							<td clas="part-container">
								<div class="remove-pad col-md-1">
									<div class="product-img">
										<img class="img" src="/img/parts/'.$fpart.'.jpg" alt="pic" data-part="'.$fpart.'">
									</div>
								</div>
								<div class="col-md-11">
									<div class="row remove-pad">
										'.buildDescrCol($P,$template['id'],'Part',$items, true, true).'
									</div>
									<div class="row remove-pad">
										<span class="descr-label part_description">'.display_part($partid, true, true, false).'</span>
									</div>
								</div>
							</td>
							<td>
							<div class="col-md-4 remove-pad" style="padding-right: 5px;">
									<input class="form-control input-sm part_qty" type="text" name="materials['.$template['id'].'][qty]" data-partid="'.$partid.'" placeholder="QTY" value="'.$template['qty'].'">
								</div>
								<div class="col-md-8 remove-pad">
									<div class="form-group" style="margin-bottom: 0;">
										<div class="input-group">
											<span class="input-group-addon">
												<i class="fa fa-usd" aria-hidden="true"></i>
											</span>
											<input class="form-control input-sm part_amount" type="text" name="materials['.$template['id'].'][amount]" placeholder="0.00" value="'.number_format((float)$template['amount'], 2, '.', '').'">
										</div>
									</div>
								</div>
							</td>';
			$htmlRows .= '	<td style="background: #FFF;">
								<div class="table market-table" data-partids="'.$partid.'">
									<div class="bg-availability">
										<a href="javascript:void(0);" class="market-title modal-results" data-target="marketModal" data-title="Supply Results" data-type="supply">
											Supply <i class="fa fa-window-restore"></i>
										</a>
										<a href="javascript:void(0);" class="market-download" data-toggle="tooltip" data-placement="top" title="" data-original-title="force re-download">
											<i class="fa fa-download"></i>
										</a>
										<div class="market-results" id="'.$partid.'" data-ln="0" data-type="supply">
										</div>
									</div>
								</div>
							</td>';
			$htmlRows .= '	<td>
								<div class="col-md-2 remove-pad">											
									<input class="form-control input-sm" type="text" name="materials['.$template['id'].'][leadtime]" data-stock="2" placeholder="#" value="'.$template['leadtime'].'">
								</div>
								<div class="col-md-4">
									<select class="form-control input-sm">
										<option value="days" '.($template['leadtime_span'] == 'Days' ? 'selected' : '').'>Days</option>
										<option value="weeks" '.($template['leadtime_span'] == 'Weeks' ? 'selected' : '').'>Weeks</option>
										<option value="months" '.($template['leadtime_span'] == 'Months' ? 'selected' : '').'>Months</option>
									</select>
								</div>										
								<div class="col-md-6 remove-pad">											
									<div class="form-group" style="margin-bottom: 0; width: 100%;">												
										<div class="input-group datepicker-date date datetime-picker" style="min-width: 100%; width: 100%;" data-format="MM/DD/YYYY">										            
											<input type="text" name="materials['.$template['id'].'][delivery_date]" class="form-control input-sm" value="">										            
											<span class="input-group-addon">										                
												<span class="fa fa-calendar"></span>										            
											</span>										        
										</div>											
									</div>										
								</div>
							</td>
							<td>
								<div class="form-group" style="margin-bottom: 0;">
									<div class="input-group">
										<input type="text" class="form-control input-sm part_perc" name="materials['.$template['id'].'][profit_pct]" value="'.number_format((float)$template['profit_pct'], 2, '.', '').'" placeholder="0">
										<span class="input-group-addon">
											<i class="fa fa-percent" aria-hidden="true"></i>
										</span>
									</div>
								</div>
							</td>';
			$htmlRows .= '	<td>
								<div class="form-group">										
									<div class="input-group">											
										<span class="input-group-addon">								                
											<i class="fa fa-usd" aria-hidden="true"></i>								           
										</span>								            
										<input type="text" placeholder="0.00" class="form-control input-sm total_amount" name="materials['.$template['id'].'][charge]" value="'.number_format((float)$template['charge'], 2, '.', '').'">								        
									</div>									
								</div>
							</td>';
			$htmlRows .= '	<td class="text-right">
								<a class="deleteTemplate" data-templateid="'.$template['id'].'" href="#" title="Delete" data-toggle="tooltip" data-placement="bottom"><i style="margin-right: 5px;" class="fa fa-trash" aria-hidden="true"></i></a>
							</td>
						</tr>';
		}

		return $htmlRows;
	}

	$templates = getTemplate($template_no);

	$TITLE = reset($templates)['name'];
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo $TITLE; ?></title>
	<?php
		/*** includes all required css includes ***/
		include_once 'inc/scripts.php';
		include_once $_SERVER["ROOT_DIR"].'/modal/image.php';
	?>

	<!-- any page-specific customizations -->
	<style type="text/css">
		.remove-pad {
			padding: 0;
			margin: 0;
		}

		form .form-group {
			margin-bottom: 0;
		}

		.materials_table td {
		    max-height: 100px;
		    height: 100px;
		    overflow: hidden;
			vertical-align: top !important;
		}

		.market-table {
			max-height: 82px;
			min-height: 82px;
			margin-bottom: 0;
			overflow: hidden;
			position: relative;
			font-size: 11px;
		}

		.market-table:hover {
		    overflow: visible;
		    position: initial;
		}

		.dropdown-searchtype {
			display: none;
		}

	</style>
</head>
<body data-scope="Materials">

<?php include_once 'inc/navbar.php'; ?>

<!-- FILTER BAR -->
<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px; max-height:60px;">
	<form class="form-inline" method="get" action="" enctype="multipart/form-data" id="filters-form" >

	<div class="row" style="padding:8px">
		<div class="col-sm-1">
		</div>
		<div class="col-sm-1">
		</div>
		<div class="col-sm-2">
		</div>
		<div class="col-sm-4 text-center">
			<h2 class="minimal"><?php echo $TITLE; ?></h2>
			<span class="info"></span>
		</div>
		<div class="col-sm-2">
		</div>
		<div class="col-sm-2">
			<button class="btn btn-success btn-sm pull-right save_template" type="submit" style="margin-right: 10px;">
				Save
			</button>
		</div>
	</div>

	</form>
</div>

<div id="pad-wrapper">
<form id="template_form" class="form-inline" method="get" action="/service_template_edit.php" enctype="multipart/form-data" >
	<input type="hidden" name="template_no" value="<?=$template_no;?>">
	<input type="hidden" name="type" value="template_items">
	<table class="table table-hover table-striped table-condensed materials_table">
		<thead>
            <tr>
                <?=buildHeader();?>
            </tr>
        </thead>

        <tbody>
        	<?=buildRows($templates)?>
        </tbody>
	</table>
</form>
</div><!-- pad-wrapper -->

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<script type="text/javascript" src="js/part_search.js"></script>
<script src="js/item_search.js?id=<?php echo $V; ?>"></script>

<script type="text/javascript">
	function deleteTemplate(templateid) {
		var input = $("<input>").attr("type", "hidden").attr("name", "template_delete").val(templateid);

		$("#template_form").append(input);
		$("#template_form").submit();
	}

	function calculateCost(object){
		var container = object.closest(".bom");

		var qty = 0;
		var amount = 0;
		var tax = 0;

		var quote = 0;

		if(container.find(".part_qty").val()) {
			qty = parseFloat(container.find(".part_qty").val());	
		}

		if(container.find(".part_amount").val()) {
			amount = parseFloat(container.find(".part_amount").val());	
		}

		if(container.find(".part_perc").val()) {
			tax = parseFloat(container.find(".part_perc").val());	
		}

		quote = (qty * amount) + (qty * amount * (tax / 100));

		container.find(".total_amount").val(quote.toFixed(2));
	}

	$(document).ready(function() {
		$(".deleteTemplate").click(function(e){
			e.preventDefault();

			modalAlertShow('<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Warning','Please Confirm You Want to Delete this Record.',true,'deleteTemplate', $(this).data("templateid"));
		});

		$(".save_template").click(function(e) {
			e.preventDefault();

			$("#template_form").submit();
		});

		$(document).on("change", ".part_amount, .part_qty, .part_perc", function(){
			calculateCost($(this));

			var total = 0;

			$('.total_amount').each(function(){
				if($(this).val()) {
					total += parseFloat($(this).val());
				}
			});
		});

		$(document).on("change", ".total_amount", function(){
			calculateTax($(this));

			var total = 0;

			$('.total_amount').each(function(){
				if($(this).val()) {
					total += parseFloat($(this).val());
				}
			});
		});
	});
</script>

</body>
</html>
