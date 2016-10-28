<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/getPipeIds.php';
?>

<!----------------------------------------------------------------------------->
<!------------------------------- HEADER OUTPUT ------------------------------->
<!----------------------------------------------------------------------------->
<!DOCTYPE html>
<html>
<!-- Declaration of the standard head with Accounts home set as title -->
<head>
	<title>VMM Inventory</title>
	<?php
		//Standard headers included in the function
		include_once $rootdir.'/inc/scripts.php';
	?>
	<link rel="stylesheet" href="../css/operations-overrides.css" type="text/css" />
	<style>
		hr {
			margin-top: 0;
			margin-bottom: 10px;
		}
	</style>

</head>

<body class="sub-nav">
	
<!----------------------------------------------------------------------------->
<!------------------------- Output the navigation bar ------------------------->
<!----------------------------------------------------------------------------->

	<?php include 'inc/navbar.php'; ?>
	
	<table class="table table-header">
		<tbody>
			<tr>
				<td class="col-md-1">
				</td>
				<td class="col-md-4">
					<div class="col-md-2" style="margin-top: 9px;"><strong>Filter Bar</strong>:</div>
					<div class="col-md-10">
						<input class="form-control" type="text" name="" placeholder="part"/>
					</div>
				</td>
				<td class="col-md-1">
					<input class="form-control" type="text" name="" placeholder="Date"/>
				</td>
				<td class="col-md-1">
					<input class="form-control" type="text" name="" placeholder="Location"/>
				</td>
				<td class="col-md-1">
					<input class="form-control" type="text" name="" placeholder="Status"/>
				</td>
				<td class="col-md-1">
					<input class="form-control" type="text" name="" placeholder="Condition"/>
				</td>
				<td class="col-md-2">
					Toggles:
					<button class="btn btn-default active">Up</button>
					<button class="btn btn-default">Down</button>
					  
					<button class="btn btn-default active">MVP</button>
					<button class="btn btn-default">...</button>
				</td>
				<td class="col-md-1">
				</td>
			</tr>
		</tbody>
	</table>
	
	<div class="row" style="margin: 0;">
		<div class="col-md-2">
			<div class="row" style="margin: 0">
				<div class="col-md-2">
					<button class="btn btn-success buttonAdd" style="margin-top: 24px;"><i class="fa fa-plus" aria-hidden="true"></i></button>
				</div>
				<div class="col-md-10">
					<img class="img-responsive" src="http://placehold.it/350x150">
				</div>
			</div>
		</div>
		<div class="col-md-3">
			<strong>Part - Amea</strong>
			<hr>
			Description, Manufacture<br><br>
			<i>Alias: David, Aaron, Andrew</i>
		</div>
		<div class="col-md-2">
			<strong>Order History</strong>
			<hr>
			<span title="Purchase Order" style="text-decoration: underline;">PO</span>: <a href="">#123</a>, <a href="">#234</a><br>
			<span title="Sales Order" style="text-decoration: underline;">SO</span>: <a href="">#111</a>, <a href="">#222</a>
		</div>
		<div class="col-md-2">
			<strong>Status</strong>
			<hr>
			<button title="In-stock" class="btn btn-danger">1</button>
			<button title="Sold" class="btn btn-success">2</button>
			<button title="Market" class="btn btn-primary">3</button>
		</div>
		<div class="col-md-2">
			<strong>Condition</strong>
			<hr>
			<button title="Refurb" class="btn btn-info">0</button>
			<button title="Broken" class="btn btn-danger">1</button>
			<button title="Used" class="btn btn-success">2</button>
			<button title="New" class="btn btn-primary">3</button>
		</div>
		<div class="col-md-1">
			<strong>Cost Avg.</strong>
			<hr>
			$1,000 - 1,500
		</div>
	</div>
	
	<div class="row addItem" style="margin-top: 60px; margin-left: 0; margin-right: 0; border: 1px solid #E7E7E7; padding: 20px; display: none;">
		<div class="row">
			<div class="col-md-12">
				<button class="btn btn-default btn-sm active pull-left" style="margin-right: 5px;"><i class="fa fa-plus" aria-hidden="true"></i></button>
				<h3>Part - Amea</h3>
				<p style="">Description Manufacture <i>Alias: David, Aaron, Andrew</i></p>
			</div>
		</div>
		
		<hr>
		<div class="row">
			<div class="col-md-3">
				<div class="col-md-2">
					<button class="btn btn-success btn-sm" style="margin-top: 15px;"><i class="fa fa-plus" aria-hidden="true"></i></button>
				</div>
				<div class="col-md-10">
					<label for="serial">Serial Number</label>
					<input class="form-control" type="text" name="serial" placeholder="#123"/>
				</div>
			</div>
			<div class="col-md-2">
				<label for="date">Date</label>
				<input class="form-control" type="text" name="date" placeholder="00/00/0000"/>
			</div>
			<div class="col-md-2">
				<label for="date">Location</label>
				<input class="form-control" type="text" name="date" placeholder="Warehouse Location"/>
			</div>
			<div class="col-md-1">
				<label for="qty">Qty</label>
				<input class="form-control" type="text" name="qty" placeholder="Quantity"/>
			</div>
			<div class="col-md-2">
				<label for="condition">Condition</label>
				<input class="form-control" type="text" name="condition" placeholder="Condition"/>
			</div>
			<div class="col-md-1">
				<label for="status">Status</label>
				<input class="form-control" type="text" name="status" placeholder="Status"/>
			</div>
			<div class="col-md-1">
				<label for="price">Cost</label>
				<input class="form-control" type="text" name="price" placeholder="$$$"/>
			</div>
		</div>
		<div class="row">
			<div class="col-md-3">
				<div class="col-md-2">
					<button class="btn btn-success btn-sm" style="margin-top: 15px;"><i class="fa fa-plus" aria-hidden="true"></i></button>
				</div>
				<div class="col-md-10">
					<label for="serial">Serial Number</label>
					<input class="form-control" type="text" name="serial" placeholder="#123"/>
				</div>
			</div>
			<div class="col-md-2">
				<label for="date">Date</label>
				<input class="form-control" type="text" name="date" placeholder="00/00/0000"/>
			</div>
			<div class="col-md-2">
				<label for="date">Location</label>
				<input class="form-control" type="text" name="date" placeholder="Warehouse Location"/>
			</div>
			<div class="col-md-1">
				<label for="qty">Qty</label>
				<input class="form-control" type="text" name="qty" placeholder="Quantity"/>
			</div>
			<div class="col-md-2">
				<label for="condition">Condition</label>
				<input class="form-control" type="text" name="condition" placeholder="Condition"/>
			</div>
			<div class="col-md-1">
				<label for="status">Status</label>
				<input class="form-control" type="text" name="status" placeholder="Status"/>
			</div>
			<div class="col-md-1">
				<label for="price">Cost</label>
				<input class="form-control" type="text" name="price" placeholder="$$$"/>
			</div>
		</div>
		<div class="row">
			<div class="col-md-3">
				<div class="col-md-2">
					<button class="btn btn-success btn-sm" style="margin-top: 15px;"><i class="fa fa-plus" aria-hidden="true"></i></button>
				</div>
				<div class="col-md-10">
					<label for="serial">Serial Number</label>
					<input class="form-control" type="text" name="serial" placeholder="#123"/>
				</div>
			</div>
			<div class="col-md-2">
				<label for="date">Date</label>
				<input class="form-control" type="text" name="date" placeholder="00/00/0000"/>
			</div>
			<div class="col-md-2">
				<label for="date">Location</label>
				<input class="form-control" type="text" name="date" placeholder="Warehouse Location"/>
			</div>
			<div class="col-md-1">
				<label for="qty">Qty</label>
				<input class="form-control" type="text" name="qty" placeholder="Quantity"/>
			</div>
			<div class="col-md-2">
				<label for="condition">Condition</label>
				<input class="form-control" type="text" name="condition" placeholder="Condition"/>
			</div>
			<div class="col-md-1">
				<label for="status">Status</label>
				<input class="form-control" type="text" name="status" placeholder="Status"/>
			</div>
			<div class="col-md-1">
				<label for="price">Cost</label>
				<input class="form-control" type="text" name="price" placeholder="$$$"/>
			</div>
		</div>
	</div>



<?php include_once 'inc/footer.php'; ?>
<script src="js/operations.js"></script>

<script>
	(function($){
		//get main header height
		var height = $('header.navbar').height();
		//get possible filter bar height
		var heightOPT = $('.table-header').height();
		var offset = height + heightOPT + 25;

		$('body').css('padding-top', offset);
	
		$('.buttonAdd').click(function(){
			$('.addItem').slideToggle('fast');
		});
	})(jQuery);
</script>

</body>
</html>
