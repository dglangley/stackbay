<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$locationid = 0;
	if(isset($_REQUEST['locationid'])) { $locationid = $_REQUEST['locationid']; }

	function getLocations() {
		$locations = array();

		$query = "SELECT * FROM locations";
		$result = qedb($query);

		while($r = qrow($result)) {
			$locations[] = $r;
		}

		return $locations;
	}

	function buildLocationRows() {
		$htmlRows= '';
		$locations = getLocations();

		foreach($locations as $r) {
			$htmlRows .= '<tr>
							<td>'.$r['place'].'</td>
							<td>'.$r['instance'].'</td>
							<td>
								<a href="location_management.php?locationid='.$r['id'].'" class="pull-right"><i class="fa fa-pencil fa-4" style="margin-right: 10px; margin-top: 4px;" aria-hidden="true"></i></a>
							</td>
						  </tr>';
		}
		
		return $htmlRows;
	}

	function getLocation($locationid) {
		$location = array();

		$query = "SELECT * FROM locations WHERE id = ".res($locationid).";";
		$result = qedb($query);

		if(qnum($result)) {
			$r = qrow($result);

			$location = $r;
		}

		return $location;
	}

	if($locationid) {
		$location = getLocation($locationid);
	}

	$TITLE = ($location['place']?$location['place'].'-'.$location['instance']:'Locations');
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo $TITLE; ?></title>
	<?php
		/*** includes all required css includes ***/
		include_once 'inc/scripts.php';
	?>

	<!-- any page-specific customizations -->
	<style type="text/css">
	</style>
</head>
<body>

<?php include_once 'inc/navbar.php'; ?>

<!-- FILTER BAR -->
<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px; max-height:60px;">
	<form class="form-inline" method="get" action="" enctype="multipart/form-data" id="filters-form" >

	<div class="row" style="padding:8px">
		<div class="col-sm-2">
		</div>
		<div class="col-sm-2">
		</div>
		<div class="col-sm-4 text-center">
			<h2 class="minimal"><?php echo $TITLE; ?></h2>
			<span class="info"></span>
		</div>
		<div class="col-sm-2">
		</div>
		<div class="col-sm-2">
            <?php if($locationid) { ?>
			    <button type="button" id="submit_button" class="btn btn-success pull-right"><i class="fa fa-save"></i> Save</button>
            <?php } ?>
		</div>
	</div>

	</form>
</div>

<div id="pad-wrapper">
	<form class="form-inline" method="get" action="location_edit.php" enctype="multipart/form-data" id="form-save">
		<div class="col-md-2">
            <?php include_once 'inc/user_dash_sidebar.php'; ?>
        </div>

        <div class="col-md-10">
            <?php if(! $locationid) { ?>
				<table class="table heighthover heightstriped table-condensed">
					<thead>
						<tr>
							<th>Place</th>
							<th>Instance</th>
							<th class="text-right">Action</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>
                                <input type="text" class="form-control input-sm" name="place" placeholder="Place">
							</td>
							<td>
								<input type="text" class="form-control input-sm" name="instance" placeholder="Instance">
							</td>
							<td>
								<button class="btn btn-success btn-sm pull-right" name="type" value="add_expense"><i class="fa fa-save" aria-hidden="true"></i></button>
							</td>
						</tr>
						<?=buildLocationRows();?>		
					</tbody>
		        </table>
	        <?php } else { ?>
                <input type="hidden" name="locationid" value="<?=$locationid;?>">
		        <div class="row">
		        	<div class="col-sm-6">
		        		<input class="form-control input-sm" type="text" name="place" placeholder="Place" value="<?=$location['place']?>">
		        	</div>
		        	<div class="col-sm-6">
		        		<input class="form-control input-sm" type="text" name="instance" placeholder="Instance" value="<?=$location['instance'];?>">
		        	</div>
	        	</div>
            <?php } ?>
        </div>
	</form>
</div><!-- pad-wrapper -->

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<script type="text/javascript">
        (function($){
            //Allow users to select without having to CTRL + Click
            $('option').mousedown(function(e) {
                e.preventDefault();
                $(this).prop('selected', $(this).prop('selected') ? false : true);
                return false;
            });

            $(document).on("click", "#submit_button", function(e) {
            	$('#form-save').submit();
            });
        })(jQuery);
    </script>

</body>
</html>
