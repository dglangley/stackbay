<?php
    require_once 'inc/user_access.php';
    require_once 'inc/user_pin.php';

    //Create an object for current instance to allow access to all functions within and extends of Ven Login under user_login.php
    $venPin = new venPin;

    $error = false;
    $exists = false;

    $userErr = '';

    //This means the form has been submitted now we will check the login info and decide if the user deserves access
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (empty($_POST["pin"]) || empty($_POST["pin_confirmation"])) {
            $userErr = "Missing Pin";
            $error = true;
        } else if($_POST["pin"] !== $_POST["pin_confirmation"]) {
            // Check if both pins match before running anything
            $userErr = "Pins Do Not Match.";
            $error = true;
        } else if(strlen($_POST["pin"]) < 4) {
            $userErr = "Pin Too Short. Please Input a Minimum 4 Digits";
            $error = true;
        }

        if(!$error) {
            //echo 'No Errors and processing request';

            $venPin->registerPin();
            // echo $venPin->getPassword() . '<br>';
            // echo $venPin->getUserID();
        }
    }
?>

<!DOCTYPE html>
    <html>
    <head>
        <title>Create Pin</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        
        <?php
            include_once 'inc/scripts.php';
        ?>
        <!-- bootstrap -->
        <link href="css/bootstrap/bootstrap.css" rel="stylesheet" />
        <link href="css/bootstrap/bootstrap-overrides.css" type="text/css" rel="stylesheet" />

        <!-- global styles -->
        <link rel="stylesheet" type="text/css" href="css/compiled/layout.css" />
        <link rel="stylesheet" type="text/css" href="css/compiled/elements.css" />
        <link rel="stylesheet" type="text/css" href="css/compiled/icons.css" />

        <!-- libraries -->
        <link rel="stylesheet" type="text/css" href="css/lib/font-awesome.css" />
        
        <!-- this page specific styles -->
        <link rel="stylesheet" href="css/compiled/signin.css" type="text/css" media="screen" />

        <!-- open sans font -->
        <link href='//fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,700italic,800italic,400,300,600,700,800' rel='stylesheet' type='text/css' />

        <!--[if lt IE 9]>
          <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->

        <style>
            .error {color: #FF0000;}
        </style>
    </head>
    <body class="login-bg">

        <div class="login-wrapper">
            <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                <img class="logo" src="img/logo.png" alt="logo" />
            </a>

            <div class="box">

                <div class="content-wrap">
                    <?php if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']) { ?>

                    <?php // print_r($GLOBALS['U']); ?>
                        <h6>Create Pin</h6>

                        <?php if(! empty($error)) { ?>
                            <div class="alert alert-danger">
                                <?=$userErr;?>
                            </div>
                        <?php } ?>

                        <form action='<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>' method='post' accept-charset='UTF-8'>
                            <div class="row">
                                <div class="col-md-12">
                                    <!-- <input name="username" class="form-control" type="text" placeholder="Username"  value="David" readonly> -->
                                    <!-- <div class="input-group" style="margin-bottom: 18px;">
                                        <input name="username" class="form-control" type="text" placeholder="Username"  value="David" readonly style="margin-bottom: 0;">
                                        <span class="input-group-addon">
                                            <i class="fa fa-lock"></i>
                                        </span>
                                    </div> -->

                                    <div class="alert alert-info">
                                        Please enter a minimum 4 digit numeric value to log in with.
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <input name="pin" class="form-control" type="password" placeholder="New Pin"  value="">
                                </div>

                                <div class="col-md-12">
                                    <input name="pin_confirmation" class="form-control" type="password" placeholder="Confirm Pin"  value="">
                                </div>
                            </div>
                            
                           <!--  <div class="row pb-10">
                                <a href="reset.php?user=reset">Not you? Login as a different user.</a>
                            </div> -->

                            <div class="action">
                                <button class="btn btn-lg btn-primary login" type='submit' name='Submit' >Create</button>
                            </div>       
                        </form>    
                    <?php } ?>
                </div>
            </div>

        </div>

        <!-- scripts -->
        <script src="//code.jquery.com/jquery-latest.js"></script>
        <script src="js/bootstrap.min.js"></script>
        <script src="js/theme.js"></script>

    </body>
    </html>
<!-- Begin the form to reset the users password if needed for a genereated password -->
