<?php
//
// Pull the items from barnesandnoble.com
//

require_once("simple_html_dom.php");
mb_internal_encoding('utf-8');

crawl_bn ();
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
