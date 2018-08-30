<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/pconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/call_remote.php';
?>

<form method="POST" action="">
<input type="text" name="user" value="">
<input type="text" name="password" value="">
<input type="submit" value="submit">
</form>
<?php
$query = "SELECT * FROM parts WHERE heci LIKE 'SLPQ0HR%'; ";
$result = qedb($query);
$r = qrow($result);
print_r($r);
die('hi');

	$USER_AGENT = 'Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko';
	$FOLLOW_LOCATION = true;

	$temp_dir = sys_get_temp_dir();
	// if last character of temp dir is not a slash, add it so we can append file after that
	if (substr($temp_dir,strlen($temp_dir)-1)<>'/') { $temp_dir .= '/'; }
	$cookiefile = $temp_dir.'srm-session.txt';
	$cookiejar = $cookiefile;

	$base_url = 'http://www.verizon.com/suppliers/';
$base_url = 'https://www.verizon.com/suppliers/suppliersservlet?action=Login&actiontype=13';
$base_url = 'https://www22.verizon.com/suppliers/public/login.jsp?TYPE=33554433&REALMOID=06-3e2dba6c-2931-0020-0000-538400005384&GUID=&SMAUTHREASON=0&METHOD=GET&SMAGENTNAME=$SM$iDOiWi0wRuyG988wp2z8ghVeBSCBXdJH%2briyacpgHxmR7sOAL7yRr%2b6ZHtYMkGW8&TARGET=$SM$HTTP%3a%2f%2fwww%2everizon%2ecom%2fsuppliers%2fsuppliersservlet%3faction%3dLogin%26actiontype%3d13';
	$srm_ch = curl_init($base_url);
	$res = call_remote($base_url,'',$cookiefile,$cookiejar,'GET',$srm_ch);
die($res);

	$newDom = new domDocument;
	$newDom->loadHTML($res);
	$inputs = $newDom->getElementsByTagName('input');

	//Loop through the form inputs and make them parameters for the post call
	$params = array();
	foreach ($inputs as $input) {
		$params[$input->getAttribute('name')] = $input->getAttribute('value');
	}

	$form = $newDom->getElementsByTagName('form')[0];
	$form_url = $form->getAttribute('action');
	$post_method = $form->getAttribute('method');

	//Make the post using the cookies file
	$res = call_remote($form_url,$params,$cookiefile,$cookiejar,$post_method,$srm_ch);

	$res = str_replace('<head>','<head> <LINK REL="stylesheet" REV="stylesheet" TYPE="text/css" HREF="/suppliers/css/site_mac_ie.css">',$res);
	$res = str_replace('"/suppliers/','"https://www.verizon.com/suppliers/',$res);

	$newDom = new domDocument;
	$newDom->loadHTML($res);
	$inputs = $newDom->getElementsByTagName('input');

	//Loop through the form inputs and make them parameters for the post call
	$params = array();
	foreach ($inputs as $input) {
		$params[$input->getAttribute('name')] = $input->getAttribute('value');
	}
	$params['UserId'] = 'ventel69';
	$params['Password'] = 'Ventel69!!!';

	$form = $newDom->getElementsByTagName('form')[0];
	$form_url = $form->getAttribute('action');
	$post_method = $form->getAttribute('method');

	//Make the post using the cookies file
	$res = call_remote($form_url,$params,$cookiefile,$cookiejar,$post_method,$srm_ch);
echo $res;exit;

	$html = $temp_dir."srm-res.html";
    $handle = fopen($html, "w");
    // add contents from file
    fwrite($handle, $res);
    fclose($handle);
?>
