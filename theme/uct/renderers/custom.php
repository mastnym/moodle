<?php

function get_logo($inverted = false) {
    global $SESSION, $OUTPUT;
    if (isset($SESSION->lang) && $SESSION->lang != "cs") {
        $imgname = 'logoUCTen';
    } else {
        $imgname = 'logoUCT';
    }
    return '<img class="uct-logo2" src="' . $OUTPUT->pix_url($imgname, 'theme') . '" alt="" />';
}

function get_elearning_logo($catname) {
    global $OUTPUT;
    $faculties = array('FPBT', 'FCHI', 'FTOP', 'FCHT'
    );
    if (!in_array($catname, $faculties)) {
        $catname = "logo";
    }
    return '<img class="uct-logo" src="' . $OUTPUT->pix_url($catname, 'theme') . '" alt=""/>';
}

function utc_footer() {
    global $SESSION, $OUTPUT;
    
    $html = '
			<div id="course-footer">';
    $html .= $OUTPUT->course_footer();
    $html .= '</div>
        <br style="clear:both" />
        <div>
        <div class="footer-logo">
        	<a class="brand-uct-footer" href="http://www.vscht.cz">';
    
    if (isset($SESSION->lang) && $SESSION->lang != "cs") {
        $imgname = 'logoUCTenNeg';
    } else {
        $imgname = 'logoUCTfull';
    }
    $html .= '<img class="uct-logo-footer" src="' . $OUTPUT->pix_url($imgname, 'theme') .
             '" alt="" />';
    
    $html .= '
               </a>
        </div>
        <div class="footer-info">
        	<p>VŠCHT Praha<p>
        	<p>Technická 5<p>
        	<p>166 28 Praha 6 - Dejvice<p>
        	<p><p>
        	<p>IČO: 6041373<p>
        	<p>DIČ: CZ6041373<p>
        </div>
        <div class="footer-info2">
        	<p>Copyright &copy; VŠCHT 2015<p>
        	<p>V případě problému kontaktujte <a href="mailto:e-learning@vscht.cz">administrátory</a><p>
        	<p><p>
        	<p><p>
        	<p>Aktualizováno 21.1 2015<p>
        	<p>
        		<a href="mailto:e-learning@vscht.cz"><img class="inline-img" src="';
    
    $html .= $OUTPUT->pix_url('mail', 'theme');
    
    $html .= '" alt="" /></a> ';
    
    $html .= '<a href="#" onClick="print()"><img class="inline-img" src="';
    $html .= $OUTPUT->pix_url('printer', 'theme');
    $html .= '" alt="" /></a>


			        	<p>
			        </div>
			        </div>
			        <br style="clear:both" />
			        <div class="additional-footer-info">
			             <p class="helplink">';
    $html .= $OUTPUT->page_doc_link();
    $html .= '</p>';
    
    $html .= $OUTPUT->login_info();
    $html .= $OUTPUT->home_link();
    $html .= $OUTPUT->standard_footer_html();
    
    $html .= ' </div>';
    return $html;
}


