<?php
	/***** this function outputs tabs for use in navbar, $pos is used as 'left' or 'right' of central search field *****/

	// list of all tabs for iterative display to be able to match selected tab ($selected_tab) with appropriate tab
	$TABS = array(
		'left' =>
			array(

				array('action'=>'/profile.php','image'=>'<i class="fa fa-book"></i><span>','title'=>'Companies','alias'=>''),
				array('action'=>'/services.php','image'=>'<i class="fa fa-cogs"></i><span>','title'=>'Services','alias'=>'/job.php'),
				array('action'=>'#','image'=>'<i class="fa fa-wrench"></i><span>','title'=>'Repairs','alias'=>''),
				array('action'=>'/shipping_home.php','image'=>'<i class="fa fa-truck"></i><span>','title'=>'Shipping','alias'=>''),
				array('action'=>'/inventory.php','image'=>'<i class="fa fa-qrcode"></i><span>','title'=>'Inventory','alias'=>''),
			),
		'right' =>
			array(
				array('action'=>'/','image'=>'<i class="fa fa-cubes"></i><span>','title'=>'Sales','alias'=>''),
				array('action'=>'/accounts.php','image'=>'<i class="fa fa-building-o"></i><span>','title'=>'Accounts','alias'=>''),
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
			if ($tab['action']==$selected_tab OR $tab['alias']==$selected_tab) { $cls = ' active'; }

			$tabs .= '
            <li class="hidden-xs hidden-sm'.$cls.'">
				<a href="javascript:void(0)" data-action="'.$tab['action'].'" class="mode-tab">'.$tab['image'].'<span> '.$tab['title'].'</span></a>
			</li>
			';
		}

		return ($tabs);
	}
?>
