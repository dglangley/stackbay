<?php
    function array_keysearch(&$haystack,$needle) {
        foreach ($haystack as $straw => $bool) {
//          echo $straw.':'.$needle.':'.stripos($straw,$needle).'<BR>';
            if (stripos($straw,$needle)!==false) {
                unset($haystack[$straw]);
//              break;
            }
        }
    }
?>
