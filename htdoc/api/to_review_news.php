<?php
ob_start('ob_gzhandler');

require_once("../db_handler.php");

switch ($_GET['action']) {
	case 'approve':
		db_update("Twitter_News", array("Status" => "Y"), array("Hash" => $_GET['hash']));
		break;
	case 'reject':
		db_update("Twitter_News", array("Status" => "R"), array("Hash" => $_GET['hash']));
		break;	
	default:
		$ret = db_query("Twitter_News", array("Provider", "Type", "Title", "Link", "PicPath", "Status"), array("Hash" => $_GET['hash']));
		if (!$ret->{'Status'})
		{
			if (!empty($ret->{'Results'}[0]))
			{
				$item = $ret->{'Results'}[0];
				echo json_encode($item);

			}
		}
		break;
}

?>