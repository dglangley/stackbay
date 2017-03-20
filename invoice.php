<?php

//=============================================================================
//=============================== INVOICE SCRIPT ==============================
//=============================================================================
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
	include_once $rootdir.'/inc/getWarranty.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/getOrderStatus.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/dropPop.php';
	include_once $rootdir.'/inc/operations_sidebar.php';
	include_once $rootdir.'/inc/display_part.php';
	include_once $rootdir.'/inc/order_parameters.php';
	include_once $rootdir.'/inc/invoice_creation.php';
	
//Function Declarations
	function getInvoice($in_no, $field = ''){
		$query = "Select * FROM `invoices` WHERE invoice_no = ".prep($in_no).";";
		$result = qdb($query);
		$row = mysqli_fetch_assoc($result);
		if ($field){
			return $row['field'];
		} else {
			return $row;
		}
	}
	function getInvoicePackages($in_no){
		$shipped_packages = "SELECT * FROM "
	}
	
	$order_number = grab('on');
	$invoice_number = grab('in');
	if ($order_number && !$invoice_number){
		//Check here to ensure there is no invoice for this order already (to prevent refresh)
		$invoice_number = create_invoice($order_number);
	}
	$order_number = $invoice_number;
	
	$o = o_params('invoice');
	
	
	$macro = getInvoice($invoice_number);
	
	$status = $macro['status'];
	$origin = $macro['so_number'];
	
?>

<!DOCTYPE html>
<html>
	<head>
		<?php 
			include_once $rootdir.'/inc/scripts.php';
		?>
		<link rel="stylesheet" href="../css/operations-overrides.css?id=<?php if (isset($V)) { echo $V; } ?>" type="text/css" />
		<title><?=($order_number != 'New')? (strtoupper($o['short'])." #".$order_number) : ('New '.strtoupper($o['short']) )?></title>

	</head>
	<!---->
	<body class="sub-nav forms" id = "order_body" data-order-type="<?=$o['type']?>" data-order-number="<?=$order_number?>">
		<div class="pad-wrapper">

			<?php 
				include 'inc/navbar.php';
				include_once $rootdir.'/modal/address.php';
				include_once $rootdir.'/modal/accounts.php';
				include_once $rootdir.'/modal/alert.php';
				include_once $rootdir.'/modal/contact.php';
			?>
			<div class="row-fluid table-header" id = "order_header" style="width:100%;height:50px;background-color:<?=$o['color']?>;">
				
				<div class="col-md-4">
					<?php
						// if($order_number != "New"){
							// echo '<a href="/'.$o['url'].'.php?on=' . $order_number . '" class="btn-flat pull-left"><i class="fa fa-truck"></i></a> ';
							// 1echo '<a href="/docs/'.strtoupper($o['short']).$order_number.'.pdf" class="btn-flat pull-left" target="_new"><i class="fa fa-file-pdf-o"></i></a>';
						// }
					?>
					
				</div>
				
				<div class="col-md-4 text-center">
					<?php
					echo"<h2 class='minimal' style='margin-top: 10px;'>";
					if ($order_number=='New'){
						echo "$order_number ";
					}
					echo 'Invoice';
					if ($order_number!='New'){
						echo " #$order_number";
					}
					echo ("<b>($status)</b>");
					echo"</h2>";
					?>
				</div>
				<div class="col-md-4">
					<button class="btn-flat btn-sm  <?=($order_number=="New")?'success':'success'?> pull-right" id = "save_button" data-validation="left-side-main" style="margin-top:2%;margin-bottom:2%;">
						Save
					</button>
				</div>
			</div>
			
			
			<!-- Row declaration for splitting the two "halves of the page  -->
			<div class="row remove-margin">
			
				<!--================== Begin Left Half ===================-->
				<div class="left-side-main col-md-3 col-lg-2" data-page="order">
					<?php $order_number = ($origin)? $origin: $order_number;?>
					<!-- Everything here is put out by the order creation ajax script -->
					<?=sidebar_out($order_number,$o['type'],"display")?>
				</div>
				<!--======================= End Left half ======================-->
			
		
				<!--===================== Begin Right half =====================-->
				<div class="col-md-9 col-lg-10">
					
<!-- TABLE FOR THE BOX INFORMATION -->

				<?php
					
				?>
				<div class="table-responsive">
					<h3>PACKAGES</h3>
					
					<table class="table table-hover table-striped table-condensed" id="packages_table" style="margin-top:1.5%;">
		
				   </table>
				</div>
			</div>
			<!--====================== End Right half ======================-->
		</div>
	</div>
	
		<?php include_once 'inc/footer.php';?>
		<script src="js/operations.js?id=<?php if (isset($V)) { echo $V; } ?>"></script>

	</body>
</html>
