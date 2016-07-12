<?php
/*
	if (! isset($root_dir)) {
		$root_dir = '';
		if (isset($_SERVER["HOME"]) AND $_SERVER["HOME"]=='/Users/davidglangley') { $root_dir = '/Users/Shared/WebServer/Sites/lunacera.com'; }
		else if (isset($_SERVER["DOCUMENT_ROOT"]) AND $_SERVER["DOCUMENT_ROOT"]) { $root_dir = preg_replace('/\/$/','',$_SERVER["DOCUMENT_ROOT"]); }
		else { $root_dir = '/var/www/html'; }
	}
*/
//2/24/16
	include_once $root_dir.'/inc/indexer.php';

	function setPart($P) {
		$part = '';
		if (isset($P['part'])) { $part = strtoupper(trim($P['part'])); }
		if (! $part) { return false; }

		$heci = '';
		if (isset($P['heci'])) { $heci = strtoupper(trim($P['heci'])); }

		$manf = '';
		if (isset($P['manf'])) { $manf = strtoupper(trim($P['manf'])); }
		$manfid = 0;
		if (isset($P['manfid']) AND is_numeric($P['manfid']) AND $P['manfid']>0) { $manfid = $P['manfid']; }
		else if ($manf) { $manfid = goManf($manf); }

		$sys = '';
		if (isset($P['sys'])) { $sys = strtoupper(trim($P['sys'])); }
		$sysid = 0;
		if (isset($P['sysid']) AND is_numeric($P['sysid']) AND $P['sysid']>0) { $sysid = $P['sysid']; }
		else if ($sys) { $sysid = goSys($sys,$manfid); }

		$descr = '';
		if (isset($P['descr'])) { $descr = strtoupper(trim($P['descr'])); }

		$partid = 0;
		$query = "SELECT id, heci, description FROM parts WHERE ";
		if ($heci AND ! is_numeric($heci) AND strlen($heci)==10) {
			$query .= "(heci = '".res($heci)."' ";
			// if there's a manfid, we can use a part/manfid/null-heci combo to identify the same part
			if ($manfid) { $query .= "OR (manfid = '".res($manfid)."' AND part = '".res($part)."' AND heci IS NULL) "; }
			$query .= ") ";
		} else {
			$query .= "part = '".res($part)."' ";
			if ($heci) { $query .= "AND heci LIKE '".res($heci)."%' "; }
			if ($manfid) { $query .= "AND manfid = '".res($manfid)."' "; }
//			if ($sysid) { $query .= "AND systemid = '".res($sysid)."' "; } else { $query .= "AND systemid IS NULL "; }
			if (! $manfid AND ! $sysid AND ! $heci AND $descr) {
//				if ($descr) { $query .= "AND description = '".res($descr)."' "; } else { $query .= "AND description IS NULL "; }
			}
		}
		if ($sysid OR $descr) {
			$query .= "ORDER BY ";
			if ($sysid) { $query .= "IF(systemid = '".res($sysid)."',0,1) "; }
			if ($sysid AND $descr) { $query .= ", "; }
			if ($descr) { $query .= "IF(description = '".res($descr)."',0,1) "; }
		}
		$query .= "; ";
		if ($GLOBALS['SUPER_ADMIN'] AND $GLOBALS['test']) { echo $query.'<BR>'; }
		$result = qdb($query);
		if (mysqli_num_rows($result)>0) {
			$r2 = mysqli_fetch_assoc($result);
			// if the db result has no heci but this search does ($heci), use the data
			// passed in (descr, manfid, sysid) instead of what's already stored
			// because it's more likely to be accurate
			if ($heci AND ! $r2['heci']) {
				$partid = $r2['id'];
				if (! $descr) {
					$descr = $r2['description'];
				} else if ($descr<>$r2['description']) {
					$descrWords = explode(' ',$descr);
					$newDescr = trim($r2['description']);
					foreach ($descrWords as $word) {
						if (! stristr($newDescr,$word)) { $newDescr .= ' '.$word; }
					}
					$descr = $newDescr;
				}
				if (! $sysid) { $sysid = $r2['systemid']; }
				if (! $manfid) { $manfid = $r2['manfid']; }
			} else {
//2/24/16
				indexer($r2['id'],'id');
				return ($r2['id']);
			}
		}

		if (! $manfid) { $manfid = 146; }//'Unknown'
		$query = "REPLACE parts (part, rel, heci, manfid, systemid, description";
		if ($partid) { $query .= ", id"; }
		$query .= ") VALUES (";
		if ($part) { $query .= "'".res($part)."',"; } else { $query .= "NULL,"; }
		$query .= "NULL,";
		if ($heci) { $query .= "'".res($heci)."',"; } else { $query .= "NULL,"; }
		$query .= "'".res($manfid)."',";
		if ($sysid) { $query .= "'".res($sysid)."',"; } else { $query .= "NULL,"; }
		if ($descr) { $query .= "'".res($descr)."'"; } else { $query .= "NULL"; }
		if ($partid) { $query .= ",'".res($partid)."'"; }
		$query .= "); ";
		if ($GLOBALS['SUPER_ADMIN'] AND $GLOBALS['test']) { echo $query.'<BR>'; }
		else { $result = qdb($query); }
		if (! $partid) { $partid = qid(); }

		if ($GLOBALS['SUPER_ADMIN'] AND $GLOBALS['test']) { return ($partid); }

//2/24/16
		indexer($partid,'id');

		return ($partid);
	}

        $MANFS = array();
        function goManf($manf) {
                global $MANFS;

                $manf = strtoupper(trim($manf));
                if (! $manf OR $manf=='CALL' OR $manf=='NOT LISTED') { return false; }//Unknown

				$manf = preg_replace('/[,]?[[:space:]]?(inc|ab|bv|ltd|us|ag|limited|l[.]?l[.]?c|plc|technologies|corp|corporation|telecom|e\\.K|\\([A-Z]+\\))?[.]*[[:space:]]*$/i','',$manf);

                if (isset($MANFS[$manf])) { return ($MANFS[$manf]); }

                $query = "SELECT * FROM manfs WHERE name LIKE '".res($manf)."%'; ";
                $result = qdb($query);// OR die(qe());
                if (mysqli_num_rows($result)>0) {
                        $r = mysqli_fetch_assoc($result);
                        $MANFS[$manf] = $r["id"];
                        return ($r["id"]);
                }

                $query = "REPLACE manfs (name) VALUES ('".res($manf)."'); ";
                $result = qdb($query);// OR die(qe());
                $MANFS[$manf] = qid();
                return ($MANFS[$manf]);
        }

        $SYSTEMS = array();
        function goSys($sys,$manfid=0) {
                global $SYSTEMS;

                $sys = strtoupper(trim($sys));
                if (! $sys) { return false; }

                if (isset($SYSTEMS[$sys])) { return ($SYSTEMS[$sys]); }

                $query = "SELECT * FROM systems WHERE system = '".res($sys)."' ";
                if ($manfid>0) { $query .= "AND manfid = '".res($manfid)."' "; }
				$query .= "; ";
                $result = qdb($query);// OR die(qe());
                if (mysqli_num_rows($result)>0) {
                        $r = mysqli_fetch_assoc($result);
                        $SYSTEMS[$sys] = $r["id"];
                        return ($r["id"]);
                }

                $query = "REPLACE systems (system, manfid) VALUES ('".res($sys)."',";
                if ($manfid>0) { $query .= "'".res($manfid)."'"; } else { $query .= "NULL"; }
                $query .= "); ";
                $result = qdb($query);// OR die(qe());
                $SYSTEMS[$sys] = qid();
                return ($SYSTEMS[$sys]);
        }
?>
