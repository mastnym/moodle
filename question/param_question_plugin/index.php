<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once('config.php');
$PAGE->set_context(context_course::instance($_GET['courseid']));
$url = new moodle_url('/question/param_question_plugin/index.php');
$PAGE->set_url($url);
$PAGE->set_title("Vkládání parametrické otázky");
$PAGE->set_heading("Vkládání parametrické otázky");

list($thispageurl, $contexts, $cmid, $cm, $module, $pagevars) =
question_edit_setup('questions', '/question/edit.php');

$questionbank = new question_bank_view($contexts, $thispageurl, $COURSE, $cm);
echo $OUTPUT->header();
?>


<script src="static/tinymce/tinymce.min.js"></script>

<link rel="stylesheet" type="text/css" href="static/styles.css">

<div id="wrap">
<?php
$questionbank->display('questions', $pagevars['qpage'], $pagevars['qperpage'],
		$pagevars['cat'], $pagevars['recurse'], $pagevars['showhidden'],
		$pagevars['qbshowtext'], "");
?>
  <table id="tiny_mce">
    <tr>
      <td id="left_col">
          <div id="instructions"></div>
          <h2>Text otázky</h2>
          <textarea id="tinyMCE1"></textarea>
      </td>
      <td id="right_col">
          <h2>Náhled výsledku (nelze editovat)</h2>
		      <textarea id="tinyMCE2"></textarea>
      </td>
    </tr>
  </table>


    <div>
    <form id='form' enctype="multipart/form-data">
  			<h2>Nahraj excel</h2>
        <input name="file" type="file" accept=".xlsx" />
  			<input id="formbutton" type="button" value="Upload" />
		</form>
    <progress></progress>
    <div id="excel"></div>
		<button id="odeslat">Vlož otázky</button>
		<div id="sendErrors"></div>
		<div id="recap">
			<p class="bold">Kategorie: <span id="cat"></span></p>
			<p class="bold">Počet otázek, který se uloží do databáze: <span id="numberOfQuestions"></span></p>
			<p id="filename"></p>
			<p id="exampleQuestion" class="bold">Vzorová otázka:</p>

			<button id="postit">Importovat</button><span id="loading"><img src="static/loading.gif"/></span>
		</div>
    </div>

