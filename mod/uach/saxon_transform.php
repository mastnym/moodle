<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once('uach_config.php');
//xml,xsl-source strings for XSL transformation, uses exec()- calling Saxon from commandline
//$test string with test category
function transform($xmlfname,$xslfname,$imageRoot,$course,$chosenid,$questionFile,$toplevel,$exercise_numbers_visible,$headerNumber,$variant,$showAnswers,$showCheckSquares,$openCV){
	global $CFG;
	
	if ($course){
		$command='java -cp '.$CFG->java_cp.':'.$CFG->saxon_home.'/saxon9.jar net.sf.saxon.Transform -s:"'.$xmlfname.'" -xsl:"'.$xslfname.'" course="'.$course.'" categoryid="'.$chosenid.'" top_level="'.$toplevel.'" imageRoot="'.$imageRoot.'" questionXML="'.$questionFile.'" showNumbers="'.$exercise_numbers_visible.'" header="'.$headerNumber.'"  variant="'.$variant.'" showAnswers="'.$showAnswers.'" showCheckSquares="'.$showCheckSquares.'" openCV="'.$openCV.'" ';// 2>&1';
		//echo $command;
	}


    $document=shell_exec($command);
    
	
	return $document;
}
function createSourceRelsXML($xml,$xsl,$relsFilePath,$filenames){
	global $CFG;
	$cmd='java -cp '.$CFG->java_cp.':'.$CFG->saxon_home.'saxon9.jar net.sf.saxon.Transform -s:'.$xml.' -xsl:'.$xsl.' -o:'.$relsFilePath.' filenames="'.trim($filenames).'"';
	exec($cmd);
}
function getAllimageNames($path){
	$names="";
	if ($handle = opendir($path)) {
	while (false !== ($entry = readdir($handle))) {
			if ((string)$entry !="." && (string)$entry !=".." ){
             $names.=" ";
	           $names.=(string)$entry;
      }
 	 

	}
	closedir($handle);
	return $names;
	}
}
function destroy($dir) {
	$mydir = opendir($dir);
	while(false !== ($file = readdir($mydir))) {
		if($file != "." && $file != "..") {
			chmod($dir.$file, 0777);
			if(is_dir($dir.$file)) {
				chdir('.');
				destroy($dir.$file.'/');
				rmdir($dir.$file) or DIE("couldn't delete $dir$file<br />");
			}
			else
			unlink($dir.$file) or DIE("couldn't delete $dir$file<br />");
		}
	}
	closedir($mydir);
}
function createZipFile($path,$files){
	$zip = new ZipArchive;
	$res = $zip->open($path, ZipArchive::CREATE);
	if ($res === TRUE) {
		foreach ($files as $key=>$file){
			$filename=basename($file);
			
			if (!_startsWith($filename, "r")){
				$zip->addFromString("test".$key.".docx", file_get_contents($file));
			}else{
				$zip->addFromString("test_results".strval(intval($key)-1).".docx", file_get_contents($file));
			}
			
		}
		$zip->close();
		
	} else {
		echo "Nepodařilo se vytvořit zip archiv, zkuste vygenerovat samostatný test";
	}
	return $path;
}
//creates a docx file in $docRoot directory
//$docRoot-directory of word document
//$archiveName
function zipDoc($docRoot,$archiveName,$testsFolder,$answers=false){
	$filename = tempnam($testsFolder, "doc");
	if ($answers){
		$filename = tempnam($testsFolder, "rdoc");
	}
	$cwd=getcwd();
	chdir ($docRoot);
	$zip = new ZipArchive();
	
	if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
		exit("cannot open <$filename>\n");
	}
	$folders = array ("_rels","docProps","word");
	// initialize an iterator
	// pass it the directory to be processed
	foreach ($folders as $folder){
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder."/"));
		// iterate over the directory
		// add each file found to the archive
		foreach ($iterator as $key=>$value) {
			if (substr($key,-1)=="."){
				continue;
			}
			$zip->addFile(realpath($key), $key) or die ("ERROR: Could not add file: $key");
		}
	}
	$zip->addFile("[Content_Types].xml");
	// close and save archive
	$zip->close();
	$newname=str_replace(".tmp",".docx",$filename);
	rename($filename,$newname);
	chdir($cwd);
	return $newname;
}
//zjistit jestli je dana kategorie top->v tom pripade generuju vse jinak jen samotnou kategorii
function processChosenCategory($chosen,&$all){
	//zjistim jestli ma kategorie rodice
	//pokud ano tak je to podkategorie a nechci nic michat->exportuju pouze ji
	//jinak exportuju cely predmet->tzn veskere testy
	$top_level=array();
	$id2parent=array();
	foreach ($all as $id=>$category){
		$id2parent[$category->id]=$category->parent;
		if (!array_key_exists($category->parent, $all) && !in_array($category->parent,$top_level)){
			$top_level[$category->id]=array() ;
		}
	}
	
	if  (array_key_exists($chosen->parent.",".$chosen->contextid,$id2parent)){
		$all=array(getCategoryById($chosen->id.",".$chosen->contextid, $all));
		
	}else{
		$without_parents=array();
		foreach($id2parent as $id=>$parent){
			if (!array_key_exists($parent, $id2parent)){
				$top_cat=getCategoryById($id, $all);
				array_push($without_parents,$top_cat);
			}
		}
		$all=$without_parents;
	}
	return $top_level;
	
}
function getCategoryById($id,$arrayOfCategories){
	foreach ($arrayOfCategories as $category){
		if ($category->id==$id){
			return $category;
		}
	}
}

