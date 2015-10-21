<?php
require_once("db_handler.php");
require_once("twitter_handler.php");
require_once("simple_html_dom.php");
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
				$arrfields["PubDate"] = date('Y-m-d H:i:s', $news->{'PubDate'});
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
	$arrfields["Message"] = "New ".$strType." '".$news->{"Title"}."' updated by ".$news->{"Provider"}.": ".$news->{"Link"};
	$arrfields["PicPath"] = $news->{"PicPath"};
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


function define_source()
{

	$newsSources = array();

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

function get_rss_news($provider, $url)
{
	$ret = new stdClass();
	$ret->{'PubDate'} = time();
	$ret->{'Provider'} = $provider;
	$ret->{'Url'} = $url;
	$ret->{'Type'} = "News-RSS";

	$arrNews = array();
	$xmlDom = new DOMDocument();
	$xmlDom->load($url);

	$xml = simplexml_import_dom($xmlDom);

	foreach ($xml->channel->item as $item)
	{
		$news = new stdClass();
		$news->{'Provider'} = $ret->{'Provider'};
		$news->{'Type'} = $ret->{'Type'};
		$news->{'Title'} = trim((string)$item->title);
		$news->{'Link'} = (string)$item->link;
		$news->{'Hash'} = substr(md5($news->{'Title'}), -12);
		$desc = (string)$item->description;

		switch ($ret->{'Provider'})
		{
			case 'ToysNBricks':
				if (preg_match('/img src=\"([^\"]*)\"/', $desc, $match))
				{
					$news->{'PicPath'} = trim($match[1]);
				}

				$date = new DateTime((string)$item->pubDate, new DateTimeZone("UTC"));
				$news->{'PubDate'} = $date->format('U');
				if ($news->{'PubDate'} < $ret->{'PubDate'})
				{
					$ret->{'PubDate'} = $news->{'PubDate'};
				}

				if (preg_match("/Amazon America currently has/", $desc))
				{
					$news->{'Publish'} = false;
					$news->{'Review'} = false;
				}
				else
				{
					$news->{'Publish'} = true;
					$news->{'Review'} = false;
				}
				break;
			case 'BrickSet';
				if (preg_match('/src=\'([^\"]*)\'/', $desc, $match))
				{
					$news->{'PicPath'} = str_replace(" ", "", trim($match[1]));
				}

				$date = new DateTime($item->pubDate, new DateTimeZone("UTC"));
				$news->{'PubDate'} = $date->format('U');
				if ($news->{'PubDate'} < $ret->{'PubDate'})
				{
					$ret->{'PubDate'} = $news->{'PubDate'};
				}
				if (preg_match('/Review:/', $news->{'Title'}))
				{
					$news->{'Publish'} = true;
					$news->{'Review'} = false;
				}
				else
				{
					$news->{'Publish'} = true;
					$news->{'Review'} = true;
				}
				break;
			default:
				$news->{'PicPath'} = null;
				$news->{'Publish'} = false;
				$news->{'Review'} = false;
				break;
		}
		$arrNews[$news->{'Hash'}] = $news;
	}

	$ret->{'News'} = $arrNews;
	
	return $ret;
}

function get_youtube_update($provider, $url)
{
	if ($provider == "LEGO")
	{
		// Prepare the MSFT Langurage Detector
		try
		{
			//OAuth Url.
			$authUrl = "https://datamarket.accesscontrol.windows.net/v2/OAuth2-13/";
			//Application Scope Url
			$scopeUrl = "http://api.microsofttranslator.com";
			//Application grant type
			$grantType = "client_credentials";

			//Create the AccessTokenAuthentication object.
			$authObj      = new AccessTokenAuthentication();
		    //Get the Access token.
		    $accessToken  = $authObj->getTokens($grantType, $scopeUrl, MS_TRANSLATOR_CLIENTID, MS_TRANSLATOR_CLIENTSECRET, $authUrl);
		    //Create the authorization Header string.
		    $authHeader = "Authorization: Bearer ". $accessToken;
		    
		    //Create the Translator Object.
		    $translatorObj = new HTTPTranslator();
		}
		catch (Exception $e)
		{
		    echo "Exception: " . $e->getMessage() . PHP_EOL;
		}	
	}

	$ret = new stdClass();
	$ret->{'PubDate'} = time();
	$ret->{'Provider'} = $provider;
	$ret->{'Url'} = $url;
	$ret->{'Type'} = "Video-Youtube";

	$arrNews = array();
	
	$xmlDom = new DOMDocument();
	$xmlDom->load($url);

	$xml = simplexml_import_dom($xmlDom);

	foreach ($xml->entry as $entry)
	{
		$media = $entry->children('media', true);

		$item = new stdClass();
		$item->{'Provider'} = $ret->{'Provider'};
		$item->{'Type'} = $ret->{'Type'};
		$item->{'Hash'} = (string)$entry->children('yt', true)->videoId;
		$item->{'Title'} = (string)$media->group->title;
		$item->{'Link'} = "https://youtu.be/".$item->{'Hash'};

		$date = new DateTime($entry->published, new DateTimeZone("UTC"));
		$item->{'PubDate'} = $date->format('U');
		if ($item->{'PubDate'} < $ret->{'PubDate'})
		{
			$ret->{'PubDate'} = $item->{'PubDate'};
		}
		//$item->{'updated'} = strtotime($entry->updated);
		//$item->{'url'} = (string)$media->group->content->attributes()['url'];
		//$item->{'thumbnail'} = (string)$media->group->thumbnail->attributes()['url'];
		$item->{'PicPath'} = null;
		$item->{'Publish'} = true;
		$item->{'Review'} = false;

		if ($provider == "LEGO")
		{			
			$desc = (string)$media->group->description;
			$detectMethodUrl = "http://api.microsofttranslator.com/V2/Http.svc/Detect?text=".urlencode($item->{'Title'}." ".$desc);
			$strResponse = $translatorObj->curlRequest($detectMethodUrl, $authHeader);
			$xmlObj = simplexml_load_string($strResponse);
			foreach((array)$xmlObj[0] as $val)
			{
				$language = $val;
			}
			if ($language <> "en")
			{
				$item->{'Publish'} = false;
			}
			$arrNews[$item->{'Hash'}] = $item;

		}
		else
		{
			$arrNews[$item->{'Hash'}] = $item;
		}
	}
	$ret->{'News'} = $arrNews;

	return $ret;
}

function get_event_update($provider, $url)
{
	$ret = new stdClass();
	$ret->{'PubDate'} = 0;
	$ret->{'Provider'} = $provider;
	$ret->{'Url'} = $url;
	$ret->{'Type'} = "Event-Update";

	$arrNews = array();

	$htmldom = curl_htmldom($url);


	$itemsdom = $htmldom->find('div[id="main-stage"] div[1] div');
	foreach ($itemsdom as $itemdom)
	{
		$picdom = str_get_html($itemdom);

		$news = new stdClass();
		$news->{'Provider'} = $ret->{'Provider'};
		$news->{'Type'} = $ret->{'Type'};
		$news->{'Title'} = $picdom->find('div a img',0)->title;
		$path = $picdom->find('div a',0)->href;
		$path = substr($path, 0, strpos($path, ";"));
		$news->{'Link'} = "http://shop.lego.com".$path;
		if (empty($news->{'Title'}))
		{
			$news->{'Title'} = str_replace("-", " ", basename($path));
		}
		$news->{'PicPath'} = $picdom->find('div a img',0)->src;
		$news->{'Hash'} = substr(md5($news->{'PicPath'}), -12);
		$news->{'Publish'} = true;
		$news->{'Review'} = false;
		$news->{'PubDate'} = time();

		$arrNews[$news->{'Hash'}] = $news;
	}
	$ret->{'News'} = $arrNews;
	
	return $ret;
}


function curl_htmldom($url)
{
	$ch = curl_init(); 
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36');

	$curlResponse = curl_exec($ch); 	
	$curlErrno = curl_errno($ch);
	if ($curlErrno)
	{
		$curlError = curl_error($ch);
		throw new Exception($curlError);
	}
	curl_close($ch);

	$htmldom = str_get_html($curlResponse);
	if (isset($htmldom))
	{
		return $htmldom;
	}
	else
	{
		return false;
	}
}

?>