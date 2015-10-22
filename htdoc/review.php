<!doctype html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="http://netdna.bootstrapcdn.com/twitter-bootstrap/2.0.4/css/bootstrap-combined.min.css">
	<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.4.7/angular.min.js"></script>
	<script>
var app = angular.module("Review", []);


app.controller("ReviewController", function($scope, $http) {

  $scope.submit = function() {
	  $http.get('api/to_review_news.php?hash='+$scope.hash)
	  .success(function(data) {
	    $scope.news = data;
	  })
	  .error(function(data) {
	    // log error
	  });
  };
});
	</script>
</head>

<body ng-app="Review">
	<div>
		<form ng-submit="submit()" ng-controller="ReviewController">
		<label>Hash/Link:</label>
		<input type="text" ng-model="hash" size="30" ng-init="hash='<?php echo $_GET['id']?>'; submit()" placeholder="Enter article hash or link here">
		<hr>
		<h3>({{news.Type}}) {{news.Provider}} - {{news.Title}}</h3>
		<h4><img src="{{news.PicPath}}"/></h4>
		<hr>
		<a href="{{news.Link}}">{{news.Link}}</a>
	</div>
</body>

</html>
<?php

?>