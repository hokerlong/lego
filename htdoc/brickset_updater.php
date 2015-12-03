<?php
require_once("crawlers.php");
require_once("db_handler.php");
require_once("twitter_handler.php");

$ret = db_query("DB_Theme", array("ThemeID", "ETheme"), null);
if (!$ret->{'Status'})
{
	$ThemeDB = array();

	foreach ($ret->{'Results'} as $theme)
	{
		$idx = $theme->{'ETheme'};
		$ThemeDB[$idx] = $theme->{'ThemeID'};
	}

	$ret = db_query("DB_Set", array("LegoID", "ThemeID", "USPrice", "Year", "Pieces", "Minifigs", "Age", "Weight", "Length", "Width", "Height", "UPC", "EAN", "USItemSN", "EUItemSN", "BK_Subset"), "LastSync = '0000-00-00 00:00:00' OR LastSync < '".date('Y-m-d H:i:s', strtotime('-7 days'))."' ORDER BY LastSync LIMIT 20");
	if (!$ret->{'Status'})
	{
		foreach ($ret->{'Results'} as $item)
		{
			$bkinfo = crawl_brickset($item->{'LegoID'}, $item->{'BK_Subset'});
			$blinfo = crawl_bricklink($item->{'LegoID'}, $item->{'BK_Subset'});
			foreach (array("Weight", "Length", "Width", "Height") as $prop)
			{
				$bkinfo->{$prop} = $blinfo->{$prop};
			}

			if (isset($bkinfo))
			{
				$arrfields = array();

				if (isset($ThemeDB[$bkinfo->{'Theme'}]))
				{
					if ($ThemeDB[$bkinfo->{'Theme'}] <> $item->{'ThemeID'})
					{
						$arrfields['ThemeID'] = $ThemeDB[$bkinfo->{'Theme'}];
					}
				}
				else
				{
					//Create new ThemeID
					if (!empty($bkinfo->{'Theme'}))
					{
						$dbr = db_insert('DB_Theme', array("ETheme" => $bkinfo->{'Theme'}), null, false);

						if (!$dbr->{'Status'})
						{
							$arrfields['ThemeID'] = $dbr->{'InsertID'};
							$ThemeDB[$bkinfo->{'Theme'}] = $dbr->{'InsertID'};
						}
						else
						{
							$arrfields['ThemeID'] = 0;
						}						
					}
				}
				
				$arrProp = array("USPrice", "Year", "Pieces", "Minifigs", "Age", "Weight", "Length", "Width", "Height", "UPC", "EAN", "USItemSN", "EUItemSN");
				foreach ($arrProp as $prop)
				{
					if (!empty($bkinfo->{$prop}) && $bkinfo->{$prop} <> $item->{$prop})
					{
						$arrfields[$prop] = $bkinfo->{$prop};
					}
				}

				$arrfields['LastSync'] = date('Y-m-d H:i:s');
				$ret = db_update("DB_Set", $arrfields, array("LegoID" => $item->{'LegoID'}));
				if (count($arrfields) > 1 || $ret->{'Status'})
				{
					echo $ret->{'Query'}."\n";
					unset($arrfields['LastSync']);
					$strupdate = "";
					foreach ($arrfields as $prop => $value)
					{
						$strupdate .= $prop."[".$item->{$prop}."=>".$value."], ";
					}
					$strupdate = trim($strupdate, ", ");
					send_Message(NOTIFICATION_RECIPIENT, "DB_Set updated: ".$item->{'LegoID'}." ".$strupdate);
				}
			}
		}
	}
	else
	{
		var_dump($ret);
	}
}
else
{
	var_dump($ret);
}


?>