<?php 

    //Permission denied Page

?>
<!DOCTYPE html>
<html class="login-bg">
<head>
	<title>Permission Denied</title>
    <?php
        include_once 'inc/scripts.php';
    ?>
    <style type="text/css">
        .robot {
            background: url(//www.google.com/images/errors/robot.png) 50px center no-repeat;
            min-height: 350px;
            padding-left: 20px;
        }
    </style>
</head>
<body class="sub-nav">

    <!-- Include Needed Files -->
    <?php include_once 'inc/keywords.php'; ?>
    <?php include_once 'inc/dictionary.php'; ?>
    <?php include_once 'inc/logSearch.php'; ?>
    <?php include_once 'inc/format_price.php'; ?>
    <?php include_once 'inc/getQty.php'; ?>

    <?php include_once 'inc/navbar.php'; ?>

    <!-- Class 'pt' is used in padding.css to simulates (p)adding-(t)op: (x)px -->
    <div class="container pt-70" style="min-height: 500px">
        <h2 class="text-center pb-20">Permission Denied</h2>
        <div class="row">
            <div class="col-md-12">
                <h4 class="text-center">Error <strong>9000.99</strong> - <strong>Permission</strong> Issues</h4>
                <div class="progress">
                    <div class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width:100%">
                        Over 9000% (DANGER)
                    </div>
                </div>
            </div>
            <div class="col-md-6 pt-40">
                <p class="text-right">Whoops looks like the power level exceeded our access levels and blew up the page or you don't have <strong>permission</strong>. Please send a complaint email to <a href="mailto:david@ven-tel.com?Subject=Permission%20Issues" target="_top">David@ven-tel.com</a> with much <span style="color:#ff0000;">C</span><span style="color:#ff2a00;">o</span><span style="color:#ff5500;">l</span><span style="color:#ff7f00;">o</span><span style="color:#ffbf00;">r</span><span style="color:#ffff00;">f</span><span style="color:#aaff00;">u</span><span style="color:#55ff00;">l</span><span style="color:#00ff00;"> </span><span style="color:#00ff80;">V</span><span style="color:#00ffff;">e</span><span style="color:#00aaff;">r</span><span style="color:#0055ff;">b</span><span style="color:#0000ff;">a</span><span style="color:#4600ff;">g</span><span style="color:#8b00ff;">e</span> to get your access as soon as possible.</p>
                <p class="text-right"><strong>- Davebot</strong></p>
            </div>
            <div class="robot col-md-6"></div>
        </div>
    </div>

    <!-- Include Needed Files -->
    <?php include_once 'inc/footer.php'; ?>
    <?php include_once 'modal/results.php'; ?>
    <?php include_once 'modal/notes.php'; ?>
    <?php include_once 'modal/remotes.php'; ?>
    <?php include_once 'modal/image.php'; ?>
    <?php include_once 'inc/jquery-fileupload.php'; ?>


</body>
</html>