<?php
    include_once $_SERVER["ROOT_DIR"] . '/inc/dbconnect.php';
?>

    <!DOCTYPE html>
    <html>
    <head>
    	<title>Break Mode</title>
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
                    <h6>Break Mode</h6>

                    <form action='<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>' method='post' accept-charset='UTF-8'>
                        <div class="row">
                            <div class="col-md-12">
                                <!-- <input name="username" class="form-control" type="text" placeholder="Username"  value="David" readonly> -->
                                <div class="input-group" style="margin-bottom: 18px;">
                                    <input name="username" class="form-control" type="text" placeholder="Username"  value="<?=$GLOBALS['U']['name'];?>" readonly style="margin-bottom: 0;">
                                    <span class="input-group-addon">
                                        <i class="fa fa-lock"></i>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <input name="password" class="form-control" type="password" placeholder="Pin or Password"  value="">
                            </div>
                        </div>
                        
                        <div class="row pb-10">
                            <a href="reset.php?user=reset">Not you? Login as a different user.</a>
                        </div>

                        <div class="action">
                            <button class="btn btn-lg btn-primary login" type='submit' name='Submit' >Sign In</button>
                        </div>       
                    </form>         
                </div>
            </div>

        </div>

    	<!-- scripts -->
        <script src="//code.jquery.com/jquery-latest.js"></script>
        <script src="js/bootstrap.min.js"></script>
        <script src="js/theme.js"></script>

    </body>
    </html>

