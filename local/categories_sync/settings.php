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
 *
 * @package local
 * @subpackage categories_sync
 * @copyright 1999 onwards Martin Dougiamas and others {@link http://moodle.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
	    
    $settings = new admin_settingpage('local_categories_sync', get_string('pluginname', 'local_categories_sync'));
  
    
    $name = 'enabled';
    $title = get_string($name,'local_categories_sync');
    $description = get_string($name.'_desc','local_categories_sync');  
    $setting = new admin_setting_configcheckbox('local_categories_sync/'.$name, $title, $description, false);
    $settings ->add($setting);

    $name = 'csvfile';
    $title = get_string($name,'local_categories_sync');
    $description = get_string($name.'_desc','local_categories_sync');
    $setting = new admin_setting_configtext('local_categories_sync/'.$name, $title, $description,'');
    $settings ->add($setting);
    
    $name = 'mode';
    $title = get_string($name,'local_categories_sync');
    $description = get_string($name.'_desc','local_categories_sync');
    $choices = array ("u"=>"update","c"=>"create","uc"=>"update/create");
    $setting = new admin_setting_configselect('local_categories_sync/'.$name, $title, $description, "u",$choices);
    $settings ->add($setting); 

    $name = 'usecertificateauth';
    $title = get_string($name,'local_categories_sync');
    $description = get_string($name.'_desc','local_categories_sync');
    $setting = new admin_setting_configcheckbox('local_categories_sync/'.$name, $title, $description,array());
    $settings ->add($setting);
    

    $name = 'certpath';
    $title = get_string($name,'local_categories_sync');
    $description = get_string($name.'_desc','local_categories_sync');  
    $setting = new admin_setting_configtext('local_categories_sync/'.$name, $title, $description,'');
    $settings ->add($setting);   
                           
    $name = 'keypath';
    $title = get_string($name,'local_categories_sync');
    $description = get_string($name.'_desc','local_categories_sync');
    $setting = new admin_setting_configtext('local_categories_sync/'.$name, $title, $description,'');
    $settings ->add($setting);
        
    $ADMIN->add('localplugins', $settings);    
    
}

