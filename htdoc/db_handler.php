<?php
require_once("conn.php");
mb_internal_encoding('utf-8');

function db_query($table, $fields, $condition)
{
	$ret = new stdClass();

	$mysqli = new mysqli(MYSQL_SERVER_NAME, MYSQL_USERNAME, MYSQL_PASSWORD,  MYSQL_DATABASE);
	if ($mysqli->connect_errno)
	{
		$ret->{'Status'} = 1;
		$ret->{'Message'} = "Database Connect failed: ".$mysqli->connect_error;
		return json_encode($ret);
	}

	$mysqli->query("SET NAMES UTF8;");
	$mysqli->query("SET time_zone = '-07:00';");

	$fieldstr = implode(",", $fields);
	if ($condition <> "" && $condition <> null)
	{
		$condition = " WHERE ".$condition;
	}
	$query = "SELECT ".$mysqli->real_escape_string($fieldstr)." FROM ".$mysqli->real_escape_string($table).$condition.";";
	$ret->{'Query'} = $query;
	$result = $mysqli->query($query);

	if ($mysqli->connect_errno && isset($result))
	{
		var_dump($query);
	}
	//var_dump($query);
	$results = array();
	while ($row = $result->fetch_array(MYSQLI_ASSOC))
	{
		$item = new stdClass();
		foreach ($fields as $field)
		{
			if (preg_match("/\w+\s+AS\s+(\w+)/i", $field, $match))
			{
				$field = $match[1];
			}
			$item->{"$field"} = $row["$field"];
		}
		array_push($results, $item);
	}
	$ret->{'Status'} = 0;
	$ret->{'Results'} = $results;
	$ret->{'Count'} = count($results);

	$mysqli->close();

	return $ret;
}

function db_insert($table, $fields, $condition, $update_while_duplicate)
{
	$ret = new stdClass();

	$mysqli = new mysqli(MYSQL_SERVER_NAME, MYSQL_USERNAME, MYSQL_PASSWORD,  MYSQL_DATABASE);
	if ($mysqli->connect_errno)
	{
		$ret->{'Status'} = 1;
		$ret->{'Message'} = "Database Connect failed: ".$mysqli->connect_error;
		return json_encode($ret);
	}

	$mysqli->query("SET NAMES UTF8;");
	$mysqli->query("SET time_zone = '-07:00';");

	if ($condition <> "" && $condition <> null)
	{
		$condition = "WHERE ".$condition;
	}
	$fieldstr = "";
	foreach ($fields as $key => $value)
	{
		$fieldstr .= $mysqli->real_escape_string($key)."='".$mysqli->real_escape_string($value)."', ";
	}

	$fieldstr = trim($fieldstr, ", ");

	$query = "INSERT INTO ".$mysqli->real_escape_string($table)." SET ".$fieldstr;
	if ($update_while_duplicate)
	{
		$query .= " ON DUPLICATE KEY UPDATE ".$fieldstr;
	}
	$query .= " ".$mysqli->real_escape_string($condition).";";

	$ret->{'Query'} = $query;

	if ($mysqli->query($query))
	{
		$ret->{'Status'} = 0;
		$ret->{'InsertID'} = $mysqli->insert_id;
	}
	else
	{
		$ret->{'Status'} = 1;
		$ret->{'Message'} = $mysqli->error;
	}

	$mysqli->close();
	return $ret;
}

function db_delete($table, $condition)
{
	$ret = new stdClass();

	$mysqli = new mysqli(MYSQL_SERVER_NAME, MYSQL_USERNAME, MYSQL_PASSWORD,  MYSQL_DATABASE);
	if ($mysqli->connect_errno)
	{
		$ret->{'Status'} = 1;
		$ret->{'Message'} = "Database Connect failed: ".$mysqli->connect_error;
		return json_encode($ret);
	}

	$mysqli->query("SET NAMES UTF8;");
	$mysqli->query("SET time_zone = '-07:00';");

	$conditionstr = "";
	if (is_array($condition))
	{
		$conditionstr .= "WHERE ";
		foreach ($condition as $key => $value)
		{
			$conditionstr .= $mysqli->real_escape_string($key)."='".$mysqli->real_escape_string($value)."' AND ";
		}
		$conditionstr = trim($conditionstr, "AND ");
	}
	elseif ($condition <> "" && $condition <> null)
	{
		$conditionstr = "WHERE ".$mysqli->real_escape_string($condition);
	}

	$query = "DELETE FROM ".$mysqli->real_escape_string($table)." ".$conditionstr.";";

	$ret->{'Query'} = $query;
	var_dump($ret->{'Query'});
	if ($mysqli->query($query))
	{
		$ret->{'Status'} = 0;
	}
	else
	{
		$ret->{'Status'} = 1;
		$ret->{'Message'} = $mysqli->error;
	}
	
	//var_dump($ret);

	$mysqli->close();
	return $ret;


}

