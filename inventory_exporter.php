<?php
	/************************************************** IMPORTANT *************************************************/
	/****** SET /etc/php.ini `max_input_vars` to something really high (ie, 3000?) or this won't process all submitted rows! ******/

	$rootdir = $_SERVER['ROOT_DIR'];
	
	require_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/getShelflife.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/dropPop.php';
	include_once $rootdir.'/inc/display_part.php';
	include_once $rootdir.'/inc/locations.php';
    $table_rows = '';

	$search = '';
	if (isset($_REQUEST['s'])) { $search = trim($_REQUEST['s']); }
	else if (isset($_REQUEST['s2'])) { $search = trim($_REQUEST['s2']); }

	$partids = array();
	if (isset($_REQUEST['partids'])) { $partids = $_REQUEST['partids']; }
	$hidden_qtys = array();
	if (isset($_REQUEST['hidden_qtys'])) { $hidden_qtys = $_REQUEST['hidden_qtys']; }
	$stock_qtys = array();
	if (isset($_REQUEST['stock_qtys'])) { $stock_qtys = $_REQUEST['stock_qtys']; }
	$shelflife_min = false;
	if (isset($_REQUEST['shelflife_min']) AND trim($_REQUEST['shelflife_min'])<>'') { $shelflife_min = trim($_REQUEST['shelflife_min']); }
	$shelflife_max = false;
	if (isset($_REQUEST['shelflife_max']) AND trim($_REQUEST['shelflife_max'])<>'') { $shelflife_max = trim($_REQUEST['shelflife_max']); }
	$search_str = '';

	$equip_class = ' btn-default';
	$equip_checked = '';
	$equipment = 0;
	$comp_class = ' btn-default';
	$comp_checked = '';
	$components = 0;
	if ((isset($_REQUEST['equipment_class']) AND $_REQUEST['equipment_class']) OR ! isset($_REQUEST['component_class'])) {
		$equip_class = ' btn-primary active';
		$equip_checked = ' checked';
		$equipment = 1;
	}
	if (isset($_REQUEST['component_class']) AND $_REQUEST['component_class']) {
		$comp_class = ' btn-primary active';
		$comp_checked = ' checked';
		$components = 1;
	}

	foreach ($partids as $i => $partid) {
		$hqty = trim($hidden_qtys[$i]);
		if ($hqty=='' OR ! is_numeric($hqty)) { $hqty = 'NULL'; }
		$hq_int = $hidden_qtys[$i];//used for calcs below
		if (! $hq_int) { $hq_int = 0; }

		$visible = 0;
		$visible = $stock_qtys[$i] - $hq_int;
		if($visible < 0 || $hidden_qtys[$i] === 0){
			$visible = 0;
		}

		$query = "REPLACE `qtys` (`partid`,`qty`, `hidden_qty`, `visible_qty`) VALUES (".fres($partid).", '".res($stock_qtys[$i])."', ".$hqty.", ".fres($visible).");";
		qdb($query) or die(qe()."<BR>".$query);
    }

	$partid_csv = '';
	if ($search) {
		$H = hecidb($search);

		$partid_csv = '';
		foreach ($H as $partid => $r) {
			if ($partid_csv) { $partid_csv .= ","; }
			$partid_csv .= $partid;
		}
	}

    $query = "SELECT q.*, p.part, p.heci FROM qtys q, parts p ";
	$query .= "WHERE q.partid = p.id AND q.qty > 0 ";
	if ($partid_csv) { $query .= "AND q.partid IN (".$partid_csv.") "; }
	if (! $search AND ($equipment OR $components)) {
		$query .= "AND (";
		if ($equipment) { $query .= "p.classification = 'equipment' "; }
		if ($equipment AND $components) { $query .= "OR "; }
		if ($components) { $query .= "p.classification = 'component' "; }
		$query .= ") ";
	}
	$query .= "ORDER BY part ASC, heci ASC; ";
    $result = qdb($query) or die(qe()." ".$query);
    foreach($result as $r){
		$shelflife = getShelflife($r['partid'],true);
		if (($shelflife_min!==false AND $shelflife<$shelflife_min) OR ($shelflife_max!==false AND $shelflife>$shelflife_max)) { continue; }

		$shelflife_str = $shelflife.' day';
		if ($shelflife<>1) { $shelflife_str .= 's'; }

        $table_rows .= '
        <tr>
            <td>'.display_part($r["partid"],true).'</td>
			<td>'.$shelflife_str.'</td>
            <td class="qty_form qty_form_stock">
				<input type="hidden" name="partids[]" value="'.$r["partid"].'">
				<input type="text" name="stock_qtys[]" class="form-control input-sm" placeholder="" readonly value="'.$r["qty"].'">
			</td>
            <td class="qty_form qty_form_hidden">
				<input type="text" name="hidden_qtys[]" class="form-control input-sm" placeholder="" value="'.$r["hidden_qty"].'">
			</td>
            <td class="qty_form qty_form_visible">
				<input type="text" name="visible_qtys[]" class="form-control input-sm" placeholder="" readonly value="'.$r["visible_qty"].'">
			</td>
        </tr>
        ';

		if ($r['heci']) {
			$search_str .= $r['heci'].chr(10);
		} else {
			$part_strs = explode(' ',$r['part']);
			$search_str .= $part_strs[0].chr(10);
		}
    }
