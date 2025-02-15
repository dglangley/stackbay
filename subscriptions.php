<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getSubEmail.php';

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

		$query = "SELECT emailid FROM subscription_emails s WHERE subscriptionid = ".res($subid).";";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$emails[] = $r['emailid'];
		}

		return $emails;
	}

	function getUserEmails(){
		$user_emails = array();

		$query = "SELECT e.*, u.id as userid FROM usernames un, users u, contacts c, emails e WHERE un.emailid = e.id AND u.id = un.userid AND u.contactid = c.id AND c.status = 'Active' AND c.companyid = 25;";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$user_emails[] = $r;
		}

		return $user_emails;
	}

	if($subscriptionid) {
		$subscription = getSubscription($subscriptionid);
		$subscription_emails = getSubEmails($subscriptionid);

		// print_r($subscription_emails);

		$emails = getUserEmails();
		$user_emails = '';

		foreach($emails as $email) {
			$user_emails .= '<option value="'.$email['id'].'" '.(in_array($email['id'], $subscription_emails) ? 'selected' : '').'>'.$email['email'].'</option>';
		}
	}

	// $email_name = "component_request";
	// print_r(getSubEmail($email_name));

	// $email_name = "so_completed";
	// print_r(getSubEmail($email_name));

	// $email_name = "po_received";
	// print_r(getSubEmail($email_name));

	// $email_name = "repair_complete";
	// print_r(getSubEmail($email_name));

	// $email_name = "service_complete";
	// print_r(getSubEmail($email_name));

	// $email_name = "timesheet_email";
	// print_r(getSubEmail($email_name));

	// $email_name = "sourcing_request";
	// print_r(getSubEmail($email_name));

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
			<button type="button" id="submit_button" class="btn btn-success pull-right"><i class="fa fa-save"></i> Save</button>
		</div>
	</div>

	</form>
</div>

<div id="pad-wrapper">
	<form class="form-inline" method="get" action="subscriptions_edit.php" enctype="multipart/form-data" id="form-save">
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
									<input type="text" class="form-control input-sm" name="name" placeholder="Name">
								</div>
							</td>
							<td>
								<input type="text" class="form-control input-sm" name="subscription" placeholder="Email Type">
							</td>
							<td>
								<button class="btn btn-success btn-sm pull-right" name="type" value="add_expense"><i class="fa fa-save" aria-hidden="true"></i></button>
							</td>
						</tr>
						<?=buildSubscriptionRows();?>		
					</tbody>
		        </table>
	        <?php } else { ?>
	        	<input type="hidden" name="subid" value="<?=$subscriptionid;?>">
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
	        			<select name="emailids[]" size="25" class="form-control" multiple="">
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
