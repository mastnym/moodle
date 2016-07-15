<?php
//jmeno nodu print_r ($node->localName);
//echo "<br/>";

function getElementById($id,$doc)
{
	$xpath = new DOMXPath($doc);
	return $xpath->query("//*[@id='$id']")->item(0);
}

function extractDomQuestionsFromXML($category,$docSettings,$exportAll){
	
	$doc = new DOMDocument('1.0', 'utf-8');
	$doc->loadXML($category->xml);
	$doc->preserveWhiteSpace = false;
	$doc->formatOutput = true;
	$root=$doc->firstChild;
	$category->domQuestions=array();
	
	$numberOfQuestionsNeeded=getElementById($category->id.",".$category->contextid,$docSettings)->getAttribute("questionsInSection");
	$numberOfQuestionsNeeded=intval($numberOfQuestionsNeeded);
	
	foreach($root->childNodes as $child){
		if ($child->nodeType == XML_ELEMENT_NODE &&  $child->getAttribute('type')!='category') {
			array_push($category->domQuestions,$child);
		}
		
	}
	shuffle($category->domQuestions);
	if (!$exportAll){
		$category->domQuestions=array_slice($category->domQuestions,0,$numberOfQuestionsNeeded);
	}
	unset($category->xml);
}
function isLeaf($category,$categories){
	$id=$category->id;
	foreach($categories as $cat){
		if ($cat->parent==$id){
			return false;
		}
	}
	return true;
}

//bude odstraneno
function extractQuestions($categories,$xml,$course){
	global $CFG;
	$docSettings= new DOMDocument( '1.0', 'utf-8');
	$docSettings->load($CFG->dataroot."/settings.xml");
	foreach ($docSettings->getElementsByTagName("course") as $course_el){
		if ($course_el->getAttribute("name")==$course){
			break;
		}
	}
	$doc = new DOMDocument('1.0', 'utf-8');
	$doc->loadXML($xml);
	$doc->preserveWhiteSpace = false;
	$doc->formatOutput = true;
	$root=$doc->firstChild;
	$currentCategory='';
	$path='';
	foreach($root->childNodes as $child){
		
		if ($child->nodeType == XML_ELEMENT_NODE) {
			if ($child->getAttribute('type')=='category'){	
				$x_path=new DOMXPath($doc);
				$x_path1=$x_path->query('category/text',$child);
				#nahradim v kategorii // za ## -> lomitko ve jmene kategorie
				$currentCategory=str_replace("//", "##", $x_path1->item(0)->nodeValue);
				
				
				$path=explode("/", $currentCategory);
				
				foreach($path as &$p){
					$p=str_replace("##", "/", $p);#vratim zpet lomitka do jmen kategorii
				}
				
				
				
				$idPath=findCategoryIdByPath($path,$course_el,$docSettings);
				
				//nastaveni pathy-zatim nepotrbnme
				$path=implode("#",$idPath);
				$idPath=array_reverse($idPath);
				$currentCategory=$idPath[0];
				
					
			}
			 else{
				if (!isset($categories[$currentCategory])){
					$categories[$currentCategory]=array($child);

				}
				else{
					$c=$categories[$currentCategory];
					
					array_push($c,$child);
					
					$categories[$currentCategory]=$c;
				}
			} 
		}
	}
	return $categories;
}
//bude ostraneno
function findCategoryIdByPath($path_array,$courseElement,$settingsDoc){
	array_shift($path_array);//od nejvyssi kategorie bez $course$
	$idPath=array();
	$cat=$courseElement;
	$domXpath=new DOMXPath($settingsDoc);
	while(count($path_array)>0){
		foreach ($domXpath->query("child::category",$cat) as $category){
			if ($category->getAttribute("name")==$path_array[0]){
				array_push($idPath, $category->getAttribute("id"));
				$cat=$category;
				break;
			}
		}
		array_shift($path_array);
	}
	return $idPath;
}

