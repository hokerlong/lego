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
$ret = db_query("Available_LegoID INNER JOIN DB_Set ON Available_LegoID.LegoID = DB_Set.LegoID INNER JOIN DB_Theme ON DB_Set.ThemeID = DB_Theme.ThemeID", array("Available_LegoID.LegoID AS LegoID", "ETheme AS Theme", "ETitle AS Title", "USPrice AS MSRP", "Badge"), "USPrice > 0 ORDER BY Available_LegoID.LegoID*1");
if (!$ret->{'Status'})
{
	foreach ($ret->{'Results'} as $item)
	{
		$jsonitem = new stdClass();
		$legoID = $item->{'LegoID'};

		$jsonitem->{'legoid'} = intval($item->{'LegoID'});
		$jsonitem->{'theme'} = $item->{'Theme'};
		$jsonitem->{'title'} = $item->{'Title'};
		if (!empty($item->{'Badge'}))
		{
			$jsonitem->{'badge'} = $item->{'Badge'};
		}
		$jsonitem->{'msrp'} = floatval($item->{'MSRP'});

		$minrate = 100;
		$jsonitem->{'toysrus_rate'} = null;
		$jsonitem->{'walmart_rate'} = null;
		$jsonitem->{'amazon_rate'} = null;
		if (isset($Toysrus["$legoID"]))
		{
			if ($Toysrus["$legoID"]->{'Price'} > 0)
			{
				$jsonitem->{'toysrus_rate'} = floatval(round($Toysrus["$legoID"]->{'Price'} / $item->{'MSRP'} * 100, 2));
				$jsonitem->{'toysrus_url'} = gen_url("toysrus.com", $Toysrus["$legoID"]->{'ItemID'});
				$jsonitem->{'toysrus_price'} = $Toysrus["$legoID"]->{'Price'};
				$jsonitem->{'toysrus_availability'} = $Toysrus["$legoID"]->{'Availability'};
				$minrate = $jsonitem->{'toysrus_rate'};
			}
		}
		if (isset($Walmart["$legoID"]))
		{
			if ($Walmart["$legoID"]->{'Price'} > 0)
			{
				$jsonitem->{'walmart_rate'} = floatval(round($Walmart["$legoID"]->{'Price'} / $item->{'MSRP'} * 100, 2));
				$jsonitem->{'walmart_url'} = gen_url("walmart.com", $Walmart["$legoID"]->{'ItemID'});
				$jsonitem->{'walmart_price'} = $Walmart["$legoID"]->{'Price'};
				$jsonitem->{'walmart_availability'} = $Walmart["$legoID"]->{'Availability'};
				$minrate = min($minrate, $jsonitem->{'walmart_rate'});
			}
		}
		if (isset($Amazon["$legoID"]))
		{
			if ($Amazon["$legoID"]->{'Price'} > 0)
			{
				$jsonitem->{'amazon_rate'} = floatval(round($Amazon["$legoID"]->{'Price'} / $item->{'MSRP'} * 100, 2));
				$jsonitem->{'amazon_url'} = gen_url("amazon.com", $Amazon["$legoID"]->{'ItemID'});
				$jsonitem->{'amazon_price'} = $Amazon["$legoID"]->{'Price'};
				$jsonitem->{'amazon_availability'} = $Amazon["$legoID"]->{'Availability'};
				$minrate = min($minrate, $jsonitem->{'amazon_rate'});
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