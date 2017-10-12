<?php
	$rootdir = $_SERVER['ROOT_DIR'];
    include_once $rootdir.'/inc/format_date.php';
    include_once $rootdir.'/inc/format_price.php';
    include_once $rootdir.'/inc/form_handle.php';
    // include_once $rootdir.'/inc/filter_parameters.php';
	function sFilter($field, $value, $first=false){
		if ($value){
			$value = prep($value);
			$andwhere = ($first)?" WHERE ":" AND ";
			$string = " $andwhere $field = $value ";
		}
		else{
			$string = '';
		}
		return $string;
	}
	
	function dFilter($field, $start = '', $end = '', $lead = "AND"){
		if ($start and $end){
	   		$start = prep(format_date($start, 'Y-m-d'));
	   		$end = prep(format_date($end, 'Y-m-d'));
	   		$string = " $lead $field between CAST($start AS DATE) and CAST($end AS DATE) ";
		}
		else if($start){
			$start = prep(format_date($start, 'Y-m-d'));
			$string = " $lead CAST($field AS DATE) >= CAST($start AS DATE) ";
		}
		else if($end){
			$end = prep(format_date($end, 'Y-m-d'));
			$string = " $lead CAST($field AS DATE) <= CAST($end AS DATE) ";
		}
		else{
			$string = '';
		}
		return $string;
	}
		
	function pFilter($field, $min = '', $max = '', $first = false){
		$andwhere = ($first)?" WHERE ":" AND ";
		if ($min and $max){
	   		$min = prep(format_price($min,'','',true));
	   		$max = prep(format_price($max,'','',true));
	   		$string = " $andwhere $field between CAST($min AS FLOAT) and CAST($max AS FLOAT) ";
		}
		else if($min){
			$min = prep(format_price($min,'','',true));
			$string = " $andwhere CAST($field AS FLOAT) >= CAST($min AS FLOAT) ";
		}
		else if($max){
			$max = prep(format_price($max,'','',true));
			$string = " $andwhere CAST($field AS FLOAT) <= CAST($max AS FLOAT) ";
		}
		else{
			$string = '';
		}
		return $string;
	}
	
	function status($table, $state){
		//State expects: Active, Complete, Void
		$f = f_params($table);
		$o = o_params($table);
	}
	
	function status_out($table){
		$f = f_params($table);
		'<div class="btn-group medium">
	        <button data-toggle="tooltip" name="filter" type="submit" value="active" data-placement="bottom" title="'.$f['active'].'" data-filter="active_radio" data-original-title="" class="btn btn-default btn-sm left filter_status ">
	        	<i class="fa fa-sort-numeric-desc"></i>	
	        </button>

	        <button data-toggle="tooltip" name="filter" type="submit" value="complete" data-placement="bottom" title="'.$f['complete'].'" data-filter="complete_radio" data-original-title="Completed" class="btn btn-default btn-sm middle filter_status ">
	        	<i class="fa fa-history"></i>	
	        </button>
			<button data-toggle="tooltip" name="filter" type="submit" value="all" data-placement="bottom" title="All" data-filter="all_radio" data-original-title="All" class="btn btn-default btn-sm right filter_status active btn-info">
	        	All
	        </button>
	    </div>';
	}
	function display_out(){
		'<div class="btn-group">
	        <button class="glow left large btn-radio" type="submit" data-value="summary" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="summary">
	        <i class="fa fa-ticket"></i>	
	        </button>
			<input type="radio" name="report_type" value="summary" class="hidden">
	        <button class="glow right large btn-radio active" type="submit" data-value="detail" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="details">
	        	<i class="fa fa-list"></i>	
        	</button>
			<input type="radio" name="report_type" value="detail" class="hidden" checked="">
	    </div>';
	}
	function date_out(){
		'<td class="col-md-3">
			<div class="form-group">
				<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY">
		            <input type="text" name="START_DATE" class="form-control input-sm" value="04/01/2017">
		            <span class="input-group-addon">
		                <span class="fa fa-calendar"></span>
		            </span>
		        </div>
			</div>
			<div class="form-group">
				<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY" data-maxdate="07/17/2017">
		            <input type="text" name="END_DATE" class="form-control input-sm" value="07/17/2017">
		            <span class="input-group-addon">
		                <span class="fa fa-calendar"></span>
		            </span>
			    </div>
			</div>
			<div class="form-group">
					<button class="btn btn-primary btn-sm" type="submit"><i class="fa fa-filter" aria-hidden="true"></i></button>
					<div class="btn-group" id="dateRanges">
						<div id="btn-range-options">
							<button class="btn btn-default btn-sm">&gt;</button>
							<div class="animated fadeIn hidden" id="date-ranges">
						        <button class="btn btn-sm btn-default left large btn-report" type="button" data-start="07/01/2017" data-end="07/17/2017">MTD</button>

				    			<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="03/01/2017" data-end="03/31/2017">Q1</button>
		
				    			<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="12/01/2016" data-end="12/31/2016">Q4</button>
		
				    			<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="09/01/2016" data-end="09/30/2016">Q3</button>
		
				    			<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="06/01/2016" data-end="06/30/2016">Q2</button>
		
								<button class="btn btn-sm btn-default right small btn-report" type="button" data-start="06/01/2017" data-end="06/30/2017">Jun</button>
		
								<button class="btn btn-sm btn-default right small btn-report" type="button" data-start="05/01/2017" data-end="05/31/2017">May</button>
		
								<button class="btn btn-sm btn-default right small btn-report" type="button" data-start="04/01/2017" data-end="04/30/2017">Apr</button>
		
								<button class="btn btn-sm btn-default right small btn-report" type="button" data-start="03/01/2017" data-end="03/31/2017">Mar</button>
		
								<button class="btn btn-sm btn-default right small btn-report" type="button" data-start="02/01/2017" data-end="02/28/2017">Feb</button>
									</div><!-- animated fadeIn -->
						</div><!-- btn-range-options -->
					</div><!-- btn-group -->
			</div><!-- form-group -->
		</td>';
	}
	function order_out($value = ''){
		'<input type="text" name="order" class="form-control input-sm" value="'.$value.'" placeholder="Order #">';
	}
	function company_out($value = ''){
		$return = '
		<div class="pull-right form-group">
			<select name="companyid" id="companyid" class="company-selector">';
				if($value){$return .= '<option value="'.$company_filter.'" selected>'.(getCompany($company_filter)).'</option>'.chr(10);
				} else {$return .= '<option value="">- Select a Company -</option>'.chr(10);}
			'</select>
			<input class="btn btn-primary btn-sm" type="submit" value="Apply">
		</div>';
	}
?>
