<?php
function sanitize_file_name( $filename ) {


    $special_chars = array("?","..", "[", "]", "/", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}");

    foreach($special_chars as $special_char){
    	$filename = str_replace($special_char, '', $filename);
    }

    $filename = preg_replace('/[\s-]+/', '-', $filename);


    return  $filename;
}