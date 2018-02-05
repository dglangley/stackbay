<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$TITLE = 'Timesheet';
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo $TITLE; ?></title>
	<?php
		/*** includes all required css includes ***/
		include_once 'inc/scripts.php';
	?>
    <link rel="stylesheet" href="css/compiled/signin.css" type="text/css" media="screen" />

	<!-- any page-specific customizations -->
	<style type="text/css">
	</style>
</head>
<body>

<?php include_once 'inc/navbar.php'; ?>

<!-- FILTER BAR -->
<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px; max-height:60px;">
	<form class="form-inline" method="get" action="" enctype="multipart/form-data" id="filters-form" >
	<input type="hidden" name="user" value="<?=$user;?>">
	<input type="hidden" name="taskid" value="<?=$taskid;?>">

	<div class="row" style="padding:8px">
		<div class="col-sm-1">
		</div>
		<div class="col-sm-1">
		</div>
		<div class="col-sm-1">
		</div>
		<div class="col-sm-2">
		</div>
		<div class="col-sm-2 text-center">
			<h2 class="minimal"><?php echo $TITLE; ?></h2>
			<span class="info"></span>
		</div>
		<div class="col-sm-2">
		</div>
		<div class="col-sm-1">
		</div>
		<div class="col-sm-2">
		</div>
	</div>

	</form>
</div>

<div id="pad-wrapper">
	<div class="text-center">
        <div class="login-wrapper">
            <div class="box">
                <div class="content-wrap">
                    <h6>Enter your password</h6>

                    <?php if(isset($loginErr) AND $loginErr) { ?>
                        <div class="alert alert-danger">
                            <?php 
                                echo $loginErr; 
                            ?>
                        </div>
                    <?php } ?>

                    <form action="timesheet.php" method="POST" accept-charset="UTF-8">
					<input type="hidden" name="user" value="<?=$user;?>">
					<input type="hidden" name="taskid" value="<?=$taskid;?>">

                        <div class="row">
                            <div class="col-md-12">
                                <input name="password" class="form-control" type="password" placeholder="Password"  value="<?php echo (isset($_POST['password']) ? $_POST['password'] : ''); ?>" autocomplete="off">
                            </div>
                        </div>

                        <div class="action">
                            <button class="btn btn-lg btn-primary login" type="submit" name="Submit">Validate</button>
                        </div>       
                    </form>         
                </div>
			</div>
		</div>
	</div>
</div><!-- pad-wrapper -->

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<script type="text/javascript">
	$(document).ready(function() {
	});
</script>

</body>
</html>
