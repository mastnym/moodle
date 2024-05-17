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
 * Defines the editing form for the drag-and-drop words into sentences question type.
 *
 * @package   qtype_ddwtos
 * @copyright 2009 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/gapselect/edit_form_base.php');


/**
 * Drag-and-drop words into sentences editing form definition.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_ddwtos_edit_form extends qtype_gapselect_edit_form_base {
    public function qtype() {
        return 'ddwtos';
    }

    protected function data_preprocessing_choice($question, $answer, $key) {
        $question = parent::data_preprocessing_choice($question, $answer, $key);
        // set the answer back as an array together with format
        $question->choices[$key]['answer'] = [];
        $question->choices[$key]['answer']['text'] = $answer->answer;
        $question->choices[$key]['answer']['format'] = $answer->answerformat;
        $options = unserialize_object($answer->feedback);
        $question->choices[$key]['choicegroup'] = $options->draggroup ?? 1;
        $question->choices[$key]['infinite'] = !empty($options->infinite);
        return $question;
    }
    public function definition_after_data(){
        // check each choice and if the 'choices[answer]' is array, then it is submitted via html editor
        // and the rest of the code is not ready for that
        // TODO - find a way to ensure compatibility between textarea and editor in a way of handling output
        foreach ($this->_form->_submitValues['choices'] as $key=>$value){
            if (is_array($value['answer'])){
                $answer_html = $value['answer']['text'];
                // replace the root p tag submitted by tinymce
                $ptagregex = "~<\s*/?\s*p\b\s*[^>]*>~";
                $answer_html = preg_replace($ptagregex, '', $answer_html);
                $this->_form->_submitValues['choices'][$key]['answer'] = $answer_html;
            }
        }
        parent::definition_after_data();
    }

    protected function choice_group($mform) {
        $options = array();
        for ($i = 1; $i <= $this->get_maximum_choice_group_number(); $i += 1) {
            $options[$i] = question_utils::int_to_letter($i);
        }
        $grouparray = array();
        $grouparray[] = $mform->createElement('editor', 'answer',
                get_string('answer', 'qtype_gapselect'), array('size' => 30, 'class' => 'tweakcss ddwtoshtml'));
        $grouparray[] = $mform->createElement('select', 'choicegroup',
                get_string('group', 'qtype_gapselect'), $options);
        $grouparray[] = $mform->createElement('checkbox', 'infinite', get_string('infinite', 'qtype_ddwtos'), '', null,
                array('size' => 1, 'class' => 'tweakcss'));
        return $grouparray;
    }

    protected function extra_slot_validation(array $slots, array $choices): ?string {
        foreach ($slots as $slot) {
            if (count(array_keys($slots, $slot)) > 1) {
                $choice = $choices[$slot - 1];
                if (!isset($choice['infinite']) || $choice['infinite'] != 1) {
                    return get_string('errorlimitedchoice', 'qtype_ddwtos',
                        html_writer::tag('b', $slot));
                }
            }
        }
        return null;
    }
}
