<?php
function htmlcp1252($string)
{
    $search = array('&',
                    '<',
                    '>',
                    '"',
                    chr(212),
                    chr(213),
                    chr(210),
                    chr(211),
                    chr(209),
                    chr(208),
                    chr(201),
                    chr(145),
                    chr(146),
                    chr(147),
                    chr(148),
                    chr(151),
                    chr(150),
                    chr(133),
                    chr(194)
                );

     $replace = array(  '&amp;',
                        '&lt;',
                        '&gt;',
                        '&quot;',
                        '&#8216;',
                        '&#8217;',
                        '&#8220;',
                        '&#8221;',
                        '&#8211;',
                        '&#8212;',
                        '&#8230;',
                        '&#8216;',
                        '&#8217;',
                        '&#8220;',
                        '&#8221;',
                        '&#8211;',
                        '&#8212;',
                        '&#8230;',
                        ''
                    );

    return str_replace($search, $replace, $string);
}
?>
