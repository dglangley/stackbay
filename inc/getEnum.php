<?php
	function getEnum($table, $field, $search='') {
		$enums = array();

		if (! $table AND ! $field) { return ($enums); }

		$query = "SHOW COLUMNS FROM {$table} WHERE Field = '" . res($field) ."';";
		$result = qedb($query);
		if (qnum($result)==0) { return ($enums); }

		$matches = array();

		$r = mysqli_fetch_assoc($result);
		preg_match("/^enum\(\'(.*)\'\)$/", $r['Type'], $matches);

		$enums = explode("','", $matches[1]);

		if ($search) {
			$res = array();
			foreach ($enums as $e) {
				if (! stristr($e,$search)) { continue; }
				$res[] = $e;
			}
			$enums = $res;
		}

		return ($enums);
	}
?>
