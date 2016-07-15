<?php
class test_export_form extends moodleform {

    protected function definition() {
        $mform = $this->_form;
        
        // Export options.
        $mform->addElement('header', 'General', get_string('choosecategoryheader','papertest'));
        
        $contexts = $this->_customdata['contexts'];
        $mform->addElement("questioncategory","category",get_string("choosecategory","papertest"),array("contexts"=>$contexts));
        
        
        $mform->addElement('text', 'copies', get_string('copies', 'papertest'),array('size'=>'2'));
        $mform->setDefault('copies', 1);
        $mform->setType('copies',PARAM_INT);
        $mform->addRule("copies", get_string("copies_validation","papertest"), "nonzero",null,'client');
        
        $mform->addElement('checkbox', 'show_points', get_string('show_points', 'papertest'));
        $mform->setDefault('show_points',true);
        
        $mform->addElement('checkbox', 'show_numbers', get_string('show_numbers', 'papertest'));
        $mform->setDefault('show_numbers',true);
       	
        
       	$mform->addElement('checkbox', 'showAnswers', get_string('showanswers', 'papertest'));
       	
       	$mform->addElement('checkbox', 'all', get_string('all', 'papertest'));
       	
       	$mform->addElement('hidden', 'openCvVersion', 0);
       	$mform->setType('openCvVersion',PARAM_INT);
       	// Set a template for the format select elements
        $renderer = $mform->defaultRenderer();
        $template = "{help} {element}\n";
        $renderer->setGroupElementTemplate($template, 'format');

        // Submit buttons.
        $gen=get_string('generate','papertest');
        $this->add_action_buttons(false, $gen); 
    }
}