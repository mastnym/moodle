//pro edit.php
<?php 
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');
?>
var tds=document.getElementById("categoryquestions").getElementsByTagName("td")
for(var i = 0; i < tds.length; i++) {
    var color=tds[i].style.backgroundColor;
    tds[i].onmouseover = function() {
       this.parentNode.style.backgroundColor = "#87CEFA";
       
    }
    tds[i].onmouseout = function() {
      this.parentNode.style.backgroundColor = color;  
    }
}
var form=document.getElementById('displayoptions');
var desc=form.nextSibling;
var button=document.getElementsByName('returnurl')[0].previousSibling;
button.style.display="none";
form.style.display="none";
desc.style.display="none";



function hideElements(action){
	var uach_button=document.getElementById('uach_button');
	var form=document.getElementById('displayoptions');
	var desc=form.nextSibling;
	var button=document.getElementsByName('returnurl')[0].previousSibling;
	var link=document.getElementById('uach_link');
	if (action=='show'){
		form.style.display="block";
		button.style.display="block";
		desc.style.display="block";
		uach_button.style.display="none";
		link.href="javascript:hideElements('hide')";
		link.innerHTML="<?php  echo get_string('showlessoptions','uach');?>";
	}
	if (action=='hide'){
		uach_button.style.display="inline";
		link.innerHTML="<?php  echo get_string('showmoreoptions','uach');?>";
		link.href="javascript:hideElements('show')";
		form.style.display="none";
		desc.style.display="none";
		button.style.display="none";
	}
	
}
