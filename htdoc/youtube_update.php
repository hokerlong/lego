<?php
require_once("db_handler.php");
require_once("twitter_handler.php");
require_once("LanguageDetect/LanguageDetect.php");

$userlist = array("LEGO", "artifexcreation");
$l = new Text_LanguageDetect;
$l->setNameMode(2);

$videos = array();
$pubtime = strtotime("Now");
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

		$item->{'user'} = $user;

		$date = new DateTime($entry->published, new DateTimeZone("UTC"));

		$item->{'pubtime'} = $date->format('U');
		if ($item->{'pubtime'} < $pubtime)
		{
			$pubtime = $item->{'pubtime'};
		}
		//$item->{'updated'} = strtotime($entry->updated);
		//$item->{'url'} = (string)$media->group->content->attributes()['url'];
		//$item->{'thumbnail'} = (string)$media->group->thumbnail->attributes()['url'];

		$item->{'title'} = (string)$media->group->title;
		$item->{'description'} = (string)$media->group->description;

		$language = $l->detectSimple($item->{'title'}." ".$item->{'description'});
		if ($language == "en")
		{
			array_push($videos, $item);
		}

	}
}
unset($l);

$YoutubeIDs = array();

$push_quota = 2;

$ret = db_query("Youtube_Update", array("YoutubeID", "Pubtime", "Tweet", "Tweettime"), " Pubtime >= '".date('Y-m-d H:i:s', $pubtime)."' LIMIT 200");

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

echo "[Info][".date('Y-m-d H:i:s')."] ".count($videos)." items crawled from RSS since ".date('Y-m-d H:i:s', $pubtime).", while ".count($YoutubeIDs)." items matched in DB.\n";


foreach ($videos as $video)
{
	if (isset($YoutubeIDs[$video->{'id'}]))
	{
		$dbitem = $YoutubeIDs[$video->{'id'}];

		// if dbitem->{'Tweet'} = 0, not publish yet, try to publish. dbitem->{'Tweet'} = 1, has been published.
		if (intval($dbitem->{'Tweet'}) <> 1 && $push_quota > 0)
		{
			//echo "In DB item publishing: ".$video->{'id'}." [".$video->{'title'}."]\n";
			if (video_publish($video, true))
			{
				$push_quota--;
			}
		}
	}
	elseif ($push_quota)
	{
		//echo "New item publishing: ".$video->{'id'}." [".$video->{'title'}."]\n";

		if (video_publish($video, true))
		{
			$push_quota--;
		}
	}
	else
	{
		//echo "New item update to db: ".$video->{'id'}." [".$video->{'title'}."]\n";
		video_publish($video, false);
	}

}

function video_publish($video, $publish)
{
	if ($publish)
	{
		$message = "New video '".$video->{'title'}."' updated by ".$video->{'user'}.": https://youtu.be/".$video->{'id'};

		$tweetID = new_tweet($message);
		if ($tweetID)
		{
			echo "DB Insert(pub) ".$video->{'id'}."=1\n";
			$ret = db_insert("Youtube_Update", array("YoutubeID" => $video->{'id'}, "Title" => $video->{'title'}, "Pubtime" => date('Y-m-d H:i:s', $video->{'pubtime'}), "Tweet" => 1, "TweetID" => $tweetID, "Tweettime" => date('Y-m-d H:i:s')), null, true);
			//var_dump($ret);
			return true;
		}
		else
		{
			//echo "DB Insert(pubfailed) ".$video->{'id'}."=0\n";
			//$ret = db_insert("Youtube_Update", array("YoutubeID" => $video->{'id'}, "Title" => $video->{'title'}, "Pubtime" => date('Y-m-d H:i:s', $video->{'pubtime'})), null, true);
			//var_dump($ret);
			return false;
		}	
	}
	else
	{
		echo "DB Insert(else) ".$video->{'id'}."=0\n";
		$ret = db_insert("Youtube_Update", array("YoutubeID" => $video->{'id'}, "Title" => $video->{'title'}, "Pubtime" => date('Y-m-d H:i:s', $video->{'pubtime'}), "Tweet" => 0), null, true);
		//var_dump($ret);
		return true;
	}	
}
?>