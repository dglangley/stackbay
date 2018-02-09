<?php
	if (! isset($EDIT)) { $EDIT = false; }
	function buildDescrCol($P,$id,$def_type='Part',$items, $override_new = false, $init = true) {
		global $EDIT;

		$types = array('Site','Part','N/A');

		$new = false;
		//if (substr($id,0,3)=='NEW') { $new = true; }
		if (! $id) { $new = true; }

		if (! $new OR ! $init) {
			$col = '
			<div class="pull-left" style="width:88%; margin-bottom:6px;">
				<input type="hidden" name="search_type['.$id.']" class="search-type" value="'.$def_type.'">
			';
		} else {
			// in Add Item mode, determine first if the user should have Site vs Part options...
			if (array_key_exists('partid',$items)) {
				$btn_options = '
					<li><a href="javascript:void(0);">Part</a></li>
				';
			} else if (array_key_exists('item_id',$items)) {
				$btn_options = '';
				foreach ($types as $type) {
					$btn_options .= '<li><a href="javascript:void(0);">'.$type.'</a></li>';
				}
			}

			$col = '
			<div class="pull-left dropdown" style="width:8%">
				<span class="dropdown-toggle dropdown-searchtype" data-toggle="dropdown">
					<button class="btn btn-default btn-sm btn-narrow btn-dropdown" type="button">'.$def_type.'</button>
					<input type="hidden" name="search_type['.$id.']" class="search-type" value="'.$def_type.'">
				</span>
				<!-- .dropdown-button takes the text value of the selected <li><a> tag, and sets it to the hidden form element within the above .dropdown-toggle and updates its text value -->
				<ul class="dropdown-menu dropdown-button dropdown-searchtype">
					'.$btn_options.'
				</ul>
			</div>
			<div class="pull-left" style="width:85%">
			';
		}

		if ($EDIT) {
			$editor = '';
			if ($def_type=='Site') {
				$cls = '';
				$fieldname = 'fieldid';
				$selname = 'address-selector';
				$dataurl = '/json/addresses.php';
				$dataplacer = '- Select an Address -';
				$editor = '<a href="javascript:void(0);" class="address-neighbor" data-name="fieldid_'.$id.'"><i class="fa fa-pencil"></i></a>';
			} else if ($def_type=='Part') {
				if (! $new OR $override_new) { $cls = 'select2'; } else { $cls = 'hidden'; }
				$fieldname = 'fieldid';
				$selname = 'part-selector';
				$dataurl = '/json/parts-dropdown.php';
				$dataplacer = '- Select a Part -';
			} else if ($def_type=='N/A') {
				$cls = 'hidden';
				$fieldname = '';
				$selname = '';
				$dataurl = '';
				$dataplacer = '';
			}

			if(! $init) {
				$cls = 'hidden';
				$editor = '';
			}

			$sel = '';
			if ($P['id']) {
				$sel = '<option value="'.$P['id'].'" selected>'.$P['name'].'</option>';
			}
			if ($fieldname) {
				$col .= '
					<select name="'.$fieldname.'['.$id.']" id="'.$fieldname.'_'.$id.'" size="1" class="form-control input-sm '.$selname.' '.$cls.'" data-url="'.$dataurl.'" data-placeholder="'.$dataplacer.'">
						'.$sel.'
					</select>
					'.$editor.'
				';
			}
		} else {
			if (array_key_exists('name',$P)) {
				$col .= $P['name'];
			}
		}

		$col .= '</div>';

		return ($col);
	}
?>
