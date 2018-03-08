<?php
    $rootdir = $_SERVER['ROOT_DIR'];

    // include dompdf autoloader
    include_once $rootdir.'/dompdf/autoload.inc.php';
	include_once $rootdir.'/inc/renderOrder.php';
    include_once $rootdir.'/inc/packing-slip.php';
    $filename = trim(preg_replace('/([\/]docs[\/])([^.]+[.]pdf)/i','$2',$_SERVER["REQUEST_URI"]));
	$file_parts = preg_replace('/^(INV|Bill|PS|OS|SO|PO|CM|RMA|LUMP|SQ|EQ|CQ|FSQ|Payment)([0-9]+).*/','$1-$2',$filename);

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
		include_once $rootdir.'/inc/renderQuote.php';

		$html = renderQuote($order_number);
	} else if ($order_type=='EQ') {//Equipment Quote
		include_once $rootdir.'/inc/renderQuote.php';

		//$html = renderQuote(0, 'Demand', false, 7.75, $order_number);
		$html = renderQuote(0, 'Demand', false, 0, $order_number);
    } else if ($order_type=='CQ') {
    	include_once $rootdir.'/inc/renderQuote.php';
    	
		$html = renderQuote($order_number, 'Service');
    } else if ($order_type=='FSQ') {
    	include_once $rootdir.'/inc/renderQuote.php';
    	
		$html = renderQuote('', 'service_quote', '', '', $order_number);
    } else if ($order_type=='Payment') {
    	include_once $rootdir.'/inc/renderCheck.php';
    	
		$html = renderCheck($order_number);;
    } else if ($order_type != "PS"){
	    $html = renderOrder($order_number,$order_type);
    } else {
        $file_parts = preg_replace('/^(PS)([0-9]+)([D](.*))/','$1,$2,$4',$filename);
    	$file_split = explode(',',$file_parts);
        $html = create_packing_slip($file_split[1],substr($file_split[2], 0,-4));
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
