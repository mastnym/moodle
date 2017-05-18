<?php
function sanitize_file_name( $filename ) {


	$special_chars = array("?","..", "[", "]", "/", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}");

	foreach($special_chars as $special_char){
		$filename = str_replace($special_char, '', $filename);
	}
	$filename = preg_replace('/[\s-]+/', '-', $filename);
	return  $filename;
}

function endsWith($haystack, $needle)
{
	return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
}


$filename = $_GET['filename'];
$location = isset($_GET['loc']);
if ($location){
	$location=$_GET['loc'];
}
$filename=sanitize_file_name($filename);
$location=sanitize_file_name($location);

if (endsWith($filename, "doc")){
	header('Content-disposition: attachment; filename='.$filename);
	header('Content-type: application/msword');
}else{
	header('Content-disposition: attachment; filename=tests.zip');
	header('Content-type: application/zip');
}

$temp=sys_get_temp_dir().DIRECTORY_SEPARATOR."papertest";
if ($location){
	readfile($temp.DIRECTORY_SEPARATOR.$location.DIRECTORY_SEPARATOR.$filename);	
}else {
	readfile($temp.DIRECTORY_SEPARATOR.$filename);
}

?>