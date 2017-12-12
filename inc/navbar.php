<?php
	include_once 'dbconnect.php';
	include_once 'format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/modal/alert.php';
	include_once $_SERVER["ROOT_DIR"].'/modal/trello.php';
	include_once 'notifications.php';
	include_once 'displayTabs.php';

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

	$search_from_right = '';
	$qty_from_right = '';
	$price_from_right = '';
    if (isset($_REQUEST['search_from_right'])) { $search_from_right = trim($_REQUEST['search_from_right']); }
    if (isset($_REQUEST['qty_from_right'])) { $qty_from_right = trim($_REQUEST['qty_from_right']); }
    if (isset($_REQUEST['price_from_right'])) { $price_from_right = trim($_REQUEST['price_from_right']); }

	$search_index = $search_field-1;

	$qty_index = false;
	if ($qty_field) { $qty_index = $qty_field-1; }

	$price_index = false;
	if ($price_field) { $price_index = $price_field-1; }

    if (! isset($startDate)) { $startDate = ''; }//format_date($today,'m-d-Y',array('d'=>-7)); }
    else { $startDate = format_date($startDate,'m/d/Y'); }
    if (isset($_REQUEST['startDate']) AND preg_match('/^[0-9]{2}.[0-9]{2}.[0-9]{4}$/',$_REQUEST['startDate'])) { $startDate = $_REQUEST['startDate']; }
	if (! isset($endDate)) { $endDate = format_date($today,'m/d/Y'); }
    if (isset($_REQUEST['endDate']) AND preg_match('/^[0-9]{2}.[0-9]{2}.[0-9]{4}$/',$_REQUEST['endDate'])) { $endDate = $_REQUEST['endDate']; }

	/* FILTERS */
	$sales_count = false;
	if (isset($_REQUEST['sales_count']) AND trim($_REQUEST['sales_count'])<>'') { $sales_count = trim($_REQUEST['sales_count']); }
	$sales_min = false;
	if (isset($_REQUEST['sales_min']) AND trim($_REQUEST['sales_min'])<>'') { $sales_min = trim($_REQUEST['sales_min']); }
	$sales_max = false;
	if (isset($_REQUEST['sales_max']) AND trim($_REQUEST['sales_max'])<>'') { $sales_max = trim($_REQUEST['sales_max']); }
	$stock_min = false;
	if (isset($_REQUEST['stock_min']) AND trim($_REQUEST['stock_min'])<>'') { $stock_min = trim($_REQUEST['stock_min']); }
	$stock_max = false;
	if (isset($_REQUEST['stock_max']) AND trim($_REQUEST['stock_max'])<>'') { $stock_max = trim($_REQUEST['stock_max']); }
	$demand_min = false;
	if (isset($_REQUEST['demand_min']) AND trim($_REQUEST['demand_min'])<>'') { $demand_min = trim($_REQUEST['demand_min']); }
	$demand_max = false;
	if (isset($_REQUEST['demand_max']) AND trim($_REQUEST['demand_max'])<>'') { $demand_max = trim($_REQUEST['demand_max']); }
	$dq_count = false;
	if (isset($_REQUEST['dq_count']) AND trim($_REQUEST['dq_count'])<>'') { $dq_count = trim($_REQUEST['dq_count']); }
	$favorites = 0;
	if (isset($_REQUEST['favorites']) AND $_REQUEST['favorites']) { $favorites = 1; }

	$invlistid = 0;
	if (isset($_REQUEST['invlistid']) AND is_numeric($_REQUEST['invlistid']) AND $_REQUEST['invlistid']>0) {
		// validate id in uploads table
		$query = "SELECT * FROM uploads WHERE id = '".res($_REQUEST['invlistid'])."'; ";
		$result = qdb($query);
		if (mysqli_num_rows($result)==1) {
			$invlistid = $_REQUEST['invlistid'];
		}
	}

	/***** SEARCH MODE *****/
	/*
		This determines where the user is sent when they submit the search field
	*/
