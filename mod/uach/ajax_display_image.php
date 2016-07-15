<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/formslib.php');
require($CFG->dirroot .'/question/format/xml/format.php');
include 'saxon_transform.php';
require_once($CFG->dirroot . '/question/type/essay/questiontype.php');?>
<div>
<img src='../mod/uach/pix/top_page.png' style="display:block;"/>
<div id="container" style=" background-image: url('../mod/uach/pix/page.png');background-repeat:repeat-y;">
<table style="margin-left:50px;">
<tr>
<td valign="top"><div id="left" style="font-size: 18px;">1.</div></td>
<td valign="top"><div id ="center" style="width: 570px; margin-left: 15px; margin-right: 15px;">
<?php
system('java -cp "'.$CFG->java_cp.'" cz.mastnym.HTMLLatex '.base64_encode($_POST['questiontext']['text']).' false');
?>
</div></td>
<td valign="top"><div id="right" style="font-size: 15px;">
<?php 
$points =round(intval($_POST['defaultmark']));
if ($points ==1){
	echo "(".$points." bod)";
}
elseif ($points<5){
	echo "(".$points." body)";
}
else{
	echo "(".$points." bodů)";
}
?> 
</div></td>
</div>
</tr>
</table>

</div>
<?php 
return;
/*$qid=$_POST['id'];
if (!isset($qid)||$qid==0){
    $question = $DB->get_record('question', array('id' => $CFG->sample_id));
 }else{
 	$question = $DB->get_record('question', array('id' => $qid));
 }	
 	$qtype= new qtype_essay();
  	$qtype= get_question_options($question);
 	$qformat=new qformat_xml();
 	$question->questiontext=$_POST['questiontext']['text'];
 	
  	$qformat->setCourse($COURSE);
  	$qformat->setQuestions(array($question->id=>$question));
  	$xml=$qformat->exportprocess();
  	//create temp source xml
  	$xmlfname = tempnam($_SERVER['TMP'], "xml");
	$xmlhandle = fopen($xmlfname, "w");
	fwrite($xmlhandle, $xml);
	fclose($xmlhandle);
	
	deleteFilesOlderThan("uach/questions",12);
	$temp_dir=tmpdir("uach/temp","tes");
	copydir("uach/word_root",$temp_dir);
  	//create document.xml for MS WORD
 	createSourceDOCXML($xmlfname,'quiz.xsl',$temp_dir.'/word',$temp_dir.'/word/media/');
 	//throw away temp source xml file
	unlink($xmlfname);
//	
	$imageNames=getAllimageNames($temp_dir.'/word/media/');
	createSourceRelsXML('uach/document.xml.rels','transform_rels.xsl',$temp_dir.'/word/_rels/document.xml.rels',$imageNames); 
	$docxArchivePath=zipDoc($temp_dir,"test","uach/questions");
	//rm $temp dir
	unlinkRecursive($temp_dir,true);
	//change dir back from zipDoc
	chdir($CFG->dirroot.'/question/');
	$docxbase=basename($docxArchivePath);
	$convertCommand='cscript /nologo "../mod/uach/uach/doc2pdf/doc2pdf.vbs" "../mod/uach/uach/questions/'.$docxbase.'"';
	exec($convertCommand,$a,$return);
 	if($return!=0){
 		echo "<a href=../mod/uach/question_preview.php?filename='.$docxbase.'>Download</a>";
 		return;
 	}
 	$pdf=basename(str_replace(".docx",".pdf",$docxArchivePath));
 	$png=basename(str_replace(".docx",".png",$docxArchivePath));
 	$convertpdfcommand=''.$CFG->imageMagick_convert.'\convert -density 200x200 ../mod/uach/uach/questions/'.$pdf.' -resize 50% -trim ../mod/uach/uach/questions/'.$png;
 	//$convertpdfcommand=''.$CFG->imageMagick_convert.'\convert -flatten -density 200x200 ../mod/uach/uach/questions/'.$pdf.' -resize 50% -background green -splice 0x1  -background white -splice 0x1 -trim  +repage -chop 0x1 ../mod/uach/uach/questions/'.$png;
   exec($convertpdfcommand);
 	echo "<br/>";
 	echo '<img style="margin-left:15px;" src="../mod/uach/image_display.php?filename='.$png.'"/>';
    
	*/
?>
