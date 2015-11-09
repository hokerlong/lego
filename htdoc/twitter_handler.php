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

function upload_pic($pic)
{
	$connection = new TwitterOAuth(TWITTER_KEY, TWITTER_SECRET, TWITTER_TOKEN, TWITTER_TOKEN_SECRET);
	$response = $connection->upload("media/upload", array("media" => $pic));
	if (isset($response->errors))
	{
		var_dump($response->errors);
		return false;
	}
	else
	{
		return $response->media_id;
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

function tweet_with_pic($message, $media_id)
{
	$connection = new TwitterOAuth(TWITTER_KEY, TWITTER_SECRET, TWITTER_TOKEN, TWITTER_TOKEN_SECRET);
	if (empty($media_id))
	{
		$response = $connection->post("statuses/update", array("status" => $message));
	}
	else
	{
		$response = $connection->post("statuses/update", array("status" => $message, "media_ids" => $media_id));
	}
	
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


?>