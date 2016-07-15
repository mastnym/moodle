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
 * External Web Service Template
 *
 * @package    localwstemplate
 * @copyright  2011 Moodle Pty Ltd (http://moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("../../config.php");
require_once("lib.php");
require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot. "/course/lib.php");
require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir.'/conditionlib.php');
require_once($CFG->libdir.'/plagiarismlib.php');




class cis_uploader extends external_api {
 
    public static function upload_file_parameters() {
        return new external_function_parameters(
                array('filename' => new external_value(PARAM_TEXT, 'Filename of uploaded file', VALUE_REQUIRED),
                		'item_id' => new external_value(PARAM_INT, 'Item id in repo (if uploading more files to same resource(page etc.), omit this with first call and then call it with the previously returned)', VALUE_OPTIONAL),
                		)
        );
    }
    
    public static function upload_file($filename,$itemid=null){
    	global $CFG,$DB;
    	//validation
    	$params = self::validate_parameters(self::upload_file_parameters(),
    			array('filename' => $filename,'item_id'=>$itemid));
    	$context = get_context_instance(CONTEXT_SYSTEM);
    	self::validate_context($context);
    	
    	
    	// Parameters
    	$action    = 'upload';
    	$repo = $DB->get_record('repository', array('type'=>'upload'));
    	$repo_id   = $repo->id;          
    	$contextid = 1; 			// system_context
    	$env       = 'filepicker';
    	$license   = $CFG->sitedefaultlicense;
    	$author    = ''; //doresit asi podle user id
    	if(!$itemid){
    		$itemid=file_get_unused_draft_itemid();
    	}
    	$maxbytes  =  0;         // Maxbytes
    	$accepted_types  = '*';
    	$saveas_path   = '/';   // save as file path
    	$overwriteexisting =  false;
    	
    		
    	list($context, $course, $cm) = get_context_info_array($contextid);
    		
    	$repooptions = array(
    			'ajax' => true,
    			'mimetypes' => $accepted_types
    	);
    	$repo = repository::get_repository_by_id($repo_id, $contextid, $repooptions);
    		
    	// Check permissions
    	$repo->check_capability();
    		
    	$coursemaxbytes = 0;
    	if (!empty($course)) {
    		$coursemaxbytes = $course->maxbytes;
    	}
    	// Make sure maxbytes passed is within site filesize limits.
    	$maxbytes = get_user_max_upload_file_size($context, $CFG->maxbytes, $coursemaxbytes, $maxbytes);	
    	// Wait as long as it takes for this script to finish
    	set_time_limit(0);
    	$result = $repo->process_upload($filename, $maxbytes, $accepted_types, $saveas_path, $itemid, $license, $author, $overwriteexisting);
    	return (array)$result;
    	
    }
    public static function upload_file_returns() {
    	return new external_single_structure(
    			array(
    					'url'       => new external_value(PARAM_TEXT, 'URL of file'),
    					'id'       => new external_value(PARAM_INT, 'itemId'),
    					'file'       => new external_value(PARAM_TEXT, 'filename'),
    						
    			)
    	);
    }
    
    
    public static function upload_original_parameters() {
    	return new external_function_parameters(
    			array('course' => new external_value(PARAM_INT, 'Related course id', VALUE_REQUIRED),
    					'section' => new external_value(PARAM_INT, 'Related section index', VALUE_REQUIRED),
    					'previousVersion' => new external_value(PARAM_INT, 'previous filename version, 0 if first', VALUE_REQUIRED),
    			)
    	);
    }
    public static function upload_original($course,$section,$previousFilenameVersion){
    	global $CFG,$DB,$USER;
    	//validation
    	$params = self::validate_parameters(self::upload_original_parameters(),
    			array('course' => $course,'section'=>$sectionindex,"previousVersion"=>$previousFilenameVersion));
    	$context = get_context_instance(CONTEXT_COURSE,$course);
    	if (!has_capability('moodle/course:manageactivities', $context)) {
    		throw new moodle_exception('cannotManageActivities','ciswebservices','');
    		//return "Error: cannot manage resources and activities";
    	}
    	self::validate_context($context);
    	if (!$_FILES['file']){
    		throw new moodle_exception('fileNotUploaded','ciswebservices','');
    		//return "Error: file not uploaded";
    	}
    	if ($_FILES['file']['error']!=0){
    		throw new moodle_exception('fileNotUploaded','ciswebservices','');
    		//return "Error: file not uploaded (errorcode:".$_FILES['name']['error'].")";
    	}
    	$filename=$_FILES['file']['name'];
    	$filesize=$_FILES['file']['size'];
    	$file=$_FILES["file"]["tmp_name"];
    	//udelam url na ktery se bude uploadovat
    	$uploadPath="$CFG->dataroot/repository/e-learning-originals/$course/$section/";
    	//zjistim jestli existuje, kdyztak vytvorim
		if (!file_exists($uploadPath)){
			mkdir($uploadPath,0770,true);
			$metadataFile = $uploadPath.".metadata.json";
			$handle = fopen($metadataFile, 'w') or die("can't open file");
			fwrite($handle, json_encode(array()));
			fclose($handle);
		}
		
    	
    	$metadatafilename=$uploadPath.".metadata.json";
    	$metadata_handle=fopen($metadatafilename,"r");
    	if (!flock($metadata_handle, LOCK_SH)) { 
    		error_log(error_get_last());
    		throw new moodle_exception('cannotLockFile');
    	}
    	$content = fread($metadata_handle, filesize($metadatafilename));
    	flock($metadata_handle, LOCK_UN);    // release the lock
    	fclose($metadata_handle);
    	
    	$serverVersion=self::getCurrentVersion($content,$filename);
    	if ($serverVersion->version!=$previousFilenameVersion){
    		//throw new Exception('{"exception":"moodle_exception","errorcode":"versionConflict","message":"ciswebservices\/versionConflict","serverVersion":"'.json_encode($serverVersion).'"}');
    		throw new moodle_exception('serverVersionMismatch',json_encode($serverVersion));
    		//TODO $a="Error: conflict, server version: $serverVersion,client version: $previousFilenameVersion";
    		
    	}
    	
    	list($versioned_filename,$previous_versions)=self::getVersionedFilename($filename,$uploadPath);
    	$previous_version=(count($previous_versions)>0 ? end($previous_versions) : "");
    	 
    	$saved=move_uploaded_file($file,$uploadPath.$versioned_filename);
    	$sha1=sha1_file ($uploadPath.$versioned_filename);
    	
    	
    	$arrayOfmetadata=json_decode($content);
    	
    	$fileinfo = new stdClass();
    	$fileinfo->path=$versioned_filename;
    	$fileinfo->version=end(explode('.', $versioned_filename));
    	$fileinfo->created=time();
    	$fileinfo->author=$USER->firstname." ".$USER->lastname;
    	$fileinfo->previous_version=$previous_version;
    	$fileinfo->sha1=$sha1;
    	$fileinfo->deleted=False;
    	array_push($arrayOfmetadata, $fileinfo);
    	
    	$metadata_handle_write = fopen($metadatafilename,"w+");
    	if (!flock($metadata_handle_write,LOCK_EX)){
    		error_log(error_get_last());
    		throw new moodle_exception("cannotLockFile");
    	}
    	fwrite($metadata_handle_write,json_encode($arrayOfmetadata));
    	flock($metadata_handle_write,LOCK_UN);
    	fclose($metadata_handle_write);
    	return (array)$fileinfo;
    }
    private static function getVersion($filename){
    	$versionNum=explode(".", $filename);
    	return intval(end($versionNum));
    }
    private static function getCurrentVersion($content,$filename){
    	$related = array();
    	foreach(json_decode($content) as  $item){
    		if ( $filename === "" || strpos($item->path, $filename) === 0){
    			array_push($related,array(intval(substr($item->path, strlen($filename)+1)),$item));
    		}
    	} 	
		if (count($related)==0){
			$fake=new stdClass();
			$fake->version=0;
			return $fake;
		}
		//srovnat podle cisla verze
		usort($related, function($a, $b)
		{
			if ($a[0] == $b[0]) {
        		return 0;
    		}
    		return ($a[0] < $b[0]) ? -1 : 1;
		});
		$last=end($related);
		return $last[1];
		
    }
    private static function getVersionedFilename($filename,$folderToScan){
    	$dirContent=scandir($folderToScan);
    	$possibleNames=array_filter($dirContent,function($elem) use($filename){
                     return $filename === "" || strpos($elem, $filename) === 0;
        });
        if (count($possibleNames)==0){
        	return array($filename.".1",array());
        }
        $otherVersions=$possibleNames;
        natsort($otherVersions);
        array_walk($possibleNames, function(&$value, $index){
    		$versionNum=explode(".", $value);
    		$value=intval(end($versionNum));
		});
		sort($possibleNames,1);
        $versionNumber=end($possibleNames);
        return array($filename.".".strval($versionNumber+1),$otherVersions);
    }
    
   
    public static function upload_original_returns() {
    	return new external_single_structure(
    			array(
    					'path'       => new external_value(PARAM_TEXT, 'relative original URL'),
    					'created'       => new external_value(PARAM_INT, 'unix time of creation'),
    					'author'      => new external_value(PARAM_TEXT, 'author'),
    					'previous_version'=> new external_value(PARAM_TEXT, 'prev. version name'),
    					'sha1'       => new external_value(PARAM_RAW, 'sha1 of file contents'),
    					'deleted'         => new external_value(PARAM_BOOL, 'is material in moodle deleted'),
    					'published_material_id'=> new external_value(PARAM_INT, 'id of material in moodle',VALUE_OPTIONAL),
    					'last_published'=> new external_value(PARAM_INT, 'publishing time in moodle', VALUE_OPTIONAL),
    					'last_published_author'=> new external_value(PARAM_TEXT, 'moodle author of material',VALUE_OPTIONAL),
    					
    			)
    	);
    }
    
    
    public static function download_original_parameters() {
    	return new external_function_parameters(
    			array('courseid' => new external_value(PARAM_INT, 'Related course id', VALUE_REQUIRED),
    					'sectionindex' => new external_value(PARAM_INT, 'Related section index', VALUE_REQUIRED),
    					'filename' => new external_value(PARAM_TEXT, 'Related section index', VALUE_REQUIRED),
    			)
    	);
    }
    public static function download_original($courseid,$sectionindex,$filename) {
    	global $CFG,$DB,$USER;
    	$params = self::validate_parameters(self::download_original_parameters(),
    			array('courseid' => $courseid,'sectionindex'=>$sectionindex,'filename'=>$filename,));
    	$context = get_context_instance(CONTEXT_COURSE,$courseid);
    	if (!has_capability('moodle/course:manageactivities', $context)) {
    		throw new moodle_exception('cannotManageActivities','ciswebservices','');
    		//return "Error: cannot manage resources and activities";
    	}
    	self::validate_context($context);
    	
    	$path="$CFG->dataroot/repository/e-learning-originals/$courseid/$sectionindex/";
    	
    	if (!file_exists($path)){
    		echo "[]";
    		exit;
    	}
    	
    	$dirContent=scandir($path);
    	$possibleNames=array_filter($dirContent,function($elem) use($filename){
    		return $filename === "" || strpos($elem, $filename) === 0;
    	});
    	if (count($possibleNames)==0){
    		throw new moodle_exception('fileDoesNotExist','ciswebservices','');
    		//return "No such file ".$filename;
    	}
    	natsort($possibleNames);
    	$lastVersion=end($possibleNames);
    		
    	header('Content-Description: File Transfer');
	    header('Content-Type: application/octet-stream');
	    header('Content-Disposition: attachment; filename=test.docx');
	    header('Content-Transfer-Encoding: binary');
	    header('Expires: 0');
	    header('Cache-Control: must-revalidate');
	    header('Pragma: public');
	    header('Content-Length: ' . filesize($path.$lastVersion));
	    ob_clean();
	    flush();
	    readfile($path.$lastVersion);
	    exit;
    }
    public static function download_original_returns() {
    	return new external_value(PARAM_RAW, 'file or []');
    }
    
    
    
