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
 * Class that extends auth_plugin_ldap and add several tweaks so it can
 * be used for syncing with LDAP but for shibboleth users
 *
 * It's useful when switching from ldap to shibboleth, but missing some of the
 * attributes in shibboleth. This way, user is after first task run instead of
 * first login.
 *
 * Advantage of this may be, that user is already enrolled in his/her courses after first login.
 * One don't have to wait for night enrollment sync.
 *
 *
 * @package    local_shibsync
 * @copyright  2015 Martin Mastny
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once("{$CFG->dirroot}/auth/ldap/auth.php");

class local_shibsync_sync_plugin extends \auth_plugin_ldap{
    public function __construct(){
        parent::__construct();
        # authtype is shibooleth
        $this->authtype = 'shibboleth';
        $this->errorlogtag = '[AUTH LDAP SHIBBOLETH]';
        $this->domain = '';
        $config= get_config('local_shibsync');
        if (isset($config) && $config -> domain){
            $domain = trim($config->domain);
            if (strpos($domain, "@")!==0){
                $this->domain = "@" . $domain;
            }
            else{
                $this->domain = $domain;
            }
        }
    }

    function append_domain($username){
        $domain = $this-> domain;
        if ($domain && strpos($username, $domain) === false){
            $username = $username . $domain;
        }
        return $username;
    }

    function remove_domain($username){
        return str_replace( $this->domain, '', $username);
    }

    function ldap_bulk_insert($username){
        $username = $this->append_domain($username);
        parent::ldap_bulk_insert($username);
    }

    function get_userinfo_asobj($username){
        $user = parent::get_userinfo_asobj($this->remove_domain($username));
        $user->username = $this->append_domain($user->username);
        return $user;
    }
}