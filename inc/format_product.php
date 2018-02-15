<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';

	function format_product($partid,$include_aliases=false) {
		if (! $partid) { return (''); }

		$H = hecidb($partid,'id');
		$P = $H[$partid];

		$descr = $P['primary_part'];
		if ($P['heci']) { $descr .= ' '.$P['heci']; }

		$alias_str = '';
		if ($include_aliases) {
			foreach ($P['aliases'] as $alias) {
				if ($alias_str) { $alias_str .= ' '; }
				$alias_str .= $alias;
			}
			if ($alias_str) { $alias_str .= ' &nbsp; <small>'.$alias_str.'</small>'; }
		}
		$descr .= $alias_str;

		return ($descr);
	}
?>