//tbd
function createXML($categories,$filename,$allCategories){
	$dom = new DOMDocument('1.0', 'utf-8');
	$root = $dom->createElement('quiz','');
	$dom->appendChild($root);
	foreach($categories as $category => $questions){
		$categoryElement=$dom->createElement('category');
		$root->appendChild($categoryElement);
		
		$nameAttribute = $dom->createAttribute('name');
		$nameAttribute->value = $allCategories[$category]->name;
		$idAttribute = $dom->createAttribute('id');
		$idAttribute ->value = $category;
		$categoryElement->appendChild($nameAttribute);
		$categoryElement->appendChild($idAttribute);
		foreach ($questions as $question){
			$clonedNode=$dom->importNode($question,true);
			$answers=$clonedNode->getElementsByTagName('answer');
			if ($answers->length > 0){
				$allanswers = array();
				foreach ($answers as $a) {
					$allanswers[] = $a;
				}
				// Remove, shuffle, append
				foreach ($allanswers as $a) {
					$clonedNode->removeChild($a);
				}
				shuffle($allanswers);
				foreach ($allanswers as $a) {
					$clonedNode->appendChild($a);
				}
			}
			$categoryElement->appendChild($clonedNode);
		}
		
	}

	$dom->save($filename);
}


function createXML_pok($categories,$filename){
	$dom = new DOMDocument('1.0', 'utf-8');
	$root = $dom->createElement('quiz','');
	$dom->appendChild($root);
	foreach($categories as $category){
		
		$categoryElement=$dom->createElement('category');
		$root->appendChild($categoryElement);

		$nameAttribute = $dom->createAttribute('name');
		$nameAttribute->value = $category->name;
		$idAttribute = $dom->createAttribute('id');
		$idAttribute ->value = $category->id.",".$category->contextid;
		$categoryElement->appendChild($nameAttribute);
		$categoryElement->appendChild($idAttribute);
		foreach ($category->domQuestions as $question){
			$clonedNode=$dom->importNode($question,true);
			$answers=$clonedNode->getElementsByTagName('answer');
			if ($answers->length > 0){
				$allanswers = array();
				foreach ($answers as $a) {
					$allanswers[] = $a;
				}
				// Remove, shuffle, append
				foreach ($allanswers as $a) {
					$clonedNode->removeChild($a);
				}
				shuffle($allanswers);
				foreach ($allanswers as $a) {
					$clonedNode->appendChild($a);
				}
			}
			$categoryElement->appendChild($clonedNode);
		}
		unset($category->domQuestions);
	}
	$dom->save($filename);
}



function checkSimilarity(&$arr,$threshold){
	$categories=array();
	while(count($arr)>0){
		$category=array();
		
		//vezmu prvni node v poli zapamatuju si text a dam ho do temp.pole
		$first=array_shift($arr);
		$first_text=$first->getElementsByTagName("questiontext")->item(0)->getElementsByTagName("text")->item(0)->textContent;
		array_push($category, $first);
		
		//pro vsechny ostatni: porovna similaritu a zapamatuju stejny, ktery zaroven nasoupu do stejneho pole
		$similar_indexes=array();
		$i=0;
		foreach($arr as $dom_node){
			$text=$dom_node->getElementsByTagName("questiontext")->item(0)->getElementsByTagName("text")->item(0)->textContent;
			similar_text($first_text, $text, $percent);
			//echo "$percent<br/>";
			if ($percent>$threshold){
				array_push($similar_indexes,$i);
				array_push($category, $dom_node);
			}
			$i++;
		}
		// pole dam do hlavniho, ktere je serazene podle similarit
		array_push($categories, $category);
		//stare pole zbavim tech, ktere jsou uz v poli similarit
		$newArr=array();
		$i=0;
		foreach($arr as $dom_node){
			if (!in_array($i, $similar_indexes)){
				array_push($newArr, $dom_node);
			}
			$i++;
		}
		//
		$arr=$newArr;
		
	}
	// sleju ale rozstrkam 
	$temp=array();
	$to_unset=null;
	while (count($categories)){
		foreach($categories as $key=>&$cat){
			if (count($cat)>0){
				$first=array_shift($cat);
				array_push($temp, $first);
			}else{
				$to_unset=$key;
			}
			
		}
		if ($to_unset!==null){
			unset($categories[$to_unset]);
			$to_unset=null;
		}
	}
		
	$arr=$temp;
}
//tbd
function mixItUp($categories2questions,$top_cats,$chosen,$checkSimilarity,$threshold){
	global $CFG;
	$id=$chosen->id.",".$chosen->contextid;
	//vse zamicham
	foreach ($categories2questions as &$arr){
		shuffle($arr);
		if ($checkSimilarity){
			checkSimilarity($arr,$threshold);
		}
			
	}
	if (array_key_exists($id, $top_cats)){//pokud to je test,musim promichat podle procent
		$docSettings= new DOMDocument( '1.0', 'utf-8');
		$docSettings->load($CFG->dataroot."/settings.xml");
		$x_path=new DOMXPath($docSettings);
		foreach ($x_path->query('//category/*[percentage]',$docSettings) as $cat_with_perc){
			$main_cat_id=$cat_with_perc->getAttribute("id");
			$cat2perc=array();
			foreach ($cat_with_perc->getElementsByTagName("percentage") as $perc_el){
				$cat=$perc_el->getAttribute("cat");
				$val=$perc_el->getAttribute("value");
				if (!isset($cat2perc[$cat])){
					$cat2perc[$cat]=$val;
				}	
			}
			if (array_key_exists($main_cat_id, $categories2questions)){
				mixIt($categories2questions,$main_cat_id,$cat2perc);
			}
			}
		}
		
		
	//$categories2questions=deleteUnusedCategories($docSettings,$categories2questions,$chosen);
	
	
	return $categories2questions;
	
}


