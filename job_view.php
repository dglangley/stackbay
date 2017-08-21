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
		<title>Job</title>
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

			.table-first {
				font-weight: bold;
				text-transform: uppercase;
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
					<h2>VZW1234</h2>
				</div>
				<div class="col-sm-4">
					<button class="btn btn-warning btn-sm pull-right btn-update" style="margin-top: 10px; margin-right: 10px;"><i class="fa fa-briefcase" aria-hidden="true"></i> Regular Pay</button>
					<button class="btn btn-default btn-sm pull-right btn-update" style="margin-top: 10px; margin-right: 10px;"><i class="fa fa-car" aria-hidden="true"></i> Travel Time</button>
				</div>
			</div>

			<div class="row" style="height: 100%; margin: 0;">
				<div class="col-md-2" data-page="addition" style="padding-top: 110px; background-color: #efefef; height: 100%;">
					<span style="font-weight: bold; font-size: 15px;">Ventura Telephone</span>
					<br><br>
					<label>Due Date</label>
					<br>
					8/23/2017

					<br><br>

					<label style="text-transform: uppercase;">Site Information</label>
					<p>David Langley</p>

					<p>3037 Golf Course Drive, Suite 2<br>
					Ventura, CA 93003<br></p>
					
					<p>Here are some notes for you to read.</p>

					<br>

					<label style="text-transform: uppercase;">Scope / Public Notes</label>
					<p>Here is a scope of everything that is being done for the job.</p>

					<br>

					<label style="text-transform: uppercase;">Internal Use Only</label>
					<p>The Big Boss Chris</p>

					<label>Private Notes</label>
					<p>These notes are only seen by us and no one else.</p>
				</div>
						
				<div class="col-sm-10" style="padding-top: 95px;">

					<section>
						<div class="row row-title">
							<div class="col-sm-2">
								<h4>Documentation</h4>
							</div>
						</div>

						<div class="row list table-first">
							<div class="col-md-3">Date/Time</div>
							<div class="col-md-3">User</div>
							<div class="col-md-3">Notes</div>
							<div class="col-md-3">Action</div>
						</div>

						<hr>

						<div class="row list">
							<div class="col-md-3">7/12/2017</div>
							<div class="col-md-3">Scott</div>
							<div class="col-md-3">MOP</div>
							<div class="col-md-3"><a href="#"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></a></div>
						</div>
						<hr>
						<div class="row list">
							<div class="col-md-3">7/18/2017</div>
							<div class="col-md-3">David</div>
							<div class="col-md-3">MOP</div>
							<div class="col-md-3"><a href="#"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></a></div>
						</div>
						<hr>
						<div class="row list">
							<div class="col-md-3">8/22/2017</div>
							<div class="col-md-3">Chris</div>
							<div class="col-md-3">MOP</div>
							<div class="col-md-3"><a href="#"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></a></div>
						</div>
					</section>

					<section>
						<div class="row row-title">
							<div class="col-sm-12">
								<h4>Activity</h4>
							</div>
						</div>

						<div class="row list table-first">
							<div class="col-md-2">Date/Time</div>
							<div class="col-md-4">Tech</div>
							<div class="col-md-6">Activity</div>
						</div>

						<hr>

						<div class="row list">
							<div class="col-md-2">8/12/2017</div>
							<div class="col-md-4">Rathna</div>
							<div class="col-md-6">Completed the Repair</div>
						</div>
						<hr>
						<div class="row list">
							<div class="col-md-2">6/18/2017</div>
							<div class="col-md-4">Sabedra</div>
							<div class="col-md-6">Began the Repair</div>
						</div>
					</section>

					<section>
						<div class="row row-title">
							<div class="col-sm-6">
								<h4>Materials</h4>
							</div>
							<div class="col-sm-6">
								<button data-toggle="modal" data-target="#modal-component" class="btn btn-flat btn-sm btn-status pull-right" type="submit">
						        	<i class="fa fa-plus"></i>	
						        </button>
					        </div>
						</div>

						<div class="row list table-first">
							<div class="col-md-3">Material</div>
							<div class="col-md-1">Requested</div>
							<div class="col-md-2">PO</div>
							<div class="col-md-1">Available</div>
							<div class="col-md-1">Pulled</div>
							<div class="col-md-2 text-right">Price Per Unit</div>
							<div class="col-md-2 text-right">EXT Price</div>
						</div>

						<hr>

						<div class="row list">
							<div class="col-md-3">
								<span class="descr-label">ERB1-CBL0071-001 &nbsp; </span> &nbsp; 
								<div class="description desc_second_line descr-label" style="color:#aaa;">
									GDM &nbsp;  <span class="description-label"></span>
								</div>
							</div>
							<div class="col-md-1">12</div>
							<div class="col-md-2">
								<span class="label label-success complete_label status_label" style="">
									<a style="color: #FFF;" href="/PO668">668</a>
								</span>
							</div>
							<div class="col-md-1">5</div>
							<div class="col-md-1">0</div>
							<div class="col-md-2 text-right">$0.00</div>
							<div class="col-md-2 text-right">$0.00</div>
						</div>

						<hr>

						<div class="row list">
							<div class="col-md-3"></div>
							<div class="col-md-1"></div>
							<div class="col-md-2"></div>
							<div class="col-md-1"></div>
							<div class="col-md-1"></div>
							<div class="col-md-2"></div>
							<div class="col-md-2 text-right">Total: $0.00</div>
						</div>

					</section>

					<section>
						<div class="row row-title">
							<div class="col-sm-6">
								<h4>Expenses</h4>
							</div>
							<div class="col-sm-6">
								<button data-toggle="modal" data-target="#modal-component" class="btn btn-flat btn-sm btn-status pull-right" type="submit">
						        	<i class="fa fa-plus"></i>	
						        </button>
					        </div>
						</div>

						<div class="row list table-first">
							<div class="col-md-2">Date/Time</div>
							<div class="col-md-2">User</div>
							<div class="col-md-4">Notes</div>
							<div class="col-md-2 text-right">Amount</div>
							<div class="col-md-2">Action</div>
						</div>

						<hr>

						<div class="row list">
							<div class="col-md-2">8/12/2017</div>
							<div class="col-md-2">Scott</div>
							<div class="col-md-4">Wishes he could eat a delicious pastrami sandwich from Stackz</div>
							<div class="col-md-2 text-right">$12.00</div>
							<div class="col-md-2"><a href="#"><i class="fa fa-download" aria-hidden="true"></i></a></div>
						</div>
					</section>

					<br>

					<section>
						<div class="row row-title">
							<div class="col-sm-12">
								<h4>Closeout</h4>
							</div>
						</div>
					</section>

					<br>
					
				</div>
			</div>
		</div> 
		
		<!-- End true body -->
		<?php include_once 'inc/footer.php';?>
		<script type="text/javascript">
		</script>
	
	</body>
</html>