    public static function download_course_metadata_parameters() {
    	return new external_function_parameters(
    			array('courseid' => new external_value(PARAM_INT, 'Course id', VALUE_REQUIRED),
    			)
    	);
    }
    public static function download_course_metadata($courseid) {
    	global $CFG,$DB,$USER;
    	$params = self::validate_parameters(self::download_course_metadata_parameters(),
    			array('courseid' => $courseid));
    	$context = get_context_instance(CONTEXT_COURSE,$courseid);
    	if (!has_capability('moodle/course:manageactivities', $context)) {
    		throw new moodle_exception('cannotManageActivities','ciswebservices','');
    		//return "Error: cannot manage resources and activities";
    	}
    	self::validate_context($context);

    	$ret=array();
    	$path="$CFG->dataroot/repository/e-learning-originals/$courseid/";
    	 
    	if (!file_exists($path)){
    		return json_encode($ret);
    		exit;
    	}
    	 
    	$dirContent=scandir($path);
    	foreach ($dirContent as $content){
    		if (is_dir($path.$content)){
    			$metadata_file=$path.$content."/.metadata.json";
    			if (file_exists($metadata_file)){
    				$sectionid = trim($content, '/');
    				
    				$size = filesize($metadata_file);  
    				$fH = fopen($metadata_file,"r"); 
    				$data = fread($fH,$size);
    				fclose($fH);
    				
    				$ret["#".$sectionid]=json_decode($data);
    			}
    		}
    	}
    	echo json_encode($ret);
    	flush();
    	exit;
    
    	
    }
    public static function download_course_metadata_returns() {
    	return new external_value(PARAM_RAW, 'json with metadata',VALUE_OPTIONAL);
    	
    
    }
    
    
    
}

	

class cis_resource_handler extends external_api {
	
	public static function handle_resource_parameters() {
		return new external_function_parameters(
				array('add' => new external_value(PARAM_TEXT, 'Name of a module(page/resource) or "" if updating', VALUE_REQUIRED),
					'update' => new external_value(PARAM_INT, 'Id of updated module, 0 if adding', VALUE_REQUIRED),
						'name' => new external_value(PARAM_TEXT, 'Module name', VALUE_REQUIRED),
						'itemId' => new external_value(PARAM_INT, 'Item id for uploaded resoursec in main text (or main document)', VALUE_REQUIRED),
						'desc' => new external_value(PARAM_RAW, 'Description of module (can be html)', VALUE_REQUIRED),
						'descItemId' => new external_value(PARAM_INT, 'Item id of previously uploaded pictures for description', VALUE_OPTIONAL),
						'course' => new external_value(PARAM_INT, 'If adding, course to add to', VALUE_OPTIONAL),
						'section' => new external_value(PARAM_INT, 'If adding, section to add to', VALUE_OPTIONAL),
						'mainContent' => new external_value(PARAM_RAW, 'If page, its content', VALUE_OPTIONAL),
				)
		);
	}
	
