<?php
	/***** this function outputs tabs for use in navbar, $pos is used as 'left' or 'right' of central search field *****/

	// I added new 'tab-submit' class for sub-links with 'mode-tab' class so users can now select a
	// sub-menu item which will not only affix the sub-link onto the master tab position of that menu,
	// but also submit the search string directly to the page
	$inventory_sub = '
                <ul class="dropdown-menu text-left">
                    <li><a href="/inventory.php"><i class="fa fa-folder-open"></i> Browse Inventory</a></li>
	';
	if (in_array("1",$USER_ROLES) OR in_array("3",$USER_ROLES) OR in_array("4",$USER_ROLES) OR in_array("5",$USER_ROLES)) {
		$inventory_sub .= '
                    <li><a href="/parts.php" class="mode-tab tab-submit"><i class="fa fa-database"></i> Add/Edit Parts DB</a></li>
		';
	}
	//if user is sales or management, they have a manage inventory link
	if (in_array("1",$USER_ROLES) OR in_array("5",$USER_ROLES) OR in_array("4",$USER_ROLES) OR in_array("7",$USER_ROLES)) {
		$inventory_sub .= '
                    <li><a href="/inventory_exporter.php" class="mode-tab tab-submit"><i class="fa fa-list-alt"></i> Exporter</a></li>
		';
	}
	$inventory_sub .= '
                    <li><a href="/tools.php" class="mode-tab tab-submit"><i class="fa fa-eye-slash"></i> Tools (DNI)</a></li>
				</ul>
	';

	// list of all tabs for iterative display to be able to match selected tab ($selected_tab) with appropriate tab
	$TABS = array(
		'left' =>
			array(
				/*array('action'=>'/services.php','image'=>'<i class="fa fa-cogs"></i>','title'=>'Services','aliases'=>array('/job.php'),'sub'=>'','privilege'=>array(1,4,5,7)),*/
				array(
					'action'=>'/services.php',
					'image'=>'<i class="fa fa-cogs"></i>',
					'title'=>'Services',
					'privilege'=>array(1,3,4,7,8),
					'aliases'=>array(),
					'sub'=>'
                <ul class="dropdown-menu text-left dropdown-mega"'.((! array_intersect($USER_ROLES,array(1,4,5))) ? ' style="min-width:200px"' : '').'>
					<li>
					  <div class="yamm-content">
						<div class="row">
							<div class="col-megamenu '.(array_intersect($USER_ROLES,array(1,4,5)) ? 'col-lg-6 col-md-6 col-sm-6' : 'col-lg-12 col-md-12 col-sm-12').'" style="height: 340px">
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
						'.(array_intersect($USER_ROLES,array(1,4,5)) ? '
							<div class="col-lg-6 col-md-6 col-sm-6 col-megamenu" style="height: 340px">
                                <div class="megamenu-block">
									<h4 class="minimal" style="margin-top:5px; margin-left:10px"><a href="/quotes.php">Quotes</a> - <a href="/sourcing_requests.php">Reqs</a></h4>
                                    <h4 class="megamenu-block-title">
									  <div class="form-group">
										<div class="input-group pull-left">
											<span class="input-group-btn">
												<a href="/quote.php" class="btn btn-default btn-xs bg-quotes" title="Start New Service Quote"><i class="fa fa-plus"></i></a>
											</span>
											<input type="text" class="form-control input-xs order-search" placeholder="Quotes..." data-type="SQ">
											<span class="input-group-btn">
												<button class="btn btn-primary btn-xs order-search-button" type="button"><i class="fa fa-search"></i></button>
											</span>
										</div>
									  </div>
									</h4>
                                    <ul id="service-quotes-list">
                                    </ul>
                                </div>
                            </div>
						':'').'
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
					'privilege'=>array(1,3,4,5,7),
					'aliases'=>array(),
					'sub'=>'
                <ul class="dropdown-menu text-left dropdown-mega">
					<li>
					  <div class="yamm-content">
						<div class="row">
							<div class="col-sm-3">
				  			  <a href="/operations.php" class="btn btn-sm btn-default" title="Operations Dashboard" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-lg fa-dashboard"></i></a>
							</div>
							<div class="col-sm-6 text-center">
							  <a href="/repairs.php" class="btn btn-sm" title="Repairs Dashboard" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-lg fa-wrench"></i></a>
							  <a href="/builds.php" class="btn btn-sm" title="Builds Dashboard" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-lg fa-industry"></i></a>
							  <a href="/returns.php" class="btn btn-sm" title="Returns Dashboard" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-lg fa-question-circle-o"></i></a>
							</div>
							<div class="col-sm-3">
							</div>
						</div>
						<div class="row">
							<div class="col-lg-6 col-md-6 col-sm-6 col-megamenu" style="height: 340px">
                                <div class="megamenu-block">
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
					'aliases'=>array(
						/* '<i class="fa fa-folder-open"></i> Browse Inventory'=>'/inventory.php', */
						'<i class="fa fa-list-alt"></i> Exporter'=>'/inventory_exporter.php',
						'<i class="fa fa-database"></i> Add/Edit Parts DB'=>'/parts.php',
						'<i class="fa fa-eye-slash"></i> Tools (DNI)'=>'/tools.php',
					),
					'sub' => $inventory_sub,
				),
			),
		'right' =>
			array(
				array(
					'action'=>'/',
					'image'=>'<i class="fa fa-cubes"></i>',
					'title'=>'Sales',
					'privilege'=>array(1,4,5),
					'aliases'=>array('/order.php'),
					'sub' => '
                <ul class="dropdown-menu dropdown-menu-left text-left dropdown-mega">
					<li>
					  <div class="yamm-content">
						<div class="row">
							<div class="col-lg-6 col-md-6 col-sm-6 col-megamenu" style="height: 340px">
                                <div class="megamenu-block">
									<div class="pull-right" style="margin-right:10px"><a href="/order.php?order_type=Outsourced" title="New Outside Order" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-lg fa-puzzle-piece"></i></a></div>
									<h4 class="minimal" style="margin-top:5px; margin-left:10px"><a href="/sales_order.php">Sales</a></h4>
                                    <h4 class="megamenu-block-title">'
                                    . ((in_array("1",$USER_ROLES) OR in_array("5",$USER_ROLES) OR in_array("4",$USER_ROLES) OR in_array("7",$USER_ROLES)) ?
									  '<div class="form-group">
										<div class="input-group pull-left">
											<span class="input-group-btn">
												<a href="/order.php?order_type=Sale" class="btn btn-default btn-xs bg-sales" title="Start New Sale"><i class="fa fa-plus"></i></a>
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
												<a href="/order.php?order_type=Purchase" class="btn btn-default btn-xs bg-purchases" title="Start New Purchase"><i class="fa fa-plus"></i></a>
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
					'image'=>'<i class="fa fa-bank"></i>',
					'title'=>'Accounts',
					'aliases'=>array(),
					'sub' => '
                <ul class="dropdown-menu dropdown-menu-left text-left dropdown-mega">
					<li>
					  <div class="yamm-content">
						<div class="row">
							<div class="col-sm-7">
							  <a href="/transactions.php" class="btn btn-sm btn-default" title="Transactions" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-lg fa-book"></i></a>
							  <a href="/credits.php" class="btn btn-sm text-danger" title="Credits" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-lg fa-inbox"></i></a>
							  <a href="/lumps.php" class="btn btn-sm" title="Invoice Lumps" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-lg fa-file"></i></a>

							  <div class="pull-right"><a href="/expenses.php" class="btn btn-sm text-info" title="Expenses" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-lg fa-credit-card-alt"></i></a></div>
							</div>
							<div class="col-sm-4 text-center">
							  <div class="pull-left"><a href="/receivables.php" class="btn btn-sm text-success" title="Receivables" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-lg fa-level-down"></i></a></div>
							  <i class="fa fa-lg fa-bank"></i>
							  <div class="pull-right"><a href="/payables.php" class="btn btn-sm text-brown" title="Payables" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-lg fa-level-up"></i></a></div>
							</div>
							<div class="col-sm-1 text-center"> </div>
						</div>
						<div class="row">
							<div class="col-lg-6 col-md-6 col-sm-6 col-megamenu" style="height: 340px">
                                <div class="megamenu-block">
                                    <h4 class="megamenu-block-title">
										<div class="form-group">
											<div class="input-group pull-left">
												<span class="input-group-btn">
													<a href="/invoices.php" class="btn btn-xs btn-default text-success" title="Invoices" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-file-text"></i></a>
												</span>
												<input type="text" class="form-control input-xs order-search" placeholder="Invoices..." data-type="INV">
												<span class="input-group-btn">
													<button class="btn btn-primary btn-xs order-search-button" type="button"><i class="fa fa-search"></i></button>
												</span>
											</div>
										</div>
									</h4>
									<ul id="invoices-list">
									</ul>
								</div>
							</div>
							<div class="col-lg-6 col-md-6 col-sm-6 col-megamenu" style="height: 340px">
                                <div class="megamenu-block">
                                    <h4 class="megamenu-block-title">
										<div class="form-group">
											<div class="input-group pull-left">
												<span class="input-group-btn">
													<a href="/bills.php" class="btn btn-xs btn-default text-brown" title="Bills" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-file-text-o"></i></a>
												</span>
												<input type="text" class="form-control input-xs order-search" placeholder="Bills..." data-type="Bill" disabled>
												<span class="input-group-btn">
													<button class="btn btn-primary btn-xs order-search-button" type="button" disabled><i class="fa fa-search"></i></button>
												</span>
											</div>
										</div>
									</h4>
									<ul id="bills-list">
									</ul>
								</div><!-- .megamenu-block -->
							</div><!-- .col-megamenu -->
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
				array('action'=>'/services.php','aliases'=>array(),'image'=>'<i class="fa fa-cogs"></i>','title'=>'Services','privilege'=>array(1,4,5,7,8)),
				array('action'=>'/operations.php','aliases'=>array(),'image'=>'<i class="fa fa-truck"></i>','title'=>'Operations','privilege'=>array(1,3,4,5,7)),
				array('action'=>'/inventory.php','aliases'=>array(),'image'=>'<i class="fa fa-qrcode"></i>','title'=>'Inventory',),
				array('action'=>'/tools.php','aliases'=>array(),'image'=>'<i class="fa fa-eye-slash"></i>','title'=>'Tools',),
				array(
					'action'=>'/',
					'image'=>'<i class="fa fa-cubes"></i>',
					'title'=>'Sales',
					'aliases'=>array(),
					'privilege'=>array(1,4,5),
					'sub'=>'',
					),
				array(
					'action'=>'/accounts.php',
					'image'=>'<i class="fa fa-bank"></i>',
					'title'=>'Accounts',
					'aliases'=>array(),
					'sub'=>'',
					'privilege'=>array(1,4,7),
				),
				array('action'=>'/expenses.php','aliases'=>array(),'image'=>'<i class="fa fa-credit-card"></i>','title'=>'My Expenses',),
				array('action'=>'#','aliases'=>array(),'image'=>'<i class="fa fa-cutlery"></i>','title'=>'Break Mode',),
			),
	);
	if ($U['hourly_rate']) { 
		if ($CLOCK) { $TABS['mobile'][] = array('action'=>'/clockout.php','aliases'=>array(),'image'=>'<i class="fa fa-close"></i>','title'=>'Clock Out'); }
		else { $TABS['mobile'][] = array('action'=>'/clockin.php','aliases'=>array(),'image'=>'<i class="fa fa-clock-o"></i>','title'=>'Clock In'); }
	} 
	$TABS['mobile'][] = array('action'=>'/signout.php','aliases'=>array(),'image'=>'<i class="fa fa-sign-out"></i>','title'=>'Logout',);

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

			$title = $tab['image'].'<span> '.$tab['title'].'</span>';
			$tab_search = array_search($selected_tab,$tab['aliases']);
			if (($tab['action']==$selected_tab OR $tab_search!==false) && !$mobile) {
				if ($tab_search) {
					$title = $tab_search;//$tab['aliases'][$tab_search];
				}
				$cls = ' active';
			}

			if ($tab['sub']) {
				$cls .= ' dropdown';
				$clsA = ' dropdown-toggle';
				$aux = ' data-toggle="dropdown"';// data-hover="dropdown" aria-expanded="false"';
				//$aux = ' data-toggle="" data-hover="dropdown" aria-expanded="false"';
				$flag = '<b class="caret"></b>';
			} else if ($mobile) {
				$clsA = ' tab-submit';
			}

				//<a href="javascript:void(0);" class="mode-tab'.$clsA.'"'.$aux.'>'.$tab['image'].'<span> '.$tab['title'].'</span> '.$flag.'</a>
			$tabs .= '
            <li class="'.(!$mobile ? "hidden-xs hidden-sm" : "hidden-md hidden-lg") .$cls.'" style="'.(!$privilege ? 'display: none !important' : '').'">
				<a href="'.(($tab['title'] == 'Sales' && in_array("3",$USER_ROLES)) ? '#' : $tab['action']).'" class="mode-tab'.$clsA.'"'.$aux.'>'.$title.' '.$flag.'</a>
				'.$tab['sub'].'
			</li>
			';
		}

		return ($tabs);
	}
?>
