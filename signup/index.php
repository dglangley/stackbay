<?php
    include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Stackbay</title>
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
		<link rel="stylesheet" href="/css/compiled/landing.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="/css/overrides.css" type="text/css" media="screen" />

        <!-- open sans font -->
        <link href='//fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,700italic,800italic,400,300,600,700,800' rel='stylesheet' type='text/css' />

        <!--[if lt IE 9]>
            <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->

        <style>
			.error {color: #FF0000;}
			
			.parallax-home {
				background-image: url(img/switch-2.jpg);
			}

			.parallax {
				background-attachment: fixed;
				background-position: center center;
				background-size: cover;
			}

			.banner {
				min-height: 400px;
				background: rgba(0,0,0,0.7);
				position: relative;
			}
        </style>
    </head>
    <body>

        <header class="site-header sticky" id="home">
			<nav class="navbar navbar-expand-xl main-menu fixed-top" style="border-radius: 0;">
				<div class="container-fluid custom-container">
					<a href="#!" class="navbar-brand" style="padding: 0;"><img src="/img/logo-white.png" style="width: 110px;" alt=""></a>
				</button>

					<div class="collapse navbar-collapse" id="siteNav">
						<ul class="navbar-nav">
							<!-- <li class="nav-item"><a href="#about">About</a></li> -->
						</ul>
						<div class="pull-right" style="margin-top: 5px;">
							<!-- <a href="#" class="btn-mr pill th-primary-outline ml-auto xs"> SIGN IN</a> -->
							<a target="_blank" href="/signup/form.php" class="btn-mr pill th-primary-outline xs"> SIGN UP</a>
						</div>
					</div>

				</div>
			</nav>
		</header>

		<!-- Banner -->
		<!-- <section class="bannerarea home-page-1" style="background: #000;"> -->
			<div class="row">
				<div class="parallax parallax-home" style="margin-top: 76px;">
					<div class="banner">
						<div class="container">
							<div class="row" style="margin-top: 150px;">
								<div class="col-sm-12 text-center center-block pt-90">
									<p class="" style="color: #FFF; font-size: 18px;"><span style="font-weight: bold; font-size: 20px;">Stackbay</span> is a cloud ERP solution that provides first-class inventory management and multi channel synchronization for IT resellers.</p>
								</div>
							</div>
						</div>
						<div class="banner-fade">
							<div class="container">
								<div class="row">
									<div class="col-sm-12 text-center center-block">
										<div class="col-sm-6">
											<a href="/signup/form.php" class="btn btn-lg btn-success" style="">Register for a Demo »</a>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		<!-- </section> -->

		<!-- About -->
		<section class="about-area" id="about">
			<div class="container">
				<div class="row align-items-center">
					<div class="col-md-6 col-lg-5">
						<div class="about-img"><img src="assets/images/all-img/aboutimg.png" alt=""></div>
					</div>
					<!-- end about img -->
					<div class="col-md-6 col-lg-6 offset-lg-1 sec-titile-wrapper">
						<h2 class="section-title">Who is Stackbay?</h2>
						<p></p>
					</div>
					<!-- end about conetent -->
				</div>
			</div>
		</section>

		<section class="feature-area" id="feature">
			<div class="feature-bg-shape-2"></div>
			<!-- end feature bg shape 2 -->
			<div class="container">
				<div class="row">
					<div class="col-lg-4 col-md-6 col-12 text-center animation animated fadeInUp" data-animation="fadeInUp" data-animation-delay="0.1s" style="animation-delay: 0.1s; visibility: visible;">
						<div class="single-feature">
							<div class="count-box">01</div>
							<h3><a href="#">Optimized to Save</a></h3>
							<p>Save in IT costs associated with maintaining, integrating and upgrading separate applications.</p>
						</div>
					</div>
					<!-- end single feature -->
					<div class="col-lg-4 col-md-6 col-12 text-center animation animated fadeInUp" data-animation="fadeInUp" data-animation-delay="0.1s" style="animation-delay: 0.1s; visibility: visible;">
						<div class="single-feature">
							<div class="count-box">02</div>
							<h3><a href="#">Mobile Friendly</a></h3>
							<p>Easy accessibility from both the desktop and the mobile phone!</p>
						</div>
					</div>
					<!-- end single feature -->
					<div class="col-lg-4 col-md-6 col-12 text-center animation animated fadeInUp" data-animation="fadeInUp" data-animation-delay="0.1s" style="animation-delay: 0.1s; visibility: visible;">
						<div class="single-feature">
							<div class="count-box">03</div>
							<h3><a href="#">Fast & Reliable</a></h3>
							<p>
								Real-time visibility across the business, with 24/7 access from any browser. Back with excellent customer support.
							</p>
						</div>
					</div>
				</div>
			</div>
		</section>

		<!-- Footer -->
		<footer class="site-footer" style="padding: 20px 0 0px;">
			<div class="container">
				<div class="row">
					<div class="col-sm-12">
						<div class="single-footer">
							<!-- <a href="#"> <img src="assets/images/logo/flogo.png" alt="" class="footer-logo"></a>
							<p>Lorem ipsum dolor sit amet consetetur sadipscing elitr invidunt.</p>
							<br> -->
							<p class="text-center">&copy;<?php echo date("Y"); ?> Stackbay All Rights Reserved</p>
						</div>
					</div>

					<!-- <div class="col-lg-2 col-md-6 col-12">
						<div class="single-footer">
							<h5 class="footer-title">Company</h5>
							<ul>
								<li><a href="#">Home </a></li>
								<li><a href="#">feature </a></li>
								<li><a href="#">overview </a></li>
								<li><a href="#">pricing </a></li>
								<li><a href="#">team </a></li>
								<li><a href="#">faqs </a></li>
								<li><a href="#">contacts </a></li>
							</ul>
						</div>
					</div>
					<div class="col-lg-3 col-md-6 col-12">
						<div class="single-footer">
							<h5 class="footer-title">Useful Link</h5>
							<ul>
								<li><a href="#">Term & Condition</a></li>
								<li><a href="#">Privacy Policy</a></li>
								<li><a href="#">Recent News</a></li>
							</ul>
						</div>
					</div>
					<div class="col-lg-3 col-md-6 col-12">
						<div class="single-footer">
							<h5 class="footer-title">Company</h5>
							<ul>
								<li><a href="#">Facebook</a></li>
								<li><a href="#">Tiwtter </a></li>
								<li><a href="#">Instagram </a></li>
								<li><a href="#">Linkedin </a></li>
								<li><a href="#">team </a></li>
								<li><a href="#">faqs </a></li>
								<li><a href="#">contacts </a></li>
							</ul>

						</div>
					</div> -->
				</div>
			</div>
		</footer>

        <!-- scripts -->
        <script src="/js/jquery.min.js"></script>
        <script src="/js/bootstrap.min.js"></script>
        
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