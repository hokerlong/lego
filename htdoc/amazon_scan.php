<?php
require_once("crawlers.php");
require_once("db_handler.php");
require_once("twitter_handler.php");

$ASINs = array();

$ret = db_query("Amazon_Item LEFT JOIN DB_Set ON Amazon_Item.LegoID = DB_Set.LegoID LEFT JOIN DB_Theme ON DB_Set.ThemeID = DB_Theme.ThemeID", array("ASIN", "Amazon_Item.LegoID AS LegoID", "ETitle AS Title", "ETheme AS Theme", "Amazon_Item.Availability AS Availability", "Price", "USPrice AS MSRP"), null);
if (!$ret->{'Status'})
{
	foreach ($ret->{'Results'} as $item)
	{
		$idx = $item->{'ASIN'};
		$ASINs["$idx"] = $item;
	}
}

echo "[Info][".date('Y-m-d H:i:s')."] ".count($ASINs)." items loaded from DB Table Amazon_Item\n";
$ret = crawl_amazon();
if ($ret->{'ItemCount'})
{
	$AmazonItems = $ret->{'Items'};
}


$arrNoupdate = array();
foreach ($AmazonItems as $item)
{
	$ASIN = $item->{'ASIN'};
	if (isset($ASINs["$ASIN"]))
	{
		$info = $ASINs["$ASIN"];
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

			$ret = db_update("Amazon_Item", $arrfields, array("ASIN" => $ASIN));
			if (!$ret->{'Status'})
			{
				unset($arrfields['LastUpdateTime']);
				$strupdate = "";
				foreach ($arrfields as $prop => $value)
				{
					$strupdate .= $prop."[".$info->{$prop}."=>".$value."], ";
				}
				$strupdate = trim($strupdate, ", ");
				echo "[Info][".date('Y-m-d H:i:s')."] Amazon_Item ".$info->{'LegoID'}." - ".$info->{'Title'}." updated: ".$strupdate." @ www.amazon.com/gp/product/$ASIN\n";
				//send_Message(NOTIFICATION_RECIPIENT, "Amazon_Item ".$info->{'LegoID'}." - ".$info->{'Title'}." updated: ".$strupdate." @ www.amazon.com/gp/product/$ASIN");
			}
		}
		else
		{
			array_push($arrNoupdate, $ASIN);
		}

		if ($rate < 76 && ($item->{"Availability"} == "Pickup Only" || $item->{"Availability"} == "Available") && !empty(($info->{'LegoID'})))
		{
			echo "[Info][".date('Y-m-d H:i:s')."] ".$info->{'LegoID'}." on sale for $".$price." (reg. $".$info->{'MSRP'}.") on www.amazon.com/gp/product/".$ASIN."\n";
			//publish_SaleMessage("amazon.com", $ASIN, $price, $info->{'LegoID'});
		}
	}
	elseif (!empty($item->{'LegoID'}))
	{
		$legoID = $item->{'LegoID'};
		db_insert("Amazon_Item", array("LegoID" => $legoID, "ASIN" => $ASIN), null, true);

		echo "[Info][".date('Y-m-d H:i:s')."] New item added by matching legoid on www.amazon.com/gp/product/".$ASIN." ".$legoID." - ".$item->{'Title'}."\n";
		//send_Message(NOTIFICATION_RECIPIENT, "New Amazon_Item ".$legoID." - ".$item->{'Title'}." listed on www.amazon.com/gp/product/".$ASIN);

	}
	else
	{
		// try to match the legoid by title.
		$ret = search_legoid(array("Title" => $item->{'Title'}));
		if (isset($ret->{'MatchID'}))
		{
			$legoID = $ret->{'MatchID'};
			db_insert("Amazon_Item", array("LegoID" => $legoID, "ASIN" => $ASIN), null, true);
		}
		else
		{
			db_insert("Amazon_Item", array("LegoID" => "", "ASIN" => $ASIN), null, true);
		}

		echo "[Info][".date('Y-m-d H:i:s')."] New item added by matching title on www.amazon.com/gp/product/".$ASIN." ".$legoID." - ".$item->{'Title'}."\n";
		//send_Message(NOTIFICATION_RECIPIENT, "New Amazon_Item ".$legoID." - ".$item->{'Title'}." listed on www.amazon.com/gp/product/".$ASIN);
	}
}

if (!empty($arrNoupdate))
{
	$ret = db_update("Amazon_Item", array("LastUpdateTime" => date('Y-m-d H:i:s')), "ASIN IN (".implode(",", $arrNoupdate).")");
	//var_dump($ret);
}

?>