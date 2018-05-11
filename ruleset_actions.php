<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getRulesets.php';

	$rulesetid = 0;
	if(isset($_REQUEST['rulesetid'])) { $rulesetid = $_REQUEST['rulesetid']; }

	function buildRulesetRows($rulesetid) {
		$htmlRows= '';
		$rulesets = getRulesets();

		foreach($rulesets as $r) {
			$htmlRows .= '<a class="list-group-item '.($r['id'] == $rulesetid ? 'active' : '').' ?>" href="ruleset_actions.php?rulesetid='.$r['id'].'">'.$r['name'].'</a>';
		}
		
		return $htmlRows;
	}

	function getRulesetActions($rulesetid) {
		$actions = array();

		$query = "SELECT * FROM ruleset_actions WHERE rulesetid = ".res($rulesetid)." LIMIT 1;";
		$result = qedb($query);

		// For now set it to only 1 result
		while($r = mysqli_fetch_assoc($result)) {
			$actions = $r;
		}

		return $actions;
	}

	function buildRulesetForm($ruleset) {

		$actions = getRulesetActions($ruleset['id']);

		$rowHTML = '
				<div class="row">
		        	<div class="col-sm-6">
		        		<input class="form-control input-sm" type="text" readonly value="'.$ruleset['name'].'">
		        	</div>
		        	<div class="col-sm-6">
						<select class="form-control select2" name="action" placeholder="- Select Action -">
							<option value="">- Select Action -</option>
							<option value="email" '.($actions['action'] == 'email' ? 'selected' : '').'>Email</option>
						</select>
		        	</div>
	        	</div>

				<div class="row" style="margin-top: 25px;">
					<div class="col-sm-6">
						<select class="form-control select2" name="days" placeholder="- Select Occurence -">
							<option value="">- Select Occurence -</option>
							<option value="1" '.($actions['days'] == '1' ? 'selected' : '').'>Daily</option>
							<option value="7" '.($actions['days'] == '7' ? 'selected' : '').'>Weekly</option>
							<option value="14" '.($actions['days'] == '14' ? 'selected' : '').'>Biweekly</option>
							<option value="30" '.($actions['days'] == '30' ? 'selected' : '').'>Monthly</option>
						</select>
					</div>

					<div class="col-sm-6">
						<div class="input-group date datetime-picker" data-format="HH:mm:ss">
							<input type="text" name="timestamp" class="form-control input-sm" value="'.$actions['time'].'" placeholder="Time (24 Hour)">
							<span class="input-group-addon">
								<span class="fa fa-calendar"></span>
							</span>
						</div>
					</div>
				</div>
		';


		return $rowHTML;
	}

	if($rulesetid){
		$ruleset = getRuleset($rulesetid);
	}

	$TITLE = ($ruleset['name'] . ' Actions'?:'Ruleset Actions');
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
			<a href="/miner.php?rulesetid=<?= $rulesetid; ?>" class="btn btn-default btn-sm">
				<img src="img/pickaxe.png" style="width:14px; vertical-align:top; margin-top:2px">
			</a>
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
			<?php if($rulesetid) { ?>
				<button type="button" id="submit_button" class="btn btn-success pull-right"><i class="fa fa-save"></i> Save</button>
			<?php } ?>
		</div>
	</div>

	</form>
</div>

<div id="pad-wrapper">
	<form class="form-inline" method="get" action="ruleset_edit.php" enctype="multipart/form-data" id="form-save">
		<div class="col-md-2">
			<h3 class="text-center pb-20">Rulesets</h3>

			<div class="list-group">
				<input type="hidden" name="ruleset_action" value="true">
				<!-- Get the ID of admin and print it out, in case ID's change as long as Admin exists the ID will be pulled -->
				<?=buildRulesetRows($rulesetid);?>
			</div>

        </div>

        <div class="col-md-10">
			<input type="hidden" name="rulesetid" value="<?=$rulesetid?>">

			<?= ($rulesetid?buildRulesetForm($ruleset):''); ?>
	    </div>
	</form>
</div><!-- pad-wrapper -->

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<script type="text/javascript">
        (function($){
            $(document).on("click", "#submit_button", function(e) {
            	$('#form-save').submit();
            });
        })(jQuery);
    </script>

</body>
</html>
