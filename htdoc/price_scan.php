<?php
require_once("crawlers.php");
require_once("db_handler.php");
require_once("twitter_handler.php");

price_scan($argv[1]);

function price_scan($provider)
{
	$tlbName = $provider."_Item";
	if ($provider == "Amazon")
	{
		$idName = "ASIN";
	}
	else
	{
		$idName = $provider."ID";

	}
	$ret = db_query($tlbName." LEFT JOIN DB_Set ON ".$tlbName.".LegoID = DB_Set.LegoID LEFT JOIN DB_Theme ON DB_Set.ThemeID = DB_Theme.ThemeID", array($idName, $tlbName.".LegoID AS LegoID", "ETitle AS Title", "ETheme AS Theme", $tlbName.".Availability AS Availability", "Price", "USPrice AS MSRP"), null);
	if (!$ret->{'Status'})
	{
		foreach ($ret->{'Results'} as $item)
		{
			$idx = $item->{$idName};
			$dbItems["$idx"] = $item;
		}
	}
	echo "[Info][".date('Y-m-d H:i:s')."] ".count($dbItems)." items loaded from DB Table $tlbName\n";

	$ret = crawl_price($provider);

	if ($ret->{'ItemCount'})
	{
		$crawlItems = $ret->{'Items'};
	}
	echo "[Info][".date('Y-m-d H:i:s')."] ".count($crawlItems)." items crawled from page ".$ret->{'URL'}."\n";

	$arrNoupdate = array();
	foreach ($crawlItems as $crawlitem)
	{
		$itemID = $crawlitem->{$idName};
		if (isset($dbItems["$itemID"]))
		{
			$dbitem = $dbItems["$itemID"];
			$msrp = $dbitem->{'MSRP'};
			$price = $crawlitem->{'Price'};
			if ($msrp && $price)
			{
				$rate = 100 - round($price / $msrp * 100);
			}
			else
			{
				$rate = null;
			}

			$arrfields = array();
			$arrProp = array("Price", "Availability");
			foreach ($arrProp as $prop)
			{
				if (!empty($crawlitem->{"$prop"}) && $crawlitem->{"$prop"} <> $dbitem->{"$prop"})
				{
					$arrfields[$prop] = $crawlitem->{$prop};

				}
			}
			if (!empty($arrfields))
			{
				$arrfields['LastUpdateTime'] = gmdate('Y-m-d H:i:s');

				$ret = db_update($tlbName, $arrfields, array($idName => $itemID));
				if (!$ret->{'Status'})
				{
					unset($arrfields['LastUpdateTime']);
					$strupdate = "";
					foreach ($arrfields as $prop => $value)
					{
						$strupdate .= $prop."[".$dbitem->{$prop}."=>".$value."], ";
					}
					$strupdate = trim($strupdate, ", ");
					echo "[Info][".date('Y-m-d H:i:s')."] ".$tlbName." ".$dbitem->{'LegoID'}." - ".$dbitem->{'Title'}." updated: ".$strupdate." ".get_url_by_itemID($provider, $itemID)."\n";
					//send_Message(NOTIFICATION_RECIPIENT, "Toysrus_Item ".$dbitem->{'LegoID'}." - ".$dbitem->{'Title'}." updated: ".$strupdate." www.toysrus.com/product/index.jsp?productId=$ToysrusID");
				}

				if (!empty($rate) && $rate > 25 && ($crawlitem->{"Availability"} == "Available" || $crawlitem->{"Availability"} == "Shipping Only") && !empty(($dbitem->{'LegoID'})))
				{
					$ret = db_query("Twitter_Pool", array("TweetID", "Price"), "Provider='".$provider."' AND ItemID='".$itemID."' AND AddTime > '".gmdate('Y-m-d H:i:s', strtotime('-7 days'))."' ORDER BY AddTime DESC");

					if (!$ret->{'Status'} && $ret->{'Results'})
					{
						$tweetID = $ret->{'Results'}[0]->{"TweetID"};
						$lastPrice = $ret->{'Results'}[0]->{"Price"};
						$lastRate = 100 - round($lastPrice / $msrp * 100);

						if ($rate - $lastRate > 2)
						{
							$message = "[".$dbitem->{'LegoID'}."] ".$dbitem->{'Theme'}." - ".$$dbitem->{'Title'}." was reduced even further to $".$price." (".$rate."% off from reg.$".$msrp.") ";
							//retweet.
							add_deal_tweet_pool($provider, $message, $itemID, $dbitem->{'LegoID'}, $price);
							//send_Message(NOTIFICATION_RECIPIENT, $message);
							
							
						}
						else
						{
							$message = "[".$dbitem->{'LegoID'}."] is on sale for $".$price." (".$rate."% off from reg.$".$msrp.") has been posted by ".$tweetID;
						}
					}
					else
					{
						$message = "[".$dbitem->{'LegoID'}."] ".$dbitem->{'Theme'}." - ".$dbitem->{'Title'}." is on sale for $".$price." (".$rate."% off from reg.$".$msrp.") ";

						//new tweet.
						add_deal_tweet_pool($provider, $message, $itemID, $dbitem->{'LegoID'}, $price);

					}
					//echo "[Info][".date('Y-m-d H:i:s')."] ".$message."\n";

				}
			}
			else
			{
				array_push($arrNoupdate, $itemID);
			}
		}
		elseif (!empty($crawlitem->{'LegoID'}))
		{
			$legoID = $crawlitem->{'LegoID'};
			db_insert($tlbName, array("LegoID" => $legoID, $idName => $itemID), null, true);

			echo "[Info][".date('Y-m-d H:i:s')."] New item added by legoid: ".$legoID." - ".$crawlitem->{'Title'}." ".get_url_by_itemID($provider, $itemID)."\n";
			//send_Message(NOTIFICATION_RECIPIENT, "New Toysrus_Item ".$legoID." - ".$crawlitem->{'Title'}." listed on www.toysrus.com/product/index.jsp?productId=".$ToysrusID);
		}
		else
		{
			// try to match the legoid by title.
			$ret = search_legoid(array("Title" => $crawlitem->{'Title'}));
			if (isset($ret->{'MatchID'}))
			{
				$legoID = $ret->{'MatchID'};
				db_insert($tlbName, array("LegoID" => $legoID, $idName => $itemID), null, true);
			}
			else
			{
				$legoID = null;
				db_insert($tlbName, array("LegoID" => "", $idName => $itemID), null, true);
			}

			echo "[Info][".date('Y-m-d H:i:s')."] New item added by title: ".$legoID." - ".$crawlitem->{'Title'}." ".get_url_by_itemID($provider, $itemID)."\n";
			//send_Message(NOTIFICATION_RECIPIENT, "New ".$tlbName." ".$legoID." - ".$crawlitem->{'Title'}." listed on ".get_url_by_itemID($provider, $itemID);
		}
	}

	if (!empty($arrNoupdate))
	{
		$ret = db_update($tlbName, array("LastUpdateTime" => gmdate('Y-m-d H:i:s')), $idName." IN (".implode(",", $arrNoupdate).")");
		echo "[Info][".date('Y-m-d H:i:s')."] No update for ".count($arrNoupdate)." items\n";
	}
}

