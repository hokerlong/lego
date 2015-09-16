<?php
require_once("crawlers.php");
require_once("db_handler.php");

function pull_DBSet($fields, $condition)
{
	array_push($fields, "LegoID");

	$LegoDB = array();

	$ret = db_query("DB_Set", $fields, $condition);
	if (!$ret->{'Status'})
	{
		foreach ($ret->{'Results'} as $item)
		{
			$id = intval($item->{'LegoID'});
			$LegoDB[$id] = $item;
		}
		//ksort($LegoDB);
		return $LegoDB;
	}
	else
	{
		//echo $ret->{'Message'};
		return false;
	}
}

function insert_NewID($field)
{
	$ret = db_insert("DB_Set", $field, null, false);
	if (!$ret->{'Status'})
	{
		return $ret->{'InsertID'};
	}
	else
	{
		//echo $ret->{'Message'};
		return false;
	}
}

function update_Item($identifier, $field)
{
	$ret = db_update("DB_Set", $field, $identifier);
	if (!$ret->{'Status'})
	{
		return true;
	}
	else
	{
		echo $ret->{'Message'};
		return false;
	}
}

$LegoDB = pull_DBSet(array("ETitle", "USPrice", "Availability"), null);

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
	if(isset($LegoDB[$item->{'LegoID'}]))
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
			if ($item->{'Availability'} == 'Retired')
			{
				// send of retirement notification
			}
		}
		if (count($arrfields))
		{
			update_Item(array("LegoID" => $item->{'LegoID'}), $arrfields);
		}
	}
	else
	{
		insert_NewID(array("LegoID" => $item->{'LegoID'}, "ETitle" => $item->{'Title'}, "USPrice" => $item->{'Price'}));
	}
}
?>