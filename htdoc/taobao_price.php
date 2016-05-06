<?php
require_once("db_handler.php");

date_default_timezone_set('America/Los_Angeles');


if (isset($argv[1]))
{
	get_price($argv[1]);
}
else
{
	$ret = db_query("TB_Pending_LegoID_UpdateTime_ASC", array("LegoID"), "1=1 LIMIT 20");
	if (!$ret->{'Status'})
	{
		foreach ($ret->{'Results'} as $item)
		{
			echo "[".date('Y-m-d H:i:s')."] Crawling ".$item->{'LegoID'}.": ";
			get_price($item->{'LegoID'});
			sleep(1);
		}
	}	
}


function isJinPaiMaiJia($icons)
{
	foreach ($icons as $icon)
	{
		if ($icon->icon_key == "icon-service-jinpaimaijia")
		{
			return true;
		}
	}
	return false;
}

function isErShou($icons)
{
	foreach ($icons as $icon)
	{
		if ($icon->icon_key == "icon-service-ershou-new")
		{
			return true;
		}
	}
	return false;
}

function get_price($legoid)
{

	$keywords = array("\d{4}+", "[A-Za-z]{2,3}\d{3}", "代购", "二手", "租赁", "租金", "预定", "图纸", "貼紙", "说明书", "搭建图", "无盒", "微瑕", "瑕疵", "不含", "杀肉", "净场景", "载具", "配件", "零件", "散件", "单出", "国产", "乐高式", "乐高类", "邦宝", "博乐", "鲁班", "开智", "兼容", "DECOOL");
	$locs = array("海外", "美国", "香港");
	//$blacklistseller = array("八脚喜", "欢乐客亲子早教积木");
	$pagecount = 1;
	$items = array();
	$totalprice = 0;
	$totalseller = 0;
	$min = 0;
	$soldmin = 0;
	$totalvol = 0;
	$threshold = 0.30;

	for ($i = 1; $i <= $pagecount; $i++)
	{
		$url = "https://s.taobao.com/search?q=lego+".$legoid."&commend=all&s=".(($i-1)*40);
		echo "$i ";
		$ch = curl_init(); 
		$timeout = 10; 
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_ENCODING , "gzip");
		curl_setopt($ch, CURLOPT_COOKIE, 'hng=CN%7Czh-cn%7CCNY;');
		curl_setopt($ch, CURLOPT_REFERER, 'https://item.taobao.com/item.htm');
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); 
		$contents = curl_exec($ch);
		curl_close($ch);

		$jsonstart = strpos($contents, "g_page_config = ") + 15;
		$jsonend = strpos($contents, "g_srp_loadCss();");

		$json = json_decode(trim(trim(substr($contents, $jsonstart, $jsonend-$jsonstart)), ";"));
		$pagecount = min($json->mods->pager->data->totalPage, 10);
		$itemlist = $json->mods->itemlist->data->auctions;
		$tips = $json->mods->tips->data->html;

		if (preg_match("/抱歉！没有找到/u", html_entity_decode($tips, ENT_NOQUOTES, 'UTF-8')))
		{
			db_insert("Taobao_Price", array("LegoID" => $legoid, "UpdateTime" => "CURRENT_TIMESTAMP()"), null, ture);
			break;
		}
		if (isset($itemlist))
		{
			foreach ($itemlist as $item)
			{
				$filter = 0;
				$reason = "";
				$iteminfo = new stdClass();
				
				$iteminfo->{'LegoID'} = $legoid;

				$iteminfo->{'Nid'} = $item->nid;
				$iteminfo->{'Title'} = $item->raw_title;

				$iteminfo->{'Price'} = $item->view_price;
				$iteminfo->{'Credit'} = $item->shopcard->sellerCredit;
				if (intval($item->shopcard->isTmall) == 1)
				{
					$filter = 1;
					$reason = "Tmall";
				}
				elseif (isJinPaiMaiJia($item->icon))
				{
					$filter = 0;
					$resaon = "JinPai";
				}
				elseif (isErShou($item->icon))
				{
					$filter = 1;
					$resaon = "ErShou";
				}
				elseif (intval($item->shopcard->sellerCredit) > 5)
				{
					$filter = 0;
					$resaon = "StarsSeller";
				}
				else
				{
					$filter = 1;
					$reason = "Not Major Seller.";
				}
				
				if (!$filter)
				{
					$replacedtitle = preg_replace("/lego/iu", "", preg_replace("/19|20\d{2}/u", "", str_replace($legoid, "", html_entity_decode($iteminfo->{'Title'}, ENT_NOQUOTES, 'UTF-8'))));
					foreach ($keywords as $keyword)
					{
						if (preg_match("/".$keyword."/u", $replacedtitle))
						{
							$filter = 1;
							$reason = $keyword." matched.";
							break;
						}
					}
				
					if (preg_match("/".$legoid."-/u", html_entity_decode($iteminfo->{'Title'}, ENT_NOQUOTES, 'UTF-8')))
					{
						$filter = 1;
						$reason = "Subsets.";
					}

					if (!preg_match("/".$legoid."/u", html_entity_decode($iteminfo->{'Title'}, ENT_NOQUOTES, 'UTF-8')))
					{
						$filter = 1;
						$reason = "No LegoID in title.";
					}

				}
				
				$strSold = $item->view_sales;
				preg_match_all("/\d+/u", html_entity_decode($strSold, ENT_NOQUOTES, 'UTF-8'), $matchSold);
				$tmp = array_pop($matchSold);
				$iteminfo->{'Volume'} = intval(array_pop($tmp));

				$iteminfo->{'Seller'} = $item->nick;
				/*
				foreach ($blacklistseller as $keyword)
				{
					if (preg_match("/".$keyword."/u", html_entity_decode($iteminfo->{'Seller'}, ENT_NOQUOTES, 'UTF-8')))
					{
						$filter = 1;
						$reason = "Blacklisted Seller.";
						break;
					}
				}
				*/

				$iteminfo->{'Location'} = $item->item_loc;

				foreach ($locs as $keyword)
				{
					if (preg_match("/".$keyword."/u", html_entity_decode($iteminfo->{'Location'}, ENT_NOQUOTES, 'UTF-8')))
					{
						$filter = 1;
						$reason = $keyword." matched.";
						break;
					}
				}

				$iteminfo->{'Filter'} = $filter;
				$iteminfo->{'Reason'} = $reason;

				if (!$iteminfo->{'Filter'})
				{
					$totalprice += $iteminfo->{'Price'};
					$totalseller++;
				}

				$items[$iteminfo->{'Nid'}] = $iteminfo;
			}	
		}
	}

	echo "\n";
	if ($i < $pagecount && $i > 1)
	{
		echo "[".date('Y-m-d H:i:s')."] [$legoid] Only $i/$pagecount page(s) crawled, not update to database.\n";
	}
	elseif (count($items) < 1)
	{
		echo "[".date('Y-m-d H:i:s')."] [$legoid] No list found for this item, not update to database.\n";
		db_insert("Taobao_Price", array("LegoID" => $legoid, "UpdateTime" => "CURRENT_TIMESTAMP()"), null, ture);
	}
	else
	{
		$avgprice = round($totalprice/$totalseller, 2);

		foreach ($items as $item)
		{
			if ($item->{'Filter'})
			{
				//echo $item->{'title'}."\t".$item->{'price'}."\t".$item->{'reason'}."\n";
			}
			else
			{
				if ($item->{'Price'} < $avgprice*(1-$threshold) || $item->{'Price'} > $avgprice*(1+$threshold))
				{
					$item->{'Filter'} = 1;
					$item->{'Resaon'} = "Price abnormal";
					//echo ">>>".$item->{'title'}."\t".$item->{'price'}."\t".$item->{'loc'}."\t".$item->{'vol'}."\t".$item->{'reason'}."\n";

				}
				else
				{
					if ($min == 0)
					{
						$min = $item->{'Price'};
					}
					else
					{
						$min = min($min, $item->{'Price'});
					}
					$totalvol += $item->{'Volume'};
					if ($item->{'Volume'} > 0)
					{
						if ($soldmin == 0)
						{
							$soldmin = $item->{'Price'};
						}
						else
						{
							$soldmin = min($soldmin, $item->{'Price'});
						}
					}

					$arrFileds = array();

					foreach (array("Nid", "LegoID", "Title", "Price", "Seller", "Location", "Volume") as $field)
					{
						$arrFileds[$field] = $item->{$field};
					}

					$ret = db_insert("Taobao_ItemPrice", $arrFileds, null, true);
					//var_dump($ret);
					//push to db.
					//echo $item->{'nid'}."\t".$item->{'title'}."\t".$item->{'price'}."\t".$item->{'loc'}."\t".$item->{'vol'}."\t".$item->{'credit'}."\t".$item->{'reason'}."\n";
				}
				
			}
		}
		echo "[".date('Y-m-d H:i:s')."] [$legoid] Total:".count($items)."\tValid:".$totalseller."\tAvg:".$avgprice."\tMin:".$min."\tSoldMin:".$soldmin."\tSold:".$totalvol."\n";
		//var_dump($items);
		db_insert("Taobao_Price", array("LegoID" => $legoid, "Price" => $soldmin, "AvgPrice" => $avgprice, "MinPrice" => $min, "Sellers" => $totalseller, "Volume" => $totalvol), null, ture);
	}
}
?>
