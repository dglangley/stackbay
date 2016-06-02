<?php
	function imap_decode($text,$encoding=1) {
		global $debug_num;
		if ($debug_num) {
			// create temp file name in temp directory
			$tempfile = sys_get_temp_dir().'/email'.$debug_num.'.txt';
			$handle = fopen($tempfile, "w");
			// add contents from file
			fwrite($handle, $text);
			fclose($handle);
			exit;
		}
/*
preg_match_all('/thank[\s\S]{0,10}/mi',$text,$matches);
foreach ($matches[0] as $match) {
	for ($i=0; $i<strlen($match); $i++) {
		echo $match[$i].':'.ord($match[$i]).'<BR>';
	}
	echo '<BR><BR>';
}
*/
//		$text = preg_replace('/([[:alnum:]])['.chr(13).chr(10).']([[:alnum:]])/','$1$2',$text);

// strip out equal signs that mark the end of a truncated line due to email line length limits
$text = str_replace('='.chr(13).chr(10),'',$text);

$text = str_replace(chr(13).chr(10), ' ', $text);
$text = preg_replace('/<p[^>]*>/i','',$text);
$text = str_ireplace('</p>','<br/>'.chr(10),$text);

$text = preg_replace('/<span[^>]*>/i','',$text);
$text = str_ireplace('</span>','',$text);

//echo $text.'<BR><BR><BR>';
//exit;

		//echo 'hi '.$encoding.'<BR>';
		switch ($encoding) {
		    # 7BIT
		    case 0:
				$text = preg_replace('/((&nbsp;)|[=])[\r]?[\n]/','',$text);
				$text = preg_replace('/[\/][\r]?[\n]/','/ ',$text);
				$text = imap_qprint($text);
		        return $text;
		    # 8BIT
		    case 1:
		        return quoted_printable_decode(imap_8bit($text));
		    # BINARY
		    case 2:
		        return imap_binary($text);
		    # BASE64
		    case 3:
		        return imap_base64($text);
		    # QUOTED-PRINTABLE
		    case 4:
				$text = preg_replace('/((&nbsp;)|[=])[\r]?[\n]/','',$text);
				$text = preg_replace('/[\/][\r]?[\n]/','/ ',$text);
//				return str_replace("=\r\n", '', quoted_printable_decode($text));
				return imap_qprint($text);
		    # OTHER
		    case 5:
		        return $text;
		    # UNKNOWN
		    default:
		        return $text;
		}
	}
?>
