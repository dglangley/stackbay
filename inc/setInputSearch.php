<?php
	function setInputSearch($def_type) {
		if ($def_type=='Part') { $search_cls = ''; }
		else { $search_cls = 'hidden'; }

		$input_search = '
			<div class="input-group input-shadow input-search '.$search_cls.'">
				<input type="text" name="" value="" id="item-search" class="form-control input-sm" placeholder="Search..." tabindex="1">
				<span class="input-group-btn">
					<button class="btn btn-primary btn-sm" type="button" id="btn-search"><i class="fa fa-search"></i></button>
				</span>
			</div>
		';

		return ($input_search);
	}
?>