function createSourceDOCXML($xmlFile,$xslFile,$wordXMLDIR,$imageRoot,$course,$chosenid,$questionFile,$toplevel,$numbers_visible,$headerNumber,$variant,$showAnswers,$showCheckSquares,$openCV){
	$fileXMLresult=($wordXMLDIR.'/document.xml');
	$handleXMLresult=fopen($fileXMLresult,"w");
	$xmlDoc=transform($xmlFile,$xslFile,$imageRoot,$course,$chosenid,$questionFile,$toplevel,$numbers_visible,$headerNumber,$variant,$showAnswers,$showCheckSquares,$openCV);
	fwrite($handleXMLresult,$xmlDoc);
	fclose($handleXMLresult);
}
function deleteFilesOlderThan($folder,$timeInHours){
	$mydir = opendir($folder);
	while(false !== ($file = readdir($mydir))) {
		if($file != "." && $file != "..") {
			$timeFromCreationInHours=(time()-filemtime($folder.DIRECTORY_SEPARATOR.$file))/60/60;
			if ($timeFromCreationInHours>$timeInHours){
				unlink($folder.DIRECTORY_SEPARATOR.$file);
			}
		}
	}
}
function _startsWith($haystack, $needle)
{
	$length = strlen($needle);
	return (substr($haystack, 0, $length) === $needle);
}
function tmpdir($path, $prefix)
{
	// Use PHP's tmpfile function to create a temporary
	// directory name. Delete the file and keep the name.
	$tempname = tempnam($path,$prefix);
	if (!$tempname)
	return false;

	if (!unlink($tempname))
	return false;

	// Create the temporary directory and returns its name.
	if (mkdir($tempname))
	return $tempname;

	return false;
}
function copydir($source,$destination)
{
	if(!is_dir($destination)){
		$oldumask = umask(0);
		mkdir($destination, 01777); // so you get the sticky bit set
		umask($oldumask);
	}
	$dir_handle = @opendir($source) or die("Unable to open");
	while ($file = readdir($dir_handle))
	{
		if($file!="." && $file!=".." && !is_dir("$source/$file")) //if it is file
		copy("$source/$file","$destination/$file");
		if($file!="." && $file!=".." && is_dir("$source/$file")) //if it is folder
		copydir("$source/$file","$destination/$file");
	}
	closedir($dir_handle);
}
function unlinkRecursive($dir, $deleteRootToo) 
{ 
    if(!$dh = @opendir($dir)) 
    { 
        return; 
    } 
    while (false !== ($obj = readdir($dh))) 
    { 
        if($obj == '.' || $obj == '..') 
        { 
            continue; 
        } 

        if (!@unlink($dir . '/' . $obj)) 
        { 
            unlinkRecursive($dir.'/'.$obj, true); 
        } 
    } 

    closedir($dh); 
    
    if ($deleteRootToo) 
    { 
        @rmdir($dir); 
    } 
    
    return; 
}
function getUniqueCode1($length = 7)
{	
	$code = md5(uniqid(rand(), true));
	if ($length != "") return substr($code, 0, $length);
	else return $code;
} 
function writecode($filePath){
	$file_handle = fopen($filePath, "r");
	$data = fread($file_handle, 4096);
	fclose($file_handle);
	$file_handle = fopen($filePath, "w");
	$code=getUniqueCode1()."";
	$toWrite=str_replace("bla",$code,$data);
	fwrite($file_handle,$toWrite);
	fclose($file_handle);
}
function getTime() 
    { 
    $a = explode (' ',microtime()); 
    return(double) $a[0] + $a[1]; 
    } 
function mb_trim( $string ) 
  { 
    $string = preg_replace( "/(^\s+)|(\s+$)/us", "", $string ); 
    return $string; 
  } 
  function substr_unicode($str, $s, $l = null) {
    return join("", array_slice(
        preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY), $s, $l));
}

function canGenerate(){
	return true;
}
function escapeEntities($input){
	$entities=array("&lt;","&gt;");
	$replace=array("###HTML###LT###","###HTML###GT###");
	$xml=str_replace($entities, $replace, $input);
	$xml=html_entity_decode($xml,ENT_COMPAT,"UTF-8");
	$xml=str_replace($replace,$entities, $xml);
	return $xml;
}
?>
