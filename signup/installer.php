<?php
    include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
    require_once $_SERVER["ROOT_DIR"].'/inc/dbsync.php'; 

    $dbSync = new DBSync;

    $token = $_REQUEST['token'];

    if(! $token) {
        $ERROR = 'Token is required. Please <a href="'.$_SERVER['HTTP_HOST'].'/signup/form.php">click here</a> to register and receive a token to view the demo system.';
    }  else {
        // Verify the token here
        $dbSync->authenticateToken($token);
    }

    if($ALERT AND ! $_REQUEST['ALERT']) {
        $ERROR = $ALERT;
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Database Setup</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        
        <!-- bootstrap -->
        <link href="/css/bootstrap/bootstrap.css" rel="stylesheet" />
        <link href="/css/bootstrap/bootstrap-overrides.css" type="text/css" rel="stylesheet" />

        <link href="/css/lib/font-awesome.min.css" type="text/css" rel="stylesheet" />

        <!-- global styles -->
        <link rel="stylesheet" type="text/css" href="/css/compiled/layout.css" />
        <link rel="stylesheet" type="text/css" href="/css/compiled/elements.css" />
        <link rel="stylesheet" type="text/css" href="/css/compiled/icons.css" />

        <!-- libraries -->
        <link rel="stylesheet" type="text/css" href="/css/lib/font-awesome.css" />
        
        <!-- this page specific styles -->
        <link rel="stylesheet" href="/css/compiled/signin.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="/css/overrides.css" type="text/css" media="screen" />

        <!-- open sans font -->
        <link href='//fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,700italic,800italic,400,300,600,700,800' rel='stylesheet' type='text/css' />

        <!--[if lt IE 9]>
            <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->

        <style>
            .error {color: #FF0000;}

            .login-wrapper .box {
                width: 600px;
            }

            .login-wrapper .box .alert-info {
                font-size: 14px;
            }
        </style>
    </head>
    <body class="login-bg">

        <?php include_once $_SERVER["ROOT_DIR"].'/modal/alert.php';?>

        <div id="loader" class="loader text-muted">
            <div>
                <i class="fa fa-refresh fa-5x fa-spin"></i><br/>
                <h1 id="loader-message"></h1>
            </div>
        </div>

        <div class="login-wrapper">
            <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                <img class="logo" src="/img/logo.png" alt="logo" style="max-width: 400px;" />
            </a>

            <div class="box">

                <div class="content-wrap">
                    <h6>Database Setup</h6>

                    <?php if($ERROR) { ?>
                        <div class="alert alert-danger">
                            <?=$ERROR;?>
                        </div>
                    <?php } ?>

                    <?php if(! $ERROR) { ?>
                        <div class="alert alert-info" style="text-align: left;">
                            Welcome to the Installer <?=$dbSync->user_name?>! <br><br>
                            Please fill out the form below to launch your Stackbay instance. 
                        </div>
                    <?php } ?>

                    <?php if($ALERTS) { ?>
                        <div class="alert alert-danger" style="text-align: left;">
                            <?php print_r(reset($ALERTS));?>
                        </div>
                    <?php } ?>

                    <form id='erp_submit' action='/erp_edit.php' method='post' accept-charset='UTF-8'>
                        <input type="hidden" name="token" value="<?=$token?>">
                        <div class="row">
                            <div class="col-md-12">
                                <input name="database" class="form-control" type="text" placeholder="http://(name).stackbay.com"  data-toggle="tooltip" data-placement="top" title="" data-original-title='Enter a name as you want it in the url "abc" would result in "abc.StackBay.com"' value="" autocomplete="off">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <input name="password" class="form-control" type="password" placeholder="Password" autocomplete="new-password" value="">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <input name="password_ver" class="form-control" type="password" placeholder="Password"  value="">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <input name="company" class="form-control" type="text" placeholder="Company"  value="<?=$dbSync->user_company?>">
                            </div>
                        </div>

                        <div class="action">
                            <button class="btn btn-lg btn-primary generateERP" type='submit' name='Submit'>Create</button>
                        </div>       
                    </form>         
                </div>
            </div>

        </div>

        <!-- scripts -->

        <script src="/js/jquery.min.js"></script>
        <script src="/js/bootstrap.min.js"></script>

        <script src="/js/theme.js"></script>
        
        <script src="/js/ventel.js?id=<?php echo $V; ?>"></script>

        <script>
            // Self invoking function
            // Similar to document . ready
            (function($) {
                $('.generateERP').click(function(e){
                    e.preventDefault();

                    // Check for missing stuff
                    if(! $('input[name="database"]').val() || ! $('input[name="company"]').val()) {
                        modalAlertShow("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning", "Namespace and company required. <br><br>If this message appears to be in error, please contact an Admin.");
                    } else {
                        $('#loader-message').html('Please wait while AMEA configures the system...<BR><BR> While we wait please make yourself a hot cup of coffee and enjoy a funny conversation.');
                        $('#loader').show();
                        
                        $('#erp_submit').submit();
                    }
                });

                <?php if($ERROR) { ?>
                    $('input').prop('disabled', true);
                    $('button').prop('disabled', true);

                    $('form').attr('action', '');
                <?php } ?>
            })(jQuery);
        </script>
    </body>
</html>