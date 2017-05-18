<?php
class settings_form extends moodleform {

	protected function definition() {
		$mform = $this->_form;

		// Export options.
		$mform->addElement('header', 'General', get_string('choosecategoryheaderandparams','papertest'));

		$contexts = $this->_customdata['contexts'];
		$mform->addElement("questioncategory","category",get_string("choosecategory","papertest"),array("contexts"=>$contexts));
		$this->add_action_buttons(false,get_string("settingssubmit","papertest"));
	}
}

class edit_settings_form extends moodleform {

	protected function definition() {
		$mform = $this->_form;

		// Export options.
		$category=$this->_customdata['cat'];
		$mform->addElement('header', 'General', $category->name);
		$mform->addElement("hidden","id",$category->id);
		$mform->setType("id", PARAM_INT);
		$mform->addElement("hidden","category",$category->id);
		$mform->setType("category", PARAM_INT);

		$mform->addElement('text', 'alternate_name', get_string('alternatename', 'papertest'),array('size'=>'100'));
		$mform->setType("alternate_name",PARAM_TEXT);
		$mform->addRule("alternate_name", get_string('alternatenameerror', 'papertest'), "maxlength",512);
		$mform->addHelpButton('alternate_name', 'alternatename', 'papertest');

		$mform->addElement('editor', 'instructions', get_string('instructions', 'papertest'));
		$mform->setType('instructions', PARAM_RAW);
		$mform->addHelpButton('instructions', 'instructions', 'papertest');

		$mform->addElement("advcheckbox","display",get_string("display","papertest"));
		$mform->setType('display', PARAM_BOOL);
		$mform->addHelpButton('display', 'display', 'papertest');

		$mform->addElement("text","points",get_string("points","papertest"),array('size'=>'4'));
		$mform->setType('points', PARAM_INT);
		//$mform->addRule("points", get_string('pointsserror', 'papertest'), "nonzero",null,'client');
		$mform->addHelpButton('points', 'points', 'papertest');

		$mform->addElement("text","questions",get_string("questions","papertest"),array('size'=>'4'));
		$mform->setType('questions', PARAM_INT);
		$mform->addRule("questions", get_string('questionserror', 'papertest'), "nonzero",null,'client');
		$mform->addHelpButton('questions', 'questions', 'papertest');

		$mform->addElement("text","spaces",get_string("spaces","papertest"),array('size'=>'4'));
		$mform->setType('spaces', PARAM_INT);
		$mform->addHelpButton('spaces', 'spaces', 'papertest');

		$mform->addElement("advcheckbox","display_points",get_string("display_points","papertest"));
		$mform->setType('display_points', PARAM_BOOL);
		$mform->addHelpButton('display_points', 'display_points', 'papertest');

		$mform->addElement('header', 'General', get_string("nextedit","papertest"));
		$contexts = $this->_customdata['contexts'];
		$mform->addElement("questioncategory","cat",get_string("choosecategory","papertest"),array("contexts"=>$contexts));;
		$mform->setDefault("cat",$category->id.",".$category->contextid);

		$this->add_action_buttons(true,get_string("savenext","papertest"));
	}
}