</div>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script>
$( document ).ready(function() {
		tinymce.init({

            selector:'#tinyMCE1',
            theme: "modern",
            entity_encoding : "raw",
            plugins: [
                      "advlist autolink lists link image charmap print preview hr anchor pagebreak",
                      "searchreplace wordcount visualblocks visualchars code fullscreen",
                      "insertdatetime media nonbreaking save table contextmenu directionality",
                      "emoticons template paste textcolor"
                  ],
                  toolbar1: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image",
                  toolbar2: "print preview media | forecolor backcolor emoticons",
                  image_advtab: true,
            width: 400,
            height: 200,
           setup: function(ed) {
               ed.on('init', function(e) {
            	   ed.on('keyUp',function(e, l) {
                      tinymce.get('tinyMCE2').setContent(processContent(tinymce.get('tinyMCE1').getContent()));
           			});
               });
               ed.on('init', function(e) {
            	   ed.on('change',function(e, l) {
                      tinymce.get('tinyMCE2').setContent(processContent(tinymce.get('tinyMCE1').getContent()));
           			});
               });
           }


        });

        tinymce.init({

            selector:'#tinyMCE2',
            width: 400,
            height: 200,
            readonly : true,



        });
        console.log("a");
        var form=$('#displayoptions');
        var select=$('#id_selectacategory').clone();
        select.attr("id","categories_select");
        form.after(select);
        $("#odeslat").click(displayQuestions);
        $("#postit").click(importQuestions);

        var fb=$('#formbutton');
        select.on('change',function(){
        	  getCategoryFromMoodle(select);
        });
        fb.on('click',function(){
        	  getCategoryFromMoodle(select);
        });


});
function getCategoryFromMoodle(select){

           $.ajax({url:"getPath.php?cat="+select.find("option:selected").val()+"&courseid="+querystring(document.location.search,"courseid"),
            	  success:function(result){
        		  $("#wrap").append("<div class='path' style='display:none;'>"+result+"</div>");
        		  },
        		  error:function(a,b,c){
        		  	$("#sendErrors").text(a.responseText);

        		  }});

}
function importQuestions(){
	$("#loading").show();
	var cat_path=$('.path:last').text();
	var textTillName='<question type="cloze"><name><text>';
	var textTillQuestion='</text></name><questiontext format="html"><text><![CDATA[';
	var textRest=']]></text></questiontext><generalfeedback format="html"><text/></generalfeedback><penalty>0.3333333</penalty><hidden>0</hidden></question>\n\n';
	var allParams=[];
	var errors=[];
	var params = [];
	var results =[];
	var dataRows =[];
	$("#params thead tr:first th").each(function() {
		//prvni sloupec
		if ($(this).find('input').length){
				return true;
			}
		allParams.push($(this));
		if ( $(this).children().length > 0 ) {
			results.push($(this).text()) ;
		}else{
			params.push($(this).text()) ;
			}
	});
	$("#params thead tr:last th").each(function() {
		//prvni sloupec
		if ($(this).find('input').length){
				return true;
			}
		errors.push($(this));
	});
	$("#params tbody tr").each(function() {
		dataRows.push($(this));
	});

	var xml="<quiz>";
	xml=writeCategory(cat_path,xml);

	for(var i=0;i<dataRows.length;i++){
		if (dataRows[i].find('input:first').is(':not(:checked)')){
			continue;
			}
		var inp=tinymce.get('tinyMCE1').getContent();
		var tds=[];
		dataRows[i].find("td").each(function() {
			//prvni sloupec
			if ($(this).find('input').length){
					return true;
				}
			tds.push($(this));
		});
		for (var j = 0; j < allParams.length; j++) {
			param=allParams[j];
			if (inp.indexOf(param.text())!=-1 && $.inArray(param.text(),params)!=-1){
			  	inp=inp.replaceAll(param.html(),tds[j].html());
			}else if (inp.indexOf(param.text())!=-1 && $.inArray(param.text(),results)!=-1){
				var special_val=constructSpecialVal(tds[j].text(),errors[j].text());
				inp=inp.replaceAll(param.text(),special_val);
			}

		}
		xml+=textTillName;
		xml+=$(inp).text().substring(0,50);
		xml+=textTillQuestion;
		xml+=inp;
		xml+=textRest;
	}
	xml+="</quiz>";
	//upload xml
	var boundary = "---------------------------7da24f2e50046";
	var body = '--' + boundary + '\r\n'
	         // Parameter name is "file" and local filename is "temp.txt"
	         + 'Content-Disposition: form-data; name="file";'
	         + 'filename="temp.xml"\r\n'
	         // Add the file's mime-type
	         + 'Content-type: plain/text\r\n\r\n'
	         // Add your data:
	         + xml + '\r\n'
	         + '--' + boundary + '--';
	$.ajax({
	    contentType: "multipart/form-data;boundary="+boundary,
	    data: body,
	    type: "POST",
	    url: "xmlUpload.php",
	    success: function (data, status) {
                console.log(data);
			if (data.startsWith("ok")){
				var filename=data.substring(2);
				postToImport(filename);
			}
			else{
				$('#sendErrors').append("Chyba uploadu, zkopirujte si otazku a načtěte stránku znovu (F5)");
			}

	    }
	});


}
function postToImport(filename){
	var posturl ="../import-cis.php?courseid="+querystring(document.location.search,"courseid");
	//kategorie

	var category=$('#categories_select').find("option:selected").val();
	//sesskey
	var href=$("div.logininfo:first a:last").attr("href");
	var sesskey=querystring(href, "sesskey");
	post(posturl, {"courseid":querystring(document.location.search,"courseid"),"sesskey":sesskey,"_qf__question_import_form":"1",
				"format":"xml","category":category,"catfromfile":"1","contextfromfile":"1","matchgrades":"error","stoponerror":"1"
					,"newfile":<?php echo $CFG->sample_draft_id;?>,"submitbutton":"Import","filename":filename});
}
function writeCategory(path,xml){
	var textTillName='<question type="category"><category><text>';
	var textRest='</text></category></question>\n\n';
	var course="$course$";

	xml+=textTillName;
	xml+=course;
	xml+=path;
	xml+=textRest;
	return xml;
}
function constructSpecialVal(rightAnswer,error){
	if (!isNumber(error)){
		error = 0;
	}
	if (!isNumber(rightAnswer)){
		//{:SHORTANSWER:=Berlin}
		return "{:SHORTANSWER:="+rightAnswer+"}";
	}else{
		//"{:NUMERICAL:=%s:%s}"
		return "{:NUMERICAL:="+rightAnswer+":"+error+"}";
		}

}
function querystring(url,key) {
	   var re=new RegExp('(?:\\?|&)'+key+'=(.*?)(?=&|$)','gi');
	   var r=[], m;
	   while ((m=re.exec(url)) != null) r.push(m[1]);
	   return r;
	}
