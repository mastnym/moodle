function VideoPriruckaKontroler($scope, $location, $rootScope) {

    $scope.stav = 'uvod';

    $scope.vstupte = function($event) {
        $scope.stav = 'obsah';
        $event.stopImmediatePropagation();
    }

    $scope.videoBezNaPozici = function($event, sekund) {
        console.log($rootScope.popcorn);
        $rootScope.popcorn.currentTime(sekund);
        $rootScope.popcorn.play();
    }

    $scope.location = $location;

    $scope.$watch('location.path()', function(path) {
        $scope.stav = path.substr(1);
        $('html, body').animate({scrollTop:0}, 'slow');
    });
}