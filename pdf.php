<?php
	// include dompdf autoloader
	include_once 'dompdf/autoload.inc.php';
	include_once 'inc/renderPHPtoHTML.php';

	$po_html = renderPHPtoHTML('order-print.php');
echo $po_html;exit;

	// reference the Dompdf namespace
	use Dompdf\Dompdf;

	// instantiate and use the dompdf class
	$dompdf = new Dompdf();
	$dompdf->loadHtml();

	// (Optional) Setup the paper size and orientation
	$dompdf->setPaper('A4', 'landscape');

	// Render the HTML as PDF
	$dompdf->render();

	// Output the generated PDF to Browser
	$dompdf->stream();
?>
