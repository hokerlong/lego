<?php
require_once("db_handler.php");

$Toysrus = array();
$ret = db_query("Toysrus_Item", array("LegoID", "Price", "Availability", "LastUpdateTime", "ToysrusID AS ItemID"), "LegoID <> ''");
if (!$ret->{'Status'})
{
	foreach ($ret->{'Results'} as $item)
	{
		$idx = $item->{'LegoID'};
		$item->{'Provider'} = "toysrus.com";
		$Toysrus["$idx"] = $item;
	}
}

$Walmart = array();
$ret = db_query("Walmart_Item", array("LegoID", "Price", "Availability", "LastUpdateTime", "WalmartID AS ItemID"), "LegoID <> ''");
if (!$ret->{'Status'})
{
	foreach ($ret->{'Results'} as $item)
	{
		$idx = $item->{'LegoID'};
		$item->{'Provider'} = "walmart.com";
		$Walmart["$idx"] = $item;
	}
}

$Amazon = array();
$ret = db_query("Amazon_Item", array("LegoID", "Price", "Availability", "LastUpdateTime", "ASIN AS ItemID"), "LegoID <> ''");
if (!$ret->{'Status'})
{
	foreach ($ret->{'Results'} as $item)
	{
		$idx = $item->{'LegoID'};
		$item->{'Provider'} = "amazon.com";
		$Amazon["$idx"] = $item;
	}
}

$json = array();
$ret = db_query("Available_LegoID INNER JOIN DB_Set ON Available_LegoID.LegoID = DB_Set.LegoID INNER JOIN DB_Theme ON DB_Set.ThemeID = DB_Theme.ThemeID", array("Available_LegoID.LegoID AS LegoID", "ETheme AS Theme", "ETitle AS Title", "USPrice AS MSRP"), "USPrice > 0");
if (!$ret->{'Status'})
{

	foreach ($ret->{'Results'} as $item)
	{
		$jsonitem = new stdClass();
		$legoID = $item->{'LegoID'};
		$minrate = 100;
		$toysrus_str = 	"N/A";
		$walmart_str = 	"N/A";
		$amazon_str = 	"N/A";
		if (isset($Toysrus["$legoID"]))
		{
			if ($Toysrus["$legoID"]->{'Price'} > 0)
			{
				$toysrus_rate = round($Toysrus["$legoID"]->{'Price'} / $item->{'MSRP'} * 100, 2);
				$minrate = $toysrus_rate;
				$toysrus_str = 	"$".$Toysrus["$legoID"]->{'Price'}."(".$toysrus_rate."%) - ".$Toysrus["$legoID"]->{'Availability'};
			}
		}
		if (isset($Walmart["$legoID"]))
		{
			if ($Walmart["$legoID"]->{'Price'} > 0)
			{
				$walmart_rate = round($Walmart["$legoID"]->{'Price'} / $item->{'MSRP'} * 100, 2);
				$minrate = min($minrate, $walmart_rate);
				$walmart_str = 	"$".$Walmart["$legoID"]->{'Price'}."(".$walmart_rate."%) - ".$Walmart["$legoID"]->{'Availability'};
			}
		}
		if (isset($Amazon["$legoID"]))
		{
			if ($Amazon["$legoID"]->{'Price'} > 0)
			{
				$amazon_rate = round($Amazon["$legoID"]->{'Price'} / $item->{'MSRP'} * 100, 2);
				$minrate = min($minrate, $amazon_rate);
				$amazon_str = 	"$".$Amazon["$legoID"]->{'Price'}."(".$amazon_rate."%) - ".$Amazon["$legoID"]->{'Availability'};
			}

		}
		$jsonitem->{'legoid'} = $item->{'LegoID'};
		$jsonitem->{'theme'} = $item->{'Theme'};
		$jsonitem->{'title'} = $item->{'Title'};
		$jsonitem->{'msrp'} = $item->{'MSRP'};
		$jsonitem->{'min_rate'} = $minrate;
		$jsonitem->{'toysrus_rate'} = $toysrus_rate;
		$jsonitem->{'toysrus_price'} = $Toysrus["$legoID"]->{'Price'};
		$jsonitem->{'toysrus_availability'} = $Toysrus["$legoID"]->{'Availability'};
		$jsonitem->{'walmart_rate'} = $walmart_rate;
		$jsonitem->{'walmart_price'} = $Walmart["$legoID"]->{'Price'};
		$jsonitem->{'walmart_availability'} = $Walmart["$legoID"]->{'Availability'};
		$jsonitem->{'amazon_rate'} = $amazon_rate;
		$jsonitem->{'amazon_price'} = $Amazon["$legoID"]->{'Price'};
		$jsonitem->{'amazon_availability'} = $Amazon["$legoID"]->{'Availability'};
		array_push($json, $jsonitem);
	}
}
echo json_encode($json);

?>