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
    if (isset($_REQUEST['price_field'])) { $price_field = trim($_REQUEST['price_field']); }

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
?>

	<form class="form-inline search-form" method="post" action="/" enctype="multipart/form-data" >

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
				<div class="form-group search-group">
					<div class="input-group">
						<span class="input-group-btn">
							<button class="btn btn-default advanced-search" type="button"><i class="fa fa-list-ol"></i> <sup><i class="fa fa-sort-desc options-toggle"></i></sup></button>
						</span>
		                <input class="form-control" type="text" name="s" id="s" value="<?php echo trim($s); ?>" placeholder="Search..." autofocus />
	                	<span class="input-group-btn">
		                	<button class="btn btn-primary" type="submit"><i class="fa fa-search"></i></button>
		                </span>
	                </div><!-- /input-group -->
                </div><!-- /form-group -->
            </li>
            <li class="hidden-xs hidden-sm">
				<a href="/accounts.php"><i class="fa fa-building-o"></i> Accounts Home</a>
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

	<div id="advanced-search-options" class="animated fadeInDown hidden">
		<div class="row">
			<div class="col-sm-3 options-group">
				<div class="text-center">
	                Date Range:<br/>
	                <a href="javascript:void(0);" class="btn btn-default btn-sm" id="dp1" data-date-format="mm-dd-yyyy" data-date="<?php echo $startDate; ?>"><span id="startDateLabel"><?php echo $startDate; ?></span></a>
	                <input type="hidden" name="startDate" id="startDate" value="<?php echo $startDate; ?>">
	                to
	                <a href="javascript:void(0);" class="btn btn-default btn-sm" id="dp2" data-date-format="mm-dd-yyyy" data-date="<?php echo $endDate; ?>"><span id="endDateLabel"><?php echo $endDate; ?></span></a>
	                <input type="hidden" name="endDate" id="endDate" value="<?php echo $endDate; ?>">
				</div>
				<div class="text-center">
					<p>
						List Upload:
					</p>
					<div class="form-group">
						<input type="text" name="list_name" class="input-xs form-control" value="" size="14" placeholder="Name (optional)" />
					</div>
					<div class="form-group">
						<label for="inventory-file" id="invfile-label"><a class="btn btn-default btn-xs">Select .xls/.xlsx/.csv/.txt</a></label>
						<input name="invfile" type="file" id="inventory-file" class="file-upload">
					</div>
					<p>
						<select name="inv-companyid" id="inv-companyid" class="company-selector">
							<option value="">- Select a Company -</option>
						</select>
					</p>
					<p>
						<button type="submit" class="btn btn-primary btn-sm">Upload</button>
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
						Search:<br/>
						<div class="form-group">
		                   <input type="text" name="search_field" value="1" class="form-control input-xs" size="2">
						</div>
						<div class="form-group">
		                   <label for="searchFromRight"><i class="fa fa-long-arrow-left"></i> <input type="checkbox" name="search_from_right" id="searchFromRight" value="1"></label>
						</div>
					</div>
					<div class="col-sm-4 text-center">
						Qty:<br/>
						<div class="form-group">
		                  	<input type="text" name="qty_field" value="2" class="form-control input-xs" size="2">
						</div>
						<div class="form-group">
		                  	<label for="qtyFromRight"><i class="fa fa-long-arrow-left"></i> <input type="checkbox" name="qty_from_right" id="qtyFromRight" value="1"></label>
						</div>
					</div>
					<div class="col-sm-4 text-center">
						Price:<br/>
						<div class="form-group">
		                  	<input type="text" name="price_field" value="<?php echo $price_field; ?>" class="form-control input-xs" size="2">
						</div>
						<div class="form-group">
		                  	<label for="priceFromRight"><i class="fa fa-long-arrow-left"></i> <input type="checkbox" name="price_from_right" id="priceFromRight" value="1"></label>
						</div>
					</div>
				</div>
				<br/>
				<p><label><input type="radio">ERB3 qty2 &nbsp; T3PQAE7</label></p>
				<p><label><input type="radio">qty2- ERB3 &nbsp; T3PQAE7</label></p>
				<p><label><input type="radio">ERB3 &nbsp; T3PQAE7 &nbsp; qty2</label></p>
			</div>
		</div>
	</div>

	</form>
<?php
	if ($s2) { $s = $s2; }
?>
