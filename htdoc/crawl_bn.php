<?php
require_once("simple_html_dom.php");
mb_internal_encoding('utf-8');

crawl_walmart ();
function curl_htmldom($url)
{
	$ch = curl_init(); 
	$timeout = 5; 
	curl_setopt($ch, CURLOPT_URL, $url); 
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

function crawl_walmart()
{
	$page = 1;
	$perpage = 40; // max per page = 40
	for ($i = 1; $i <= $page; $i++)
	{
		$url = "http://www.walmart.com/browse/toys/building-sets-blocks/4171_4186?cat_id=4171_4186&facet=brand:LEGO&page=".$i;
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
			$price = trim(str_replace("$", "", $item->find('/div[class="tile-price"]/div[class="item-price-container"]/span[class="price price-display"]', 0)->plaintext));
			$WalmartID = $item->getAttribute("data-item-id");

			$href = "http://www.walmart.com/ip/".$WalmartID;

			$title = trim(str_replace("LEGO ", "", str_replace(":", " ", $item->find('/a[class="js-product-title"]/h3[class="tile-heading"]', 0)->plaintext)));

			preg_match_all("/\d{4,5}/u", html_entity_decode($title, ENT_NOQUOTES, 'UTF-8'), $matches);
			$legoid = array_pop(array_pop($matches));

			var_dump($legoid, $title, $WalmartID, $price, "<br/>");
		}

		/*
		foreach($html->find('//div[@class="prodInfoBox"]') as $item)
		{
			$title = str_replace(" Building Set", "", str_replace(" Play Set", "", $item->find('./a[@class=\'prodLink ListItemLink\']',0)->plaintext));
			$walmartID = substr($item->find('./a[@class=\'prodLink ListItemLink\']',0)->href, -8);
			$priceint = str_replace("$", "", str_replace(".", "", $item->find('./div/div/div/div[@class=\'camelPrice\']/span[@class=\'bigPriceText2\']', 0)->plaintext));
			$pricedec = $item->find('./div/div/div/div[@class=\'camelPrice\']/span[@class=\'smallPriceText2\']', 0)->plaintext;
			$price = $priceint + 0.01 * $pricedec;

			if (!isset($WalmartIDs["$walmartID"]))
			#不在数据库内的新套装：
			{
				$pic = str_replace("180X180", "500X500", $item->parent()->parent()->find("./div/a/img",0)->getAttribute('src'));
				preg_match_all("/\d{4,8}/u", html_entity_decode($title, ENT_NOQUOTES, 'UTF-8'), $matches);
				$legoid = array_pop(array_pop($matches));
				if (isset($legoid))
				{
					echo "<tr><td><img src=\"".$pic."\"></td><td><a href=\"http://www.walmart.com/ip/".$walmartID."\" target=\"_blank\">".$walmartID."</a></td><td>".$title."</td><td><input type=\"text\" name=\"".$walmartID."\" size=\"6\" value=\"".$legoid."\" /></td><td><input type=\"radio\" checked=\"checked\" name=\"rad_$walmartID\" value=\"import\" />导入<br /><input type=\"radio\" name=\"rad_$walmartID\" value=\"ignore\" />暂不<br /><input type=\"radio\" name=\"rad_$walmartID\" value=\"never\" />永不</td></tr>";
				}
				else
				{
					echo "<tr><td><div style=\"background-image:url($pic); background-position:left;height:150; width:150;\"></div></td><td><a href=\"http://www.walmart.com/ip/".$walmartID."\" target=\"_blank\">".$walmartID."</a></td><td>".$title."</td><td><input type=\"text\" name=\"".$walmartID."\" size=\"6\" value=\"".$legoid."\" /></td><td><input type=\"radio\" name=\"rad_$walmartID\" value=\"import\" />导入<br /><input type=\"radio\" name=\"rad_$walmartID\" value=\"ignore\" checked=\"checked\" />暂不<br /><input type=\"radio\" name=\"rad_$walmartID\" value=\"never\" />永不</td></tr>";
				}
			}
		}
		*/
	}
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
/*

			#不在数据库内的新套装：
			{
				$pic = "http://img1.imagesbn.com/p/".$EAN."_p0_v2_s600.jpg";
				preg_match_all("/\d{4,8}/u", html_entity_decode($title, ENT_NOQUOTES, 'UTF-8'), $matches);
				$legoid = array_pop(array_pop($matches));
				if (isset($legoid))
				{
					echo "<tr><td></td><td><a href=\"http://www.barnesandnoble.com/p/?ean=".$EAN."\" target=\"_blank\">".$EAN."</a></td><td>".$title."</td><td><input type=\"text\" name=\"".$EAN."\" size=\"6\" value=\"".$legoid."\" /></td><td><input type=\"radio\" checked=\"checked\" name=\"rad_$EAN\" value=\"import\" />导入<br /><input type=\"radio\" name=\"rad_$EAN\" value=\"ignore\" />暂不<br /><input type=\"radio\" name=\"rad_$EAN\" value=\"never\" />永不</td></tr>";
				}
				else
				{
					echo "<tr><td><div style=\"background-image:url($pic); background-position:left;height:150; width:150;\"></div></td><td><a href=\"http://www.barnesandnoble.com/p/?ean=".$EAN."\" target=\"_blank\">".$EAN."</a></td><td>".$title."</td><td><input type=\"text\" name=\"".$EAN."\" size=\"6\" value=\"".$legoid."\" /></td><td><input type=\"radio\" name=\"rad_$EAN\" value=\"import\" />导入<br /><input type=\"radio\" name=\"rad_$EAN\" value=\"ignore\" checked=\"checked\" />暂不<br /><input type=\"radio\" name=\"rad_$EAN\" value=\"never\" />永不</td></tr>";
				}
			}
		}
	}
*/
?>