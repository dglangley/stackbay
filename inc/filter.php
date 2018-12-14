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
