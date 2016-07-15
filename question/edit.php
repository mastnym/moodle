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
 * Page to edit the question bank
 *
 * @package    moodlecore
 * @subpackage questionbank
 * @copyright  1999 onwards Martin Dougiamas {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__FILE__) . '/../config.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once("$CFG->dirroot/mod/uach/uach_config.php");
require_once("$CFG->dirroot/question/param_question_plugin/config.php");

list($thispageurl, $contexts, $cmid, $cm, $module, $pagevars) =
        question_edit_setup('questions', '/question/edit.php');

$url = new moodle_url($thispageurl);
if (($lastchanged = optional_param('lastchanged', 0, PARAM_INT)) !== 0) {
    $url->param('lastchanged', $lastchanged);
}
$PAGE->set_url($url);

$questionbank = new core_question\bank\view($contexts, $thispageurl, $COURSE, $cm);
$questionbank->process_actions();

// TODO log this page view.

$context = $contexts->lowest();
$streditingquestions = get_string('editquestions', 'question');
$PAGE->set_title($streditingquestions);
$PAGE->set_heading($COURSE->fullname);
echo $OUTPUT->header();

echo '<div class="questionbankwindow boxwidthwide boxaligncenter">';
$questionbank->display('questions', $pagevars['qpage'], $pagevars['qperpage'],
        $pagevars['cat'], $pagevars['recurse'], $pagevars['showhidden'],
        $pagevars['qbshowtext'],$pagevars['srchtext']);
if (isset($CFG->param_question_subjects[$COURSE->shortname])){
	echo '<a href="param_question_plugin/index.php?courseid='.$COURSE->id.'">Vložit parametrickou otázku</a> (Vyžaduje zapnutý Javascript a moderní prohlížeč - IE 10, Firefox 20+, Chrome 25+)';
  echo '<br/>';
  echo '<a href="param_question_plugin/sablona.xlsx">Zde si můžete stánout šablonu pro Excel.</a>'   ;
}
echo "</div>\n";

echo $OUTPUT->footer();
