<?php
	include 'sanitize.php';
	$filename = $_GET['filename'];
	$filename=sanitize_file_name($filename);
	header('Content-type: image/png');
	readfile("uach/questions/".$filename);
?>