	public static function handle_resource($add,$update,$name,$itemId,$desc,$descItemId,$course,$section,$mainContent){
		global $USER,$CFG,$DB;
		//validation
		$params = self::validate_parameters(self::handle_resource_parameters(),
				array('add' => $add,'update'=>$update,'name'=>$name,'itemId'=>$itemId,'desc'=>$desc,'descItemId'=>$descItemId,'course'=>$course,'section'=>$section,'mainContent'=>$mainContent));

		if (!empty($add)) {
		    $section = required_param('section', PARAM_INT);
		    $course  = required_param('course', PARAM_INT);
		
		    $course = $DB->get_record('course', array('id'=>$course), '*', MUST_EXIST);
		    $module = $DB->get_record('modules', array('name'=>$add), '*', MUST_EXIST);
		
		    $context = get_context_instance(CONTEXT_COURSE, $course->id);
		    self::validate_context($context);
		    require_capability('moodle/course:manageactivities', $context);
		
		    $cw = get_course_section($section, $course->id);
		
		    if (!course_allowed_module($course, $module->name)) {
		        print_error('moduledisable');
		    }
		
		    $cm = null;
		
		    $data = new stdClass();
		    $data->section          = $section;  
		    $data->visible          = $cw->visible;
		    $data->course           = $course->id;
		    $data->module           = $module->id;
		    $data->modulename       = $module->name;
		    $data->groupmode        = $course->groupmode;
		    $data->groupingid       = $course->defaultgroupingid;
		    $data->groupmembersonly = 0;
		    $data->id               = '';
		    $data->instance         = '';
		    $data->coursemodule     = '';
		    $data->add              = $add;
		
		    if (plugin_supports('mod', $data->modulename, FEATURE_MOD_INTRO, true)) {
		        $draftid_editor = file_get_submitted_draft_itemid('introeditor');
		        file_prepare_draft_area($draftid_editor, null, null, null, null);
		        $data->introeditor = array('text'=>'', 'format'=>FORMAT_HTML, 'itemid'=>$draftid_editor); // TODO: add better default
		    }
		
		    if (plugin_supports('mod', $data->modulename, FEATURE_ADVANCED_GRADING, false)
		            and has_capability('moodle/grade:managegradingforms', $context)) {
		        require_once($CFG->dirroot.'/grade/grading/lib.php');
		
		        $data->_advancedgradingdata['methods'] = grading_manager::available_methods();
		        $areas = grading_manager::available_areas('mod_'.$module->name);
		
		        foreach ($areas as $areaname => $areatitle) {
		            $data->_advancedgradingdata['areas'][$areaname] = array(
		                'title'  => $areatitle,
		                'method' => '',
		            );
		            $formfield = 'advancedgradingmethod_'.$areaname;
		            $data->{$formfield} = '';
		        }
		    }
		
		
		    $sectionname = get_section_name($course, $cw);
		    $fullmodulename = get_string('modulename', $module->name);
		
		} else if (!empty($update)) {
		
		    $cm = get_coursemodule_from_id('', $update, 0, false, MUST_EXIST);
		    $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
		
		 
		    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
		    require_capability('moodle/course:manageactivities', $context);
		
		    $module = $DB->get_record('modules', array('id'=>$cm->module), '*', MUST_EXIST);
		    $data = $data = $DB->get_record($module->name, array('id'=>$cm->instance), '*', MUST_EXIST);
		    $cw = $DB->get_record('course_sections', array('id'=>$cm->section), '*', MUST_EXIST);
		
		    $data->coursemodule       = $cm->id;
		    $data->section            = $cw->section;  // The section number itself - relative!!! (section column in course_sections)
		    $data->visible            = $cm->visible; //??  $cw->visible ? $cm->visible : 0; // section hiding overrides
		    $data->cmidnumber         = $cm->idnumber;          // The cm IDnumber
		    $data->groupmode          = groups_get_activity_groupmode($cm); // locked later if forced
		    $data->groupingid         = $cm->groupingid;
		    $data->groupmembersonly   = $cm->groupmembersonly;
		    $data->course             = $course->id;
		    $data->module             = $module->id;
		    $data->modulename         = $module->name;
		    $data->instance           = $cm->instance;
		    $data->update             = $update;
		    $data->completion         = $cm->completion;
		    $data->completionview     = $cm->completionview;
		    $data->completionexpected = $cm->completionexpected;
		    $data->completionusegrade = is_null($cm->completiongradeitemnumber) ? 0 : 1;
		    $data->showdescription    = $cm->showdescription;
		    if (!empty($CFG->enableavailability)) {
		        $data->availablefrom      = $cm->availablefrom;
		        $data->availableuntil     = $cm->availableuntil;
		        $data->showavailability   = $cm->showavailability;
		    }
		
		    if (plugin_supports('mod', $data->modulename, FEATURE_MOD_INTRO, true)) {
		        $draftid_editor = file_get_submitted_draft_itemid('introeditor');
		        $currentintro = file_prepare_draft_area($draftid_editor, $context->id, 'mod_'.$data->modulename, 'intro', 0, array('subdirs'=>true), $data->intro);
		        $data->introeditor = array('text'=>$currentintro, 'format'=>$data->introformat, 'itemid'=>$draftid_editor);
		    }
		
		    if (plugin_supports('mod', $data->modulename, FEATURE_ADVANCED_GRADING, false)
		            and has_capability('moodle/grade:managegradingforms', $context)) {
		        require_once($CFG->dirroot.'/grade/grading/lib.php');
		        $gradingman = get_grading_manager($context, 'mod_'.$data->modulename);
		        $data->_advancedgradingdata['methods'] = $gradingman->get_available_methods();
		        $areas = $gradingman->get_available_areas();
		
		        foreach ($areas as $areaname => $areatitle) {
		            $gradingman->set_area($areaname);
		            $method = $gradingman->get_active_method();
		            $data->_advancedgradingdata['areas'][$areaname] = array(
		                'title'  => $areatitle,
		                'method' => $method,
		            );
		            $formfield = 'advancedgradingmethod_'.$areaname;
		            $data->{$formfield} = $method;
		        }
		    }
		
		    if ($items = grade_item::fetch_all(array('itemtype'=>'mod', 'itemmodule'=>$data->modulename,
		                                             'iteminstance'=>$data->instance, 'courseid'=>$course->id))) {
		        // add existing outcomes
		        foreach ($items as $item) {
		            if (!empty($item->outcomeid)) {
		                $data->{'outcome_'.$item->outcomeid} = 1;
		            }
		        }
		
		        // set category if present
		        $gradecat = false;
		        foreach ($items as $item) {
		            if ($gradecat === false) {
		                $gradecat = $item->categoryid;
		                continue;
		            }
		            if ($gradecat != $item->categoryid) {
		                //mixed categories
		                $gradecat = false;
		                break;
		            }
		        }
		        if ($gradecat !== false) {
		            // do not set if mixed categories present
		            $data->gradecat = $gradecat;
		        }
		    }
		
		    $sectionname = get_section_name($course, $cw);
		    $fullmodulename = get_string('modulename', $module->name);
		
		} else {
		    print_error('invalidaction');
		}
		
		
		
		$modmoodleform = "$CFG->dirroot/mod/$module->name/mod_form.php";
		if (file_exists($modmoodleform)) {
		    require_once($modmoodleform);
		} else {
		    print_error('noformdesc');
		}
		
		$modlib = "$CFG->dirroot/mod/$module->name/lib.php";
		if (file_exists($modlib)) {
		    include_once($modlib);
		} else {
		    print_error('modulemissingcode', '', '', $modlib);
		}
		
		$mformclassname = 'mod_'.$module->name.'_mod_form';
		$mform = new $mformclassname($data, $cw->section, $cm, $course);
		$mform->set_data($data);
		
		//test uach
		$mform_name = $mform->getForm();
		$fromform = (object)$mform_name->exportValues();
		$fromform->name=$params['name'];
		$fromform->introeditor['text']=$params['desc'];
		if ($params['descItemId']>0){
			$fromform->introeditor['itemid']=$params['descItemId'];
		}
		
		if ($module->name=='page'){
			$fromform->page["text"]= $params['mainContent'];
			$fromform->page["format"] = 1;
			$fromform->page["itemid"] = $params[itemId];
			if (!empty($add)){
				$fromform->showdescription = 1;
			}
		}elseif ($module->name=='resource'){
			$fromform->files=$params[itemId];
		}
		
		
		//TODO
		//Zacatek postu
		//
		
		if (empty($fromform->coursemodule)) {
			// Add
			$cm = null;
			$course = $DB->get_record('course', array('id'=>$fromform->course), '*', MUST_EXIST);
			$fromform->instance     = '';
			$fromform->coursemodule = '';
		} else {
			// Update
			$cm = get_coursemodule_from_id('', $fromform->coursemodule, 0, false, MUST_EXIST);
			$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
			$fromform->instance     = $cm->instance;
			$fromform->coursemodule = $cm->id;
		}
		
		if (!empty($fromform->coursemodule)) {
			$context = get_context_instance(CONTEXT_MODULE, $fromform->coursemodule);
		} else {
			$context = get_context_instance(CONTEXT_COURSE, $course->id);
		}
		
		$fromform->course = $course->id;
		$fromform->modulename = clean_param($fromform->modulename, PARAM_PLUGIN);  // For safety
		
		$addinstancefunction    = $fromform->modulename."_add_instance";
		$updateinstancefunction = $fromform->modulename."_update_instance";
		
		if (!isset($fromform->groupingid)) {
			$fromform->groupingid = 0;
		}
		
		if (!isset($fromform->groupmembersonly)) {
			$fromform->groupmembersonly = 0;
		}
		
		if (!isset($fromform->name)) { //label
			$fromform->name = $fromform->modulename;
		}
		
		if (!isset($fromform->completion)) {
			$fromform->completion = COMPLETION_DISABLED;
		}
		if (!isset($fromform->completionview)) {
			$fromform->completionview = COMPLETION_VIEW_NOT_REQUIRED;
		}
		
		// Convert the 'use grade' checkbox into a grade-item number: 0 if
		// checked, null if not
		if (isset($fromform->completionusegrade) && $fromform->completionusegrade) {
			$fromform->completiongradeitemnumber = 0;
		} else {
			$fromform->completiongradeitemnumber = null;
		}
		
		// the type of event to trigger (mod_created/mod_updated)
		$eventname = '';
		
		if (!empty($fromform->update)) {
		
			if (!empty($course->groupmodeforce) or !isset($fromform->groupmode)) {
				$fromform->groupmode = $cm->groupmode; // keep original
			}
		
			// update course module first
			$cm->groupmode        = $fromform->groupmode;
			$cm->groupingid       = $fromform->groupingid;
			$cm->groupmembersonly = $fromform->groupmembersonly;
		
			$completion = new completion_info($course);
			if ($completion->is_enabled()) {
				// Update completion settings
				$cm->completion                = $fromform->completion;
				$cm->completiongradeitemnumber = $fromform->completiongradeitemnumber;
				$cm->completionview            = $fromform->completionview;
				$cm->completionexpected        = $fromform->completionexpected;
			}
			if (!empty($CFG->enableavailability)) {
				$cm->availablefrom             = $fromform->availablefrom;
				$cm->availableuntil            = $fromform->availableuntil;
				$cm->showavailability          = $fromform->showavailability;
				condition_info::update_cm_from_form($cm,$fromform,true);
			}
			if (isset($fromform->showdescription)) {
				$cm->showdescription = $fromform->showdescription;
			} else {
				$cm->showdescription = 0;
			}
		
			$DB->update_record('course_modules', $cm);
		
			$modcontext = get_context_instance(CONTEXT_MODULE, $fromform->coursemodule);
		
			// update embedded links and save files
			if (plugin_supports('mod', $fromform->modulename, FEATURE_MOD_INTRO, true)) {
				$fromform->intro = file_save_draft_area_files($fromform->introeditor['itemid'], $modcontext->id,
						'mod_'.$fromform->modulename, 'intro', 0,
						array('subdirs'=>true), $fromform->introeditor['text']);
				$fromform->introformat = $fromform->introeditor['format'];
				unset($fromform->introeditor);
			}
		
			if (!$updateinstancefunction($fromform, $mform)) {
				print_error('cannotupdatemod', '', course_get_url($course, $cw->section), $fromform->modulename);
			}
		
			// make sure visibility is set correctly (in particular in calendar)
			if (has_capability('moodle/course:activityvisibility', $modcontext)) {
				set_coursemodule_visible($fromform->coursemodule, $fromform->visible);
			}
		
			if (isset($fromform->cmidnumber)) { //label
				// set cm idnumber - uniqueness is already verified by form validation
				set_coursemodule_idnumber($fromform->coursemodule, $fromform->cmidnumber);
			}
		
			// Now that module is fully updated, also update completion data if
			// required (this will wipe all user completion data and recalculate it)
			if ($completion->is_enabled() && !empty($fromform->completionunlocked)) {
				$completion->reset_all_state($cm);
			}
		
			$eventname = 'mod_updated';
		
			add_to_log($course->id, "course", "update mod",
					"../mod/$fromform->modulename/view.php?id=$fromform->coursemodule",
					"$fromform->modulename $fromform->instance");
			add_to_log($course->id, $fromform->modulename, "update",
					"view.php?id=$fromform->coursemodule",
					"$fromform->instance", $fromform->coursemodule);
		
		} else if (!empty($fromform->add)) {
		
			if (!empty($course->groupmodeforce) or !isset($fromform->groupmode)) {
				$fromform->groupmode = 0; // do not set groupmode
			}
		
			if (!course_allowed_module($course, $fromform->modulename)) {
				print_error('moduledisable', '', '', $fromform->modulename);
			}
		
			// first add course_module record because we need the context
			$newcm = new stdClass();
			$newcm->course           = $course->id;
			$newcm->module           = $fromform->module;
			$newcm->instance         = 0; // not known yet, will be updated later (this is similar to restore code)
			$newcm->visible          = $fromform->visible;
			$newcm->groupmode        = $fromform->groupmode;
			$newcm->groupingid       = $fromform->groupingid;
			$newcm->groupmembersonly = $fromform->groupmembersonly;
			$completion = new completion_info($course);
			if ($completion->is_enabled()) {
				$newcm->completion                = $fromform->completion;
				$newcm->completiongradeitemnumber = $fromform->completiongradeitemnumber;
				$newcm->completionview            = $fromform->completionview;
				$newcm->completionexpected        = $fromform->completionexpected;
			}
			if(!empty($CFG->enableavailability)) {
				$newcm->availablefrom             = $fromform->availablefrom;
				$newcm->availableuntil            = $fromform->availableuntil;
				$newcm->showavailability          = $fromform->showavailability;
			}
			if (isset($fromform->showdescription)) {
				$newcm->showdescription = $fromform->showdescription;
			} else {
				$newcm->showdescription = 0;
			}
		
			if (!$fromform->coursemodule = add_course_module($newcm)) {
				print_error('cannotaddcoursemodule');
			}
		
			if (plugin_supports('mod', $fromform->modulename, FEATURE_MOD_INTRO, true)) {
				$introeditor = $fromform->introeditor;
				unset($fromform->introeditor);
				$fromform->intro       = $introeditor['text'];
				$fromform->introformat = $introeditor['format'];
			}
		
			$returnfromfunc = $addinstancefunction($fromform, $mform);
		
			if (!$returnfromfunc or !is_number($returnfromfunc)) {
				// undo everything we can
				$modcontext = get_context_instance(CONTEXT_MODULE, $fromform->coursemodule);
				delete_context(CONTEXT_MODULE, $fromform->coursemodule);
				$DB->delete_records('course_modules', array('id'=>$fromform->coursemodule));
		
				if (!is_number($returnfromfunc)) {
					print_error('invalidfunction', '', course_get_url($course, $cw->section));
				} else {
					print_error('cannotaddnewmodule', '', course_get_url($course, $cw->section), $fromform->modulename);
				}
			}
		
			$fromform->instance = $returnfromfunc;
		
			$DB->set_field('course_modules', 'instance', $returnfromfunc, array('id'=>$fromform->coursemodule));
		
			// update embedded links and save files
			$modcontext = get_context_instance(CONTEXT_MODULE, $fromform->coursemodule);
			if (!empty($introeditor)) {
				$fromform->intro = file_save_draft_area_files($introeditor['itemid'], $modcontext->id,
						'mod_'.$fromform->modulename, 'intro', 0,
						array('subdirs'=>true), $introeditor['text']);
				$DB->set_field($fromform->modulename, 'intro', $fromform->intro, array('id'=>$fromform->instance));
			}
		
			// course_modules and course_sections each contain a reference
			// to each other, so we have to update one of them twice.
			$sectionid = add_mod_to_section($fromform);
		
			$DB->set_field('course_modules', 'section', $sectionid, array('id'=>$fromform->coursemodule));
		
			// make sure visibility is set correctly (in particular in calendar)
			// note: allow them to set it even without moodle/course:activityvisibility
			set_coursemodule_visible($fromform->coursemodule, $fromform->visible);
		
			if (isset($fromform->cmidnumber)) { //label
				// set cm idnumber - uniqueness is already verified by form validation
				set_coursemodule_idnumber($fromform->coursemodule, $fromform->cmidnumber);
			}
		
			// Set up conditions
			if ($CFG->enableavailability) {
				condition_info::update_cm_from_form((object)array('id'=>$fromform->coursemodule), $fromform, false);
			}
		
			$eventname = 'mod_created';
		
			add_to_log($course->id, "course", "add mod",
					"../mod/$fromform->modulename/view.php?id=$fromform->coursemodule",
					"$fromform->modulename $fromform->instance");
			add_to_log($course->id, $fromform->modulename, "add",
					"view.php?id=$fromform->coursemodule",
					"$fromform->instance", $fromform->coursemodule);
		} else {
			print_error('invaliddata');
		}
		
		// Trigger mod_created/mod_updated event with information about this module.
		$eventdata = new stdClass();
		$eventdata->modulename = $fromform->modulename;
		$eventdata->name       = $fromform->name;
		$eventdata->cmid       = $fromform->coursemodule;
		$eventdata->courseid   = $course->id;
		$eventdata->userid     = $USER->id;
		events_trigger($eventname, $eventdata);
		
		// sync idnumber with grade_item
		if ($grade_item = grade_item::fetch(array('itemtype'=>'mod', 'itemmodule'=>$fromform->modulename,
				'iteminstance'=>$fromform->instance, 'itemnumber'=>0, 'courseid'=>$course->id))) {
				if ($grade_item->idnumber != $fromform->cmidnumber) {
					$grade_item->idnumber = $fromform->cmidnumber;
					$grade_item->update();
				}
		}
		
		$items = grade_item::fetch_all(array('itemtype'=>'mod', 'itemmodule'=>$fromform->modulename,
				'iteminstance'=>$fromform->instance, 'courseid'=>$course->id));
		
		// create parent category if requested and move to correct parent category
		if ($items and isset($fromform->gradecat)) {
			if ($fromform->gradecat == -1) {
				$grade_category = new grade_category();
				$grade_category->courseid = $course->id;
				$grade_category->fullname = $fromform->name;
				$grade_category->insert();
				if ($grade_item) {
					$parent = $grade_item->get_parent_category();
					$grade_category->set_parent($parent->id);
				}
				$fromform->gradecat = $grade_category->id;
			}
			foreach ($items as $itemid=>$unused) {
				$items[$itemid]->set_parent($fromform->gradecat);
				if ($itemid == $grade_item->id) {
					// use updated grade_item
					$grade_item = $items[$itemid];
				}
			}
		}
		
		// add outcomes if requested
		if ($outcomes = grade_outcome::fetch_all_available($course->id)) {
			$grade_items = array();
		
			// Outcome grade_item.itemnumber start at 1000, there is nothing above outcomes
			$max_itemnumber = 999;
			if ($items) {
				foreach($items as $item) {
					if ($item->itemnumber > $max_itemnumber) {
						$max_itemnumber = $item->itemnumber;
					}
				}
			}
		
			foreach($outcomes as $outcome) {
				$elname = 'outcome_'.$outcome->id;
		
				if (property_exists($fromform, $elname) and $fromform->$elname) {
					// so we have a request for new outcome grade item?
					if ($items) {
						foreach($items as $item) {
							if ($item->outcomeid == $outcome->id) {
								//outcome aready exists
								continue 2;
							}
						}
					}
		
					$max_itemnumber++;
		
					$outcome_item = new grade_item();
					$outcome_item->courseid     = $course->id;
					$outcome_item->itemtype     = 'mod';
					$outcome_item->itemmodule   = $fromform->modulename;
					$outcome_item->iteminstance = $fromform->instance;
					$outcome_item->itemnumber   = $max_itemnumber;
					$outcome_item->itemname     = $outcome->fullname;
					$outcome_item->outcomeid    = $outcome->id;
					$outcome_item->gradetype    = GRADE_TYPE_SCALE;
					$outcome_item->scaleid      = $outcome->scaleid;
					$outcome_item->insert();
		
					// move the new outcome into correct category and fix sortorder if needed
					if ($grade_item) {
						$outcome_item->set_parent($grade_item->categoryid);
						$outcome_item->move_after_sortorder($grade_item->sortorder);
		
					} else if (isset($fromform->gradecat)) {
						$outcome_item->set_parent($fromform->gradecat);
					}
				}
			}
		}
		
		if (plugin_supports('mod', $fromform->modulename, FEATURE_ADVANCED_GRADING, false)
				and has_capability('moodle/grade:managegradingforms', $modcontext)) {
			require_once($CFG->dirroot.'/grade/grading/lib.php');
			$gradingman = get_grading_manager($modcontext, 'mod_'.$fromform->modulename);
			$showgradingmanagement = false;
			foreach ($gradingman->get_available_areas() as $areaname => $aretitle) {
				$formfield = 'advancedgradingmethod_'.$areaname;
				if (isset($fromform->{$formfield})) {
					$gradingman->set_area($areaname);
					$methodchanged = $gradingman->set_active_method($fromform->{$formfield});
					if (empty($fromform->{$formfield})) {
						// going back to the simple direct grading is not a reason
						// to open the management screen
						$methodchanged = false;
					}
					$showgradingmanagement = $showgradingmanagement || $methodchanged;
				}
			}
		}
		
		rebuild_course_cache($course->id);
		grade_regrade_final_grades($course->id);
		plagiarism_save_form_elements($fromform); //save plagiarism settings
		
		$page_url="$CFG->wwwroot/mod/$module->name/view.php?id=$fromform->coursemodule";
		$ret=array();
		$ret['url']=$page_url;
		$ret['timemodified']=$fromform->timemodified;
		return json_encode($ret);
		    
		
		 
	}
	
