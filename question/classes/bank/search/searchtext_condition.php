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
 * A class for searching questions according to name and text
 *
 * @package    core_question
 * @copyright  2015 Martin Mastny<mastnym@vscht.cz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_question\bank\search;
defined('MOODLE_INTERNAL') || die();

/**
 * Class which defines wheater certain questions are listed based on question
 * text or question name.
 *
 * See also {@link question_bank_view::init_search_conditions()}.
 * @copyright 2015 Martin Mastny<mastnym@vscht.cz>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
class searchtext_condition extends condition {
    /** @var array query param used in where. */
    protected $params;
    /** @var string SQL fragment to add to the where clause. */
    protected $where;
    /** @var string Text to be searched. */
    protected $searchtext;
    /**
     * Constructor
     */
    public function __construct($searchtext) {
        global $DB;
        $this->searchtext = trim($searchtext);
        if ($this->searchtext == ""){
            $this->where = "";
            $this->params = array();
        }else{
            $this->where = $DB -> sql_like("q.questiontext", ':search', false)
                            . ' OR ' .
                           $DB -> sql_like("q.name", ':search1', false);
            $this->params = array('search' => "%$searchtext%", 'search1' => "%$searchtext%");
        }
    }
    /**
     * Return an SQL fragment to be ANDed into the WHERE clause to filter which questions are shown.
     * @return string SQL fragment. Must use named parameters.
     */
    public function where(){
        return $this->where;
    }
    /**
     * Return parameters to be bound to the above WHERE clause fragment.
     * @return array parameter name => value.
     */
    public function params() {
        return $this->params;
    }
    /**
     * Displays the text search field
     *
     * @return string HTML form fragment
     */
    public function display_options() {
        echo \html_writer::start_div('searchtext');
        echo \html_writer::label(get_string('searchtext', 'question'), 'id_searchtext');
        echo \html_writer::tag("input", "", array('class' => 'searchoptions input', 'type' => 'text',
                                 'id' => 'id_searchtext', 'name' => 'srchtext', 'value' => $this->searchtext));
        echo \html_writer::end_div() . "\n";
    }
}
