<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <link rel="stylesheet" href="css/table_items.css">
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
        
        $scope.getCNY = function(item){
        if (item){
            item.shipping = item.weight /453.6 * 4 * 6.4;
            item.cny = item.msrp * item.min_rate / 100 * 1.0875 * 6.4 + item.shipping;
            if (item.taobao_price > 0)
            {
              item.taobao_url = "https://item.taobao.com/item.htm?id=" + item.taobao_nid;
              item.rev = item.taobao_price - item.cny;
              item.revrate = item.rev / item.cny * 100;
            }
        }
    }

        $scope.order = function(predicate, reverse) {
          $scope.items = $filter('orderBy')($scope.items, predicate, reverse);
        };
        $scope.order('legoid', true);
      });
  })(window.angular);

  </script>



</head>

<body ng-app="barginList">
  <div><p>Tax:<input type="number" style="width:30px" ng-model="text_tax">%&nbsp;&nbsp;&nbsp;Shipping:<input type="number" style="width:30px" ng-model="text_shipping">US$/lb</p><p>Filter:<input type="text" ng-model="text_filter"></p></div>
  <div ng-controller="listController" class="div_items" ng-show="items">
    <table class="items">
      <tr>
        <th><a href="" ng-click="reverse=!reverse;order('legoid', !reverse)">LegoID</a></th>
        <th><a href="" ng-click="reverse=!reverse;order('badge', !reverse)">Badge</a></th>
        <th>Title</th>
        <th><a href="" ng-click="reverse=!reverse;order('msrp', !reverse)">MSRP</a></th>
        <th><a href="" ng-click="reverse=!reverse;order('min_rate', !reverse)">MinRate</a></th>
        <th>Shipping</th>
        <th>CNY</th>
        <th>Taobao</th>
        <th><a href="" ng-click="reverse=!reverse;order('revrate', !reverse)">Rev</th>
        <th>S&H</th>
        <th><a href="" ng-click="reverse=!reverse;order('toysrus_rate', !reverse)">Toysrus</a></th>
        <th><a href="" ng-click="reverse=!reverse;order('walmart_rate', !reverse)">Walmart</a></th>
        <th><a href="" ng-click="reverse=!reverse;order('amazon_rate', !reverse)">Amazon</a></th>
        <th><a href="" ng-click="reverse=!reverse;order('target_rate', !reverse)">Target</a></th>
        <th><a href="" ng-click="reverse=!reverse;order('bn_rate', !reverse)">BN</a></th>
      </tr>
      <tr ng-repeat="item in items | filter:text_filter" ng-init="getCNY(item)">
        <td>{{item.legoid}}</td>
        <td>{{item.badge}}</td>
        <td>{{item.theme}} - {{item.title}}</td>
        <td>{{item.msrp}}</td>
        <td>{{item.min_rate}}%</td>
        <td>{{item.shipping | number:2}}</td>
        <td>{{item.cny | number:2}}</td>
        <td><span ng-if="item.taobao_price != null"><a href="{{item.taobao_url}}">¥{{item.taobao_price}} ({{item.taobao_avg | number:2}}, {{item.taobao_vol}}/{{item.taobao_total}})</a></span></td>
        <td><span ng-if="item.revrate != null">{{item.rev | number:2}} ({{item.revrate | number:2}}%)</span></td>
        <td><span ng-if="item.lego_price != null">${{item.lego_price | number:2}}</span></td>
        <td><span ng-if="item.toysrus_rate != null"><span ng-if="item.toysrus_rate <= item.min_rate" color="red"><a href="{{item.toysrus_url}}">${{item.toysrus_price}} ({{item.toysrus_rate}}%)</a></span><span ng-if="item.toysrus_rate != item.min_rate"><a href="{{item.toysrus_url}}">${{item.toysrus_price}} ({{item.toysrus_rate}}%)</a></span></span></td>
        <td><span ng-if="item.walmart_rate != null"><a href="{{item.walmart_url}}">${{item.walmart_price}} ({{item.walmart_rate}}%)</a></span></td>
        <td><span ng-if="item.amazon_rate != null"><a href="{{item.amazon_url}}">${{item.amazon_price}} ({{item.amazon_rate}}%)</a></span></td>
        <td><span ng-if="item.target_rate != null"><a href="{{item.target_url}}">${{item.target_price}} ({{item.target_rate}}%)</a></span></td>
        <td><span ng-if="item.bn_rate != null"><a href="{{item.bn_url}}">${{item.bn_price}} ({{item.bn_rate}}%)</a></span></td>
      </tr>
    </table>
  </div>
</body>

</html>