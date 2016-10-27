<?php

//=============================================================================
//======================== Order Form General Template ========================
//=============================================================================
//  This is the general output form for the sales and purchase order forms.   |
//	This will be designed to cover all general use cases for shipping forms,  |
//  so generality will be crucial. Each of the sections is to be modularized  |
//	for the sake of general accessiblilty and practicality.					  |
//																			  |
//	Aaron Morefield - October 18th, 2016									  |
//=============================================================================

	//Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	//include_once $rootdir.'/inc/order-creation.php';
	/*function outputCompleteRow($params = array(),$row){
	echo("<tr class = 'easy-output'>
		        <td>".$row."</td>
	            <td>".$params['part']." &nbsp; ".$params['heci']." &nbsp; ".$params['descr']."</td>
	            <td>".$params['date']."</td>
	            <td>".$params['qty']."</td>
	            <td>".format_price($params['price'])."</td>
	            <td>".format_price($params['qty']*$params['price'])."</td>
				<td class='forms_edit'><i class='fa fa-pencil fa-4' aria-hidden='true'></i></td>
				<td class='forms_trash'><i class='fa fa-trash fa-4' aria-hidden='true'></i></td>
	    </tr>
	    <tr class='lazy-entry' style='display:none;'>
					<td style='padding:0;'><input class='form-control input-sm' type='text' name='ni_line' placeholder='#' value = '$row' style='height:28px;padding:0;text-align:center;'></td>
		            <td id='search_collumn'>
		            	<div \ id = 'item-selected'>
							<select class='item_search'>
								<option>".$params['part']."
							</select>
						</div>
					</td>
		            <td>				
		            	<div class='input-group date datetime-picker-line'>
				            <input type='text' name='ni_date' class='form-control input-sm' value='".$params['date']."' style = 'min-width:50px;'/>
				            <span class='input-group-addon'>
				                <span class='fa fa-calendar'></span>
				            </span>
			            </div>
				    </td>
		            <td><input class='form-control input-sm' type='text' name='ni_qty' placeholder='QTY' value = '".$params['qty']."'></td>
		            <td><input class='form-control input-sm' type='text' name = 'ni_price' placeholder='UNIT PRICE' value=  '".$params['price']."'></td>
		            <td><input class='form-control input-sm' readonly='readonly' type='text' name='ni_ext' placeholder='ExtPrice'></td>
					<td colspan='2' id = 'check_collumn'>
						<a class='btn-flat success pull-right line_item_submit' >
							<i class='fa fa-check fa-4' aria-hidden='true'></i>
						</a>
					</td>
	    </tr>");
	}*/
	
	$order_number = isset($_REQUEST['on']) ? $_REQUEST['on'] : "New";
	$order_type = ($_REQUEST['ps'] == 'p') ? "Purchase" : "Sales";

?>

