<?php 
	
require_once(dirname(__FILE__) . '/../../config.php');
$id = optional_param('moduleid', 0, PARAM_INT);
if ($id) {
	$cm         = get_coursemodule_from_id('uach', $id, 0, false, MUST_EXIST);
	$course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
	$uach  = $DB->get_record('uach', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
	print_error('You must specify a course_module ID or an instance ID');
}
require_login($course, true, $cm);

$PAGE->set_context($context);
$PAGE->set_title("Nastavení");
$PAGE->set_heading("Nastavení");
$PAGE->set_url($CFG->wwwroot.'/mod/uach/uach_settings.php');
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
echo $OUTPUT->header();
echo $OUTPUT->heading('Nastavení souboru settings.xml');

if (!isset($_GET["course"]) || !isset($_SESSION["cat"])){
	echo "Vraťte se na předchozí stránku a znovu ji načtěte (F5)";
	die;
}
$course=$_GET["course"];
$categories=$_SESSION["cat"];

$parent2children=array();
$top_level=array();




foreach ($categories as $id =>$category){
	if (!array_key_exists($category->parent, $parent2children)){
		$parent2children[$category->parent]=array($category->id)	;
	}
	else{
		array_push($parent2children[$category->parent],$category->id);
	}
	if (!array_key_exists($category->parent, $categories) && !in_array($category->parent,$top_level)){
			$top_level[$category->id]=array() ;
	}	
}

r($categories,$top_level);
synchronizeXml($course);

if (has_capability("mod/uach:editsettings",$context)){
	echo makeSettings();
}
echo makeList($top_level);
echo "<script type='text/javascript' src='uach_settings.js'/>";
echo $OUTPUT->footer();


function r($categories,&$result){
	foreach ($categories as $id=>$cat){
		if (array_key_exists($cat->parent, $result)){
			$result[$cat->parent][$id]=array();
			r($categories,$result[$cat->parent]);
		}
	}
}
function makeList($array) {
	global $categories,$parent2children;
	//Base case: an empty array produces no list
	if (empty($array)) return '';

	
	$output = '<ul id="categories_list">';
	foreach ($array as $key => $subArray) {
		$category=$categories[$key];
			$output .= '<li id="'.$category->id.'">' . $category->name . makeList($subArray) . '</li>';
	}
	$output .= '</ul>';

	return $output;
}
function makeSettings(){
	global $categories,$parent2children,$context;
	$output ="<h2>Nastavení</h2><div id='error'></div>
	<div id='settings' style='border:2px solid;'>";
	$output.=createCategorySelect("category_select");
	$output.="<div id='leaf'>
				 <table>
				 	<tr>
				 		<td>Body za kategorii:</td><td><input type='text' size='2' name='points'/><span id='points'></span></td>
						<td>Počet otázek:</td><td><input type='text' size='2' name='questionsInSection'/><span id='questionsInSection'></span></td>
						<td>Počet prázdných odstavců:</td><td><input type='text' size='2' name='spaceAfterQuestion'/><span id='spaceAfterQuestion'></span></td>
					</tr>
				</table>
				<span>Míchání otázek </span>(<em><span style='font-size:smaller;'>Udávají se kategorie, ze kterých se losuje otázka s danou pravděpodobností.</span></em>)<img  src='pix/plus-icon.png' id='add'/>
				<table id='perc'>
				</table>
			</div>";
	$output.="<div id='notleaf'><table><tr><td>Zobraz v testu:</td><td><input type='checkbox' name='display' checked='checked'/><span id='display'></span></td>
						<td>Zobraz počet bodů za podkategorie:</td><td><input type='checkbox' name='displayPoints' /><span id='displayPoints'></span></td>
						<td>Pokyny k sekci(zobrazí se nad názvem):</td><td><input type='text'  name='instructions'/><span id='instructions'></span></td>
						</tr></table></div>";
	$output.="<button id='update'>Změň</button><img id='success_image' src='pix/icon_ok.png' style='display:none;'/></div>";
	
	return $output;
}

function createCategorySelect($id){
	global $categories;
	$output="<select id='$id' style='max-width:90%;'>";
	foreach ($categories as $category){
	
		$output .= '<option value="'.$category->id.'">' . $category->indentedname . '</option>';
	
	}
	$output.="</select>";
	return $output;
}


function synchronizeXml($course){
	global $CFG;
	global $top_level,$categories;
	$loaded=false;
	$dom = new DOMDocument('1.0', 'utf-8');
	$xmlFile=$CFG->dataroot.'/settings.xml';
	$fp = fopen($xmlFile,'r');
	if (flock($fp, LOCK_EX)) {
		$xmlstring=fread($fp, filesize($xmlFile));
		$loaded=$dom->loadXML($xmlstring);
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput   = true;
		flock($fp, LOCK_UN); 
	}
	
	if ($loaded){
		$settings=$dom->documentElement;
		$course_found=false;
		foreach ($settings->getElementsByTagName("course") as $xml_course){
			if( $xml_course->getAttribute("name")==$course){
				$cats=$xml_course->childNodes;
				$course_found=true;
				break;
			}
		}
		if (!$course_found){
			$xml_course=$dom->createElement("course");
			$nameAttribute = $dom->createAttribute('name');
			$nameAttribute->value=$course;
			$xml_course->appendChild($nameAttribute);
			$settings->appendChild($xml_course);
			$cats=createTopNodes($dom);
			foreach ($cats as $c){
				$xml_course->appendChild($c);
			}
			$cats=$xml_course->childNodes;
		}
		
		//mam cats a muzu se pustit do checku
		checkCategories($dom,$cats, $top_level,$xml_course);
		$xmlstring=$dom->saveXML();
		$fp = fopen($xmlFile,'w');
		if (flock($fp, LOCK_EX)) {
			fwrite($fp, $xmlstring);
			flock($fp, LOCK_UN);
		}
	}
	
	
}
function checkCategories($dom,$cat_nodes,$arr,$parent_el){//catnodes jsou vrchni elementy
	global $categories;
	//vezmu vsechny elementy bez textnodu apod
	$nodes_array=array();
	foreach ($cat_nodes as $cat_node) {
		if ($cat_node->nodeType == XML_ELEMENT_NODE && $cat_node->tagName=="category"){
				$attr=$cat_node->getAttribute("id");
				array_push($nodes_array, $attr);			
		}
	}
	// pokud je vic elementu v moodle pridam
	if (isset($nodes_array) && count($arr)>count($nodes_array)){
		foreach ($arr as $id=>$children){
			if (!in_array($id, $nodes_array)){
				$category_el=$dom->createElement("category");
				//id
				$idAttribute = $dom->createAttribute('id');
				$idAttribute->value=$id;
				//name
				$nameAttr= $dom->createAttribute('name');
				$nameAttr->value=getPropertyById($id, "name");
				//pos
				$posAttr=$dom->createAttribute('pos');
				$posAttr->value=array_search($id, array_keys($arr));
				
				$category_el->appendChild($idAttribute);
				$category_el->appendChild($nameAttr);
				$category_el->appendChild($posAttr);
				
				
				$category_el=generateAttributes($dom,$category_el);
				
				$parent_el->appendChild($category_el);
			}
		}
	}
	//zkontroluju jestli neni potreba neco vyhazet a zanorim se
	foreach ($cat_nodes as $cat_node) {
		if ($cat_node->nodeType == XML_ELEMENT_NODE && $cat_node->tagName=="category"){
			$el_id=$cat_node->getAttribute('id');
			if (!array_key_exists($el_id, $arr)){
				$parent=$cat_node->parentNode;
				$parent->removeChild($cat_node);
			}
			else{
				checkName($categories[$el_id]->name,$cat_node->getAttribute("name"),$cat_node);
				checkPosition(array_search($el_id, array_keys($arr)),$cat_node->getAttribute("pos"),$cat_node);
				checkCategories($dom,$cat_node->childNodes,$arr[$el_id],$cat_node);
			}
		}
	}
}

function checkPosition($moodle_pos,$current_pos,$node){
	if ($moodle_pos!=$current_pos){
		$node->setAttribute("pos",$moodle_pos);
	}
}

function checkName($name_from_moodle,$xml_name,$domNode){
	if ($name_from_moodle!= $xml_name) 
		 $domNode->setAttribute("name",$name_from_moodle);
}

function getPropertyById($id,$property){
	global $categories;
	return $categories[$id]->$property;
}
function generateAttributes($dom,$node){
	global $parent2children,$top_level;
	if (!array_key_exists($node->getAttribute("id"), $parent2children) && !array_key_exists($node->getAttribute("id"), $top_level) ){
		//leaf
		$points_attr = $dom->createAttribute('points');
		$points_attr->value=2;
		$questions_attr = $dom->createAttribute('questionsInSection');
		$questions_attr->value=1;
		$space_attr = $dom->createAttribute('spaceAfterQuestion');
		$space_attr->value=0;
		$node->appendChild($points_attr);
		$node->appendChild($questions_attr);
		$node->appendChild($space_attr);	
	}
	else{
		$display_attr = $dom->createAttribute('display');
		$display_attr->value=1;
		$points_attr = $dom->createAttribute('displayPoints');
		$points_attr->value=0;
		$instructions_attr = $dom->createAttribute('instructions');
		$instructions_attr->value="";
		$node->appendChild($display_attr);
		$node->appendChild($points_attr);
		$node->appendChild($instructions_attr);
	}
	return $node;
}
function createTopNodes($dom){
	global $top_level,$categories;
	$nodeList=array();
	foreach ($top_level as $id=>$val){
		$cat=$categories[$id];
		$category_el=$dom->createElement("category");
		$idAttribute = $dom->createAttribute('id');
		$idAttribute->value=$id;
		$nameAttr= $dom->createAttribute('name');
		$nameAttr->value=getPropertyById($id, "name");
		$category_el->appendChild($idAttribute);
		$category_el->appendChild($nameAttr);
		$category_el=generateAttributes($dom,$category_el);
		array_push($nodeList, $category_el);
	}
	return $nodeList;
}
?>