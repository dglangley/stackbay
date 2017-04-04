<?php
    $rootdir = $_SERVER['ROOT_DIR'];

    // include dompdf autoloader
    include_once $rootdir.'/dompdf/autoload.inc.php';
	include_once $rootdir.'/inc/renderOrder.php';

    $filename = trim(preg_replace('/([\/]docs[\/])([^.]+[.]pdf)/i','$2',$_SERVER["REQUEST_URI"]));
	$file_parts = preg_replace('/^(INV|SO|PO|CM||RMA)([0-9]+).*/','$1-$2',$filename);

	$file_split = explode('-',$file_parts);
	$order_type = $file_split[0];
	if ($order_type=='PO') { $order_type = 'Purchase'; }
	else if ($order_type=='SO') { $order_type = 'Sales'; }
	else if ($order_type=='RMA') { $order_type = 'RMA'; }
	$order_number = $file_split[1];

	$html = renderOrder($order_number,$order_type);
    // reference the Dompdf namespace
    use Dompdf\Dompdf;

    // instantiate and use the dompdf class
    $dompdf = new Dompdf();
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
