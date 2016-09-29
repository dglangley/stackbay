<?php
	include_once 'dbconnect.php';
	include_once 'format_date.php';
	include_once $_SERVER["DOCUMENT_ROOT"].'/modal/alert.php';

	$s = '';
	$s2 = '';
	if (isset($_REQUEST['s'])) { $s = trim($_REQUEST['s']); }
	if (isset($_REQUEST['s2'])) { $s2 = trim($_REQUEST['s2']); }

	if (! isset($search_field)) { $search_field = 1; }
	if (! isset($qty_field)) { $qty_field = 2; }
	if (! isset($price_field)) { $price_field = ''; }

    if (isset($_REQUEST['search_field'])) { $search_field = trim($_REQUEST['search_field']); }
    if (isset($_REQUEST['qty_field'])) { $qty_field = trim($_REQUEST['qty_field']); }
    if (isset($_REQUEST['price_field']) AND $_REQUEST['price_field']<>'') { $price_field = trim($_REQUEST['price_field']); }

	$search_from_right = false;
	$qty_from_right = false;
	$price_from_right = false;
    if (isset($_REQUEST['search_from_right'])) { $search_from_right = trim($_REQUEST['search_from_right']); }
    if (isset($_REQUEST['qty_from_right'])) { $qty_from_right = trim($_REQUEST['qty_from_right']); }
    if (isset($_REQUEST['price_from_right'])) { $price_from_right = trim($_REQUEST['price_from_right']); }

	$search_index = $search_field-1;

	$qty_index = false;
	if ($qty_field) { $qty_index = $qty_field-1; }

	$price_index = false;
	if ($price_field) { $price_index = $price_field-1; }

    if (! isset($startDate)) { $startDate = format_date($today,'m-d-Y',array('d'=>-7)); }
    else { $startDate = format_date($startDate,'m-d-Y'); }
    if (isset($_REQUEST['startDate']) AND preg_match('/^[0-9]{2}.[0-9]{2}.[0-9]{4}$/',$_REQUEST['startDate'])) { $startDate = $_REQUEST['startDate']; }
    $endDate = format_date($today,'m-d-Y');
    if (isset($_REQUEST['endDate']) AND preg_match('/^[0-9]{2}.[0-9]{2}.[0-9]{4}$/',$_REQUEST['endDate'])) { $endDate = $_REQUEST['endDate']; }

	$favorites = 0;
	if (isset($_REQUEST['favorites'])) { $favorites = 1; }
	$invlistid = 0;
	if (isset($_REQUEST['invlistid']) AND is_numeric($_REQUEST['invlistid']) AND $_REQUEST['invlistid']>0) {
		// validate id in uploads table
		$query = "SELECT * FROM uploads WHERE id = '".res($_REQUEST['invlistid'])."'; ";
		$result = qdb($query);
		if (mysqli_num_rows($result)==1) {
			$invlistid = $_REQUEST['invlistid'];
		}
	}
?>
	<div id="loading-bar">Loading...</div>

	<div id="loader" class="loader text-muted">
		<div>
			<i class="fa fa-refresh fa-5x fa-spin"></i><br/>
			<h1 id="loader-message">Please wait while your RFQ is being sent...</h1>
		</div>
	</div>

	<form class="form-inline search-form" method="post" action="/" enctype="multipart/form-data" >

    <!-- navbar -->
    <header class="navbar navbar-inverse" role="banner">
        <div class="navbar-header">
            <button class="navbar-toggle" type="button" data-toggle="collapse" data-target=".navbar-collapse"><!-- id="menu-toggler">-->
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="logo" href="/" title="home">
                <img src="img/logo-white.png" alt="logo" />
            </a>
			<div class="search-center">
			  <div class="form-group search-group">
				<div class="input-group">
					<span class="input-group-btn">
						<button class="btn btn-default advanced-search" type="button"><i class="fa fa-list-ol"></i> <sup><i class="fa fa-sort-desc options-toggle"></i></sup></button>
					</span>
	                <input class="form-control" type="text" name="s" id="s" value="<?php echo trim($s); ?>" placeholder="Search..." <?php if ($_SERVER["PHP_SELF"]<>'/accounts.php') { echo 'autofocus'; } ?> />
                	<span class="input-group-btn">
	                	<button class="btn btn-primary" type="submit"><i class="fa fa-search"></i></button>
	                </span>
                </div><!-- /input-group -->
              </div><!-- /form-group -->
			</div>
        </div>
		<div class="collapse navbar-collapse text-center">
          <ul class="nav navbar-nav pull-left"><!-- pull-right hidden-xs">-->
            <li class="hidden-xs hidden-sm">
				<a href="/profile.php"><i class="fa fa-book"></i><span> Companies</span></a>
			</li>
            <li class="hidden-xs hidden-sm">
				<a href="#"><i class="fa fa-wrench"></i><span> Repair Home</span></a>
			</li>
            <li class="hidden-xs hidden-sm">
				<a href="#"><i class="fa fa-truck"></i><span> Shipping Home</span></a>
			</li>
          </ul>
          <ul class="nav navbar-nav pull-right"><!-- pull-right hidden-xs">-->
            <li class="hidden-xs hidden-sm">
				<a href="/accounts.php"><i class="fa fa-building-o"></i><span> Accounts Home</span></a>
			</li>
            <li class="dropdown">
                <a href="#" class="dropdown-toggle hidden-xs hidden-sm" data-toggle="dropdown">
                    <i class="fa fa-tasks"></i>
                    <span>Reports</span>
                    <b class="caret"></b>
                </a>
                <ul class="dropdown-menu">
                    <li><a href="/supply_demand.php"><i class="fa fa-line-chart"></i> Supply and Demand</a></li>
                    <li style="padding-left:22px; font-size:13px; color:gray"><i class="fa fa-minus-circle"></i> Profits &amp; Loss (tbd)</li>
				</ul>
			</li>
            <li class="hidden-xs hidden-sm">
				<a href="/amea.php"><i class="fa fa-female"></i><span> Améa</span></a>
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
            <li class="dropdown">
                <a href="#" class="dropdown-toggle hidden-xs hidden-sm" data-toggle="dropdown">
                    <?php echo $U['name']; ?>
                    <b class="caret"></b>
                </a>
                <ul class="dropdown-menu">
