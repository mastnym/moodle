<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/question/export_form.php');
require($CFG->dirroot .'/question/format/xml/format.php');
include 'saxon_transform.php';
include 'test_export_form.php';
ini_set('memory_limit', '-1');
$Start=getTime();
include 'parseWord.php';

$mid = optional_param('moduleid', 0, PARAM_INT);


list($thispageurl, $contexts, $cmid, $cm, $module, $pagevars) = question_edit_setup('export', '/mod/uach/test.php');


list($catid, $catcontext) = explode(',', $pagevars['cat']);
$exporttest=get_string("exporttest",'uach');
$category = $DB->get_record('question_categories', array("id" => $catid, 'contextid' => $catcontext), '*', MUST_EXIST);
/// Header
$PAGE->set_url($thispageurl);
$PAGE->set_title($exporttest);
$PAGE->set_heading($COURSE->fullname);

echo $OUTPUT->header();

$export_form = new test_export_form($thispageurl, array('format'=>'xml','contexts'=>$contexts->having_one_edit_tab_cap('export'), 'defaultcategory'=>$catid));

//debug
//$from_form=(object) array( "format" => "xml", "category" => "6,14", "submitbutton" => "Generuj" );


if (($from_form= $export_form->get_data()) && canGenerate()) {//
        $thiscontext = $contexts->lowest();

        //what user chooses
        $current_cat=$category;
        $copyOfAllCategories=$CFG->exportCategories;

        $xmls=array();//array of xmls of each category

        $top_cats=processChosenCategory($current_cat, $CFG->exportCategories);

        foreach($CFG->exportCategories as $category){
                $classname = 'qformat_' . $from_form->format;
                $qformat = new $classname();
                $idc=   explode(",",$category->id);
                $category->id=$idc[0];
                $qformat->setCategory($category);//stdClass id=6 name=Test1 contextid=14
                $qformat->setContexts($contexts->having_one_edit_tab_cap('export'));
                $qformat->setCourse($COURSE);//stdClass id=2 category=1
                //export with categories, context
                $withcategories = 'withcategories';
                $withcontexts = 'withcontexts';
                $qformat->setCattofile(true);
                $qformat->setContexttofile(true);

                try{
                        $xml = $qformat->exportprocess(true);
                        $xml= str_replace("&reg;","&rarr;",$xml);
                        $xml=escapeEntities($xml);
                        array_push($xmls,$xml);

                }
                catch (moodle_exception $e){
                        //print_r($e);

                }
                //crate xml file from all categories
                $arrayOfCategories=array();
                //$Start1=getTime();

                //$End1=getTime();
                //echo "Cas parsovani xml = ".number_format(($End1 - $Start1),2)." secs";
                //print_r( $arrayOfCategories);

        }
        //prepare temp
        if(!file_exists ( "/tmp/uach/" )){
                mkdir("/tmp/uach/");
        }

        $arrayOfCategories=array();
        foreach($xmls as $xml){
        	$arrayOfCategories=extractQuestions($arrayOfCategories,$xml,$COURSE->shortname);
        	
        	
        	}
        	
        	if (isset($from_form->copies) && is_numeric($from_form->copies) && intval($from_form->copies)<30){
        		$copies=intval($from_form->copies);
        	}
        	else{
        		$copies=1;
        	
        	}
        	$showAnswers=0;
        	if (isset($from_form->showAnswers)){
        		$showAnswers=1;
        	}
        	$showCheckSquares=0;
        	if (isset($from_form->showCheckSquares)){
        		$showCheckSquares=1;
        	}
        	
        	
        	$docxes=array();
        	for ($i = 0; $i < $copies; $i++) {
        	
        		if (isset($from_form->mix_similar)){
        			if (isset($from_form->perc_similar) && is_numeric($from_form->perc_similar)){
        				$arrayOfCategories=mixItUp($arrayOfCategories,$top_cats,$current_cat,true,$from_form->perc_similar);
        			}
        			else{
        				$arrayOfCategories=mixItUp($arrayOfCategories,$top_cats,$current_cat,true,60);
        			}
        		}else{
        	
        			$arrayOfCategories=mixItUp($arrayOfCategories,$top_cats,$current_cat,false,false);
        		}
        	
        		//create an input XML
        		$tempxml=tempnam("/tmp/uach", "xml");
        		createXML($arrayOfCategories,$tempxml,$copyOfAllCategories);
        		//perform transformation
        	
        		$temporary_dir=tmpdir("/tmp/uach","tes");
        	
        		copydir("word_root",$temporary_dir);
        		unlink($temporary_dir."/word/media/.dummy");
        		if (isset($from_form->show_numbers)){
        			$show_numbers=1;
        		}else{
        			$show_numbers=0;
        		}
        		$headerNumber=$from_form->header;
        		$openCV=$from_form->openCvVersion;
        		createSourceDOCXML($CFG->dataroot.'/settings.xml','transformation_schema.xsl',$temporary_dir."/word",$temporary_dir."/word/media/",$COURSE->shortname,$current_cat->id.",".$current_cat->contextid,str_replace('\\', '/', $tempxml),implode("#",array_keys($top_cats)),$show_numbers,$headerNumber,$i,0,$showCheckSquares,$openCV);
        	
        	
        		$imageNames=getAllimageNames($temporary_dir."/word/media/");
        	
        		createSourceRelsXML('document.xml.rels','transform_rels.xsl',$temporary_dir."/word/_rels/document.xml.rels",$imageNames);
        	
        	
        	
        		$docxArchivePath=zipDoc($temporary_dir,"test","/tmp/uach");
        	
        		array_push($docxes, $docxArchivePath);
        	
        		//kdyztak vygenerovat spravnou odpoved
        		if ($showAnswers){
        			$temporary_results_dir=tmpdir("/tmp/uach/","rtes");
        			copydir("word_root",$temporary_results_dir);
        			unlink($temporary_results_dir."/word/media/.dummy");
        			createSourceDOCXML($CFG->dataroot.'/settings.xml','transformation_schema.xsl',$temporary_results_dir."/word",$temporary_results_dir."/word/media/",$COURSE->shortname,$current_cat->id.",".$current_cat->contextid,str_replace('\\', '/', $tempxml),implode("#",array_keys($top_cats)),$show_numbers,$headerNumber,$i,1,1,$openCV);
        			createSourceRelsXML('document.xml.rels','transform_rels.xsl',$temporary_results_dir."/word/_rels/document.xml.rels",$imageNames);
        			$docxArchivePath_result=zipDoc($temporary_results_dir,"test_res","/tmp/uach/",true);
        			array_push($docxes, $docxArchivePath_result);
        			unlinkRecursive($temporary_results_dir,true);
        		}
        		unlinkRecursive($temporary_dir,true);
        		unlink($tempxml);
        	}
        	deleteFilesOlderThan("/tmp/uach",24);
        	
        	
        	$filename = question_default_export_filename($COURSE, $category) .
        	$qformat->export_file_extension();
        	
        	//adress of the xml file
        	$export_url = question_make_export_url($thiscontext->id, $category->id,
        			$from_form->format, $withcategories, $withcontexts, $filename);
        	
        	
        	
        	echo $OUTPUT->box_start();
        	if (count($docxes)==1){
        		$baseName=basename($docxArchivePath);
        		echo '<a href="test_document.php?filename='.$baseName.'">';
        		print_string('downloaddocx','uach');
        	
        	}else if (count($docxes)>1){
        		$zipfileName = tempnam("/tmp/uach/", "zip");
        		$newname=str_replace(".tmp",".zip",$zipfileName);
        		rename($zipfileName,$newname);
        		$baseName=basename(createZipFile($newname,$docxes));
        		echo '<a href="test_document_zip.php?filename='.$baseName.'">';
        		print_string('downloadzip','uach');
        	}
        	
        	echo '</a><br/>';
        	//echo '<a href="test_document_pdf.php?filename='.$basePdfname.'">';
        	//print_string('downloadpdf','uach');
        	//echo '</a>';
        	
        	echo $OUTPUT->box_end();
        	
        	//Don't need automatic savedialog popup
        	//$PAGE->requires->js_function_call('document.location.replace', array($export_url->out()), false, 1);
        	
        	echo $OUTPUT->single_button("../../course/view.php?id={$COURSE->id}",get_string('continue','uach'));
        	$End = getTime();
        	echo "Cas = ".number_format(($End - $Start),2)." secs";
        	
        	echo $OUTPUT->footer();
        	
        	exit;
        	
        	}
        	
        	if (!canGenerate()){
        		echo '<div id="resp">'.get_string('cannot_generate','uach').'</div>';
        		echo $OUTPUT->footer();
        	}else{
        	
        	
        		echo $OUTPUT->heading(get_string('moduleusage','uach').' '.$COURSE->fullname);
        		echo $OUTPUT->heading($exporttest);
        		$export_form->display();
        		if (isset($_SESSION['cat'])) {
        			unset($_SESSION['cat']);
        		}
        		if (isset($_SESSION['cour'])) {
        			unset($_SESSION['cour']);
        		}
        		$_SESSION['cat']=$CFG->all_categories;
        		$_SESSION['cour']=$COURSE->shortname;
        		echo '<a href="uach_settings.php?course='.$COURSE->shortname.'&moduleid='.$mid.'">'.get_string('editsettings','uach').'</a>';
        		echo "<div><h3>Pokyny</h3><ul>
        		<li>Kategorie v nastavení korespondují s kategoriemi v Moodle
        		<li>Kategorie jsou generovány do testu v tom pořadí, v jakém jsou v Moodle - Bance úloh
        		<li><b>Je potřeba zachovat tuto strukturu</b> (v Bance úloh kategorie) - Alespoň 1. vrchní kategorie(symbolizuje test) s alespoň jednou podkategorií (jinak není generátor omezen)
        		<li>Do testů se promítají otázky pouze z nejspodnějších (hierarchicky) kategorií
        		<li>Pokud změníte v bance úloh pořadí kategorií, je třeba tuto stránku načíst znovu, aby se projevily změny
        		<li>Pokud zvolíte v menu nahoře při generování něco jiného než test (hierarchicky nejvyšší kategorie), dojde k exportu všech otázek z dané kategorie
        		<li>Položka 'pokyny k sekci' u jednotlivých kategorií slouží k dovysvělení, u testů slouží jako název testu a vygeneruje se na vrchu
        		</ul></div>";
        		echo $OUTPUT->footer();
        	}
        	 