function db_update($table, $fields, $condition)
{
	$ret = new stdClass();

	$mysqli = new mysqli(MYSQL_SERVER_NAME, MYSQL_USERNAME, MYSQL_PASSWORD,  MYSQL_DATABASE);
	if ($mysqli->connect_errno)
	{
		$ret->{'Status'} = 1;
		$ret->{'Message'} = "Database Connect failed: ".$mysqli->connect_error;
		return json_encode($ret);
	}

	$mysqli->query("SET NAMES UTF8;");
	$mysqli->query("SET time_zone = '-07:00';");

	$conditionstr = "";
	if (is_array($condition))
	{
		$conditionstr .= "WHERE ";
		foreach ($condition as $key => $value)
		{
			$conditionstr .= $mysqli->real_escape_string($key)."='".$mysqli->real_escape_string($value)."' AND ";
		}
		$conditionstr = trim($conditionstr, "AND ");
	}
	elseif ($condition <> "" && $condition <> null)
	{
		$conditionstr = "WHERE ".$mysqli->real_escape_string($condition);
	}

	$fieldstr = "";
	foreach ($fields as $key => $value)
	{
		$fieldstr .= $mysqli->real_escape_string($key)."='".$mysqli->real_escape_string($value)."', ";
	}

	$fieldstr = trim($fieldstr, ", ");

	$query = "UPDATE ".$mysqli->real_escape_string($table)." SET ".$fieldstr." ".$conditionstr.";";

	$ret->{'Query'} = $query;
	if ($mysqli->query($query))
	{
		$ret->{'Status'} = 0;
	}
	else
	{
		$ret->{'Status'} = 1;
		$ret->{'Message'} = $mysqli->error;
	}
	
	//var_dump($ret);

	$mysqli->close();
	return $ret;
}

function pull_DBSet($fields, $condition)
{
	array_push($fields, "LegoID");

	$LegoDB = array();

	$ret = db_query("DB_Set", $fields, $condition);
	if (!$ret->{'Status'})
	{
		foreach ($ret->{'Results'} as $item)
		{
			$id = intval($item->{'LegoID'});
			$LegoDB[$id] = $item;
		}
		//ksort($LegoDB);
		return $LegoDB;
	}
	else
	{
		//echo $ret->{'Message'};
		return false;
	}
}

function insert_DBSet($field)
{
	$ret = db_insert("DB_Set", $field, null, false);
	if (!$ret->{'Status'})
	{
		return $ret->{'InsertID'};
	}
	else
	{
		//echo $ret->{'Message'};
		return false;
	}
}

function update_DBSet($identifier, $field)
{
	$ret = db_update("DB_Set", $field, $identifier);
	if (!$ret->{'Status'})
	{
		return true;
	}
	else
	{
		echo $ret->{'Message'};
		return false;
	}
}

