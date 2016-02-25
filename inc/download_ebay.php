<?php
	if (! isset($root_dir)) {
		$root_dir = '';
		if (isset($_SERVER["HOME"]) AND $_SERVER["HOME"]=='/Users/davidglangley') { $root_dir = '/Users/Shared/WebServer/Sites/lunacera.com/db'; }
		else if (isset($_SERVER["DOCUMENT_ROOT"]) AND $_SERVER["DOCUMENT_ROOT"]) { $root_dir = preg_replace('/\/$/','',$_SERVER["DOCUMENT_ROOT"]).'/db'; }
		else { $root_dir = '/var/www/html/db'; }
	}
	include_once $root_dir.'/inc/mconnect.php';
	include_once $root_dir.'/inc/getCompany.php';
	include_once $root_dir.'/inc/getPartId.php';
	include_once $root_dir.'/inc/insertMarket.php';
	include_once $root_dir.'/inc/getManf.php';
	include_once $root_dir.'/inc/getSys.php';
	include_once $root_dir.'/inc/restricted.php';

	libxml_use_internal_errors(true); //prevent errors from displaying
	$companyid = getCompany('eBay seller','name','id');

	// minimum length of a keyword
	$minLength = 3;

	function download_ebay($search='') {
		$search = preg_replace('/[^[:alnum:]]*/','',trim($search));

		$results = array();
		$last_partid = 0;
		$keywords = array();
		$searches = array();
		$manfs = array();
		$systems = array();
		if ($search) {
			$query = "SELECT keyword, parts_index.partid FROM parts_index, keywords ";
			$query .= "WHERE keyword LIKE '".res($search)."%' ";
		} else {
			$query = "SELECT keyword, parts_index.partid FROM ignitor, parts_index, keywords ";
			$query .= "WHERE ignitor.partid = parts_index.partid ";
		}
		$query .= "AND parts_index.keywordid = keywords.id AND rank = 'primary' ";
		$query .= "ORDER BY length(keyword) ASC, keyword ASC ";
		$query .= "; ";
//		echo $query.'<BR>';
		$result = qdb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$search = $r['keyword'];
			if (strlen($search)<=$GLOBALS['minLength']) { continue; }

			$str = '';
			$search_exists = false;
			for ($s=strlen($search); $s>=5; $s--) {
				$str = substr($search,0,$s);
				if (! isset($searches[$str])) { continue; }

				$search_exists = true;
				break;
			}
			if ($search_exists OR isset($searches[$search])) { continue; }

			$searches[$search] = $r['partid'];
			$keywords[$r['partid']][] = $search;
		}

		krsort($keywords);
