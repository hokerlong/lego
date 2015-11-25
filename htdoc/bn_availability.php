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

?>