//	$modes = array('/services.php','/repairs.php','/operations.php','/inventory.php','/','/accounts.php','/job.php');
//	$mode = str_replace('index.php','',$_SERVER["PHP_SELF"]);
//	$mode_index = array_search($mode,$modes);
	if (isset($_REQUEST['SEARCH_MODE']) AND $_REQUEST['SEARCH_MODE']) {
		$SEARCH_MODE = preg_replace('/^(https?:\/\/[[:alnum:]_.-]*)(\/[[:alnum:]_.-]*)(\?.*)?$/','$2',$_REQUEST['SEARCH_MODE']);
	} else if (isset($_COOKIE['SEARCH_MODE'])) {
		//$SEARCH_MODE = $_COOKIE['SEARCH_MODE'];
		$SEARCH_MODE = preg_replace('/^(https?:\/\/[[:alnum:]_.-]*)(\/[[:alnum:]_.-]*)(\?.*)?$/','$2',$_COOKIE['SEARCH_MODE']);
	} else {
		$SEARCH_MODE = '/';//default
		// if the current page is one of the allowable $modes, set it to the global variable so we use it as our submit page
//		if ($mode_index!==false) { $SEARCH_MODE = $modes[$mode_index]; }
	}
?>
	<!-- Please add this css into the overrides css when it is complete -->
	<style type="text/css">
		.list-group-item.active, .list-group-item.active:hover, .list-group-item.active:focus {
			background: rgb(60, 91, 121);
			border-color: rgb(60, 91, 121);
		}
		.dropdown-menu > li > a.active, .dropdown-menu > li > a.active:hover, .dropdown-menu > li > a.active:focus, .dropdown-submenu:hover > a.active, .dropdown-submenu:focus > a.active {
			background: rgb(60, 91, 121);
			color: #FFF;
		}
	</style>
	
	<div id="loading-bar">Loading...</div>

	<div id="loader" class="loader text-muted">
		<div>
			<i class="fa fa-refresh fa-5x fa-spin"></i><br/>
			<h1 id="loader-message">Please wait while your RFQ is being sent...</h1>
		</div>
	</div>

	<form class="form-inline search-form" method="post" action="<?php echo $SEARCH_MODE; ?>" enctype="multipart/form-data" >
	<input type="hidden" name="SEARCH_MODE" id="SEARCH_MODE" value="<?php echo $SEARCH_MODE; ?>">

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
					<button class="btn btn-default advanced-search hidden-xs hidden-sm" type="button"><i class="fa fa-list-ol"></i> <sup><i class="fa fa-sort-desc options-toggle"></i></sup></button>
				</span>
                <input class="form-control" type="text" name="s" id="s" value="<?php echo trim($s); ?>" placeholder="Search..." <?php if ($_SERVER["PHP_SELF"]<>'/accounts.php' AND $_SERVER["PHP_SELF"]<>'/services.php') { echo ''; } ?> />
            	<span class="input-group-btn">
                	<button class="btn btn-primary" type="submit"><i class="fa fa-search"></i></button>
                </span>
            </div><!-- /input-group -->
          </div><!-- /form-group -->
			</div>
        </div>
		<div class="collapse navbar-collapse text-center">
          <ul class="nav navbar-nav pull-left"><!-- pull-right hidden-xs">-->
			<?php if(in_array("1", $USER_ROLES) || in_array("5", $USER_ROLES) || in_array("7", $USER_ROLES) || in_array("4", $USER_ROLES)) { ?>
			<li class="hidden-xs hidden-sm">
				<a href="/profile.php"><i class="fa fa-building"></i> Companies</a>
			</li>
			<?php } ?>
			<?php echo displayTabs('left',$SEARCH_MODE); ?>
          </ul>

          <ul class="nav navbar-nav"><!-- pull-right hidden-xs">-->
			<?php echo displayTabs('mobile',$SEARCH_MODE, true); ?>
          </ul>
          
          <ul class="nav navbar-nav pull-right"><!-- pull-right hidden-xs">-->
			<?php echo displayTabs('right',$SEARCH_MODE); ?>
			<?php if(in_array("1", $USER_ROLES) || in_array("5", $USER_ROLES) || in_array("7", $USER_ROLES) || in_array("4", $USER_ROLES)) { ?>
	            <li class="dropdown">
	                <a href="#" class="dropdown-toggle hidden-xs hidden-sm" data-toggle="dropdown">
	                    <i class="fa fa-tasks"></i>
	                    <span>Reports</span>
	                    <b class="caret"></b>
	                </a>
	                <ul class="dropdown-menu text-left">
<!--
	                    <li><a href="/manage_inventory.php"><i class="fa fa-list-alt"></i> Inventory</a></li>
-->
	                    <li><a href="/shipping_report.php"><i class="fa fa-truck"></i> Shipping</a></li>
                    	<li><a href="/repair_export.php"><i class="fa fa-wrench"></i> Repairs</a></li>
	                    <li><a href="/rma_report.php"><i class="fa fa-info-circle"></i> Returns</a></li>
	                    <li><a href="/supply_demand.php"><i class="fa fa-line-chart"></i> Supply and Demand</a></li>
	                    <li><a href="/profit_loss.php"><i class="fa fa-money"></i> Profit and Loss</a></li>
	                    <?php if(in_array("4", $USER_ROLES)) { ?>
	                    	<li><a href="/commissions.php"><i class="fa fa-percent"></i> Commissions</a></li>
	                    	<li><a href="/timesheet.php"><i class="fa fa-clock-o"></i> Timesheets</a></li>
	                    <?php } ?>
	<!--
	                    <li style="padding-left:22px; font-size:13px; color:gray"><i class="fa fa-minus-circle"></i> Profits &amp; Loss (tbd)</li>
	-->
					</ul>
				</li>
			<?php } ?>
            <li class="notification-dropdown hidden-xs hidden-sm">