<?php
	$query = "SELECT name, users.id FROM users, contacts WHERE users.contactid = contacts.id AND users.id <> '".$U['id']."'; ";
	$result = qdb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		echo '<li><a href="/switch_user.php?userid='.$r['id'].'">'.$r['name'].'</a></li>';
	}
?>
<!--
                    <li><a href="#"><i class="fa fa-user"></i> Personal info</a></li>
                    <li><a href="#"><i class="fa fa-calendar-o"></i> Calendar</a></li>
                    <li><a href="#">Submit issue</a></li>
                    <li><a href="#">Logout</a></li>
-->
                </ul>
            </li>
          </ul><!-- end navbar-collapse -->
		</div>
    </header>
    <!-- end navbar -->

<?php
	// verizon morning bid
	$morning_bid = date("m/d/Y 10:00")." AM";
	$afternoon_bid = date("m/d/Y 12:00")." PM";
	$N = date("N");
	if ($N>=5) {//friday should have a monday expiration
		// if friday, 3 days away (8-5); if saturday, 2 days away (8-6); if sunday, 1 day away (8-7)
		$evening_bid = format_date(date("m-d-Y 07:00"),"m/d/Y g:i",array("d"=>8-$N))." AM";
		//$tomoro_j = format_date(date("m-d-Y"),"j",array("d"=>8-$N));
	} else {
		$evening_bid = format_date(date("m-d-Y 07:00"),"m/d/Y g:i",array("d"=>1))." AM";
		//$tomoro_j = format_date(date("m-d-Y"),"j",array("d"=>1));
	}
	if ($now>=$today." 07:33:00" AND $now<=$today." 08:45:00") {
		$expDate = $morning_bid;
	} else if ($now>=$today." 09:33:00" AND $now<=$today." 10:45:00") {//vz afternoon bid
		$expDate = $afternoon_bid;
	} else if ($now>=$today." 13:33:00" AND $now<=$today." 14:30:00") {//vz evening bid
		$expDate = $evening_bid;
	} else {
		// default is one week away, for things like inventory and WTS lists that may not necessariy have an immediate expiration
		$expDate = format_date(date("m-d-Y 17:00"),"m/d/Y g:i A",array('d'=>7));
	}
?>

	<div id="advanced-search-options" class="animated fadeInDown hidden">
		<div class="row">
			<div class="col-sm-3 options-group">
				<div class="text-center">
	                <p>Date Range:</p>
					<p>
		                <a href="javascript:void(0);" class="btn btn-default btn-sm datepicker-date" data-date-format="mm-dd-yyyy" data-date="<?php echo $startDate; ?>" data-target="startDate"><span><?php echo $startDate; ?></span></a>
		                <input type="hidden" name="startDate" id="startDate" value="<?php echo $startDate; ?>">
		                to
		                <a href="javascript:void(0);" class="btn btn-default btn-sm datepicker-date" id="dp2" data-date-format="mm-dd-yyyy" data-date="<?php echo $endDate; ?>"><span id="dp2Label"><?php echo $endDate; ?></span></a>
		                <input type="hidden" name="endDate" id="endDate" value="<?php echo $endDate; ?>">
					</p>
				</div>
				<div class="text-center lists-manager">
					<p>Lists Manager:</p>
					<p>
						<input name="upload_file" type="file" id="upload-file" class="file-upload" />
						<select name="upload_listid" id="upload-listid" class="lists-selector">
							<option value="">- Upload or Select a List -</option>
						</select>
						<button type="button" class="btn btn-primary btn-sm btn-upload">GO</button>
					</p>
					<div class="upload-options animated fadeIn hidden">
						<p>
							<div class="form-group" style="padding-right:8px">
								<select name="upload_companyid" id="upload-companyid" class="company-selector">
									<option value="">- Select a Company -</option>
								</select>
							</div>
							<div class="form-group" style="padding-left:8px">
								<input type="radio" name="upload_type" class="upload-type hidden" value="Req">
								<input type="radio" name="upload_type" class="upload-type hidden" value="Avail">
								<div class="slider-frame success">
									<span data-on-text="Avail" data-off-text="Req" class="slider-button" id="upload-slider">Req</span>
								</div>
							</div>
						</p>
					</div>
					<div class="upload-options animated fadeIn hidden">
						<div class="row">
							<div class="col-sm-5">
								<div class="btn-group">
