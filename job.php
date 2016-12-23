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
	include_once $_SERVER["ROOT_DIR"].'/inc/htmlcp1252.php';

	$jobid = 0;
	if (isset($_REQUEST['id']) AND is_numeric($_REQUEST['id']) AND $_REQUEST['id']>0) { $jobid = $_REQUEST['id']; }

	$job_out = '';
	$job = array();
	$contact = '';
	$special_instructions = '';
	if ($jobid) {
		$query = "SELECT * FROM services_job WHERE id = '".res($jobid)."'; ";
		$result = qdb($query,'SVCS_PIPE') OR die(qe().' '.$query);
		$job = mysqli_fetch_assoc($result);

		$contact = preg_replace('/^N[\/]?A$/i','',str_replace(chr(10),'<BR>',$job['site_access_info_contact']));
		if ($contact) { $contact .= "<BR>"; }

		$special_instructions = preg_replace('/^N[\/]?A$/i','',str_replace(chr(10),'<BR>',$job['site_access_info_special']));
		if ($special_instructions) { $special_instructions .= "<BR><BR>"; }

		$job_out = ' #'.$job['job_no'];
		$job['status'] = 'ACTIVE';
		$job['status_color'] = 'success';
		$job['status_flag'] = 'flag-checkered';
		if ($job['admin_complete']) {
			$job['status'] = 'COMPLETE';
			$job['status_color'] = '';
			$job['status_flag'] = 'flag';
		} else if ($job['on_hold']) {
			$job['status'] = 'ON HOLD';
			$job['status_color'] = 'danger';
			$job['status_flag'] = 'flag-o';
		}
	}
?>

<div class="container">
    <section class="margin-bottom">
        <div class="row">
            <div class="col-md-12">
                <h2 class="right-line">Job<?php echo $job_out; ?></h2>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="text-icon">
                    <span class="icon-ar icon-ar-lg"><i class="fa fa-building-o"></i></span>
                    <div class="text-icon-content">
                        <h3 class="no-margin"><?php echo $job['customer']; ?></h3>
                        <p class="info">Customer</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="text-icon">
                    <span class="icon-ar icon-ar-lg bg-info"><i class="fa fa-hashtag"></i></span>
                    <div class="text-icon-content">
                        <h3 class="no-margin"><?php echo $job['customer_job_no']; ?></h3>
                        <p class="info">Customer Job#</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="text-icon">
                    <span class="icon-ar icon-ar-lg fa-stack"><i class="fa fa-calendar-o fa-stack-2x fa-stack-lg"></i><span class="fa-stack-sm calendar-text"><?php echo format_date($job['date_entered'],'d'); ?></span></span>
                    <div class="text-icon-content">
                        <h3 class="no-margin"><?php echo format_date($job['date_entered'],'M d, Y'); ?></h3>
                        <p class="info">Date Entered</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="text-icon">
                    <span class="icon-ar icon-ar-lg bg-<?php echo $job['status_color']; ?>"><i class="fa fa-<?php echo $job['status_flag']; ?>"></i></span>
                    <div class="text-icon-content">
                        <h3 class="no-margin text-<?php echo $job['status_color']; ?>"><?php echo $job['status']; ?></h3>
                        <p class="info">Status</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="text-icon">
                    <span class="icon-ar icon-ar-lg fa-stack bg-brown"><i class="fa fa-calendar-o fa-stack-2x fa-stack-lg"></i><span class="fa-stack-sm calendar-text"><?php echo format_date($job['scheduled_date_of_work'],'d'); ?></span></span>
                    <div class="text-icon-content">
                        <h3 class="no-margin"><?php echo format_date($job['scheduled_date_of_work'],'D M d, Y'); ?></h3>
                        <p class="info">Scheduled Work</p>
                    </div>
                    <span class="icon-ar icon-ar-lg fa-stack bg-brown"><i class="fa fa-calendar-o fa-stack-2x fa-stack-lg"></i><span class="fa-stack-sm calendar-text"><?php echo format_date($job['scheduled_completion_date'],'d'); ?></span></span>
                    <div class="text-icon-content">
                        <h3 class="no-margin"><?php echo format_date($job['scheduled_completion_date'],'D M d, Y'); ?></h3>
                        <p class="info">Scheduled Completion</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="text-icon">
                    <span class="icon-ar icon-ar-lg bg-brown"><i class="fa fa-map-marker"></i></span>
                    <div class="text-icon-content text-icon-absolute">
                        <h5 class="no-margin"><?php echo $contact; ?><?php echo str_replace(chr(10),'<BR>',$job['site_access_info_address']); ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-sm-6">
                <div class="text-icon">
                    <span class="icon-ar icon-ar-lg bg-info"><i class="fa fa-info-circle"></i></span>
                    <div class="text-icon-content text-icon-absolute">
                        <?php echo $special_instructions.str_replace(chr(10),'<BR>',htmlcp1252($job['description'])); ?>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
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
