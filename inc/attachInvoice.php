<?php
    include_once $_SERVER["ROOT_DIR"].'/inc/renderOrder.php';
    include_once $_SERVER["ROOT_DIR"].'/dompdf/autoload.inc.php';

    // instantiate and use the dompdf class
	use Dompdf\Dompdf;

	$temp_dir = sys_get_temp_dir();
	if (substr($temp_dir,strlen($temp_dir)-1,1)<>'/') { $temp_dir .= '/'; }
	function attachInvoice($invoice) {
		global $temp_dir;

		// get html-rendered invoice for passing to dompdf
        $invoice_html = renderOrder($invoice,'INV');

		$dompdf = new Dompdf();
		$dompdf->loadHtml($html);

		// (Optional) Setup the paper size and orientation
		$dompdf->setPaper('A4');//, 'portrait');

		// Render the HTML as PDF
		$dompdf->render();

        $output = $dompdf->output();

		$attachment = $temp_dir."invoice_attachment_".$invoice_id.".pdf";
		$handle = fopen($attachment, "w");
		// add contents from file
		fwrite($handle, $output);
		fclose($handle);

		return ($attachment);
	}
?>
