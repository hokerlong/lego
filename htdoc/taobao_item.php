<?php
require_once("db_handler.php");

date_default_timezone_set('America/Los_Angeles');

if (isset($argv[1]))
{
	$ret = db_query("TB_Pending_LegoIDItemID", array("LegoID", "ItemID"), "LegoID=".$argv[1]." LIMIT 30");	
}
else
{
	$ret = db_query("TB_Pending_LegoIDItemID", array("LegoID", "ItemID"), "1=1 LIMIT 30");

}

if (!$ret->{'Status'})
{
	foreach ($ret->{'Results'} as $item)
	{
		//echo "[".date('Y-m-d H:i:s')."] Crawling ".$item->{'Nid'}.".\n";
		crawl_trasaction($item->{'ItemID'}, $item->{'LegoID'});
	}
}

function crawl_trasaction($ItemID, $LegoID)
{
	$flawlist = array("压", "瑕");
	$invalidlist = array("预", "补", "联系");

	$url = "https://item.taobao.com/item.htm?id=".$ItemID;
	$contents = curl($url);

	if (preg_match('/;userid=(\d+);/mi', $contents, $match))
	{
		$SellerID = $match[1];
	}
	if (preg_match('/&sbn=(\w+)&/mi', $contents, $match))
	{
		$sbn = $match[1];
	}

	if (isset($ItemID, $SellerID, $sbn))
	{
		$i = 0;
		$nextpage = true;
		while($nextpage)
		{
			$url = "https://detailskip.taobao.com/service/getData/1/p1/item/detail/showBuyerList.htm?title=null&ends=1449401057000&starts=1448796257000&item_id=".$ItemID."&seller_num_id=".$SellerID."&sbn=".$sbn."&modules=showBuyerList&bid_page=".$i++;
			$contents = curl($url);
			$json = json_decode($contents);
			$nextpage = $json->data->showBuyerList->hasNext;
			$itemField = array();
			$itemField["LegoID"] = $LegoID;
			$itemField["ItemID"] = $ItemID;
			$itemField["SellerID"] = $SellerID;
			foreach ($json->data->showBuyerList->data as $item)
			{
				$itemField["Buyer"] = $item->buyerNick;
				$itemField["Amount"] = $item->amount;
				// if 专属优惠 
				$price = floatval($item->price);
				if ($price)
				{
					$itemField["Price"] = $price;
				}
				$itemField["Timestamp"] = gmdate('Y-m-d H:i:s', intval($item->gmtReceivePay/1000));
				$itemField["SKUInfo"] = str_replace("颜色分类:", "", $item->skuInfo[0]);
				$itemField["Flaw"] = 0;
				$itemField["Invalid"] = 0;

				foreach ($flawlist as $keyword)
				{
					if (preg_match("/".$keyword."/u", html_entity_decode($itemField["SKUInfo"], ENT_NOQUOTES, 'UTF-8')))
					{
						$itemField["Flaw"] = 1;
						break;
					}
				}

				foreach ($invalidlist as $keyword)
				{
					if (preg_match("/".$keyword."/u", html_entity_decode($itemField["SKUInfo"], ENT_NOQUOTES, 'UTF-8')))
					{
						$itemField["Invalid"] = 1;
						break;
					}
				}
				if($itemField["Price"] > 0)
				{
					$ret = db_insert("Taobao_Transaction", $itemField, null, true);
				}
				//var_dump($ret);
			}
			sleep(1);		
		}
		echo "[".date('Y-m-d H:i:s')."] $LegoID - $ItemID updated.\n";

	}
	else
	{
		echo "[".date('Y-m-d H:i:s')."] Unable to get SellerID/SBN for $ItemID.\n";
	}
}


function curl($url)
{
	$ch = curl_init(); 
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_ENCODING , "gzip");
	curl_setopt($ch, CURLOPT_COOKIE, 'hng=CN%7Czh-cn%7CCNY;');
	curl_setopt($ch, CURLOPT_REFERER, 'https://item.taobao.com/item.htm');
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); 

	$curlResponse = curl_exec($ch); 	
	$curlErrno = curl_errno($ch);
	if ($curlErrno)
	{
		$curlError = curl_error($ch);
		throw new Exception($curlError);
	}
	curl_close($ch);

	return $curlResponse;
}

?>