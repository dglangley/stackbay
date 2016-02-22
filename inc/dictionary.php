<?php
	$DICT = array();
	function dictionary($phrase) {
		$words = explode(' ',$phrase);

		$defined_str = '';
		foreach ($words as $word) {
			if ($defined_str) { $defined_str .= ' '; }

			if (isset($GLOBALS['DICT'][$word])) {
				$defined_str .= $GLOBALS['DICT'][$word]['link'];// = array('word'=>$word,'link'=>$word,'definition'=>$word);
			} else {
				$query = "SELECT * FROM dictionary WHERE word = '".res($word)."'; ";
				$result = qdb($query);
				$ln = $word;
				$def = $word;
				$id = false;
				if (mysqli_num_rows($result)>0) {
					$r = mysqli_fetch_assoc($result);
					$def = $r['definition'];
					$id = $r['id'];
//					$ln = '<a href="#def-'.$id.'" title="'.$def.'">'.$word.'</a>';
					$ln = '<abbr title="'.$def.'">'.$word.'</abbr>';
				}
				$GLOBALS['DICT'][$word] = array('word'=>$word,'link'=>$ln,'definition'=>$def,'id'=>$id);

				$defined_str .= $ln;
			}
		}

		return ($defined_str);
	}
?>
