<?php 
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');
?>

onload=function(){
if (document.getElementsByClassName == undefined) {
	document.getElementsByClassName = function(className)
	{
		var hasClassName = new RegExp("(?:^|\\s)" + className + "(?:$|\\s)");
		var allElements = document.getElementsByTagName("*");
		var results = [];

		var element;
		for (var i = 0; (element = allElements[i]) != null; i++) {
			var elementClass = element.className;
			if (elementClass && elementClass.indexOf(className) != -1 && hasClassName.test(elementClass))
				results.push(element);
		}

		return results;
	};
}

var main_help= document.getElementsByTagName("h2")[0];

if (main_help.innerHTML.indexOf("Essay question")!=-1 || main_help.innerHTML.indexOf("dlouhou tvořenou odpovědí")!=-1){
	
	//tagy se odstrani vzdy
	var tags= document.getElementById("id_tagsheader");
	tags.style.display="none";
	
	//modifikace pouze u editu
	var modifiedBy=document.getElementById("createdmodifiedheader");
	if (modifiedBy!=undefined){
		modifiedBy.style.display="none";
	}
	
	
	//pocet elementu na schovani se lisi pri pridani/editu
	var content=document.getElementsByClassName("fitem");
	var defaultMark=document.getElementsByClassName("fitem required")[0];
		

	
	if (main_help.innerHTML.indexOf("Adding")!=-1 || main_help.innerHTML.indexOf("Přidání")!=-1){ //pridani otazky
		var default_mark= document.getElementById("id_defaultmark");
		default_mark.value="";
		defaultMark.style.display="none";
		
	
		
		
		for (i=0;i<content.length;i++){
			if (i>4 && i<content.length-1 ){
				content[i].style.display="none";
			}
		}
	}
	else{			//uprava otazky
		defaultMark.style.display="none";
		for (i=0;i<content.length;i++){
			if (i>5&& i<content.length-1){
				content[i].style.display="none";
			}
		}
	}
	
}
};


var last="";
function callPreview(actual) {
 if (last != actual){
  	preview();
  	last=tinyMCE.activeEditor.getContent();
 } 
  
}
//var interval = setInterval( function() { callPreview(tinyMCE.activeEditor.getContent());}, 10000);


function preview(){
tinyMCE.activeEditor.save();
YUI().use("io-base","io-form",'node', function(Y) {
    var elementResp=document.getElementById("resp");
    elementResp.innerHTML='<img src="../mod/uach/pix/icon_wait.gif"/>';
    var formObject =document.getElementById('mform1');
    
    var cfg = {
    method: 'POST',
    form: {
        id: formObject
    }
};
    function onComplete(transactionId, responseObject) {
      elementResp.innerHTML= responseObject.responseText;
      MathJax.Hub.Queue(["Typeset",MathJax.Hub]);
    
      
      
    };
    function onFailure(transactionId, responseObject) {
      alert("<?php echo get_string('refresh','uach');?>"); //FAILURE 
      
    };
    
    Y.on('io:complete', onComplete, Y);
    Y.on('io:failure',onFailure,Y)
    var request = Y.io("../mod/uach/ajax_display_image.php",cfg);
    
    
 
});
}


