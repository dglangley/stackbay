<?php
	$USER_CLASSES = array();
	function getUserClasses($userid=0,$param_csv='',$filter_mode='exclude') {
		global $USER_CLASSES;

		$classes = array();
		if (! $userid OR ! is_numeric($userid)) { return ($classes); }

		if (isset($USER_CLASSES[$userid])) { return ($USER_CLASSES[$userid]); }

		$query = "SELECT sc.* FROM service_classes sc, user_classes uc ";
		$query .= "WHERE uc.userid = '".res($userid)."' ";
		if ($param_csv) {
			$query .= "AND sc.class_name ".($filter_mode=='exclude' ? 'NOT' : '')." IN ('".res($param_csv)."') ";
		}
		$query .= "AND uc.classid = sc.id; ";
		$result = qedb($query);
		while ($r = qrow($result)) {
			$classes[$r['id']] = $r['class_name'];
			$classes[$r['class_name']] = $r['id'];
		}

		$USER_CLASSES[$userid] = $classes;

		return ($classes);
	}
?>
