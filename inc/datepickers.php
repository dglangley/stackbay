<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/calcQuarters.php';

	function datepickers($startDate='',$endDate='') {
		global $today;

		$m = date("m");
		$Y = date("Y");

		$quarters = '';
		$Q = calcQuarters();
		foreach ($Q as $qnum => $q) {
			if ($q['Y']<>$Y) { $qnum .= ' '.$q['Y']; }

			$quarters .= '
				    			<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="'.$q['start'].'" data-end="'.$q['end'].'">Q'.$qnum.'</button>
			';
		}

		// define a group of quick date range options
		$months = '';
		for ($n=1; $n<=5; $n++) {
			$month = format_date($today,'M m/t/Y',array('m'=>-$n));
			$mfields = explode(' ',$month);
			$month_name = $mfields[0];
			$mcomps = explode('/',$mfields[1]);
			$MM = $mcomps[0];
			$DD = $mcomps[1];
			$YYYY = $mcomps[2];
			$months .= '
								<button class="btn btn-sm btn-default right small btn-report" type="button" '.
									'data-start="'.date($MM."/01/".$YYYY).'" data-end="'.date($MM."/".$DD."/".$YYYY).'">'.$month_name.'</button>
			';
		}

		$str = '
			<div class="form-group">
				<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY">
		            <input type="text" name="START_DATE" class="form-control input-sm" value="'.$startDate.'">
		            <span class="input-group-addon">
		                <span class="fa fa-calendar"></span>
		            </span>
		        </div>
			</div>
			<div class="form-group">
				<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY" data-maxdate="'.date("m/d/Y").'">
		            <input type="text" name="END_DATE" class="form-control input-sm" value="'.$endDate.'">
		            <span class="input-group-addon">
		                <span class="fa fa-calendar"></span>
		            </span>
			    </div>
			</div>
			<div class="form-group">
					<button class="btn btn-primary btn-sm" type="submit" ><i class="fa fa-filter" aria-hidden="true"></i></button>
					<div class="btn-group" id="dateRanges">
						<div id="btn-range-options">
							<button class="btn btn-default btn-sm">&gt;</button>
							<div class="animated fadeIn hidden" id="date-ranges">
						        <button class="btn btn-sm btn-default left large btn-report" type="button" data-start="'.$m.'/01/'.$Y.'" data-end="'.date("m/d/Y").'">MTD</button>
								'.$months.'
								'.$quarters.'
							</div><!-- animated fadeIn -->
						</div><!-- btn-range-options -->
					</div><!-- btn-group -->
			</div><!-- form-group -->
		';

		return ($str);
	}
?>
