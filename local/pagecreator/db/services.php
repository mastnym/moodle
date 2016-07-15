<?php

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
 * Web service local plugin template external functions and service definitions.
 *
 * @package    localpagecreator
 * @copyright  2013 Martin Mastny
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We defined the web service functions to install.
$functions = array(
        'local_create_page' => array(
                'classname'   => 'local_page_creator',
                'methodname'  => 'create_page',
                'classpath'   => 'local/pagecreator/externallib.php',
                'description' => 'Returns an URL of a newly created page in moodle',
                'type'        => 'read',
        ),
		'local_delete_page' => array(
				'classname'   => 'local_page_creator',
				'methodname'  => 'delete_page',
				'classpath'   => 'local/pagecreator/externallib.php',
				'description' => 'Deletes a page in Moodle specified by id',
				'type'        => 'read',
		),
		'local_move_resource' => array(
				'classname'   => 'local_page_creator',
				'methodname'  => 'move_resource',
				'classpath'   => 'local/pagecreator/externallib.php',
				'description' => 'Moves resource within course',
				'type'        => 'read',
		),
		'local_update_page' => array(
				'classname'   => 'local_page_creator',
				'methodname'  => 'update_page',
				'classpath'   => 'local/pagecreator/externallib.php',
				'description' => 'Updates a single page',
				'type'        => 'read',
		) ,
		'local_get_plugin_version' => array(
				'classname'   => 'local_page_creator',
				'methodname'  => 'get_version',
				'classpath'   => 'local/pagecreator/externallib.php',
				'description' => 'Returns current version of application',
				'type'        => 'read',
		)
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
        'Page creation' => array(
                'functions' => array ('local_create_page'),
                'restrictedusers' => 0,
                'enabled'=>1,
        		'shortname'=>'page_create'
        ),
		'Page deletion' => array(
				'functions' => array ('local_delete_page'),
				'restrictedusers' => 0,
				'enabled'=>1,
        		'shortname'=>'delete_page'
		),
		'Move resource' => array(
				'functions' => array ('local_move_resource'),
				'restrictedusers' => 0,
				'enabled'=>1,
        		'shortname'=>'move_resource'
		),
		'Page update' => array(
				'functions' => array ('local_update_page'),
				'restrictedusers' => 0,
				'enabled'=>1,
				'shortname'=>'update_page'
		),
		'Get plugin version' => array(
				'functions' => array ('local_get_plugin_version'),
				'restrictedusers' => 0,
				'enabled'=>1,
				'shortname'=>'get_version'
		),
);
