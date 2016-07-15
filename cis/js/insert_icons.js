//NOT USED ANY MORE


function notify(div_icons_id){
	YUI().use('event','node', function (Y) {
    div_icon=Y.one("#"+div_icons_id);
		icons=Y.all('.e-learn_icon');
		
		icons.on('mouseover',function(e){
			src=e.target.get('src');
      alt=e.target.get('alt'); 
			region=e.target.getXY();
			region[1]=region[1]+20;
			larger_image=Y.Node.create('<div id="temp_icon" style="position:absolute;z-index:1;"><img src="'+src.replace("/thumbs","")+'"/><div class="desc" style="background-image:url(\'https://e-learning.vscht.cz/cis/moodle_icons/background.png\');padding-left:5px; align="center">'+alt+'</div></div>');
			div_icon.insert(larger_image,'after');
			larger_image.setXY(region);
		icons.on('mouseout',function(e){
			temp=Y.one("#temp_icon");
			if (temp){
				temp.remove();
			}
			
		});
		})
	});
	
}
function insert_icon(editor_id,name){
	//hide icons
  var div_with_icons=document.getElementById(editor_id+'_e-learning_icons');
   div_with_icons.style.display="none";
  //get MCE
  ed=tinyMCE.get(editor_id);
	ed.focus();
	//set image
  server=window.location.protocol+"//"+window.location.host;
	img='<img style="float: left; margin-left: 10px; margin-right: 10px;" src="'+server+'/cis/moodle_icons/'+name+'" alt="" width="64" height="64" />'
	ed.selection.setContent(img);
  ed.focus();
}