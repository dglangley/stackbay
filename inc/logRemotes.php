<?php
	include_once $_SERVER["DOCUMENT_ROOT"].'/inc/format_date.php';
	$past_time = format_date($now,'Y-m-d H:i:s',array('i'=>-360));//60 mins
	$REMPOS = array();
	$SEARCHES = array();

	$query = "SELECT * FROM remotes; ";
	$result = qdb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		$REMPOS[$r['id']] = $r['remote'];
		$REMOTES[$r['remote']] = array('setting'=>'Y');
	}

	function logRemotes($search,$user_remotes='') {
		global $SEARCHES;

		$pos = $GLOBALS['REMPOS'];
		// set defaults as 0's across all remotes
		$def = array();
		$R = $GLOBALS['REMOTES'];
//		$user_remotes = '';//storing in search log below

		// set the most recent datetime for this search
		if (! isset($SEARCHES[$search])) { $SEARCHES[$search] = array(); }

		if (! $user_remotes) {
			foreach ($pos as $k => $s) {
				$def[$s] = false;
				if (isset($R[$pos[$k]]) AND $R[$pos[$k]]['setting']=='Y') { $user_remotes .= '1'; }
				else { $user_remotes .= '0'; }
			}
		}

		if (! $search) { return ($def); }

		$userid = 1;//$GLOBALS['U']['id'];

		// check for duplicate search within the recent time that scanned inventories,
		// and also check for user's same search within time frame for logging purposes
		$logid = 0;
		$query = "SELECT id, datetime, scan, userid FROM searches WHERE search = '".res($search)."' ";
//		$query .= "AND datetime >= '".$GLOBALS['past_time']."' ";
		$query .= "ORDER BY datetime DESC; ";
		$result = qdb($query);
		$expired_time = false;//once set to true, we know the ordered results are all expired
		$datedSearches = 0;
		while ($r = mysqli_fetch_assoc($result)) {
			// once the datetime is beyond the allowable past time, we are not setting booleans for preventing search
			if ($expired_time===false AND $r['datetime']<$GLOBALS['past_time']) { $expired_time = true; }

			// after we've found a search that was scanned AND we're past the expired time, break from loop
			if ($expired_time===true AND $datedSearches>1) { break; }

			$foundRemote = false;//if we find at least one remote scanned, this is tripped so we increment $datedSearches
			// for each remote as keyed by $pos, find its most recent scan time
			for ($i=0; $i<strlen($r['scan']); $i++) {
				if (! isset($SEARCHES[$search][$i]) OR ! $SEARCHES[$search][$i]) { $SEARCHES[$search][$i] = ''; }
				if (substr($r['scan'],$i,1)==1) {//scanned recently already
					if (! $SEARCHES[$search][$i]) { $SEARCHES[$search][$i] = $r['datetime']; }
					if ($expired_time===false) {
						$foundRemote = true;
						$def[$pos[$i]] = 0;//instructs not to scan remote
						$user_remotes[$i] = '0';
					}
				} else if ($def[$pos[$i]]===false) {//not scanned and $def isn't set
					if ($expired_time===false) {
						if ($user_remotes[$i]=='1') { $def[$pos[$i]] = 1; }// user has remote activated
						else { $def[$pos[$i]] = 0; }// not activated so don't turn on
					}
				}
			}
			if ($foundRemote) { $datedSearches++; }

			// overwrite previous search if same user and same scan settings
			if ($expired_time===false AND $r['userid']==$userid AND $r['scan']==$user_remotes AND ! $logid) { $logid = $r['id']; }
		}
		if ($datedSearches==0) {
			foreach ($pos as $k => $s) {
				if ($user_remotes[$k]==1) { $SEARCHES[$search][$k] = $GLOBALS['now']; }
				else { $SEARCHES[$search][$k] = ''; }
				$def[$s] = $user_remotes[$k];
			}
		}

		$query = "REPLACE searches (search, userid, datetime, scan";
		if ($logid) { $query .= ", id"; }
		$query .= ") VALUES ('".res($search)."','".$userid."','".$GLOBALS['now']."',";
		// log the user's scans that will be active for this search
		$query  .= "'".res($user_remotes)."'";
		if ($logid) { $query .= ",'$logid'"; }
		$query .= "); ";
		$result = qdb($query);

		return ($def);
	}
?>
