<?php
    // include dompdf autoloader
    include_once $_SERVER['ROOT_DIR'].'/dompdf/autoload.inc.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/renderOrder.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/order_type.php';
    include_once $_SERVER['ROOT_DIR'].'/inc/packing-slip.php';
    include_once $_SERVER['ROOT_DIR'].'/inc/renderPackage.php';
    $filename = trim(preg_replace('/([\/]docs[\/])([^.]+[.]pdf)/i','$2',$_SERVER["REQUEST_URI"]));
	$file_parts = preg_replace('/^(INV|Bill|PS|PSP|OS|SO|PO|CM|RMA|LUMP|SQ|EQ|CQ|FSQ|Payment)([0-9_]+).*/','$1-$2',$filename);

	$file_split = explode('-',$file_parts);
	$order_type = $file_split[0];
	if ($order_type=='PO') { $order_type = 'Purchase'; }
	else if ($order_type=='SO') { $order_type = 'Sale'; }
	else if ($order_type=='INV') { $order_type = 'Invoice'; }
	else if ($order_type=='OS') { $order_type = 'Outsourced'; }
	else if ($order_type=='CM') { $order_type = 'Credit'; }
	else if ($order_type=='LUMP') { $order_type = 'Lump'; }
/*
	else if ($order_type=='RMA') { $order_type = 'RMA'; }
	else if ($order_type=='PS') { $order_type = 'PS'; }
	else if ($order_type=='Bill') { $order_type = 'Bill'; }
	else if ($order_type=='SQ') { $order_type = 'SQ'; }
	else if ($order_type=='EQ') { $order_type = 'EQ'; }
	else if ($order_type=='FSQ') { $order_type = 'FSQ'; }
	else if ($order_type=='CQ') { $order_type = 'CQ'; }
	else if ($order_type=='Payment') { $order_type = 'Payment'; }
*/
	$order_number = $file_split[1];

	if ($order_type=='SQ') {
		include_once $_SERVER['ROOT_DIR'].'/inc/renderQuote.php';

		$html = renderQuote($order_number);
	} else if ($order_type=='EQ') {//Equipment Quote
		include_once $_SERVER['ROOT_DIR'].'/inc/renderQuote.php';

		//$html = renderQuote(0, 'Demand', false, 7.75, $order_number);

		$quote_table = 'Demand';//default
		// determine the quote type
		$types = array('Demand','Supply','Repair Quote','Repair Vendor');
		foreach ($types as $type) {
			$T = order_type($type);

			$query = "SELECT * FROM ".$T['items']." WHERE ".$T['order']." = '".res($order_number)."'; ";
			$result = qedb($query);
			if (qnum($result)>0) {
				$quote_table = $type;
				break;
			}
		}

		$html = renderQuote(0, $quote_table, false, 0, $order_number);
    } else if ($order_type=='CQ') {
    	include_once $_SERVER['ROOT_DIR'].'/inc/renderQuote.php';
    	
		$html = renderQuote($order_number, 'Service');
    } else if ($order_type=='FSQ') {
    	include_once $_SERVER['ROOT_DIR'].'/inc/renderQuote.php';
    	
		$html = renderQuote('', 'service_quote', '', '', $order_number);
    } else if ($order_type=='Payment') {
    	include_once $_SERVER['ROOT_DIR'].'/inc/renderCheck.php';
    	
		$html = renderCheck($order_number);;
    } else if ($order_type == "PSP") {
    	$packageids = explode('_',$file_split[1]);
        $html = renderPackage($packageids, $order_type);
    } else if ($order_type == "PS") {
        $file_parts = preg_replace('/^(PS)([0-9]+)([D](.*))/','$1,$2,$4',$filename);
    	$file_split = explode(',',$file_parts);
        $html = create_packing_slip($file_split[1],substr($file_split[2], 0,-4));
    } else if ($order_type != "PS"){
	    $html = renderOrder($order_number,$order_type);
    }


    // reference the Dompdf namespace
    use Dompdf\Dompdf;
    use Dompdf\Options;

	//for external images
	$options = new Options();
	$options->set('isRemoteEnabled', TRUE);

    // instantiate and use the dompdf class
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);

    // (Optional) Setup the paper size and orientation
    $dompdf->setPaper('A4');//, 'portrait');

    // Render the HTML as PDF
    $dompdf->render();

    // set HTTP response headers
	header('Content-Type:application/pdf');
	header("Cache-Control: max-age=0");
	header("Accept-Ranges: none");
//  header("Content-Disposition: attachment; filename=PO".$order_number.".pdf");

    // Output the generated PDF to Browser
//	$dompdf->stream();

	$output = $dompdf->output();
	echo $output;
?>
