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
 * Sync course categories from external system's exported CSV 
 * 
 *

 *
 * @package local
 * @subpackage categories_sync
 * @copyright 1999 onwards Martin Dougiamas and others {@link http://moodle.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


function sync(){
	global $CFG;
	require_once $CFG->dirroot.'/lib/coursecatlib.php';
	//to config

	
	$config= get_config('local_categories_sync');
	//print_r($config);
	//no config
	if (!isset($config)){
		mtrace( get_string('noconfig', 'local_categories_sync'));
		die;
	}
	//turn on plugin
	if ($config->enabled != 1){
		mtrace( get_string('pluginnotenabled', 'local_categories_sync'));
		die;
	}
	
	$csv_file=$config->csvfile;
	$categories=array();
	//get categories or die
	if (!$config->usecertificateauth){//local or unprotected remote file
		$csv_handle = fopen($csv_file, "r");
		if (!$csv_handle){
			mtrace( get_string('nocsv', 'local_categories_sync'));
			die;
		}
		if (flock($csv_handle, LOCK_EX)) {
			while (($data = fgetcsv($csv_handle)) !== FALSE) {
				$data=array_map('trim', $data);
				array_push($categories, $data);
			}
			flock($csv_handle, LOCK_UN);
		}else{
			mtrace( get_string('nocsv', 'local_categories_sync'));
			die;
		}
	}else{
		if (!file_exists($config->certpath)){
			mtrace( get_string('invalidcertpath', 'local_categories_sync'));
			die;
		}
		if (!file_exists($config->keypath)){
			mtrace( get_string('invalidkeypath', 'local_categories_sync'));
			die;
		}
		if (!startsWith(strtolower($csv_file), "https://")){
			mtrace( get_string('csvmustbehttps', 'local_categories_sync'));
			die;
		}
		//connect to server
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $csv_file);
		//uncomment for debug
		//curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($ch, CURLOPT_SSLCERT, $config->certpath);
		//curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');-
		curl_setopt($ch, CURLOPT_SSLKEY, $config->keypath);
		$resp = curl_exec($ch);
		$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if ($http_status!=200){
			mtrace( get_string('httpnocsv', 'local_categories_sync',$http_status));
			die;
		}
		//create categories
		$rows = str_getcsv($resp, "\n");
		if (count($rows)<1){
			mtrace( get_string('nocategories', 'local_categories_sync'));
			die;
		} 
		foreach($rows as $row){
			$row=str_getcsv($row);
			$row=array_map('trim', $row);
			array_push($categories, array_slice($row,0,3));
		} 
	}
	
	
	//check file
	if (count($categories)==0){
		mtrace( get_string('nocategories', 'local_categories_sync'));
		die;
	}
	
	if (!checkCategories($categories)){
		mtrace( get_string('csvbroken', 'local_categories_sync'));
		die;
	}
	
	$mode=$config->mode;
	$topmost=getTopmostCategories($categories);
	if (!$topmost){
		mtrace( get_string('notopmost', 'local_categories_sync'));
		die;
	}
	sync_categories($topmost,$mode,$categories);

	
}


function sync_categories($top,$mode,$categories){
	global $DB;
	foreach ($top as $cat){
		mtrace(get_string('processing', 'local_categories_sync',$cat[0]." - ".$cat[1]));
		$existing = $DB->get_record('course_categories', array('idnumber' => $cat[0]),"*",IGNORE_MISSING);
		if (strpos($mode,'c') !== false) {
			if (!$existing){
				createCategory($cat);
			}
			else{
				mtrace(get_string('categoryexists', 'local_categories_sync',$existing->idnumber." - ".$existing->name));
			}
			
		}
		if (strpos($mode,'u') !== false) {
			if ($existing){
				updateCategory($cat);
			}else{
				mtrace(get_string('noneedtoupdate', 'local_categories_sync',$cat[1]));
			}
		}
		$children=getCategoriesByParent($categories, $cat[0]);
		sync_categories($children,$mode,$categories);
	}
}


function createCategory($cat){
	global $DB;
	if ($cat[2]=="#TOP#"){
		$parent=0;
	}else{
		$parent= $DB->get_record('course_categories', array('idnumber' => $cat[2]),"id",IGNORE_MISSING);
		if ($parent===false){
			mtrace( get_string('invalidparent', 'local_categories_sync',$cat[1]));
			return;
		}
		$parent=$parent->id;
	}		
	$newcategory = new stdClass();
	$newcategory->id=0;
	$newcategory->name = $cat[1];
	$newcategory->idnumber = $cat[0];
	$newcategory->parent = $parent;

	try{
		coursecat::create($newcategory);
		mtrace ("created ".$cat[1]."\n");
	}catch (Exception $e){
		if ($e->errorcode!='categoryidnumbertaken'){
			mtrace( get_string('moodleexception', 'local_categories_sync',$e->getMessage()));
		}
	}
}

function updateCategory($cat){
	global $DB;
	$category=$DB->get_record('course_categories', array('idnumber' => $cat[0]),'*',IGNORE_MISSING);
	if ($category===false){
		mtrace( get_string('categorynotinmoodle', 'local_categories_sync',$cat[0]));
		return;
	}
	
	if ($cat[2]=="#TOP#"){
		$parent=0;
	}else{
		$parent= $DB->get_record('course_categories', array('idnumber' => $cat[2]),"id",IGNORE_MISSING);
		if ($parent===false){
			mtrace( get_string('invalidparent', 'local_categories_sync',$cat[1]));
			return;
		}
		$parent=$parent->id;
	}		
	$data=new stdClass();
	$data->parent=$parent;
	$data->idnumber=$cat[0];
	$data->name=$cat[1];
	try{
		$newcategory = coursecat::get($category->id,MUST_EXIST,true);
		$newcategory->update($data);
		mtrace ("updating ".$cat[1]."\n");
	}catch (Exception $e){
		mtrace( get_string('moodleexception', 'local_categories_sync',$e->getMessage()));
	}
			
	
}
function need_to_update($cat,$existing){
	if ($existing===false){
		return false;
	}
	//same ,dont neeed to update
	if ($cat[0]==$existing->idnumber && $cat[1] == $existing->name && $cat[2] == $existing->parent){
		return false;
	}
	return true;
}

function getTopmostCategories($categories){
	return getCategoriesByParent($categories, "#TOP#");
	/*
	//get all parents first
	$parent_ids=array();
	foreach ($categories as $cat){
		if (!in_array($cat[2], $parent_ids)){
			array_push($parent_ids, $cat);
		}
	}
	$top=array_filter($categories, function($cat) use($parent_ids){
                     return !in_array($cat[0], $parent_ids);
                 });
    return $top;*/
}

function getCategoriesByParent($categories,$parent_id){
	$ret=array();
	foreach ($categories as $cat){
		if ($cat[2]==$parent_id){
			array_push($ret, $cat);
		}
	}
	return $ret;
}

function checkCategories($c){
	foreach ($c as $cat){
		//must have 3 items
		if (count($cat)<3){
			return false;
		}
		
	}
	return true;
}
function startsWith($haystack, $needle)
{
	return $needle === "" || strpos($haystack, $needle) === 0;
}

