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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * A two column layout for the UCT theme.
 *
 * @package theme_uct
 * @copyright 2014 Martin Mastny based on work of Bas Brends, www.basbrands.nl
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// comented to move post blocks to right
$left = (!right_to_left()); // To know if to add 'pull-right' and 'desktop-first-column' classes in
                            // the layout for LTR.
                            // print_r($left);
require_once $CFG->dirroot . '/theme/uct/renderers/custom.php';
require_once $CFG->dirroot . '/theme/uct/local/lib.php';

// hide/show sideblock according to saved state in $SESSION->showside
global $SESSION;

if (!isset($SESSION->showside)) {
    $SESSION->showside = "show";
}
// current state of sidebar
$state = $SESSION->showside;
// means we don't want sidebar to be shown
if ($state == "hide") {
    $main_region_span = "span11";
    $image = "show";
    $alttext = get_string("show_sidebar", "theme_uct");
} else {
    $main_region_span = "span9";
    $image = "hide";
    $alttext = get_string("hide_sidebar", "theme_uct");
}

echo $OUTPUT->doctype()?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
<meta http-equiv="X-UA-Compatible" content="IE=Edge" />
<title><?php echo $OUTPUT->page_title(); ?></title>
<link rel="shortcut icon" href="<?php echo $OUTPUT->favicon(); ?>" />
    <?php echo $OUTPUT->standard_head_html()?>
    <meta name="viewport"
 content="width=device-width, initial-scale=1.0">
</head>

<body <?php echo $OUTPUT->body_attributes('two-column'); ?>>

<?php

echo $OUTPUT->standard_top_of_body_html();
$catname = get_faculty_name_for_css();

?>



<header role="banner" class="navbar navbar-fixed-top moodle-has-zindex">
  <nav role="navigation" class="navbar-inner <?php echo $catname; ?>">
   <div class="container-fluid">
    <div class="logos">
     <a class="brand" href="<?php echo $CFG->wwwroot;?>">
            	<?php
            echo get_elearning_logo($catname);
            ?>

               </a> <a class="brand-uct" href="http://www.vscht.cz">
	               <?php
                echo get_logo();
                ?>

               </a>
    </div>
    <a class="btn btn-navbar" data-toggle="collapse"
     data-target=".nav-collapse"> <span class="icon-bar"></span> <span
     class="icon-bar"></span> <span class="icon-bar"></span>
    </a>
            <?php echo $OUTPUT->user_menu(); ?>
            <div class="nav-collapse collapse">
                <?php echo $OUTPUT->custom_menu(); ?>
                <ul class="nav pull-right">
      <li><?php echo $OUTPUT->page_heading_menu(); ?></li>
     </ul>
    </div>
   </div>
  </nav>
 </header>

 <div id="page" class="container-fluid">



  <div id="page-content" class="row-fluid">


   <!--<?php if ($left) { echo ' pull-right'; } ?>   belongs to class attr-->
   <section id="region-main" class="<?php echo $main_region_span ?>">
    <header id="page-header" class="clearfix">
     <div id="page-navbar" class="clearfix">
      <nav class="breadcrumb-nav"><?php echo $OUTPUT->navbar(); ?></nav>
      <div class="breadcrumb-button"><?php echo $OUTPUT->page_heading_button(); ?></div>
     </div>
		        <?php echo $OUTPUT->page_heading(); ?>
		        <div id="course-header">
		            <?php echo $OUTPUT->course_header(); ?>
		        </div>
    </header>
            <?php
            global $SESSION;
            
            echo '<a href="#" title="' . $alttext . '" class="hidden-phone sidebarshow" data-show="' .
                     $image . '"><img alt="' . $alttext . '" class="showhide" src="' .
                     $OUTPUT->pix_url($image, 'theme') . '" alt="" /></a>';
            echo '<hr style="clear:both;"/>';
            
            echo $OUTPUT->course_content_header();
            echo $OUTPUT->main_content();
            echo $OUTPUT->course_content_footer();
            ?>
        </section>

        <?php
        /*
         * $classextra = '';
         * if ($left) {
         * $classextra = ' desktop-first-column';
         * }
         */
        echo $OUTPUT->blocks('side-post', 'span3 ' . $state); // .$classextra);
        
        ?>
    </div>



 </div>
 <footer id="page-footer">
		<?php echo utc_footer(); ?>


    </footer>

    <?php echo $OUTPUT->standard_end_of_body_html()?>
</body>
</html>
