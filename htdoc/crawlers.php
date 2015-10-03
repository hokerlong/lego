<?php
date_default_timezone_set('America/Los_Angeles');

require_once("simple_html_dom.php");
ini_set('memory_limit', '256M');

mb_internal_encoding('utf-8');

function getArray($node)
{ 
	$array = false; 

	//echo var_dump($node);
	if ($node->hasAttributes())
	{ 
		foreach ($node->attributes as $attr)
		{ 
			$array[$attr->nodeName] = $attr->nodeValue; 
		} 
	} 

	if ($node->hasChildNodes())
	{ 
		if ($node->childNodes->length == 1)
		{ 
			$array[$node->firstChild->nodeName] = $node->firstChild->nodeValue; 
		}
		else
		{ 
			foreach ($node->childNodes as $childNode)
			{
				if ($childNode->nodeName == "br")
				{
					$array["#text"] = $node->nodeValue;
				}
				if ($childNode->nodeType != XML_TEXT_NODE)
				{ 
					$array[$childNode->nodeName][] = getArray($childNode); 
				} 
			} 
		} 
	} 
	return $array; 
} 

function curl_htmldom($url)
{
	$ch = curl_init(); 
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36');

	$curlResponse = curl_exec($ch); 	
	$curlErrno = curl_errno($ch);
	if ($curlErrno)
	{
		$curlError = curl_error($ch);
		throw new Exception($curlError);
	}
	curl_close($ch);

	$htmldom = str_get_html($curlResponse);
	if (isset($htmldom))
	{
		return $htmldom;
	}
	else
	{
		return false;
	}
}

function crawl_brickset($LegoID, $BK_SetID)
{
	if (!isset($BK_SetID) || $BK_SetID == '')
	{
		$BK_SetID = "-1";
	}
	
	$ret = new stdClass();

	$ret->{'URL'} = 'brickset.com/sets/'.$LegoID.$BK_SetID;
	$htmldom = curl_htmldom($ret->{'URL'});

	if ($htmldom)
	{
		$titleDom = $htmldom->find('section[class=main]/header/h1', 0);
		if (isset($titleDom))
		{
			$ret->{'ETitle'} = trim(str_replace($LegoID.$BK_SetID.": ", "", $titleDom->plaintext));
		}
		if ($ret->{'ETitle'} <> $LegoID.$BK_SetID)
		{
			$dom = new DOMDocument();
			$dom->loadHTML($htmldom->find('section[class=featurebox]/div[class=text]', 0)->innertext);
			$nodes = $dom->getElementsByTagName('dl');
			foreach ($nodes as $node)
			{
				$dl = getArray($node);
			}
			$dt = $dl["dt"];
			$dd = $dl["dd"];
			for ($i = 0; $i <= count($dt); $i++)
			{
				if ($dt[$i]["#text"] == "Theme")
				{
					$ret->{'Theme'} = trim($dd[$i]["a"]);
				}
				elseif ($dt[$i]["#text"] == "Subtheme")
				{
					$ret->{'Subtheme'} = trim($dd[$i]["a"]);
				}
				elseif ($dt[$i]["#text"] == "Year released")
				{
					$ret->{'Year'} = trim($dd[$i]["a"]);
				}
				elseif ($dt[$i]["#text"] == "Pieces")
				{
					$ret->{'Pieces'} = trim($dd[$i]["a"]);
				}
				elseif ($dt[$i]["#text"] == "Age range")
				{
					$ret->{'Age'} = str_replace(" ", "", trim($dd[$i]["#text"]));
				}
				elseif ($dt[$i]["#text"] == "Minifigs")
				{
					$ret->{'Minifigs'} = trim($dd[$i]["a"]);
				}
				elseif ($dt[$i]["#text"] == "RRP")
				{
					if (preg_match("/US.(\d+\.\d+)/", trim($dd[$i]["#text"]), $m))
					{
						$ret->{'USPrice'} = (float)$m[1];
					}
				}
				elseif ($dt[$i]["#text"] == "Barcodes")
				{
					$barcodes = trim($dd[$i]["#text"]);
					//echo var_dump($dd[$i]);
					if (preg_match('/UPC: (\d{12})/', $barcodes,  $matches))
					{
						$ret->{'UPC'} = (int)$matches[1];
					}
					else
					{
						$ret->{'UPC'} = null;
					}
					if (preg_match('/EAN: (\d{13})/', $barcodes,  $matches))
					{
						$ret->{'EAN'} = (int)$matches[1];
					}
					else
					{
						$ret->{'EAN'} = null;
					}
				}
				elseif ($dt[$i]["#text"] == "LEGO item numbers")
				{
					$legosnstr = trim($dd[$i]["#text"]);
					if (preg_match('/NA: (\d{7})/', $legosnstr,  $m))
					{
						$ret->{'USItemSN'} = (int)$m[1];
					}
					if (preg_match('/EU: (\d{7})/', $legosnstr,  $m))
					{
						$ret->{'EUItemSN'} = (int)$m[1];
					}
				}											
			}
		}
		else
		{
			return false;
		}
	}
	return $ret;
}