<?php
	$num_notifications = count($NOTIFICATIONS);
	$notif_suffix = '';
	if ($num_notifications<>1) { $notif_suffix = 's'; }
	if ($num_notifications==0) { $read_notifications = 'no'; }
	else { $read_notifications = $num_notifications; }
?>
                <a href="javascript:void(0);" class="trigger">
                    <i class="fa fa-comments-o"></i>
                    <?php if ($num_notifications>0) { echo '<span class="count" style="background:#b94a48">'.$num_notifications.'</span>'; } ?>
                </a>
                <div class="pop-dialog">
                    <div class="pointer right">
                        <div class="arrow"></div>
                        <div class="arrow_border"></div>
                    </div>
                    <div class="body">
                        <a href="#" class="close-icon"><i class="fa fa-close"></i></a>
                        <h5>You have <?php echo $read_notifications; ?> new notification<?php echo $notif_suffix; ?></h5>
                        <div class="notifications"></div>
                    </div>
                </div>
            </li>
            <li class="dropdown text-left">
                <a href="#" class="dropdown-toggle hidden-xs hidden-sm" data-toggle="dropdown">
					<i class="fa fa-user"></i><span> <?php echo $U['name']; ?></span>
                    <b class="caret"></b>
				</a>
                <ul class="dropdown-menu dropdown-menu-right">
                	<?php if(in_array("1",$USER_ROLES) OR in_array("5",$USER_ROLES) OR in_array("4",$USER_ROLES) OR in_array("7",$USER_ROLES)) { ?>
		            	<li class="hidden-xs hidden-sm"><a href="/amea.php"><i class="fa fa-female"></i><span> Améa</span></a></li>
		            <?php } ?>
                	<li><a class="<?php echo ($pageName == 'user_profile.php' ? 'active' : ''); ?>" href="user_profile.php">User Information</a></li>
	                <!-- Get the ID of admin and print it out, in case ID's change as long as Admin exists the ID will be pulled -->
	                <?php if($USER_ROLES[array_search(array_search('Management', $ROLES), $USER_ROLES)] == array_search('Management', $ROLES)) { ?>
		                <li><a class="<?php echo ($pageName == 'edit_user.php' ? 'active' : ''); ?>" href="edit_user.php">Add/Edit Users</a></li>
                        <li><a class="<?php echo ($pageName == 'user_commissions.php' ? 'active' : ''); ?>" href="user_commissions.php">Commissions</a></li>
		                <li><a class="<?php echo ($pageName == 'page_permissions.php' ? 'active' : ''); ?>" href="page_permissions.php">Page Permissions</a></li>
		                <li><a class="<?php echo ($pageName == 'password.php' ? 'active' : ''); ?>" href="password.php">Password Policy</a></li>
		                <li><a class="<?php echo ($pageName == 'ghost_settings.php' ? 'active' : ''); ?>" href="ghost_settings.php">Ghost Settings</a></li>
		                <li><a class="<?php echo ($pageName == 'system_settings.php' ? 'active' : ''); ?>" href="system_settings.php"><i class="fa fa-cog"></i> System Settings</a></li>
	                <?php } ?>
	                <hr>
	                <li><a href="/expenses.php"><i class="fa fa-credit-card"></i> My Expenses</a></li>
	                <li><a href="#"><i class="fa fa-cutlery" aria-hidden="true"></i> Break Mode</a></li>
	                <li><a href="signout.php">Logout</a></li>

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

	<div id="advanced-search-options" class="hidden">
		<div class="row">
			<div class="col-sm-3 options-group">
				<div class="text-center lists-manager">
					<p>
						<input name="upload_file" type="file" id="upload-file" class="file-upload" />
						<select name="upload_listid" id="upload-listid" class="lists-selector">
							<option value="">Upload or Select a List...</option>
						</select>
						<div id="upload-details" class="hidden">
							<div class="content-box box-default">
								<div class="content-box-header"></div>
								<div class="content-box-body"></div>
								<div class="content-box-footer"></div>
							</div>
						</div>
						<div id="upload-options" class="hidden">
							<div class="content-box box-default">
								<div class="content-box-header">
									<select name="upload_companyid" id="upload-companyid" class="company-selector">
										<option value="">- Select a Company -</option>
									</select>
								</div>
								<div class="content-box-body">
									<div class="row">
										<div class="col-sm-6">
											<p>
												<div class="slider-frame success">
													<!-- include radio's inside slider-frame to set appropriate actions to them -->
													<input type="radio" name="upload_type" class="upload-type hidden" value="Req">
													<input type="radio" name="upload_type" class="upload-type hidden" value="Avail">
													<span data-on-text="Avail" data-off-text="Req" class="slider-button upload-slider" id="upload-slider">Req</span>
												</div>
												<div class="btn-group btn-group-bids">
													<button class="btn btn-default btn-expdate fa-stack fa-lg" type="button" data-date="<?php echo $morning_bid; ?>"><i class="fa fa-calendar-o fa-stack-2x"></i><span class="calendar-text">10a</span></button>
													<button class="btn btn-default btn-expdate fa-stack fa-lg" type="button" data-date="<?php echo $afternoon_bid; ?>"><i class="fa fa-calendar-o fa-stack-2x"></i><span class="calendar-text">12p</span></button>
													<button class="btn btn-default btn-expdate fa-stack fa-lg" type="button" data-date="<?php echo $evening_bid; ?>"><i class="fa fa-calendar-o fa-stack-2x"></i><span class="calendar-text">7a</span></button>
												</div>
											</p>
										</div><!-- col-sm-6 -->
										<div class="col-sm-6">
											<p>
								                <div class="input-group datepicker-datetime date datetime-picker" data-hposition="right">
					   		    			         <input type="text" name="expDate" id="exp-date" class="form-control input-sm" value="<?php echo $expDate; ?>" />
					           		       			 <span class="input-group-addon">
							       		                 <span class="fa fa-calendar"></span>
					       					         </span>
												</div>
											</p>
										</div><!-- col-sm-6 -->
									</div><!-- row -->
								</div>
								<div class="content-box-footer">
									<p>
										<button type="button" class="btn btn-primary btn-sm btn-upload btn-action" title="upload this file"><i class="fa fa-upload"></i></button>
									</p>
									<p class="info text-left">
										<ul class="fa-ul text-left" style="font-size:10px; margin-bottom:0; padding-bottom:0">
											<li><i class="fa-li fa fa-asterisk text-danger"></i> Column headers are optional but recommended, and case insensitive
											<li><i class="fa-li fa fa-asterisk text-danger"></i> Duplicate headers (i.e., "Part" &amp; "Item") are not allowed
											<li><i class="fa-li fa fa-asterisk text-danger"></i> Required columns: Qty, and either Part or HECI. Allowed headers:
										</ul>
										<ul class="fa-ul fa-nav text-center">
											<li><i class="fa-li fa fa-check text-danger"></i> Part, Item, Model, MPN
											<li><i class="fa-li fa fa-check text-danger"></i> Qty, Quantity, Qnty, Count
											<li><i class="fa-li fa fa-check text-danger"></i> HECI, CLEI
										</ul>
									</p>
								</div>
							</div>
						</div>
