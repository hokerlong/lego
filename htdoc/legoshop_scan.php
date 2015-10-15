<?php
require_once("crawlers.php");
require_once("db_handler.php");
require_once("twitter_handler.php");

$LegoDB = pull_DBSet(array("ETitle", "USPrice", "Availability", "Badge"), null);

$LegoInfo = crawl_lego();

$sortedItems = array();
foreach ($LegoInfo->{'Items'} as $item)
{
	$id = intval($item->{'LegoID'});
	$sortedItems[$id] = $item;
}
ksort($sortedItems);


foreach ($sortedItems as $item)
{
	if (isset($item->{'Price'}))
	{
		$price = floatval($item->{'Price'});
	}
	else
	{
		$price = 0;
	}
	if (isset($item->{'Saleprice'}))
	{
		$salePrice = floatval($item->{'Saleprice'});
	}
	else
	{
		$salePrice = 0;
	}
	//generate the csv
	echo $item->{'LegoID'}.",\"".$item->{'Availability'}."\",".$price.",".$salePrice.",\"".$item->{'Title'}."\"\n";

	//update DB_Set
	if (isset($LegoDB[$item->{'LegoID'}]))
	{
		$dbitem = $LegoDB[$item->{'LegoID'}];
		$arrfields = array();
		if ($price > 0 && $dbitem->{'USPrice'} <> $price)	
		{
			$arrfields['USPrice'] = $item->{'Price'};
		}
		if ($item->{'Title'} <> $dbitem->{'ETitle'})
		{
			$arrfields['ETitle'] = $item->{'Title'};
		}
		if ($dbitem->{'Availability'} <> $item->{'Availability'})
		{
			$arrfields['Availability'] = $item->{'Availability'};

			if (!empty($dbitem->{'Badge'}))
			{
				send_Message(NOTIFICATION_RECIPIENT, $item->{'LegoID'}." - ".$item->{'Title'}." (".$dbitem->{'Badge'}."): Availability changed from '".$dbitem->{'Availability'}."' to '".$item->{'Availability'}."' ".$item->{'URL'});
			}
		}
		if ($dbitem->{'Badge'} <> $item->{'Badge'})
		{
			$arrfields['Badge'] = $item->{'Badge'};
		}
		if (count($arrfields))
		{
			update_DBSet(array("LegoID" => $item->{'LegoID'}), $arrfields);
		}
	}
	else
	{
		send_Message(NOTIFICATION_RECIPIENT, "New item listed on Official Lego Shop: ".$item->{'LegoID'}." - ".$item->{'Title'}." at $".$item->{'Price'}." ".$item->{'URL'});
		new_tweet("New item listed on Official Lego Shop: ".$item->{'LegoID'}." - ".$item->{'Title'}." at $".$item->{'Price'}." ".$item->{'URL'});
		insert_DBSet(array("LegoID" => $item->{'LegoID'}, "ETitle" => $item->{'Title'}, "USPrice" => $item->{'Price'}, "Badge" => $item->{'Badge'}));
	}
}
?>