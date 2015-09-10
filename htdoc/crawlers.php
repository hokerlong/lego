<?php
require_once("simple_html_dom.php");
ini_set('memory_limit', '256M');

mb_internal_encoding('utf-8');

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
			if (isset($LegoID))
			{
				$retItem = new stdClass();
				$retItem->{'LegoID'} = $LegoID;
				$retItem->{'Title'} = $item->find('/h4/a[title]', 0)->plaintext;
				//$retItem->{'URL'} = $item->find('/h4/a[title]', 0)->href;
				$retItem->{'Availability'} = $item->find('/ul/li[class^="availability"]/em', 0)->plaintext;
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

	return json_encode($ret);
}

function crawl_walmart()
{
	$page = 1;
	$perpage = 40; // max per page = 40
	$ret = new stdClass();
	$ret->{'Provider'} = "www.walmart.com";
	$ret->{'URL'} = "http://www.walmart.com/browse/toys/building-sets-blocks/4171_4186?cat_id=4171_4186&facet=brand:LEGO";
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
			$retItem->{'Price'} = trim(str_replace("$", "", $item->find('/div[class="tile-price"]/div[class="item-price-container"]/span[class="price price-display"]', 0)->plaintext));
			$retItem->{'WalmartID'} = $item->getAttribute("data-item-id");

			//$retItem->{'URL'} = "http://www.walmart.com/ip/".$retItem->{'WalmartID'};

			$retItem->{'Title'} = trim(str_replace("LEGO ", "", str_replace(":", " ", $item->find('/a[class="js-product-title"]/h3[class="tile-heading"]', 0)->plaintext)));

			preg_match_all("/\d{4,5}/u", html_entity_decode($retItem->{'Title'}, ENT_NOQUOTES, 'UTF-8'), $matches);
			$retItem->{'LegoID'} = array_pop(array_pop($matches));

			$ret->{'ItemCount'}++;
			array_push($ret->{'Items'}, $retItem);
		}
	}
	return json_encode($ret);

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