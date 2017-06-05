<?php
require_once(dirname(__FILE__) . '/../../config.php');
if (is_uploaded_file($_FILES['file']['tmp_name'])){
// $path = 'tmp/';
//  if ($handle = opendir($path)) {
//     while (false !== ($file = readdir($handle))) {
//        if ($file != "." && $file != ".." &&(time()-filectime($path.$file)) < 2*60*60) { //2 hours
//                 unlink($path.$file);
//
//        }
//     }
//   }

	$tmp_name=$_FILES['file']['tmp_name'];
	$filename=basename($tmp_name);
	$filename=substr_replace($filename , 'xml', strrpos($filename , '.') +1);
	$path="$CFG->dataroot/temp/questionimport/";
	if (!file_exists($path)) {
	    mkdir($path, 0777, true);
	}
	$path = $path .$filename;
	copy($tmp_name, $path);
	echo "ok";
	echo $filename;
}
else{
	echo "Není možné naimportovat";
}