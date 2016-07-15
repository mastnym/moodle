<?php
//settings
$string['pluginname'] = 'Sync course categories with external system';

$string['enabled']='Turn plugin on/off';
$string['enabled_desc']='';

$string['csvfile']='Path to CSV file';
$string['csvfile_desc']='Local or remote path(eg.: /tmp/csv.csv or https://domain.com/csv.csv)';

$string['mode']='Mode';
$string['mode_desc']='Just update or create new course categories or both';

$string['usecertificateauth']='Use certificate authentization';
$string['usecertificateauth_desc']='Use certificate when reading csv file from another machine';

$string['certpath']='Absolute path to certificate(PEM)';
$string['certpath_desc']='Specify absolute path to certificate (used when "use certificate auth." is checked)';
$string['keypath']='Absolute path to private key';
$string['keypath_desc']='Specify absolute path to private key (used when "use certificate auth." is checked)';


//lang
$string['noconfig']='Cannot get config';
$string['pluginnotenabled']='Plugin is not enabled in settings';
$string['nocsv']='Cannot load CSV file, check settings';
$string['nocategories']='There are no categories in csv';
$string['csvbroken']='CSV file is broken';
$string['notopmost']='There should be at least one category which parent id is #TOP# (topmost category)';
$string['invalidparent']='Parent category does not exist for category {$a}';
$string['moodleexception']='Moodle exception was raised: {$a}';
$string['categorynotinmoodle']='Category with id {$a} is not in Moodle';
$string['invalidcertpath']='Cannot open certificate file';
$string['invalidkeypath']='Cannot open key file';
$string['csvmustbehttps']='Use https when requesting csv';
$string['httpnocsv']='Cannot load CSV file: return code {$a}';
$string['categoryexists']='Failed to create, category with same idnumber already exists ({$a})';
$string['noneedtoupdate']='Dont\'t need to update {$a}';
$string['processing']='Processing category - {$a}';

?>
