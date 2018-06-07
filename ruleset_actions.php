<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getRulesets.php';

	// Getters
	include_once $_SERVER["ROOT_DIR"].'/inc/getPart.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getSupply.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/ruleset_script.php';

	$rulesetid = 0;
	if(isset($_REQUEST['rulesetid'])) { $rulesetid = $_REQUEST['rulesetid']; }

	$ACTIONS = array();

	function buildRulesetRows($rulesetid) {
		$htmlRows= '';
		$rulesets = getRulesets();

		foreach($rulesets as $r) {
			$htmlRows .= '<a class="list-group-item '.($r['id'] == $rulesetid ? 'active' : '').' ?>" href="ruleset_actions.php?rulesetid='.$r['id'].'">'.$r['name'].'</a>';
		}
		
		return $htmlRows;
	}

	function buildRulesetForm($ruleset) {
		global $ACTIONS;

		$ACTIONS = getRulesetActions($ruleset['id']);

		$rowHTML = '
				<div class="row">
		        	<div class="col-sm-3">
		        		<input class="form-control input-sm" type="text" readonly value="'.$ruleset['name'].'">
		        	</div>
		        	<div class="col-sm-3">
						<select class="form-control select2" name="action" placeholder="- Select Action -">
							<option value="">- Select Action -</option>
							<option value="email" '.($ACTIONS['action'] == 'email' ? 'selected' : '').'>Email</option>
						</select>
					</div>
					<div class="col-sm-3">
						<select class="form-control select2" name="option" placeholder="- Select Action -">
							<option value="">- Select Market -</option>
							<option value="Supply" '.($ACTIONS['options'] == 'Supply' ? 'selected' : '').'>Supply</option>
							<option value="Demand" '.($ACTIONS['options'] == 'Demand' ? 'selected' : '').'>Demand</option>
						</select>
		        	</div>
	        	</div>

				<div class="row" style="margin-top: 25px;">
					<div class="col-sm-3">
						<select class="form-control select2" name="days" placeholder="- Select Occurence -">
							<option value="">- Select Occurence -</option>
							<option value="1" '.($ACTIONS['days'] == '1' ? 'selected' : '').'>Daily</option>
							<option value="7" '.($ACTIONS['days'] == '7' ? 'selected' : '').'>Weekly</option>
							<option value="14" '.($ACTIONS['days'] == '14' ? 'selected' : '').'>Biweekly</option>
							<option value="30" '.($ACTIONS['days'] == '30' ? 'selected' : '').'>Monthly</option>
						</select>
					</div>

					<div class="col-sm-3">
						<div class="input-group date datetime-picker" data-format="HH:mm:ss">
							<input type="text" name="timestamp" class="form-control input-sm" value="'.$ACTIONS['time'].'" placeholder="Time (24 Hour)">
							<span class="input-group-addon">
								<span class="fa fa-calendar"></span>
							</span>
						</div>
					</div>
				</div>

				<HR>
		';

		return $rowHTML;
	}

	function buildAccordion($rulesetid) {
		global $ACTIONS;
		
		$string_searchs = getRulesetData($rulesetid, false);

		$groupedParts = array();
		$grouped = array();

		if(! empty($string_searchs)){
			// Now we need to sourced all the companies that either 
			// A. We want to quote from
			// B. We want to sell to

			foreach($string_searchs as $heci) {

				$results = hecidb($heci, 'heci');
				$partids = array();

				foreach($results as $partid => $part) {
					$partids[] = $partid;
				}

				$results = array();
				
				// Only get the static data, no API's or force
				if($ACTIONS['options'] == 'Demand') {
					$results = getDemand($partids, false);
				} else {
					$results = getSupply($partids, false);
				}
				$companys = array();

				foreach($results['results'] as $data) {
					if($company = array_column($data, 'company')) {
						$companys = $company;
					}
				}

				$groupedParts[$heci] = $companys; 
			}

			// Sort each company and the partids they have from the list of parts
			foreach($groupedParts as $heci => $data) {
				foreach($data as $company) {
					// if(! isset($grouped[$company]['companyid'])) {
					// 	$grouped[$company]['companyid'] = getCompany(trim($company),'name','id');
					// }
					$grouped[(getCompany(trim($company),'name','id')?:trim($company))]['parts'][] = $heci;
				}
			}

			// print_r($grouped);
		}

		// print "<pre>".print_r($ACTIONS,true)."</pre>";
		$rowHTML = '
			<input type="hidden" value="'.$ACTIONS['option'].'" name="type">
			<div id="accordion">
		';

		foreach($grouped as $companyid => $r) {
			$rowHTML .= '
				<input type="hidden" name="email['.$companyid.'][type]" value="'.$ACTIONS['options'].'">

				<div class="card">
					<div class="card-header" id="heading'.$companyid.'">
						<h5 class="mb-0">
							<a class="btn btn-link" data-toggle="collapse" data-target="#collapse'.$companyid.'" aria-expanded="false" aria-controls="collapseOne">
								'.getCompany($companyid).' <i class="fa fa-caret-down" aria-hidden="true"></i>
							</a>

							<input type="checkbox" class="pull-right email_checkbox" style="margin-right: 10px; margin-top: 10px;" name="email['.$companyid.'][status]" checked>
						</h5>
					</div>

					<div id="collapse'.$companyid.'" class="collapse" aria-labelledby="heading'.$companyid.'" data-parent="#accordion">
						<div class="card-body" style="padding: 0 20px;">
							<BR>
							<textarea class="form-control text-left" rows="8" name="email['.$companyid.'][content]">'.(($ACTIONS['options'] == 'Demand')?'We have the following available FOR SALE, ready to ship immediately. If interested in any, please make an offer.':'Please quote:').'
						';
			foreach($r['parts'] as $partid => $part) {
				$rowHTML .= "\n" . $part;
			}
			$rowHTML .= '					
							</textarea>
							<BR><BR>
						</div>
					</div>
				</div>
			';
		}

		$rowHTML .= '</div>';

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
		.card {
			border-top: 1px solid #CCC;
			border-left: 1px solid #CCC;
			border-right: 1px solid #CCC;

			border-radius: 3px;
		}

		.card-header, .card-content {
			border-bottom: 1px solid #CCC;

			border-radius: 3px;
		}
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
	<div class="col-md-2">
		<h3 class="text-center pb-20">Rulesets</h3>

		<div class="list-group">
			<!-- Get the ID of admin and print it out, in case ID's change as long as Admin exists the ID will be pulled -->
			<?=buildRulesetRows($rulesetid);?>
		</div>

	</div>

	<div class="col-md-10">
		<form class="form-inline" method="get" action="ruleset_edit.php" enctype="multipart/form-data" id="form-save">
			<input type="hidden" name="rulesetid" value="<?=$rulesetid?>">
			<input type="hidden" name="ruleset_action" value="true">
			<?=($rulesetid?buildRulesetForm($ruleset):'');?>
		</form>

		<form class="form-inline" method="get" action="ruleset_emailer.php" enctype="multipart/form-data">
			<h4 class="text-center">Generated Emails</h4>

			<div class="pull-right" style="margin-top: -30px;">
				<button class="pull-right btn btn-sm btn-primary" style="" type="submit"><i class="fa fa-envelope-o" aria-hidden="true"></i></button>
				<i class="fa fa-check-square-o pull-right toggle_select" aria-hidden="true" style="margin-top: 10px; margin-right: 20px; cursor: pointer;"></i>
			</div>

			<BR>

			<input type="hidden" name="rulesetid" value="<?=$rulesetid?>">
			<?=($rulesetid?buildAccordion($rulesetid):'');?>
		</form>
	</div>
</div><!-- pad-wrapper -->

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<script type="text/javascript">
        (function($){
            $(document).on("click", "#submit_button", function(e) {
            	$('#form-save').submit();
			});
			
			$('.toggle_select').click(function(e){
				var checkBoxes = $(".email_checkbox");
        		checkBoxes.prop("checked", !checkBoxes.prop("checked"));
			});
        })(jQuery);
    </script>

</body>
</html>
