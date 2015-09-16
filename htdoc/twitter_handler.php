<?php

require_once("conn.php");
require_once("twitteroauth/autoload.php");

use TwitterOAuth\TwitterOAuth;

function send_Message($recipient, $text)
{
	$connection = new TwitterOAuth(TWITTER_KEY, TWITTER_SECRET, TWITTER_TOKEN, TWITTER_TOKEN_SECRET);
	$content = $connection->post("direct_messages/new", array("screen_name" => $recipient, "text" => $text));
	if (isset($content->errors))
	{
		return $content->errors[0]->code;
	}
	else
	{
		return true;
	}

}

function send_tweet()
{
	//$content = $connection->get("followers/list", array("screen_name" => "LEGO_Group"));

	//$content = $connection->post("statuses/update", array("status" => "hello world, this is another test: http://amzn.to/1EAZ3Ge"));
	//var_dump($connection);
}

?>