<?php

require_once("conn.php");
require_once("twitteroauth/autoload.php");

use TwitterOAuth\TwitterOAuth;

function send_Message($recipient, $text)
{
	$connection = new TwitterOAuth(TWITTER_KEY, TWITTER_SECRET, TWITTER_TOKEN, TWITTER_TOKEN_SECRET);
	$response = $connection->post("direct_messages/new", array("screen_name" => $recipient, "text" => $text));
	if (isset($response->errors))
	{
		var_dump($response->errors);
		return false;
	}
	else
	{
		return true;
	}

}

function new_tweet($message)
{
	$connection = new TwitterOAuth(TWITTER_KEY, TWITTER_SECRET, TWITTER_TOKEN, TWITTER_TOKEN_SECRET);
	$response = $connection->post("statuses/update", array("status" => $message));
	if (isset($response->errors))
	{
		var_dump($response->errors);
		return false;
	}
	else
	{
		return $response->id;
	}
}

function reply_tweet($tweetID, $message)
{
	$connection = new TwitterOAuth(TWITTER_KEY, TWITTER_SECRET, TWITTER_TOKEN, TWITTER_TOKEN_SECRET);
	$response = $connection->post("statuses/update", array("in_reply_to_status_id" => $tweetID, "status" => $message));
	if (isset($response->errors))
	{
		var_dump($response->errors);
		return false;
	}
	else
	{
		return $response->id;
	}
}

function list_follower()
{
	//$content = $connection->get("followers/list", array("screen_name" => "LEGO_Group"));
}


function publish_SaleMessage($provider, $itemID, $salePrice, $legoID)
{
	//$theme, $title, $msrp, $url;

	$ret = db_query("DB_Set INNER JOIN DB_Theme ON DB_Set.ThemeID = DB_Theme.ThemeID", array("LegoID", "ETitle AS Title", "ETheme AS Theme", "USPrice AS MSRP"), "LegoID=".$legoID);
	//var_dump($ret);
	if (!$ret->{'Status'} && $ret->{'Results'})
	{
		$title = $ret->{'Results'}[0]->{"Title"};
		$theme = $ret->{'Results'}[0]->{"Theme"};
		$msrp = $ret->{'Results'}[0]->{"MSRP"};
		$url = $provider."/ip/".$itemID;

		if ($msrp && $salePrice)
		{
			$rate = 100 - round($salePrice / $msrp * 100);
		}
		else
		{
			$rate = null;
		}
		$message = "[".$legoID."] ".$theme." - ".$title." is on sale for $".$salePrice." (".$rate."% off from reg.$".$msrp.") at ".$url;

		$ret = db_query("Twitter_Tweet", array("TweetID", "Price"), "Provider='".$provider."' AND ItemID='".$itemID."' AND LastPublishTime > '".date('Y-m-d H:i:s', strtotime('-7 days'))."'");

		if (!$ret->{'Status'} && $ret->{'Results'})
		{
			$tweetID = $ret->{'Results'}[0]->{"TweetID"};
			$lastPrice = $ret->{'Results'}[0]->{"Price"};
			$lastRate = 100 - round($lastPrice / $msrp * 100);

			if ($rate - $lastRate > 2)
			{
				reply_tweet($tweet->{'TweetID'}, "[".$legoID."] ".$theme." - ".$title." was reduced even further to $".$salePrice." (".$rate."% off from reg.$".$msrp.") at ".$url);
				send_Message(NOTIFICATION_RECIPIENT, $message);
			}
		}
		else
		{
			$tweetID = new_tweet($message);
			if($tweetID)
			{
				$ret = db_insert("Twitter_Tweet", array('TweetID' => $tweetID, 'Provider' => $provider, 'ItemID' => $itemID, 'LegoID' => $legoID, 'Price' => $salePrice, 'LastPublishTime' => date('Y-m-d H:i:s')), null, true);
				send_Message(NOTIFICATION_RECIPIENT, $message);
			}
			//var_dump($message);
		}
	}

}

?>