<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <script type="text/javascript" src="http://ossweb-img.qq.com/images/js/jquery/jquery-1.7.1.min.js"></script>
  <script type="text/javascript">
  function loadthumb(legoid)
  {
    $.get("pic.php", { id: legoid}, function(data) { $("#thumb").html("<img src='pic.php?quality=98&id="+legoid+"'>");} );
  }
  function loadmainimg(legoid)
  {
    $.get("pic.php", { id: legoid}, function(data) { $("#mainimg").html("<img src='pic.php?filename="+legoid+"&nocrop=1&size=600&nomark=1'>");} );
  }
  </script>
  <title>模板生成</title>
</head>
<body>

<form action='' method='get'>
<p>请输入LegoID：</p>
<input type="text" name="legoid" /> 
<input type="submit" value="生成"> 
</form>

<?php
	require("conn.php");
	require_once("simple_html_dom.php");
	$legoid=$_GET["legoid"];
	date_default_timezone_set('Asia/Shanghai');
	$conn=mysql_connect($mysql_server_name, $mysql_username, $mysql_password) or die ("数据库错误：".mysql_error());
	mysql_query("SET NAMES UTF8;", $conn);
	mysql_query("SET time_zone = '+08:00';", $conn);
	$strsql="SELECT * FROM `DB_Set` INNER JOIN DB_Theme ON DB_Set.ThemeID=DB_Theme.ThemeID WHERE LegoID = '".$legoid."'";
	$result=mysql_db_query($mysql_database, $strsql, $conn);
	$query=mysql_fetch_array($result);
  If ($query['CTitle'] == '')
  {
    $txtTitle = $query['ETitle'];
  }
  else
  {
    $txtTitle = $query['CTitle'];
  }
  If ($query['CTheme'] == '')
  {
    $txtTheme = $query['ETheme'];
  }
  else
  {
    $txtTheme = $query['CTheme'];
  }
  $txtTitle = "【乐乐萌】乐高 LEGO $legoid $txtTheme"."系列 $txtTitle 专柜正品\r\n";
?>
	<h3>模版生成：</h3>
<div id="main">
<input type="text" size="65" value="<?php echo $txtTitle; ?>"> <br />
<input type="text" size="20" value="<?php echo $query['Weight']; ?>"><br />
<textarea rows="20" style="width:800px;">
<p><img align="absMiddle" src="http://img02.taobaocdn.com/imgextra/i2/12352442/T2Q1aiXfxXXXXXXXXX_!!12352442.gif" /></p>
<p><span style="font-family:microsoft yahei;color:#ff9900;font-size:18.0px;font-weight:bold;">北京现货，可自提~~</span></p>
<p>----------------------------------------------------------------------------------------------------------------------</p>
<p>
	<span style="font-family:microsoft yahei;font-size:18.0px;font-weight:bold;">商品名称：<?php echo $query['ETitle']; ?> / <?php echo $query['CTitle']; ?><br />
	商品系列：<?php echo $query['CTheme']; ?>系列<br />
	商品型号：<?php echo $query['LegoID']; ?><br />
	商品品牌：丹麦品牌LEGO乐高<br />
	适合年龄：<?php echo $query['Age']; ?>岁<br />
	主要材质：环保塑胶类<br />
	商品保养：可清洗消毒<br />
	颗 粒 数：<?php echo $query['Pieces']; ?><br />
	人 仔 数：<?php echo $query['Minifigs']; ?><br />
	上市年份：<?php echo $query['Year']; ?>年<br />
	专柜价格：<?php echo $query['CNPrice']; ?>元</span></p>