	public static function handle_resource_returns() {
		return new external_value(PARAM_TEXT, 'Json with info');
	}
	
	private static function update_resource_info(&$content,$filename,$id,$time,$author){
		//published_material_id,last_published,last_published_author
		$basic_filename=implode(".",array_slice(explode('.',$filename),0,-1));
		
		$related = array_filter($content, function($item)use($basic_filename){
			return $basic_filename === "" || strpos($item->path, $basic_filename) === 0;
		});
		array_walk($content, function(&$item,$key)use ( $filename,$id,$time,$author){
			if ($item->path==$filename){
				$item->published_material_id=$id;
				$item->last_published=$time;
				$item->last_published_author=$author;
			}
		});
		
	}
	public static function add_resource_parameters() {
		return new external_function_parameters(
				array('add' => new external_value(PARAM_TEXT, 'Name of a module(page/resource) or "" if updating', VALUE_REQUIRED),
						'course' => new external_value(PARAM_INT, 'If adding, course to add to', VALUE_REQUIRED),
						'section' => new external_value(PARAM_INT, 'If adding, section to add to', VALUE_REQUIRED),
						'filename'=>new external_value(PARAM_TEXT, 'name of uploaded filename', VALUE_REQUIRED),
						'name' => new external_value(PARAM_TEXT, 'Module name', VALUE_REQUIRED),
						'desc' => new external_value(PARAM_RAW, 'Description of module (can be html)', VALUE_REQUIRED),
						'itemId' => new external_value(PARAM_INT, 'Item id for uploaded resoursec in main text (or main document)', VALUE_REQUIRED),
						'descItemId' => new external_value(PARAM_INT, 'Item id of previously uploaded pictures for description', VALUE_OPTIONAL),
						'mainContent' => new external_value(PARAM_RAW, 'If page, its content', VALUE_OPTIONAL),
						'metadata'	=> new external_value(PARAM_RAW, 'material metadata to table mdl_cis_page_metadata', VALUE_REQUIRED),
				)
		);
	}
	
