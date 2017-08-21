<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
?>

<!DOCTYPE html>
<html>
	<head>
		<?php 
			include_once $rootdir.'/inc/scripts.php';
		?>
		<title>Job Quote</title>
		<link rel="stylesheet" href="../css/operations-overrides.css?id=<?php if (isset($V)) { echo $V; } ?>" type="text/css" />
		<style type="text/css">
			.list {
				padding: 5px;
			}

			.list-pad span {
				margin-top: 5px;
				display: block;
			}

/*			.materials_list:nth-child(odd), .expenses_list:nth-child(odd), .services_list:nth-child(odd) {
				background: #FFF;
			}

			.materials_list:nth-child(even), .expenses_list:nth-child(even), .services_list:nth-child(even) {
				background: transparent;
			}*/

			.row-title {
				padding: 5px 0;
				background-color: #fff;
			}

			.row-title-pad {
				padding: 10px 0;
			}

			hr {
				margin: 0;
			}

			section {
				margin-bottom: 15px;
			}
		</style>
	</head>
	
	<body class="sub-nav" id="rma-add" data-order-type="<?=$order_type?>" data-order-number="<?=$order_number?>">
	<!---------------- Begin the header output  ---------------------->
		<div class="container-fluid pad-wrapper data-load">
			<?php include 'inc/navbar.php'; include 'modal/package.php';?>
			<div class="row table-header" id = "order_header" style="margin: 0; width: 100%;">
				<div class="col-sm-4">
					
				</div>
				<div class="col-sm-4 text-center" style="padding-top: 5px;">
					<h2>Job Quote</h2>
				</div>
				<div class="col-sm-4">
					<button class="btn-flat success pull-right btn-update" style="margin-top: 10px; margin-right: 10px;">Create</button>
				</div>
			</div>

			<div class="row" style="height: 100%; margin: 0;">
				<div class="col-md-2" data-page="addition" style="padding-top: 120px; background-color: #efefef; height: 100%;">
					<select name="companyid" id='companyid' class="form-control input-xs" data-field="" style="margin-bottom: 10px;">
						<option>Company</option>
					</select>
					<input class="form-control input-sm" id="bid_no" name="bid_no" type="text" placeholder="Bid No." value="" style="margin-bottom: 10px;">

					<select name="contactid" id='contactid' class="form-control input-xs" data-field="" style="margin-bottom: 10px;">
						<option>Contact</option>
					</select>
					<textarea id="scope" class="form-control" name="scope" rows="3" style="margin-bottom: 10px;" placeholder="Scope"></textarea>

					<br>

					<label>Site Information</label>
					<select name="addressid" id='addresssid' class="form-control input-xs" data-field="" style="margin-bottom: 10px;">
						<option>Address</option>
					</select>
					<select name="contactid" id='contactid' class="form-control input-xs" data-field="" style="margin-bottom: 10px;">
						<option>Contact</option>
					</select>
					<textarea id="notes" class="form-control" name="notes" rows="3" style="margin-bottom: 10px;" placeholder="Public Notes"></textarea>

					<br>

					<label>Internal Use Only</label>
					<input class="form-control input-sm" id="mileage" name="mileage" type="text" placeholder="Mileage Rate" value="" style="margin-bottom: 10px;">
					<textarea id="private_notes" class="form-control textarea-info" name="private_notes" rows="3" style="margin-bottom: 10px;" placeholder="Private Notes"></textarea>
				</div>
						
				<div class="col-sm-10" style="padding-top: 95px;">

					<section>
						<div class="row row-title">
							<div class="col-sm-2">
								<h4>Labor</h4>
							</div>
						</div>

						<div class="row list">
							<div class="col-sm-2"></div>
							<div class="col-sm-10 remove-pad">
								<div class="col-sm-10">
									<div class="col-sm-2 remove-pad pull-right">
						            	<input type="text" class="form-control input-sm" value="" placeholder="Rate">
						            </div>
						            <div class="col-md-1 pull-right">
						            	<span style="text-align: center; display: block; margin-top: 5px; color: #777;">x</span>
						            </div>
						            <div class="col-sm-2 remove-pad pull-right">
						            	<input type="text" class="form-control input-sm" value="" placeholder="Hours">
						            </div>
					            </div>
					            <div class="col-sm-2">
					            	<!-- <span style="display: block; margin-top: 5px; color: #777;">= Calculated Amount</span> -->
					            	<input type="text" class="form-control input-sm pull-right" value="" placeholder="0.00" style="max-width: 100px;" readonly>
					            </div>
							</div>
						</div>
					</section>

					<section>
						<div class="row row-title">
							<div class="col-sm-12">
								<h4>Materials</h4>
							</div>
						</div>

						<div class="row list">
							<div class="col-sm-2">
								<span class="descr-label">ERB1-CBL0071-001 &nbsp; </span> &nbsp; 
								<div class="description desc_second_line descr-label" style="color:#aaa;">
									GDM &nbsp;  <span class="description-label"></span>
								</div>
							</div>
							<div class="col-sm-10 remove-pad">
								<div class="col-sm-10">
									<div class="col-sm-2 remove-pad pull-right">
						            	<input type="text" class="form-control input-sm" value="" placeholder="Price">
						            </div>
						            <div class="col-md-1 pull-right">
						            	<span style="text-align: center; display: block; margin-top: 5px; color: #777;">x</span>
						            </div>
						            <div class="col-sm-2 remove-pad pull-right">
						            	<input type="text" class="form-control input-sm" value="" placeholder="Qty">
						            </div>
					            </div>
					            <div class="col-sm-2">
					            	<!-- <span style="display: block; margin-top: 5px; color: #777;">= Calculated Amount</span> -->
					            	<input type="text" class="form-control input-sm pull-right" value="" placeholder="0.00" style="max-width: 100px;" readonly>
					            </div>
							</div>
							
						</div>

						<hr>

						<div class="row list">
							<div class="col-sm-12">
								<a href="#">Add... <i class="fa fa-plus"></i></a>
							</div>
						</div>
					</section>

					<section>
						<div class="row">
							<div class="col-sm-6">
								<h5>Materials Total:</h5>
							</div>
							<div class="col-sm-6">
								<input type="text" class="form-control input-sm pull-right" value="" placeholder="$0.00" style="max-width: 100px; margin-right: 10px;" readonly>
							</div>
						</div>

						<br>

						<div class="row row-title">
							<div class="col-sm-12">
								<h4>Expenses</h4>
							</div>
						</div>

						<div class="row list-pad list">
							<div class="col-sm-4">
								<span>Lunch</span>
							</div>
							<div class="col-sm-8">
				            	<input type="text" class="form-control input-sm pull-right" value="" placeholder="$0.00" style="max-width: 100px;">
				            </div>
						</div>
						<hr>
						<div class="row list-pad list">
							<div class="col-sm-4">
								<span>Coffee</span>
							</div>
							<div class="col-sm-8">
				            	<input type="text" class="form-control input-sm pull-right" value="" placeholder="$0.00" style="max-width: 100px;">
				            </div>
						</div>
						<hr>
						<div class="row list">
							<div class="col-sm-12">
								<a href="#">Add... <i class="fa fa-plus"></i></a>
							</div>
						</div>
					</section>

					<br>

					<section>
						<div class="row row-title">
							<div class="col-sm-12">
								<h4>Outside Services</h4>
							</div>
						</div>

						<div class="row list-pad list">
							<div class="col-sm-4">
								<span>Drilling</span>
							</div>
							<div class="col-sm-8">
				            	<input type="text" class="form-control input-sm pull-right" value="" placeholder="$0.00" style="max-width: 100px;">
				            </div>
						</div>
						<hr>
						<div class="row list">
							<div class="col-sm-12">
								<a href="#">Add... <i class="fa fa-plus"></i></a>
							</div>
						</div>
					</section>

					<br>

					<div class="row" style="padding-bottom: 25px;">
						<div class="col-sm-6">
							<h3>Total</h3>
						</div>
						<div class="col-sm-6">
							<input type="text" class="form-control input-sm pull-right" value="" placeholder="$0.00" style="max-width: 100px; margin-right: 10px;" readonly>
						</div>
					</div>
					
				</div>
			</div>
		</div> 
		
		<!-- End true body -->
		<?php include_once 'inc/footer.php';?>
		<script type="text/javascript">
		</script>
	
	</body>
</html>
