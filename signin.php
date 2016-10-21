<?php
    //Must have singin.php file otherwise throw error
    require_once 'inc/user_access.php';
    require_once 'inc/user_login.php';

    //Create an object for current instance to allow access to all functions within and extends of Ven Login under user_login.php
    $venLog = new venLogin;

    $error = false;

    //Check if the user used signout template to log out and give a success message
    if(isset($_REQUEST['logged_out']) && $_REQUEST['logged_out']) {
        $loggedmsg = 'Successfully logged out';
    }

    if(isset($_REQUEST['reset']) && $_REQUEST['reset']) {
        $loggedmsg = 'Password has been reset';
    }

    //This means the form has been submitted now we will check the login info and decide if the user deserves access
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (empty($_POST["username"])) {
            $userErr = "Username is required";
            $error = true;
        } else {
            //Check if the username actually exists
            $exists = $venLog->checkUsername($_POST["username"]);
        }

        if (empty($_POST["password"])) {
            $passwordErr = "Password is required";
            $error = true;
        }

        if(!$error) {
            //echo 'No Errors and processing request';
            //Run thru the user registration
            $venLog->loginMember();

            //Check and see if any of the errors was flagged during the user login otherwise user will be logged into the website
            if($venLog->getError()) {
                $loginErr =  $venLog->getError();
            } 
        } else {
            $loginErr =  'User credentials missing';
        }
    }


//Check if a session exists or not
$loggedin = false;

$loggedin = (!empty($_SESSION['loggedin']) ? $_SESSION['loggedin'] : false);
if(!$loggedin && $venLog->generated_pass == '0') { 
?>

    <!DOCTYPE html>
    <html>
    <head>
    	<title>Sign in</title>
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
                    <h6>Log In</h6>

                    <?php if(isset($loginErr) || isset($_REQUEST['timeout'])) { ?>
                        <div class="alert alert-danger">
                            <?php 
                                if(isset($_REQUEST['timeout']) && $_REQUEST['timeout']) { 
                                    echo 'Your session has timed out. Please log back in.'; 
                                } else { 
                                    echo $loginErr; 
                                } 
                            ?>
                        </div>
                    <?php } ?>

                    <?php if(isset($loggedmsg)) { ?>
                        <div class="alert alert-success">
                            <?php echo $loggedmsg; ?>
                        </div>
                    <?php } ?>

                    <form action='<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>' method='post' accept-charset='UTF-8'>
                        <div class="row">
                            <div class="col-md-12">
                                <input name="username" class="form-control" type="text" placeholder="Username"  value="<?php echo (isset($_POST['username']) ? $_POST['username'] : ''); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <input name="password" class="form-control" type="password" placeholder="Password"  value="<?php echo (isset($_POST['password']) ? $_POST['password'] : ''); ?>">
                            </div>
                        </div>

                        <div class="action">
                            <button class="btn btn-lg btn-primary login" type='submit' name='Submit' >Sign In</button>
                        </div>       
                    </form>         
                </div>
            </div>

        </div>

    	<!-- scripts -->
        <script src="http://code.jquery.com/jquery-latest.js"></script>
        <script src="js/bootstrap.min.js"></script>
        <script src="js/theme.js"></script>

        <!-- pre load bg imgs -->
        <script type="text/javascript">
            $(function () {

            });
        </script>
    </body>
    </html>
<!-- Begin the form to reset the users password if needed for a genereated password -->
<?php 
    exit;
} else if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
    //Remove the object once the user is logged in
    unset($venLog);

    $is_loggedin = is_loggedin();
}
?>