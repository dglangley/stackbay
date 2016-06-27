<?php

//Input: Login and Password information
//Does: Logs into the Alcatel System
//Output: Boolean "Login Successful"
function aluLogin($user,$pass){
    $call = curl_init();
    
     $user = urlencode($user);
    $pass = urlencode($pass);
    
    $url = 'http://reuse.alcatel-lucent.com/ScoDotNet/Login.aspx';
    $url .= '?Login='.$user;
    $url .= '&PWord='.$pass;
    
    curl_setopt($call, CURLOPT_URL, $url);
    curl_setopt($call, CURLOPT_POST, true);
    curl_setopt($call, CURLOPT_TRANSFERTEXT, true);
    
    
    $results = curl_exec($call);
    echo ($results);
    
}


//Input: 
//Does:
//Output:

//Input: 
//Does:
//Output:


//==============================================================================
//============================= Main Function ==================================
//==============================================================================
$username = 'david@ven-tel.com';
$password = 'vAlu2008!';

aluLogin($username, $password)
?>