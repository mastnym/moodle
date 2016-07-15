<?php
//initialize
require_once 'phpExcel/PHPExcel/IOFactory.php';
require_once 'phpExcel/PHPExcel.php';

$error=new stdClass();
$error->messages=array();
$result=new stdClass();

//ExceptionThrower::Start();
if (isset($_POST["sheet"])){
	$filename=$_POST["filename"];
	$sheetName=$_POST["sheet"];

}else{
	if(!file_exists ( "/tmp/param_q_plugin/" )){
		mkdir("/tmp/param_q_plugin/");
	}
	$xlsxFile=$_FILES['file'];
	$filename=$xlsxFile['tmp_name'];
	$movedFile="/tmp/param_q_plugin/".basename($filename);
	move_uploaded_file ( $filename , $movedFile);
	$filename=$movedFile;
}

try {
	
	$objReader = PHPExcel_IOFactory::createReader('Excel2007');
	#$objReader->setReadDataOnly(true);
	$worksheetNames = $objReader->listWorksheetNames($filename);
	$objPHPExcel = $objReader->load($filename);
	if (isset($sheetName)){
		$objWorksheet = $objPHPExcel->setActiveSheetIndex(array_search($sheetName, $worksheetNames));
	}
	else{
		$objWorksheet = $objPHPExcel->setActiveSheetIndex(0);
	}
	
	$rows=array();
	foreach ($objWorksheet->getRowIterator() as $row) {
		$r=array();
		$cellIterator = $row->getCellIterator();
		$cellIterator->setIterateOnlyExistingCells(false);
		foreach ($cellIterator as $cell) {
                $cellVal=$cell->getValue();  
                if ($cellVal instanceof PHPExcel_RichText){
				$html="";
				
				foreach ($cell->getValue()->getRichTextElements() as $richText){
					$text=replaceDotForCommaInNumber($richText->getText());
					if ($richText instanceof PHPExcel_RichText_Run){//text s formatem
						$richRunStyle=$richText->getFont();
						
						if ($richRunStyle->getSubScript()){
							wrapWith("sub",$text);
						}
						if ($richRunStyle->getSuperScript()){
							wrapWith("sup",$text);
						}
						if ($richRunStyle->getBold()){
							wrapWith("strong",$text);
						}
						if ($richRunStyle->getItalic()){
							wrapWith("i",$text);
						}
						
					}
					
					$html.=$text;
				}
				
				array_push($r,$html);
			}	
			
			else{
				
				array_push($r,replaceDotForCommaInNumber($cell->getFormattedValue()));
			}
			
		}
		array_push($rows,$r);
	}


} catch (Exception $e) {
	array_push($error->messages, "Nemohu otevřít soubor");
	endScript($error);
}




try {
	$parameters=$rows[0];
	$relative_error=$rows[1];
	$data=array_slice($rows,2);
} catch (Exception $e) {
	array_push($error->messages, "V souboru nejsou všechny potřebné řádky, zkontrolujte správnou syntax.");
	endScript($error);
}

//ExceptionThrower::Stop();

//check na spravnost udaju
checkLenghtsOfRows($parameters, $relative_error, $data, $error);
checkParams($parameters,$error);
if ($error->messages){
	endScript($error);
}


$sortedParams=getSortedParams($parameters, $relative_error,$data);
createChangeSheetSelect($worksheetNames,$filename);

echo createHtmlTable($sortedParams,$data);
exit();




    
    
//funkce

function replaceDotForCommaInNumber($text){
	
	if (is_numeric($text)){
		$text=str_replace(".", ",", $text);
	}
	return $text;
}
function checkDecimals($arg){
    if (!is_numeric($arg)){
      return $arg;
    }
    $arr=explode(".",$arg);
    if (count($arr)==2 && count($arr[1]>5)){
        return strval(round(floatval($arg),4));
    }
    return $arg;
}
function wrapWith($element,&$text){
	$text="<".$element.">".$text."</".$element.">";
}
function checkParams($params,&$error){
	if (count(array_unique($params))!=count($params) ){
		array_push($error->messages, "V souboru se vyskytuje stejný parametr více než jednou, přejmenujte tento parametr");
	}
}
function endScript($error){
		global $worksheetNames,$filename;
		header('HTTP/1.1 400 Bad Request');
		header('Content-Type: application/json');
		createChangeSheetSelect($worksheetNames,$filename);
		echo "<div id='error'>";
		foreach ($error->messages as $message){
			echo $message;
			echo "<br>";
		}
		echo "</div>";
		die();
}

function flip_row_col_array($array) {
    	$out = array();
    	foreach ($array as  $rowkey => $row) {
    		foreach($row as $colkey => $col){
    			$out[$colkey][$rowkey]=$col;
    		}
    	}
    	return $out;
    }
    
