<?php
	/***** this function outputs tabs for use in navbar, $pos is used as 'left' or 'right' of central search field *****/

	// list of all tabs for iterative display to be able to match selected tab ($selected_tab) with appropriate tab
	$TABS = array(
		'left' =>
			array(

				array('action'=>'/profile.php','image'=>'<i class="fa fa-book"></i>','title'=>'Companies','aliases'=>array()),
				array('action'=>'/services.php','image'=>'<i class="fa fa-cogs"></i>','title'=>'Services','aliases'=>array('/job.php')),
				array('action'=>'#','image'=>'<i class="fa fa-wrench"></i>','title'=>'Repairs','aliases'=>array()),
				array('action'=>'/shipping_home.php','image'=>'<i class="fa fa-truck"></i>','title'=>'Shipping','aliases'=>array()),
				array('action'=>'/inventory.php','image'=>'<i class="fa fa-qrcode"></i>','title'=>'Inventory','aliases'=>array()),
			),
		'right' =>
			array(
				/*array('action'=>'/','image'=>'<i class="fa fa-cubes"></i>','title'=>'Sales','aliases'=>array()),*/
				array('action'=>'/accounts.php','image'=>'<i class="fa fa-building-o"></i>','title'=>'Accounts','aliases'=>array()),
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
			if ($tab['action']==$selected_tab OR array_search($selected_tab,$tab['aliases'])!==false) { $cls = ' active'; }

			$tabs .= '
            <li class="hidden-xs hidden-sm'.$cls.'">
				<a href="javascript:void(0)" data-action="'.$tab['action'].'" class="mode-tab">'.$tab['image'].'<span> '.$tab['title'].'</span></a>
			</li>
			';
		}

		return ($tabs);
	}
?>
