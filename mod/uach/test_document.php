<?php
include 'sanitize.php';
	$filename = $_GET['filename'];
	$filename=sanitize_file_name($filename);
	header('Content-disposition: attachment; filename=test.docx');
	header('Content-type: application/msword');
	readfile("/tmp/uach/".$filename);
?>