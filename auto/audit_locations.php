<?php
    include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';

    include_once $_SERVER["ROOT_DIR"].'/inc/send_gmail.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/getSubEmail.php';

    include_once $_SERVER["ROOT_DIR"].'/inc/getLocation.php';

    setGoogleAccessToken(5); //5 is amea’s userid, this initializes her gmail session
   
    function getLocations() {
		$locations = array();

		$query = "SELECT * FROM locations";
		$result = qedb($query);

		while($r = qrow($result)) {
			$locations[] = $r;
		}

		return $locations;
    }
    
    function sortbyLatest($csv_locations) {
        $locations = array();

        if($csv_locations) {
            // Check all the locations here to the audit and see which one hasn't been touched in the longest time
            $query = "SELECT locationid FROM location_audits WHERE locationid IN (".$csv_locations.") GROUP BY locationid ORDER BY datetime ASC;";
            $result = qedb($query);

            while($r = qrow($result)) {
                // This query gets the latest datetime... from other query we know for sure it is past expired
                $locations[] = $r['locationid'];
            }
        }

        return $locations;
    }

    function sortByPopularity($csv_locations, $temp, $datetime) {
        $locations = array();
        // This function combs through inventory history to count and see what locationid is used the most
        $query = "SELECT COUNT(value) as total, value as locationid FROM inventory_history WHERE field_changed = 'locationid' AND value IN (".$csv_locations.") AND date_changed > ".fres($datetime)." GROUP BY value ORDER BY COUNT(value) DESC;";
        $result = qedb($query);

        while($r = qrow($result)) {
            // This query gets the latest datetime... from other query we know for sure it is past expired
            $locations[] = $r['locationid'];
        }

        foreach($temp as $temp_location) {
            if (!in_array($temp_location, $locations)) {
                $locations[] = $temp_location; 
            }
        }

        return $locations;
    }
	
    function audit_locations() {
        $weeks = 52; // Set a default amount of weeks to comb through
        $query = "SELECT * FROM audit_settings LIMIT 1;";
        $result = qedb($query);

        if(qnum($result)) {
            $r = qrow($result);

            $duration = $r['duration'];
            $type = $r['type'];
        }

        $days = $duration * 7 * -1;

        // Convert to days
        if($type == 'Months') {
            $days = $duration * 30 * -1;
        } else if($type == 'Years') {
            $days = $duration * 365 * -1;
        }

        // Calculate how many days ago from now
        $past = format_date($GLOBALS['now'],'Y-m-d 00:00:00',array('d'=> $days));

        $locations = getLocations();
        $needs_audit = array();
        $temp_collection = array();

        // CSV locations contains all the locations that have an audit and have expired in respect to the duration
        $csv_locations = '';

        foreach($locations as $location) {
            $query = "SELECT * FROM location_audits WHERE locationid = ".res($location['id'])." ORDER BY datetime DESC LIMIT 1;";
            $result = qedb($query);

            if(qnum($result) == 0) {
                // No results
                $needs_audit[] = $location['id'];
            } else {
                $r = qrow($result);

                if(strtotime($r['datetime']) < strtotime($past)) {
                    // record exists of this location but it is past due
                    if ($csv_locations) { $csv_locations .= ','; }
                    $csv_locations .= $r['locationid'];

                    $temp_collection[] = $r['locationid'];
                }
            }
        }

        $locations_per_week = ceil(count($needs_audit) / $duration);

        $needs_audit_sort = array_merge($needs_audit, sortbyLatest($csv_locations));

        // print "<pre>" . print_r($needs_audit_sort, true) . "</pre>";
        

        // echo '<BR> NEED TO COMPLETE ' . ceil($locations_per_week) . ' PER WEEK';

        $email_name = "audit_locations";

        $recipients = getSubEmail($email_name);

        $email_subject = 'Weekly Location Audit Requirements';
        $email_body_html = 'Please audit the following location(s) by end of week.<BR>';

        $counter = 0;
        $col_spacing = 100 / $locations_per_week;

        foreach($needs_audit_sort as $locationid) {
            $counter++;

            // 4 evenly spaced columns with a color background link div block
            $email_body_html .= "
                <div style='width: 100%;'>
                    <a href='http://".$_SERVER['HTTP_HOST']."/audit.php?locationid=".$locationid."'>
                        <button style='float: left; background-color: #ed9c28; color: #FFF; width: ".$col_spacing."%;'>
                            <h4>".getLocation($locationid)."</h4>
                        </button>
                    </a>
                </div>
            ";

            if($counter >= $locations_per_week) {
                break;
            }
        }

        $bcc = '';

        if (! $GLOBALS['DEV_ENV']) {
            $send_success = send_gmail($email_body_html,$email_subject,$recipients,$bcc);

            if (! $send_success) {
                $ERR = $SEND_ERR;
                die($ERR);
            }
        }
    }

	audit_locations();
?>
