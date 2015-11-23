<?php
require("db_handler.php");
require("crawlers.php");

$ret = db_query("BN_Item", array("BNID", "Price", "StatusUpdateTime"), "1 = 1 ORDER BY StatusUpdateTime ASC LIMIT 50");
if (!$ret->{'Status'})
{
	foreach ($ret->{'Results'} as $item)
	{
		$Availability = "Unknown";
		$BNID = $item->{'BNID'};

		$ret = crawl_bn_status($BNID);

		if (isset($ret->{'Availability'}))
		{
			$ret = db_update("BN_Item", array("Availability" => $ret->{'Availability'}, "StatusUpdateTime" => gmdate('Y-m-d H:i:s')), array("BNID" => $BNID));
			var_dump($ret);
		}
	}
}

function crawl_bn_status($BNID)
{
	$ret = new stdClass();

	$ret->{'URL'} = 'http://www.barnesandnoble.com/w/'.$BNID;
	$htmldom = curl_htmldom($ret->{'URL'});

	if ($htmldom)
	{
		$priceDom = $htmldom->find('//*[@id="prodInfoContainer"]/div[itemprop="offers"]/span[itemprop="price"]', 0);
		$marketDom = $htmldom->find('//*[@id="prodInfoContainer"]/h3', 0);

		if (isset($priceDom))
		{
			$ret->{'Price'} = trim($priceDom->plaintext);
			$ret->{'Availability'} = "Available";
		}
		elseif (isset($marketDom))
		{
			if ($marketDom->plaintext == "Item is available through our marketplace sellers and in stores.")
			{
				$ret->{'Availability'} = "Pickup Only";
			}
			elseif ($marketDom->plaintext == "Item is available through our marketplace sellers.")
			{
				$ret->{'Availability'} = "Out of Stock";
			}
			else
			{
				$ret->{'Availability'} = "Unknown";
			}
		}
		else
		{
			$ret->{'Availability'} = "Unknown";
		}
	}
	return $ret;
}

?>