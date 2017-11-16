<?php
    function format_phone($number) {
        $number = trim($number);

        if (! $number) { return false; }

        // Replace all '-' with '.' so that the processes below are
        // the same for both punctuation marks, but don't go through
        // these girations if the format is already correct...
        // if the raw numeric string has 10-digits and a number in front (11 total), it's the country code prefix
        if (preg_match('/^[0-9]{11}$/',preg_replace('/[^[0-9]]+/','',$number))) {
            $number = preg_replace('/([0-9])[(]?([0-9]{3})([^[:alnum:]]{0,2})?([0-9]{3})([[:space:]|[-]|[.]]{0,2})?([0-9]{4})/','+$1 ($2) $4-$6',$number);
		} else if (strlen($number)>=10 AND preg_match('/^[(]?([0-9]{3})([^[:alnum:]]{0,2})?([0-9]{3})([[:space:]|[-]|[.]]{0,2})?([0-9]{4})$/',$number)) {
            $number = preg_replace('/^[(]?([0-9]{3})([^[:alnum:]]{0,2})?([0-9]{3})([[:space:]|[-]|[.]]{0,2})?([0-9]{4})$/','($1) $3-$5',$number);
        } else {
			$number = false;//'(909) 496-0151';
        }

        return ($number);
    }
?>
