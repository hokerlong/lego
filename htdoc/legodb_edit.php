<?php
require("conn.php");
require_once("simple_html_dom.php");
$legoid=$_POST["LegoID"];
$update=$_GET["t"];
$defaultid=$_GET["id"];

if (isset($update))
{
	$mysqli = new mysqli($mysql_server_name, $mysql_username, $mysql_password, $mysql_database);

	if (mysqli_connect_errno()) {
		printf("Database Connect failed: %s\n", mysqli_connect_error());
		exit();
	}

	$mysqli->query("SET NAMES UTF8;");
	$mysqli->query("SET time_zone = '+08:00';");
	if ($_POST["Update_Set"])
	{
		$query = "UPDATE DB_Set SET ETitle='".$mysqli->real_escape_string($_POST["DB_ETitle"])."', CTitle='".$mysqli->real_escape_string($_POST["DB_CTitle"])."', ThemeID='".$mysqli->real_escape_string($_POST["DB_Theme"])."', Age='".$mysqli->real_escape_string($_POST["DB_Age"])."', Minifigs='".$mysqli->real_escape_string($_POST["DB_Minifigs"])."', `Year`='".$mysqli->real_escape_string($_POST["DB_Year"])."', Pieces='".$mysqli->real_escape_string($_POST["DB_Pieces"])."', Weight='".$mysqli->real_escape_string($_POST["DB_Weight"])."', Length='".$mysqli->real_escape_string($_POST["DB_Length"])."', Width='".$mysqli->real_escape_string($_POST["DB_Width"])."', Height='".$mysqli->real_escape_string($_POST["DB_Height"])."', USPrice='".$mysqli->real_escape_string($_POST["DB_USPrice"])."', CNPrice='".$mysqli->real_escape_string($_POST["DB_CNPrice"])."', UPC='".$mysqli->real_escape_string($_POST["DB_UPC"])."', EAN='".$mysqli->real_escape_string($_POST["DB_EAN"])."', ItemSN='".$mysqli->real_escape_string($_POST["DB_ItemSN"])."' WHERE LegoID='".$mysqli->real_escape_string($_POST["LegoID"])."' LIMIT 1;";
		$result = $mysqli->query($query);
	}
	else
	{
		$query = "INSERT INTO DB_Set(LegoID, ETitle, CTitle, ThemeID, Age, Minifigs, `Year`, Pieces, Weight, Length, Width, Height, USPrice, CNPrice, UPC, EAN, ItemSN) VALUES ('".$mysqli->real_escape_string($_POST["LegoID"])."','".$mysqli->real_escape_string($_POST["DB_ETitle"])."','".$mysqli->real_escape_string($_POST["DB_CTitle"])."','".$mysqli->real_escape_string($_POST["DB_Theme"])."','".$mysqli->real_escape_string($_POST["DB_Age"])."','".$mysqli->real_escape_string($_POST["DB_Minifigs"])."','".$mysqli->real_escape_string($_POST["DB_Year"])."','".$mysqli->real_escape_string($_POST["DB_Pieces"])."','".$mysqli->real_escape_string($_POST["DB_Weight"])."','".$mysqli->real_escape_string($_POST["DB_Length"])."','".$mysqli->real_escape_string($_POST["DB_Width"])."','".$mysqli->real_escape_string($_POST["DB_Height"])."','".$mysqli->real_escape_string($_POST["DB_USPrice"])."','".$mysqli->real_escape_string($_POST["DB_CNPrice"])."','".$mysqli->real_escape_string($_POST["DB_UPC"])."','".$mysqli->real_escape_string($_POST["DB_EAN"])."','".$mysqli->real_escape_string($_POST["DB_ItemSN"])."');";
		$result = $mysqli->query($query);
	}
	if ($_POST["DB_USASIN"] != "")
	{
		if ($_POST["Update_USASIN"])
		{
			$query = "UPDATE PW_AmazonInfo SET ASIN='".$mysqli->real_escape_string($_POST["DB_USASIN"])."', Scan=1 WHERE LegoID='".$mysqli->real_escape_string($_POST["LegoID"])."' AND Country='US' LIMIT 1;";
			$result = $mysqli->query($query);
		}
		else
		{
			$query = "INSERT INTO PW_AmazonInfo(Country, LegoID, ASIN, Scan) VALUES ('US', '".$mysqli->real_escape_string($_POST["LegoID"])."', '".$mysqli->real_escape_string($_POST["DB_USASIN"])."', 1);";
			$result = $mysqli->query($query);
		}
	}
	if ($_POST["DB_CNASIN"] != "")
	{
		if ($_POST["Update_CNASIN"])
		{
			$query = "UPDATE PW_AmazonInfo SET ASIN='".$mysqli->real_escape_string($_POST["DB_CNASIN"])."', Scan=1 WHERE LegoID='".$mysqli->real_escape_string($_POST["LegoID"])."' AND Country='CN' LIMIT 1;";
			$result = $mysqli->query($query);
		}
		else
		{
			$query = "INSERT INTO PW_AmazonInfo(Country, LegoID, ASIN, Scan) VALUES ('CN', '".$mysqli->real_escape_string($_POST["LegoID"])."', '".$mysqli->real_escape_string($_POST["DB_CNASIN"])."', 1);";
			$result = $mysqli->query($query);
		}
	}	
	//echo $query;
	echo "OK";
	exit;

}
else
{
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<style type="text/css">
.bk {cursor:pointer; color: white; background-color: #4667a4;}
.bl {cursor:pointer; color: black; background-color: #63b8ff}
.amzn {cursor:pointer; color: #c60000}
.amzncn {cursor:pointer; color: #E47911}
	</style>
	<script type="text/javascript" src="http://ossweb-img.qq.com/images/js/jquery/jquery-1.7.1.min.js"></script>
  	<script language="JavaScript">
  	function query_all()
  	{
  		var legoid = $("#LegoID").val();
  		
  		$("#bk_loading").html("<span class=\"bk\">BrickSet: <img src=\"/images/loading.gif\" height=\"14px\"></span>");
  		$("#bl_loading").html("<span class=\"bl\">BrickLink: <img src=\"/images/loading.gif\" height=\"14px\"></span>");
  		$("#amzn_loading").html("<span class=\"amzn\">amazon.com: <img src=\"/images/loading.gif\" height=\"14px\"></span>");
  		$("#amzncn_loading").html("<span class=\"amzncn\">amazon.cn: <img src=\"/images/loading.gif\" height=\"14px\"></span>");
		$.get("ajax_bk.php", {legoid: legoid}, function(data) {fetch_data('bk', data);} );
		$.get("ajax_bl.php", {legoid: legoid}, function(data) {fetch_data('bl', data);} );
		$.get("ajax_amzn.php", {legoid: legoid}, function(data) {fetch_data('amzn', data);} );
		$.get("ajax_zcn.php", {legoid: legoid}, function(data) {fetch_data('amzncn', data);} );

  	}
	function fetch_data(provider, data)
	{
		$("#"+provider+"_loading").html("<span class=\""+provider+"\">"+provider+": Loaded.</span>");
		var obj = jQuery.parseJSON(data);
	
		$.each(obj, function(key, value)
		{
			if ($("#DB_"+key).val() == obj[key])
			{
				$("#"+provider+"_"+key).css("text-decoration","line-through");
			}
			$("#"+provider+"_"+key).html(obj[key]);

		});
		if (typeof obj['Length'] !== "undefined")
		{
			var demesion = obj['Length']+"x"+obj['Width']+"x"+obj['Height'];
			$("#"+provider+"_Demesion").html(demesion);
		}
		$("#"+provider+"_loading").hide(1000);
	}
	function fill_data(provider, field)
	{
		if (field == 'Theme')
		{
			select_theme('txt', $("#"+provider+"_"+field).html());
		}
		else if (field == 'Demesion')
		{
			var res = $("#"+provider+"_"+field).html().split("x");
			$("#DB_Length").val(res[0]);
			$("#DB_Width").val(res[1]);
			$("#DB_Height").val(res[2]);
		}
		else
		{
			$("#DB_"+field).val($("#"+provider+"_"+field).html());
		}
	}
	function select_theme(type, data)
	{
		if (type == 'id')
		{
			$("#DB_Theme").find('option[value="'+data+'"]').attr("selected",true);
		}
		else
		{
			$("#DB_Theme").find('option:contains("'+data+'")').attr("selected",true);
		}
	}
	function submit_result()
	{
		$.post("legodb_edit.php?t=update", $("form").serialize(), function(data) { if (data=="OK") {$("#LegoID").select(); $("#LegoID").focus();} else { alert(data); } } );
	}

	</script>
	<title>数据库维护</title>
</head>
<?php
	if (isset($legoid))
	{
		echo "<body onload=\"query_all()\">";
	}
	else
	{
		echo "<body>";
	}
?>
<form action='' method='post'>
<input type="text" id="LegoID" name="LegoID" size="6" value="<?php if (isset($legoid)) {echo $legoid;} else {echo $defaultid;} ?>"/><input type="submit" value="找"><br />
<div id="bk_loading"></div><div id="bl_loading"></div><div id="amzn_loading"></div><div id="amzncn_loading"></div>
<?
	if (isset($legoid))
	{
		$mysqli = new mysqli($mysql_server_name, $mysql_username, $mysql_password, $mysql_database);

		if (mysqli_connect_errno()) {
			printf("Database Connect failed: %s\n", mysqli_connect_error());
			exit();
		}

		$mysqli->query("SET NAMES UTF8;");
		$mysqli->query("SET time_zone = '+08:00';");

		$theme_str = "";
		$query = "SELECT * FROM DB_Theme ORDER BY ThemeID;";
		$result = $mysqli->query($query);
		while ($row = $result->fetch_array(MYSQLI_ASSOC))
		{
			$theme_str = $theme_str."<option value=\"".$row['ThemeID']."\">".$row['ETheme']." | ".$row['CTheme']."</option>";
		}
		
		$query="SELECT * FROM DB_Set WHERE LegoID = '".$legoid."';";
		$result = $mysqli->query($query);
		$row = $result->fetch_array(MYSQLI_ASSOC);
		if (isset($row['LegoID']))
		{
			$update_set = 1;
			$update_str = "更新";
		}
		else
		{
			$update_set = 0;
			$update_str = "新建";
		}
		echo "<input type=\"hidden\" name=\"Update_Set\" value=\"".$update_set."\">\r\n";
		echo "<table>\r\n";
		echo "\t<tr><th>字段</th><th>数据库数据</th><th>参考数据</th></tr>\r\n";
		echo "\t<tr><td>Image</td><td><img src=\"http://www.1000steine.com/brickset/thumbs/tn_".$legoid."-1_jpg.jpg\"></td><td></td></tr>\r\n";
		echo "\t<tr><td>ETitle</td><td><input type=\"text\" id=\"DB_ETitle\" name=\"DB_ETitle\" size=\"30\" value=\"".$row['ETitle']."\"/></td><td><a onclick=\"fill_data('bk','ETitle');\"><span class=\"bk\" id=\"bk_ETitle\" title=\"bk_ETitle\"></span></a><a onclick=\"fill_data('bk','ETitle');\"></a>&nbsp;<a onclick=\"fill_data('bl','ETitle');\"><span class=\"bl\" id=\"bl_ETitle\" title=\"bl_ETitle\"></span></a>&nbsp;<a onclick=\"fill_data('amzn','ETitle');\"><span class=\"amzn\" id=\"amzn_ETitle\" title=\"amzn_ETitle\"></span></a></td></tr>\r\n";
		echo "\t<tr><td>CTitle</td><td><input type=\"text\" id=\"DB_CTitle\" name=\"DB_CTitle\" size=\"30\" value=\"".$row['CTitle']."\"/></td><td><a onclick=\"fill_data('amzncn','CTitle');\"><span class=\"amzncn\" id=\"amzncn_CTitle\" title=\"amzncn_CTitle\"></span></a></td></tr>\r\n";
		echo "\t<tr><td>Theme</td><td><select id=\"DB_Theme\" name=\"DB_Theme\">".$theme_str."</select><script language=\"JavaScript\">select_theme('id','".$row['ThemeID']."');</script></td><td><a onclick=\"fill_data('bk','Theme');\"><span class=\"bk\" id=\"bk_Theme\" title=\"bk_Theme\"></span></a></td>\r\n";
		echo "\t<tr><td>USD$</td><td><input type=\"text\" id=\"DB_USPrice\" name=\"DB_USPrice\" size=\"5\" value=\"".$row['USPrice']."\"/></td><td><a onclick=\"fill_data('bk','USPrice');\"><span class=\"bk\" id=\"bk_USPrice\" title=\"bk_USPrice\"></span></a>&nbsp;<a onclick=\"fill_data('amzn','USPrice');\"><span class=\"amzn\" id=\"amzn_USPrice\" title=\"amzn_USPrice\"></span></a></td></tr>\r\n";
		echo "\t<tr><td>CNY¥</td><td><input type=\"text\" id=\"DB_CNPrice\" name=\"DB_CNPrice\" size=\"5\" value=\"".$row['CNPrice']."\"/></td><td><a onclick=\"fill_data('amzncn','CNPrice');\"><span class=\"amzncn\" id=\"amzncn_CNPrice\" title=\"amzncn_CNPrice\"></span></a></td></tr>\r\n";
		echo "\t<tr><td>Age</td><td><input type=\"text\" id=\"DB_Age\" name=\"DB_Age\" size=\"6\" value=\"".$row['Age']."\"/></td><td><a onclick=\"fill_data('bk','Age');\"><span class=\"bk\" id=\"bk_Age\" title=\"bk_Age\"></span></a></td></tr>\r\n";
		echo "\t<tr><td>Year</td><td><input type=\"text\" id=\"DB_Year\" name=\"DB_Year\" size=\"4\" value=\"".$row['Year']."\"/></td><td><a onclick=\"fill_data('bk','Year');\"><span class=\"bk\" id=\"bk_Year\" title=\"bk_Year\"></span></a></td></tr>\r\n";
		echo "\t<tr><td>Pieces</td><td><input type=\"text\" id=\"DB_Pieces\" name=\"DB_Pieces\" size=\"4\" value=\"".$row['Pieces']."\"/></td><td><a onclick=\"fill_data('bk','Pieces');\"><span class=\"bk\" id=\"bk_Pieces\" title=\"bk_Pieces\"></span></a></td></tr>\r\n";
		echo "\t<tr><td>Minifigs</td><td><input type=\"text\" id=\"DB_Minifigs\" name=\"DB_Minifigs\" size=\"4\" value=\"".$row['Minifigs']."\"/></td><td><a onclick=\"fill_data('bk','Minifigs');\"><span class=\"bk\" id=\"bk_Minifigs\" title=\"bk_Minifigs\"></span></a></td></tr>\r\n";
		echo "\t<tr><td>Weight</td><td><input type=\"text\" id=\"DB_Weight\" name=\"DB_Weight\" size=\"4\" value=\"".$row['Weight']."\"/></td><td><a onclick=\"fill_data('bl','Weight');\"><span class=\"bl\" id=\"bl_Weight\" title=\"bl_Weight\"></span></a></td></tr>\r\n";
		echo "\t<tr><td>Demesion</td><td><input type=\"text\" id=\"DB_Length\" name=\"DB_Length\" size=\"3\" value=\"".$row['Length']."\"/>*<input type=\"text\" id=\"DB_Width\" name=\"DB_Width\" size=\"3\" value=\"".$row['Width']."\"/>*<input type=\"text\" id=\"DB_Height\" name=\"DB_Height\" size=\"3\" value=\"".$row['Height']."\"/>cm<sup>3</sup></td><td><a onclick=\"fill_data('bk','Demesion');\"><span class=\"bk\" id=\"bk_Demesion\" title=\"bk_Demesion\"></span></a><a onclick=\"fill_data('bl','Demesion');\"><span class=\"bl\" id=\"bl_Demesion\" title=\"bl_Demesion\"></span></a></td></tr>\r\n";
		echo "\t<tr><td>UPC</td><td><input type=\"text\" id=\"DB_UPC\" name=\"DB_UPC\" size=\"15\" value=\"".$row['UPC']."\"/></td><td><a onclick=\"fill_data('bk','UPC');\"><span class=\"bk\" id=\"bk_UPC\" title=\"bk_UPC\"></span></a></td></tr>\r\n";
		echo "\t<tr><td>EAN</td><td><input type=\"text\" id=\"DB_EAN\" name=\"DB_EAN\" size=\"16\" value=\"".$row['EAN']."\"/></td><td><a onclick=\"fill_data('bk','EAN');\"><span class=\"bk\" id=\"bk_EAN\" title=\"bk_EAN\"></span></a></td></tr>\r\n";
		echo "\t<tr><td>ItemSN</td><td><input type=\"text\" id=\"DB_ItemSN\" name=\"DB_ItemSN\" size=\"8\" value=\"".$row['ItemSN']."\"/></td><td><a onclick=\"fill_data('bk','ItemSN');\"><span class=\"bk\" id=\"bk_ItemSN\" title=\"bk_ItemSN\"></span></a></td></tr>\r\n";
		$query="SELECT * FROM PW_AmazonInfo WHERE LegoID = '".$legoid."';";
		$result = $mysqli->query($query);
		while ($row = $result->fetch_array(MYSQLI_ASSOC))
		{
			if ($row["Country"] == "CN")
			{
				$CNASIN = $row["ASIN"];
			}
			elseif ($row["Country"] == "US")
			{
				$USASIN = $row["ASIN"];
			}
		}

		echo "\t<tr><td>US ASIN</td><td><input type=\"text\" id=\"DB_USASIN\" name=\"DB_USASIN\" size=\"18\" value=\"".$USASIN."\"/></td><td><a onclick=\"fill_data('amzn','USASIN');\"><span class=\"amzn\" id=\"amzn_USASIN\" title=\"amzn_USASIN\"></span></a></td></tr>\r\n";
		echo "\t<tr><td>CN ASIN</td><td><input type=\"text\" id=\"DB_CNASIN\" name=\"DB_CNASIN\" size=\"18\" value=\"".$CNASIN."\"/></td><td><a onclick=\"fill_data('amzncn','CNASIN');\"><span class=\"amzncn\" id=\"amzncn_CNASIN\" title=\"amzncn_CNASIN\"></span></a></td></tr>\r\n";
		echo "</table>\r\n";
		if ($USASIN == "")
		{
			echo "<input type=\"hidden\" name=\"Update_USASIN\" value=\"0\">\r\n";
		}
		else
		{
			echo "<input type=\"hidden\" name=\"Update_USASIN\" value=\"1\">\r\n";
		}
		if ($CNASIN == "")
		{
			echo "<input type=\"hidden\" name=\"Update_CNASIN\" value=\"0\">\r\n";
		}
		else
		{
			echo "<input type=\"hidden\" name=\"Update_CNASIN\" value=\"1\">\r\n";
		}
		echo "<input type=\"button\" value=\"".$update_str."\" onclick=\"submit_result();\">\r\n";
	}
}
?>

</form>

</body>
</html>
