<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/getPipeIds.php';
	
	$results_array = array();
	
	$query  = "SELECT * FROM parts where id IN (SELECT partid FROM inventory)";
	$result = qdb($query);
	
	while ($row = $result->fetch_assoc()) {
		$parts_array[] = $row;
	}
	
	function getManufacture($manfid) {
		$manf;
		
		$query  = "SELECT * FROM manfs where id = $manfid";
		$result = qdb($query);
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$manf = $result['name'];
		}
		
		return $manf;
	}
	
	function getSystemName($systemid) {
		$system;
		
		$query  = "SELECT * FROM systems where id = $systemid";
		$result = qdb($query);
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$system = $result['system'];
		}
		
		return $system;
	}
	
	function getPartSerials($partid) {
		$query  = "SELECT * FROM inventory where partid = $partid";
		$result = qdb($query);
		
		while ($row = $result->fetch_assoc()) {
			$partSerial_array[] = $row;
		}
		
		return $partSerial_array;
	}
	
	function updateToDatabase($serial, $date, $locationid, $qty, $condition, $status, $cost) {
		
	}
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
	
<!----------------------------------------------------------------------------->
<!-------------------------- Header/Filter Bar/Title -------------------------->
<!----------------------------------------------------------------------------->
	<div class="table-header" style="width: 100%; min-height: 60px;">
		<div class="row" style="padding-top: 15px; margin: 0 10px;">
			<div class="col-md-5 col-sm-5" style="padding-bottom: 15px;">
				<input class="form-control" type="text" name="" placeholder="Part"/>
			</div>
			<div class="col-md-1 col-sm-1" style="padding-bottom: 15px;">
				<input class="form-control" type="text" name="" placeholder="Date"/>
			</div>
			<div class="col-md-1 col-sm-1" style="padding-bottom: 15px;">
				<input class="form-control" type="text" name="" placeholder="Location"/>
			</div>
			<div class="col-md-1 col-sm-1" style="padding-bottom: 15px;">
				<input class="form-control" type="text" name="" placeholder="Status"/>
			</div>
			<div class="col-md-1 col-sm-1" style="padding-bottom: 15px;">
				<input class="form-control" type="text" name="" placeholder="Condition"/>
			</div>
			<div class="col-md-3 col-sm-3" style="padding-bottom: 15px;">
				Toggles:
				<div class="btn-group" role="group">
					<button class="btn btn-default active">Up</button>
					<button class="btn btn-default">Down</button>
				</div>
				
				<div class="btn-group" role="group">  
					<button class="btn btn-default active">MVP</button>
					<button class="btn btn-default">...</button>
				</div>
			</div>
		</div>
	</div>
	
	<?php foreach($parts_array as $part): ?>
		<div class="part-container">
			<div class="row" style="margin: 35px 0 0 0;">
				<div class="col-md-2 col-sm-2">
					<div class="row" style="margin: 0">
						<div class="col-md-2 col-sm-2">
							<button class="btn btn-success buttonAdd" style="margin-top: 24px;"><i class="fa fa-plus" aria-hidden="true"></i></button>
						</div>
						<div class="col-md-10 col-sm-10">
							<img class="img-responsive" src="http://placehold.it/350x150">
						</div>
					</div>
				</div>
				<div class="col-md-3 col-sm-3">
					<strong><?php echo getSystemName($part['systemid']); ?> - <?php echo $part['part']; ?></strong>
					<hr>
					Description, <?php echo getManufacture($part['manfid']); ?><br><br>
					<i>Alias: David, Aaron, Andrew</i>
				</div>
				<div class="col-md-2 col-sm-2">
					<strong>Order History</strong>
					<hr>
					<span title="Purchase Order" style="text-decoration: underline;">PO</span>: <a href="">#123</a>, <a href="">#234</a><br>
					<span title="Sales Order" style="text-decoration: underline;">SO</span>: <a href="">#111</a>, <a href="">#222</a>
				</div>
				<div class="col-md-2 col-sm-2">
					<strong>Status</strong>
					<hr>
					<button title="In-stock" class="btn btn-danger">1</button>
					<button title="Sold" class="btn btn-success">2</button>
					<button title="Market" class="btn btn-primary">3</button>
				</div>
				<div class="col-md-2 col-sm-2">
					<strong>Condition</strong>
					<hr>
					<button title="Refurb" class="btn btn-info">0</button>
					<button title="Broken" class="btn btn-danger">1</button>
					<button title="Used" class="btn btn-success">2</button>
					<button title="New" class="btn btn-primary">3</button>
				</div>
				<div class="col-md-1 col-sm-1">
					<strong>Cost Avg.</strong>
					<hr>
					$1,000 - 1,500
				</div>
			</div>
		
			<div class="row addItem" style="margin-top: 60px; margin-left: 0; margin-right: 0; border: 1px solid #E7E7E7; padding: 20px; display: none;">
				<div class="row">
					<div class="col-md-12 col-sm-12">
						<button class="btn btn-success buttonAddRows btn-sm add pull-right" style="margin-right: 5px;"><i class="fa fa-plus" aria-hidden="true"></i></button>
						<button class="btn btn-warning btn-sm add pull-right updateAll" style="margin-right: 5px;">Save Changes</button>
						<h3><?php echo getSystemName($part['systemid']); ?> - <?php echo $part['part']; ?></h3>
						<p style="">Description Manufacture <i>Alias: David, Aaron, Andrew</i></p>
					</div>
				</div>
				
				<hr>
				<div class="addRows">
					<?php foreach(getPartSerials($part['id']) as $serial): ?>
						<div class="row product-rows" style="padding-bottom: 10px;">
							<div class="col-md-2 col-sm-2">
								<label for="serial">Serial/Lot Number</label>
								<input class="form-control" type="text" name="serial" placeholder="#123" value="<?php echo $serial['serial_no']; ?>"/>
								<div class="form-text"></div>
							</div>
							<div class="col-md-2 col-sm-2">
								<label for="date">Date</label>
								<input class="form-control" type="text" name="date" placeholder="00/00/0000" value="<?php echo date_format(date_create($serial['date_created']), 'm/d/Y'); ?>"/>
							</div>
							<div class="col-md-2 col-sm-2">
								<label for="date">Location</label>
								<input class="form-control" type="text" name="date" placeholder="Warehouse Location" value="<?php echo $serial['locationid']; ?>"/>
							</div>
							<div class="col-md-1 col-sm-1">
								<label for="qty">Qty</label>
								<input class="form-control" type="text" name="qty" placeholder="Quantity" value="<?php echo $serial['qty']; ?>"/>
							</div>
							<div class="col-md-2 col-sm-2">
								<label for="condition">Condition</label>
								<input class="form-control" type="text" name="condition" placeholder="Condition" value="<?php echo $serial['item_condition']; ?>"/>
							</div>
							<div class="col-md-1 col-sm-1">
								<label for="status">Status</label>
								<input class="form-control" type="text" name="status" placeholder="Status" value="<?php echo $serial['status']; ?>"/>
							</div>
							<div class="col-md-2 col-sm-2">
								<div class="row">
									<div class="col-md-7 col-sm-7">
										<label for="price">Cost</label>
										<input class="form-control" type="text" name="price" placeholder="$$$" value=""/>
									</div>
									<div class="col-md-5 col-sm-5">
										<div class="btn-group" role="group" style="margin: 23px auto 0; display: block;">
											<button class="btn btn-primary btn-sm"><i class="fa fa-check" aria-hidden="true"></i></button>
											<button class="btn btn-danger delete btn-sm" disabled><i class="fa fa-minus" aria-hidden="true"></i></button>
										</div>
									</div>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	<?php endforeach; ?>



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
			$(this).closest('.part-container').children('.addItem').slideToggle('fast');
			
			$(this).children('.fa-plus').toggleClass('fa-minus');
			$(this).toggleClass('btn-success btn-danger');
		});
		
	     $('.update').click(function () {
	    	// $($(this).siblings('.form-text')).html($(this).val());
	    	// $(this).hide();
	     });
		
		//Append new row of data
		var element = '<div class="product-rows row new-row appended" style="padding-bottom: 10px; display: none;">\
				<div class="col-md-2 col-sm-2">\
					<label for="serial">Serial/Lot Number</label>\
					<input class="form-control" type="text" name="serial" placeholder="#123" value=""/>\
				</div>\
				<div class="col-md-2 col-sm-2">\
					<label for="date">Date</label>\
					<input class="form-control" type="text" name="date" placeholder="00/00/0000" value=""/>\
				</div>\
				<div class="col-md-2 col-sm-2">\
					<label for="date">Location</label>\
					<input class="form-control" type="text" name="date" placeholder="Warehouse Location" value=""/>\
				</div>\
				<div class="col-md-1 col-sm-1">\
					<label for="qty">Qty</label>\
					<input class="form-control" type="text" name="qty" placeholder="Quantity" value=""/>\
				</div>\
				<div class="col-md-2 col-sm-2">\
					<label for="condition">Condition</label>\
					<input class="form-control" type="text" name="condition" placeholder="Condition" value=""/>\
				</div>\
				<div class="col-md-1 col-sm-1">\
					<label for="status">Status</label>\
					<input class="form-control" type="text" name="status" placeholder="Status" value=""/>\
				</div>\
				<div class="col-md-2 col-sm-2">\
					<div class="col-md-7 col-sm-7">\
						<div class="row">\
							<label for="price">Cost</label>\
							<input class="form-control" type="text" name="price" placeholder="$$$" value=""/>\
						</div>\
					</div>\
					<div class="col-md-5 col-sm-5">\
						<div class="btn-group" role="group" style="margin: 23px auto 0; display: block;">\
							<button class="btn btn-primary btn-sm"><i class="fa fa-check" aria-hidden="true"></i></button>\
							<button class="btn btn-danger delete btn-sm"><i class="fa fa-minus" aria-hidden="true"></i></button>\
						</div>\
					</div>\
				</div>\
			</div>';
		
		//Once button is clicked the new row will be appended
		$('.buttonAddRows').click(function(){
			$('.addRows').append(element);
			$('.appended').slideDown().removeClass('appended');
			
			$('.delete').click(function(){
				$($(this).closest('.new-row')).slideUp("normal", function() { $(this).remove(); });
			});
		});
		
		//Remove rows
		$('.delete').click(function(){
			$($(this).closest('.new-row')).slideUp("normal", function() { $(this).remove(); });
		});
		
		//Update all query
		$('.updateAll').click(function() {
			//Get how many rows created + initial row
			var totalRows = $('.product-rows').length;
			var results = new Array();
			$('.product-rows').each(function() {
				
			});
		});
	})(jQuery);
</script>

</body>
</html>
