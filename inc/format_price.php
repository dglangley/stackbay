<?php
	function format_price($price,$decimal4=true,$sep='',$dbformat=false) {
		//$decimal4 = show full price extension out to 4 decimal places
		//$sep = separator between "$" and dollar value
		//$dbformat = output in db format

		$price = trim($price);
		$price = str_replace(array('$',','),'',$price);

		$fprice = $price;//'';
		//if ($price>0 AND preg_match('/^[0-9]+([.][0-9]+)?$/',$price)) {
		if (preg_match('/^[-]?[0-9]+([.][0-9]+)?$/',$price)) {
			if ($dbformat) {
				// truncate to 2-decimals so long as it's not using 4-decimals
				if (round($price,2)<>round($price,4)) {
					$fprice = $sep.number_format($price,4,'.','');
				} else {
					$fprice = $sep.number_format($price,2,'.','');
				}
			} else if ($decimal4===false AND $price==round($price)) {//show truncated format without decimals, if none
				$fprice = '$'.$sep.number_format($price,0,'.','');
			} else {
				// truncate to 2-decimals so long as it's not using 4-decimals
				if (round($price,2)<>round($price,4)) {
					$fprice = '$'.$sep.number_format($price,4);
				} else {
					$fprice = '$'.$sep.number_format($price,2);
				}
			}
		}
		return ($fprice);
	}

	// Converts a price to the worded variant case is for printable checks with words required
	function convertNumber($number) {
	    list($integer, $fraction) = explode(".", (string) $number);

	    $output = "";

	    if ($integer[0] == "-") {
	        $output = "negative ";
	        $integer    = ltrim($integer, "-");
	    } else if ($integer[0] == "+") {
	        $output = "positive ";
	        $integer    = ltrim($integer, "+");
	    }

	    if ($integer[0] == "0") {
	        $output .= "zero";
	    } else {
	        $integer = str_pad($integer, 36, "0", STR_PAD_LEFT);
	        $group   = rtrim(chunk_split($integer, 3, " "), " ");
	        $groups  = explode(" ", $group);

	        $groups2 = array();
	        foreach ($groups as $g) {
	            $groups2[] = convertThreeDigit($g{0}, $g{1}, $g{2});
	        }

	        for ($z = 0; $z < count($groups2); $z++) {
	            if ($groups2[$z] != "") {
	                $output .= $groups2[$z] . convertGroup(11 - $z);
	                $output .= (($z < 11 AND !array_search('', array_slice($groups2, $z + 1, -1)) AND $groups2[11] != '' AND $groups[11]{0} == '0') ? " and " : " ");
	            }
	        }

	        $output = rtrim($output, ", ");
	    }

	    if ($fraction > 0) {
	        $output .= " and ";
	        for ($i = 0; $i < strlen($fraction); $i++) {
	            $output .= $fraction[$i];
	        }

	        if(strlen($fraction) == 1) {
	        	$output .= '0';
	        }
	        $output .= "/100*********************************";
	    } else {
	    	 $output .= " and 00/100*********************************";
	    }

	    return $output;
	}

	function convertGroup($index) {
	    switch ($index) {
	        case 11:
	            return " Decillion";
	        case 10:
	            return " Nonillion";
	        case 9:
	            return " Octillion";
	        case 8:
	            return " Septillion";
	        case 7:
	            return " Sextillion";
	        case 6:
	            return " !uintrillion";
	        case 5:
	            return " !uadrillion";
	        case 4:
	            return " Trillion";
	        case 3:
	            return " Billion";
	        case 2:
	            return " Million";
	        case 1:
	            return " Thousand";
	        case 0:
	            return "";
	    }
	}

	function convertThreeDigit($digit1, $digit2, $digit3) {
	    $buffer = "";

	    if ($digit1 == "0" && $digit2 == "0" && $digit3 == "0") {
	        return "";
	    }

	    if ($digit1 != "0") {
	        $buffer .= convertDigit($digit1) . " Hundred";
	        if ($digit2 != "0" || $digit3 != "0")
	        {
	            $buffer .= " and ";
	        }
	    }

	    if ($digit2 != "0") {
	        $buffer .= convertTwoDigit($digit2, $digit3);
	    } else if ($digit3 != "0") {
	        $buffer .= convertDigit($digit3);
	    }

	    return $buffer;
	}

	function convertTwoDigit($digit1, $digit2) {
	    if ($digit2 == "0") {
	        switch ($digit1) {
	            case "1":
	                return "Ten";
	            case "2":
	                return "Twenty";
	            case "3":
	                return "Thirty";
	            case "4":
	                return "Forty";
	            case "5":
	                return "Fifty";
	            case "6":
	                return "Sixty";
	            case "7":
	                return "Seventy";
	            case "8":
	                return "Eighty";
	            case "9":
	                return "Ninety";
	        }
	    } else if ($digit1 == "1") {
	        switch ($digit2) {
	            case "1":
	                return "Eleven";
	            case "2":
	                return "Twelve";
	            case "3":
	                return "Thirteen";
	            case "4":
	                return "Fourteen";
	            case "5":
	                return "Fifteen";
	            case "6":
	                return "Sixteen";
	            case "7":
	                return "Seventeen";
	            case "8":
	                return "Eighteen";
	            case "9":
	                return "Nineteen";
	        }
	    } else {
	        $temp = convertDigit($digit2);
	        switch ($digit1) {
	            case "2":
	                return "Twenty-$temp";
	            case "3":
	                return "Thirty-$temp";
	            case "4":
	                return "Forty-$temp";
	            case "5":
	                return "Fifty-$temp";
	            case "6":
	                return "Sixty-$temp";
	            case "7":
	                return "Seventy-$temp";
	            case "8":
	                return "Eighty-$temp";
	            case "9":
	                return "Ninety-$temp";
	        }
	    }
	}

	function convertDigit($digit) {
	    switch ($digit) {
	        case "0":
	            return "Zero";
	        case "1":
	            return "One";
	        case "2":
	            return "Two";
	        case "3":
	            return "Three";
	        case "4":
	            return "Four";
	        case "5":
	            return "Five";
	        case "6":
	            return "Six";
	        case "7":
	            return "Seven";
	        case "8":
	            return "Eight";
	        case "9":
	            return "Nine";
	    }
	}
