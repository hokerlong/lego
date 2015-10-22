<?php
ob_start('ob_gzhandler');

require_once("../db_handler.php");


$ret = db_query("Twitter_News", array("Provider", "Type", "Title", "Link", "PicPath", "PubDate"), "Hash = '".$_GET['hash']."'");
if (!$ret->{'Status'})
{
	if (!empty($ret->{'Results'}[0]))
	{
		$item = $ret->{'Results'}[0];
		echo json_encode($item);

	}
}
?>