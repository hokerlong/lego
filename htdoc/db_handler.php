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
	$query = "SELECT ".$mysqli->real_escape_string($fieldstr)." FROM ".$mysqli->real_escape_string($table).$mysqli->real_escape_string($condition).";";
	$ret->{'Query'} = $query;
	$result = $mysqli->query($query);

	$results = array();
	while ($row = $result->fetch_array(MYSQLI_ASSOC))
	{
		$item = new stdClass();
		foreach ($fields as $field)
		{
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
?>