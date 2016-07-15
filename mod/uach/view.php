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
 * Prints a particular instance of uach
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod
 * @subpackage uach
 * @copyright  2011 Martin Mastny
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // uach instance ID - it should be named as the first character of the module

if ($id) {
    $cm         = get_coursemodule_from_id('uach', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $uach  = $DB->get_record('uach', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $uach  = $DB->get_record('uach', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $uach->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('uach', $uach->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);

add_to_log($course->id, 'uach', 'view', "view.php?id={$cm->id}", $uach->name, $cm->id);

/// Print the page header

$PAGE->set_url('/mod/uach/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($uach->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// other things you may want to set - remove if not needed
//$PAGE->set_cacheable(false);
//$PAGE->set_focuscontrol('some-html-id');
//$PAGE->add_body_class('uach-'.$somevar);

// Output starts here
echo $OUTPUT->header();
?>
<script>
window.location="test.php?courseid=<?php echo $course->id;?>&moduleid=<?php echo $cm->id;?>";
</script>
<?php 
if ($uach->intro) { // Conditions to show the intro can change to look for own settings or whatever
    echo $OUTPUT->box(format_module_intro('uach', $uach, $cm->id), 'generalbox mod_introbox', 'uachintro');
}

// Replace the following lines with you own code
echo $OUTPUT->heading(get_string('moduleusage','uach').' '.$course->fullname);

echo $OUTPUT->single_button("test.php?courseid={$course->id}&moduleid={$cm->id}",get_string('generate','uach'));
// Finish the page
echo $OUTPUT->footer();




