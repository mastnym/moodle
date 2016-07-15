if(typeof String.prototype.trim !== 'function') {
  String.prototype.trim = function() {
    return this.replace(/^\s+|\s+$/g, ''); 
  }
}
String.prototype.endsWith = function(suffix) {
    return this.indexOf(suffix, this.length - suffix.length) !== -1;
};
function showHiddenText(id) {
            hiddenText=document.getElementById("hidden_"+id);
            hiddenDots=document.getElementById("dots_hidden_"+id);
            hiddenText.style.display="inline";
            hiddenDots.style.display="none";
           }
           
function checkAnswer(id){
			var input=document.getElementById("input_"+id);
			var correctAnswer=document.getElementById("answer_"+id).firstChild.innerHTML.trim();	
            if (input.tagName=="SELECT"){//odpoved je index, snizim ho o 1
				correctAnswer=(parseInt(correctAnswer)-1)+"";
			}
			
			if (input.tagName=="DIV"){//odpoved je index, snizim ho o 1
				answered=""
				var inps= input.getElementsByTagName("input")
				for (var i=0;i<inps.length;i++){
					if (inps[i].checked){
						answered+=inps[i].value+","
						}
					}
				if (answered.endsWith(",")){
					answered=answered.substring(0,answered.length-1);
					}
				if (correctAnswer.endsWith(",")){
					correctAnswer=correctAnswer.substring(0,correctAnswer.length-1);
					}
			}else{
				answered=input.value.trim();
			}
			
            correct_answer_link=document.getElementById("show_answer_"+id);
            respImage=document.getElementById("resp_image_"+id);
            if (document.getElementById("correct_answer_"+id).innerHTML!=""){
				document.getElementById("correct_answer_"+id).innerHTML="";
				}
			if (correctAnswer.toLowerCase()==answered.toLowerCase()){
                respImage.innerHTML="<img src='https://e-learning.vscht.cz/cis/js/image/ok.png' alt='ok'/>" ;
                if (correct_answer_link.style.display!="none"){
                    correct_answer_link.style.display="none";
                }
            }
            else if (answered==""){
                alert("odpovězte na otázku");
            }
            else{
                respImage.innerHTML="<img src='https://e-learning.vscht.cz/cis/js/image/wrong.png' alt='wrong'/>";
                correct_answer_link.style.display="inline";
            }
            
           }
function showAnswer(id){
                document.getElementById("show_answer_"+id).style.display="none";
                input=document.getElementById("input_"+id);
				if (input.tagName=="SELECT"){
						correct_answer_index=document.getElementById("answer_"+id).firstChild.innerHTML;
						document.getElementById("correct_answer_"+id).innerHTML=input.options[correct_answer_index-1].text;
					}
					else{
						document.getElementById("correct_answer_"+id).innerHTML=document.getElementById("answer_"+id).innerHTML;
						}
                
                document.getElementById("resp_image_"+id).innerHTML="<img src='https://e-learning.vscht.cz/cis/js/image/ok.png' alt='ok'/>";;
            }
			
function getNumberFromID(IDattr){
      arr=IDattr.split("_");
      return arr.reverse()[0]  ;
    }	 
	
$(document).ready(function() {
   $('*[id^=dots_hidden] a:first-child').click(function(event) {
				event.preventDefault();
				id=getNumberFromID($(this).closest("*[id^=dots_hidden]").attr('id'));
				showHiddenText(id);
			  });
  
	 $('button[id^=check_answer]').click(function(event) {
			   id=getNumberFromID($(this).attr('id'));
				checkAnswer(id);
			  });
        
    $('a[id^=show_answer]').click(function(event) {
			   event.preventDefault();
				id=getNumberFromID($(this).attr('id'));
				showAnswer(id);
			  });
	$('table[class^="Mso"]').each(function(index, element) {
        firstImage=$(element).find("img").first();
		if (firstImage.height()!=56){
			$(element).css("margin","auto auto");	
			$(element).find("td,th").each(function(index, element) {
				$(element).css("padding","3px 30px 3px 30px");
            });
		}
		else{
			$(element).find("td").css("padding-top","5px");
			//$(element).css("background-color","azure");
			}
    });
 });