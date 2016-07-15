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
 * English strings for papertest
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_papertest
 * @copyright  2011 Martin Mastny
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'papertest';
$string['modulenameplural'] = 'papertests';
$string['modulename_help'] = 'Use the papertest module for generating tests from question bank in "doc" format';
$string['moduleusage'] = 'Generate test for {$a}';
$string['papertestname'] = 'Paper test generator';
$string['papertestname_help'] = 'Generator name aka "Test generator"';
$string['papertest'] = 'paper test';
$string['pluginadministration'] = 'papertest administration';
$string['pluginname'] = 'papertest';
$string['defaultname'] = 'Paper-test generator';

$string['downloaddoc'] = 'Download test';
$string['downloadzip'] = 'Download zip with tests';

//rules
$string['rules'] = 'Generator rules';
$string['rule1'] = 'Categories corespond with those in Moodle';
$string['rule2'] = 'Ordering of categories in test is the same as ordering in Moodle';
$string['rule3'] = 'At least on category is required in Question Bank';
$string['rule4'] = 'Only bottom most category questions are considered in test';
$string['rule5'] = 'Refresh this site after changing order ofcategories in Question Bank';
$string['rule6'] = 'If you choose other than topmost category, then all questions will be exported';
$string['rule7'] = 'Field "section notes" is used for category decription, or test name';

//form
$string['choosecategoryheader'] = 'Choose category and parameters';
$string['choosecategory'] = 'Choose category';
$string['copies'] = 'Number of different copies';
$string['copies_validation'] = 'Must be a non zero number';
$string['show_numbers'] = 'Show question ordering numbers';
$string['show_points'] = 'Show points for each quiz question';
$string['showanswers'] = 'Generate copy with results';
$string['all'] = 'Export all questions from this category';
$string['generate'] = 'Generate';
$string['editsettings'] = 'Edit settings';
$string['warning'] = 'There was an error in formating chemical substances (test {$a}), it\'s marked by red color';
$string['ziperror'] = 'Can\'t create zip file';

//settings
$string['settings'] = 'Settings';
$string['addsettings'] = 'Additional test settings';
$string['choosecategoryheaderandparams'] = 'Choose category and fill parameters';
$string['settingssubmit'] = 'Edit category';
$string['nextedit'] = 'Next category to edit';
$string['savenext'] = 'Save and proceed to next category';

$string['alternatename'] = 'Alternate name';
$string['alternatenameerror'] = 'Must be max 512 characters';
$string['alternatename_help'] = 'When filled - alternate name is used instead of a category name in test, to use category name leave this blank';
$string['instructions'] = 'Category instructions';
$string['instructions_help'] = 'When filled - used bellow the category name in test as an additional instructions for category';
$string['display'] = 'Use category in test';
$string['display_help'] = 'Category won\'t be used in test when unchecked ';
$string['points'] = 'Alternate points for cat.';
$string['points_help'] = 'When nonzero, these points will be used instead of default question points';
$string['questions'] = 'No. of questions in category';
$string['questions_help'] = 'How many questions generate in this category';
$string['questionserror'] = 'Must be at least 1, uncheck use category in test to leave out this category';
$string['spaces'] = 'Blank lines left for answer';
$string['spaces_help'] = 'Number of blanks left after each question';
$string['display_points'] = 'Show point summary for category';
$string['display_points_help'] = 'Show total points for category next to name';


$string['categorysaved'] = 'Category settings saved sucesfully';
$string['points_test'] = '({$a}&nbsp;p.)';
$string['no_writing'] = 'No writing!!!';


$string["no_capability"] = "You don't have sufficent permissions";


//cron
$string['nodir'] = 'Directory for tests does not exist';
$string['removed'] = 'Removed: {$a}';
$string['notremoved'] = 'Could not remove: {$a} ';