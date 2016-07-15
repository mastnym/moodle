<?php
 //require_once('fpdf.php');

 require_once('tcpdf.php');
 require_once('tcpdi.php');
 require_once('pdfwatermarker/pdfwatermarker.php');
 require_once('pdfwatermarker/pdfwatermark.php');


//Specify path to image. The image must have a 96 DPI resolution.
$watermark = new PDFWatermark('C:\Users\Martin\Desktop\watermark\watermark.png'); 
//Place watermark behind original PDF content. Default behavior places it over the content.
$watermark->setAsBackground();

//Specify the path to the existing pdf, the path to the new pdf file, and the watermark object
$watermarker = new PDFWatermarker('C:\Users\Martin\Desktop\watermark\watermark.pdf','C:\Users\Martin\Desktop\watermark\watermark_mod.pdf',$watermark); 

//Save the new PDF to its specified location
$watermarker->savePdf(); 
echo "done"
?>