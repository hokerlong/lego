<?php
require_once("crawlers.php");


$json = crawl_lego ();

$LegoInfo = json_decode($json);

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
	echo $item->{'LegoID'}.",\"".$item->{'Availability'}."\",".$price.",".$salePrice.",\"".$item->{'Title'}."\"\n";
}
?>