	public static function add_resource($add,$course,$section,$filename,$name,$desc,$itemId,$descItemId,$mainContent,$metadata){
		global $USER,$CFG,$DB;
		$params = self::validate_parameters(self::add_resource_parameters(),
				array('add' => $add,'course'=>$course,'section'=>$section,'filename'=>$filename,'name'=>$name,'desc'=>$desc,'itemId'=>$itemId,'desc'=>$desc,'descItemId'=>$descItemId,'mainContent'=>$mainContent,'metadata'=>$metadata));
	
		
		$course = $DB->get_record('course', array('id'=>$course), '*', MUST_EXIST);
		$module = $DB->get_record('modules', array('name'=>$add), '*', MUST_EXIST);
		
		$context = get_context_instance(CONTEXT_COURSE, $course->id);
		self::validate_context($context);
		require_capability('moodle/course:manageactivities', $context);
		
		$cw = get_course_section($section, $course->id);
		
		if (!course_allowed_module($course, $module->name)) {
			print_error('moduledisable');
		}
		
		$cm = null;
		
		$data = new stdClass();
		$data->section          = $section;
		$data->visible          = $cw->visible;
		$data->course           = $course->id;
		$data->module           = $module->id;
		$data->modulename       = $module->name;
		$data->groupmode        = $course->groupmode;
		$data->groupingid       = $course->defaultgroupingid;
		$data->groupmembersonly = 0;
		$data->id               = '';
		$data->instance         = '';
		$data->coursemodule     = '';
		$data->add              = $add;
		
		if (plugin_supports('mod', $data->modulename, FEATURE_MOD_INTRO, true)) {
			$draftid_editor = file_get_submitted_draft_itemid('introeditor');
			file_prepare_draft_area($draftid_editor, null, null, null, null);
			$data->introeditor = array('text'=>'', 'format'=>FORMAT_HTML, 'itemid'=>$draftid_editor); // TODO: add better default
		}
		
		if (plugin_supports('mod', $data->modulename, FEATURE_ADVANCED_GRADING, false)
				and has_capability('moodle/grade:managegradingforms', $context)) {
			require_once($CFG->dirroot.'/grade/grading/lib.php');
		
			$data->_advancedgradingdata['methods'] = grading_manager::available_methods();
			$areas = grading_manager::available_areas('mod_'.$module->name);
		
			foreach ($areas as $areaname => $areatitle) {
				$data->_advancedgradingdata['areas'][$areaname] = array(
						'title'  => $areatitle,
						'method' => '',
				);
				$formfield = 'advancedgradingmethod_'.$areaname;
				$data->{$formfield} = '';
			}
		}
		
		
		$sectionname = get_section_name($course, $cw);
		$fullmodulename = get_string('modulename', $module->name);
		
		
		$modmoodleform = "$CFG->dirroot/mod/$module->name/mod_form.php";
		if (file_exists($modmoodleform)) {
			require_once($modmoodleform);
		} else {
			print_error('noformdesc');
		}
		
		$modlib = "$CFG->dirroot/mod/$module->name/lib.php";
		if (file_exists($modlib)) {
			include_once($modlib);
		} else {
			print_error('modulemissingcode', '', '', $modlib);
		}
		
		$mformclassname = 'mod_'.$module->name.'_mod_form';
		$mform = new $mformclassname($data, $cw->section, $cm, $course);
		$mform->set_data($data);
		//test uach
		$mform_name = $mform->getForm();
		$fromform = (object)$mform_name->exportValues();
		$fromform->name=$params['name'];
		$fromform->introeditor['text']=$params['desc'];
		if ($params['descItemId']>0){
			$fromform->introeditor['itemid']=$params['descItemId'];
		}
		
		if ($module->name=='page'){
			$fromform->page["text"]= $params['mainContent'];
			$fromform->page["format"] = 1;
			$fromform->page["itemid"] = $params['itemId'];
			$fromform->showdescription = 1;
		}elseif ($module->name=='resource'){
			$fromform->files=$params['itemId'];
		}
		
		
		
		$cm = null;
		$course = $DB->get_record('course', array('id'=>$fromform->course), '*', MUST_EXIST);
		$fromform->instance     = '';
		$fromform->coursemodule = '';
		$context = get_context_instance(CONTEXT_COURSE, $course->id);
		
		$fromform->course = $course->id;
		$fromform->modulename = clean_param($fromform->modulename, PARAM_PLUGIN);  // For safety
		
		$addinstancefunction    = $fromform->modulename."_add_instance";
		$updateinstancefunction = $fromform->modulename."_update_instance";
		self::handleForm($fromform);
		if (!empty($course->groupmodeforce) or !isset($fromform->groupmode)) {
			$fromform->groupmode = 0; // do not set groupmode
		}
		
		if (!course_allowed_module($course, $fromform->modulename)) {
			print_error('moduledisable', '', '', $fromform->modulename);
		}
		
		// first add course_module record because we need the context
		$newcm = new stdClass();
		$newcm->course           = $course->id;
		$newcm->module           = $fromform->module;
		$newcm->instance         = 0; // not known yet, will be updated later (this is similar to restore code)
		$newcm->visible          = $fromform->visible;
		$newcm->groupmode        = $fromform->groupmode;
		$newcm->groupingid       = $fromform->groupingid;
		$newcm->groupmembersonly = $fromform->groupmembersonly;
		$completion = new completion_info($course);
		if ($completion->is_enabled()) {
			$newcm->completion                = $fromform->completion;
			$newcm->completiongradeitemnumber = $fromform->completiongradeitemnumber;
			$newcm->completionview            = $fromform->completionview;
			$newcm->completionexpected        = $fromform->completionexpected;
		}
		if(!empty($CFG->enableavailability)) {
			$newcm->availablefrom             = $fromform->availablefrom;
			$newcm->availableuntil            = $fromform->availableuntil;
			$newcm->showavailability          = $fromform->showavailability;
		}
		if (isset($fromform->showdescription)) {
			$newcm->showdescription = $fromform->showdescription;
		} else {
			$newcm->showdescription = 0;
		}
		
		if (!$fromform->coursemodule = add_course_module($newcm)) {
			print_error('cannotaddcoursemodule');
		}
		
		if (plugin_supports('mod', $fromform->modulename, FEATURE_MOD_INTRO, true)) {
			$introeditor = $fromform->introeditor;
			unset($fromform->introeditor);
			$fromform->intro       = $introeditor['text'];
			$fromform->introformat = $introeditor['format'];
		}
		
		$returnfromfunc = $addinstancefunction($fromform, $mform);
		
		if (!$returnfromfunc or !is_number($returnfromfunc)) {
			// undo everything we can
			$modcontext = get_context_instance(CONTEXT_MODULE, $fromform->coursemodule);
			delete_context(CONTEXT_MODULE, $fromform->coursemodule);
			$DB->delete_records('course_modules', array('id'=>$fromform->coursemodule));
		
			if (!is_number($returnfromfunc)) {
				print_error('invalidfunction', '', course_get_url($course, $cw->section));
			} else {
				print_error('cannotaddnewmodule', '', course_get_url($course, $cw->section), $fromform->modulename);
			}
		}
		
		$fromform->instance = $returnfromfunc;
		
		$DB->set_field('course_modules', 'instance', $returnfromfunc, array('id'=>$fromform->coursemodule));
		
		// update embedded links and save files
		$modcontext = get_context_instance(CONTEXT_MODULE, $fromform->coursemodule);
		if (!empty($introeditor)) {
			$fromform->intro = file_save_draft_area_files($introeditor['itemid'], $modcontext->id,
					'mod_'.$fromform->modulename, 'intro', 0,
					array('subdirs'=>true), $introeditor['text']);
			$DB->set_field($fromform->modulename, 'intro', $fromform->intro, array('id'=>$fromform->instance));
		}
		
		// course_modules and course_sections each contain a reference
		// to each other, so we have to update one of them twice.
		$sectionid = add_mod_to_section($fromform);
		
		$DB->set_field('course_modules', 'section', $sectionid, array('id'=>$fromform->coursemodule));
		
		// make sure visibility is set correctly (in particular in calendar)
		// note: allow them to set it even without moodle/course:activityvisibility
		set_coursemodule_visible($fromform->coursemodule, $fromform->visible);
		
		if (isset($fromform->cmidnumber)) { //label
			// set cm idnumber - uniqueness is already verified by form validation
			set_coursemodule_idnumber($fromform->coursemodule, $fromform->cmidnumber);
		}
		
		// Set up conditions
		if ($CFG->enableavailability) {
			condition_info::update_cm_from_form((object)array('id'=>$fromform->coursemodule), $fromform, false);
		}
		
		$eventname = 'mod_created';
		
		add_to_log($course->id, "course", "add mod",
				"../mod/$fromform->modulename/view.php?id=$fromform->coursemodule",
				"$fromform->modulename $fromform->instance");
		add_to_log($course->id, $fromform->modulename, "add",
				"view.php?id=$fromform->coursemodule",
				"$fromform->instance", $fromform->coursemodule);
		
		// Trigger mod_created/mod_updated event with information about this module.
		$eventdata = new stdClass();
		$eventdata->modulename = $fromform->modulename;
		$eventdata->name       = $fromform->name;
		$eventdata->cmid       = $fromform->coursemodule;
		$eventdata->courseid   = $course->id;
		$eventdata->userid     = $USER->id;
		events_trigger($eventname, $eventdata);
		
		// sync idnumber with grade_item
		if ($grade_item = grade_item::fetch(array('itemtype'=>'mod', 'itemmodule'=>$fromform->modulename,
				'iteminstance'=>$fromform->instance, 'itemnumber'=>0, 'courseid'=>$course->id))) {
				if ($grade_item->idnumber != $fromform->cmidnumber) {
					$grade_item->idnumber = $fromform->cmidnumber;
					$grade_item->update();
				}
		}
		
		$items = grade_item::fetch_all(array('itemtype'=>'mod', 'itemmodule'=>$fromform->modulename,
				'iteminstance'=>$fromform->instance, 'courseid'=>$course->id));
		
		// create parent category if requested and move to correct parent category
		if ($items and isset($fromform->gradecat)) {
			if ($fromform->gradecat == -1) {
				$grade_category = new grade_category();
				$grade_category->courseid = $course->id;
				$grade_category->fullname = $fromform->name;
				$grade_category->insert();
				if ($grade_item) {
					$parent = $grade_item->get_parent_category();
					$grade_category->set_parent($parent->id);
				}
				$fromform->gradecat = $grade_category->id;
			}
			foreach ($items as $itemid=>$unused) {
				$items[$itemid]->set_parent($fromform->gradecat);
				if ($itemid == $grade_item->id) {
					// use updated grade_item
					$grade_item = $items[$itemid];
				}
			}
		}
		
		// add outcomes if requested
		if ($outcomes = grade_outcome::fetch_all_available($course->id)) {
			$grade_items = array();
		
			// Outcome grade_item.itemnumber start at 1000, there is nothing above outcomes
			$max_itemnumber = 999;
			if ($items) {
				foreach($items as $item) {
					if ($item->itemnumber > $max_itemnumber) {
						$max_itemnumber = $item->itemnumber;
					}
				}
			}
		
			foreach($outcomes as $outcome) {
				$elname = 'outcome_'.$outcome->id;
		
				if (property_exists($fromform, $elname) and $fromform->$elname) {
					// so we have a request for new outcome grade item?
					if ($items) {
						foreach($items as $item) {
							if ($item->outcomeid == $outcome->id) {
								//outcome aready exists
								continue 2;
							}
						}
					}
		
					$max_itemnumber++;
		
					$outcome_item = new grade_item();
					$outcome_item->courseid     = $course->id;
					$outcome_item->itemtype     = 'mod';
					$outcome_item->itemmodule   = $fromform->modulename;
					$outcome_item->iteminstance = $fromform->instance;
					$outcome_item->itemnumber   = $max_itemnumber;
					$outcome_item->itemname     = $outcome->fullname;
					$outcome_item->outcomeid    = $outcome->id;
					$outcome_item->gradetype    = GRADE_TYPE_SCALE;
					$outcome_item->scaleid      = $outcome->scaleid;
					$outcome_item->insert();
		
					// move the new outcome into correct category and fix sortorder if needed
					if ($grade_item) {
						$outcome_item->set_parent($grade_item->categoryid);
						$outcome_item->move_after_sortorder($grade_item->sortorder);
		
					} else if (isset($fromform->gradecat)) {
						$outcome_item->set_parent($fromform->gradecat);
					}
				}
			}
		}
		
		if (plugin_supports('mod', $fromform->modulename, FEATURE_ADVANCED_GRADING, false)
				and has_capability('moodle/grade:managegradingforms', $modcontext)) {
			require_once($CFG->dirroot.'/grade/grading/lib.php');
			$gradingman = get_grading_manager($modcontext, 'mod_'.$fromform->modulename);
			$showgradingmanagement = false;
			foreach ($gradingman->get_available_areas() as $areaname => $aretitle) {
				$formfield = 'advancedgradingmethod_'.$areaname;
				if (isset($fromform->{$formfield})) {
					$gradingman->set_area($areaname);
					$methodchanged = $gradingman->set_active_method($fromform->{$formfield});
					if (empty($fromform->{$formfield})) {
						// going back to the simple direct grading is not a reason
						// to open the management screen
						$methodchanged = false;
					}
					$showgradingmanagement = $showgradingmanagement || $methodchanged;
				}
			}
		}
		
		rebuild_course_cache($course->id);
		grade_regrade_final_grades($course->id);
		plagiarism_save_form_elements($fromform); //save plagiarism settings
	
		$metadata_filename="$CFG->dataroot/repository/e-learning-originals/$course->id/$section/.metadata.json";
		if (!file_exists($metadata_filename)){
			mkdir(dirname($metadata_filename),0770,true);
			file_put_contents($metadata_filename, "[]");
			//throw new moodle_exception('missingMetadata','ciswebservices','');
			//return "Missing metadata";
		}
		$fp = fopen($metadata_filename, "r");
		if (!flock($fp, LOCK_SH)) {
			error_log(error_get_last());
			throw new moodle_exception('cannotLockFile','ciswebservices','');
		}
		$content=json_decode(fread($fp, filesize($metadata_filename)));
		flock($fp, LOCK_UN);
		fclose($fp);
		
		self::update_resource_info($content,$filename,$fromform->coursemodule,$fromform->timemodified,$USER->firstname." ".$USER->lastname);
		
		$fp = fopen($metadata_filename, "w");
		if (!flock($fp, LOCK_EX)) {
			error_log(error_get_last());
			throw new moodle_exception('cannotLockFile','ciswebservices','');
		}
		fwrite($fp, json_encode($content));
		flock($fp, LOCK_UN);
		fclose($fp);
		
		$page_url="$CFG->wwwroot/mod/$module->name/view.php?id=$fromform->coursemodule";
		$ret=array();
		$ret['id']=$fromform->coursemodule;
		$ret['url']=$page_url;
		$ret['timemodified']=$fromform->timemodified;
		
		//update mdl_cis_page_metadata
		$record = new stdClass();
		$record->module_id = $fromform->coursemodule;
		$record->metadata = $metadata;
		$DB->insert_record('cis_page_metadata',$record,false);
		
		return $ret;
		
	}
		
	
	public static function add_resource_returns() {
		return new external_single_structure(
				array(
						'id'       => new external_value(PARAM_INT, 'id of module'),
						'url'       => new external_value(PARAM_TEXT, 'URL of resource'),
						'timemodified'       => new external_value(PARAM_INT, 'unix time of creation'),
							
				)
		);
	}
	
	
	