//		print "<pre>".print_r($keywords,true)."</pre>";
		while (list($partid,$groups) = each($keywords)) {
			$headers_set = array('100'=>true);
			$header = '';
			$current_str = '';//keeps track of character count for currently-concatenating string
			while (list($k,$search) = each($groups)) {
				$query2 = "SELECT manfid, systemid, heci FROM parts WHERE id = '".$partid."' ";
				$result2 = qdb($query2);
				$r2 = mysqli_fetch_assoc($result2);
				$heci = substr($r2['heci'],0,7);

/*
				$header = getManf($r2['manfid']);//.' '.getSys($r2['systemid']));
				$sys = getSys($r2['systemid']);
				if ($sys) {
					$header .= ','.$sys;
				}
				if ($header) { $header = ' ('.$header.')'; }
*/

				if (! isset($manfs[$r2['manfid']])) {
					$manfs[$r2['manfid']] = array();
					$query3 = "SELECT keyword FROM keywords, manfs_index WHERE manfid = '".$r2['manfid']."' AND keywords.id = manfs_index.keywordid ";
					$result3 = qdb($query3);
					while ($r3 = mysqli_fetch_assoc($result3)) {
						if (is_numeric($r3['keyword'])) { continue; }
	
						$manfs[$r2['manfid']][] = $r3['keyword'];
					}
				}
				while (list($mkey,$mkeyword) = each($manfs[$r2['manfid']])) {
					if (isset($headers_set[$mkeyword])) { continue; }
					$headers_set[$mkeyword] = true;
					if ($header) { $header .= ','; }
					$header .= $mkeyword;
				}
				reset($manfs[$r2['manfid']]);

				if (! isset($systems[$r2['systemid']])) {
					$systems[$r2['systemid']] = array();
					$query3 = "SELECT keyword FROM keywords, systems_index WHERE systemid = '".$r2['systemid']."' AND keywords.id = systems_index.keywordid ";
					$result3 = qdb($query3);
					while ($r3 = mysqli_fetch_assoc($result3)) {
						$systems[$r2['systemid']][] = $r3['keyword'];
					}
				}
				while (list($skey,$skeyword) = each($systems[$r2['systemid']])) {
					if (isset($headers_set[$skeyword])) { continue; }
					$headers_set[$skeyword] = true;
					if ($header) { $header .= ','; }
					$header .= $skeyword;
				}
				reset($systems[$r2['systemid']]);

				$this_line = '';
				if ($heci<>$search) {
//					if ($this_line) { $this_line .= ','; }

					if (preg_match('/[-.]+/',$search)) {
						//$this_line .= '"'.$search.'"';
						$this_line .= $search;
						$this_line .= ','.preg_replace('/[-.]+/','',$search);
					} else {
						$this_line .= $search;
					}
				} else {
					$this_line .= $search;
				}

				$this_line = $search;
				if (strlen($current_str.$this_line.$header)<70) {
					if ($current_str) { $current_str .= ','; }// else { $current_str .= '('; }

					$current_str .= $this_line;
				} else {
					$manfSysHeader = $header;
					if ($manfSysHeader) { $manfSysHeader = ' ('.$manfSysHeader.')'; }
					$ebay_results = callEbay('('.$current_str.')',$manfSysHeader);
					if (is_array($ebay_results)) {
						$results = array_merge($results,$ebay_results);
					}

					$current_str = $this_line;
				}
			}

			$manfSysHeader = $header;
			if ($manfSysHeader) { $manfSysHeader = ' ('.$manfSysHeader.')'; }
			if ($current_str) {
				$ebay_results = callEbay('('.$current_str.')',$manfSysHeader);
				if (is_array($ebay_results)) {
					$results = array_merge($results,$ebay_results);
				}
			}
		}

		return ($results);
	}

	function callEbay($current_str,$header) {
		$ebay_ln = 'http://www.ebay.com/dsc/i.html?_from=R40&_sacat=0&LH_TitleDesc=1&_nkw='.$current_str.$header.'&_rss=1';

//		echo $ebay_ln.'<BR>'.chr(10);
		$xml = simplexml_load_file($ebay_ln, 'SimpleXMLElement', LIBXML_NOCDATA);
//		print "<pre>".print_r($xml,true)."</pre>";

		if (! $xml->channel) { return; }
		$newDom = $xml->channel;
//		print "<pre>".print_r($newDom,true)."</pre>";
		if (! $newDom->item) { return; }
		if (count($newDom->item)==0) { return; }

//		print "<pre>".print_r($newDom->item,true)."</pre>";

		$wordWords = array();
		$arr = array();
		foreach ($newDom->item as $node) {
			$title_words = preg_split('/[[:space:]]+/',$node->title);
			while (list($k,$word) = each($title_words)) {
				$fword = preg_replace('/[^[:alnum:]]*/','',$word);
				if (isset($wordWords[$word])) { continue; }

				$query = "SELECT keywordid, keyword FROM keywords, parts_index ";
				$query .= "WHERE keyword = '".res($fword)."' AND keywords.id = keywordid AND rank = 'primary' ";
				$result = qdb($query) OR die(qe().' '.$query);
				if (mysqli_num_rows($result)==0) { continue; }

				$r = mysqli_fetch_assoc($result);
				$wordWords[$r['keyword']] = $r['keywordid'];
			}

			//$descrDom = $node->description.chr(10);
			$descrDom = new domDocument;
			$descrDom->loadHTML($node->description);
			$price = str_replace(',','',str_replace('$','',$descrDom->getElementsByTagName('td')->item(1)->getElementsByTagName('strong')->item(0)->nodeValue));
			$node = (array)$node;
//			$node['link'] = 'http://www.ebay.com/itm/LUCENT-WAVESTAR-WSRH1B-WMW8FW0BAB-NICE-/271222534914?pt=LH_DefaultDomain_0&hash=item3f261f7302&ssPageName=RSS:B:SHOP:US:101';
		    $item = array ( 
		        'title' => $node['title'],
		        'date' => $node['pubDate'],
		        'link' => $node['link'],
		        'price' => $price,
		    );

//			print "<pre>".print_r($item,true)."</pre>";

			preg_match('/(\\/[0-9]+\\?)/',$node['link'],$itemids);
			$itemid = substr($itemids[0],1,strlen($itemids[0])-2);
			//$itemid = preg_replace('/(\\/)([0-9]+)(\\?)/','$1',$node['link']);
			$itemDescrUrl = 'http://vi.vipr.ebaydesc.com/ws/eBayISAPI.dll?ViewItemDescV4&item='.$itemid;

			$ch = curl_init('http://www.ebay.com');
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_VERBOSE, false);
			curl_setopt($ch, CURLOPT_POST, false);
			curl_setopt($ch, CURLOPT_HTTPGET, true);
			curl_setopt($ch, CURLOPT_COOKIESESSION, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/535.6 (KHTML, like Gecko) Chrome/16.0.897.0 Safari/535.6');
			curl_setopt($ch, CURLOPT_REFERER, $ebay_ln);
			//curl_setopt($ch, CURLOPT_URL, $node['link']);
			curl_setopt($ch, CURLOPT_URL, $itemDescrUrl);
			$result = str_replace('</p>',' ',str_replace('<p>',' ',str_replace('&nbsp;',' ',str_replace('</li>',' ',str_replace('<li>',' ',curl_exec($ch))))));
			curl_close($ch);

//			$itempage = simplexml_load_file($node['link'], 'SimpleXMLElement', LIBXML_NOCDATA);
			$dom = new domDocument;
			$dom->loadHTML($result);
/*
			$iframes = $dom->getElementById('desc_div')->getElementsByTagName('iframe');
			for ($i=0; $i<$iframes->length; $i++) {
				echo 'i:'.$iframes->item($i)->getAttribute('src').'<BR>';
			}
*/
			//$iframe = str_replace(chr(10),' ',str_replace('&amp;','&',htmlentities($dom->getElementById('desc_div')->nodeValue)));
			$iframe = $dom->getElementById('ds_div')->nodeValue;
//			echo htmlentities($iframe);
//			print "<pre>".print_r($dom->getElementById('ds_div'),true)."</pre>";

			$all_words = explode(' ',$iframe);
			$words_array = array();
			while (list($k,$phrase) = each($all_words)) {
				$phrase = trim($phrase);
				if (strlen($phrase)<=$GLOBALS['minLength']) { continue; }

				$fphrase = preg_replace('/[^[:alnum:]]+/','',$phrase);
				if (strlen($fphrase)<=$GLOBALS['minLength']) { continue; }

				// if the first letter is lowercase, disregard it
				$phraselower = strtolower($fphrase);
				if (ucfirst($fphrase)!==$fphrase OR isset($GLOBALS['restricted'][$phraselower]) OR (is_numeric($fphrase) AND $fphrase<=20000)) { continue; }
				$words_array[$fphrase] = true;
				//$sub_words = preg_split('/[\\*\\/\\.\\,\\!\\:x]+/',$phrase);
				$sub_words = preg_split('/[x^[:alnum:]]+/',$phrase);
				while (list($k2,$word) = each($sub_words)) {
					if (strlen($word)<=$GLOBALS['minLength']) { continue; }

					$fword = preg_replace('/[^[:alnum:]]+/','',$word);
					if (strlen($fword)<=$GLOBALS['minLength']) { continue; }

					// if the first letter is lowercase, disregard it
					$wordlower = strtolower($fword);
					if (ucfirst($fword)!==$fword OR isset($GLOBALS['restricted'][$wordlower]) OR (is_numeric($fword) AND $fword<=20000)) { continue; }
					if (isset($words_array[$fword])) { continue; }
					$words_array[$fword] = true;
				}
			}
			reset($all_words);

//			print "<pre>".print_r($words_array,true)."</pre>";
			$results = array();
			$uniques = array();
			while (list($word,$bool) = each($words_array)) {
				$query = "SELECT keywordid, partid, part, heci, description, systemid, manfid FROM keywords, parts_index, parts ";
				$query .= "WHERE keyword = '".res($word)."' AND keywords.id = parts_index.keywordid AND parts_index.rank = 'primary' ";
				$query .= "AND parts.id = parts_index.partid GROUP BY partid ";
				$result = qdb($query);
				$num_results = mysqli_num_rows($result);
				if ($num_results==0 OR $num_results>=30) {
					// add to restricted so duplicate attempts aren't made on the same word
					$GLOBALS['restricted'][strtolower($word)] = true;
					continue;
				}

				while ($r = mysqli_fetch_assoc($result)) {
					$splitpart = explode(' ',$r['part']);

					if ($r['heci']) { $partkey = substr($r['heci'],0,7); }
					else { $partkey = $splitpart[0]; }
					$partkey .= '.'.$itemid;

					if (isset($uniques[$partkey])) { continue; }
					$uniques[$partkey] = true;

//					echo 'Identifying '.$word.' = '.$r['partid'].' to be added...<BR>'.chr(10);
					insertMarket($r['partid'],1,$GLOBALS['companyid'],$GLOBALS['today'],$itemid,$item['price']);

					$descr = $r['description'];
					if ($r['systemid']) { $descr = getSys($r['systemid']).' '.$descr; }
					if ($r['manfid']) { $descr = getManf($r['manfid']).' '.$descr; }
					$descr = trim($descr);
					$results[$r['partid']][] = array($r['partid'],'eBay seller',1,$r['part'],$r['heci'],$descr);
// just break now??
//break;
				}
			}
			reset($words_array);
		}

		return ($results);
	}

?>
