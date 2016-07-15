function notify(icons_div_id,url){	
	var icons_div=Y.one("#"+icons_div_id);
	var icons=icons_div.all('.e-learn_icon');
	icons.on('mouseover',function(e){
		var src=e.target.get('src');
		var alt=e.target.get('alt'); 
		var region=e.target.getXY();
		region[1]=region[1]+20;
		var larger_image=Y.Node.create("<div/>").set("id","temp_icon")
										.setStyle("position","absolute")
										.setStyle("zIndex","1")
		var img = Y.Node.create("<img/>").set("src",src.replace("/thumbs",""))
		img.appendTo(larger_image);
		
		var desc = Y.Node.create("<div/>").set("className","desc")
									.set("text",alt)
									.setStyle("backgroundImage","url('"+url+"/elearningicons/tinymce/img/icons/background.png')")
									.setStyle("paddingLeft","5px")
									.setStyle("align","center");
		desc.appendTo(larger_image);
		
		icons_div.insert(larger_image,'after');
		larger_image.setXY(region);
	});
	
	icons.on('mouseout',function(e){
		temp=Y.one("#temp_icon");
		if (temp){
			temp.remove();
		}
	});

}
