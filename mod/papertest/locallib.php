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
 * Internal library of functions for module papertest
 *
 * All the papertest specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_papertest
 * @copyright  2011 Martin Mastny
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use Symfony\Component\Translation\Dumper\DumperInterface;

defined('MOODLE_INTERNAL') || die();
require_once 'question_types/qtypes.php';




//category related
function processCategories(&$categories,$testid){
	foreach ($categories as $cat){
		$cat->joinedID=$cat->id.",".$cat->contextid;
		$cat->selected = false;
		if ($cat->joinedID == $testid){
			$cat->selected = true;
		}

		$data=get_or_create_setting($cat->id);
		$cat->alternate_name=$data->alternate_name;
		$cat->instructions=$data->instructions;
		$cat->display = $data->display;
		$cat->count=intval($data->questions);
		$cat->points=intval($data->points);
		$cat->spaces=intval($data->spaces);
		$cat->display_points = $data->display_points;

		$cat->isTop = $cat->parent == 0;
		$cat->level=getNestedLevel($cat,$categories);
		$cat->isLeaf = categoryIsLeaf($cat, $categories);
	}
}

function categoryIsLeaf($tested,$categories){
	foreach ($categories as $category){
		if ($tested->id == $category->parent){
			return false;
		}
	}
	return true;
}
function getNestedLevel($cat,$categories){
	$level=0;
	while ($cat->parent!=0){
		$cat=getQuestionCategoryById($cat->parent, $categories);
		$level++;
	}
	return $level;
}

function getQuestionCategoryById($id,$categories){
	foreach ($categories as $category){
		if ($category->id==$id){
			return $category;
		}
	}
	return null;
}


function getQuestionsToCategories(&$categories,$all, $shuffle){
	//get questions to each category
	if (!$all){
		foreach($categories as $cat){
				getQuestionsInCategory($cat, false, $shuffle);
		}
	}
	//export only one category-but all questions
	//dont consider children for security reasons
	else{
		foreach($categories as $cat){
			if ($cat->selected){
				getQuestionsInCategory($cat, true, $shuffle);
			}
		}
	}
}

function getQuestionsInCategory(&$cat,$all,$shuffle){
	global $DB;
	if (!$all){
		$q=$DB->get_records("question", array("category"=>$cat->id,"parent"=>0),'','id');
		if ($q){
                        if ($shuffle){
                            shuffle($q);
                        }
			if ($cat->count > count($q)){
				$cat->count = count($q);
			}
			$q=array_slice($q, 0,$cat->count);
			$ids=array();
			foreach($q as $object_with_id){
				array_push($ids, $object_with_id->id);
			}
			$cat->questions=$DB->get_records_list("question", 'id', $ids);
                        if ($shuffle){
                            shuffle($cat->questions);
                        }			
		}else{
			$cat->questions = array();
		}

	}else{
		$q=$DB->get_records("question", array("category"=>$cat->id,"parent"=>0));
		$cat->questions=array();
		if ($q){
			$cat->questions = $q;
                        if ($shuffle){
                            shuffle($cat->questions); 
                        }
		}
	}
	foreach ($cat->questions as $question){
		$question->contextid=$cat->contextid;
		$qtype = question_bank::get_qtype($question->qtype, false);
		$qtype->get_question_options($question);
		getQuestionFiles($question);
	}
}


function getQuestionFiles(&$question){
	$fs = get_file_storage();
	$contextid = $question->contextid;
	// Get files used by the questiontext.
	$question->questiontextfiles = $fs->get_area_files(
			$contextid, 'question', 'questiontext', $question->id);
	// Get files used by the generalfeedback.
	$question->generalfeedbackfiles = $fs->get_area_files(
			$contextid, 'question', 'generalfeedback', $question->id);
	if (!empty($question->options->answers)) {
		foreach ($question->options->answers as $answer) {
			$answer->answerfiles = $fs->get_area_files(
					$contextid, 'question', 'answer', $answer->id);
			$answer->feedbackfiles = $fs->get_area_files(
					$contextid, 'question', 'answerfeedback', $answer->id);
		}
	}
}



function getChildren($parent,$categories){
	$ret=array();
	foreach ($categories as $cat){
		if ($cat->parent==$parent->id){
			$ret[]=$cat;
		}
	}
	return $ret;
}


//generation of doc

class DocGenerator{
	public function __construct($categories){
		foreach ($categories as $cat){
			if ($cat -> selected){
				$this->selected=$cat;
			}
		}
		$this->categories=$categories;

		$tmp=sys_get_temp_dir().DIRECTORY_SEPARATOR."papertest";
		$this->tmpdir=tempdir($tmp,"test");
		$this->failed=false;
		$this->metadata = array();
		/*$this -> question_points;
		$this -> question_numbers;
		$this -> results;
		$this -> all;*/
	}



