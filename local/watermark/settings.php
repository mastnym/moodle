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
 * @subpackage watermark
 * @copyright 1999 onwards Martin Dougiamas and others {@link http://moodle.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
	    
    $settings = new admin_settingpage('local_watermark', get_string('pluginname', 'local_watermark'));
  
    
    $name = 'enabled';
    $title = get_string($name,'local_watermark');
    $description = get_string($name.'_desc','local_watermark');  
    $setting = new admin_setting_configcheckbox('local_watermark/'.$name, $title, $description, false);
    $settings ->add($setting);  
                    

    $name = 'courses';
    $title = get_string($name,'local_watermark');
    $description = get_string($name.'_desc','local_watermark');  
    $setting = new admin_setting_configtext('local_watermark/'.$name, $title, $description,'');
    $settings ->add($setting);   
                            
                    
        
    $ADMIN->add('localplugins', $settings);    
    
}