	public static function update_resource_parameters() {
		return new external_function_parameters(
				array('update' => new external_value(PARAM_INT, 'Id of updated module', VALUE_REQUIRED),
						'filename' => new external_value(PARAM_TEXT, 'Original filename', VALUE_REQUIRED),
						'name' => new external_value(PARAM_TEXT, 'Module name', VALUE_REQUIRED),
						'desc' => new external_value(PARAM_RAW, 'Description of module (can be html)', VALUE_REQUIRED),
						'itemId' => new external_value(PARAM_INT, 'Item id for uploaded resoursec in main text (or main document)', VALUE_REQUIRED),
						'descItemId' => new external_value(PARAM_INT, 'Item id of previously uploaded pictures for description', VALUE_OPTIONAL),
						'mainContent' => new external_value(PARAM_RAW, 'If page, its content', VALUE_OPTIONAL),
						'metadata'	=> new external_value(PARAM_RAW, 'material metadata to table mdl_cis_page_metadata', VALUE_REQUIRED),
				)
		);
	}
	
	public static function update_resource($update,$filename,$name,$desc,$itemId,$descItemId,$mainContent,$metadata){
		global $USER,$CFG,$DB;
		$params = self::validate_parameters(self::update_resource_parameters(),
				array('update' => $update,'filename'=>$filename,'name'=>$name,'desc'=>$desc,'itemId'=>$itemId,'desc'=>$desc,'descItemId'=>$descItemId,'mainContent'=>$mainContent,'metadata'=>$metadata));
	
		
		$cm = get_coursemodule_from_id('', $update, 0, false, MUST_EXIST);
		$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
		
			
		$context = get_context_instance(CONTEXT_MODULE, $cm->id);
		require_capability('moodle/course:manageactivities', $context);
		
		$module = $DB->get_record('modules', array('id'=>$cm->module), '*', MUST_EXIST);
		$data = $data = $DB->get_record($module->name, array('id'=>$cm->instance), '*', MUST_EXIST);
		$cw = $DB->get_record('course_sections', array('id'=>$cm->section), '*', MUST_EXIST);
		
		$data->coursemodule       = $cm->id;
		$data->section            = $cw->section;  // The section number itself - relative!!! (section column in course_sections)
		$data->visible            = $cm->visible; //??  $cw->visible ? $cm->visible : 0; // section hiding overrides
		$data->cmidnumber         = $cm->idnumber;          // The cm IDnumber
		$data->groupmode          = groups_get_activity_groupmode($cm); // locked later if forced
		$data->groupingid         = $cm->groupingid;
		$data->groupmembersonly   = $cm->groupmembersonly;
		$data->course             = $course->id;
		$data->module             = $module->id;
		$data->modulename         = $module->name;
		$data->instance           = $cm->instance;
		$data->update             = $update;
		$data->completion         = $cm->completion;
		$data->completionview     = $cm->completionview;
		$data->completionexpected = $cm->completionexpected;
		$data->completionusegrade = is_null($cm->completiongradeitemnumber) ? 0 : 1;
		$data->showdescription    = $cm->showdescription;
		if (!empty($CFG->enableavailability)) {
			$data->availablefrom      = $cm->availablefrom;
			$data->availableuntil     = $cm->availableuntil;
			$data->showavailability   = $cm->showavailability;
		}
		
		if (plugin_supports('mod', $data->modulename, FEATURE_MOD_INTRO, true)) {
			$draftid_editor = file_get_submitted_draft_itemid('introeditor');
			$currentintro = file_prepare_draft_area($draftid_editor, $context->id, 'mod_'.$data->modulename, 'intro', 0, array('subdirs'=>true), $data->intro);
			$data->introeditor = array('text'=>$currentintro, 'format'=>$data->introformat, 'itemid'=>$draftid_editor);
		}
		
		if (plugin_supports('mod', $data->modulename, FEATURE_ADVANCED_GRADING, false)
				and has_capability('moodle/grade:managegradingforms', $context)) {
			require_once($CFG->dirroot.'/grade/grading/lib.php');
			$gradingman = get_grading_manager($context, 'mod_'.$data->modulename);
			$data->_advancedgradingdata['methods'] = $gradingman->get_available_methods();
			$areas = $gradingman->get_available_areas();
		
			foreach ($areas as $areaname => $areatitle) {
				$gradingman->set_area($areaname);
				$method = $gradingman->get_active_method();
				$data->_advancedgradingdata['areas'][$areaname] = array(
						'title'  => $areatitle,
						'method' => $method,
				);
				$formfield = 'advancedgradingmethod_'.$areaname;
				$data->{$formfield} = $method;
			}
		}
		
		if ($items = grade_item::fetch_all(array('itemtype'=>'mod', 'itemmodule'=>$data->modulename,
				'iteminstance'=>$data->instance, 'courseid'=>$course->id))) {
				// add existing outcomes
		foreach ($items as $item) {
			if (!empty($item->outcomeid)) {
				$data->{'outcome_'.$item->outcomeid} = 1;
			}
		}
		
		// set category if present
		$gradecat = false;
		foreach ($items as $item) {
			if ($gradecat === false) {
				$gradecat = $item->categoryid;
				continue;
			}
			if ($gradecat != $item->categoryid) {
				//mixed categories
				$gradecat = false;
				break;
			}
		}
		if ($gradecat !== false) {
			// do not set if mixed categories present
			$data->gradecat = $gradecat;
		}
		}
		
		$sectionname = get_section_name($course, $cw);
		$fullmodulename = get_string('modulename', $module->name);
		
		
		$modmoodleform = "$CFG->dirroot/mod/$module->name/mod_form.php";
		if (file_exists($modmoodleform)) {
			require_once($modmoodleform);
		} else {
			print_error('noformdesc');
		}
		
		$modlib = "$CFG->dirroot/mod/$module->name/lib.php";
		if (file_exists($modlib)) {
			include_once($modlib);
		} else {
			print_error('modulemissingcode', '', '', $modlib);
		}
		
		$mformclassname = 'mod_'.$module->name.'_mod_form';
		$mform = new $mformclassname($data, $cw->section, $cm, $course);
		$mform->set_data($data);
		
		//test uach
		$mform_name = $mform->getForm();
		$fromform = (object)$mform_name->exportValues();
		$fromform->name=$params['name'];
		$fromform->introeditor['text']=$params['desc'];
		if ($params['descItemId']>0){
			$fromform->introeditor['itemid']=$params['descItemId'];
		}
		
		if ($module->name=='page'){
			$fromform->page["text"]= $params['mainContent'];
			$fromform->page["format"] = 1;
			$fromform->page["itemid"] = $params['itemId'];
		}elseif ($module->name=='resource'){
			$fromform->files=$params['itemId'];
		}
		
		
		$cm = get_coursemodule_from_id('', $fromform->coursemodule, 0, false, MUST_EXIST);
		$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
		$fromform->instance     = $cm->instance;
		$fromform->coursemodule = $cm->id;
		
		$context = get_context_instance(CONTEXT_MODULE, $fromform->coursemodule);
		
		$fromform->course = $course->id;
		$fromform->modulename = clean_param($fromform->modulename, PARAM_PLUGIN);  // For safety
		
		$addinstancefunction    = $fromform->modulename."_add_instance";
		$updateinstancefunction = $fromform->modulename."_update_instance";
		
		self::handleForm($fromform);
		
		
		if (!empty($course->groupmodeforce) or !isset($fromform->groupmode)) {
			$fromform->groupmode = $cm->groupmode; // keep original
		}
		
		// update course module first
		$cm->groupmode        = $fromform->groupmode;
		$cm->groupingid       = $fromform->groupingid;
		$cm->groupmembersonly = $fromform->groupmembersonly;
		
		$completion = new completion_info($course);
		if ($completion->is_enabled()) {
			// Update completion settings
			$cm->completion                = $fromform->completion;
			$cm->completiongradeitemnumber = $fromform->completiongradeitemnumber;
			$cm->completionview            = $fromform->completionview;
			$cm->completionexpected        = $fromform->completionexpected;
		}
		if (!empty($CFG->enableavailability)) {
			$cm->availablefrom             = $fromform->availablefrom;
			$cm->availableuntil            = $fromform->availableuntil;
			$cm->showavailability          = $fromform->showavailability;
			condition_info::update_cm_from_form($cm,$fromform,true);
		}
		if (isset($fromform->showdescription)) {
			$cm->showdescription = $fromform->showdescription;
		} else {
			$cm->showdescription = 0;
		}
		
		$DB->update_record('course_modules', $cm);
		
		$modcontext = get_context_instance(CONTEXT_MODULE, $fromform->coursemodule);
		
		// update embedded links and save files
		if (plugin_supports('mod', $fromform->modulename, FEATURE_MOD_INTRO, true)) {
			$fromform->intro = file_save_draft_area_files($fromform->introeditor['itemid'], $modcontext->id,
					'mod_'.$fromform->modulename, 'intro', 0,
					array('subdirs'=>true), $fromform->introeditor['text']);
			$fromform->introformat = $fromform->introeditor['format'];
			unset($fromform->introeditor);
		}
		
		if (!$updateinstancefunction($fromform, $mform)) {
			print_error('cannotupdatemod', '', course_get_url($course, $cw->section), $fromform->modulename);
		}
		
		// make sure visibility is set correctly (in particular in calendar)
		if (has_capability('moodle/course:activityvisibility', $modcontext)) {
			set_coursemodule_visible($fromform->coursemodule, $fromform->visible);
		}
		
		if (isset($fromform->cmidnumber)) { //label
			// set cm idnumber - uniqueness is already verified by form validation
			set_coursemodule_idnumber($fromform->coursemodule, $fromform->cmidnumber);
		}
		
		// Now that module is fully updated, also update completion data if
		// required (this will wipe all user completion data and recalculate it)
		if ($completion->is_enabled() && !empty($fromform->completionunlocked)) {
			$completion->reset_all_state($cm);
		}
		
		$eventname = 'mod_updated';
		
		add_to_log($course->id, "course", "update mod",
				"../mod/$fromform->modulename/view.php?id=$fromform->coursemodule",
				"$fromform->modulename $fromform->instance");
		add_to_log($course->id, $fromform->modulename, "update",
				"view.php?id=$fromform->coursemodule",
				"$fromform->instance", $fromform->coursemodule);
		$eventdata = new stdClass();
		$eventdata->modulename = $fromform->modulename;
		$eventdata->name       = $fromform->name;
		$eventdata->cmid       = $fromform->coursemodule;
		$eventdata->courseid   = $course->id;
		$eventdata->userid     = $USER->id;
		events_trigger($eventname, $eventdata);
		
		// sync idnumber with grade_item
		if ($grade_item = grade_item::fetch(array('itemtype'=>'mod', 'itemmodule'=>$fromform->modulename,
				'iteminstance'=>$fromform->instance, 'itemnumber'=>0, 'courseid'=>$course->id))) {
				if ($grade_item->idnumber != $fromform->cmidnumber) {
					$grade_item->idnumber = $fromform->cmidnumber;
					$grade_item->update();
				}
		}
		
		$items = grade_item::fetch_all(array('itemtype'=>'mod', 'itemmodule'=>$fromform->modulename,
				'iteminstance'=>$fromform->instance, 'courseid'=>$course->id));
		
		// create parent category if requested and move to correct parent category
		if ($items and isset($fromform->gradecat)) {
			if ($fromform->gradecat == -1) {
				$grade_category = new grade_category();
				$grade_category->courseid = $course->id;
				$grade_category->fullname = $fromform->name;
				$grade_category->insert();
				if ($grade_item) {
					$parent = $grade_item->get_parent_category();
					$grade_category->set_parent($parent->id);
				}
				$fromform->gradecat = $grade_category->id;
			}
			foreach ($items as $itemid=>$unused) {
				$items[$itemid]->set_parent($fromform->gradecat);
				if ($itemid == $grade_item->id) {
					// use updated grade_item
					$grade_item = $items[$itemid];
				}
			}
		}
		
		// add outcomes if requested
		if ($outcomes = grade_outcome::fetch_all_available($course->id)) {
			$grade_items = array();
		
			// Outcome grade_item.itemnumber start at 1000, there is nothing above outcomes
			$max_itemnumber = 999;
			if ($items) {
				foreach($items as $item) {
					if ($item->itemnumber > $max_itemnumber) {
						$max_itemnumber = $item->itemnumber;
					}
				}
			}
		
			foreach($outcomes as $outcome) {
				$elname = 'outcome_'.$outcome->id;
		
				if (property_exists($fromform, $elname) and $fromform->$elname) {
					// so we have a request for new outcome grade item?
					if ($items) {
						foreach($items as $item) {
							if ($item->outcomeid == $outcome->id) {
								//outcome aready exists
								continue 2;
							}
						}
					}
		
					$max_itemnumber++;
		
					$outcome_item = new grade_item();
					$outcome_item->courseid     = $course->id;
					$outcome_item->itemtype     = 'mod';
					$outcome_item->itemmodule   = $fromform->modulename;
					$outcome_item->iteminstance = $fromform->instance;
					$outcome_item->itemnumber   = $max_itemnumber;
					$outcome_item->itemname     = $outcome->fullname;
					$outcome_item->outcomeid    = $outcome->id;
					$outcome_item->gradetype    = GRADE_TYPE_SCALE;
					$outcome_item->scaleid      = $outcome->scaleid;
					$outcome_item->insert();
		
					// move the new outcome into correct category and fix sortorder if needed
					if ($grade_item) {
						$outcome_item->set_parent($grade_item->categoryid);
						$outcome_item->move_after_sortorder($grade_item->sortorder);
		
					} else if (isset($fromform->gradecat)) {
						$outcome_item->set_parent($fromform->gradecat);
					}
				}
			}
		}
		
		if (plugin_supports('mod', $fromform->modulename, FEATURE_ADVANCED_GRADING, false)
				and has_capability('moodle/grade:managegradingforms', $modcontext)) {
			require_once($CFG->dirroot.'/grade/grading/lib.php');
			$gradingman = get_grading_manager($modcontext, 'mod_'.$fromform->modulename);
			$showgradingmanagement = false;
			foreach ($gradingman->get_available_areas() as $areaname => $aretitle) {
				$formfield = 'advancedgradingmethod_'.$areaname;
				if (isset($fromform->{$formfield})) {
					$gradingman->set_area($areaname);
					$methodchanged = $gradingman->set_active_method($fromform->{$formfield});
					if (empty($fromform->{$formfield})) {
						// going back to the simple direct grading is not a reason
						// to open the management screen
						$methodchanged = false;
					}
					$showgradingmanagement = $showgradingmanagement || $methodchanged;
				}
			}
		}
		
		rebuild_course_cache($course->id);
		grade_regrade_final_grades($course->id);
		plagiarism_save_form_elements($fromform); //save plagiarism settings
		
		
		$metadata_filename="$CFG->dataroot/repository/e-learning-originals/$course->id/$fromform->section/.metadata.json";
		if (!file_exists($metadata_filename)){
			mkdir(dirname($metadata_filename),0770,true);
			file_put_contents($metadata_filename, "[]");
			//throw new moodle_exception('missingMetadata','ciswebservices','');
			//return "Missing metadata";
		}
		$fp = fopen($metadata_filename, "r");
		if (!flock($fp, LOCK_SH)) {
			error_log(error_get_last());
			throw new moodle_exception('cannotLockFile','ciswebservices','');
		}
		$content=json_decode(fread($fp, filesize($metadata_filename)));
		flock($fp, LOCK_UN);
		fclose($fp);
		self::update_resource_info($content,$filename,$fromform->coursemodule,$fromform->timemodified,$USER->firstname." ".$USER->lastname);
		$fp = fopen($metadata_filename, "w");
		if (!flock($fp, LOCK_EX)) {
			error_log(error_get_last());
			throw new moodle_exception('cannotLockFile','ciswebservices','');
		}
		fwrite($fp, json_encode($content));
		flock($fp, LOCK_UN);
		fclose($fp);
		
		$page_url="$CFG->wwwroot/mod/$module->name/view.php?id=$fromform->coursemodule";
		$ret=array();
		$ret['id']=$fromform->coursemodule;
		$ret['url']=$page_url;
		$ret['timemodified']=$fromform->timemodified;
		
		
		//update mdl_cis_page_metadata
		$old_metadata=$DB->get_record_sql('SELECT * FROM {cis_page_metadata} WHERE module_id = ?', array($fromform->coursemodule));
		
		$record = new stdClass();
		$record->id=$old_metadata->id;
		$record->module_id = $fromform->coursemodule;
		$record->metadata = $metadata;
		$DB->update_record('cis_page_metadata',$record);
		
		
		
		return $ret;
		
	}
	
	
	public static function update_resource_returns() {
		return new external_single_structure(
				array(
						'id'       => new external_value(PARAM_INT, 'id of updated resource'),
						'url'       => new external_value(PARAM_TEXT, 'URL of resource'),
						'timemodified'       => new external_value(PARAM_INT, 'unix time of creation'),
							
				)
		);
	}
	
	
	public static function handleForm(&$fromform){
		
		if (!isset($fromform->groupingid)) {
			$fromform->groupingid = 0;
		}
		
		if (!isset($fromform->groupmembersonly)) {
			$fromform->groupmembersonly = 0;
		}
		
		if (!isset($fromform->name)) { //label
			$fromform->name = $fromform->modulename;
		}
		
		if (!isset($fromform->completion)) {
			$fromform->completion = COMPLETION_DISABLED;
		}
		if (!isset($fromform->completionview)) {
			$fromform->completionview = COMPLETION_VIEW_NOT_REQUIRED;
		}
		
		// Convert the 'use grade' checkbox into a grade-item number: 0 if
		// checked, null if not
		if (isset($fromform->completionusegrade) && $fromform->completionusegrade) {
			$fromform->completiongradeitemnumber = 0;
		} else {
			$fromform->completiongradeitemnumber = null;
		}
	}
	
	
}
class cis_test_manipulator extends external_api {
	public static function get_test_names_parameters() {
		return new external_function_parameters(
				array('courseid' => new external_value(PARAM_INT, 'Id of course', VALUE_REQUIRED),
				)
		);
	}
	