	function generateDoc(){
		global $CFG,$COURSE;
		$path=dirname(__FILE__);
		//generating test
		if (!$this->all){
			$template = $path.DIRECTORY_SEPARATOR."test_templates".DIRECTORY_SEPARATOR.$COURSE->idnumber.".htm";
			if (!file_exists($template)){
				$template=$path.DIRECTORY_SEPARATOR."test_templates".DIRECTORY_SEPARATOR."default".DIRECTORY_SEPARATOR."test.htm";
			}
			$template_content=file_get_contents($template);
			$html=$this->generateCategory($this->selected, $this->categories,true,false);
                        //css
                        $css = file_get_contents($path.DIRECTORY_SEPARATOR."test_templates".DIRECTORY_SEPARATOR."css".DIRECTORY_SEPARATOR."default.css");
                        $custom_css_path = $path.DIRECTORY_SEPARATOR."test_templates".DIRECTORY_SEPARATOR."css".DIRECTORY_SEPARATOR.$COURSE->idnumber.".css";
                        if (file_exists($custom_css_path)){
                            $css .= file_get_contents($custom_css_path); 
                        }
                        //insert generated html into template
			$name=$this->getCategoryName($this->selected);
			$month = intval(date("m"));
			$year = intval(date("Y"));
			if ($month < 9){
			     $year -= 1;
			}
			$year = strval($year) . "/" . strval(($year+1));
                        $html=str_replace(array("{{TEST_NAME}}","{{TEST_NUM}}","{{code}}","{{content}}","{{year}}","{{CSS}}"),
			         array($name,$this->variant,getUniqueCode(),$html, $year, $css), $template_content);

		}
		//export all - category
		else{
			$template = $path.DIRECTORY_SEPARATOR."test_templates".DIRECTORY_SEPARATOR."default".DIRECTORY_SEPARATOR."category.htm";
			$template_content=file_get_contents($template);
			$html=$this->generateCategory($this->selected, $this->categories, false,true);
			$html=str_replace("{{content}}", $html, $template_content);
		}
		$file=$this->tmpdir.DIRECTORY_SEPARATOR."test.htm";
		if ($this->results){
			$file=$this->tmpdir.DIRECTORY_SEPARATOR."test_res.htm";
		}
		file_put_contents($file, $html);
	}

	private function generateCategory($category,&$categories,$recursive,$generate_header){
		$html="<div class=\"cat_".$category->id."\">";
		//display only categories you want, except bulk export
		if (!$recursive && !$category->display){
			return $html;
		}
		if ($generate_header){
			$functionName="createHeader".$category->level;
			if (!function_exists($functionName)){
				$functionName="createHeader2";
			}

			$name = $this->getCategoryName($category);
			$html.=createHeader($name,$category->level);
		}

		if ($category->instructions){
			$html.=$category->instructions;
		}

		if ($category->questions){
			$html.=$this->generateQuestions($category);
		}

		if ($recursive){
			foreach (getChildren($category, $categories) as $child){
				$html.=$this->generateCategory($child,$categories,$recursive,true);
			}
		}
                $html .= "<br class=\"end_".$category->id."\"/></div>";
		return $html;
	}

	private function generateQuestions($category){
		$html="<table class=\"questionTable\">";
		$order=0;
		if ($this->question_numbers){
			$order=1;//numbering questions within category, if 0 ->no numbers
		}

		foreach ($category->questions as $question){

			$type=$question->qtype."Qtype";
			if (class_exists($type)){
				$class =new $type($question,$category,$order,$this);
			}else{
				$class =new generalQtype($question,$category,$order,$this);
			}
			if ($this->cvVersion){
				$class->generateMetadata();
			}

			$html.=$class->exportQuestion();


			if ($this->cvVersion){
				$html.=$class->generateCheckboxesForSemiAutomaticCorrection();
			}

			if ($class->failed()){
				$this->failed=true;
			}

			if ($order!=0){
				$order++;
			}
		}
		return $html . "</table>";

	}


	function createMHTML(){
                global $CFG,$COURSE;
		$exp = new mime10class();
		$testdir=$this->tmpdir.DIRECTORY_SEPARATOR;
		$doc=$testdir."test.htm";
		if ($this->results){
			$doc=$testdir."test_res.htm";
		}
		if (!file_exists($doc)){
			throw new Exception("Doc file not present!");
		}
		$exp->addFile("test.htm", 'text/html; charset="utf-8"', file_get_contents($doc));

		$files_folder=$testdir."test_files";
		if (!file_exists($files_folder)){
			mkdir($files_folder);
		}
		//add header file
		$path=dirname(__FILE__);
                $header_path = $path.DIRECTORY_SEPARATOR."test_templates".DIRECTORY_SEPARATOR."headerfooter".DIRECTORY_SEPARATOR."header.htm";
                $custom_header_path = $path.DIRECTORY_SEPARATOR."test_templates".DIRECTORY_SEPARATOR."headerfooter".DIRECTORY_SEPARATOR.$COURSE->idnumber.".htm";
                if (file_exists($custom_header_path)){
                    $header_path = $custom_header_path;
                }
                copy($header_path, $files_folder.DIRECTORY_SEPARATOR."header.htm");
		$files = new DirectoryIterator($files_folder);
		foreach ($files as $fileinfo) {
			if (!$fileinfo->isDot()) {
				$filename=$fileinfo->getFilename();
				$chunks=explode(".", $filename);
				$extension=end($chunks);
				$exp->addFile("test_files/".$filename, 'image/'.$extension, file_get_contents($files_folder.DIRECTORY_SEPARATOR.$filename));
			}
		}

		return $exp->getFile();
	}
	function writeTestToFile($mhtml){
		$filename="test.doc";
		if ($this->results){
			$filename="test_res.doc";
		}
		$path=$this->tmpdir.DIRECTORY_SEPARATOR.$filename;
		$ret=file_put_contents($path, $mhtml);
		if ($ret===false){
			throw new Exception("Cannot write doc file");
		}
		return $path;
	}

