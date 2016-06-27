<?php
	function setContact($name,$companyid=0,$title='',$notes='',$ebayid='') {
		$name = (string)$name;
		$title = (string)$title;
		$notes = (string)$notes;
		$ebayid = (string)$ebayid;
		$companyid = (int)$companyid;

		$query = "INSERT INTO contacts (name, title, notes, ebayid, status, companyid) ";
		$query .= "VALUES ('".res($name)."',";
		if ($title) { $query .= "'".res($title)."',"; } else { $query .= "NULL,"; }
		if ($notes) { $query .= "'".res($notes)."',"; } else { $query .= "NULL,"; }
		if ($ebayid) { $query .= "'".res($ebayid)."',"; } else { $query .= "NULL,"; }
		$query .= "'Active','".res($companyid)."'); ";
		$result = qdb($query) OR die(qe().' '.$query);
		$contactid = qid();

		return ($contactid);
	}
?>
