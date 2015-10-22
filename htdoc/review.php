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
	    $http.get('api/to_review_news.php?hash=' + $scope.hash)
	      .success(function(data) {
	        $scope.news = data;
	      });
	  };

	  $scope.approve = function() {
	    $http.get('api/to_review_news.php?action=approve&hash=' + $scope.hash)
	      .success(function() {
	        $scope.btn_disable = true;
	      });
	  };

	  $scope.reject = function() {
	    $http.get('api/to_review_news.php?action=reject&hash=' + $scope.hash)
	      .success(function() {
	        $scope.btn_disable = true;
	      });
	  };
	});
	</script>
</head>

<body ng-app="Review">
	<div>
		<form ng-submit="submit()" ng-controller="ReviewController">
		<h2>Hash:
		&nbsp;<input type="text" ng-model="hash" size="30" ng-init="hash='<?php echo $_GET['id']?>'; submit()" placeholder="Enter article hash or link here">
		&nbsp;<button ng-model="btn_approve" ng-disabled="btn_disable" ng-click="approve()">Approve</button>
		&nbsp;<button ng-model="btn_reject" ng-disabled="btn_disable" ng-click="reject()">Reject</button></h2>
		<hr>
		<h2>[{{news.Status}}] ({{news.Type}}) {{news.Provider}} - {{news.Title}}</h2>
		<img src="{{news.PicPath}}"/>
		<hr>
		<a href="{{news.Link}}">{{news.Link}}</a>
	</div>
</body>

</html>
<?php

?>