	private function getCategoryName($category){
		$name = $category->alternate_name;
		if (!$name){
			$name = $category->name;
		}
		if ($category->display_points){
			$points=$this->getTotalPoints($category);

			if ($points!=0){
				return $name.= " ".get_string("points_test","papertest",round($points,2));
			}
		}
		return $name;
	}
	private function getTotalPoints($cat){
		$points=0;
		if ($cat->points){
			$points+=(floatval($cat->points) * count($cat->questions));
		}else{
			foreach($cat->questions as $q){
				$points+=floatval($q->defaultmark);
			}
		}
		foreach (getChildren($cat, $this->categories) as $child){
			$points+=$this->getTotalPoints($child);
		}
		return $points;
	}


}


class mime10class
{
	private $data;
	const boundary='----=_NextPart_ERTUP.EFETZ.FTYIIBVZR.EYUUREZ';

	function __construct() {
		$this->data="MIME-Version: 1.0\nContent-Type: multipart/related; boundary=\"".self::boundary."\"\n\n";
	}

	public function addFile($filepath,$contenttype,$data)
	{
		$this->data = $this->data.'--'.self::boundary."\nContent-Location: file:///C:/".preg_replace('!\\\!', '/', $filepath)."\nContent-Transfer-Encoding: base64\nContent-Type: ".$contenttype."\n\n";
		$this->data = $this->data.base64_encode($data)."\n\n";
	}

	public function getFile() {
		return $this->data.'--'.self::boundary.'--';
	}
}




//formatting functions
function createHeader($text,$level){
	$headerlevel = $level +1;
	if ($headerlevel > 6){
		$headerlevel =6;
	}
	return "<h$headerlevel>$text</h$headerlevel>";
}
function createSpace(){
	return "<p class=MsoNormal align=center style='text-align:center'><i><span
	style='font-size:12.0pt;line-height:115%'>&nbsp;</span></i></p>";
}

//other functions

function tempdir($dir=null,$prefix="") {
	if (!$dir || !file_exists($dir)){
		$dir=sys_get_temp_dir();
	};
	$tempfile=tempnam($dir,$prefix);
	if (file_exists($tempfile)) {
		unlink($tempfile);
	}
	mkdir($tempfile);
	if (is_dir($tempfile)) {
		return $tempfile;
	}
}

function getUniqueCode($length = 7)
{
	$code = md5(uniqid(rand(), true));
	if ($length != "") return substr($code, 0, $length);
	else return $code;
}

function createZip($files){
	$zip = new ZipArchive;
	$path =tempnam(sys_get_temp_dir().DIRECTORY_SEPARATOR."papertest", "zip");
	$res = $zip->open($path, ZipArchive::CREATE);
	if ($res === TRUE) {
		foreach ($files as $key=>$file){
			$dir=dirname($file);
			$filename=basename($file);
			$zip->addFile($file,"test".($key + 1).".doc");
			$result=$dir.DIRECTORY_SEPARATOR."test_res.doc";
			if (file_exists($result)){
				$zip->addFile($result,"test_res".($key+1).".doc");
			}

		}
		$zip->close();

	} else {
		echo html_writer::span(get_string("ziperror","papertest"));
		return "";
	}
	return $path;
}


function get_or_create_setting($categoryid){
	global $DB;
	$initial_data=$DB->get_record("papertest_settings", array("category"=>$categoryid));
	if (!$initial_data){
		$initial_data= new stdClass();
		$initial_data -> category = $categoryid;
		$initial_data -> alternate_name = "";
		$initial_data -> instructions = "";
		$initial_data -> display = true;
		$initial_data -> points = 0;
		$initial_data -> questions = 1;
		$initial_data -> spaces = 0;
		$initial_data -> display_points = false;
		$DB->insert_record("papertest_settings", $initial_data);
	}
	if (!isset($initial_data->id)){
		$initial_data=$DB->get_record("papertest_settings", array("category"=>$categoryid));
	}
	return $initial_data;
}


function deleteFolder($path)
{
	if (is_dir($path) === true)
	{
		$files = array_diff(scandir($path), array('.', '..'));

		foreach ($files as $file)
		{
			deleteFolder(realpath($path) . DIRECTORY_SEPARATOR . $file);
		}

		return rmdir($path);
	}

	else if (is_file($path) === true)
	{
		return unlink($path);
	}

	return false;
}