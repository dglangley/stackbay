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
	
	function updateContactName($name,$companyid,$title='',$notes='',$ebayid='', $contactid) {
		$name = (string)$name;
		$title = (string)$title;
		$notes = (string)$notes;
		$ebayid = (string)$ebayid;
		$companyid = (int)$companyid;
		
		if ($title) { $title = "'".res($title)."'"; } else { $title = "NULL"; }
		if ($notes) { $notes = "'".res($notes)."'"; } else { $notes = "NULL"; }
		if ($ebayid) { $ebayid = "'".res($ebayid)."'"; } else { $ebayid = "NULL"; }
		
		$query = "UPDATE contacts SET name = '".res($name)."', title = $title, notes = $notes, ebayid = $ebayid, status = 'Active' WHERE id = $contactid;";

		$result = qdb($query) OR die(qe().' '.$query);
		
		//return $query;
	}
?>
