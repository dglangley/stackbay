<?php
	include_once $_SERVER["ROOT_DIR"] . '/inc/dbconnect.php';

	$title = "Financial Accounts Management";

	function getFinanceTypes() {
		$types = array();

		$query = "SELECT * FROM finance_types;";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$types[] = $r;
		}

		return $types;
	}

	function getFinanceType($type_id) {
		$type = '';

		$query = "SELECT type FROM finance_types WHERE id = ".fres($type_id).";";
		$result = qedb($query);

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);
			$type = $r['type'];
		}

		return $type;
	}

	function getFinanceAccounts() {
		$accounts = array();

		$query = "SELECT * FROM finance_accounts WHERE status = 'Active';";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$accounts[] = $r;
		}

		return $accounts;
	}

	$accounts = getFinanceAccounts();
?>

<!------------------------------------------------------------------------------------------->
<!-------------------------------------- HEADER OUTPUT -------------------------------------->
<!------------------------------------------------------------------------------------------>
<!DOCTYPE html>
<html>
<head>
	<title><?=$title?></title>
	<?php
		//Standard headers included in the function
		include_once $_SERVER["ROOT_DIR"] . '/inc/scripts.php';
	?>
	<style>
	</style>
</head>

<body class="sub-nav accounts-body">
	
	<?php include 'inc/navbar.php'; ?>

	<form action="finance_edit.php" method="POST">

		<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px;">
			<div class="row" style="padding: 8px;" id="filterBar">
				<div class="col-md-4 mobile-hide" style="max-height: 30px;">
					<div class="col-md-3">
						
					</div>

					<div class="col-md-9 date_container mobile-hid remove-pad">
						
					</div>
				</div>

				<div class="text-center col-md-4 remove-pad">
					<h2 class="minimal" id="filter-title"><?=$title?></h2>
				</div>

				<div class="col-md-4" style="padding-left: 0; padding-right: 10px;">
					<div class="col-md-2 col-sm-2 phone-hide" style="padding: 5px;">

					</div>
					<div class="col-md-2 col-sm-2 col-xs-3">

					</div>

					<div class="col-md-8 col-sm-8 col-xs-9 remove-pad">
						<button class="btn btn-success btn-sm save_sales pull-right" type="submit" data-validation="left-side-main" style="padding: 5px 25px;">
							CREATE					
						</button>
					</div>
				</div>
			</div>
		</div>
		<div id="pad-wrapper">

			<div class="row">
				<table class="table heighthover heightstriped table-condensed p_table">
					<thead>
						<tr>
							<th>Institution</th>
							<th>Nickname</th>
							<th>Account Number</th>
							<th>Type</th>
							<th>Action</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>
								<input class="form-control" type="text" name="institution" value="">
							</td>
							<td>
								<input class="form-control" type="text" name="nickname" value="">
							</td>
							<td>
								<input class="form-control" type="text" name="digits" value="">
							</td>
							<td>
								<select class="form-control select2" name="typeid">
									<option selected="" value="null">- Select Type -</option>
									<?php
										foreach(getFinanceTypes() as $type) {
											echo '<option value="'.$type['id'].'">' .$type['type']. '</option>';
										}
									?>
								</select>
							</td>
							<td></td>
						</tr>
						<?php if(! empty($accounts)) { foreach($accounts as $account) { ?>
							<tr>
								<td><?=$account['bank'];?></td>
								<td><?=$account['nickname'];?></td>
								<td><?=$account['account_number'];?></td>
								<td><?=getFinanceType($account['type_id']);?></td>
								<td><button type="submit" name="deleteid" value="<?=$account['id'];?>"><i class="fa fa-trash"></i></button></td>
							</tr>
						<?php } } ?>
					</tbody>
		        </table>
			</div>
		</div>
	</form>

	<?php include_once 'inc/footer.php'; ?>

    <script type="text/javascript">
    </script>

</body>
</html>
