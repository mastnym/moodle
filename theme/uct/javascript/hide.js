arrowimg=Y.one("a.sidebarshow");
if (arrowimg){
	arrowimg.on('click', function(e) {
	    e.preventDefault();
	    image = e.target
	    imagelink = image.ancestor("a.sidebarshow");
	    show = imagelink.getAttribute("data-show");
	    /*console.log(show);
	    alert('event: ' + e.type + ' target: ' + e.target.get('tagName'));*/
	    Y.io('/theme/uct/showhide.php?show='+show, {
	        method: 'GET',
	        on: {
	            success: function (id, result) {
	            	sidebar=Y.one('#block-region-side-post');
	            	mainregion = Y.one('#region-main');
	            	imagesrc = image.getAttribute("src");
	            	if (show=="hide"){
	            		sidebar.hide(true);
	            		imagelink.setAttribute("data-show","show");
	            		mainregion.replaceClass("span9","span11");
	            		image.setAttribute("src",imagesrc.replace("hide","show"));
	            		
	            		
	            	}
	            	else{
	            		sidebar.show(true);
	            		imagelink.setAttribute("data-show","hide");
	            		mainregion.replaceClass("span11","span9");
	            		image.setAttribute("src",imagesrc.replace("show","hide"));
	            		sidebar.removeClass("hide");
	            	}
	            	//console.log(result.responseText);
	            },
	        }
	    });
	});

	
}
