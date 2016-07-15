<?php
include 'sanitize.php';
	$filename = $_GET['filename'];
	$filename=sanitize_file_name($filename);
	header('Content-disposition: attachment; filename=tests.zip');
	header('Content-type: application/octet-stream');
	readfile("/tmp/uach/".$filename);
?>