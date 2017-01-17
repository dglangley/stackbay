<?php
	// establish quarter end dates for all 4 quarters
	$QUARTER_ENDS = array(1=>'03/31',2=>'06/30',3=>'09/30',4=>'12/31');
	// calculate past quarterly dates based on present date
	function calcQuarters() {
		global $QUARTER_ENDS;

		$quarters = array();
		// get current date's quarter
		$q = ceil(date('m')/3);
		// populate full date of beginning of current date's quarter
		$qdate = str_pad($q,2,0,STR_PAD_LEFT).'/01/'.date('Y');
		$Y = date("Y");
		for ($i=0; $i<4; $i++) {
			// set the start of the iterating quarter
			$qstart = format_date($qdate,'m/d/Y',array('m'=>-($i*3)));
			// get the 4-digit year for appending to quarter end below
			$qy = substr($qstart,6,4);
			// what's the quarter number? (ie, 1/2/3/4)
			$qnum = ceil(substr($qstart,0,2)/3);
			$qend = $QUARTER_ENDS[$qnum].'/'.$qy;
			$quarters[$qnum] = array('start'=>$qstart,'end'=>$qend);
		}

		return ($quarters);
	}
?>
