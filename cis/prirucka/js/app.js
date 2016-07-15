
(function ( Popcorn ) {

    function _getclazz (options) {
        if ('clazz' in options) {
            return options.clazz;
        } else {
            return 'highlighted';
        }
    }


    Popcorn.plugin( "setclass", {

        manifest: {
            about: {
                name: "Popcorn setclass Plugin",
                version: "0.2",
                author: "@annasob, @rwaldron",
                website: "annasob.wordpress.com"
            },
            options: {
                start: {
                    elem: "input",
                    type: "number",
                    label: "Start"
                },
                end: {
                    elem: "input",
                    type: "number",
                    label: "End"
                },
                clazz: {
                    elem: "input",
                    type: "text",
                    label: "Text"
                },
                target: "setclass-container"
            }
        },

        _setup: function( options ) {
        },


        /**
         * @member setclass
         * The start function will be executed when the currentTime
         * of the video  reaches the start time provided by the
         * options variable
         */
        start: function( event, options ){
            //console.log('setting class on', options.target);
            var target = Popcorn.dom.find( options.target );
            var clazz = _getclazz(options);
            $(target).addClass(clazz);
        },

        /**
         * @member setclass
         * The end function will be executed when the currentTime
         * of the video  reaches the end time provided by the
         * options variable
         */
        end: function( event, options ){
            var target = Popcorn.dom.find( options.target );
            var clazz = _getclazz(options);
            $(target).removeClass(clazz);
        },

        _teardown: function( options ) {
        }

    });
})( Popcorn );

angular.module('VideoPrirucka', [])
    .directive('videoPos', function($compile, $rootScope) {
        $rootScope.popcorn_id = 0;
        return {
            restrict: 'A',
            compile: function(iElement, iAttrs, transclude) {
                return {
                    pre : function() {

                    },
                    post: function(scope, tElement, tAttrs, controller) {
                        var id = "popcorn_item-" + $rootScope.popcorn_id++;
                        var el = $compile('<a ng-click="videoBezNaPozici($event, ' + tAttrs.videoPos + ')"' +
                                            ' id="' + id + '"'+
                                            ' class="popcorn_navig"></a>')(scope);
                        $(tElement).wrap($(el));
                        $(tElement).parent().parent().attr('id', "parent_" + id)
                        if (!('popcorn_navig' in $rootScope)) {
                            $rootScope.popcorn_navig = [];
                        }
                        $rootScope.popcorn_navig.push(id);
                    }
                }
            }
        };
    })
    .directive('priruckaVideo', function($rootScope, $timeout) {
        return {
            restrict: 'A',
            priority: 100,
            link: function(scope, element, attrs)
            {
                var kontejner = $('.castvideo', element);
                var url = attrs.priruckaVideo;
                // vytvor video element ...
                var videoElement = $('<video id="pop" width="' + kontejner.width() + '" poster="' + url + 'Poster.jpg" controls><source src="' + url + '.mp4"><source src="' + url + '.ogv"><source src="' + url + '.webm"></video>').appendTo(kontejner);
                $timeout(function() {
                    $rootScope.video = videoElement;
                    $rootScope.popcorn = Popcorn('#pop');
                    for (var i=0; i<$rootScope.popcorn_navig.length; i++) {
                        var pc = $rootScope.popcorn_navig[i];
                        var start = parseInt($('#' + pc).children().attr('video-pos'));
                        var end = 99999999;
                        if (i<$rootScope.popcorn_navig.length-1) {
                            var po = $rootScope.popcorn_navig[i+1];
                            end = parseInt($('#' + po).children().attr('video-pos'));
                        }
                        $rootScope.popcorn.setclass({
                            start: start,
                            end: end,
                            target: '#parent_' + pc
                        });
                    }
                    $rootScope.popcorn.play();
                }, 200);
            }
        };
    });

/*
$(document).ready(function() {

    var pop = Popcorn("#video");

    // add a setclass at 2 seconds, and remove it at 6 seconds
    pop.setclass({
        start: 1,
        end: 6,
        target: "#text"
    });

    pop.play();

});
*/