<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/display_part.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/getUser.php';
	include_once $rootdir.'/inc/form_handle.php';
	
	//$search = trim($_REQUEST['s'] ? $_REQUEST['s'] : $_REQUEST['search']);

	if(isset($_REQUEST['purchase_request_id'])) {
		$query = "UPDATE purchase_requests SET status = 'Void' WHERE id=".prep($_REQUEST['purchase_request_id']).";";
		qdb($query) OR die(qe() . ' ' . $query);
	}

	if(!in_array("5", $USER_ROLES) && !in_array("4", $USER_ROLES)) {
	 	header('Location: /operations.php');
	}

	//Query items from parts table
	$itemList = array();

	//if(!$search) {
		$query = "SELECT pr.*, p.part FROM purchase_requests pr, parts p WHERE p.id = pr.partid AND (pr.status = 'Active' OR pr.status IS NULL) ORDER BY requested DESC LIMIT 100;";
		$result = qdb($query) OR die(qe());
			
		while ($row = $result->fetch_assoc()) {
			$itemList[] = $row;
		}
	//} 

	function getRepairItemId($ro_number, $partid) {
		$repair_item_id;

		$query = "SELECT id as repair_item_id FROM repair_items WHERE ro_number = ".prep($ro_number)." LIMIT 1;";
		$result = qdb($query);

		if (mysqli_num_rows($result)) {
			$result = mysqli_fetch_assoc($result);
			$repair_item_id = $result['repair_item_id'];
		}

		//echo $query;

		return $repair_item_id;
	}
	
?>

<!------------------------------------------------------------------------------------------->
<!-------------------------------------- HEADER OUTPUT -------------------------------------->
<!------------------------------------------------------------------------------------------>
<!DOCTYPE html>
<html>
<head>
	<title>Purchase Requests</title>
	<?php
		//Standard headers included in the function
		include_once $rootdir.'/inc/scripts.php';
		include_once $rootdir. '/modal/image.php';
	?>
	<style>
		.goog-te-banner-frame.skiptranslate {
		    display: none !important;
	    } 
		body {
		    top: 0px !important; 
	    }

/*	    .complete {
	    	color: rgb(129, 189, 130) !important;
	    }*/
	</style>
</head>

<body class="sub-nav accounts-body">
	
	<?php include 'inc/navbar.php'; ?>
	<div class="table-header" id = 'filter_bar' style="width: 100%; min-height: 48px;">
		<div class="row" style="padding: 8px;" id = "filterBar">
			<div class="col-md-4">
				<div class="row">
				
				</div>
			</div>

			<div class="col-md-4 text-center remove-pad">
            	<h2 class="minimal" id="filter-title">Purchase Requests</h2>
			</div>
			
			<div class="col-md-4">
			<?php if($search): ?>
				<button class="btn btn-sm btn-primary part-modal-show pull-right" style="cursor: pointer" data-partid="">
					<i class="fa fa-plus" aria-hidden="true"></i>
				</button>
			<?php endif; ?>
			</div>

		</div>
	</div>
	<div id="pad-wrapper">
		<div class="row">
			<table class="table heighthover heightstriped table-condensed p_table">
				<thead>
					<tr>
						<th class="col-md-1"></th>
						<th class="col-md-1">Repair#</th>
						<th class="col-md-3">Component</th>
						<th class="col-md-1">QTY</th>
						<th class="col-md-2">Order#</th>
						<th class="col-md-2">Tech</th>
						<th class="col-md-2">Action</th>
					</tr>
				</thead>
				<tbody>
					<?php 
						foreach($itemList as $part): 
							$parts = explode(' ',$part['part']);
							$part_name = $parts[0];
					?>
						<tr>
							<td><div class="product-img"><img class="img" src="/img/parts/<?php echo $part_name; ?>.jpg" alt="pic" data-part="<?php echo $part_name; ?>"></div></td>
							<td><?=$part['ro_number'];?> <a href="/order_form.php?ps=ro&on=<?=$part['ro_number'];?>"><i class="fa fa-arrow-right"></i></a></td>
							<td><?=(display_part($part['partid'], true) ? display_part($part['partid'], true) : $part['part']); ?></td>
							<td><?=$part['qty'];?></td>
							<td><?=$part['po_number'];?> 
								<?php if($part['po_number']) { ?> <a href="/order_form.php?on=<?=$part['po_number'];?>&amp;ps=p"><i class="fa fa-arrow-right"></i></a><?php } ?> </td>
							<td><?=getUser($part['techid']);?></td>
							<td>
								<?php if(!$part['po_number']) { ?>
								<form class="disable_form" method="POST">
									<a href="/order_form.php?ps=Purchase&s=<?=$part['partid'];?>&repair=<?=getRepairItemId($part['ro_number'], $part['partid']);?>">
										<i style="margin-right: 5px;" class="fa fa-pencil" aria-hidden="true"></i>
									</a>
									<input type="text" name="purchase_request_id" class="hidden" value="<?=$part['id'];?>">
									<a class="disable_trash" style="cursor: pointer;">
										<i style="margin-right: 5px;" class="fa fa-trash" aria-hidden="true"></i>
									</a>
								</form>
								<?php } ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
	        </table>
		</div>
	</div>

	<?php include_once 'inc/footer.php'; ?>

    <script type="text/javascript">
    	(function($){
    		$(document).on('click', '.disable_trash', function(e) {
    			if( !confirm('Are you sure you want to cancel this purchase request?')) {
            		event.preventDefault();
    			} else {
    				$(this).closest('form').submit();
    			}
    		});
    	})(jQuery);
    </script>

</body>
</html>
