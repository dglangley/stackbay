<?php
	function getDisposition($id = '') {
		$dispositions = array();
		$disp_value;
		
		if($id == '') {
			$query = "SELECT * FROM dispositions;";
			$result = qdb($query) or die(qe());
			
			while ($row = $result->fetch_assoc()) {
				$dispositions[$row['id']] = $row['disposition'];
			}
		} else {
			$query = "SELECT * FROM dispositions WHERE id = ".prep($id).";";
			$result = qdb($query) or die(qe());
			
			if (mysqli_num_rows($result)>0) {
				$result = mysqli_fetch_assoc($result);
				$disp_value = $result['disposition'];
			}
			
			return $disp_value;
		}
		
		return $dispositions;
	}
?>