function createHtmlTable($parameters,$data){
	
	echo "<br/>";
	$table="<h2>Parametry ze souboru</h2><table id=\"params\" ><thead><tr>";
	$table.="<th><input type='checkbox' name='question_all' checked='checked'/></th>";
		foreach ($parameters as $param){
			if ($param->isResult){
				$table.="<th class='makeEditable'>$param->name<span></span></th>";
			}
			if (!($param->isResult)){
				$table.="<th class='makeEditable'>$param->name</th>";
			}
		}
	$table.="</tr><tr style='display:none;'>";
	foreach ($parameters as $param){
		$table.="<th>$param->relativeError</th>";
	}
	$table.="</tr></thead><tbody>";
	for ($i=0;$i<count($data);$i++){
		$table.="<tr>";
		$table.="<td><input type='checkbox' name='question_row' checked='checked'/></td>";
		 foreach ($parameters as $param){
				$table.="<td>".$param->data[$i]."</td>";
		} 
		$table.="</tr>";
	}
	$table.="</tbody></table>";
	return  $table;
}
function getSortedParams($parameters,$relativeError,$data){
	$params=array();
	foreach ($parameters as $key=>$value){
			$par=new Parameter();
			$par->name=$value;
			$par->column=$key;
			$par->isResult=is_numeric(str_replace(",", ".", $relativeError[$key]));
			$par->relativeError=str_replace(",", ".", $relativeError[$key]);
			$flipped_data=flip_row_col_array($data);
			if ($par->isResult){
				$par->data=str_replace(",", ".",$flipped_data[$key]);
			}else{
				$par->data=$flipped_data[$key];
			}
			array_push($params, $par);
	}
	return $params;
}
function checkLenghtsOfRows(&$params,&$relative_errors,&$data,&$error){
	if (!startsWith(strtolower(trim($params[0])),"parametry")){
		array_push($error->messages,"Chyba v syntaxi: (V první buňce musí být deklarace: \"Parametry:\")");
	}
	$params=array_slice($params, 1,null,true);
	if (!startsWith(strtolower(trim($relative_errors[0])),"relativn")){
		array_push($error->messages,"Chyba v syntaxi: (V první buňce druhého řádku musí být deklarace: \"Relativní chyba výsledku:\")");
	}
	$relative_errors=array_slice($relative_errors, 1,null,true);
	if (!startsWith(strtolower(trim($data[0][0])),"hodnoty")){
		array_push($error->messages,"Chyba v syntaxi: (V první buňce třetího řádku musí být deklarace: \"Hodnoty:\")");
	}
	
	$params=array_filter($params,"isParam");
	$paramLength=count($params);
	foreach ($params as $key=>$value){
		if ($value==""){
			array_push($error->messages,"Parametry nejsou kompletní");
		}
	}
	$relative_errors=array_slice($relative_errors, 0,$paramLength,TRUE);
	if (count($relative_errors)<$paramLength){
		while (($err_length=count($relative_errors))<$paramLength){
			array_push($relative_errors, "");
		} 
	}
	foreach ($data as $key=>&$row){
		$row=array_slice($row, 1,null,true);
		$row=array_slice($row, 0,$paramLength,TRUE);
		$numberOfValues=count($row);
		if ($paramLength<$numberOfValues){
			array_push($error->messages,"Nesouhlasí počet parametrů");
		}
		if ($numberOfValues!=$paramLength){
			array_push($error->messages,"Chyba v syntaxi: na řádku ".($key+3)." je nesprávný počet hodnot.");
		}
		
	}
	$isOneRelativeError=false;
	foreach($relative_errors as $err){
    $err=str_replace(",", ".", $err);
    if (is_numeric($err)){
			$isOneRelativeError=true;
			break;
		}
	}
	if (!$isOneRelativeError){
		array_push($error->messages,"Chyba v syntaxi: Do řádku relativních chyb je potřeba zadat alespoň jednu hodnotu (může být 0). Tyto hodnoty poté určují co je v příkladu výsledek.");
	}
	
}
class Parameter{
	public $name;
	public $isResult;
	public $column;
	public $relativeError;
	public $data;
	
}
function isParam($el){
	return $el!="";
}
function createChangeSheetSelect($sheets,$filename){
	echo "Zvolte list: ";
	echo "<select id='sheets' name='$filename'>";
	foreach ($sheets as $sheet){
		echo "<option value='$sheet'>$sheet</option>";
	}
	echo "</select>";
	echo "<button id='changeSheet'>Změn list v excelu</button>";
	echo"<br/>";
}
function startsWith($haystack, $needle)
{
	return !strncmp($haystack, $needle, strlen($needle));
}

class ExceptionThrower
{

	static $IGNORE_DEPRECATED = true;

	/**
	 * Start redirecting PHP errors
	 * @param int $level PHP Error level to catch (Default = E_ALL & ~E_DEPRECATED)
	 */
	static function Start($level = null)
	{

		if ($level == null)
		{
			if (defined("E_DEPRECATED"))
			{
				$level = E_ALL & ~E_DEPRECATED ;
			}
			else
			{
				// php 5.2 and earlier don't support E_DEPRECATED
				$level = E_ALL;
				self::$IGNORE_DEPRECATED = true;
			}
		}
		set_error_handler(array("ExceptionThrower", "HandleError"), $level);
	}

	/**
	 * Stop redirecting PHP errors
	 */
	static function Stop()
	{
		restore_error_handler();
	}

	/**
	 * Fired by the PHP error handler function.  Calling this function will
	 * always throw an exception unless error_reporting == 0.  If the
	 * PHP command is called with @ preceeding it, then it will be ignored
	 * here as well.
	 *
	 * @param string $code
	 * @param string $string
	 * @param string $file
	 * @param string $line
	 * @param string $context
	 */
	static function HandleError($code, $string, $file, $line, $context)
	{
		// ignore supressed errors
		if (error_reporting() == 0) return;
		if (self::$IGNORE_DEPRECATED && strpos($string,"deprecated") === true) return true;

		throw new Exception($string,$code);
	}
}