function getMixPercentages(&$categories,$docSettings){
	$x_path=new DOMXPath($docSettings);
	foreach ($x_path->query('//category/*[percentage]',$docSettings) as $cat_with_perc){
		$main_cat_id=$cat_with_perc->getAttribute("id");
		$category=getQCategoryById($categories, $main_cat_id);
		$cat2perc=array();
		foreach ($cat_with_perc->getElementsByTagName("percentage") as $perc_el){
			$cat=$perc_el->getAttribute("cat");
			$val=$perc_el->getAttribute("value");
			if (!isset($cat2perc[$cat])){
				$cat2perc[$cat]=$val;
			}
		}
		$category->cat2perc = $cat2perc;
	}
}
function mixCategories(&$categories){
	foreach ($categories as $category){
		if (isset($category->cat2perc)){
			
			$maxlen=count($category->domQuestions);
			$result_arr=array();
			$source_arr=array();		
			
			//normalize to 100%
			if (array_sum($category->cat2perc)>100){
				$coeficient=100/array_sum($category->cat2perc);
				foreach ($category->cat2perc as $key=>$val){
					$category->cat2perc[$key]=$coeficient*$val;
				}
			}
			
			//add this category if it is not in there
			if (!in_array($category->id.",".$category->contextid,$category->cat2perc)){
				$category->cat2perc[$category->id.",".$category->contextid]=100 - array_sum($category->cat2perc);
			}
			foreach($category->cat2perc as $key => $val){
				$c=getQCategoryById($categories, $key);
				$source_arr[$key]=$c->domQuestions;
				shuffle($source_arr[$key]);
			}
			while ($maxlen>0){
				$max=calculateMinMax($category);
				$rand=rand(0,$max);
				foreach ($category->cat2perc as $cat => $perc){
					if ($rand > $category->minMax[$cat."_min"] && $rand <= $category->minMax[$cat."_max"] ){
						$first=array_shift($source_arr[$cat]);
						array_push($result_arr, $first);
						if (empty($source_arr[$cat])){
							unset($source[$cat]);
							unset($category->cat2perc[$cat]);
						}
						break;
					}else{
						$last=$perc;
					}
				
				}			
				$maxlen=$maxlen-1;
			}
			$category->domQuestions=$result_arr;		
		}
	}
}

