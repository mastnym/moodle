<?php

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 20160901;        // The current plugin version (Date: YYYYMMDDXX)

$plugin->requires  = 2016052301;        // Requires this Moodle version (Moodle 2.0 )
$plugin->component = 'local_shibsync';       // Full name of the plugin (used for diagnostics)
$plugin->dependencies = array (
                'auth_ldap'=> 2016052300
)
?>
