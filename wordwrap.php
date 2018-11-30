<?php
$test = 'This is a test of word-wrapping. Hey, just received the Sessa Fan PO so we can pay them...Were waiting on some small clips that were left off of the original build that are on PO 518494 and most of the fans themselves were on back order from Mouser.  Whats the best way to keep these units quarantined for now?';
$lim = 40;
$str = wordwrap($test,$lim,'<BR>');//chr(10));
echo $str;
?>
