<?php
require_once("conn.php");
require_once("db_handler.php");
require_once("twitteroauth/autoload.php");

use TwitterOAuth\TwitterOAuth;

$connection = new TwitterOAuth(TWITTER_KEY, TWITTER_SECRET, TWITTER_TOKEN, TWITTER_TOKEN_SECRET);
/*
$ret = db_query("Twitter_Follower", array("UserID"), "screen_name = '' LIMIT 150");
//var_dump($ret);
if (!$ret->{'Status'})
{
	foreach ($ret->{'Results'} as $item)
	{
		$response = $connection->get("users/show", array("user_id" => $item->{'UserID'}));
		if (isset($response->errors))
		{
			var_dump($response->errors);
			//return false;
		}
		else
		{
			if ($response->followers_count > 20 && $response->statuses_count > 50 && strtotime('-10 days') < strtotime($response->status->created_at))
			{
				$friend = $connection->post("friendships/create", array("user_id" => $item->{'UserID'}));
				if (isset($friend->errors))
				{
					var_dump($friend->errors);
				}
				else
				{
					$request_friend = 1;
					$ret = db_update("Twitter_Follower", array("request_friend" => $request_friend, "screen_name" => $response->screen_name, "followers_count" => $response->followers_count, "statuses_count" => $response->statuses_count, "location" => $response->location, "utc_offset" => $response->utc_offset, "last_update" => date('Y-m-d H:i:s', strtotime($response->status->created_at))), "UserID = ".$item->{'UserID'});
				}
			}
			else
			{
				$request_friend = 0;
				$ret = db_update("Twitter_Follower", array("request_friend" => $request_friend, "screen_name" => $response->screen_name, "followers_count" => $response->followers_count, "statuses_count" => $response->statuses_count, "location" => $response->location, "utc_offset" => $response->utc_offset, "last_update" => date('Y-m-d H:i:s', strtotime($response->status->created_at))), "UserID = ".$item->{'UserID'});
			}

			//var_dump($ret);
			//return $response->id;
		}
	}
}
else
{
	var_dump($ret);
}
*/


$ret = db_query("Twitter_Follower", array("UserID"), "request_friend = 0 AND last_update < '".date('Y-m-d H:i:s', strtotime('-3 days'))."' LIMIT 150");
//var_dump($ret);
if (!$ret->{'Status'})
{
	foreach ($ret->{'Results'} as $item)
	{
		$friend = $connection->post("friendships/destroy", array("user_id" => $item->{'UserID'}));
		if (isset($friend->errors))
		{
			var_dump($friend->errors);
		}
		else
		{
			$ret = db_delete("Twitter_Follower", array("UserID" => $item->{'UserID'}));
			var_dump($ret);
		}
	}
}
else
{
	var_dump($ret);
}



/*
$ret = db_query("Twitter_Tweet", array("TweetID"), null);
if (!$ret->{'Status'})
{
	foreach ($ret->{'Results'} as $item)
	{
		
		$item->{'TweetID'};
		$connection = new TwitterOAuth(TWITTER_KEY, TWITTER_SECRET, TWITTER_TOKEN, TWITTER_TOKEN_SECRET);
		$response = $connection->post("statuses/destroy/".$item->{'TweetID'}, array("id" => $item->{'TweetID'}));
		if (isset($response->errors))
		{
			var_dump($response->errors);
			//return false;
		}
		else
		{
			//return $response->id;
		}
	}
}
*/
?>