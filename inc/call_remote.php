<?php
	$USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.11; rv:47.0) Gecko/20100101 Firefox/47.0';
	//IE11: Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko
	$FOLLOW_LOCATION = false;
	function call_remote($base,$params,&$cookiefile,&$cookiejarfile,$getpost='GET',$global_ch=false, $timeout = 0, $header = false) {
		global $USER_AGENT,$FOLLOW_LOCATION;

		if ($global_ch) { $ch = $global_ch; }
		else { $ch = curl_init($base); }

		curl_setopt($ch, CURLOPT_REFERER, $base);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);//mostly for T-E
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);//mostly for T-E
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, $header);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $FOLLOW_LOCATION);
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		if ($cookiefile AND $cookiejarfile) {
			curl_setopt($ch, CURLOPT_COOKIESESSION, true);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiefile);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiejarfile);
//			curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID='.$_COOKIE['PHPSESSID'].'; path=/');
		} else {
			curl_setopt($ch, CURLOPT_COOKIESESSION, false);
		}
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		if (isset($_SERVER['HTTP_USER_AGENT']) AND $_SERVER['HTTP_USER_AGENT']) {
			curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		} else {
//			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_2) AppleWebKit/601.3.9 (KHTML, like Gecko) Version/9.0.2 Safari/601.3.9');
			curl_setopt($ch, CURLOPT_USERAGENT, $USER_AGENT);
		}
		if ($getpost=='GET') {
			curl_setopt($ch, CURLOPT_POST, false);
			curl_setopt($ch, CURLOPT_HTTPGET, true);
			curl_setopt($ch, CURLOPT_URL, $base.$params);
		} else {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_HTTPGET, false);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
			curl_setopt($ch, CURLOPT_URL, $base);
		}

		$res = curl_exec($ch);

		// if we don't have a global connection we want left open, close the connection upon completion of this script
		if (! $global_ch) { curl_close($ch); }

		return ($res);
	}
?>
