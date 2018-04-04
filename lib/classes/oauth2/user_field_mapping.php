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
 * Class for loading/storing oauth2 endpoints from the DB.
 *
 * @package    core
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core\oauth2;

defined('MOODLE_INTERNAL') || die();

use core\persistent;

require_once($CFG->dirroot.'/user/profile/lib.php');
/**
 * Class for loading/storing oauth2 user field mappings from the DB
 *
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_field_mapping extends persistent {

    const TABLE = 'oauth2_user_field_mapping';

    /**
     * Return the list of valid internal user fields.
     *
     * @return array
     */
    private static function get_user_fields() {
        return array_merge(\core_user::AUTHSYNCFIELDS, ['picture', 'username']);
    }

    /**
     * Return the list of valid custom profile user fields.
     *
     * @return array array of profile fields
     */
    private static function get_custom_user_fields() {
        return array_map(function($o) {
                return $o->inputname;
        }, profile_get_user_fields_with_data(0));
    }

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return array(
            'issuerid' => array(
                'type' => PARAM_INT
            ),
            'externalfield' => array(
                'type' => PARAM_RAW_TRIMMED,
            ),
            'internalfield' => array(
                'type' => PARAM_ALPHANUMEXT,
                'choices' => array_merge(self::get_user_fields(), self::get_custom_user_fields())
            )
        );
    }

    /**
     * Return the list of internal fields
     * in a format they can be used for choices in a select menu
     * @return array
     */
    public function get_internalfield_list() {
        return array_combine(self::get_user_fields(), self::get_user_fields());
    }

    /**
     * Return the list of profile fields
     * in a format they can be used for choices in a group select menu
     * @return array array of category name with its profile fields
     */
    public function get_customfield_list() {
        $customfields = profile_get_user_fields_with_data_by_category(0);
        $data = array();
        foreach ($customfields as $category) {
            foreach ($category as $field) {
                $categoryname = $field->get_category_name();
                if (!isset($data[$categoryname])) {
                    $data[$categoryname] = array();
                }
                $data[$categoryname][$field->inputname] = $field->field->name;
            }
        }
        return $data;
    }
}
