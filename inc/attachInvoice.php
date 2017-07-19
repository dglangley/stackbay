<?php
    include_once $_SERVER["ROOT_DIR"].'/inc/renderOrder.php';
    include_once $_SERVER["ROOT_DIR"].'/dompdf/autoload.inc.php';

    // instantiate and use the dompdf class
	use Dompdf\Dompdf;
    use Dompdf\Options;

	//for external images
	$pdf_options = new Options();
	$pdf_options->set('isRemoteEnabled', TRUE);

	$temp_dir = sys_get_temp_dir();
	if (substr($temp_dir,strlen($temp_dir)-1,1)<>'/') { $temp_dir .= '/'; }
	function attachInvoice($invoice) {
		global $temp_dir,$pdf_options;

		// get html-rendered invoice for passing to dompdf
        $html = renderOrder($invoice,'INV');

		$dompdf = new Dompdf($pdf_options);
		$dompdf->loadHtml($html);

		// (Optional) Setup the paper size and orientation
		$dompdf->setPaper('A4');//, 'portrait');

		// Render the HTML as PDF
		$dompdf->render();

        $output = $dompdf->output();

		$attachment = $temp_dir."Invoice_".$invoice.".pdf";
		$handle = fopen($attachment, "w");
		// add contents from file
		fwrite($handle, $output);
		fclose($handle);

		return ($attachment);
	}
?>
