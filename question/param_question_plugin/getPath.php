<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/question/import_form.php');
require_once($CFG->dirroot . '/question/format.php');
require_once($CFG->dirroot . '/question/format/xml/format.php');
$mycat=$_GET['cat'];
class qformat_xmlmy extends qformat_xml{
	public function getmyPath($id){
		return parent::get_category_path($id);
	}
}
list($thispageurl, $contexts, $cmid, $cm, $module, $pagevars) =
        question_edit_setup('import', '/question/import.php', false, false);

// get display strings
$txt = new stdClass();
$txt->importerror = get_string('importerror', 'question');
$txt->importquestions = get_string('importquestions', 'question');

list($catid, $catcontext) = explode(',', $pagevars['cat']);
$category = $DB->get_record("question_categories", array('id' => $catid));
$categorycontext = context::instance_by_id($category->contextid);
$category->context = $categorycontext;
$classname = 'qformat_xmlmy';
$qformat = new $classname();
$qformat->setCategory($category);
$qformat->setContexts($contexts->having_one_edit_tab_cap('import'));
$qformat->setCourse($COURSE);
$path=$qformat->getmyPath($catid);
print_r(str_replace("\$course\$", "", $path));
