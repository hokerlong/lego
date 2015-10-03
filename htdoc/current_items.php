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

$jsonitems = array();
$ret = db_query("Available_LegoID INNER JOIN DB_Set ON Available_LegoID.LegoID = DB_Set.LegoID INNER JOIN DB_Theme ON DB_Set.ThemeID = DB_Theme.ThemeID", array("Available_LegoID.LegoID AS LegoID", "ETheme AS Theme", "ETitle AS Title", "USPrice AS MSRP"), "USPrice > 0");
if (!$ret->{'Status'})
{

	foreach ($ret->{'Results'} as $item)
	{
		$jsonitem = new stdClass();
		$legoID = $item->{'LegoID'};
		$minrate = 100;
		$toysrus_rate = null;
		$walmart_rate = null;
		$amazon_rate = null;
		if (isset($Toysrus["$legoID"]))
		{
			if ($Toysrus["$legoID"]->{'Price'} > 0)
			{
				$toysrus_rate = floatval(round($Toysrus["$legoID"]->{'Price'} / $item->{'MSRP'} * 100, 2));
				$minrate = $toysrus_rate;
			}
		}
		if (isset($Walmart["$legoID"]))
		{
			if ($Walmart["$legoID"]->{'Price'} > 0)
			{
				$walmart_rate = floatval(round($Walmart["$legoID"]->{'Price'} / $item->{'MSRP'} * 100, 2));
				$minrate = min($minrate, $walmart_rate);
			}
		}
		if (isset($Amazon["$legoID"]))
		{
			if ($Amazon["$legoID"]->{'Price'} > 0)
			{
				$amazon_rate = floatval(round($Amazon["$legoID"]->{'Price'} / $item->{'MSRP'} * 100, 2));
				$minrate = min($minrate, $amazon_rate);
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
		array_push($jsonitems, $jsonitem);
	}
}
$json = new stdClass();
$json->{'items'} = $jsonitems;
echo json_encode($json);

?>