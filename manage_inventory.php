<?php

	$rootdir = $_SERVER['ROOT_DIR'];
	
	require_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/getTerms.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/getPipeIds.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/dropPop.php';
	include_once $rootdir.'/inc/display_part.php';
	include_once $rootdir.'/inc/locations.php';
    $table_rows = '';
    $rearrange = array();
    $rearrange = $_POST;
    if($rearrange){
        // prexit($rearrange);
        foreach($rearrange as $partid => $r){
            $visible = 0;
            $visible = $r['stock'] - $r['hidden'];
            if($visible < 0 || $r['hidden'] === 0){
                $visible = 0;
            }
            $replace = "REPLACE INTO `qtys` (`partid`,`qty`, `hidden_qty`, `visible_qty`) VALUES ($partid, ".$r['stock'].", ".prep($r['hidden']).", ".prep($visible,0).");";
            qdb($replace) or die(qe()." $replace");
        }
    }
    $select = "SELECT * FROM `qtys`;";
    $result = qdb($select) or die(qe()." | $select");
    foreach($result as $r){
        $table_rows .="
        <tr>
            <td class='col-md-9'>".display_part($r['partid'],true)."</td>
            <td class='qty_form qty_form_stock col-md-1'><input type='text' name='".$r['partid']."[stock]' class='form-control input-sm' placeholder='' readonly value=".$r['qty']."></td>
            <td class='qty_form qty_form_hidden col-md-1'><input type='text' name='".$r['partid']."[hidden]' class='form-control input-sm' placeholder='' value=".$r['hidden_qty']."></td>
            <td class='qty_form qty_form_visible col-md-1'><input type='text' name='".$r['partid']."[visible]' class='form-control input-sm' placeholder='' readonly value=".$r['visible_qty']."></td>
        </tr>
        ";
    }
?>
<!DOCTYPE html>
<html>
<head>
	<title>Manage Inventory</title>
	<?php
		//Standard headers included in the function
		include_once $rootdir.'/inc/scripts.php';
	?>
</head>
<body>
<!----------------------------------------------------------------------------->
<!------------------------- Output the navigation bar ------------------------->
<!----------------------------------------------------------------------------->

	<?php include 'inc/navbar.php'; ?>

    <form class="form-inline" method="POST" action="/manage_inventory.php">

<!----------------------------------------------------------------------------->
<!-------------------------- Header/Filter Bar/Title -------------------------->
<!----------------------------------------------------------------------------->
	<table class="table table-header table-filter">
		<tr>
			<td class="col-md-2"></td>
			<td class = "col-md-3"></td>

			<!-- TITLE -->
			<td class="col-md-2 text-center">
            	<h2 class="minimal">Inventory Manager</h2>
			</td>
			
			<!--This Handles the Search Bar-->
			
			<!--Condition Drop down Handler-->
			
			<td class="col-md-2"></td>			
			<td class="col-md-1"></td>
			<td class="col-md-2">
				<div class="pull-right form-group">
		    		<button class="btn btn-primary btn-sm" type="submit">Save Inventory</button>
				</div>
			</td>
		</tr>
	</table>

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
    
    <div id="pad-wrapper">
        <div class ='col-md-12'>
        	<div class='table-responsive'>
        		<table class='table table-hover table-striped table-condensed'>
        		    <thead>
        		        <th>Description</th>
        		        <th>Stock Qty</th>
        		        <th>Hidden Qty</th>
        		        <th>Visible Qty</th>
        		    </thead>
        		    <tbody>
                        <?=$table_rows?>
                    </tbody>
        		</table>
        	</div>
        </div>
    </div>
</form>
	





<?php include_once 'inc/footer.php'; ?>


</body>
</html>