function processContent(inp){

		var allParams=[];
		var errors=[];
		var params = [];
		var results =[];
		var row =[];
		var glow = [];
		$("#params thead tr:first th").each(function() {
			//prvni sloupec
			if ($(this).find('input').length){
					return true;
				}
			allParams.push($(this));
			if ( $(this).children().length > 0 ) {
				results.push($(this).text()) ;
			}else{
				params.push($(this).text()) ;
				}
		});
		$("#params thead tr:last th").each(function() {
			//prvni sloupec
			if ($(this).find('input').length){
					return true;
				}
			errors.push($(this));
		});

		$("#params tbody tr:has(input:checked):first td").each(function() {
			//prvni sloupec
			if ($(this).find('input').length){
					return true;
				}
			row.push($(this)) ;

		});



		for (var i = 0; i < allParams.length; i++) {
				param=allParams[i];
				if (inp.indexOf(param.text())!=-1 && $.inArray(param.text(),params)!=-1){
				  	inp=inp.replaceAll(param.html(),row[i].html());
				  	glow.push(param);
				}else if (inp.indexOf(param.text())!=-1 && $.inArray(param.text(),results)!=-1){
					inp=inp.replaceAll(param.text(),"<input type='text' placeholder='"+row[i].text()+"'/>");
					glow.push(param);
				}

		}
		for (var i = 0; i < glow.length; i++){
			glow[i].css("color","green");

			$("#params tbody tr:has(input:checked):first td").addClass("glow");
		}
	return inp;
}
String.prototype.replaceAll = function (find, replace) {
    var str = this;
    return str.replace(new RegExp(find, 'g'), replace);
};
$(':file').change(function(){
    var file = this.files[0];
    name = file.name;
    size = file.size;
    type = file.type;
    if (type!="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"){
		this.value="";
    }
});



$('#formbutton').click(function(){
    var formData = new FormData($('#form')[0]);
    getCategoryFromMoodle($("#cat_menu"));
    $.ajax({
        url: 'upload.php',  //server script to process data
        type: 'POST',
        xhr: function() {  // custom xhr
            var myXhr = $.ajaxSettings.xhr();
            if(myXhr.upload){ // check if upload property exists
                myXhr.upload.addEventListener('progress',progressHandlingFunction, false); // for handling the progress of the upload
            }
            return myXhr;
        },
        //Ajax events
        //beforeSend: beforeSendHandler,
        success: completeHandler,
        error: errorHandler,
        // Form data
        data: formData,
        //Options to tell JQuery not to process data or worry about content-type
        cache: false,
        contentType: false,
        processData: false
    });
});

