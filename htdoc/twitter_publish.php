<?php
require_once("db_handler.php");
require_once("twitter_handler.php");


$ret = db_query("Twitter_Pool", array("PoolID", "Message", "PicPath", "MediaID"), "TweetID IS NULL ORDER BY AddTime LIMIT 1");

if (!$ret->{'Status'})
{
	$item = $ret->{'Results'}[0];

	if (!empty($item))
	{
		if ($item->{'MediaID'} == null)
		{
			if (!empty($item->{'PicPath'}))
			{
				$mediaID = upload_pic($item->{'PicPath'});

				if($mediaID)
				{
					$ret = db_update("Twitter_Pool", array("MediaID" => $mediaID), array("PoolID" => $item->{'PoolID'}));
					if ($ret->{'Status'})
					{
						echo "[".date('Y-m-d H:i:s')."] Failed to update Twitter_Pool: MediaID = '".$mediaID."' while PoolID = ".$item->{'PoolID'}."\n";
						var_dump($ret);
						exit(1);
					}
				}
			}
			else
			{
				$mediaID = null;
			}	
		}
		else
		{
			$mediaID = $item->{'MediaID'};
		}

		$tweetID = tweet_with_pic($item->{'Message'}, $mediaID);
		if ($tweetID)
		{
			$ret = db_update("Twitter_Pool", array("TweetID" => $tweetID, "PubTime" => date('Y-m-d H:i:s')), array("PoolID" => $item->{'PoolID'}));
			if (!$ret->{'Status'})
			{
				echo "[".date('Y-m-d H:i:s')."] Published tweet successfully [".$tweetID.":".$mediaID."]: ".$item->{'Message'}."\n";
			}
			else
			{
				echo "[".date('Y-m-d H:i:s')."] Failed to update Twitter_Pool: TweetID = '".$tweetID."' while PoolID = ".$item->{'PoolID'}."\n";
				var_dump($ret);
			}
		}
		else
		{
			$ret = db_update("Twitter_Pool", array("TweetID" => 0, "PubTime" => date('Y-m-d H:i:s')), array("PoolID" => $item->{'PoolID'}));
			echo "[".date('Y-m-d H:i:s')."] Failed to publish to twitter API: ^^\n".var_dump($item);
		}
	}
	else
	{
		//echo "[".date('Y-m-d H:i:s')."] No more new content to publish.\n";
	}

}
else
{
	echo "[".date('Y-m-d H:i:s')."] Failed to get new content from Twitter_Pool: \n";
	var_dump($ret);
}


?>