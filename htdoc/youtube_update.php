<?php
require_once("db_handler.php");
require_once("twitter_handler.php");
$YoutubeIDs = array();

$push_quota = 2;

$ret = db_query("Youtube_Update", array("YoutubeID", "Pubtime", "Tweet", "Tweettime"), " Pubtime > '0000-00-00 00:00:00' LIMIT 200");

if (!$ret->{'Status'})
{
	foreach ($ret->{'Results'} as $item)
	{
		$idx = $item->{'YoutubeID'};
		if (strtotime($item->{'Tweettime'}) > strtotime('-20 mins'))
		{
			$push_quota--;
		}
		$YoutubeIDs["$idx"] = $item;
	}
}

//var_dump($push_quota);

require_once 'LanguageDetect/LanguageDetect.php';

$l = new Text_LanguageDetect;
$l->setNameMode(2);

$userlist = array("LEGO", "artifexcreation");

foreach ($userlist as $user)
{
	$url = "https://www.youtube.com/feeds/videos.xml?user=".$user;
	$xmlDom = new DOMDocument();
	$xmlDom->load($url);

	$xml = simplexml_import_dom($xmlDom);

	foreach ($xml->entry as $entry)
	{
		$item = new stdClass();
		$item->{'id'} = (string)$entry->children('yt', true)->videoId;
		$media = $entry->children('media', true);

		$item->{'pubtime'} = strtotime($entry->published);
		//$item->{'updated'} = strtotime($entry->updated);
		$item->{'title'} = (string)$media->group->title;
		//$item->{'url'} = (string)$media->group->content->attributes()['url'];
		//$item->{'thumbnail'} = (string)$media->group->thumbnail->attributes()['url'];
		$item->{'description'} = (string)$media->group->description;

		$language = $l->detectSimple($item->{'title'}." ".$item->{'description'});
		if ($language == "en")
		{
			$message = "New video '".$item->{'title'}."' updated by ".$user.": https://youtu.be/".$item->{'id'};


			if (!isset($YoutubeIDs[$item->{'id'}]))
			{
				// not exsit in db
				if ($push_quota)
				{
					//push tweet
					send_Message(NOTIFICATION_RECIPIENT, $message);

					$retID = new_tweet($message);
					if ($retID)
					{
						db_insert("Youtube_Update", array("YoutubeID" => $item->{'id'}, "Title" => $item->{'title'}, "Pubtime" => date('Y-m-d H:i:s', $item->{'pubtime'}), "Tweet" => 1, "TweetID" => $retID, "Tweettime" => date('Y-m-d H:i:s')), null, true);
						$push_quota--;
					}
					else
					{
						db_insert("Youtube_Update", array("YoutubeID" => $item->{'id'}, "Title" => $item->{'title'}, "Pubtime" => date('Y-m-d H:i:s', $item->{'pubtime'}), "Tweet" => 0), null, true);
					}

				}
				else
				{
					db_insert("Youtube_Update", array("YoutubeID" => $item->{'id'}, "Title" => $item->{'title'}, "Pubtime" => date('Y-m-d H:i:s', $item->{'pubtime'}), "Tweet" => 0), null, true);
				}
			}
			else
			{
				// exsit in db, but not publish yet
				$dbitem = $YoutubeIDs[$item->{'id'}];
				if (!$dbitem->{'Tweet'})
				{
					//push tweet
					$retID = new_tweet($message);
					if ($retID)
					{
						db_update("Youtube_Update", array("Tweet" => 1, "TweetID" => $retID, "Tweettime" => date('Y-m-d H:i:s')), array("YoutubeID" => $dbitem->{'YoutubeID'}));
						$push_quota--;
					}

				}
			}
		}

	}
}

unset($l);


?>