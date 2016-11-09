<?php
	//Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];
?>

<!DOCTYPE html>
<html>
<!-- Declaration of the standard head with S&D home set as title -->
<head>
	<title>Ghost Settings</title>
	<?php
		//Standard headers included in the function
		include_once $rootdir.'/inc/scripts.php';
		include_once $rootdir.'/inc/dbconnect.php';
		include_once $rootdir.'/inc/getCompany.php';
		include_once $rootdir.'/inc/pipe.php';
		
			if (!$_POST['new'] && !empty($_POST)){
				foreach ($_POST as $num => $x) {
						$update = "UPDATE `ghosting_values` SET `ghost_value`=$x WHERE `companyid`= $num";
						qdb($update);
					};
			}
			else if($_POST['new']){
				$drop = "DELETE FROM `ghosting_values` WHERE `companyid` = ".$_POST['companyid'].";";
				qdb($drop);
				$insert = "INSERT INTO `ghosting_values` VALUES (".$_POST['companyid'].",".$_POST['new'].");";
				qdb($insert);
			}
			else{
				$drop = "DELETE FROM `ghosting_values` WHERE `ghost_value` = 0;";

			}

		$query = "Select * from ghosting_values where ghost_value > 0;";
		$results = qdb($query);
		print_r($results);
		
		function result_out($company,$value,$i){
					echo'
					<tr>
						<td>
							<input type="text" class="form-control ghost_company" name="'.$company.'" readonly="readonly" value = "'.getCompany($company).'">
						</td>
                        <td>
						    <div class="input-group" height="22">
						      <input type="text" class="form-control ghost_percent" name = "'.$company.'"value = "'.$value.'">
								<span class="input-group-addon">
								<i class="fa fa-percent" aria-hidden="true"></i>
								</span>
						    </div>
						</td>
						<td class="ghost_delete">
							<i class="fa fa-times" aria-hidden="true" style="color:red;"></i>
						</td>
					</tr>';
		}

	?>
</head>
<?php include 'inc/navbar.php'; ?>
<body>
	<div class="container-fluid" style="margin-top:100px;">
	<form class="form-inline" method="post" action="">

		<div class="row">
			<div class="col-md-4"></div>
			<div class="col-md-4">
				<table id="ghost">
					<?php
					$i = 0;
					foreach ($results as $row) {
						result_out($row['companyid'],$row['ghost_value'],$i);
						$i++;
					}
					?>

					<tr>
						<td>
							<select name="companyid" class="company-selector">
								<option value="">- Select a Company -</option>
								<?php 
								if ($company_filter) {echo '<option value="'.$company_filter.'" selected>'.(getCompany($company_filter)).'</option>'.chr(10);}
								else {echo '<option value="">- Select a Company -</option>'.chr(10);} 
								?>
							</select>
						</td>
						<td>
							<div class="input-group">
							    <div class="input-group">
							      <input type="text" class="form-control" name = "new">
							      <span class="input-group-btn">
							      	<button class="btn btn-secondary"  type = "submit" name="save_changes" style="padding-left:9px;padding-right:9px;width=10">
							        	<i class="fa fa-plus" aria-hidden="true"></i>
						        	</button>
							      </span>
							    </div>
                            </div>
                        </td>

					</tr>
					<td>
						<button class="btn-flat primary" type="submit" style="margin:0 auto" id = "save_changes">Submit Changes</button>
					</td>

				</table>

			</div>
			<div class="col-md-4"></div>
		</div>
	</form>	
	</div>
	<?php include_once 'inc/footer.php'; ?>
</body>
</html>
