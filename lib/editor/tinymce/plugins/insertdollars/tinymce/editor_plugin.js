(function() {
    
        // Load plugin specific language pack
        tinymce.PluginManager.requireLangPack('insertdollars');

        tinymce.create('tinymce.plugins.Insertdollars', {
                /**
                 * Initializes the plugin, this will be executed after the plugin has been created.
                 * This call is done before the editor instance has finished it's initialization so use the onInit event
                 * of the editor instance to intercept that event.
                 *
                 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
                 * @param {string} url Absolute URL to where the plugin is located.
                 */
                init : function(ed, url) {

                        // Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceExample');
                        ed.addCommand('insert', function() {
                        	ed.focus();
        					ed.selection.setContent('$<span id="my_marker">\u200b</span>$');
        					var marker = Y.one(ed.getBody()).one('#my_marker');
        					ed.selection.select(ed.dom.select('#my_marker')[0],false);
        					marker.remove();
                        });

                        // Register example button
                        ed.addButton('insertdollars', {
                                title : 'insertdollars.button',
                                cmd : 'insert',
                                image : url + '/img/icon.gif'
                        });
                        
                },

                /**
                 * Returns information about the plugin as a name/value array.
                 * The current keys are longname, author, authorurl, infourl and version.
                 *
                 * @return {Object} Name/value array containing information about the plugin.
                 */
                getInfo : function() {
                        return {
                                longname : 'Insert dollars plugin',
                                author : 'Martin mastny',
                                authorurl : '',
                                infourl : '',
                                version : "1.0"
                        };
                }
        });

        // Register plugin
        tinymce.PluginManager.add('insertdollars', tinymce.plugins.Insertdollars);
})();