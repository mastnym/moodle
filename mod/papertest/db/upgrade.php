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
 * This file keeps track of upgrades to the papertest module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 * @package    mod_papertest
 * @copyright  2011 Martin Mastny
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute papertest upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_papertest_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

 	if ($oldversion < 2010032201) {

        // Define table papertest_settings to be created.
        $table = new xmldb_table('papertest_settings');

        // Adding fields to table papertest_settings.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('category', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('alternate_name', XMLDB_TYPE_CHAR, '512', null, null, null, null);
        $table->add_field('instructions', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('display', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('points', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('questions', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '1');
        
        // Adding keys to table papertest_settings.table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        // Conditionally launch create table for papertest_settings.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Papertest savepoint reached.
        upgrade_mod_savepoint(true, 2010032201, 'papertest');
    }
    if ($oldversion < 2010032202) {
    
    	// Define field spaces to be added to papertest_settings.
    	$table = new xmldb_table('papertest_settings');
    	$field = new xmldb_field('spaces', XMLDB_TYPE_INTEGER, '3', null, null, null, '0', 'questions');
    	$field2 = new xmldb_field('display_points', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'spaces');
    	// Conditionally launch add field spaces.
    	if (!$dbman->field_exists($table,$field)) {
    		$dbman->add_field($table, $field);
    	}
    	if (!$dbman->field_exists($table,$field2)) {
    		$dbman->add_field($table, $field2);
    	}
    	// Papertest savepoint reached.
    	upgrade_mod_savepoint(true, 2010032202, 'papertest');
    }
    
    
    return true;
}
