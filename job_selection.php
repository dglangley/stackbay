<?php
?>

    <!DOCTYPE html>
    <html>
    <head>
    	<title>Job Selection</title>
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
                    <h6>Job Selection</h6>

                    <form action='<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>' method='post' accept-charset='UTF-8'>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <select name="job[]" size="6" class="form-control" multiple="">
                                        <option value="1" selected="">Job 1 - Verizon...</option>
                                        <option value="2">Job 2 - Ventura Telephone...</option>
                                        <option value="3">Job 3 - Repair at Location</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="action">
                            <button class="btn btn-lg btn-primary login" type='submit' name='Submit' >Continue</button>
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

