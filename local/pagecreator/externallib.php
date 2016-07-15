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




class local_page_creator extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function create_page_parameters() {
        return new external_function_parameters(
                array('courseid' => new external_value(PARAM_INT, 'Course to edit', VALUE_REQUIRED),
                'section' => new external_value(PARAM_INT, 'Section which material goes to', VALUE_REQUIRED),
                		'myitemid' => new external_value(PARAM_INT,'Item id of images(can be parsed from url)',VALUE_REQUIRED),
                		'sitename'=>new external_value(PARAM_TEXT,'Lecture name',VALUE_REQUIRED),
                		'htmlintro'=>new external_value(PARAM_RAW,'Introduction to material',VALUE_REQUIRED),
                		'maincontent'=>new external_value(PARAM_RAW,'Content of material itself',VALUE_REQUIRED),
                		)
        );
    }
     public static function delete_page_parameters() {
    	return new external_function_parameters(
    			array('url' => new external_value(PARAM_URL, 'URL of a page to be deleted', VALUE_REQUIRED),
    					
    			)
    	);
    }
    
    public static function move_resource_parameters() {
    	return new external_function_parameters(
    			array('courseid' => new external_value(PARAM_INT, 'id of course where a resource should be moved', VALUE_REQUIRED),
    					'sectionid' => new external_value(PARAM_INT, 'id of section to be moved to', VALUE_REQUIRED),
    					'id' => new external_value(PARAM_URL, 'url of resource to be moved', VALUE_REQUIRED),
    					'beforeid' => new external_value(PARAM_URL, 'url of resource before which a resource should be placed', VALUE_REQUIRED),
    					
    			)
    	);
    }
	
    public static function update_page_parameters() {
    	return new external_function_parameters(
    			array('update' => new external_value(PARAM_INT, 'id of a page to edit', VALUE_REQUIRED),
    					'myitemid' => new external_value(PARAM_INT,'Item id of images(can be parsed from url)',VALUE_REQUIRED),
    					'sitename'=>new external_value(PARAM_TEXT,'Lecture name',VALUE_REQUIRED),
    					'htmlintro'=>new external_value(PARAM_RAW,'Introduction to material',VALUE_REQUIRED),
    					'maincontent'=>new external_value(PARAM_RAW,'Content of material itself',VALUE_REQUIRED),
    			)
    	);
    }
     public static function get_version_parameters() {
    	return new external_function_parameters(
    			array(
    			)
    	);
    }
    public static function get_version(){
    	global $CFG;
    	$updateFile = $CFG->dirroot."/cis/inst/version.json";
    	$fh = fopen($updateFile, 'r');
    	$json = fread($fh,  filesize($updateFile));
    	fclose($fh);
    	return $json;
    		
    }
    
    /**
     * Returns url of a newly created material-page submited from MS Word
     * @return string url of material
     */
    public static function create_page($courseid,$section,$myitemid,$sitename,$htmlintro,$maincontent) {
        
    	global $USER,$DB,$CFG;
		
        //Parameter validation
        $params = self::validate_parameters(self::create_page_parameters(),
                array('courseid' => $courseid,'section'=>$section,'myitemid'=>$myitemid,'sitename' => $sitename,'htmlintro' => $htmlintro,'maincontent' => $maincontent));

        //Context validation
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        self::validate_context($context);

        //Capability check
        if (!has_capability('mod/page:addinstance', $context)) {
            throw new moodle_exception('cannotaddthisblocktype','page');
        }
        //additional params
        
        
        
        $sectionreturn=null;
        $cm = null;
        
        
        
        
        $course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
        $module = $DB->get_record('modules', array('name'=>"page"), '*', MUST_EXIST);
		
        $cw = get_course_section($section, $course->id);
        
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
        $data->add              = 'page';
        $data->return           = 0; //must be false if this is an add, go back to course view on cancel
        $data->sr               = $sectionreturn;
        
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
       	$mform_name = $mform->getForm();
       	$fromform = (object)$mform_name->exportValues();
       	//error_log(print_r($fromform,true));
       	//pridat jmeno, obsah do fromform
       	
       	$cm = null;
       	$course = $DB->get_record('course', array('id'=>$fromform->course), '*', MUST_EXIST);
       	$fromform->instance     = '';
       	$fromform->coursemodule = '';
       	
       	$context = get_context_instance(CONTEXT_COURSE, $course->id);
        
       	//specify other options
       	$fromform->course = $course->id;
       	$fromform->modulename = clean_param($fromform->modulename, PARAM_PLUGIN);
       	
       	$fromform->groupingid = 0;
       	$fromform->groupmembersonly = 0;
       	$fromform->name = $sitename;
       	$fromform->completion = COMPLETION_DISABLED;
       	$fromform->completionview = COMPLETION_VIEW_NOT_REQUIRED;
       	$fromform->completiongradeitemnumber = null;
       	$fromform->groupmode = 0;
       	// the type of event to trigger (mod_created/mod_updated)
       	$eventname = '';
       	
       	$newcm = new stdClass();
       	$newcm->course           = $course->id;
       	$newcm->module           = $fromform->module;
       	$newcm->instance         = 0; // not known yet, will be updated later (this is similar to restore code)
       	$newcm->visible          = $fromform->visible;
       	$newcm->groupmode        = $fromform->groupmode;
       	$newcm->groupingid       = $fromform->groupingid;
       	$newcm->groupmembersonly = $fromform->groupmembersonly;
       	$newcm->showdescription = 1;
       	$fromform->coursemodule = add_course_module($newcm);
       	$fromform->intro       = $htmlintro;
       	$fromform->introformat = 1;
       	$fromform->printintro = 1;
       	$fromform->page["text"]= $maincontent;
       	$fromform->page["format"] = 1;
       	$fromform->page["itemid"] = $myitemid;
       	
       	
       	
       	$addinstancefunction    = $fromform->modulename."_add_instance";
       	$returnfromfunc = $addinstancefunction($fromform, $mform);
       	//error_log(print_r($returnfromfunc,true));
       	//error_log(print_r($fromform,true));
       	if (!$returnfromfunc or !is_number($returnfromfunc)) {
       		// undo everything we can
       		error_log(print_r($fromform,true));
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
       	$modcontext = get_context_instance(CONTEXT_MODULE, $fromform->coursemodule);
       	$sectionid = add_mod_to_section($fromform);
       	$DB->set_field('course_modules', 'section', $sectionid, array('id'=>$fromform->coursemodule));
       	
       	set_coursemodule_visible($fromform->coursemodule, $fromform->visible);
       	
       	
       	set_coursemodule_idnumber($fromform->coursemodule, $fromform->cmidnumber);
       	
       	
       	$eventname = 'mod_created';
       	
       	add_to_log($course->id, "course", "add mod",
       			"../mod/$fromform->modulename/view.php?id=$fromform->coursemodule",
       			"$fromform->modulename $fromform->instance");
       	add_to_log($course->id, $fromform->modulename, "add",
       			"view.php?id=$fromform->coursemodule",
       			"$fromform->instance", $fromform->coursemodule);
       	
       	
       	//trigger event
       	$eventdata = new stdClass();
       	$eventdata->modulename = $fromform->modulename;
       	$eventdata->name       = $fromform->name;
       	$eventdata->cmid       = $fromform->coursemodule;
       	$eventdata->courseid   = $course->id;
       	$eventdata->userid     = $USER->id;
       	events_trigger($eventname, $eventdata);
       	
       	rebuild_course_cache($course->id);
       	grade_regrade_final_grades($course->id);
       	plagiarism_save_form_elements($fromform);
       	$page_url="$CFG->wwwroot/mod/$module->name/view.php?id=$fromform->coursemodule";
       	
        $ret=array();
        $ret['url']=$page_url;
        $ret['timemodified']=$fromform->timemodified;
       	//remove extra files from private files
        local_page_creator::deleteFromPrivateFiles($maincontent.$htmlintro);

       	return json_encode($ret);
       	
    }

     public static function delete_page($url) {
    	
    	global $USER,$DB,$CFG;
    	
    	//Parameter validation
    	$params = self::validate_parameters(self::delete_page_parameters(),
    			array('url' => $url));
    	//get id
    	$url_query_string=parse_url($url,PHP_URL_QUERY);
    	parse_str($url_query_string);
      
      
    	$cm     = get_coursemodule_from_id('', $id, 0, true, MUST_EXIST);
    	$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    	//Context validation
    	//$context = get_context_instance(CONTEXT_COURSE, $courseid);
    	$coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
    	$modcontext = get_context_instance(CONTEXT_MODULE, $cm->id);
    	self::validate_context($modcontext);
    	
    	//Capability check
    	if (!has_capability('moodle/course:manageactivities', $modcontext)) {
    		throw new moodle_exception('cannotdeletefile');
    	}
    	
    
    	
    	
	  	$modlib = "$CFG->dirroot/mod/$cm->modname/lib.php";
    	
    	if (file_exists($modlib)) {
    		require_once($modlib);
    	} else {
    		print_error('modulemissingcode', '', '', $modlib);
    	}
    	
     	$deleteinstancefunction = $cm->modname."_delete_instance";
    	if (!$deleteinstancefunction($cm->instance)) {
    		echo $OUTPUT->notification("Could not delete the $cm->modname (instance)");
    	}
    	
    	// remove all module files in case modules forget to do that
    	$fs = get_file_storage();
    	$fs->delete_area_files($modcontext->id);
    	
    	if (!delete_course_module($cm->id)) {
    		echo $OUTPUT->notification("Could not delete the $cm->modname (coursemodule)");
    	}
    	if (!delete_mod_from_section($cm->id, $cm->section)) {
    		echo $OUTPUT->notification("Could not delete the $cm->modname from that section");
    	}
    	
    	
    	$eventdata = new stdClass();
    	$eventdata->modulename = $cm->modname;
    	$eventdata->cmid       = $cm->id;
    	$eventdata->courseid   = $course->id;
    	$eventdata->userid     = $USER->id;
    	events_trigger('mod_deleted', $eventdata);
    	
    	add_to_log($course->id, 'course', "delete mod",
    			"view.php?id=$cm->course",
    			"$cm->modname $cm->instance", $cm->id);
    	
    	rebuild_course_cache($course->id);
    	
    
    	return $cm->id;
    
    }
    public static function move_resource($courseid,$sectionid,$id,$beforeid) {
    	 
    	global $USER,$DB,$CFG;
    	$params = self::validate_parameters(self::move_resource_parameters(),
    			array('courseid' => $courseid,'sectionid' => $sectionid,'id' => $id,'beforeid' => $beforeid));
    	
    	$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    	//get $beforeid from url
    	$before_array=array();
    	$beforeid_query_string=parse_url($beforeid,PHP_URL_QUERY);
    	parse_str($beforeid_query_string,$before_array);
    	$beforeid=$before_array['id'];
    	
    	
    	
    	
    	//get $id from url
    	$id_array=array();
    	$id_query_string=parse_url($id,PHP_URL_QUERY);
    	parse_str($id_query_string,$id_array);
    	$id=$id_array['id'];
    	
    	
    	$cm = get_coursemodule_from_id(null, $id, $course->id, false, MUST_EXIST);
    	$modcontext = context_module::instance($cm->id);
    	require_capability('moodle/course:manageactivities', $modcontext);
    	
    
    	if (!$section = $DB->get_record('course_sections', array('course'=>$course->id, 'section'=>$sectionid))) {
    		throw new moodle_exception('AJAX commands.php: Bad section ID '.$sectionid);
    	}
    	
    	if ($beforeid > 0){
    		$beforemod = get_coursemodule_from_id('', $beforeid, $course->id);
    		$beforemod = $DB->get_record('course_modules', array('id'=>$beforeid));
    	} else {
    		$beforemod = NULL;
    	}
    	
    	if (moveto_module($cm, $section, $beforemod)){
    		rebuild_course_cache($course->id);
    		return $cm->id;
    	}
    	
    
    }
    public static function update_page($update,$myitemid,$sitename,$htmlintro,$maincontent) {
    	 //update->id materialu pro update
    	global $USER,$DB,$CFG;
    	 
    	//Parameter validation
    	$params = self::validate_parameters(self::update_page_parameters(),
    			 array('update' => $update,'myitemid'=>$myitemid,'sitename' => $sitename,'htmlintro' => $htmlintro,'maincontent' => $maincontent));
    	
    	$cm = get_coursemodule_from_id('', $update, 0, false, MUST_EXIST);
    	$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
    	
    	$context = get_context_instance(CONTEXT_MODULE, $cm->id);
    	self::validate_context($context);
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
    	$data->return             = 0;
    	$data->sr                 = 0;
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
    		$draftid_editor = $myitemid;//file_get_submitted_draft_itemid('introeditor');
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
    	
    	if ($data->section && $course->format != 'site') {
    		$heading = new stdClass();
    		$heading->what = $fullmodulename;
    		$heading->in   = $sectionname;
    		$pageheading = get_string('updatingain', 'moodle', $heading);
    	} else {
    		$pageheading = get_string('updatinga', 'moodle', $fullmodulename);
    	}
    	//naplneni formu
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
    	
    	$mform_name = $mform->getForm();
    	$fromform = (object)$mform_name->exportValues();
    	
    	$cm = get_coursemodule_from_id('', $fromform->coursemodule, 0, false, MUST_EXIST);
    	$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
    	$fromform->instance     = $cm->instance;
    	$fromform->coursemodule = $cm->id;
    	
    	if (!empty($fromform->coursemodule)) {
    		$context = get_context_instance(CONTEXT_MODULE, $fromform->coursemodule);
    	} else {
    		$context = get_context_instance(CONTEXT_COURSE, $course->id);
    	}
    	
    	$fromform->course = $course->id;
    	$fromform->modulename = clean_param($fromform->modulename, PARAM_PLUGIN); 
    	
    	$fromform->name   = $sitename;
    	$fromform->introeditor["text"]   = $htmlintro;
    	$fromform->introeditor["format"] = 1;
    	$fromform->introeditor["itemid"] = $myitemid;
    	$fromform->printintro = 1;
    	$fromform->page["text"]= $maincontent;
    	$fromform->page["format"] = 1;
    	$fromform->page["itemid"] = $myitemid;
    	
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
    	plagiarism_save_form_elements($fromform);
    	//remove extra files from private files
    	local_page_creator::deleteFromPrivateFiles($maincontent.$htmlintro);
    	
    	$page_url="$CFG->wwwroot/mod/$module->name/view.php?id=$fromform->coursemodule";
    	$ret=array();
    	$ret['url']=$page_url;
    	$ret['timemodified']=$fromform->timemodified;
    	return json_encode($ret);
    }
   
    
    
    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function create_page_returns() {
        return new external_value(PARAM_TEXT, 'url of a newly created page');
    }

    public static function delete_page_returns() {
    	return new external_value(PARAM_TEXT, 'id o a deleted page');
    }
    public static function move_resource_returns() {
    	return new external_value(PARAM_INT, 'id of moved resource');
    }
    public static function update_page_returns() {
    	return new external_value(PARAM_TEXT, 'url of updated page');
    }
    public static function get_version_returns() {
    	return new external_value(PARAM_TEXT, 'json with info');
    }
    public static function deleteFromPrivateFiles($html){
    	global $USER;
    	$DOM = new DOMDocument;
    	$DOM->loadHTML($html);
    	$items = $DOM->getElementsByTagName('img');
    	if ($items){
    		foreach ($items as $item){
    			$src=$item->getAttribute('src');
    			if (strpos($src, "draftfile.php")){
    				$got=true;
    				break;
    			}
    		}
    		if ($got){
    			$endpos=strrpos($src, "/");
    			preg_match("#draftfile\.php/\d{1,3}/user/draft/\d{1,10}#", $src, $matches);
    	
    			$startpos=strpos($src,$matches[0])+strlen($matches[0]);
    			$folder_to_delete=substr($src ,$startpos ,$endpos-$startpos+1 );
    	
    	
    			//clear private files
    			$fs = get_file_storage();
    			$context=get_context_instance(CONTEXT_USER,$USER->id);
    			$fileinfo = array(
    					'component' => 'user',
    					'filearea' => 'private',
    					'itemid' => 0,               // usually = ID of row in table
    					'contextid' => $context->id, // ID of context
    					'filepath' => $folder_to_delete,           // any path beginning and ending in /
    					'filename' => ''); // any filename
    	
    			// Get folder
    			$file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
    					$fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
    	
    			// Delete it if it exists
    			if ($file) {
    				if ($file->is_directory()) {
    					$pathname = $fileinfo['filepath'];
    					 
    					// delete files in folder
    					$files = $fs->get_directory_files($context->id, 'user', 'private', 0, $pathname, true);
    					foreach ($files as $storedfile) {
    						$storedfile->delete();
    					}
    					$file->delete();
    				}
    			}
    		}
    	
    	}
    }
}