function crawl_toysrus()
{
	$page = 1;
	$perpage = 200; 
	$ret = new stdClass();
	$ret->{'Provider'} = "toysrus.com";
	$ret->{'URL'} = "http://www.toysrus.com/family/index.jsp?categoryId=31820206&view=all";
	$ret->{'ItemCount'} = 0;
	$ret->{'Items'} = array();
	for ($i = 1; $i <= $page; $i++)
	{
		$htmldom = curl_htmldom($ret->{'URL'}."&page=$i");


		$totalstr = trim(preg_replace("/\s+/u", " ", html_entity_decode($htmldom->find('//div[@id="contentright"]/div[class="showingText"]', 0)->plaintext, ENT_NOQUOTES, 'UTF-8')));
		if (preg_match("/Showing (\d+) - (\d+) of (\d+) results/u", $totalstr, $matches))
		{
			$page = intval(ceil($matches[3]/$perpage));
		}

		$items = $htmldom->find('//div[@id="familyProducts"]/div[class^="clearfix prodloop_row_cont"]/div[class^="prodloop_float"]/div[class="prodloop_cont"]');

		foreach ($items as $item)
		{
			$retItem = new stdClass();
			$retItem->{'ToysrusID'} = $item->find('/div[class="varHeightTop"]/div[class="prodloop-thumbnail"]/div[class="expressShopButtonGlobal button"]',0)->getAttribute('data-productid');

			$retItem->{'Title'} = trim(html_entity_decode($item->find('/div[class="varHeightTop"]/a[class="prodtitle"]',0)->plaintext, ENT_NOQUOTES, 'UTF-8'));

			preg_match_all("/\d{4,8}/u", $retItem->{'Title'}, $matches);
			if (isset($matches))
			{
				$legoID = intval(array_pop(array_pop($matches)));
				if ($legoID > intval(date('Y')))
				{
					$retItem->{'LegoID'} = $legoID;
				}
				else
				{
					$retItem->{'LegoID'} = null;
				}
			}
			else
			{
				$retItem->{'LegoID'} = null;
			}

			if ($item->find('/div[class="varHeightTop"]/div[class="prodPrice familyPrices"]/span[class="adjusted ourPrice2"]',0))
			{
				$retItem->{'Price'} = trim(str_replace("$", "", html_entity_decode($item->find('/div[class="varHeightTop"]/div[class="prodPrice familyPrices"]/span[class="adjusted ourPrice2"]',0)->plaintext, ENT_NOQUOTES, 'UTF-8')));
			}
			else
			{
				$retItem->{'Price'} = trim(str_replace("$", "", html_entity_decode($item->find('/div[class="varHeightTop"]/div[class="prodPrice familyPrices"]/span[class="ourPrice2"]',0)->plaintext, ENT_NOQUOTES, 'UTF-8')));

			}

			$ul = $item->find('ul[@id="eligibility"]/li');

			$shipping = 1;
			$pickup = 1;

			foreach ($ul as $li)
			{
				if (preg_match("/unavail/", $li->getAttribute("class")))
				{
					switch (trim($li->plaintext))
					{
						case "Ship-To-Home":
							$shipping = 0;
							break;
						case "Free Store Pickup":
							$pickup = 0;
							break;
					}
				}
			}

			if ($shipping + $pickup == 2)
			{
				$retItem->{'Availability'} = "Available";
			}
			elseif ($shipping)
			{
				$retItem->{'Availability'} = "Shipping Only";
			}
			elseif ($pickup)
			{
				$retItem->{'Availability'} = "Pickup Only";
			}
			else
			{
				$retItem->{'Availability'} = "Sold Out";
			}
			$ret->{'ItemCount'}++;
			array_push($ret->{'Items'}, $retItem);
		}
	}
	return $ret;
}


