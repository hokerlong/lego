<?php
date_default_timezone_set('America/Los_Angeles');

$filename = "/tmp/current_items.cache";

if (file_exists($filename) && !isset($_GET["refresh"]))
{
	if (filemtime($filename) > strtotime('-5 min'))
	{
		ob_start('ob_gzhandler');
		echo file_get_contents($filename);
		exit;
	}
}

require_once("crawlers.php");
require_once("db_handler.php");

$Toysrus = array();
$ret = db_query("Toysrus_Item", array("LegoID", "Price", "Availability", "LastUpdateTime", "ToysrusID AS ItemID"), "LegoID <> '' AND LastUpdateTime > '".gmdate('Y-m-d H:i:s', strtotime('-2 days'))."'");
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
$ret = db_query("Walmart_Item", array("LegoID", "Price", "Availability", "LastUpdateTime", "WalmartID AS ItemID"), "LegoID <> '' AND LastUpdateTime > '".gmdate('Y-m-d H:i:s', strtotime('-2 days'))."'");
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
$ret = db_query("Amazon_Item", array("LegoID", "Price", "Availability", "LastUpdateTime", "ASIN AS ItemID"), "LegoID <> '' AND LastUpdateTime > '".gmdate('Y-m-d H:i:s', strtotime('-2 days'))."'");
if (!$ret->{'Status'})
{
	foreach ($ret->{'Results'} as $item)
	{
		$idx = $item->{'LegoID'};
		$item->{'Provider'} = "amazon.com";
		$Amazon["$idx"] = $item;
	}
}

$Target = array();
$ret = db_query("Target_Item", array("LegoID", "Price", "Availability", "LastUpdateTime", "TargetID AS ItemID"), "LegoID <> '' AND LastUpdateTime > '".gmdate('Y-m-d H:i:s', strtotime('-2 days'))."'");
if (!$ret->{'Status'})
{
	foreach ($ret->{'Results'} as $item)
	{
		$idx = $item->{'LegoID'};
		$item->{'Provider'} = "target.com";
		$Target["$idx"] = $item;
	}
}

$BN = array();
$ret = db_query("BN_Item", array("LegoID", "Price", "Availability", "LastUpdateTime", "BNID AS ItemID"), "LegoID <> '' AND LastUpdateTime > '".gmdate('Y-m-d H:i:s', strtotime('-2 days'))."'");
if (!$ret->{'Status'})
{
	foreach ($ret->{'Results'} as $item)
	{
		$idx = $item->{'LegoID'};
		$item->{'Provider'} = "bn.com";
		$BN["$idx"] = $item;
	}
}

$Taobao = array();
$ret = db_query("(SELECT * FROM Top_SellerPrice ORDER BY Price, Volume) AS TEMP", array("LegoID", "MIN(Price) AS Low", "AVG(Price) AS Avg", "Nid", "Volume", "SUM(Volume) AS Total"), "1=1 GROUP BY LegoID");
//$ret = db_query("(SELECT * FROM Taobao_Transaction WHERE Flaw=0 AND Invalid=0 AND Timestamp >  DATE_SUB(NOW(), INTERVAL 15 DAY) ORDER BY Price) AS TEMP", array("LegoID", "MIN(Price) AS Low", "SUM(Amount*Price)/SUM(Amount) AS Avg", "ItemID", "COUNT(DISTINCT SellerID) AS Sellers", "SUM(Amount) AS Volume"), "1=1 GROUP BY LegoID");

//var_dump($ret);
if (!$ret->{'Status'})
{
	foreach ($ret->{'Results'} as $item)
	{
		$idx = $item->{'LegoID'};
		$Taobao["$idx"] = $item;
	}
}