function progressHandlingFunction(e){
    if(e.lengthComputable){
        $('progress').attr({value:e.loaded,max:e.total});
    }
}
function completeHandler(textStatus){
	$("#form").hide();
	$("progress").hide();
	$('#excel').html(textStatus);
	$('#excel').css("overflow-x","scroll");

	$("#odeslat").show();
	$("input[name='question_all']").on('change',function(){
		$("input[name='question_row']").prop('checked', this.checked);
	});
	changeSheet();

	$(function () {
	    $(".makeEditable").dblclick(function () {
	        var OriginalContent = $(this).text();

	        $(this).addClass("cellEditing");
	        $(this).html("<input type='text' value='" + OriginalContent + "' />");
	        $(this).children().first().focus();

	        $(this).children().first().keypress(function (e) {
	            if (e.which == 13) {
	                var newContent = $(this).val();
	                $(this).parent().text(newContent);
	                $(this).parent().removeClass("cellEditing");
	            }

	        });

	        $(this).children().first().blur(function(){
	        	 var newContent = $(this).val();
	                $(this).parent().text(newContent);
	                $(this).parent().removeClass("cellEditing");
    	    });

	    });
	});
  toTex();
  MathJax.Hub.Queue(["Typeset",MathJax.Hub]);
}
function errorHandler( jqXHR,  textStatus, errorThrown ){
	$("#left_col form").hide();
	$("#left_col progress").hide();
	if(jqXHR.status&&jqXHR.status==400){
		$('#excel').html(jqXHR.responseText);
   	}else{
       alert("Něco je špatně");
   }
	changeSheet();
}
function changeSheet(){
	$('#changeSheet').on('click',function(){
		 var request = $.ajax({
			  url: "upload.php",
			  type: "POST",
			  data: {sheet : $("#sheets").find(":selected").val(),filename : $("#sheets").attr("name")},
			});

			request.done(completeHandler);

			request.fail(errorHandler);
       });
}
function displayQuestions(){
	var $error=$('#sendErrors');
	var $recap=$('#recap');
	var $select=$('#categories_select');
	var numberOfQuestions=$("#params tbody tr td input:checked").length;
	var content=tinymce.get('tinyMCE2').getContent();
	var cat_path=$('.path:last').text();
	//jsou errory z minula
	if ($error.text()!=""){
		$error.text("");
	}
	//neni kategorie
	if (cat_path==""){
		$('#sendErrors').text("Zvolte kategorii");
		$recap.hide();
		return;
	}
	// neni vyplnena otazka
	if (content==""){
		$('#sendErrors').text("Je potřeba vyplnit otázku a použít alespoň jeden parametr");
		$recap.hide();
		return;
	}
	if ($select.val()==""){
		$('#sendErrors').text("Je potřeba zvolit kategorii");
		$recap.hide();
		return;
		}
	$("#numberOfQuestions").text(numberOfQuestions);
	$("#cat").text($select.find("option:selected").text());
	$(content).insertAfter($("#exampleQuestion"));
	$recap.show();

}
function isNumber(n) {
	  return !isNaN(parseFloat(n)) && isFinite(n);
	}

function post(path, parameters) {
    var form = $('<form></form>');

    form.attr("method", "post");
    form.attr("action", path);

    $.each(parameters, function(key, value) {
        var field = $('<input></input>');

        field.attr("type", "hidden");
        field.attr("name", key);
        field.attr("value", value);

        form.append(field);
    });

    $(document.body).append(form);
    form.submit();
}
if (typeof String.prototype.startsWith != 'function') {
	  // see below for better implementation!
	  String.prototype.startsWith = function (str){
	    return this.indexOf(str) == 0;
	  };
	}
</script>
<?php
echo $OUTPUT->footer();
?>