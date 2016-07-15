<?php
class test_export_form extends moodleform {

    protected function definition() {
        $mform = $this->_form;
		
        //nahrazeni vyberu formatu
        $mform->addElement('hidden','format','xml');
        $mform->setType('format',PARAM_RAW);
        
        
        $defaultcategory = $this->_customdata['defaultcategory'];
        $contexts = $this->_customdata['contexts'];
        // Export options.
        $exporttest=get_string('exporttest','uach').':';
        $mform->addElement('header', 'General', get_string('choosecategory','uach'));
        $mform->addElement('testlist', 'category',$exporttest, compact('contexts'));
        
        $mform->addElement('text', 'copies', get_string('copies', 'uach'),array('size'=>'2'));
        $mform->setDefault('copies', 1);
        $mform->setType('copies',PARAM_INT);
        
        
        $mform->setDefault('category', $defaultcategory);
       	$mform->addElement('checkbox', 'mix_similar', get_string('mix_similar', 'uach'));
       	$mform->addElement('text', 'perc_similar', get_string('perc_similar', 'uach'),array('size'=>'2'));
       	$mform->setType('perc_similar',PARAM_INT);
        
       	$mform->addElement('checkbox', 'show_numbers', get_string('show_numbers', 'uach'));
       	$mform->setDefault('show_numbers',true);
   

       	$radioarray=array();
       	$radioarray[] =& $mform->createElement('radio', 'header', '', get_string('header1','uach'), 1);
       	$radioarray[] =& $mform->createElement('radio', 'header', '', get_string('header2','uach'), 2);
       	$mform->addGroup($radioarray, 'header', get_string('header','uach'), array(' '), false);
       	$mform->setDefault('header', 1);

       	$mform->addElement('checkbox', 'showAnswers', get_string('showAnswers', 'uach'));
       	$mform->addElement('checkbox', 'showCheckSquares', get_string('showCheckSquares', 'uach'));
       	
       	$mform->addElement('hidden', 'openCvVersion', '0');
       	$mform->setType('openCvVersion',PARAM_TEXT);
       	// Set a template for the format select elements
        $renderer = $mform->defaultRenderer();
        $template = "{help} {element}\n";
        $renderer->setGroupElementTemplate($template, 'format');

        // Submit buttons.
        $gen=get_string('generate','uach');
        $this->add_action_buttons(false, $gen);
        
    }
}