function calculateMinMax($category){
	$a=array();
	$last=0;
	foreach ($category->cat2perc as $cat=>$perc){
		$a[$cat."_min"]=$last;
		$a[$cat."_max"]=$last+$perc;
		$last=$last+$perc;
	}
	$category->minMax=$a;
	return $last;
}
function getQCategoryById($categories,$id){
	foreach ($categories as $cat){
		if ($cat->id.",".$cat->contextid==$id){
			return $cat;
		}
	}
}
//tbd
function mixIt_pok(&$categories,$main_cat_id,$cat2perc){
	$temp_array=array();
	$main_cat_perc=100-array_sum($cat2perc);

	$percentages=array();
	$copy_of_categories=array();
	$cat2perc[$main_cat_id]=$main_cat_perc;//dodelat if
	$sumOfPrevious=0;
	$i=0;
	foreach($cat2perc as $cat=>$perc) {
		$i=$i+1;
		$sumOfPrevious=array_sum(array_slice($cat2perc, 0,$i));
		$percentages[$cat]=$sumOfPrevious;
		$copy_of_categories[$cat]=$categories2questions[$cat];
	}

	foreach ($copy_of_categories as $key => $val){
		shuffle($copy_of_categories[$key]);
	}

	while(!empty($copy_of_categories)){
		if (count($copy_of_categories)==1){
			$arrayOfquestions=array_shift($copy_of_categories);
			$temp_array=array_merge($temp_array,$arrayOfquestions);
			$copy_of_categories=null;
		}else{
			$rand=rand(0,100);
			$last=0;
			foreach ($percentages as $cat => $perc){
				if ($rand > $last && $rand <= $perc ){
					$first=array_shift($copy_of_categories[$cat]);
					array_push($temp_array, $first);
					if (empty($copy_of_categories[$cat])){
						unset($copy_of_categories[$cat]);
						unset($cat2perc[$cat]);
						$percentages=recalculatePercentages($cat2perc);
					}
					break;
				}else{
					$last=$perc;
				}

			}
		}

	}
	$categories2questions[$main_cat_id]=$temp_array;
}



//tbd
function mixIt(&$categories2questions,$main_cat_id,$cat2perc){
	$temp_array=array();
	$main_cat_perc=100-array_sum($cat2perc);
	
	$percentages=array();
	$copy_of_categories=array();
	$cat2perc[$main_cat_id]=$main_cat_perc;//dodelat if
	$sumOfPrevious=0;
	$i=0;
	foreach($cat2perc as $cat=>$perc) {
		$i=$i+1;
		$sumOfPrevious=array_sum(array_slice($cat2perc, 0,$i));
		$percentages[$cat]=$sumOfPrevious;
		
		$copy_of_categories[$cat]=$categories2questions[$cat];
	}
	
	foreach ($copy_of_categories as $key => $val){
		shuffle($copy_of_categories[$key]);
	}
	
	while(!empty($copy_of_categories)){
		if (count($copy_of_categories)==1){
			$arrayOfquestions=array_shift($copy_of_categories);
			$temp_array=array_merge($temp_array,$arrayOfquestions);
			$copy_of_categories=null;
		}else{
			$rand=rand(0,100);
			$last=0;
			foreach ($percentages as $cat => $perc){
				if ($rand > $last && $rand <= $perc ){
					$first=array_shift($copy_of_categories[$cat]);
					array_push($temp_array, $first);
					if (empty($copy_of_categories[$cat])){
						unset($copy_of_categories[$cat]);
						unset($cat2perc[$cat]);
						$percentages=recalculatePercentages($cat2perc);
					}
					break;
				}else{
					$last=$perc;
				}
				
			}
		}
		
	}
	$categories2questions[$main_cat_id]=$temp_array;
}
//tbd
function recalculatePercentages($currentPercentages){
	$sum=array_sum($currentPercentages);
	if ($sum==0){
		$coeficient=100;
	}else{
		$coeficient=100/array_sum($currentPercentages);
	}
	$return_array=array();
	$numItems = count($currentPercentages);
	$i = 0;
	foreach ($currentPercentages as $cat => $perc){
		if(($i+1)==$numItems){
			$sumOfPrevious=array_sum($return_array);
			$return_array[$cat]=100-$sumOfPrevious;
		} 
		else{
			$return_array[$cat]=round($perc*$coeficient);
		}
		
		$i=$i+1;
	}
	return $return_array;
}
//tbd
function deleteUnusedCategories($dom,$categories2questions,$chosen){
	$using=array();
	foreach ($dom->getElementsByTagName("category") as $cat){
		if ($cat->getAttribute("id")==$chosen->id.",".$chosen->contextid){
			array_push($using, $cat->getAttribute("id"));
			foreach ($cat->getElementsByTagName("category") as $desc_cat){
				array_push($using, $desc_cat->getAttribute("id"));
			}
		}
	}
	$return=array();
	print_r($categories2questions);
	foreach ($using as $id){
		
		$return[$id]=$categories2questions[$id];
	}
	return $return;
}