<!--
									<button class="left btn btn-default btn-sm btn-expdate" type="button" data-date="<?php echo $morning_bid; ?>"><i class="fa fa-hourglass-1"></i></button>
									<button class="middle btn btn-default btn-sm btn-expdate" type="button" data-date="<?php echo $afternoon_bid; ?>"><i class="fa fa-hourglass-2"></i></button>
									<button class="right btn btn-default btn-sm btn-expdate" type="button" data-date="<?php echo $evening_bid; ?>"><i class="fa fa-hourglass-3"></i></button>
-->
									<button class="btn btn-default btn-expdate fa-stack fa-lg" type="button" data-date="<?php echo $morning_bid; ?>"><i class="fa fa-calendar-o fa-stack-2x"></i><span class="calendar-text">10a</span></button>
									<button class="btn btn-default btn-expdate fa-stack fa-lg" type="button" data-date="<?php echo $afternoon_bid; ?>"><i class="fa fa-calendar-o fa-stack-2x"></i><span class="calendar-text">12p</span></button>
									<button class="btn btn-default btn-expdate fa-stack fa-lg" type="button" data-date="<?php echo $evening_bid; ?>"><i class="fa fa-calendar-o fa-stack-2x"></i><span class="calendar-text">7a</span></button>
								</div>
							</div>
							<div class="col-sm-7">
				                <div class="input-group date datetime-picker">
	   		    			         <input type="text" name="expDate" id="exp-date" class="form-control input-sm" value="<?php echo $expDate; ?>" />
	           		       			 <span class="input-group-addon">
			       		                 <span class="fa fa-calendar"></span>
	       					         </span>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="text-center">
					<p>
					</p>
				</div>
			</div>
			<div class="col-sm-6">
				<textarea name="s2" id="s2" rows="12" placeholder="Enter multi-line searches here"><?php echo trim($s2); ?></textarea>
				<button class="btn btn-primary btn-submit" type="submit">Search</button>
			</div>
			<div class="col-sm-3 options-group text-left">
				<div class="row">
					<div class="col-sm-4 text-center">
						<p>Search:</p>
						<p>
							<div class="form-group">
			                   <input type="text" name="search_field" value="1" class="form-control input-xs" size="2">
							</div>
							<div class="form-group">
			                   <label for="searchFromRight"><i class="fa fa-long-arrow-left"></i> <input type="checkbox" name="search_from_right" id="searchFromRight" value="1"></label>
							</div>
						</p>
					</div>
					<div class="col-sm-4 text-center">
						<p>Qty:</p>
						<p>
							<div class="form-group">
			                  	<input type="text" name="qty_field" value="2" class="form-control input-xs" size="2">
							</div>
							<div class="form-group">
			                  	<label for="qtyFromRight"><i class="fa fa-long-arrow-left"></i> <input type="checkbox" name="qty_from_right" id="qtyFromRight" value="1"></label>
							</div>
						</p>
					</div>
					<div class="col-sm-4 text-center">
						<p>Price:</p>
						<p>
							<div class="form-group">
			                  	<input type="text" name="price_field" value="" class="form-control input-xs" size="2">
							</div>
							<div class="form-group">
			                  	<label for="priceFromRight"><i class="fa fa-long-arrow-left"></i> <input type="checkbox" name="price_from_right" id="priceFromRight" value="1"></label>
							</div>
						</p>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-12">
						<p><label><input type="radio">ERB3 qty2 &nbsp; T3PQAE7</label></p>
						<p><label><input type="radio">qty2- ERB3 &nbsp; T3PQAE7</label></p>
						<p><label><input type="radio">ERB3 &nbsp; T3PQAE7 &nbsp; qty2</label></p>
					</div>
				</div>
				<hr>
				<div class="row">
					<div class="col-sm-6">
						<input type="checkbox" name="favorites" id="favorites" value="1" class="hidden">
						<button type="button" class="btn btn-default btn-xs btn-favorites"><i class="fa fa-star"></i></button>
					</div>
					<div class="col-sm-6">
						<div class="form-group">
		                  	<input type="text" name="value_min" value="" class="form-control input-xs" size="6" placeholder="min price">
						</div>
						<div class="form-group">
		                  	<input type="text" name="value_max" value="" class="form-control input-xs" size="6" placeholder="max price">
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	</form>

<?php
	if ($s2) { $s = $s2; }
?>