	public static function get_test_names($courseid){
		global $CFG,$DB;
		//validation
		$context= context_course::instance($courseid);
		
		if (!has_capability('moodle/question:managecategory', $context)){
			throw new moodle_exception('cannotManageActivities','ciswebservices','');
			
		}
		$select = "parent = 0 AND contextid = ?";
		$categories = $DB->get_records('question_categories', array("parent"=>'0',"contextid"=>$context->id), "sortorder ASC");
		$ret = array();
		foreach ($categories as $cat){
			$arr=array();
			$arr["id"]=$cat->id.",".$context->id;
			$arr["name"]=$cat->name;
			array_push($ret, $arr);
		}
		return $ret;
		
		
		 
	}
	public static function get_test_names_returns() {
		return new external_multiple_structure(
				new external_single_structure(
					array(
							'id'       => new external_value(PARAM_TEXT, 'ID of the topmost categories'),
							'name'       => new external_value(PARAM_TEXT, 'Names of the topmost categories'),
					)
				)
		);
	}
	
	
	
	
	
	public static function get_test_parameters() {
		return new external_function_parameters(
				array('courseid' => new external_value(PARAM_INT, 'Id of course', VALUE_REQUIRED),
					'testid' => new external_value(PARAM_TEXT, 'Moodle question category id xxx,xx', VALUE_REQUIRED),
				)
		);
	}
	
