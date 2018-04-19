<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$subscriptionid = 0;
	if(isset($_REQUEST['subscription'])) { $subscriptionid = $_REQUEST['subscription']; }

	function getSubscriptions() {
		$subscriptions = array();

		$query = "SELECT * FROM subscriptions";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$subscriptions[] = $r;
		}

		return $subscriptions;
	}

	function buildSubscriptionRows() {
		$htmlRows= '';
		$subscriptions = getSubscriptions();

		foreach($subscriptions as $r) {
			$htmlRows .= '<tr>
							<td>'.$r['nickname'].'</td>
							<td>'.$r['subscription'].'</td>
							<td>
								<a href="subscriptions.php?subscription='.$r['id'].'" class="pull-right"><i class="fa fa-pencil fa-4" style="margin-right: 10px; margin-top: 4px;" aria-hidden="true"></i></a>
							</td>
						  </tr>';
		}
		
		return $htmlRows;
	}

	function getSubscription($subid) {
		$subscription = array();

		$query = "SELECT * FROM subscriptions s WHERE id = ".res($subid).";";
		$result = qedb($query);

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);

			$subscription = $r;
		}

		return $subscription;
	}

	function getSubEmails($subid) {
		$emails = array();

		$query = "SELECT * FROM subscription_emails s WHERE subscriptionid = ".res($subid).";";
		$result = qedb($query);

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);

			$emails = $r;
		}

		return $emails;
	}

	function getUserEmails(){
		$user_emails = array();

		$query = "SELECT e.*, u.id as userid FROM usernames u, emails e WHERE u.emailid = e.id;";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$user_emails[] = $r;
		}

		return $user_emails;
	}

	if($subscriptionid) {
		$subscription = getSubscription($subscriptionid);
		$subscription_emails = getSubEmails($subscriptionid);

		$emails = getUserEmails();
		$user_emails = '';

		foreach($emails as $email) {
			$user_emails .= '<option value="">'.$email['email'].'</option>';
		}
	}

	// print_r($subscription);

	$TITLE = ($subscription['nickname']?:'Subscriptions');
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
			<button type="button" class="btn btn-success btn-submit pull-right"><i class="fa fa-save"></i> Save</button>
		</div>
	</div>

	</form>
</div>

<div id="pad-wrapper">
	<form class="form-inline" method="get" action="subscriptions_edit.php" enctype="multipart/form-data" >
		<div class="col-md-2">
            <?php include_once 'inc/user_dash_sidebar.php'; ?>
        </div>

        <div class="col-md-10">
        	<?php if(! $subscriptionid) { ?>
				<table class="table heighthover heightstriped table-condensed">
					<thead>
						<tr>
							<th>Name</th>
							<th>Email</th>
							<th class="text-right">Action</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>
								<div class="input-group pull-left">
									<input type="text" class="form-control input-sm" name="subscription_name" placeholder="Name">
								</div>
							</td>
							<td>
								<input type="text" class="form-control input-sm" name="subscription_email" placeholder="Email">
							</td>
							<td>
								<button class="btn btn-success btn-sm pull-right" name="type" value="add_expense"><i class="fa fa-save" aria-hidden="true"></i></button>
							</td>
						</tr>
						<?=buildSubscriptionRows();?>		
					</tbody>
		        </table>
	        <?php } else { ?>
		        <div class="row">
		        	<div class="col-sm-6">
		        		<input class="form-control input-sm" type="text" name="name" placeholder="Name" value="<?=$subscription['nickname']?>">
		        	</div>
		        	<div class="col-sm-6">
		        		<input class="form-control input-sm" type="text" name="subscription" placeholder="Subscription" value="<?=$subscription['subscription'];?>">
		        	</div>
	        	</div>

	        	<div class="row" style="margin-top: 25px;">
	        		<div class="col-sm-12">
	        			<select name="emailids[]" size="6" class="form-control" multiple="">
                            <?=$user_emails;?>
                        </select>
	        		</div>
	        	</div>
	        <?php } ?>
	    </div>
	</form>
</div><!-- pad-wrapper -->

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<script type="text/javascript">
	$(document).ready(function() {
	});
</script>

</body>
</html>