?>
<!DOCTYPE html>
<html>
<head>
	<title>Inventory Exporter</title>
	<?php
		//Standard headers included in the function
		include_once $rootdir.'/inc/scripts.php';
	?>
	<style type="text/css">
		.table-striped > tbody > tr:nth-child(odd) > td {
			background-color: #ddd;
		}
		.form-inline .form-group .form-control {
			margin-left:0px;
			margin-right:0px;
		}
	</style>
</head>
<body>

<?php
	include_once 'inc/navbar.php';
?>

<!----------------------------------------------------------------------------->
<!-------------------------- Header/Filter Bar/Title -------------------------->
<!---------------------------------------------------------------------------->
<form class="form-inline form-filter" method="POST" action="/manage_inventory.php">
<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px; max-height:60px;">
	<div class="row" style="padding:8px">
		<div class="col-sm-3">
	    	<button class="btn btn-danger btn-sm btn-export" type="button"><i class="fa fa-share-square-o"></i> Export</button>
<?php if (in_array("1",$USER_ROLES) OR in_array("5",$USER_ROLES) OR in_array("4",$USER_ROLES)) { ?>
	    	<button class="btn btn-primary btn-sm btn-sales" type="button"><i class="fa fa-cubes"></i> Open in Sales</button>
<?php } ?>
	    	<button class="btn btn-default btn-sm btn-download" type="button"><i class="fa fa-download"></i> Download</button>
		</div>
		<div class="col-sm-2">
		    <div class="filter-group" style="display:inline-block">
				<label class="btn btn-xs btn-toggle<?=$equip_class;?>" type="button" for="equipment_class" data-toggle="tooltip" data-placement="right" title="Equipment"><i class="fa fa-cube"></i></label><br/>
				<input type="checkbox" name="equipment_class" id="equipment_class" value="1" class="checkbox-submit hidden"<?=$equip_checked;?>>
				<label class="btn btn-xs btn-toggle<?=$comp_class;?>" type="button" for="component_class" data-toggle="tooltip" data-placement="right" title="Components"><i class="fa fa-microchip"></i></label>
				<input type="checkbox" name="component_class" id="component_class" value="1" class="checkbox-submit hidden"<?=$comp_checked;?>>
			</div>
		    <div style="display:inline-block; vertical-align:top; text-align:center">
				<input type="text" name="shelflife_min" value="<?=$shelflife_min;?>" class="form-control input-sm" placeholder="Min" style="width:80px">
				<input type="text" name="shelflife_max" value="<?=$shelflife_max;?>" class="form-control input-sm" placeholder="Max" style="width:80px">
				<button class="btn btn-primary btn-sm" type="submit"><i class="fa fa-filter"></i></button><br/>
				<strong>Shelflife</strong>
			</div>
		</div>
		<div class="col-sm-2">
           	<h2 class="minimal">Inventory Exporter</h2>
		</div>
		<div class="col-sm-2">
			<div class="input-group">
				<input type="text" name="s2" value="<?php echo $search; ?>" id="s2" class="form-control input-sm" placeholder="Search...">
				<span class="input-group-btn">
					<button class="btn btn-primary btn-sm" type="submit"><i class="fa fa-search"></i></button>
				</span>
			</div>
		</div>
		<div class="col-sm-1">
		</div>
		<div class="col-sm-1">
		</div>
		<div class="col-sm-1">
    		<button class="btn btn-success btn-submit pull-right" type="button"><i class="fa fa-save"></i> Save Inventory</button>
		</div>
	</div>