function add_deal_tweet_pool($provider, $message, $itemID, $legoID, $price)
{
	$arrfields = array();
	$arrfields["Provider"] = $provider;
	$arrfields["Type"] = "Deals";
	$arrfields["ContentID"] = md5($provider.$message);
	//$arrfields["PicPath"] = $news->{"PicPath"};
	$arrfields["ItemID"] = $itemID;
	$arrfields["LegoID"] = $legoID;
	$arrfields["Price"] = $price;

	if (!empty($arrfields["PicPath"]))
	{
		$maxlen = 140 - 23 - 22;
	}
	else
	{
		$maxlen = 140 - 22;
	}

	if (strlen($message) > $maxlen)
	{
		echo "[Error][".date('Y-m-d H:i:s')."] Message is too long: ".$message."\n";
		return false;
	}
	else
	{
		$arrfields["Message"] = $message.get_url_by_itemID($provider, $itemID);
		$ret = db_insert("Twitter_Pool", $arrfields, null, false);
		if (!$ret->{'Status'})
		{
			echo "[Info][".date('Y-m-d H:i:s')."] Publishing to Twitter_Pool: [".$arrfields["ContentID"]."] ".$arrfields["Message"]."\n";
			return true;
		}
		else
		{
			echo "[Error][".date('Y-m-d H:i:s')."] Publishing failed: ";
			var_dump($ret);
			return false;
		}
	}
}


?>