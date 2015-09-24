<?php
require_once("crawlers.php");
require_once("db_handler.php");
require_once("twitter_handler.php");

$WalmartIDs = array();

$ret = db_query("Walmart_Item LEFT JOIN DB_Set ON Walmart_Item.LegoID = DB_Set.LegoID LEFT JOIN DB_Theme ON DB_Set.ThemeID = DB_Theme.ThemeID", array("WalmartID", "Walmart_Item.LegoID AS LegoID", "ETitle AS Title", "ETheme AS Theme", "Walmart_Item.Availability AS Availability", "Price", "USPrice AS MSRP"), null);
if (!$ret->{'Status'})
{
	foreach ($ret->{'Results'} as $item)
	{
		$idx = $item->{'WalmartID'};
		$WalmartIDs["$idx"] = $item;
	}
}

echo "[Info][".date('Y-m-d H:i:s')."] ".count($WalmartIDs)." items loaded from DB Table Walmart_Item\n";
$ret = crawl_walmart();
if ($ret->{'ItemCount'})
{
	$WalmartItems = $ret->{'Items'};
}

echo "[Info][".date('Y-m-d H:i:s')."] ".count($WalmartItems)." items crawled from page ".$ret->{'URL'}."\n";

if (count($WalmartItems) < count($WalmartIDs) * 0.8)
{
	send_Message(NOTIFICATION_RECIPIENT, "Only ".count($WalmartItems)." items crawled from walmart.com, out of ".count($WalmartIDs)." items in DB.");
}
$arrNoupdate = array();
foreach ($WalmartItems as $item)
{
	$walmartID = $item->{'WalmartID'};
	if (isset($WalmartIDs["$walmartID"]))
	{
		$info = $WalmartIDs["$walmartID"];
		$msrp = $info->{'MSRP'};
		$price = $item->{'Price'};
		if ($msrp && $price)
		{
			$rate = round($price / $msrp * 100, 2);
		}
		else
		{
			$rate = null;
		}

		$arrfields = array();
		$arrProp = array("Price", "Availability");
		foreach ($arrProp as $prop)
		{
			if (!empty($item->{"$prop"}) && $item->{"$prop"} <> $info->{"$prop"})
			{
				$arrfields[$prop] = $item->{$prop};
			}
		}
		if (!empty($arrfields))
		{
			$arrfields['LastUpdateTime'] = date('Y-m-d H:i:s');

			$ret = db_update("Walmart_Item", $arrfields, array("WalmartID" => $walmartID));
			if (!$ret->{'Status'})
			{
				unset($arrfields['LastUpdateTime']);
				$strupdate = "";
				foreach ($arrfields as $prop => $value)
				{
					$strupdate .= $prop."[".$info->{$prop}."=>".$value."], ";
				}
				$strupdate = trim($strupdate, ", ");
				echo "[Info][".date('Y-m-d H:i:s')."] Walmart_Item ".$info->{'LegoID'}." - ".$info->{'Title'}." updated: ".$strupdate." @ http://www.walmart.com/ip/$walmartID\n";
				send_Message(NOTIFICATION_RECIPIENT, "Walmart_Item ".$info->{'LegoID'}." - ".$info->{'Title'}." updated: ".$strupdate." @ http://www.walmart.com/ip/$walmartID");
			}
		}
		else
		{
			array_push($arrNoupdate, $walmartID);
		}

		/*
		if ($rate > 100 || $rate < 50)
		{
			echo "[Warning][".date('Y-m-d H:i:s')."] $walmartID [".$item->{'Title'}."] seems not mapping to ".$info->{'LegoID'}." [".$info->{'Title'}."].\n";
		}
		*/
	}
	elseif (!empty($item->{'LegoID'}))
	{
		$legoID = $item->{'LegoID'};
		db_insert("Walmart_Item", array("LegoID" => $legoID, "WalmartID" => $walmartID), null, true);

		echo "[Info][".date('Y-m-d H:i:s')."] New item added by matching legoid on www.walmart.com/ip/".$walmartID." ".$legoID." - ".$item->{'Title'}."\n";
		send_Message(NOTIFICATION_RECIPIENT, "New Walmart_Item ".$legoID." - ".$item->{'Title'}." listed on www.walmart.com/ip/".$walmartID);

	}
	else
	{
		// try to match the legoid by title.
		$ret = search_legoid(array("Title" => $item->{'Title'}));
		if (isset($ret->{'MatchID'}))
		{
			$legoID = $ret->{'MatchID'};
			db_insert("Walmart_Item", array("LegoID" => $legoID, "WalmartID" => $walmartID), null, true);
		}
		else
		{
			db_insert("Walmart_Item", array("LegoID" => "", "WalmartID" => $walmartID), null, true);
		}

		echo "[Info][".date('Y-m-d H:i:s')."] New item added by matching title on www.walmart.com/ip/".$walmartID." ".$legoID." - ".$item->{'Title'}."\n";
		send_Message(NOTIFICATION_RECIPIENT, "New Walmart_Item ".$legoID." - ".$item->{'Title'}." listed on www.walmart.com/ip/".$walmartID);
	}
}

if (!empty($arrNoupdate))
{
	$ret = db_update("Walmart_Item", array("LastUpdateTime" => date('Y-m-d H:i:s')), "WalmartID IN (".implode(",", $arrNoupdate).")");
	//var_dump($ret);
}

?>