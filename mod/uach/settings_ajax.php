<?php
require_once(dirname(__FILE__) . '/../../config.php');
if (!isset($_GET["type"])){
	echo "bad request";
	return;
}
//xml
global $CFG;
$dom = new DOMDocument('1.0', 'utf-8');
$xmlFile=$CFG->dataroot.'/settings.xml';
$fp = fopen($xmlFile,'r');
$loaded=false;
if (flock($fp, LOCK_EX)) {
	$xmlstring=fread($fp, filesize($xmlFile));
	$loaded=$dom->loadXML($xmlstring);
	$dom->preserveWhiteSpace = false;
	$dom->formatOutput   = true;
	flock($fp, LOCK_UN);
}


//promene
$type=$_GET["type"];
if (!$loaded){
	exit;
}

//menim select
if ($type=="category_changed"){
	$cat_id=$_GET["id"];
	foreach ($dom->getElementsByTagName("category") as $cat ){
		if ($cat->getAttribute("id")==$cat_id){
			if ($cat->getElementsByTagName("category")->length==0){
				echo json_encode(getAttrs($cat,true));
			}else{ 
				echo json_encode(getAttrs($cat,false));
			}
			
			exit;
		}
	}
	
}

//updatuju 
if ($type=="update"){
	$attrs=$_GET;
	$cat_id=$attrs["id"];
	unset($attrs["id"]);
	unset($attrs["type"]);
	
	$percentage=array();
	foreach ($attrs as $key=>$val){
		if (startsWith($key, 'perc_cat_')){
			$repl=str_replace('perc_cat_', "perc_", $key);
			$percentage[$attrs[$key]]=$attrs[$repl];
			unset($attrs[$key]);
			unset($attrs[$repl]);
		}
	}
	foreach ($dom->getElementsByTagName("category") as $cat ){
		if ($cat->getAttribute("id")==$cat_id){
			updateNode($cat,$attrs);
			if (!empty($percentage)){
				createPercentage($cat,$percentage);
			}
			
			break;
		}
	}
	if (!empty($percentage)){
		$attrs=getAttrs($cat,true);
	}
	else{
		$attrs=getAttrs($cat,false);
	}
	
	$xmlstring=$dom->saveXML();
	$fp = fopen($xmlFile,'w');
	if (flock($fp, LOCK_EX)) {
		fwrite($fp, $xmlstring);
		flock($fp, LOCK_UN);
	}
	echo json_encode($attrs); 
	exit;
}




function updateNode($node,$attrs){
	foreach ($attrs as $key=>$val){
		if ($node->hasAttribute($key)){
			$validated_val=validateVal($node,$key,$val);
			$node->setAttribute($key,$validated_val);
		}
	}
}



function validateVal($node,$name,$value){
	if ($name=="questionsInSection" || $name=="points" || $name=="spaceAfterQuestion"){
		
			if (is_numeric($value)) return $value;
			else return "1";	
	}
	else if ($name=="instructions" || $name=="display" || $name=="displayPoints"){
		return $value;
	}
}

function createPercentage($node,$id2perc){
	global $dom;
	$percentage_els=$node->getElementsByTagName("percentage");
	while($percentage_els->length>0){
		$node->removeChild($percentage_els->item(0));
	}
	foreach ($id2perc as $id=>$perc){
		$percElement=$dom->createElement("percentage");
		$percElement->setAttribute("cat",$id);
		if (!is_numeric($perc)){
			$perc=10;
		}
		$percElement->setAttribute("value",$perc);
		$node->appendChild($percElement);
	}
}

function getAttrs($node,$getPerc){
	$attrs=array();
	foreach($node->attributes as $key=>$val){
		$attrs[$key]=$val->nodeValue;	
	}
	if ($getPerc){
		$i=0;
		foreach ($node->getElementsByTagName("percentage") as $perc_el){
			$attrs["perc_cat_".$i]=$perc_el->getAttribute("cat");
			$attrs["perc_".$i]=$perc_el->getAttribute("value");
			$i+=1;
		}
	}
	
	return $attrs;
}
function startsWith($haystack, $needle)
{
	return !strncmp($haystack, $needle, strlen($needle));
}