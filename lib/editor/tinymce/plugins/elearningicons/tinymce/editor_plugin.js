(function() {

	// Load plugin specific language pack
	tinymce.PluginManager.requireLangPack('elearningicons');

	tinymce.create('tinymce.plugins.Elearningicons', {
		init : function(ed, url) {
			ed.addCommand('insertIcon', function() {
				editor_id=this["id"];

				icon_div_id=editor_id+'_e-learning_icons';
				icon_div = Y.one("#"+icon_div_id);

				if (icon_div){
					icon_div.toggleView();
					return;
				}
				var base_url = ed.getParam("moodle_plugin_base");
				var icon_button=Y.one('#'+editor_id+"_elearningicons");

				Y.Get.js(base_url+"/elearningicons/tinymce/js/elearningicons.js", function (err) {
					if (!err) {
						//create div with icons
						var a = Y.Node.create("div");
						var image_icon_names=new Array("mico_cas.png",
								"mico_dotaz2.png",
								"mico_dulezite.png",
								"mico_check-no.png",
								"mico_check-yes.png",
								"mico_info.png",
								"mico_klicova-slova1.png",
								"mico_kniha.png",
								"mico_napad2.png",
								"mico_otazka.png",
								"mico_poznamka-autora.png",
								"mico_pozor.png",
								"mico_priklad.png",
								"mico_rekapitulace.png",
								"mico_text-poznamka.png",
								"mico_video.png",
								"mico_vzorec.png"
						);
						var image_icon_desc=new Array(ed.getLang("elearningicons.timeguess",""),
								ed.getLang("elearningicons.question",""),
								ed.getLang("elearningicons.important",""),
								ed.getLang("elearningicons.badresult",""),
								ed.getLang("elearningicons.correctresult",""),
								ed.getLang("elearningicons.info",""),
								ed.getLang("elearningicons.keywords",""),
								ed.getLang("elearningicons.litlink",""),
								ed.getLang("elearningicons.idea",""),
								ed.getLang("elearningicons.question",""),
								ed.getLang("elearningicons.anote",""),
								ed.getLang("elearningicons.important",""),
								ed.getLang("elearningicons.assignment",""),
								ed.getLang("elearningicons.recap",""),
								ed.getLang("elearningicons.note",""),
								ed.getLang("elearningicons.video",""),
								ed.getLang("elearningicons.formula",""));
						var icon_div = Y.Node.create("<div/>")
						.set("id",icon_div_id)
						.setStyle("position","absolute")
						.setStyle("zIndex","1")
						.setStyle("backgroundColor","white");
						for (var i = 0; i < image_icon_names.length; i++) {
							(function (i){
								var link = Y.Node.create("<a/>").set("href","javascript:void(0)");
								link.on("click",function(e){
									icon_div.hide();
									ed.focus();
									var inner_img = Y.Node.create("<img/>")
									.set("src",base_url+"elearningicons/tinymce/img/icons/"+image_icon_names[i])
									.set("width","64")
									.set("height","64")
									.setStyle("float","left")
									.setStyle("marginLeft","10px")
									.setStyle("marginRight","10px");
									ed.selection.setContent(inner_img.wrap("<p/>").ancestor().getHTML());
									ed.focus();
									return false;
								});
								var img = Y.Node.create("<img/>").set("className","e-learn_icon")
								.set("alt",image_icon_desc[i])
								.set("src",base_url+
										"/elearningicons/tinymce/img/icons/thumbs/"+image_icon_names[i]);
								img.appendTo(link);
								link.appendTo(icon_div);

							})(i);
						}
						region=icon_button.get('region');
						//region["top"]=region["top"]+100;
						icon_div.set('region',region);
						icon_button.insert(icon_div,'after');
						notify(icon_div_id,base_url);
					}else{
						icon_button.hide();
						alert("elearningicons.failjs");
					}

				});
			});
			// Register example button
			ed.addButton('elearningicons', {
				title : 'elearningicons.button',
				cmd : 'insertIcon',
				image : url + '/img/icon.png'
			});

		},
		getInfo : function() {
			return {
				longname : 'Insert e-learning icons plugin',
				author : 'Martin mastny',
				authorurl : '',
				infourl : '',
				version : "1.0"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('elearningicons', tinymce.plugins.Elearningicons);
})();