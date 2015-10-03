<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <script src="http://ajax.googleapis.com/ajax/libs/angularjs/1.5.0-beta.1/angular.min.js"></script>
  <script>
  (function(angular) {
    'use strict';
    angular.module('barginList', [])
      .controller('listController', function($scope, $http, $filter) {

        $http.get("current_items.php")
          .success(function(response) {
            $scope.items = response.items;
          });

        $scope.order = function(predicate, reverse) {
          $scope.items = $filter('orderBy')($scope.items, predicate, reverse);
        };
        $scope.order('legoid', false);
      });
  })(window.angular);

  </script>



</head>

<body ng-app="barginList">
  <div ng-controller="listController">
    <table class="items">
      <tr>
        <th><a href="" ng-click="reverse=!reverse;order('legoid', !reverse)">LegoID</a></th>
        <th>Theme</th>
        <th>Title</th>
        <th><a href="" ng-click="reverse=!reverse;order('msrp', !reverse)">MSRP</a></th>
        <th><a href="" ng-click="reverse=!reverse;order('min_rate', !reverse)">MinRate</a></th>
        <th><a href="" ng-click="reverse=!reverse;order('toysrus_rate', !reverse)">Toysrus</a></th>
        <th><a href="" ng-click="reverse=!reverse;order('walmart_rate', !reverse)">Walmart</a></th>
        <th><a href="" ng-click="reverse=!reverse;order('amazon_rate', !reverse)">Amazon</a></th>

      </tr>
      <tr ng-repeat="item in items">
        <td>{{item.legoid}}</td>
        <td>{{item.theme}}</td>
        <td>{{item.title}}</td>
        <td>{{item.msrp}}</td>
        <td>{{item.min_rate}}%</td>
        <td>${{item.toysrus_price}} ({{item.toysrus_rate}}%)</td>
        <td>${{item.walmart_price}} ({{item.walmart_rate}}%)</td>
        <td>${{item.amazon_price}} ({{item.amazon_rate}}%)</td>
      </tr>
    </table>
  </div>
</body>

</html>