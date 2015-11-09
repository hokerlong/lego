<?php
require_once("crawlers.php");
require_once("db_handler.php");
require_once("twitter_handler.php");
require_once("languageDetect.php");

foreach (define_source() as $source)
{
	switch ($source->{'Type'})
	{
		case 'News-RSS':
			$newsResult = get_rss_news($source->{'Provider'}, $source->{'Url'});
			break;
		case 'Video-Youtube':
			$newsResult = get_youtube_update($source->{'Provider'}, $source->{'Url'});
			break;		
		case 'Event-Update':
			$newsResult = get_event_update($source->{'Provider'}, $source->{'Url'});
			break;
		default:
			# code...
			break;
	}
	
	echo "[".date('Y-m-d H:i:s')."] ".count($newsResult->{'News'})." items crawled from ".$newsResult->{'Provider'}." since ".date('Y-m-d H:i:s', $newsResult->{'PubDate'})."\n";

	if(!empty($newsResult->{'News'}))
	{
		$ret = db_query("Twitter_News", array("Hash", "Status"), "Provider = '".$newsResult->{'Provider'}."' AND Type = '".$newsResult->{'Type'}."' AND PubDate >= FROM_UNIXTIME(".$newsResult->{'PubDate'}.")");
		//var_dump($ret->{'Query'});

		//Status = N: do not publish; P: published; Y: reviewed, should publish; 

		if (!$ret->{'Status'})
		{
			foreach ($ret->{'Results'} as $item)
			{
				$idx = $item->{'Hash'};
				$NewsHash["$idx"] = $item->{'Status'};
			}
		}
		foreach ($newsResult->{'News'} as $hash => $news)
		{
			if (!isset($NewsHash["$hash"]))
			{
				//new content
				$arrfields = array();
				$arrfields["Provider"] = $newsResult->{'Provider'};
				$arrfields["Type"] = $newsResult->{'Type'};

				$arrProp = array("Hash", "Title", "Link", "PicPath", "Publish", "Review");
				foreach ($arrProp as $prop)
				{
					$arrfields[$prop] = $news->{$prop};
				}
				$arrfields["PubDate"] = gmdate('Y-m-d H:i:s', $news->{'PubDate'});
				$ret = db_insert("Twitter_News", $arrfields, null, true);

				if ($news->{'Publish'} == true && $news->{'Review'} == false)
				{
					//publish directly
					add_news_tweet_pool($news);

				}
				elseif ($news->{'Publish'} == true && $news->{'Review'} == true)
				{
					//send review notification
					$mesg = "News pending on review: ".date('Y-m-d H:i:s', $news->{'PubDate'})."(".$news->{'PubDate'}.")[".$news->{'Hash'}."] ".$news->{'Title'};
					
					echo "[".date('Y-m-d H:i:s')."] ".$mesg."\n";
					send_Message(NOTIFICATION_RECIPIENT, $mesg." http://ec2.lelemeng.com/review.php?id=".$news->{'Hash'});
				}
			}
			elseif ($NewsHash["$hash"] == "Y")
			{
				add_news_tweet_pool($news);
			}
			
		}
	}
}

function define_source()
{

	$newsSources = array();

	$source = new stdClass();
	$source->{'Provider'} = "TheBrothersBrick";
	$source->{'Url'} = "http://feeds.feedburner.com/TheBrothersBrick";
	$source->{'Type'} = "News-RSS";

	array_push($newsSources, $source);

	$source = new stdClass();
	$source->{'Provider'} = "ToysNBricks";
	$source->{'Url'} = "http://toysnbricks.com/feed/";
	$source->{'Type'} = "News-RSS";

	array_push($newsSources, $source);

	$source = new stdClass();
	$source->{'Provider'} = "BrickSet";
	$source->{'Url'} = "http://brickset.com/feed";
	$source->{'Type'} = "News-RSS";

	array_push($newsSources, $source);

	$source = new stdClass();
	$source->{'Provider'} = "Shop.LEGO-US";
	$source->{'Url'} = "http://shop.lego.com/en-US/";
	$source->{'Type'} = "Event-Update";

	array_push($newsSources, $source);

	$source = new stdClass();
	$source->{'Provider'} = "LEGO";
	$source->{'Url'} = "https://www.youtube.com/feeds/videos.xml?user=".$source->{'Provider'};
	$source->{'Type'} = "Video-Youtube";

	array_push($newsSources, $source);

	$source = new stdClass();
	$source->{'Provider'} = "artifexcreation";
	$source->{'Url'} = "https://www.youtube.com/feeds/videos.xml?user=".$source->{'Provider'};
	$source->{'Type'} = "Video-Youtube";

	array_push($newsSources, $source);

	$source = new stdClass();
	$source->{'Provider'} = "brickbuilder23";
	$source->{'Url'} = "https://www.youtube.com/feeds/videos.xml?user=".$source->{'Provider'};
	$source->{'Type'} = "Video-Youtube";

	array_push($newsSources, $source);

	return $newsSources;
}

function add_news_tweet_pool($news)
{
	$arrfields = array();
	$arrfields["Provider"] = $news->{"Provider"};
	$arrfields["Type"] = $news->{"Type"};
	$arrfields["ContentID"] = $news->{"Hash"};
	switch ($news->{"Type"})
	{
		case 'News-RSS':
			$strType = "article";
			break;
		case 'Video-Youtube':
			$strType = "video";
			break;	
		default:
			$strType = "event";
			break;
	}
	$arrfields["PicPath"] = $news->{"PicPath"};
	$mesg = "New ".$strType." '".$news->{"Title"}."' updated by ".$news->{"Provider"}.": ";
	//$arrfields["Message"] = "New ".$strType." '".$news->{"Title"}."' updated by ".$news->{"Provider"}.": ".$news->{"Link"};

	if (!empty($news->{"PicPath"}))
	{
		$maxlen = 140 - 23 - 22;
	}
	else
	{
		$maxlen = 140 - 22;
	}

	if (strlen($mesg) > $maxlen)
	{
		$mesg = "'".$news->{"Title"}."' updated by ".$news->{"Provider"}.": ";
		if (strlen($mesg) > $maxlen)
		{
			$mesg = "'".$news->{"Title"}."' by ".$news->{"Provider"}.": ";
		}
		if (strlen($mesg) > $maxlen)
		{
			$mesg = "'".$news->{"Title"}."': ";
		}
	}
	$arrfields["Message"] = $mesg.$news->{"Link"};
	$ret = db_insert("Twitter_Pool", $arrfields, null, false);
	if (!$ret->{'Status'})
	{
		$ret = db_update("Twitter_News", array("Status" => "P"), array("Hash" => $news->{'Hash'}));
		if (!$ret->{'Status'})
		{
			echo "[".date('Y-m-d H:i:s')."] Publishing to Twitter_Pool: ".date('Y-m-d H:i:s', $news->{'PubDate'})."(".$news->{'PubDate'}.")[".$news->{'Hash'}."] ".$arrfields["Message"]."\n";
			return true;
		}
		else
		{
			echo "[".date('Y-m-d H:i:s')."] Publishing failed: ";
			var_dump($ret);
			return false;
		}
	}
	else
	{
		echo "[".date('Y-m-d H:i:s')."] Publishing failed: ";
		var_dump($ret);
		return false;
	}
}
?>