function crawl_amazon()
{
	$page = 1;
	$perpage = 24; 
	$maxpage = 20; //can't pull more than 20 pages at one time.
	$ret = new stdClass();
	$ret->{'Provider'} = "amazon.com";
	$ret->{'URL'} = "http://www.amazon.com/s?ie=UTF8&rh=n%3A165793011%2Cp_4%3ALEGO%2Cp_6%3AATVPDKIKX0DER";
	$ret->{'ItemCount'} = 0;
	$ret->{'Items'} = array();
	for ($i = 1; $i <= $page; $i++)
	{
		$htmldom = curl_htmldom($ret->{'URL'}."&page=$i");

		$totalstr = trim(preg_replace("/\s+/u", " ", html_entity_decode($htmldom->find('//*[@id="s-result-count"]', 0)->plaintext, ENT_NOQUOTES, 'UTF-8')));
		if (preg_match("/(\d+)-(\d+) of (\d+) results for/u", $totalstr, $matches))
		{
			$page = min($maxpage,intval(ceil($matches[3]/$perpage)));			
		}

		$items = $htmldom->find('/li[id^="result_"]');

		foreach ($items as $item)
		{
			$retItem = new stdClass();
			$retItem->{'ASIN'} = $item->getAttribute("data-asin");
			$retItem->{'Title'} = trim(html_entity_decode($item->find('/div/div/div/a/h2',0)->plaintext, ENT_NOQUOTES, 'UTF-8'));


			preg_match_all("/\d{4,8}/u", $retItem->{'Title'}, $matches);
			if (isset($matches))
			{
				$legoID = intval(array_pop(array_pop($matches)));
				if ($legoID > intval(date('Y')))
				{
					$retItem->{'LegoID'} = $legoID;
				}
			}
			else
			{
				$retItem->{'LegoID'} = null;
			}
			$retItem->{'Availability'} = "Available";
			$retItem->{'Price'} = trim(str_replace("$", "", $item->find('span[class="a-size-base a-color-price s-price a-text-bold"]',0)->plaintext));

			$ret->{'ItemCount'}++;
			array_push($ret->{'Items'}, $retItem);
		}

	}
	return $ret;
}

function crawl_lego()
{
	$page = 1;
	$perpage = 500; 
	$ret = new stdClass();
	$ret->{'Provider'} = "shop.lego.com";
	$ret->{'URL'} = "http://search-en.lego.com/?cc=us";
	$ret->{'ItemCount'} = 0;
	$ret->{'Items'} = array();
	for ($i = 1; $i <= $page; $i++)
	{
		$htmldom = curl_htmldom($ret->{'URL'}."&count=$perpage&page=$i");

		$totalstr = trim(preg_replace("/\s+/u", " ", $htmldom->find('//*[@id="adab-tools-top"]/div[2]/span[1]', 0)->plaintext));

		if (preg_match("/Items (\d+) - (\d+) of (\d+)/u", $totalstr, $matches))
		{
			$page = ceil($matches[3]/$perpage);			
		}

		$items = $htmldom->find('//div[@id="adab-results"]/ul[@id="product-results"]/li[class^="product-thumbnail test-product"]');
		foreach ($items as $item)
		{
			$LegoID = $item->find('/h4/span[class="item-code"]', 0)->plaintext;
			if (isset($LegoID) && $LegoID <> "")
			{
				$retItem = new stdClass();
				$retItem->{'LegoID'} = $LegoID;
				$retItem->{'Title'} = html_entity_decode($item->find('/h4/a[title]', 0)->plaintext, ENT_NOQUOTES, 'UTF-8');
				$retItem->{'Badge'} = html_entity_decode($item->find('/ul[@id="product-badges"]/li', 0)->plaintext, ENT_NOQUOTES, 'UTF-8');
				$retItem->{'URL'} = $item->find('/h4/a[title]', 0)->href;
				$strAvailability = trim(html_entity_decode($item->find('/ul/li[class^="availability"]/em', 0)->plaintext, ENT_NOQUOTES, 'UTF-8'));
				if ($strAvailability == "Retired product")
				{
					$retItem->{'Availability'} = "Retired";
				}
				elseif ($strAvailability == "Available Now")
				{
					$retItem->{'Availability'} = "Available";
				}
				elseif ($strAvailability == "Sold Out")
				{
					$retItem->{'Availability'} = "Sold Out";
				}
				elseif ((preg_match("/Out of stock/ui", $strAvailability)) || (preg_match("/Call to check/ui", $strAvailability)))
				{
					$retItem->{'Availability'} = "Out of Stock";
				}
				elseif (preg_match("/Coming Soon/ui", $strAvailability))
				{
					$retItem->{'Availability'} = "Coming Soon";
				}
				else
				{
					$retItem->{'Availability'} = "Unknown";
				}
				$retItem->{'Price'} = trim(str_replace("$", "", $item->find('/ul[class^="test-navigation-show-price-"]/li/em', 0)->plaintext));
				$saleprice = $item->find('/ul[class^="test-navigation-show-price-"]/li/em', 1)->plaintext;
				if (isset($saleprice))
				{
					$retItem->{'Saleprice'} = trim(str_replace("$", "", $saleprice));
				}
				$ret->{'ItemCount'}++;
				array_push($ret->{'Items'}, $retItem);
			}
		}	
	}

	return $ret;
}

