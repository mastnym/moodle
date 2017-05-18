<?php 
	
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/question/export_form.php');
require_once 'settings_form.php';
require_once 'locallib.php';

$id = optional_param('moduleid', 0, PARAM_INT);
$cat = optional_param("category", 0, PARAM_INT);
$saved = optional_param("saved", 0, PARAM_INT);

if ($id) {
	$cm         = get_coursemodule_from_id('papertest', $id, 0, false, MUST_EXIST);
	$course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
	$papertest  = $DB->get_record('papertest', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
	print_error('You must specify a course_module ID or an instance ID');
}


require_login($course, true, $cm);
$context = context_module::instance($cm->id);
$course_context = context_course::instance($course->id);
$contexts=new question_edit_contexts($context);

if (!has_capability("mod/papertest:editsettings",$context)){
	print_error('no_capability','papertest');
}

$PAGE->set_url('/mod/papertest/papertest_settings.php', array('moduleid' => $cm->id));
$PAGE->set_context($context);
$PAGE->set_title(get_string("settings","papertest"));
$PAGE->set_heading(get_string("settings","papertest"));



$settings_form = new settings_form($PAGE->url, array('contexts'=>array($course_context)));

if ($cat!=0){
	$category=$DB->get_record("question_categories", array("id"=>$cat),"*",MUST_EXIST);
	$edit_settings_form = new edit_settings_form($PAGE->url,array("cat"=>$category,'contexts'=>array($course_context)));
	
	$data = get_or_create_setting($category->id);
	//editor hack
	$data ->instructions = array('text'=>$data ->instructions);
	
	$edit_settings_form ->set_data($data);
	
	if ($edit_settings_form->is_cancelled()) {;
		redirect("view.php?id=".$id);
	} else if ($fromform = $edit_settings_form->get_data()) {
		$fromform->instructions = $fromform->instructions["text"];
		$DB->update_record("papertest_settings", $fromform);
		redirect($PAGE->url."&category=".$fromform->cat."&saved=1");
	}
}



if (($from_form= $settings_form->get_data())){
	$cat=$from_form->category;
	redirect($PAGE->url."&category=".$cat);
}

if (($from_form= $settings_form->get_data())){
	$cat=$from_form->category;
	redirect($PAGE->url."&category=".$cat);
}
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("addsettings","papertest"));
if ($saved){
	echo html_writer::div(get_string("categorysaved","papertest"),"saved",array("style"=>"color:green;"));
}
if ($cat == 0){
	$settings_form -> display();
}
else{
	$edit_settings_form->display();
}


echo $OUTPUT->footer();
exit();

?>