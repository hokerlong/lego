<?php
require_once("crawlers.php");
require_once("db_handler.php");
require_once("twitter_handler.php");

$ToysrusIDs = array();

$ret = db_query("Toysrus_Item LEFT JOIN DB_Set ON Toysrus_Item.LegoID = DB_Set.LegoID LEFT JOIN DB_Theme ON DB_Set.ThemeID = DB_Theme.ThemeID", array("ToysrusID", "Toysrus_Item.LegoID AS LegoID", "ETitle AS Title", "ETheme AS Theme", "Toysrus_Item.Availability AS Availability", "Price", "USPrice AS MSRP"), null);
if (!$ret->{'Status'})
{
	foreach ($ret->{'Results'} as $item)
	{
		$idx = $item->{'ToysrusID'};
		$ToysrusIDs["$idx"] = $item;
	}
}

echo "[Info][".date('Y-m-d H:i:s')."] ".count($ToysrusIDs)." items loaded from DB Table Toysrus_Item\n";
$ret = crawl_toysrus();
if ($ret->{'ItemCount'})
{
	$ToysrusItems = $ret->{'Items'};
}

echo "[Info][".date('Y-m-d H:i:s')."] ".count($ToysrusItems)." items crawled from page ".$ret->{'URL'}."\n";

$arrNoupdate = array();
foreach ($ToysrusItems as $item)
{
	$ToysrusID = $item->{'ToysrusID'};
	if (isset($ToysrusIDs["$ToysrusID"]))
	{
		$info = $ToysrusIDs["$ToysrusID"];
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
			$arrfields['LastUpdateTime'] = gmdate('Y-m-d H:i:s');

			$ret = db_update("Toysrus_Item", $arrfields, array("ToysrusID" => $ToysrusID));
			if (!$ret->{'Status'})
			{
				unset($arrfields['LastUpdateTime']);
				$strupdate = "";
				foreach ($arrfields as $prop => $value)
				{
					$strupdate .= $prop."[".$info->{$prop}."=>".$value."], ";
				}
				$strupdate = trim($strupdate, ", ");
				//echo "[Info][".date('Y-m-d H:i:s')."] Toysrus_Item ".$info->{'LegoID'}." - ".$info->{'Title'}." updated: ".$strupdate." www.toysrus.com/product/index.jsp?productId=$ToysrusID\n";
				//send_Message(NOTIFICATION_RECIPIENT, "Toysrus_Item ".$info->{'LegoID'}." - ".$info->{'Title'}." updated: ".$strupdate." www.toysrus.com/product/index.jsp?productId=$ToysrusID");
			}
		}
		else
		{
			array_push($arrNoupdate, $ToysrusID);
		}

		if (!empty($rate) && $rate < 76 && $item->{"Availability"} <> "Sold Out" && !empty(($info->{'LegoID'})))
		{
			$ret = publish_SaleMessage("toysrus.com", $ToysrusID, $price, $info->{'LegoID'});
			if (!$ret->{'Status'})
			{
				echo "[Info][".date('Y-m-d H:i:s')."] ".$info->{'LegoID'}." on sale for $".$price." (".$rate."% off from reg. $".$info->{'MSRP'}.") www.toysrus.com/product/index.jsp?productId=".$ToysrusID."\n";
			}
			else
			{
				echo "[Warning][".date('Y-m-d H:i:s')."] Failed to publish tweet due to ".$ret->{'Message'}.": ".$info->{'LegoID'}." on sale for $".$price." (".$rate."% off from reg. $".$info->{'MSRP'}.")\n";
			}
		}
	}
	elseif (!empty($item->{'LegoID'}))
	{
		$legoID = $item->{'LegoID'};
		db_insert("Toysrus_Item", array("LegoID" => $legoID, "ToysrusID" => $ToysrusID), null, true);

		echo "[Info][".date('Y-m-d H:i:s')."] New item added by legoid: ".$legoID." - ".$item->{'Title'}." www.toysrus.com/product/index.jsp?productId=".$ToysrusID."\n";
		send_Message(NOTIFICATION_RECIPIENT, "New Toysrus_Item ".$legoID." - ".$item->{'Title'}." listed on www.toysrus.com/product/index.jsp?productId=".$ToysrusID);

	}
	else
	{
		// try to match the legoid by title.
		$ret = search_legoid(array("Title" => $item->{'Title'}));
		if (isset($ret->{'MatchID'}))
		{
			$legoID = $ret->{'MatchID'};
			db_insert("Toysrus_Item", array("LegoID" => $legoID, "ToysrusID" => $ToysrusID), null, true);
		}
		else
		{
			$legoID = null;
			db_insert("Toysrus_Item", array("LegoID" => "", "ToysrusID" => $ToysrusID), null, true);
		}

		echo "[Info][".date('Y-m-d H:i:s')."] New item added by title: ".$legoID." - ".$item->{'Title'}." www.toysrus.com/product/index.jsp?productId=".$ToysrusID."\n";
		send_Message(NOTIFICATION_RECIPIENT, "New Toysrus_Item ".$legoID." - ".$item->{'Title'}." listed on www.toysrus.com/product/index.jsp?productId=".$ToysrusID);
	}
}

if (!empty($arrNoupdate))
{
	$ret = db_update("Toysrus_Item", array("LastUpdateTime" => gmdate('Y-m-d H:i:s')), "ToysrusID IN (".implode(",", $arrNoupdate).")");
	echo "[Info][".date('Y-m-d H:i:s')."] No update for ".count($arrNoupdate)." items\n";
}

?>