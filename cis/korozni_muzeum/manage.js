/*
* $ lightbox_me
* By: Buck Wilson
* Version : 2.4
*
* Licensed under the Apache License, Version 2.0 (the "License");
* you may not use this file except in compliance with the License.
* You may obtain a copy of the License at
*
*     http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and
* limitations under the License.
*/


(function($) {

    $.fn.lightbox_me = function(options) {

        return this.each(function() {

            var
                opts = $.extend({}, $.fn.lightbox_me.defaults, options),
                $overlay = $(),
                $desc = $(this).nextUntil("img","span.foto-popis");
                $self = $(this).clone(false).wrap("div"),
                
                $iframe = $('<iframe id="foo" style="z-index: ' + (opts.zIndex + 1) + ';border: none; margin: 0; padding: 0; position: absolute; width: 100%; height: 100%; top: 0; left: 0; filter: mask();"/>');
            
            $self.width("auto");
            $self.height("auto");
            $self.css("max-width","1000px");
            
            if (opts.showOverlay) {
                //check if there's an existing overlay, if so, make subequent ones clear
               var $currentOverlays = $(".js_lb_overlay:visible");
                if ($currentOverlays.length > 0){
                    $overlay = $('<div class="lb_overlay_clear js_lb_overlay"/>');
                } else {
                    $overlay = $('<div class="' + opts.classPrefix + '_overlay js_lb_overlay"/>');
                }
            }

            /*----------------------------------------------------
               DOM Building
            ---------------------------------------------------- */
            $('body').append($self).append($overlay);
            if ($desc.length>0){
                $self.wrap("<div id='lightbox_wrapper'></div>");
                $self = $("#lightbox_wrapper"); 
                var desc =  $desc.html() ;
                $self.append("<div class='lightbox_desc'>"+ desc + "</div>");
            }


            /*----------------------------------------------------
               Overlay CSS stuffs
            ---------------------------------------------------- */

            // set css of the overlay
            if (opts.showOverlay) {
                setOverlayHeight(); // pulled this into a function because it is called on window resize.
                $overlay.css({ position: 'absolute', width: '100%', top: 0, left: 0, right: 0, bottom: 0, zIndex: (opts.zIndex + 2), display: 'none' });
    if (!$overlay.hasClass('lb_overlay_clear')){
                 $overlay.css(opts.overlayCSS);
                }
            }

            /*----------------------------------------------------
               Animate it in.
            ---------------------------------------------------- */
               //
            if (opts.showOverlay) {
                $overlay.fadeIn(opts.overlaySpeed, function() {
                    setSelfPosition();
                    $self[opts.appearEffect](opts.lightboxSpeed, function() { setOverlayHeight(); setSelfPosition(); opts.onLoad()});
                });
            } else {
                setSelfPosition();
                $self[opts.appearEffect](opts.lightboxSpeed, function() { opts.onLoad()});
            }

            /*----------------------------------------------------
               Hide parent if parent specified (parentLightbox should be jquery reference to any parent lightbox)
            ---------------------------------------------------- */
            if (opts.parentLightbox) {
                opts.parentLightbox.fadeOut(200);
            }


            /*----------------------------------------------------
               Bind Events
            ---------------------------------------------------- */

            $(window).resize(setOverlayHeight)
                     .resize(setSelfPosition)
                     .scroll(setSelfPosition);

            $(window).bind('keyup.lightbox_me', observeKeyPress);

            if (opts.closeClick) {
                $overlay.click(function(e) { closeLightbox(); e.preventDefault; });
            }
            $self.delegate(opts.closeSelector, "click", function(e) {
                closeLightbox(); e.preventDefault();
            });
            $self.bind('close', closeLightbox);
            $self.bind('reposition', setSelfPosition);



            /*--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
              -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- */


            /*----------------------------------------------------
               Private Functions
            ---------------------------------------------------- */

            /* Remove or hide all elements */
            function closeLightbox() {
                var s = $self[0].style;
                if (opts.destroyOnClose) {
                    $self.add($overlay).remove();
                } else {
                    $self.add($overlay).hide();
                }

                //show the hidden parent lightbox
                if (opts.parentLightbox) {
                    opts.parentLightbox.fadeIn(200);
                }
                if (opts.preventScroll) {
                    $('body').css('overflow', '');
                }
                $iframe.remove();

            // clean up events.
                $self.undelegate(opts.closeSelector, "click");
                $self.unbind('close', closeLightbox);
                $self.unbind('repositon', setSelfPosition);
                
                $(window).unbind('resize', setOverlayHeight);
                $(window).unbind('resize', setSelfPosition);
                $(window).unbind('scroll', setSelfPosition);
                $(window).unbind('keyup.lightbox_me');
                opts.onClose();
            }


            /* Function to bind to the window to observe the escape/enter key press */
            function observeKeyPress(e) {
                if((e.keyCode == 27 || (e.DOM_VK_ESCAPE == 27 && e.which==0)) && opts.closeEsc) closeLightbox();
            }


            /* Set the height of the overlay
                    : if the document height is taller than the window, then set the overlay height to the document height.
                    : otherwise, just set overlay height: 100%
            */
            function setOverlayHeight() {
                if ($(window).height() < $(document).height()) {
                    $overlay.css({height: $(document).height() + 'px'});
                     $iframe.css({height: $(document).height() + 'px'});
                } else {
                    $overlay.css({height: '100%'});
                }
            }


            /* Set the position of the modal'd window ($self)
                    : if $self is taller than the window, then make it absolutely positioned
                    : otherwise fixed
            */
            function setSelfPosition() {
                var s = $self[0].style;

                // reset CSS so width is re-calculated for margin-left CSS
                $self.css({left: '50%', marginLeft: ($self.outerWidth() / 2) * -1,  zIndex: (opts.zIndex + 3) });


                /* we have to get a little fancy when dealing with height, because lightbox_me
                    is just so fancy.
                 */

                // if the height of $self is bigger than the window and self isn't already position absolute
                if (($self.height() + 80  >= $(window).height()) && ($self.css('position') != 'absolute')) {

                    // we are going to make it positioned where the user can see it, but they can still scroll
                    // so the top offset is based on the user's scroll position.
                    var topOffset = $(document).scrollTop() + 40;
                    $self.css({position: 'absolute', top: topOffset + 'px', marginTop: 0})
                } else if ($self.height()+ 80  < $(window).height()) {
                    //if the height is less than the window height, then we're gonna make this thing position: fixed.
                    if (opts.centered) {
                        $self.css({ position: 'fixed', top: '50%', marginTop: ($self.outerHeight() / 2) * -1})
                    } else {
                        $self.css({ position: 'fixed'}).css(opts.modalCSS);
                    }
                    if (opts.preventScroll) {
                        $('body').css('overflow', 'hidden');
                    }
                }
            }

        });



    };

    $.fn.lightbox_me.defaults = {

        // animation
        appearEffect: "fadeIn",
        appearEase: "",
        overlaySpeed: 250,
        lightboxSpeed: 300,

        // close
        closeSelector: ".close",
        closeClick: true,
        closeEsc: true,

        // behavior
        destroyOnClose: true,
        showOverlay: true,
        parentLightbox: false,
        preventScroll: false,

        // callbacks
        onLoad: function() {},
        onClose: function() {},

        // style
        classPrefix: 'lb',
        zIndex: 99999,
        centered: false,
        modalCSS: {top: '40px'},
        overlayCSS: {background: 'black', opacity: .3}
    }
})(jQuery);

