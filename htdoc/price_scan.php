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
	foreach ($crawlItems as $item)
	{
		$itemID = $item->{$idName};
		if (isset($dbItems["$itemID"]))
		{
			$info = $dbItems["$itemID"];
			$msrp = $info->{'MSRP'};
			$price = $info->{'Price'};
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

				$ret = db_update($tlbName, $arrfields, array($idName => $itemID));
				if (!$ret->{'Status'})
				{
					unset($arrfields['LastUpdateTime']);
					$strupdate = "";
					foreach ($arrfields as $prop => $value)
					{
						$strupdate .= $prop."[".$info->{$prop}."=>".$value."], ";
					}
					$strupdate = trim($strupdate, ", ");
					echo "[Info][".date('Y-m-d H:i:s')."] ".$tlbName." ".$info->{'LegoID'}." - ".$info->{'Title'}." updated: ".$strupdate." ".get_url_by_itemID($provider, $itemID)."\n";
					//send_Message(NOTIFICATION_RECIPIENT, "Toysrus_Item ".$info->{'LegoID'}." - ".$info->{'Title'}." updated: ".$strupdate." www.toysrus.com/product/index.jsp?productId=$ToysrusID");
				}

				if (!empty($rate) && $rate < 76 && $item->{"Availability"} <> "Sold Out" && !empty(($info->{'LegoID'})))
				{
					echo "[Info][".date('Y-m-d H:i:s')."] ".$info->{'LegoID'}." on sale for $".$price." (".$rate."% off from reg. $".$info->{'MSRP'}.") ".get_url_by_itemID($provider, $itemID)."\n";

					/*
					$ret = publish_SaleMessage($provider, $itemID, $price, $info->{'LegoID'});
					if (!$ret->{'Status'})
					{
						echo "[Info][".date('Y-m-d H:i:s')."] ".$info->{'LegoID'}." on sale for $".$price." (".$rate."% off from reg. $".$info->{'MSRP'}.") ".get_url_by_itemID($provider, $itemID)."\n";
					}
					else
					{
						echo "[Warning][".date('Y-m-d H:i:s')."] Failed to publish tweet due to ".$ret->{'Message'}.": ".$info->{'LegoID'}." on sale for $".$price." (".$rate."% off from reg. $".$info->{'MSRP'}.")\n";
					}
					*/
				}
			}
			else
			{
				array_push($arrNoupdate, $itemID);
			}
		}
		elseif (!empty($item->{'LegoID'}))
		{
			$legoID = $item->{'LegoID'};
			db_insert($tlbName, array("LegoID" => $legoID, $idName => $itemID), null, true);

			echo "[Info][".date('Y-m-d H:i:s')."] New item added by legoid: ".$legoID." - ".$item->{'Title'}." ".get_url_by_itemID($provider, $itemID)."\n";
			//send_Message(NOTIFICATION_RECIPIENT, "New Toysrus_Item ".$legoID." - ".$item->{'Title'}." listed on www.toysrus.com/product/index.jsp?productId=".$ToysrusID);
		}
		else
		{
			// try to match the legoid by title.
			$ret = search_legoid(array("Title" => $item->{'Title'}));
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

			echo "[Info][".date('Y-m-d H:i:s')."] New item added by title: ".$legoID." - ".$item->{'Title'}." ".get_url_by_itemID($provider, $itemID)."\n";
			//send_Message(NOTIFICATION_RECIPIENT, "New ".$tlbName." ".$legoID." - ".$item->{'Title'}." listed on ".get_url_by_itemID($provider, $itemID);
		}
	}

	if (!empty($arrNoupdate))
	{
		$ret = db_update($tlbName, array("LastUpdateTime" => gmdate('Y-m-d H:i:s')), $idName." IN (".implode(",", $arrNoupdate).")");
		echo "[Info][".date('Y-m-d H:i:s')."] No update for ".count($arrNoupdate)." items\n";
	}
}


function crawl_price($provider)
{
	switch ($provider)
	{
		case 'Amazon':
			return crawl_amazon();
			break;
		case 'Walmart':
			return crawl_walmart();
			break;
		case 'Toysrus':
			return crawl_toysrus();
			break;
		case 'LegoShop':
			return crawl_lego();
			break;
		default:
			# code...
			break;
	}
}

function get_url_by_itemID($provider, $itemID)
{
	switch ($provider)
	{
		case 'Amazon':
			$url = "http://www.amazon.com/gp/product/".$itemID;
		case 'Walmart':
			$url = "http://www.walmart.com/ip/".$itemID;
			break;
		case 'Toysrus':
			$url = "http://www.toysrus.com/product/index.jsp?productId=".$itemID;
			break;
		case 'LegoShop':
			$url = "";
			break;
		default:
			$url = "";
			break;
	}
	return $url;
}

?>