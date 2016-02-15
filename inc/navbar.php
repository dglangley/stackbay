<?php
	include_once 'dbconnect.php';

	$s = trim($_REQUEST['s']);
	if (! isset($search_field)) { $search_field = 1; }
	if (! isset($qty_field)) { $qty_field = 2; }
	if (! isset($price_field)) { $price_field = 3; }

	$search_index = $search_field-1;
	$qty_index = $qty_field-1;
	$price_index = $price_field-1;
?>

    <!-- navbar -->
    <header class="navbar navbar-inverse" role="banner">
        <div class="navbar-header">
            <button class="navbar-toggle" type="button" data-toggle="collapse" id="menu-toggler">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="logo" href="/" title="home">
                <img src="img/logo-white.png" alt="logo" />
            </a>
        </div>
        <ul class="nav navbar-nav pull-right hidden-xs">
            <li class="hidden-xs hidden-sm">
				<form method="post" action="/" class="form-inline search-form">

				<div class="form-group">
					<div class="input-group">
						<span class="input-group-btn">
							<button class="btn btn-default" type="button"><i class="fa fa-list-ol"></i> <sup><i class="fa fa-sort-desc"></i></sup></button>
						</span>
		                <input class="form-control" type="text" name="s" id="s" value="<?php echo $s; ?>" placeholder="Search..." />
	                	<span class="input-group-btn">
		                	<button class="btn btn-primary" type="submit"><i class="fa fa-search"></i></button>
		                </span>
	                </div><!-- /input-group -->
                </div><!-- /form-group -->

				</form>
            </li>
            <li class="hidden-xs hidden-sm">
				<a href="#"><i class="fa fa-wrench"></i> Repair Home</a>
			</li>
            <li class="hidden-xs hidden-sm">
				<a href="#"><i class="fa fa-truck"></i> Shipping Home</a>
			</li>
            <li class="dropdown">
                <a href="#" class="dropdown-toggle hidden-xs hidden-sm" data-toggle="dropdown">
                    <i class="fa fa-signal"></i>
                    <span>Reports</span>
                </a>
                <ul class="dropdown-menu">
                    <li><a href="#"> Inventory Export</a></li>
                    <li><a href="#"> Profits &amp; Loss</a></li>
                    <li><a href="#"> Requests Report</a></li>
                    <li><a href="#"> Revenue Report</a></li>
                    <li><a href="#"> RMA Report</a></li>
				</ul>
			</li>
            <li class="notification-dropdown hidden-xs hidden-sm">
                <a href="#" class="trigger">
                    <i class="fa fa-warning"></i>
                    <span class="count">3</span>
                </a>
                <div class="pop-dialog">
                    <div class="pointer right">
                        <div class="arrow"></div>
                        <div class="arrow_border"></div>
                    </div>
                    <div class="body">
                        <a href="#" class="close-icon"><i class="fa fa-close"></i></a>
                        <div class="notifications">
                            <h3>You have 3 new notifications</h3>
                            <a href="#" class="item">
                                <i class="fa fa-sign-in"></i> New user registration
                                <span class="time"><i class="fa fa-clock-o"></i> 13 min.</span>
                            </a>
                            <a href="#" class="item">
                                <i class="fa fa-sign-in"></i> New user registration
                                <span class="time"><i class="fa fa-clock-o"></i> 18 min.</span>
                            </a>
                            <a href="#" class="item">
                                <i class="fa fa-envelope-o"></i> New message from Alejandra
                                <span class="time"><i class="fa fa-clock-o"></i> 28 min.</span>
                            </a>
                        </div>
                    </div>
                </div>
            </li>
            <li class="hidden-xs hidden-sm">
			</li>
            <li class="dropdown">
                <a href="#" class="dropdown-toggle hidden-xs hidden-sm" data-toggle="dropdown">
                    David Langley
                    <b class="caret"></b>
                </a>
                <ul class="dropdown-menu">
                    <li><a href="#"><i class="fa fa-user"></i> Personal info</a></li>
                    <li><a href="#"><i class="fa fa-calendar-o"></i> Calendar</a></li>
                    <li><a href="#">Submit issue</a></li>
                    <li><a href="#">Logout</a></li>
                </ul>
            </li>
        </ul>
    </header>
    <!-- end navbar -->
