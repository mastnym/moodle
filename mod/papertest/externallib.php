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
 * External assign API
*
* @package    mod_papertest
* @since      Moodle 2.4
* @copyright  2012 Martin Mastny
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");

require_once("$CFG->dirroot/mod/papertest/locallib.php");
require_once("$CFG->dirroot/mod/papertest/question_types/qtypes.php");
require_once($CFG->dirroot .'/question/editlib.php');

class mod_papertest_external extends external_api{
	public static function get_test_names_parameters() {
		return new external_function_parameters(
				array('courseid' => new external_value(PARAM_INT, 'Id of course', VALUE_REQUIRED),
				)
		);
	}
	
	public static function get_test_names($courseid){
		global $CFG,$DB;
		//validation
		$context= context_course::instance($courseid);
	
		if (!has_capability('moodle/question:managecategory', $context)){
			throw new moodle_exception('cannotManageActivities','ciswebservices','');
				
		}
		$select = "parent = 0 AND contextid = ?";
		$categories = $DB->get_records('question_categories', array("parent"=>'0',"contextid"=>$context->id), "sortorder ASC");
		$ret = array();
		foreach ($categories as $cat){
			$arr=array();
			$arr["id"]=$cat->id.",".$context->id;
			$arr["name"]=$cat->name;
			array_push($ret, $arr);
		}
		return $ret;
	
	
			
	}
	public static function get_test_names_returns() {
		return new external_multiple_structure(
				new external_single_structure(
						array(
								'id'       => new external_value(PARAM_TEXT, 'ID of the topmost categories'),
								'name'       => new external_value(PARAM_TEXT, 'Names of the topmost categories'),
						)
				)
		);
	}

	public static function get_test_parameters() {
		return new external_function_parameters(
				array('courseid' => new external_value(PARAM_INT, 'Id of course', VALUE_REQUIRED),
						'testid' => new external_value(PARAM_TEXT, 'Moodle question category id xxx,xx', VALUE_REQUIRED),
				)
		);
	}
	
	public static function get_test($courseid,$testid){
		global $CFG,$DB,$COURSE;
		//validation
		$course = $DB->get_record('course', array('id'=>$courseid));
		require_login($course->id);
		$context = context_course::instance($course->id, MUST_EXIST);
		$contexts = new question_edit_contexts($context);
		
		if (!has_capability('moodle/question:managecategory', $context)){
			throw new moodle_exception('cannotManageActivities','ciswebservices','');
	
		}
		list($catid,$catcontext)=explode(',', $testid);
		
	
		//search for all others from same course
		$categories=get_categories_for_contexts($catcontext);
	
		//add atributes to each cat and get selected category object
		processCategories($categories,$testid);
	
	
		$temp=sys_get_temp_dir().DIRECTORY_SEPARATOR."papertest";
		if(!file_exists($temp)){
				mkdir($temp);
		}
		$all=false;
		getQuestionsToCategories($categories,$all,true);
	
		$doc = new DocGenerator( $categories);
		$doc -> question_points = 0;
		$doc -> question_numbers = 0;
		$doc -> results = false;
		$doc -> all = 0;
		$doc -> cvVersion = 1;
		$doc-> variant = "0";
		
		$doc-> generateDoc();
		$mhtml=$doc->createMHTML();
		$failed=$doc->failed;
		$doc->writeTestToFile($mhtml);
		
		$metadata = json_encode(array("questions"=>$doc->metadata));
		//gzip and base64 
		$metadata = base64_encode(gzencode($metadata,9));
		
		header ('X-metadata: '.$metadata);
		return $mhtml;
		/*
		header('Content-type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
		header('Content-Disposition: attachment; filename="test.doc"');
		ob_clean();
		flush();
		echo $mhtml;
	
		exit();*/
	
	
	
			
	}
	public static function get_test_returns() {
		return new external_value(PARAM_RAW, 'Id of course', VALUE_REQUIRED) ;
	}
}