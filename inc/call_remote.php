<?php
	function call_remote($base,$params,&$cookiefile,&$cookiejarfile,$getpost='GET',$global_ch=false) {
		if ($global_ch) { $ch = $global_ch; }
		else { $ch = curl_init($base); }

		curl_setopt($ch, CURLOPT_REFERER, $base);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);//mostly for T-E
		curl_setopt($ch, CURLOPT_TIMEOUT, 3);//mostly for T-E
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
//		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_COOKIESESSION, true);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiefile);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiejarfile);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		if (isset($_SERVER['HTTP_USER_AGENT']) AND $_SERVER['HTTP_USER_AGENT']) {
			curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		} else {
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_2) AppleWebKit/601.3.9 (KHTML, like Gecko) Version/9.0.2 Safari/601.3.9');
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

		if (! $global_ch) { curl_close($ch); }

		return ($res);
	}
?>
