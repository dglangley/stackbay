<?php
	$META_EXISTS = false;
	function logSearchMeta($companyid,$searchlistid=false,$metadatetime='',$source='') {
		global $now,$META_EXISTS;

		if (! $companyid) { return false; }
		if (! $metadatetime) { $metadatetime = $now; }
		$metadate = substr($metadatetime,0,10);

		//global var to help us know when this function creates a new record or calls the old id
		$META_EXISTS = false;

		$userid = $GLOBALS['U']['id'];

		$metaid = 0;
		// have we already posted this page? replace instead of create
		if ($searchlistid) {
			$query = "SELECT id FROM search_meta WHERE companyid = '".$companyid."' ";
			if ($GLOBALS['U']['id']>0) { $query .= "AND userid = '".$userid."' "; }
			$query .= "AND datetime LIKE '".$metadate."%' AND searchlistid = '".$searchlistid."'; ";
			$result = qdb($query);
			if (mysqli_num_rows($result)==1) {
				$META_EXISTS = true;
				$r = mysqli_fetch_assoc($result);
				$metaid = $r['id'];
			}
		} else if ($source) {
			$query = "SELECT id FROM search_meta WHERE companyid = '".$companyid."' ";
			if ($GLOBALS['U']['id']>0) { $query .= "AND userid = '".$userid."' "; }
			$query .= "AND datetime LIKE '".$metadate."%' AND source = '".$source."'; ";
			$result = qdb($query);
			if (mysqli_num_rows($result)==1) {
				$META_EXISTS = true;
				$r = mysqli_fetch_assoc($result);
				$metaid = $r['id'];
			}
		}

		// save meta data
		$query = "REPLACE search_meta (companyid, datetime, source, searchlistid, userid";
		if ($metaid) { $query .= ", id"; }
		$query .= ") VALUES ('".$companyid."','".$metadatetime."',";
		if ($source) { $query .= "'".$source."',"; } else { $query .= "NULL,"; }
		if ($searchlistid) { $query .= "'".$searchlistid."',"; } else { $query .= "NULL,"; }
		if ($userid) { $query .= "'".$userid."'"; } else { $query .= "NULL"; }
		if ($metaid) { $query .= ",'".$metaid."'"; }
		$query .= "); ";
		$result = qdb($query) OR die(qe().' '.$query);
		if (! $metaid) { $metaid = qid(); }

		return ($metaid);
	}
?>