<!--
-->
					</p>
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
				<div class="row header-border">
					<h4 class="text-primary">Filters</h4>
				</div>
				<div class="row">
					<div class="col-sm-4 text-center">
						<span class="info">Search</span>
						<p>
							<div class="form-group">
			                   <input type="text" name="search_field" value="1" class="form-control input-xs" size="2">
							</div>
							<div class="form-group">
			                   <label for="searchFromRight"><i class="fa fa-long-arrow-left"></i> <input type="checkbox" name="search_from_right" id="searchFromRight" class="" value="1"></label>
							</div>
						</p>
					</div>
					<div class="col-sm-4 text-center">
						<span class="info">Qty</span>
						<p>
							<div class="form-group">
			                  	<input type="text" name="qty_field" value="2" class="form-control input-xs" size="2">
							</div>
							<div class="form-group">
			                  	<label for="qtyFromRight"><i class="fa fa-long-arrow-left"></i> <input type="checkbox" name="qty_from_right" id="qtyFromRight" value="1" class=""></label>
							</div>
						</p>
					</div>
					<div class="col-sm-4 text-center">
						<span class="info">Price</span>
						<p>
							<div class="form-group">
			                  	<input type="text" name="price_field" value="" class="form-control input-xs" size="2">
							</div>
							<div class="form-group">
			                  	<label for="priceFromRight"><i class="fa fa-long-arrow-left"></i> <input type="checkbox" name="price_from_right" id="priceFromRight" value="1" class=""></label>
							</div>
						</p>
					</div>
				</div>
