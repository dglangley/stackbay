<!DOCTYPE html>
<html lang="en">
<head>
	<title>Job</title>
	<?php
		include_once 'inc/scripts.php';
	?>
</head>
<body>

	<?php include_once 'inc/navbar.php'; ?>

    <div id="pad-wrapper">

<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/svcs_pipe.php';

	$jobid = 0;
	if (isset($_REQUEST['id']) AND is_numeric($_REQUEST['id']) AND $_REQUEST['id']>0) { $jobid = $_REQUEST['id']; }

	$job_out = '';
	$job = array();
	if ($jobid) {
		$query = "SELECT * FROM services_job WHERE id = '".res($jobid)."'; ";
		$result = qdb($query,'SVCS_PIPE') OR die(qe().' '.$query);
		$job = mysqli_fetch_assoc($result);

		$job_out = ' #'.$job['job_no'];
	}
?>

<div class="container">
    <section class="margin-bottom">
        <div class="row">
            <div class="col-md-12">
                <h2 class="right-line">Job<?php echo $job_out; ?></h2>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="text-icon">
                    <span class="icon-ar icon-ar-lg"><i class="fa fa-desktop"></i></span>
                    <div class="text-icon-content">
                        <h3 class="no-margin"><?php echo $job['customer']; ?></h3>
                        <p class="info">Customer</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="text-icon">
                    <span class="icon-ar icon-ar-lg"><i class="fa fa-cloud"></i></span>
                    <div class="text-icon-content">
                        <h3 class="no-margin"><?php echo $job['customer_job_no']; ?></h3>
                        <p class="info">Customer Job#</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="text-icon">
                    <span class="icon-ar icon-ar-lg"><i class="fa fa-tablet"></i></span>
                    <div class="text-icon-content">
                        <h3 class="no-margin"><?php echo format_date($job['date_entered'],'M d, Y'); ?></h3>
                        <p class="info">Date Entered</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="text-icon">
                    <span class="icon-ar icon-ar-lg"><i class="fa fa-wordpress"></i></span>
                    <div class="text-icon-content">
                        <h3 class="no-margin">Wordpress Themes</h3>
                        <p>Praesentium cumque voluptate harum quae doloribus, atque error debitis, amet velit in similique, necessitatibus odit vero sunt.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="text-icon">
                    <span class="icon-ar icon-ar-lg"><i class="fa fa-graduation-cap"></i></span>
                    <div class="text-icon-content">
                        <h3 class="no-margin">Training and development</h3>
                        <p>Praesentium cumque voluptate harum quae doloribus, atque error debitis, amet velit in similique, necessitatibus odit vero sunt.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="text-icon">
                    <span class="icon-ar icon-ar-lg"><i class="fa fa-paper-plane-o"></i></span>
                    <div class="text-icon-content">
                        <h3 class="no-margin">Customer service</h3>
                        <p>Praesentium cumque voluptate harum quae doloribus, atque error debitis, amet velit in similique, necessitatibus odit vero sunt.</p>
                    </div>
                </div>
            </div>
        </div> <!-- row -->
    </section>

    <section>
        <div class="row">
            <div class="col-md-12">
                <h2 class="right-line">Our Values</h2>
            </div>
            <div class="col-md-6">
                <div class="content-box box-default">
                    <div class="row">
                        <div class="col-md-4">
                            <img src="assets/img/demo/office1.jpg" alt="" class="img-responsive">
                        </div>
                        <div class="col-md-8">
                            <h4 class="content-box-title margin-small">Lorem ipsum dolor sit amet</h4>
                            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Cumque, alias similique sapiente rerum ipsam delectus corporis.</p>
                        </div>
                    </div>
                </div>
                <hr class="dotted">
                <div class="content-box box-default">
                    <div class="row">
                        <div class="col-md-4">
                            <img src="assets/img/demo/office2.jpg" alt="" class="img-responsive">
                        </div>
                        <div class="col-md-8">
                            <h4 class="content-box-title margin-small">Lorem ipsum dolor sit amet</h4>
                            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Cumque, alias similique sapiente rerum ipsam delectus corporis.</p>
                        </div>
                    </div>
                </div>
                <hr class="dotted">
                <div class="content-box box-default">
                    <div class="row">
                        <div class="col-md-4">
                            <img src="assets/img/demo/office3.jpg" alt="" class="img-responsive">
                        </div>
                        <div class="col-md-8">
                            <h4 class="content-box-title margin-small">Lorem ipsum dolor sit amet</h4>
                            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Cumque, alias similique sapiente rerum ipsam delectus corporis.</p>
                        </div>
                    </div>
                </div>
                <hr class="dotted">
                <div class="content-box box-default">
                    <div class="row">
                        <div class="col-md-4">
                            <img src="assets/img/demo/office4.jpg" alt="" class="img-responsive">
                        </div>
                        <div class="col-md-8">
                            <h4 class="content-box-title margin-small">Lorem ipsum dolor sit amet</h4>
                            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Cumque, alias similique sapiente rerum ipsam delectus corporis.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <ol class="service-list list-unstyled">
                    <li class="wow fadeInUp">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Nihil suscipit cupiditate expedita hic earum vero sint, recusandae itaque, rem distinctio.</li>
                    <li class="wow fadeInUp">Totam porro sit, obcaecati quos quae iure tenetur, soluta voluptatem sapiente rerum ipsam delectus corporis voluptates voluptate, nulla mollitia pariatur.</li>
                    <li class="wow fadeInUp">Amet dolorum ullam, rerum ratione distinctio, quia iusto rem! Asperiores et quas, ratione in dolores dolorum doloribus magni suscipit labore!</li>
                    <li class="wow fadeInUp">Enim quas nesciunt sequi odit, ut quisquam vitae commodi animi placeat nihil saepe magnam aliquam, vero harum quae doloribus aut nostrum veniam alias!</li>
                    <li class="wow fadeInUp">Expedita doloribus vel nam fuga iusto aperiam maxime aut amet pariatur. Libero quidem, optio itaque ducimus. Nulla laboriosam voluptas voluptates.</li>
                    <li class="wow fadeInUp">Amet dolorum ullam, rerum ratione distinctio, quia iusto rem! Asperiores et quas, ratione in dolores dolorum doloribus magni suscipit labore!</li>
                    <li class="wow fadeInUp">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Nihil suscipit cupiditate expedita hic earum vero sint, recusandae itaque, rem distinctio.</li>
                </ol>
            </div>
        </div>
    </section>
</div> <!-- container -->

</div> <!-- pad-wrapper -->

<?php include_once 'inc/footer.php'; ?>

</body>
</html>