function crawl_walmart()
{
	$page = 1;
	$perpage = 40; // max per page = 40
	$ret = new stdClass();
	$ret->{'Provider'} = "www.walmart.com";
	$ret->{'URL'} = "http://www.walmart.com/browse/toys/building-sets-blocks/4171_4186?cat_id=4171_4186&facet=brand:LEGO||Cretailer:Walmart.com";
	$ret->{'ItemCount'} = 0;
	$ret->{'Items'} = array();
	for ($i = 1; $i <= $page; $i++)
	{
		$url = $ret->{'URL'}."&page=".$i;
		$htmldom = curl_htmldom($url);
		$totalstr = $htmldom->find('//div[@id="search-container"]/div[class="search-container-sidebar"]/div[class="result-summary-container"]', 0)->plaintext;

		if (preg_match("/Showing (\d+) of (\d+) results/u", $totalstr, $matches))
		{
			if ($matches[1] == $perpage)
			{
				$page = ceil($matches[2]/$matches[1]);
			}
			
		}

		$items = $htmldom->find('/li[class="tile-grid-unit-wrapper"]/div[class="js-tile tile-grid-unit"]');

		foreach ($items as $item)
		{
			$retItem = new stdClass();

			if ($item->find('/div[class="tile-price"]/div[class="item-price-container"]/span[class$="price price-display"]', 0))
			{
				$retItem->{'Price'} = trim(str_replace("$", "", $item->find('/div[class="tile-price"]/div[class="item-price-container"]/span[class="price price-display"]', 0)->plaintext));

				if ($item->find('/div[class="tile-price"]/div[class="item-price-container"]/div[class="js-stock-message pick-up-only"]', 0))
				{
					$retItem->{'Availability'} = "Pickup Only";
				}
				else
				{
					$retItem->{'Availability'} = "Available";
				}
			}
			else
			{

				$retItem->{'Price'} = trim(str_replace("$", "", $item->find('/div[class="tile-price"]/div[class="item-price-container"]/span[class$="price price-display price-not-available"]', 0)->plaintext));
				$retItem->{'Availability'} = "Out of Stock";
			}

			$retItem->{'WalmartID'} = $item->getAttribute("data-item-id");

			//$retItem->{'URL'} = "http://www.walmart.com/ip/".$retItem->{'WalmartID'};

			$retItem->{'Title'} = trim(str_replace("&#39;", "'", str_replace(":", " ", html_entity_decode($item->find('/a[class="js-product-title"]/h3[class="tile-heading"]', 0)->plaintext, ENT_NOQUOTES, 'UTF-8'))));

			preg_match_all("/\d{4,8}/u", $retItem->{'Title'}, $matches);
			if (isset($matches))
			{
				$legoID = intval(array_pop(array_pop($matches)));
				if ($legoID > intval(date('Y')))
				{
					$retItem->{'LegoID'} = $legoID;
				}
			}
			else
			{
				$retItem->{'LegoID'} = null;
			}

			if (!in_array($retItem, $ret->{'Items'}))
			{
				$ret->{'ItemCount'}++;
				array_push($ret->{'Items'}, $retItem);
			}
		}
	}
	return $ret;

}

function crawl_bn()
{
	$page = 1;
	$perpage = 40; // max per page = 40
	for ($i = 0; $i < $page; $i++)
	{
		$url = "http://www.barnesandnoble.com/b/lego-systems/_/N-1z0lmfj?No=".($i*$perpage)."&Nrpp=".$perpage;
		$htmldom = curl_htmldom($url);
		$total = $htmldom->find('[@id="searchNotice"]/div/strong[2]', 0)->plaintext;
		$page = ceil($total/$perpage);

		$items = $htmldom->find('/ul[@id="gridView"]/li[class="clearer"]/ul/li/div[class="product-info"]');
		foreach ($items as $item)
		{
			$price = str_replace("$", "", $item->find('/ul/li/span[class="price"]/a', 0)->plaintext);
			$href = $item->find('/ul/li/span[class="price"]/a', 0)->href;

			preg_match_all("/\/w\/(.*)\/(\d+);.*\?ean=(\d+)/u", html_entity_decode($href, ENT_NOQUOTES, 'UTF-8'), $matches);
			$title = str_replace("toys games ", "", str_replace("-", " ", $matches[1][0]));
			$BNID = $matches[2][0];
			$EAN = $matches[3][0];

			preg_match_all("/\d{4,5}/u", html_entity_decode($title, ENT_NOQUOTES, 'UTF-8'), $matches);
			$legoid = array_pop(array_pop($matches));

			var_dump($legoid, $title, $BNID, $EAN, $price, "<br/>");
		}
	}
}

?>