function search_legoid($info)
{
	$ret = new stdClass();
	$starttime = microtime(true);

	if (!empty($info['Barcode']))
	{
		$barcode = $info['Barcode'];
		$ret->{'QueryBarcode'} = $barcode;

		if (preg_match("/^673419\d{6}$/", $barcode))
		{
			$type = "UPC";
		}
		elseif (preg_match("/^5\d{12}$/", $barcode))
		{
			$type = "EAN";
		}
		elseif (preg_match("/^0673419\d{6}$/", $barcode))
		{
			$type = "UPC";
			$barcode = substr($barcode, 1);
		}
		elseif (preg_match("/^\d{7}$/", $barcode))
		{
			$type = "ItemSN";
		}

		if (isset($type))
		{
			if ($type == "ItemSN")
			{
				$dbquery = db_query("DB_Set", array("LegoID", "ETitle"), "USItemSN = '".$barcode."' OR EUItemSN = '".$barcode."' LIMIT 1");
				if (!$dbquery->{'Status'})
				{
					if (count($dbquery->{'Results'}) == 1)
					{
						$ret->{'MatchID'} = $dbquery->{'Results'}[0]->{'LegoID'};
						$ret->{'MatchTitle'} = $dbquery->{'Results'}[0]->{'ETitle'};
						$ret->{'MatchType'} = $type;
						$ret->{'MatchValue'} = $barcode;
					}
				}
			}
			else
			{
				$dbquery = db_query("DB_Set", array("LegoID", "ETitle"), $type." = '".$barcode."' LIMIT 1");
				if (!$dbquery->{'Status'})
				{
					if (count($dbquery->{'Results'}) == 1)
					{
						$ret->{'MatchID'} = $dbquery->{'Results'}[0]->{'LegoID'};
						$ret->{'MatchTitle'} = $dbquery->{'Results'}[0]->{'ETitle'};
						$ret->{'MatchType'} = $type;
						$ret->{'MatchValue'} = $barcode;
					}
				}				
			}
		}
		else
		{
			$dbquery = db_query("DB_Set", array("LegoID", "ETitle"), "EAN = '".$barcode."' OR UPC = '".$barcode."' OR USItemSN = '".$barcode."' OR EUItemSN = '".$barcode."' OR 3rdCode = '".$barcode."' LIMIT 1");
			if (!$dbquery->{'Status'})
			{
				if (count($dbquery->{'Results'}) == 1)
				{
					$ret->{'MatchID'} = $dbquery->{'Results'}[0]->{'LegoID'};
					$ret->{'MatchTitle'} = $dbquery->{'Results'}[0]->{'ETitle'};
					$ret->{'MatchType'} = $type;
					$ret->{'MatchValue'} = $barcode;
				}
			}		
		}
		//var_dump($type, $barcode);
	}
	elseif (!empty($info['Title']))
	{
		$title = $info['Title'];
		$ret->{'QueryTitle'} = $title;
		$title = trim(preg_replace("/[®|™]/ui", "", $title));
		$title = trim(preg_replace("/(\s*)lego(\s*)/ui", "", $title));
		$title = trim(preg_replace("/(\s*)(play)|(build)(ing)*(\s*)(set)*/ui", "", $title));
		$title = trim(preg_replace("/[\'|’](s*)/ui", "", $title));

		$ret->{'NormalizedTitle'} = $title;

		$searchterms = array();
		array_push($searchterms, $title);

		$terms = explode(" ", $title);

		$n = count($terms);
		for ($k = $n - 1; $k >= 1; $k--)
		{
			
			for ($i = 0; $i <= $n - $k; $i++)
			{
				$searchterm = "";
				for ($j = $i; $j < $i + $k; $j ++)
				{
					$searchterm .= $terms[$j]." ";
				}
				$searchterm = trim($searchterm);
				array_push($searchterms, $searchterm);

			}
		}

		$IDWeights = array();
		$IDTitle = array();
		foreach($searchterms as $term)
		{
			$dbquery = db_query("DB_Set", array("LegoID", "ETitle"), "ETitle LIKE '%".$term."%'");
			if (!$dbquery->{'Status'})
			{
				$count = count($dbquery->{'Results'});
				if ($count)
				{
					foreach ($dbquery->{'Results'} as $item)
					{
						$IDTitle[$item->{'LegoID'}] = $item->{'ETitle'};
						if (isset($IDWeights[$item->{'LegoID'}]))
						{
							$IDWeights[$item->{'LegoID'}] += floatval(1/$count);
						}
						else
						{
							$IDWeights[$item->{'LegoID'}] = floatval(1/$count);
						}
						
					}
				}
			}
		}

		arsort($IDWeights);

		//var_dump($searchterms);
		$ret->{'MatchID'} = null;
		if(!empty($IDWeights))
		{
			$ret->{'IDWeights'} = $IDWeights;

			$r = each($IDWeights);
			$topValue = $r['value'];

			if ($r['value'] > 2)
			{
				$ret->{'MatchID'} = $r['key'];
				$ret->{'MatchWeight'} = $r['value'];
				$ret->{'MatchTitle'} = $IDTitle[$r['key']];
				$ret->{'SearchTerms'} = $searchterms;

			}
			else
			{

				$i = 0;
				do
				{
					$possTitle = preg_replace('/[\'|’|®|™|\.]/u', "", $IDTitle[$r['key']]);
					$matchTitle = preg_replace('/[\'|’|®|™|\.]/u', "", $ret->{'QueryTitle'});
					//var_dump($IDTitle[$r['key']], $possTitle, $matchTitle);
					if (preg_match('/'.$possTitle.'/i', $matchTitle))
					{
						$ret->{'MatchID'} = $r['key'];
						$ret->{'MatchWeight'} = $r['value'];
						$ret->{'MatchTitle'} = $IDTitle[$r['key']];
						$ret->{'SearchTerms'} = $searchterms;
						break;
					}

					$ret->{'MatchID'} = null;
					$ret->{'MatchTitles'} = $IDTitle;
					$ret->{'MatchWeight'} = $r['value'];
					$ret->{'SearchTerms'} = $searchterms;
					$r = each($IDWeights);
					$i++;
				}
				while ($r['value'] >= $topValue && $i < 10);

			}
		
		}

	}
	else
	{
		$ret->{'MatchID'} = null;
	}
	$endtime = microtime(true);
	$duration = $endtime - $starttime;
	$ret->{'SearchDuration'} = sprintf("%.3f", $duration);

	return $ret;
}

?>