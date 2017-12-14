<?php
	
	function getCategory($catid, $table = 'expense_categories') {
		$category = '';

		if($catid) {
			$query = "SELECT * FROM $table WHERE id = ".res($catid).";";
			$result = qdb($query) OR die(qe()."<BR>".$query);

			if(mysqli_num_rows($result)) {
				$r = mysqli_fetch_assoc($result);

				$category = $r['category'];
			}
		}

		return $category;
	}
