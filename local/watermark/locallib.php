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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Apply watermark to PDF's served to students
 * Additional hack is in mod/resource/view.php
 *
 *

 *
 * @package local
 * @subpackage watermark
 * @copyright 1999 onwards Martin Dougiamas and others {@link http://moodle.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();
require_once '../../config.php';
function watermark_create($user_name,$output_png_name) {
	global $CFG;
	$watermark = dirname(__FILE__). "/pix/watermark.png";
	$date_today = date("d.m.y");
	$command="convert $watermark -encoding Unicode -pointsize 24  -fill rgba\(0,0,0,0.3\) -gravity South -annotate +0+0 \"$user_name - $date_today  \" $output_png_name 2>&1";
	exec($command,$output,$retval);
	return $retval;
}

function watermark_apply($pdf,$watermark,$output){
	require_once('pdf/tcpdf.php');
	require_once('pdf/tcpdi.php');
	require_once('pdf/pdfwatermarker/pdfwatermarker.php');
	require_once('pdf/pdfwatermarker/pdfwatermark.php');
	try{
	    //Specify path to image. The image must have a 96 DPI resolution.
	    $watermark = new PDFWatermark($watermark);
	    //Place watermark behind original PDF content. Default behavior places it over the content.
	    //$watermark->setAsBackground();

	    //Specify the path to the existing pdf, the path to the new pdf file, and the watermark object
	    $watermarker = new PDFWatermarker($pdf,$output,$watermark);

	    //Save the new PDF to its specified location
	    $watermarker->savePdf();
	    return 0;
	}
	catch(Exception $e){
	    return 1;
	}

}

function watermark_stampPDF(&$file,$context){
	global $USER,$CFG;
	$filerecord=array();
	$filename = $file->get_filename();
	$temp_file_path=tempnam("/tmp","pdf");
	$file->copy_content_to($temp_file_path);
	$watermark_file="$CFG->dataroot"."/watermark/watermark".strval($USER->id).".png";

	$user_name=$USER->firstname." ".$USER->lastname." (".strval($USER->id).")";
	$watermarkCreated=watermark_create($user_name, $watermark_file);


	if ($watermarkCreated==0){
		$with_watermark=tempnam(sys_get_temp_dir(),"pdf");
		$applied_retval=watermark_apply($temp_file_path, $watermark_file, $with_watermark);
		if ($applied_retval==0){
			$filerecord=array('contextid'=>$context->id,'component'=>"mod_resource",'filearea'=>"content",
					"itemid"=>0,'filepath'=> "/".strval(time())."/", "filename"=>$filename, "author"=> "watermark");
			$fs=get_file_storage();
			$file=$fs->create_file_from_pathname($filerecord,$with_watermark);

		}
		unlink($with_watermark);
	}
	unlink($temp_file_path);
	return $filerecord;
}

