<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Prints a particular instance of papertest
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_papertest
 * @copyright  2011 Martin Mastny
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/// (Replace papertest with the name of your module and remove this line)

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once('locallib.php');
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/question/export_form.php');
include 'test_export_form.php';

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // papertest instance ID - it should be named as the first character of the module

if ($id) {
    $cm         = get_coursemodule_from_id('papertest', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $papertest  = $DB->get_record('papertest', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $papertest  = $DB->get_record('papertest', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $papertest->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('papertest', $papertest->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}
$_POST["courseid"]=$course->id;
$mid=$cm->id;

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
$course_context = context_course::instance($course->id);
$contexts=new question_edit_contexts($context);

//add_to_log($course->id, 'papertest', 'view', "view.php?id={$cm->id}", $papertest->name, $cm->id);
$event = \mod_papertest\event\course_module_viewed::create(array(
		'objectid' => $mid,
		'context' => $context,
));
$event->add_record_snapshot('course', $course);
// In the next line you can use $PAGE->activityrecord if you have set it, or skip this line if you don't have a record.
//$event->add_record_snapshot($PAGE->cm->modname, $activityrecord);
$event->trigger();


/// Print the page header

$PAGE->set_url('/mod/papertest/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($papertest->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Output starts here
echo $OUTPUT->header();

if ($papertest->intro) {
    echo $OUTPUT->box(format_module_intro('papertest', $papertest, $cm->id), 'generalbox mod_introbox', 'papertestintro');
}


$export_form = new test_export_form($PAGE->url, array('contexts'=>array($course_context)));


if (($from_form= $export_form->get_data())) {
	$testid = $from_form->category;
	$copies = $from_form -> copies;
	$question_points = isset($from_form -> show_points);
	$question_numbers = isset($from_form -> show_numbers);
	$results = isset($from_form -> showAnswers);
	$all = isset($from_form->all);
        $shuffle = isset($from_form->shuffle);
	$cvVersion=$from_form->openCvVersion;
        if ($cvVersion) {
            $shuffle = true;
        }
        if ($all){
		$copies=1;
	}
	//search for all others from same course
	$categories=get_categories_for_contexts($course_context->id);
	
	//add atributes to each cat and get selected category object
	processCategories($categories,$testid);
	
	
	$temp=sys_get_temp_dir().DIRECTORY_SEPARATOR."papertest";
	if(!file_exists($temp)){
		mkdir($temp);
	}
	
	$generatedFiles=array();
	for ($i = 0; $i < $copies; $i++) {
		getQuestionsToCategories($categories,$all, $shuffle);

		$doc = new DocGenerator( $categories);
		$doc -> question_points = $question_points;
		$doc -> question_numbers = $question_numbers;
		$doc -> results = false;
		$doc -> all = $all;
		$doc -> cvVersion = $cvVersion;
		$doc-> variant = strval($i);
		
		$doc-> generateDoc();
		$mhtml=$doc->createMHTML();
		$failed=$doc->failed;
		$filename = $doc->writeTestToFile($mhtml);
		array_push($generatedFiles, $filename);
		
		if ($results){
			//settings for results
			$doc -> question_points = true; //always show points for question in results
			$doc -> results=true;
			
			$doc-> generateDoc();
			$mhtml=$doc->createMHTML();
			$failed=$doc->failed;
			$doc->writeTestToFile($mhtml);
		}
		if ($failed){
			echo $OUTPUT -> heading(get_string("warning","papertest",i),4);
		}
	}
	
	if (count($generatedFiles)==1 && !$results){//only one doc
		$pathchunks=explode(DIRECTORY_SEPARATOR, $filename);
		$loc=$pathchunks[count($pathchunks)-2];
		echo html_writer::link('test_document.php?loc='.$loc.'&filename=test.doc',get_string("downloaddoc","papertest"));
	}else{//zipfile
		$zipfile = createZip($generatedFiles);
		if ($zipfile){
			echo html_writer::link('test_document.php?filename='.basename($zipfile),get_string("downloadzip","papertest"));
		}
	}

	echo $OUTPUT->footer();
	exit();
}
echo $OUTPUT->heading(get_string('moduleusage','papertest',$COURSE->fullname));
$export_form->display();


echo $OUTPUT->box_start();
echo $OUTPUT->heading(get_string("rules","papertest"),3);
echo html_writer::link('papertest_settings.php?moduleid='.$mid, get_string('editsettings','papertest'));
$rules = array(
		get_string("rule1","papertest"),
		get_string("rule2","papertest"),
		get_string("rule3","papertest"),
		get_string("rule4","papertest"),
		get_string("rule5","papertest"),
		get_string("rule6","papertest"),
		get_string("rule7","papertest"),
		);
echo html_writer::alist($rules);
echo $OUTPUT->box_end();

echo $OUTPUT->footer();
exit();
