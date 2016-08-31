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
 * Settings for local_shibsync plugin.
 * domain: if shibboleth username uses domain (username@domain.com)
 * and ldap username is without it (username), specify domain parameter
 * 'domain.com' and it gets added.
 *
 * @package    local_shibsync
 * @copyright  2015 Martin Mastny
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if ($hassiteconfig) {

    $settings = new admin_settingpage('local_shibsync', get_string('pluginname', 'local_shibsync'));

    $name = 'domain';
    $title = get_string($name,'local_shibsync');
    $description = get_string($name.'_desc','local_shibsync');
    $setting = new admin_setting_configtext('local_shibsync/'.$name, $title, $description,'');
    $settings ->add($setting);

    $ADMIN->add('localplugins', $settings);

}