<!--
				<div class="row">
					<div class="col-sm-12">
						<p><label><input type="radio">ERB3 qty2 &nbsp; T3PQAE7</label></p>
						<p><label><input type="radio">qty2- ERB3 &nbsp; T3PQAE7</label></p>
						<p><label><input type="radio">ERB3 &nbsp; T3PQAE7 &nbsp; qty2</label></p>
					</div>
				</div>
-->
				<div class="row">
					<div class="col-sm-2 text-center">
						<span class="info">Favorites</span>
						<p>
							<input type="checkbox" name="favorites" id="favorites" value="1" class="hidden">
							<button type="button" class="btn btn-default btn-xs btn-favorites"><i class="fa fa-star"></i></button>
						</p>
					</div>
					<div class="col-sm-2 text-center">
						<span class="info">Min DQ</span>
						<p>
							<input type="text" name="dq_count" value="" class="form-control input-xs" size="3" placeholder="0">
						</p>
					</div>
					<div class="col-sm-8 text-center">
						<span class="info">Date Range</span>
						<p>
							<div class="form-group">
				                <div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY" data-maxdate="<?php echo date("m/d/Y"); ?>" data-hposition="right">
   			    			         <input type="text" name="startDate" id="startDate" class="form-control input-sm" value=""/>
   	        		       			 <span class="input-group-addon">
			       		                 <span class="fa fa-calendar"></span>
   	    					         </span>
								</div>
							</div>
							<div class="form-group">
				                <div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY" data-maxdate="<?php echo date("m/d/Y"); ?>" data-hposition="right">
   			    			         <input type="text" name="endDate" id="endDate" class="form-control input-sm" value="<?php echo date("m/d/Y"); ?>"/>
   	        		       			 <span class="input-group-addon">
			       		                 <span class="fa fa-calendar"></span>
   	    					         </span>
								</div>
							</div>
						</p>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-2 text-center">
						<span class="info">Min Sales</span>
						<p>
							<div class="form-group">
			                  	<input type="text" name="sales_count" value="" class="form-control input-xs" size="3" placeholder="0">
							</div>
						</p>
					</div>
					<div class="col-sm-4 text-center">
						<span class="info">Sales $$$</span>
						<p>
							<div class="form-group">
			                  	<input type="text" name="sales_min" value="" class="form-control input-xs" size="5" placeholder="min">
							</div>
							<div class="form-group">
			                  	<input type="text" name="sales_max" value="" class="form-control input-xs" size="5" placeholder="max">
							</div>
						</p>
					</div>
					<div class="col-sm-3 text-center">
						<span class="info">Stock Qty</span>
						<p>
							<div class="form-group">
			                  	<input type="text" name="stock_min" value="" class="form-control input-xs" size="3" placeholder="0">
							</div>
							<div class="form-group">
			                  	<input type="text" name="stock_max" value="" class="form-control input-xs" size="3" placeholder="9999">
							</div>
						</p>
					</div>
					<div class="col-sm-3 text-center" data-toggle="tooltip" data-placement="left" title="Uniquely-Dated Requests">
						<span class="info">Requests</span>
						<p>
							<div class="form-group">
			                  	<input type="text" name="demand_min" value="" class="form-control input-xs" size="3" placeholder="0">
							</div>
							<div class="form-group">
			                  	<input type="text" name="demand_max" value="" class="form-control input-xs" size="3" placeholder="9999">
							</div>
						</p>
					</div>
					<div class="col-sm-3">
					</div>
				</div>
			</div>
		</div>
	</div><!-- advanced-search-options -->

	</form>

<?php
	if ($s2) { $s = $s2; }
	if (! isset($ALERTS)) { $ALERTS = array(); }//initialize for alert modal, see inc/footer.php
?>