	public static function get_test($courseid,$testid){
		global $CFG,$DB;
		require_once($CFG->dirroot .'/mod/uach/locallib.php');
		require_once($CFG->dirroot .'/mod/uach/question_types/qtypes.php');
		require_once($CFG->dirroot .'/question/editlib.php');
		require_once($CFG->dirroot .'/question/format/xml/format.php');
		require_once($CFG->libdir .'/questionlib.php');
		
		//validation
		$COURSE = $DB->get_record('course', array('id'=>$courseid));
		$context= context_course::instance($courseid);
		$contexts = new question_edit_contexts($context);
		if (!has_capability('moodle/question:managecategory', $context)){
			throw new moodle_exception('cannotManageActivities','ciswebservices','');
		
		}
		list($catid,$catcontext)=explode(',', $testid);
		
		
		//search for all others from same course
		$categories=get_categories_for_contexts($catcontext);
		
		//add atributes to each cat and get selected category object
		$selected=processCategories($categories,$testid);
		
		$docSettings= new DOMDocument( '1.0', 'utf-8');
		$docSettings->load($CFG->dataroot."/settings.xml");
		
		
		getQuestionsToCategories($selected,$categories,$docSettings);
		
		if(!file_exists ( "/tmp/uach/" )){
			mkdir("/tmp/uach/");
		}
		
		$doc = new DocGenerator($selected, $categories);
		$doc-> generateDoc();
		$mhtml=$doc->createMHTML();
		$failed=$doc->failed;
		
		header('Content-type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
		header('Content-Disposition: attachment; filename="test.doc"');
		ob_clean();
		flush();
		echo $mhtml;
		
		exit();
		
	
	
			
	}
	public static function get_test_returns() {
		return null;
	}
	
	
	
	
}