$jsonitems = array();
$ret = db_query("Available_LegoID INNER JOIN DB_Set ON Available_LegoID.LegoID = DB_Set.LegoID INNER JOIN DB_Theme ON DB_Set.ThemeID = DB_Theme.ThemeID", array("Available_LegoID.LegoID AS LegoID", "ETheme AS Theme", "ETitle AS Title", "USPrice AS MSRP", "Badge", "Weight", "Availability"), "USPrice > 0 ORDER BY Available_LegoID.LegoID*1");
if (!$ret->{'Status'})
{
	foreach ($ret->{'Results'} as $item)
	{
		$jsonitem = new stdClass();
		$legoID = $item->{'LegoID'};

		$jsonitem->{'legoid'} = intval($item->{'LegoID'});
		$jsonitem->{'theme'} = $item->{'Theme'};
		$jsonitem->{'title'} = $item->{'Title'};
		$jsonitem->{'weight'} = $item->{'Weight'};
		if (!empty($item->{'Badge'}))
		{
			$jsonitem->{'badge'} = $item->{'Badge'};
		}
		$jsonitem->{'msrp'} = floatval($item->{'MSRP'});

		if ($item->{'Availability'} == "Available")
		{
			$jsonitem->{'lego_price'} = floatval($item->{'MSRP'});
		}
		$minrate = 100;
		$jsonitem->{'toysrus_rate'} = null;
		$jsonitem->{'walmart_rate'} = null;
		$jsonitem->{'amazon_rate'} = null;
		$jsonitem->{'target_rate'} = null;
		$jsonitem->{'bn_rate'} = null;
		if (isset($Toysrus["$legoID"]))
		{
			if ($Toysrus["$legoID"]->{'Price'} > 0 && ($Toysrus["$legoID"]->{'Availability'} == "Available" || $Toysrus["$legoID"]->{'Availability'} == "Shipping Only" ))
			{
				$jsonitem->{'toysrus_rate'} = floatval(round($Toysrus["$legoID"]->{'Price'} / $item->{'MSRP'} * 100, 2));
				$jsonitem->{'toysrus_url'} = get_url_by_itemID("Toysrus", $Toysrus["$legoID"]->{'ItemID'});
				$jsonitem->{'toysrus_price'} = $Toysrus["$legoID"]->{'Price'};
				$jsonitem->{'toysrus_availability'} = $Toysrus["$legoID"]->{'Availability'};
				$minrate = $jsonitem->{'toysrus_rate'};
			}
		}
		if (isset($Walmart["$legoID"]))
		{
			if ($Walmart["$legoID"]->{'Price'} > 0 && $Walmart["$legoID"]->{'Availability'} == "Available")
			{
				$jsonitem->{'walmart_rate'} = floatval(round($Walmart["$legoID"]->{'Price'} / $item->{'MSRP'} * 100, 2));
				$jsonitem->{'walmart_url'} = get_url_by_itemID("Walmart", $Walmart["$legoID"]->{'ItemID'});
				$jsonitem->{'walmart_price'} = $Walmart["$legoID"]->{'Price'};
				$jsonitem->{'walmart_availability'} = $Walmart["$legoID"]->{'Availability'};
				$minrate = min($minrate, $jsonitem->{'walmart_rate'});
			}
		}
		if (isset($Amazon["$legoID"]))
		{
			if ($Amazon["$legoID"]->{'Price'} > 0 && $Amazon["$legoID"]->{'Availability'} == "Available")
			{
				$jsonitem->{'amazon_rate'} = floatval(round($Amazon["$legoID"]->{'Price'} / $item->{'MSRP'} * 100, 2));
				$jsonitem->{'amazon_url'} = get_url_by_itemID("Amazon", $Amazon["$legoID"]->{'ItemID'});
				$jsonitem->{'amazon_price'} = $Amazon["$legoID"]->{'Price'};
				$jsonitem->{'amazon_availability'} = $Amazon["$legoID"]->{'Availability'};
				$minrate = min($minrate, $jsonitem->{'amazon_rate'});
			}
		}
		if (isset($Target["$legoID"]))
		{
			if ($Target["$legoID"]->{'Price'} > 0 && $Target["$legoID"]->{'Availability'} == "Available")
			{
				$jsonitem->{'target_rate'} = floatval(round($Target["$legoID"]->{'Price'} / $item->{'MSRP'} * 100, 2));
				$jsonitem->{'target_url'} = get_url_by_itemID("Target", $Target["$legoID"]->{'ItemID'});
				$jsonitem->{'target_price'} = $Target["$legoID"]->{'Price'};
				$jsonitem->{'target_availability'} = $Target["$legoID"]->{'Availability'};
				$minrate = min($minrate, $jsonitem->{'target_rate'});
			}
		}
		if (isset($BN["$legoID"]))
		{
			if ($BN["$legoID"]->{'Price'} > 0 && $BN["$legoID"]->{'Availability'} == "Available")
			{
				$jsonitem->{'bn_rate'} = floatval(round($BN["$legoID"]->{'Price'} / $item->{'MSRP'} * 100, 2));
				$jsonitem->{'bn_url'} = get_url_by_itemID("BN", $BN["$legoID"]->{'ItemID'});
				$jsonitem->{'bn_price'} = $BN["$legoID"]->{'Price'};
				$jsonitem->{'bn_availability'} = $BN["$legoID"]->{'Availability'};
				$minrate = min($minrate, $jsonitem->{'bn_rate'});
			}
		}
		if (isset($Taobao["$legoID"]))
		{
			if ($Taobao["$legoID"]->{'Low'} > 0)
			{
				$jsonitem->{'taobao_price'} = $Taobao["$legoID"]->{'Low'};
				$jsonitem->{'taobao_avg'} = $Taobao["$legoID"]->{'Avg'};
				$jsonitem->{'taobao_nid'} = $Taobao["$legoID"]->{'ItemID'};
				$jsonitem->{'taobao_vol'} = $Taobao["$legoID"]->{'Volume'};
				$jsonitem->{'taobao_sellers'} = $Taobao["$legoID"]->{'Sellers'};
			}
		}
		$jsonitem->{'min_rate'} = $minrate;

		array_push($jsonitems, $jsonitem);
	}
}
$json = new stdClass();
$json->{'pubtime'} = time();
$json->{'items'} = $jsonitems;
file_put_contents($filename, json_encode($json));
ob_start('ob_gzhandler');
echo json_encode($json);

?>
