<?php
    class bbSoap{
        // file writing
        var $chmod=0777;
        var $safelock = false;

        // un-encrypted stuff
        var $user;
        var $pass;
        var $soapkey;

        // encrypted keys    
        var $ukey;
        var $pkey;
        var $uid;

        // Search Settings
        var $search_type = 'partclei';   // "partkey","clei","mfg","description","partclei"
        var $sort_by = 'partsno';       // age,partsno,partkey,clei,mfg
        var $sort_order = 'ASC';         // ASC,DESC
        var $max_resultset = 9999;        // max number results per request
        var $offset = 0;                // "paging" (page 2 == max_resultset * (page - 1))
        var $omit_all=true;             // omit the server and meta data from the result
        var $result_type='arr';         // "arr","str","xml"
        
        // private data    
        var $soap_connection;
        var $site;

        // DEBUG    
        var $dbg_log    = true;// post debug messages to the error log
        var $dbg_screen = true;// post debug messages to the screen

        // Broker Exchange Network Search
        function bbSoap($user,$pass,$soapkey,$site,$ini_dir="./"){
            // save some stuff
            $this->user = $user;
            $this->pass = $pass;
            $this->soapkey = $soapkey;
            $this->site = $site;
            
            // build some stuff
            $this->ini = $ini_dir.$this->site."_".$this->user.".ini";
            // connect to site
            $this->soap_connection = new SoapClient('http://soap.'.$this->site.'.com/'.$this->site.'_search/search.wsdl');
            // find authentication from ini file or reauthenticate (key is good for 1 day by default!)
            $this->getAuth();
        }
        
        function soapSearch($searchstring,$tryagain=true){
            if($this->uid == 'Improper Credentials'){
                $this->getAuth();
                // try again to be sure.
                if($this->uid == 'Improper Credentials'){
                    return false;
                }
            }
            if(!empty($searchstring)){
				// this was the source of about 2 hours of frustration on 1/3/18 when $searchstring is interpreted as an INT (ie, 234130160000)
				// and stupid BB doesn't know what to do with an int and it produces an Internal Search Error, so we convert to STRING
                $search = (string)$searchstring;
            } else {
                $search = '188122-b22';
            }
            $opts = array(
                'uid'=>$this->uid,
                'search_type'=>$this->search_type,
                // 'clm' =>'partclei',
                'sort_by'=>$this->sort_by,
                'sort_order'=>$this->sort_order,
                'offset'=>$this->offset,
                'max_resultset'=>$this->max_resultset,
                'omit_all'=>$this->omit_all,
                'result_type'=>$this->result_type
            );
            $this->debug('opts: '.var_export($opts,true)."\n\n");

            $results = $this->soap_connection->Search($search, $opts);
            if(($results=='Malformed uid' || $results=='Checkout expired/unlinked') && $tryagain){
                $this->debug("Search Failed: Malformed uid\n\n");
                // force reauthentication
                if($this->getAuth(true)){
                    return $this->search($searchstring,false);// using $tryagain to prevent looping.
                } else {
                    $this->debug("Search Failed: ReAuthenticate Failed from Search\n\n");
                }
            }
            
            $this->debug('results: '.var_export($results,true)."\n\n");
            return $results;
        }
        
        function getAuth($reauthenticate = false){
            if(!$reauthenticate){
                if(file_exists($this->ini)){
                    if(is_readable($this->ini)){
                        $ini_array = parse_ini_file($this->ini);
                        if($this->user != $ini_array['user']){
                            // somehow using different data
                            $this->debug("Ini: mismatched ini file with user (".$this->ini.")");
                            // soap key changed?
                            $reauthenticate = true;
                            $new_user = true;
                        } else {
                            $this->ukey = $ini_array['ukey'];
                        }
                        if($this->pass != $ini_array['pass']){
                            // somehow using different data
                            $this->debug("Ini: mismatched ini file with pass (".$this->ini.")");
                            // soap key changed?
                            $reauthenticate = true;
                            $new_pass = true;
                        } else {
                            $this->pkey = $ini_array['pkey'];
                        }
                        if($this->soapkey != $ini_array['soapkey']){
                            // somehow using different data
                            $this->debug("Ini: mismatched ini file with soapkey (".$this->ini.")");
                            // soap key changed?
                            $reauthenticate = true;
                            $new_soapkey = true;
                            $this->pkey='';// soap key is used to make pkey
                        }
                        if(!$reauthenticate){
                            $this->uid = $ini_array['uid'];
                        }
                    } else {
                        $this->debug("Ini Load Error: NOT READABLE (".$this->ini.")");
                        error_log("Ini Load Error: NOT READABLE (".$this->ini.")");
                        $reauthenticate = true;
                        $new_soapkey = true;
                        $new_pass = true;
                        $new_user = true;
                    }
                } else {
                    $this->debug("Ini Load Error: DOES NOT EXIST (".$this->ini.")");
                    error_log("Ini Load Error: DOES NOT EXIST (".$this->ini.")");
                    $reauthenticate = true;
                    $new_soapkey = true;
                    $new_pass = true;
                    $new_user = true;
                }
            }
            // reauthenticate the user and save the uid
            if($reauthenticate || $this->uid == 'Improper Credentials' || $this->uid == ''){
                // redo the encryption if the data changed
                if(isset($new_user) || $this->ukey==''){
                    /* Open the cipher */
                    $td = mcrypt_module_open (MCRYPT_BLOWFISH, "", MCRYPT_MODE_ECB, "");

                    /* Create the IV and determine the keysize length, use MCRYPT_RAND
                     * on Windows instead */
                    $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_DEV_RANDOM);
                    $ks = mcrypt_enc_get_key_size($td);

                    /* Create key */
                    $key = 'yTtG1EtON5uFIeuS'; // public key, do not post to web.

                    /* Intialize encryption */
                    mcrypt_generic_init($td, $key, $iv);

                    /* Encrypt data */
                    $this->ukey = base64_encode(mcrypt_generic($td, $this->user));
                    $this->debug('User Key Generated: '.$this->ukey);
                }
                // redo the encryption if the data changed
                if(isset($new_pass) || isset($new_soapkey) || $this->pkey==''){
                    /* Open the cipher */
                    $td = mcrypt_module_open (MCRYPT_BLOWFISH, "", MCRYPT_MODE_ECB, "");

                    /* Create the IV and determine the keysize length, use MCRYPT_RAND
                     * on Windows instead */
                    $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_DEV_RANDOM);
                    $ks = mcrypt_enc_get_key_size($td);

                    /* Intialize encryption using the soapkey */
                    mcrypt_generic_init($td, $this->soapkey, $iv);

                    /* Encrypt data */
                    $this->pkey = base64_encode(mcrypt_generic($td, $this->pass));
                    $this->debug('Pass Key Generated: '.$this->pkey);
                }
                
                $opts = array();
				//added 3/20/18 because dead connections were hanging here forever when in this scenario
				if (! $this->soap_connection) { return; }

                $this->uid = $this->soap_connection->Authenticate($this->ukey, $this->pkey, $opts);
                if($this->uid == 'Improper Credentials'){
                    $this->debug("Improper Credentials: failed to authenticate");
                    error_log("Improper Credentials: failed to authenticate");
                    return false;
                }
                $this->debug('Auth Key (uid): '.$this->uid);
                
                // save the config
                $savethis = array(
                    'user'=>$this->user,
                    'pass'=>$this->pass,
                    'soapkey'=>$this->soapkey,
                    'ukey'=>$this->ukey,
                    'pkey'=>$this->pkey,
                    'uid'=>$this->uid,
                );
                $this->write_php_ini($savethis, $this->ini);
            }
            return true;
        }

        function debug($data){
            if($this->dbg_log){
                error_log($data);
            }
            if($this->dbg_screen){
               // echo "<pre>".$data."</pre><br/>";
            }
        }

        function write_php_ini($array, $file){
            $res = array();
            foreach($array as $key => $val){
                if(is_array($val)){
                    $res[] = "[$key]";
                    foreach($val as $skey => $sval) $res[] = "$skey = ".(is_numeric($sval) ? $sval : '"'.$sval.'"');
                }
                else $res[] = "$key = ".(is_numeric($val) ? $val : '"'.$val.'"');
            }
            $str = implode("\r\n", $res);
            $this->debug('Ini String: '.$str);
            if($this->safelock){
                $res = $this->safefilerewrite($file, $str);
            } else {
                $res = $this->filerewrite($file, $str);
            }
            return $res;
        }
        
        function safefilerewrite($fileName, $dataToSave){
            if ($fp = fopen($fileName, 'w')){
                $startTime = microtime();
                do{            
                    $canWrite = flock($fp, LOCK_EX);
                    // If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
                    if(!$canWrite) 
                        usleep(round(rand(0, 100)*1000));
                } while ((!$canWrite)and((microtime()-$startTime) < 1000));

                //file was locked so now we can store information
                if ($canWrite){            
                    $result = fwrite($fp, $dataToSave);
                    flock($fp, LOCK_UN);
                }
                fclose($fp);
                $res = chmod($fileName,$this->chmod);
                if(!$res){
                    $this->debug("CHMOD FAILED (".$fileName.")");
                    error_log("CHMOD FAILED (".$fileName.")");
                }
            } else {
                $this->debug("File Write Error: Failed to Open File (".$fileName.")");
                error_log("File Write Error: Failed to Open File (".$fileName.")");
                return false;
            }
            return $result;
        }
        
        // this does not care about file locking!!!
        function filerewrite($fileName, $dataToSave){
            if ($fp = fopen($fileName, 'w')){
                $startTime = microtime();
                $result = fwrite($fp, $dataToSave);
                fclose($fp);
                $res = chmod($fileName,$this->chmod);
                if(!$res){
                    $this->debug("CHMOD FAILED (".$fileName.")");
                    error_log("CHMOD FAILED (".$fileName.")");
                }
            } else {
                $this->debug("File Write Error: Failed to Open File (".$fileName.")");
                error_log("File Write Error: Failed to Open File (".$fileName.")");
                return false;
            }
            return $result;
        }
    }





    // echo 'SOURCE CODE:<br/>';
    // echo '<pre>';
    // echo htmlentities(file_get_contents('./search.php'));
    // echo '</pre>';
