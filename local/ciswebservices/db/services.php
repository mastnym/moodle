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
 * @package    localwstemplate
 * @copyright  2011 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We defined the web service functions to install.
$functions = array(
        'cis_upload_file' => array(
                'classname'   => 'cis_uploader',
                'methodname'  => 'upload_file',
                'classpath'   => 'local/ciswebservices/externallib.php',
                'description' => 'Uploads a file to upload repo',
                'type'        => 'read',
        ),'cis_upload_original' => array(
                'classname'   => 'cis_uploader',
                'methodname'  => 'upload_original',
                'classpath'   => 'local/ciswebservices/externallib.php',
                'description' => 'Uploads a file repositorey,which stores original files',
                'type'        => 'read',
        ),'cis_download_original' => array(
                'classname'   => 'cis_uploader',
                'methodname'  => 'download_original',
                'classpath'   => 'local/ciswebservices/externallib.php',
                'description' => 'Downloads a file from repositorey,which stores original files',
                'type'        => 'read',
        ),'cis_handle_resource' => array(
                'classname'   => 'cis_resource_handler',
                'methodname'  => 'handle_resource',
                'classpath'   => 'local/ciswebservices/externallib.php',
                'description' => 'Handles file/page adding/updating',
                'type'        => 'read',
        ),'cis_add_resource' => array(
                'classname'   => 'cis_resource_handler',
                'methodname'  => 'add_resource',
                'classpath'   => 'local/ciswebservices/externallib.php',
                'description' => 'Handles file/page adding',
                'type'        => 'read',
        ),'cis_update_resource' => array(
                'classname'   => 'cis_resource_handler',
                'methodname'  => 'update_resource',
                'classpath'   => 'local/ciswebservices/externallib.php',
                'description' => 'Handles file/page updates',
                'type'        => 'read',
        ),'cis_download_course_metadata' => array(
                'classname'   => 'cis_uploader',
                'methodname'  => 'download_course_metadata',
                'classpath'   => 'local/ciswebservices/externallib.php',
                'description' => 'Downloads all metadata for a course',
                'type'        => 'read',
        ),'cis_get_test_names' => array(
                'classname'   => 'cis_test_manipulator',
                'methodname'  => 'get_test_names',
                'classpath'   => 'local/ciswebservices/externallib.php',
                'description' => 'Gets names of uppermost categories ie tests in qb',
                'type'        => 'read',
        ),'cis_get_test' => array(
                'classname'   => 'cis_test_manipulator',
                'methodname'  => 'get_test',
                'classpath'   => 'local/ciswebservices/externallib.php',
                'description' => 'Gets the generated test',
                'type'        => 'read',
        ),
		
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
        'Cis-file upload' => array(
                'functions' => array ('cis_upload_file'),
                'restrictedusers' => 0,
                'enabled'=>1,
                'shortname'=>'cis_upload_file',
        ),'Cis- original file upload' => array(
                'functions' => array ('cis_upload_original'),
                'restrictedusers' => 0,
                'enabled'=>1,
                'shortname'=>'cis_upload_original',
        ),'Cis- original file download' => array(
                'functions' => array ('cis_download_original'),
                'restrictedusers' => 0,
                'enabled'=>1,
                'shortname'=>'cis_download_original',
        ),'Cis-resource handler' => array(
				'functions' => array ('cis_handle_resource'),
				'restrictedusers' => 0,
				'enabled'=>1,
				'shortname'=>'cis_handle_resource',
		),
		'Cis add resource handler' => array(
				'functions' => array ('cis_add_resource'),
				'restrictedusers' => 0,
				'enabled'=>1,
				'shortname'=>'cis_add_resource',
		),'Cis update resource handler' => array(
				'functions' => array ('cis_update_resource'),
				'restrictedusers' => 0,
				'enabled'=>1,
				'shortname'=>'cis_update_resource',
		),'Cis course metadata download' => array(
				'functions' => array ('cis_download_course_metadata'),
				'restrictedusers' => 0,
				'enabled'=>1,
				'shortname'=>'cis_download_course_metadata',
		),'Cis test names download' => array(
				'functions' => array ('cis_get_test_names'),
				'restrictedusers' => 0,
				'enabled'=>1,
				'shortname'=>'cis_get_test_names',
		),'Cis test download' => array(
				'functions' => array ('cis_get_test'),
				'restrictedusers' => 0,
				'enabled'=>1,
				'shortname'=>'cis_get_test',
		)
);
