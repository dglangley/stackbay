<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/display_part.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/form_handle.php';
	
	$search = trim($_REQUEST['s'] ? $_REQUEST['s'] : $_REQUEST['search']);

	//Query items from parts table
	$itemList = hecidb($search);
/*
	if(!$search) {
		$query = "SELECT * FROM parts ;";
		$result = qdb($query) OR die(qe());
			
		while ($row = $result->fetch_assoc()) {
			$itemList[] = $row;
		}
	} else {
		$itemList = hecidb($search); 
	}
*/
	
?>

<!------------------------------------------------------------------------------------------->
<!-------------------------------------- HEADER OUTPUT -------------------------------------->
<!------------------------------------------------------------------------------------------>
<!DOCTYPE html>
<html>
<head>
	<title>Parts Database</title>
	<?php
		//Standard headers included in the function
		include_once $rootdir.'/inc/scripts.php';
		include_once $rootdir. '/modal/image.php';
		include_once $rootdir. '/modal/parts.php';
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
            	<h2 class="minimal" id="filter-title">Parts Database</h2>
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
						<th class="col-md-1 text-center"></th>
						<th class="col-md-4 text-center">Description</th>
						<th class="col-md-4 text-center">Keywords (DB Index)</th>
						<th class="col-md-1 text-center">Classification</th>
						<th class="col-md-1 text-center">ID</th>
						<th class="col-md-1 text-center">Action</th>
					</tr>
				</thead>
				<tbody>
					<?php 
						foreach($itemList as $part): 
							$parts = explode(' ',$part['part']);
							$part_name = $parts[0];

							$keywords = '';
							$query = "SELECT keyword FROM keywords k, parts_index pi ";
							$query .= "WHERE pi.partid = '".$part['id']."' AND pi.keywordid = k.id; ";
							$result = qedb($query);
							while ($r = qrow($result)) {
								if ($keywords) { $keywords .= ', '; }
								$keywords .= $r['keyword'];
							}
					?>
						<tr>
							<td><div class="product-img"><img class="img" src="/img/parts/<?php echo $part_name; ?>.jpg" alt="pic" data-part="<?php echo $part_name; ?>"></div></td>
							<td>
<?php if ($part['heci']) { ?>
								<a class="indexer" data-search="<?=$part['heci7']?>" data-type="heci" style="cursor: pointer" title="Re-index <?=$part['heci7']?>" data-toggle="tooltip" data-placement="bottom">
									<i style="margin-right: 5px;" class="fa fa-database" aria-hidden="true"></i>
								</a>
<?php } else { ?>
								<a class="indexer" data-search="<?=$part['primary_part']?>" data-type="part" style="cursor: pointer" title="Re-index <?=$part['primary_part']?>" data-toggle="tooltip" data-placement="bottom">
									<i style="margin-right: 5px;" class="fa fa-database" aria-hidden="true"></i>
								</a>
<?php } ?>
								<?=(display_part($part['id'], true) ? display_part($part['id'], true) : $part['part']); ?>
							</td>
							<td class="text-left"><span class="info"><?=strtoupper($keywords);?></span></td>
							<td class="text-center"><?=ucwords($part['classification']);?></td>
							<td class="text-center"><?=$part['id']?></td>
							<td class="text-center">
								<a class="part-modal-show" data-partid="<?=$part['id']?>" style="cursor: pointer">
									<i style="margin-right: 5px;" class="fa fa-pencil" aria-hidden="true"></i>
								</a>
								<a class="indexer" data-search="<?=$part['id']?>" data-type="id" style="cursor: pointer" title="Re-index <?=$part['id']?>" data-toggle="tooltip" data-placement="bottom">
									<i style="margin-right: 5px;" class="fa fa-database" aria-hidden="true"></i>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>

					<?php if (count($itemList)==0) { ?>
						<tr>
							<td colspan="5" class="text-center">
								- There are no results to your search "<?=$search;?>". Consider creating the part at the top right of this view. -
							</td>
						</tr>
					<?php } ?>
				</tbody>
	        </table>
		</div>
	</div>

	<?php include_once 'inc/footer.php'; ?>

<script type="text/javascript">
	$(document).ready(function() {
		$(".indexer").on('click',function() {
			$('#loader-message').html('Please wait while this item is re-indexed...');
			$('#loader').show();

			var search = $(this).data('search');
			var type = $(this).data('type');

			$.ajax({
				url: 'json/indexer.php',
				type: 'get',
				data: { 'search': search, 'search_type': type },
				settings: {async:true},
				error: function(xhr, desc, err) {
				},
				success: function(json, status) {
					if (json.message && json.message!='Success') {
						modalAlertShow('Error',json.message,false);

						return;
					}
				},
				complete: function(result) {
					$('#loader').hide();
					toggleLoader('Re-indexing Complete');
				},
			});
		});
	});
</script>

</body>
</html>
