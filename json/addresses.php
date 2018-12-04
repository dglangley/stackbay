<?php
	header("Content-Type: application/json", true);
	include_once '../inc/dbconnect.php';
	include_once '../inc/order_type.php';
	include_once '../inc/jsonDie.php';
	include_once '../inc/format_address.php';
	include_once '../inc/getContact.php';
	include_once '../inc/getOrder.php';

	$companyid = 0;
	if (isset($_REQUEST['companyid'])) { $companyid = $_REQUEST['companyid']; }
	$q = '';
	if (isset($_REQUEST['q'])) { $q = trim($_REQUEST['q']); }
	$order_type = 'Sale';
	if (isset($_REQUEST['order_type'])) { $order_type = $_REQUEST['order_type']; }
	$fieldid = 'bill_to_id';
	if (isset($_REQUEST['fieldid'])) { $fieldid = $_REQUEST['fieldid']; }
	if ($order_type=='service_quote') { $order_type = 'Service'; }
	else if ($order_type=='purchase_request') { $order_type = 'Purchase'; }
	if ($order_type=='Purchase' AND $fieldid=='bill_to_id') { $fieldid = 'remit_to_id'; }
	if ($fieldid<>'ship_to_id' AND $fieldid<>'bill_to_id' AND $fieldid<>'remit_to_id') { $fieldid = ''; }

	$addresses = array();

	if (! $companyid AND ! $q) { return ($addresses); }

	$T = order_type($order_type);

	$ORDER = getOrder(0,$order_type);
	if (! array_key_exists($fieldid,$ORDER)) {
		$fieldid = $T['addressid'];
	}

	$fq = preg_replace('/[^[:alnum:]]+/','.*',$q);
	$ids = array();
	$strs = array();
	if ($fieldid) {
		$query = "SELECT *, a.id addyid FROM ".$T['orders']." o, addresses a WHERE ";
		if ($companyid) { $query .= "o.companyid = '".res($companyid)."' "; }
		if ($companyid AND $q) { $query .= "AND "; }
		if ($q) {
			$query .= "(a.name RLIKE '".res($fq)."' OR a.street RLIKE '".res($fq)."' OR a.addr2 RLIKE '".res($fq)."' OR a.city RLIKE '".res($fq)."' OR a.notes RLIKE '".res($fq)."') ";
		}
		$query .= "AND o.".res($fieldid)." = a.id ";//AND o.".res($fieldid)." IS NOT NULL ";
		$query .= "GROUP BY a.id ORDER BY ".$T['datetime']." DESC; ";
		$result = qdb($query) OR jsonDie(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$ids[$r['addyid']] = true;
			$str = utf8_encode(format_address($r['addyid'],', ',true));
			if (isset($strs[$str])) { continue; }
			$strs[$str] = true;

			$addresses[] = array('id'=>$r['addyid'],'text'=>$str);
		}
	}

	$id_str = '';
	foreach ($ids as $id => $bool) {
		if ($id_str) { $id_str .= ','; }
		$id_str .= $id;
	}

	if ($companyid) {
		$query = "SELECT * FROM company_addresses ca, addresses a ";
		$query .= "WHERE ca.companyid = '".res($companyid)."' AND ca.addressid = a.id ";
		if ($q) {
			$query .= "AND (a.name RLIKE '".res($fq)."' OR a.street RLIKE '".res($fq)."' OR a.addr2 RLIKE '".res($fq)."' OR a.city RLIKE '".res($fq)."' OR a.notes RLIKE '".res($fq)."' ";
			$query .= "OR ca.nickname RLIKE '".res($fq)."' OR ca.alias RLIKE '".res($fq)."' OR ca.code RLIKE '".res($fq)."' OR ca.notes RLIKE '".res($fq)."') ";
		}
		if ($id_str) { $query .= "AND ca.addressid NOT IN (".$id_str.") "; }
		$query .= "; ";
		$result = qdb($query) OR jsonDie(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$ids[$r['addressid']] = true;

			$attn = '';
			if ($r['contactid']) { $attn = getContact($r['contactid']); }
			if ($r['nickname']) {
				$str = utf8_encode($r['nickname'].', '.format_address($r['addressid'],', ',false,$attn));
			} else {
				$str = utf8_encode(format_address($r['addressid'],', ',true,$attn));
			}

			if (isset($strs[$str])) { continue; }
			$strs[$str] = true;

			$addresses[] = array('id'=>$r['addressid'],'text'=>$str);
		}
	}

	$id_str = '';
	foreach ($ids as $id => $bool) {
		if ($id_str) { $id_str .= ','; }
		$id_str .= $id;
	}

	if ($q) {
		$query = "SELECT a.*, ca.nickname FROM addresses a ";
		$query .= "LEFT JOIN company_addresses ca ON a.id = ca.addressid ";
		$query .= "WHERE ";
		$query .= "(a.name RLIKE '".res($fq)."' OR a.street RLIKE '".res($fq)."' OR a.addr2 RLIKE '".res($fq)."' OR a.city RLIKE '".res($fq)."' OR a.notes RLIKE '".res($fq)."') ";
		if ($id_str) { $query .= "AND a.id NOT IN (".$id_str.") "; }
		$query .= "; ";
		$result = qdb($query) OR jsonDie(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$ids[$r['id']] = true;

			if ($r['nickname']) {
				$str = utf8_encode($r['nickname'].', '.format_address($r['id'],', ',false,$attn));
			} else {
				$str = utf8_encode(format_address($r['id'],', ',true,$attn));
			}

			$addresses[] = array('id'=>$r['id'],'text'=>$str);
		}
	}

	if ($q) {
		// always provide an Add option for new addresses
        $addresses[] = array(
            'id' => "Add $q",
            'text' => "Add $q..."
        );
	}

	echo json_encode($addresses);
	exit;
?>
