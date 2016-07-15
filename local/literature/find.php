
<?php
require_once '../../config.php';
require_once 'locallib.php';

define('CSV_URL',"http://vydavatelstvi.vscht.cz/katalog/csv");
define('CSV_PATH',"/srv/e-learning/data/katalogVSCHT.csv");
define('ONLINE_URL',"http://vydavatelstvi.vscht.cz/katalog/uid_isbn-");
define('TECHLIB_URL',"https://vufind.techlib.cz/vufind/Search/Results?lookfor=");

$isbn=optional_param('isbn', 0, PARAM_ALPHANUMEXT);
$page=optional_param('page', -1, PARAM_INT);

$ret_json=new stdClass();
$literature=array();

$config= get_config('local_literature');
if (!isset($config) || $config->enabled != 1){
	$ret_json->error=get_string('plugin_off', 'local_literature');
	return json_encode($ret_json);
}

if (!findIsbn(strval($isbn))){
	$ret_json->error=get_string('isbn_not_valid', 'local_literature');
	return json_encode($ret_json);
}

//vydavatelstvi
$filename = CSV_PATH;
if (file_exists($filename)) {
	$modified=filemtime($filename);
	if( (time() - $modified) > 24*60*60 ){
		downloadCSV(CSV_URL,CSV_PATH);
	} 
}else {
	downloadCSV(CSV_URL,CSV_PATH);
}
if (file_exists($filename)){
	$isbns=array();
	if (($handle = fopen(CSV_PATH, "r")) !== FALSE) {
		while (($data = fgetcsv($handle, 10000, ";")) !== FALSE) {
			array_push($isbns, trim($data[3]));//isbn
		}
		fclose($handle);
	}
}else{
	$ret_json->error=get_string('no_csv', 'local_literature');
	return json_encode($ret_json);
}

//vydavatelstvi
if (isset($isbns) && in_array($isbn, $isbns) ){
	$online=new stdClass();
	$online->label="online";
	$page_suffix="";
	if ($page>0){
		$page_suffix="/view/page=".$page;
	}
	$online->url=ONLINE_URL.$isbn.$page_suffix;
	array_push($literature, $online);
}

//techlib-vufind
$online=new stdClass();
$online->label="techlib";
$online->url=TECHLIB_URL.$isbn;
array_push($literature, $online);


//pack and return
$ret_json->literature=$literature;
print_r($ret_json);
return json_encode($ret_json);



