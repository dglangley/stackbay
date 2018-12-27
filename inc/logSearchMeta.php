<?php
	$META_EXISTS = false;
	function logSearchMeta($companyid,$searchlistid=false,$metadatetime='',$source='', $userid = '', $contactid = 0) {
		global $now,$META_EXISTS;

		if (! $companyid) { return false; }
		if (! $metadatetime) { $metadatetime = $now; }
		$metadate = substr($metadatetime,0,10);

		//global var to help us know when this function creates a new record or calls the old id
		$META_EXISTS = false;
		
		//Added for the sake of clean imports
		if(!$userid){
			$userid = $GLOBALS['U']['id'];
		}

		if (! $contactid OR ! is_numeric($contactid)) { $contactid = false; }

		$metaid = 0;
		// have we already posted this page? replace instead of create
		if ($searchlistid OR $source) {
			$query = "SELECT id FROM search_meta WHERE companyid = '".res($companyid)."' ";
			if ($userid>0) { $query .= "AND userid = '".res($userid)."' "; }
			if ($searchlistid) { $query .= "AND searchlistid = '".res($searchlistid)."' "; }
			else if ($source) { $query .= "AND source = '".res($source)."' "; }
			$query .= "AND datetime LIKE '".$metadate."%'; ";
			$result = qedb($query);
			if (qnum($result)==1) {
				$META_EXISTS = true;
				$r = qrow($result);
				$metaid = $r['id'];
			}
		}

		// save meta data
		$query = "REPLACE search_meta (companyid, contactid, datetime, source, searchlistid, userid";
		if ($metaid) { $query .= ", id"; }
		$query .= ") VALUES ('".res($companyid)."',".fres($contactid).",'".res($metadatetime)."',";
		if ($source) { $query .= "'".res($source)."',"; } else { $query .= "NULL,"; }
		if ($searchlistid) { $query .= "'".res($searchlistid)."',"; } else { $query .= "NULL,"; }
		if ($userid) { $query .= "'".res($userid)."'"; } else { $query .= "NULL"; }
		if ($metaid) { $query .= ",'".res($metaid)."'"; }
		$query .= "); ";
		$result = qedb($query);
		if (! $metaid) { $metaid = qid(); }

		if ($GLOBALS['DEBUG']==1 OR $GLOBALS['DEBUG']==3) { $metaid = 9999999; }

		return ($metaid);
	}
?>
