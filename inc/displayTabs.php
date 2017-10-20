<?php
	/***** this function outputs tabs for use in navbar, $pos is used as 'left' or 'right' of central search field *****/

	$inventory_sub = '
                <ul class="dropdown-menu text-left animated-2x animated fadeIn">
                    <li><a href="/parts.php" class="mode-tab"><i class="fa fa-list-alt"></i> Manage Parts</a></li>
	';
	//if user is sales or management, they have a manage inventory link
	if (in_array("1",$USER_ROLES) OR in_array("5",$USER_ROLES) OR in_array("4",$USER_ROLES) OR in_array("7",$USER_ROLES)) {
		$inventory_sub .= '
                    <li><a href="/manage_inventory.php"><i class="fa fa-list-alt"></i> Manage Inventory</a></li>
		';
	}
	$inventory_sub .= '
				</ul>
	';

	// list of all tabs for iterative display to be able to match selected tab ($selected_tab) with appropriate tab
	$TABS = array(
		'left' =>
			array(
				array('action'=>'/profile.php','image'=>'<i class="fa fa-book"></i>','title'=>'Companies','aliases'=>array(),'sub'=>'','privilege'=>array(1,4,5,7)),
				array('action'=>'/services.php','image'=>'<i class="fa fa-cogs"></i>','title'=>'Services','aliases'=>array('/job.php'),'sub'=>'','privilege'=>array(1,4,5,7)),
				array(
					'action'=>'/services.php',
					'image'=>'<i class="fa fa-cogs"></i>',
					'title'=>'Services',
					'aliases'=>array(),
					'sub'=>'
                <ul class="dropdown-menu text-left animated-2x animated fadeIn dropdown-mega" style="min-width:200px">
					<li>
					  <div class="yamm-content">
						<div class="row">
							<div class="col-lg-12 col-md-12 col-sm-12 col-megamenu" style="height: 340px; border-right:0px">
                                <div class="megamenu-block">
									<h4 class="minimal" style="margin-top:5px; margin-left:10px"><a href="/services.php">Services</a></h4>
                                    <h4 class="megamenu-block-title">
									  <div class="form-group">
										<div class="input-group pull-left">
											<span class="input-group-btn">
												<a href="/order.php?order_type=Service" class="btn btn-default btn-xs bg-services" title="Start New Service"><i class="fa fa-plus"></i></a>
											</span>
											<input type="text" class="form-control input-xs order-search" placeholder="Services..." data-type="SO">
											<span class="input-group-btn">
												<button class="btn btn-primary btn-xs order-search-button" type="button"><i class="fa fa-search"></i></button>
											</span>
										</div>
									  </div>
									</h4>
                                    <ul id="service-orders-list">
                                    </ul>
                                </div>
                            </div>
						</div>
					  </div>
					</li>
				</ul>
					',
				),
				array(
					'action'=>'/operations.php',
					'image'=>'<i class="fa fa-truck"></i>',
					'title'=>'Operations',
					'aliases'=>array(),
					'sub'=>'
                <ul class="dropdown-menu text-left animated-2x animated fadeIn dropdown-mega">
					<li>
					  <div class="yamm-content">
						<div class="row">
							<div class="col-lg-6 col-md-6 col-sm-6 col-megamenu" style="height: 340px">
                                <div class="megamenu-block">
									<h4 class="minimal" style="margin-top:5px; margin-left:10px"><a href="/repairs.php">Repairs</a> - <a href="/builds.php">Builds</a></h4>
                                    <h4 class="megamenu-block-title">
									  <div class="form-group">
										<div class="input-group pull-left">
											<span class="input-group-btn">
												<a href="/order.php?order_type=Repair" class="btn btn-default btn-xs bg-repairs" title="Start New Repair"><i class="fa fa-plus"></i></a>
											</span>
											<input type="text" class="form-control input-xs order-search" placeholder="Repairs..." data-type="RO">
											<span class="input-group-btn">
												<button class="btn btn-primary btn-xs order-search-button" type="button"><i class="fa fa-search"></i></button>
											</span>
										</div>
										<!-- <a href="/accounts.php?orders_table=repairs" class="mode-tab"><i class="fa fa-money"></i> Repairs</a> -->
									  </div>
									</h4>
                                    <ul id="repair-orders-list">
                                    </ul>
                                </div>
                            </div>
							<div class="col-lg-6 col-md-6 col-sm-6 col-megamenu" style="height: 340px">
                                <div class="megamenu-block">
									<h4 class="minimal" style="margin-top:5px; margin-left:10px"><a href="/returns.php">Returns</a></h4>
                                    <h4 class="megamenu-block-title">
									  <div class="form-group">
										<div class="input-group pull-left">'.
									((in_array("1",$USER_ROLES) OR in_array("5",$USER_ROLES) OR in_array("4",$USER_ROLES) OR in_array("7",$USER_ROLES)) ?
											'<span class="input-group-btn">
												<a href="#" class="btn btn-default btn-xs bg-returns" title="Start New Return"><i class="fa fa-plus"></i></a>
											</span>'
									:'').
											'<input type="text" class="form-control input-xs order-search" placeholder="Returns..." data-type="RMA">
											<span class="input-group-btn">
												<button class="btn btn-primary btn-xs order-search-button" type="button"><i class="fa fa-search"></i></button>
											</span>
										</div>
									  </div>
									</h4>
                                    <ul id="return-orders-list">
                                    </ul>
                                </div>
                            </div>
<!--
                            <div class="col-lg-4 col-md-4 col-sm-4 col-megamenu" style="height: 340px; border-right: 0;">
                                <div class="megamenu-block">
									<h4 class="minimal" style="margin-top:5px; margin-left:10px"><a href="/builds.php">Builds</a></h4>
                                    <h4 class="megamenu-block-title">'
                                    . ((in_array("1",$USER_ROLES) OR in_array("5",$USER_ROLES) OR in_array("4",$USER_ROLES) OR in_array("7",$USER_ROLES)) ?
									  '<div class="form-group">
										<div class="input-group pull-left">
											<span class="input-group-btn">
												<a href="/builds_management.php" class="btn btn-default btn-xs bg-returns" title="Start New Build"><i class="fa fa-plus"></i></a>
											</span>
											<input type="text" class="form-control input-xs order-search" placeholder="Builds..." data-type="BO">
											<span class="input-group-btn">
												<button class="btn btn-primary btn-xs order-search-button" type="button"><i class="fa fa-search"></i></button>
											</span>
										</div>
									  </div>'
									  : '').
									'</h4>
                                    <ul id="build-orders-list">
                                    </ul>
                                </div>
                            </div>
-->
						</div>
					  </div>
					</li>
				</ul>
					',
				),
				array(
					'action'=>'/inventory.php',
					'image'=>'<i class="fa fa-qrcode"></i>',
					'title'=>'Inventory',
					'aliases'=>array('/manage_inventory.php','/parts.php'),
					'sub' => $inventory_sub,
				),
			),
		'right' =>
			array(
				array(
					'action'=>'/',
					'image'=>'<i class="fa fa-cubes"></i>',
					'title'=>'Sales',
					'aliases'=>array('/order_form.php'),
					'sub' => '
                <ul class="dropdown-menu dropdown-menu-left text-left animated-2x animated fadeIn dropdown-mega">
					<li>
					  <div class="yamm-content">
						<div class="row">
							<div class="col-lg-6 col-md-6 col-sm-6 col-megamenu" style="height: 340px">
                                <div class="megamenu-block">
									<h4 class="minimal" style="margin-top:5px; margin-left:10px"><a href="/sales_order.php">Sales</a></h4>
                                    <h4 class="megamenu-block-title">'
                                    . ((in_array("1",$USER_ROLES) OR in_array("5",$USER_ROLES) OR in_array("4",$USER_ROLES) OR in_array("7",$USER_ROLES)) ?
									  '<div class="form-group">
										<div class="input-group pull-left">
											<span class="input-group-btn">
												<a href="/order.php?order_type=Sale" class="btn btn-default btn-xs bg-sales" title="Start New SO"><i class="fa fa-plus"></i></a>
											</span>
											<input type="text" class="form-control input-xs order-search" placeholder="Sales..." data-type="SO">
											<span class="input-group-btn">
												<button class="btn btn-primary btn-xs order-search-button" type="button"><i class="fa fa-search"></i></button>
											</span>
										</div>
										<!-- <a href="/accounts.php?orders_table=sales" class="mode-tab"><i class="fa fa-money"></i> Sales</a> -->
									  </div>'
									  :'').
									'</h4>
                                    <ul id="sales-orders-list">
                                    </ul>
                                </div>
                            </div>
							<div class="col-lg-6 col-md-6 col-sm-6 col-megamenu" style="height: 340px">
                                <div class="megamenu-block">
									<h4 class="minimal" style="margin-top:5px; margin-left:10px"><a href="/purchase_order.php">Purchases</a> - <a href="/purchase_requests.php">Reqs</a></h4>
                                    <h4 class="megamenu-block-title">
<!--
										<a href="/accounts.php?orders_table=purchases" class="mode-tab"><i class="fa fa-shopping-cart"></i> Purchases</a> <span class="pull-right"><a href="/order_form.php?ps=Purchase" class="mode-tab" title="Start New PO"><i class="fa fa-plus"></i></a></span></h4>
-->'
										. ((in_array("1",$USER_ROLES) OR in_array("5",$USER_ROLES) OR in_array("4",$USER_ROLES) OR in_array("7",$USER_ROLES)) ?
									  '<div class="form-group">
										<div class="input-group pull-left">
											<span class="input-group-btn">
												<a href="/order.php?order_type=Purchase" class="btn btn-default btn-xs bg-purchases" title="Start New SO"><i class="fa fa-plus"></i></a>
											</span>
											<input type="text" class="form-control input-xs order-search" placeholder="Purchases..." data-type="PO">
											<span class="input-group-btn">
												<button class="btn btn-primary btn-xs order-search-button" type="button"><i class="fa fa-search"></i></button>
											</span>
										</div>
									  </div>'
									  :'').
									'</h4>
                                    <ul id="purchase-orders-list">
                                    </ul>
                                </div>
                            </div>
						</div>
					  </div>
					</li>
				</ul>
					',
				),
				/*array('action'=>'/accounts.php','image'=>'<i class="fa fa-building-o"></i>','title'=>'Accounts','aliases'=>array(),'sub'=>'',),*/
				array(
					'action'=>'/accounts.php',
					'image'=>'<i class="fa fa-building-o"></i>',
					'title'=>'Accounts',
					'aliases'=>array('/order_form.php'),
					'sub' => '
                <ul class="dropdown-menu text-left animated-2x animated fadeIn">
                    <li><a href="/transactions.php"><i class="fa fa-list-alt"></i> Transactions</a></li>
					<hr style="margin:0px">
					<li>
					  <div class="yamm-content">
						<div class="row">
                            <div class="megamenu-block">
								<h4 class="minimal" style="margin-top:5px; margin-left:10px"><a href="/invoices.php">Invoices</a> - <a href="/lumps.php">Lumps</a></h4>
                                <h4 class="megamenu-block-title">
								  <div class="form-group">
									<div class="input-group pull-left">
										<input type="text" class="form-control input-xs order-search" placeholder="Invoices..." data-type="INV">
										<span class="input-group-btn">
											<button class="btn btn-primary btn-xs order-search-button" type="button"><i class="fa fa-search"></i></button>
										</span>
									</div>
								  </div>
								</h4>
                                <ul id="repair-orders-list">
                                </ul>
                            </div><!-- .megamenu-block -->
						</div><!-- .row -->
					  </div><!-- .yamm-content -->
					</li>
				</ul>
					',
					'privilege'=>array(1,4,7),
				),
			),

		'mobile' =>
			array(
				array('action'=>'/profile.php','aliases'=>array(),'image'=>'<i class="fa fa-book"></i>','title'=>'Companies','privilege'=>array(1,4,5,7)),
				array('action'=>'/services.php','aliases'=>array(),'image'=>'<i class="fa fa-cogs"></i>','title'=>'Services','privilege'=>array(1,4,5,7)),
				array('action'=>'/operations.php','aliases'=>array(),'image'=>'<i class="fa fa-truck"></i>','title'=>'Operations',),
				array('action'=>'/inventory.php','aliases'=>array(),'image'=>'<i class="fa fa-qrcode"></i>','title'=>'Inventory',),
				array(
					'action'=>'/',
					'image'=>'<i class="fa fa-cubes"></i>',
					'title'=>'Sales',
					'aliases'=>array(),
					'sub'=>'',
					),
				array(
					'action'=>'/accounts.php',
					'image'=>'<i class="fa fa-building-o"></i>',
					'title'=>'Accounts',
					'aliases'=>array(),
					'sub'=>'',
					'privilege'=>array(1,4,7),
				),
			),
	);

	function displayTabs($pos='',$selected_tab='', $mobile = false) {
		global $TABS, $USER_ROLES;

		$tabs_arr = array();
		// if a position ($pos) is passed in, process only that portion; otherwise, get all from array
		if ($pos AND isset($TABS[$pos])) {
			$tabs_arr = $TABS[$pos];
		} else {
			foreach ($TABS as $tabs_pos) {
				$tabs_arr = array_merge($tabs_arr,$tabs_pos);
			}
		}

		$tabs = '';
		foreach ($tabs_arr as $tab) {
			// set addl class to 'active' when tab is selected ($selected_tab)
			$cls = '';
			$clsA = '';
			$aux = '';
			$flag = '';
			$privilege = false;
			//If User Roles has at least 1 from the privilege array
			if($tab['privilege']) {
				if (array_intersect($tab['privilege'],$USER_ROLES)) {$privilege = true;}
			} else {
				$privilege = true;
			}
			
			if (($tab['action']==$selected_tab OR array_search($selected_tab,$tab['aliases'])!==false) && !$mobile) { $cls = ' active'; }

			if ($tab['sub']) {
				$cls .= ' dropdown';
				$clsA = ' dropdown-toggle';
				$aux = ' data-toggle="dropdown" data-hover="dropdown" aria-expanded="false"';
				//$aux = ' data-toggle="" data-hover="dropdown" aria-expanded="false"';
				$flag = '<b class="caret"></b>';
			}

			$tabs .= '
            <li class="'.(!$mobile ? "hidden-xs hidden-sm" : "hidden-md hidden-lg") .$cls.'" style="'.(!$privilege ? 'display: none !important' : '').'">
				<a href="'.(($tab['title'] == 'Sales' && in_array("3",$USER_ROLES)) ? '#' : $tab['action']).'" class="mode-tab'.$clsA.'"'.$aux.'>'.$tab['image'].'<span> '.$tab['title'].'</span> '.$flag.'</a>
				'.$tab['sub'].'
			</li>
			';
		}

		return ($tabs);
	}
?>
