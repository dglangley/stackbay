<?php
    include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

    include_once $_SERVER["ROOT_DIR"].'/inc/send_gmail.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/getSubEmail.php';

    include_once $_SERVER["ROOT_DIR"] . '/inc/keywords.php';

    include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/getUser.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/cmp.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/format_part.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/getSearch.php';

    setGoogleAccessToken(5); //5 is amea’s userid, this initializes her gmail session
    
    function rfq_email() {
        $data = array();

        // Subtract 1 day from now
        $previousDay = date('Y-m-d H:i:s',strtotime("-1 days"));

        // for testing
        // $previousDay = '2017-08-10 12:49:58';

        // Previous day but set at midnight
        $previousDayMid = date('Y-m-d 00:00:00',strtotime("-1 days"));

        // for testing
        // $previousDayMid = '2017-08-10 00:00:00';

        $query = "SELECT rf.*, a.*, sm.companyid, sm.contactid, sm.datetime, sm.source, sm.searchlistid, sm.id ";
		$query .= "FROM rfqs rf, availability a, search_meta sm ";
        // Only grab from a certain date period
        // In this case for RFQs its 24 hours before this current time and within that 24 hours
        $query .= "WHERE rf.datetime >= ".fres($previousDay)." ";
        // Join RFQs with availablility based on partid
        // Join company id to search_meta
        // Avail price IS NULL or no entered value
        // We need this to have a value as a reminder to the user
        $query .= "AND rf.partid = a.partid AND a.avail_price IS NULL AND sm.companyid = rf.companyid ";
        // Search Meta > midnight yesterday
        $query .= "AND sm.datetime >= ".fres($previousDayMid)." ";
        // Line up metaid with avail
        $query .= "AND sm.id = a.metaid ";
        // Grouping by search meta companyid and searchid on avail
        // $query .= "GROUP BY sm.companyid, a.searchid;";
        $query .= "GROUP BY rf.companyid, a.partid;";

        // echo $query;

        $result = qedb($query);

        while($r = qrow($result)) {
            // searchid
            $key = '';

            if($r['searchid']) {
                $key = getSearch($r['searchid']);
            } else {
                // Do the HECI 7 or primary part
                $r2 = reset(hecidb($r['partid'], 'id'));

                if($r2['heci7']) {
                    $key = $r2['heci7'];
                } else {
                    $key = format_part($r2['primary_part']);
                }
            }

            $data[$r['userid']][$key][$r['companyid']] = $r;
        }

        generate_RFQ_email($data);

        return $data;
    }

    function generate_RFQ_email($data) {
        global $CMP;

        $ord = 'partid';//default
		$dir = 'desc';

		foreach ($data as $userid => $res) {
			uasort($res,$CMP($ord,$dir));

			// $email_name = "";
			// $recipients = getSubEmail($email_name);

			$recipients = getUser($userid,'id','email');

			$email_subject = 'Unanswered RFQs - '.format_date($GLOBALS['now'], 'M j, Y');
			$email_body_html = '';

			// die($email_subject);

			$partid = 0;
			$new = false;

			$search_string = '';

			// print_r($res); die();

			$counter = 0;

			foreach($res as $search_string => $results) {
				$counter++;
				// print_r($r);

				$display = "<span>".$counter.". ".($search_string)."</span>";
				$email_body_html .= "".$display."<BR>";

				foreach($results as $companyid => $row) {
					$email_body_html .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . getCompany($companyid);
					$email_body_html .= '<BR>';
				}

				$email_body_html .= '<BR>';
			}

			$bcc = '';

//			echo $email_body_html;
			// die();
        
			// return 0;

			if ($GLOBALS['DEV_ENV']) {
				$send_success = send_gmail($email_body_html,$email_subject,$recipients,$bcc);

				if (! $send_success) {
					$ERR = $SEND_ERR;
				} else {
//					echo 'SENT';
				}
			}
		}
	}
?>
