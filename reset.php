<?php
    //If no session user should not even be here, redirect them to the main login on the index.php
    if(empty($_SESSION) && !isset($_GET['user'])) {
         header('Location: index.php');    
    }
    
    if(!isset($_GET['user'])) {
        //Required files for the object
        require_once 'inc/user_access.php';
        require_once 'inc/user_reset.php';
    
        //Create an object for current instance to allow access to all functions within and extends of Ven privileges under user_reset.php
        $venRes = new venReset;
    
        $error = false;
        $resetErr = '';
    
        //This means the form has been submitted now we will check the login info and decide if the user deserves access
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new-password'])) {
            if($_POST['new-password'] === $_POST['confirm-password']) {
                $venRes->resetMember();
                $resetErr = $venRes->getError();
            } else {
                $resetErr = 'Passwords do not match. Please try again.';
            }
        }
    
    //Check if a session exists or not
?>

    <!DOCTYPE html>
    <html>
    <head>
        <title>Reset Password</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        
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
        <link href='http://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,700italic,800italic,400,300,600,700,800' rel='stylesheet' type='text/css' />

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
                    <h6>Reset Password</h6>

                    <?php if(!empty($resetErr)) { ?>
                        <div class="alert alert-danger">
                            <?php echo $resetErr; ?>
                        </div>
                    <?php } ?>

                    <form action='<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>' method='post' accept-charset='UTF-8'>
                        <div class="row">
                            <div class="col-md-12">
                                <input name="new-password" class="form-control" type="password" placeholder="New Password" value="">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <input name="confirm-password" class="form-control" type="password" placeholder="Confirm Password" value="">
                            </div>
                        </div>

                        <div class="action">
                            <button class="btn btn-lg btn-primary login" type='submit' name='Submit' >Submit</button>
                        </div>       
                    </form>         
                </div>
            </div>

        </div>

    
<?php } else { ?>
            <!DOCTYPE html>
    <html>
    <head>
        <title>Reset Password</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        
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
        <link href='http://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,700italic,800italic,400,300,600,700,800' rel='stylesheet' type='text/css' />

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
                    <h6>Password Recovery</h6>
                    
                    <div class="alert alert-info">
                        (Coming soon) Please enter your username and email and an admin will help recover your password.
                    </div>

                    <form action='index.php?user=request' method='post' accept-charset='UTF-8'>
                        <div class="row">
                            <div class="col-md-12">
                                <input name="username" class="form-control" type="text" placeholder="Username" value="">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <input name="email" class="form-control" type="text" placeholder="Email" value="">
                            </div>
                        </div>
                        <div class="action">
                            <button class="btn btn-lg btn-primary login" type='submit' name='Submit' >Submit</button>
                        </div>    
                    </form>         
                </div>
            </div>

        </div>
<?php } ?>

<!-- scripts -->
    <script src="http://code.jquery.com/jquery-latest.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/theme.js"></script>
</body>
</html>