<?php
class AccessTokenAuthentication {
    /*
     * Get the access token.
     *
     * @param string $grantType    Grant type.
     * @param string $scopeUrl     Application Scope URL.
     * @param string $clientID     Application client ID.
     * @param string $clientSecret Application client ID.
     * @param string $authUrl      Oauth Url.
     *
     * @return string.
     */
    function getTokens($grantType, $scopeUrl, $clientID, $clientSecret, $authUrl){
        try {
            //Initialize the Curl Session.
            $ch = curl_init();
            //Create the request Array.
            $paramArr = array (
                 'grant_type'    => $grantType,
                 'scope'         => $scopeUrl,
                 'client_id'     => $clientID,
                 'client_secret' => $clientSecret
            );
            //Create an Http Query.//
            $paramArr = http_build_query($paramArr);
            //Set the Curl URL.
            curl_setopt($ch, CURLOPT_URL, $authUrl);
            //Set HTTP POST Request.
            curl_setopt($ch, CURLOPT_POST, TRUE);
            //Set data to POST in HTTP "POST" Operation.
            curl_setopt($ch, CURLOPT_POSTFIELDS, $paramArr);
            //CURLOPT_RETURNTRANSFER- TRUE to return the transfer as a string of the return value of curl_exec().
            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, TRUE);
            //CURLOPT_SSL_VERIFYPEER- Set FALSE to stop cURL from verifying the peer's certificate.
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            //Execute the  cURL session.
            $strResponse = curl_exec($ch);
            //Get the Error Code returned by Curl.
            $curlErrno = curl_errno($ch);
            if($curlErrno){
                $curlError = curl_error($ch);
                throw new Exception($curlError);
            }
            //Close the Curl Session.
            curl_close($ch);
            //Decode the returned JSON string.
            $objResponse = json_decode($strResponse);
            if ($objResponse->error){
                throw new Exception($objResponse->error_description);
            }
            return $objResponse->access_token;
        } catch (Exception $e) {
            echo "Exception-".$e->getMessage();
        }
    }
}
?>
</textarea><br />
<a href="view-source:http://www.brickset.com/ajax/setImages/description.aspx?setID=<?php echo $setID; ?>" target="_blank">--></a>
</div>
<div id="images">
<?php
  echo "<p>封面图:</p><div width=\"470\" height=\"470\" id=\"thumb\" style=\"text-align:center;\"><a download=\"".$legoid."_main.jpg\" href=\"pic.php?quality=98&id=$legoid\"><img src=\"pic.php?quality=98&id=$legoid\"></a><br /><br /></div>";
  //echo "<p>展示图:</p><div width=\"600\" height=\"600\" id=\"mainimg\" style=\"text-align:center;\">正在读取展示图......<script>loadmainimg('".$legoid."');</script><br /><br /></div>";
  echo "<div width=\"600\" style=\"block\">以下是原始细节图:<br />";
  
  	$url = 'http://cache.lego.com/e/dynamic/is/image/LEGO/'.$legoid.'_is?req=imageset';
	$ch = curl_init(); 
	$timeout = 5; 
	curl_setopt($ch, CURLOPT_URL, $url); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); 
	$curlResponse = curl_exec($ch); 	
    $curlErrno = curl_errno($ch);
    if ($curlErrno) {
        $curlError = curl_error($ch);
        throw new Exception($curlError);
    }
    curl_close($ch);
    
	$result = explode(";", $curlResponse);
	$ret = array();
	foreach ($result as $line)
	{
		if (strpos($line, ",") > 0)
		{
			$start = strpos($line, ",") + 1;
		}
		else
		{
			$start = 0;
		}
		$line = substr($line, $start);
		$line = trim(substr($line, 5));
		if (!in_array($line, $ret))
		{
			array_push($ret, $line);
		}
	}
	foreach ($ret as $line)
	{
		$url1 = "http://cache.lego.com/r/dynamic/is/image/LEGO/".$line."?op_sharpen=1&resMode=sharp2&wid=750&fit=constrain,1&fmt=jpeg;";
	    $url2 = "http://cache.lego.com/r/dynamic/is/image/LEGO/".$line."?op_sharpen=1&resMode=sharp2&hei=960&wid=620&fit=constrain,1&fmt=jpeg;";
   		echo "<a download=\"".$line."_750.jpg\" href=\"$url1\"><img width=\"50%\" src=\"$url1\"></a><a download=\"".$line."_620.jpg\" href=\"$url2\"></img><img width=\"45%\" src=\"$url2\"></img></a><br />";
	}
  	echo "</div>";
?>                
</div>
</body>
</html>
