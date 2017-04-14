<?php
	function setContact($name,$companyid=0,$title='',$notes='',$ebayid='',$aim='',$status='Active', $contactid = 0) {
		$name = (string)$name;
		$name = trim($name);
		$title = (string)$title;
		$title = trim($title);
		$notes = (string)$notes;
		$notes = trim($notes);
		$ebayid = (string)$ebayid;
		$aim = (string)$aim;
		$companyid = (int)$companyid;

		$query = "REPLACE contacts (name, companyid, title, notes, ebayid, aim, status";
		if ($contactid) { $query .= ", id"; }
		$query .= ") VALUES ('".res($name)."',";
		if ($companyid) { $query .= "'".res($companyid)."',"; } else { $query .= "NULL,"; }
		if ($title) { $query .= "'".res($title)."',"; } else { $query .= "NULL,"; }
		if ($notes) { $query .= "'".res($notes)."',"; } else { $query .= "NULL,"; }
		if ($ebayid) { $query .= "'".res($ebayid)."',"; } else { $query .= "NULL,"; }
		if ($aim) { $query .= "'".res($aim)."',"; } else { $query .= "NULL,"; }
		if ($status=='Inactive') { $query .= "'Inactive'"; } else { $query .= "'Active'"; }
		if ($contactid) { $query .= ",'".res($contactid)."'"; }
		$query .= "); ";
		$result = qdb($query) OR die(qe().' '.$query);
		if(!$contactid) {$contactid = qid();}

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