<!DOCTYPE html>
<html>
	<head>
		<?php 
			include_once $rootdir.'/inc/scripts.php';
		?>
		<link rel="stylesheet" href="../css/operations-overrides.css" type="text/css" />
	</head>
	<!---->
	<body class="sub-nav forms" id = "order_body" data-order-type="<?=$order_type?>" data-order-number="<?=$order_number?>">
		<div class="container-fluid pad-wrapper">
		<?php include 'inc/navbar.php';?>
		<div class="row-fluid table-header" id = "order_header" style="width:100%;height:50px;background-color:
		<?= ($order_type == "Sales")?"#faefdd":"#f7fff1";?>
		;">
			<div class="col-md-4"></div>
			<div class="col-md-4 text-center">
				<?php
				echo"<h1>";
				if ($order_number=='New'){
					echo $order_number;
				}
				echo " $order_type Order";
				if ($order_number!='New'){
					echo " #$order_number";
				}
				echo"</h1>"
				?>
			</div>
			<div class="col-md-4">
				<button class="btn-flat  <?=($order_number=="New")?'success':'default'?> pull-right" id = "save_button" style="margin-top:2%;margin-bottom:2%;">
					<?=($order_number=="New") ? 'Create' :'Save'?>
				</button>
			</div>
		</div>
		<div class="spacer" style = "margin:120px; width:100%"></div>
	
	<!-- Row declaration for splitting the two "halves of the page  -->
	<div class="container-fluid">
	
		<!--================== Begin Left Half ===================-->
		<div class="col-md-4" id="left-side-main">
			<?php //echo order_left($order_number,$order_type); ?>
		</div>
		<!--======================= End Left half ======================-->
	

		<!--===================== Begin Right half =====================-->
		<div class="col-md-8">
			
			<!-- Output the sales-rep dropdown in the top right corner -->
			<div class="forms_section">
				<div style="float:right;">
					<div class="ui-select" style="width:125px;margin-bottom:0;">
	                    <select id="sales-rep">
	                        <option data-rep-id=1><?=getRep(1)?></option>
	                        <option data-rep-id=2><?=getRep(2)?></option>
							<option data-rep-id=3><?=getRep(3)?></option>
	                    </select>
	                </div>
				</div>
			</div>
			
			<table class="table table-hover table-striped table-condensed table-responsive" id="items_table" style="margin-top:1.5%;">
			<thead>
		         <tr>
		            <th style = "min-width:30px;">
		            	<span class="line"></span>
		        	</th>
		            <th class="col-md-7" style="max-width:600px;">
		            	<span class="line"></span>		
		            	Item	
		            </th>
		            <th class="col-md-2" style="min-width:130px;">
		            	<span class="line"></span>   	
		            	Date
		            </th>
		            <th class="col-md-1">   	
		            	<span class="line"></span>   	
		            	Qty
		            </th>
		            <th class="col-md-1">
						<span class="line"></span>   	
   		            	Unit Price
		        	</th>
		            <th class="col-md-1">
		            	<span class="line"></span>
		            	Ext. Price 
		        	</th>
		        	<th>
		            	<span class="line"></span>   	
		        	</th>
					<th>
						&nbsp;
		        	</th>
		         </tr>
		      </thead>
			<tbody id="right_side_main">
				
	        </tbody>
			<tfoot>
				<tr id="add_row">
		            <td>
		            	<a class="btn-flat gray pull-left" id="NewSalesOrder">
							<i class="fa fa-plus fa-4" aria-hidden="true"></i>
						</a>
					</td>
					<td style="display:none;padding:0;" class = "newLineNumber"><input class="form-control input-sm" type="text" name="ni_line" placeholder="#" style="height:28px;display:none;padding:0;text-align:center;width:100%;"></td>
		            <td id="search_collumn">
		            	<div style="display:none;" id = "item-selected">
							<select class="item_search">
							</select>
						</div>
					</td>
		            <td>				
		            	<div class="input-group datetime-picker" data-format="MM/DD/YYYY" style="display:none;">
				            <input type="text" name="ni_date" class="form-control input-sm" value="<?=$endDate?>" style = "min-width:50px;"/>
				            <span class="input-group-addon">
				                <span class="fa fa-calendar"></span>
				            </span>
			            </div>
				    </td>
		            <td><input class="form-control input-sm" type="text" name="ni_qty" placeholder="QTY" style="display:none;"></td>
		            <td><input class="form-control input-sm" type="text" name = "ni_price" placeholder="UNIT PRICE" style="display:none;"></td>
		            <td><input class="form-control input-sm" readonly="readonly" type="text" name="ni_ext" placeholder="ExtPrice"  style="display:none;"></td>
					<td colspan="2" id = "check_collumn">
						<a class="btn-flat success pull-right" id = "forms_submit" style="display:none;">
							<i class="fa fa-check fa-4" aria-hidden="true"></i>
						</a>
					</td>
	
		         </tr>
			</tfoot> 

		   </table>
	</div>
		<!--====================== End Right half ======================-->
	</div>
</div>
		<?php include_once 'inc/footer.php';?>
		<script src="js/operations.js"></script>

	</body>
</html>