//delete blank rows
lang = $("html").attr("lang");
var divs = $(".Druhy_koroze, .Povrchové_úpravy, .Kovové_materiály, .Protikorozní_ochrana, .Testovací")
var table = divs.find("table.entrytable")
$.each(table.find("td.pravy"),function(){
     var td = $(this);
     if ($.trim(td.text())=="" && td.children().length==0){
          //td.closest("tr").find("td").css("background-color","rgba(255,0,0,0.1)");
          td.closest("tr").addClass("hidden-row");
     }
});
//english/czech advanced
var entry_table = $("table.entrytable");
// avoid translation on edit page
console.log(entry_table.find("input"));
if (!entry_table.find("input").length){
    $("table.entrytable td.pravy, table.entrytable td.nadpis,table.entrytable td.roh-pravy").each(function(){
        var repl_lang = "cs";
        $(this).find("*").andSelf().contents().filter(function() {
            return this.nodeType === 3; //Node.TEXT_NODE
        }).wrap( "<span class='repl'></span>" );
        $(this).find("span.repl").each(function(){
            var repl_nodes = []
            var start_pos = 0;
            var inner_text = $(this).text();
            
            for (var i = 0, len = inner_text.length; i < len; i++) {
                if (inner_text[i] == "{" && inner_text[i+1]=="{"){
                    repl_nodes.push($('<span data-lang="'+repl_lang+'">'+ inner_text.substring(start_pos, i) + '</span>'));
                    start_pos = i+2;
                    repl_lang = "en";
                }
                else if (inner_text[i] == "}" && inner_text[i+1]=="}"){
                    repl_nodes.push($('<span data-lang="'+repl_lang+'">'+ inner_text.substring(start_pos, i) + '</span>'));
                    start_pos = i+2;
                    repl_lang = "cs";
                }
            } 
            
            //add the rest of string
            if (start_pos < i){
                repl_nodes.push($('<span data-lang="'+repl_lang+'">'+ inner_text.substring(start_pos, i) + '</span>'));
            }
            //trim all nodes to see if theyre not blank
            $.grep(repl_nodes, function(v){
                return v.text().trim().length > 0;
            });
            //single node??? remove attribute data-lang
            if (repl_nodes.length == 1){
                repl_nodes[0].removeAttr("data-lang");
            }
            $(this).empty();
            var self = $(this);
            $.each(repl_nodes, function(i,v){
                self.append(v);
            });
            
        });
    });
    
    $( "td.pravy span[data-lang], td.nadpis span[data-lang], td.roh-pravy span[data-lang]").not("[data-lang='"+lang+"']").hide();
}




//larger images
table.find(".pravy img:not(.entriesform .pravy img)").click(function(){
    $(this).lightbox_me();
});


//QR code
var qr = $("div.qr");
var link = qr.text()
var a = $("<a></a>")
a.attr("download","qr_code.png");
qr.append(a)

var qrbutton = $('<button>QR kód</button>')
qrbutton.insertAfter(qr);
qrbutton.click(function(){
    var img = qr.find("img")    
     if (img.length == 0){
           a.qrcode({
                 "render": "image",
                 "size": 300,
                 "color": "#3a3",
                 "text": qr.text()
           });
           a.attr("href", a.find("img").attr("src"));
    }
    a[0].click();
});

