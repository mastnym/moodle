<?php

defined('MOODLE_INTERNAL') || die();

class tinymce_elearningicons extends editor_tinymce_plugin {
    /** @var array list of buttons defined by this plugin */
    protected $buttons = array('elearningicons');

    protected function update_init_params(array &$params, context $context,
            array $options = null) {

        // Add button after 'spellchecker' in advancedbuttons3.
        $this->add_button_after($params, 3, 'elearningicons', 'spellchecker');

        // Add JS file, which uses default name.
        $this->add_js_plugin($params);
    }
}
