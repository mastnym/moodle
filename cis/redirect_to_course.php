<?php
require_once '../config.php';

if (isset($_GET["sis_id"])){
	$course_sis_id=$_GET["sis_id"];
	$courses=$DB->get_records("course", array());
	foreach ($courses as $course ){
		if ($course->idnumber==$course_sis_id){
			header( 'Location: '.$CFG->wwwroot."/course/view.php?id=$course->id" ) ;
			exit();
		}
	}
	header( 'Location: '.$CFG->wwwroot ) ;

}else{
	header( 'Location: '.$CFG->wwwroot ) ;
}