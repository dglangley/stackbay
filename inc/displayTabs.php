<?php
	/***** this function outputs tabs for use in navbar, $pos is used as 'left' or 'right' of central search field *****/

	// list of all tabs for iterative display to be able to match selected tab ($selected_tab) with appropriate tab
	$TABS = array(
		'left' =>
			array(

				array('action'=>'/profile.php','image'=>'<i class="fa fa-book"></i>','title'=>'Companies','aliases'=>array(),'sub'=>'',),
				array('action'=>'/services.php','image'=>'<i class="fa fa-cogs"></i>','title'=>'Services','aliases'=>array('/job.php'),'sub'=>'',),
				array(
					'action'=>'/operations.php',
					'image'=>'<i class="fa fa-truck"></i>',
					'title'=>'Operations',
					'aliases'=>array(),
					'sub'=>'
                <ul class="dropdown-menu text-left animated-2x animated fadeIn">
					<li>
					  <div class="yamm-content">
						<div class="row">
							<div class="col-lg-6 col-md-6 col-sm-6 col-megamenu" style="height: 340px">
                                <div class="megamenu-block">
                                    <h4 class="megamenu-block-title"><i class="fa fa-wrench"></i> Repairs <span class="pull-right"><a href="#" class="mode-tab" title="Start New Repair Order"><i class="fa fa-plus"></i></a></span></h4>
                                    <ul id="repairs-orders-list">
                                    </ul>
                                </div>
                            </div>
							<div class="col-lg-6 col-md-6 col-sm-6 col-megamenu" style="height: 340px">
                                <div class="megamenu-block">
                                    <h4 class="megamenu-block-title"><i class="fa fa-shopping-cart"></i> Returns <span class="pull-right"><a href="/returns.php" class="mode-tab" title="Start New RMA"><i class="fa fa-plus"></i></a></span></h4>
                                    <ul id="returns-orders-list">
                                    </ul>
                                </div>
                            </div>
						</div>
					  </div>
					</li>
				</ul>
					',
				),
				array('action'=>'/inventory.php','image'=>'<i class="fa fa-qrcode"></i>','title'=>'Inventory','aliases'=>array(),'sub'=>'',),
			),
		'right' =>
			array(
				array(
					'action'=>'/',
					'image'=>'<i class="fa fa-cubes"></i>',
					'title'=>'Sales',
					'aliases'=>array('/order_form.php'),
					'sub' => '
                <ul class="dropdown-menu text-left animated-2x animated fadeIn">
					<li>
					  <div class="yamm-content">
						<div class="row">
							<div class="col-lg-6 col-md-6 col-sm-6 col-megamenu" style="height: 340px">
                                <div class="megamenu-block">
                                    <h4 class="megamenu-block-title"><a href="/accounts.php?orders_table=sales" class="mode-tab"><i class="fa fa-money"></i> Sales</a> <span class="pull-right"><a href="/order_form.php?ps=Sale" class="mode-tab" title="Start New SO"><i class="fa fa-plus"></i></a></span></h4>
                                    <ul id="sales-orders-list">
                                    </ul>
                                </div>
                            </div>
							<div class="col-lg-6 col-md-6 col-sm-6 col-megamenu" style="height: 340px">
                                <div class="megamenu-block">
                                    <h4 class="megamenu-block-title"><a href="/accounts.php?orders_table=purchases" class="mode-tab"><i class="fa fa-shopping-cart"></i> Purchases</a> <span class="pull-right"><a href="/order_form.php?ps=Purchase" class="mode-tab" title="Start New PO"><i class="fa fa-plus"></i></a></span></h4>
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
				</ul>
					',
				),
			),
	);

	function displayTabs($pos='',$selected_tab='') {
		global $TABS;

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
			if ($tab['action']==$selected_tab OR array_search($selected_tab,$tab['aliases'])!==false) { $cls = ' active'; }
			if ($tab['sub']) {
				$cls .= ' dropdown';
				$clsA = ' dropdown-toggle';
				$aux = ' data-toggle="dropdown" data-hover="dropdown" aria-expanded="false"';
				//$aux = ' data-toggle="" data-hover="dropdown" aria-expanded="false"';
				$flag = '<b class="caret"></b>';
			}

			$tabs .= '
            <li class="hidden-xs hidden-sm'.$cls.'">
				<a href="'.$tab['action'].'" class="mode-tab'.$clsA.'"'.$aux.'>'.$tab['image'].'<span> '.$tab['title'].'</span> '.$flag.'</a>
				'.$tab['sub'].'
			</li>
			';
		}

		return ($tabs);
	}
?>