</div>
</form>

<!---------------------------------------------------------------------------->
<!------------------------------ Alerts Section ------------------------------>
<!---------------------------------------------------------------------------->

	<div id="item-updated" class="alert alert-success fade in text-center" style="display: none;">
	    <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
	    <strong>Success!</strong> Changes have been updated.
	</div>
	
<!----------------------------------------------------------------------------->
<!---------------------------------- Body Out --------------------------------->
<!----------------------------------------------------------------------------->

<form class="form-inline form-inventory" method="POST" action="/manage_inventory.php">
<input type="hidden" name="s2" value="">

    <div id="pad-wrapper">
		<div class="text-center">
			<em>Leave Hidden Qty blank for unadjusted qty, set to "0" for indefinitely-hidden stock, and set to any other qty for manually-calculated adjustment</em>
		</div>
    
        <div class ='col-md-12'>
        	<div class='table-responsive'>
        		<table class='table table-hover table-striped table-condensed'>
        		    <thead>
        		        <th class="col-md-6">Description</th>
        		        <th class="col-md-1">Shelflife</th>
        		        <th class="col-md-1">Stock Qty</th>
        		        <th class="col-md-1">Hidden Qty</th>
        		        <th class="col-md-1">Visible Qty</th>
        		    </thead>
        		    <tbody>
                        <?=$table_rows?>
                    </tbody>
        		</table>
        	</div>
        </div>
    </div>
</form>

<form class="form-inline form-search" method="POST" action="/sales.php">
	<textarea name="s2" class="form-control hidden" rows="5"><?=$search_str;?></textarea>
</form>

<?php include_once 'inc/footer.php'; ?>

    <script type="text/javascript">
		$(document).ready(function() {
			$(".btn-submit").on("click",function() {
				$(".form-inventory").prop('action','/manage_inventory.php');
				$(".form-inventory").find("input[name='s2']").val($("#s2").val());
				$(".form-inventory").submit();
			});

			$(".btn-export").on("click",function() {
				var modal_msg = "This is one of those Don't-Take-It-Lightly things that you only do if you know what you're doing.<br/></br/>"+
					"Like walking down a dark alley by yourself at night, singing 'I got money in my pocket, money in my pocket...'";
				modalAlertShow('An important warning...',modal_msg,true,'exportInventory');
			});
			$(".btn-sales").on("click",function() {
				$(".form-search").submit();
			});
			$(".btn-download").on("click",function() {
				$(".form-inventory").prop('action','downloads/inventory-export-<?=$today;?>.csv');
				$(".form-inventory").submit();
			});
			$(".btn-toggle").on("click",function() {
				$(this).toggleClass('active');
				if ($(this).hasClass('active')) { $(this).removeClass('btn-default').addClass('btn-primary'); }
				else { $(this).removeClass('btn-primary').addClass('btn-default'); }
			});
		});
		//submits form on hidden checkbox changes
		$(".checkbox-submit").on("change",function() {
			$(this).closest("form").submit();
		});
		function exportInventory() {
        	console.log(window.location.origin+"/json/inventory-export.php");
	        $.ajax({
				url: 'json/inventory-export.php',
				type: 'get',
				data: {},
				dataType: 'json',
				cache: false,
				success: function(json, status) {
					if (json.message) {
						alert(json.message);
					} else if (json.error) {
						alert(json.error);
					}
				},
				error: function(xhr, desc, err) {
//					console.log(xhr);
				},
			});
		}
	</script>

</body>
</html>
