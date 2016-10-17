<?php
if ($_GET["id"])
{
  $strid = $_GET["id"];
  $filepath = "./setimg/png2880/".$strid."_cover.png";
  if (file_exists($filepath) and (!$_GET["force"]))
  {
    $image_name = $filepath;
  }
  else
  {
    $image_name = "http://cache.lego.com/r/dynamic/is/image/LEGO/".$strid."_alt1?op_sharpen=1&resMode=sharp2&hei=2880&wid=2880&fit=constrain,1&fmt=png-alpha";
  }
}
elseif ($_GET["filename"])
{
  $filename = $_GET["filename"];
  $filepath = "./setimg/original/".$filename.".png";
  if (file_exists($filepath) and (!$_GET["force"]))
  {
    $image_name = $filepath;
  }
  else
  {
    $image_name = "http://cache.lego.com/r/dynamic/is/image/LEGO/".$filename."?op_sharpen=1&resMode=sharp2&hei=2880&wid=2880&fit=constrain,1&fmt=png-alpha";  
  }
}
elseif ($_GET["thumb150"])
{
  $strid = $_GET["thumb150"];
  $filepath = "./setimg/thumb150/".$strid."_150.jpg";
  if (file_exists($filepath) and (!$_GET["force"]))
  {
	$image_name = file_get_contents($filepath);
	header('Content-type: image/jpeg');
	echo $image_name;
  	exit;
  }
  else
  {
  	$image_name = file_get_contents("./setimg/thumb150/null.jpg");
  	header('Content-type: image/jpeg');
	echo $image_name;
  	exit;
  }
}
else
{
  print "id={id}&margin=5&size=470&nomark=0&output={jpg|png}|filename={filename}";
  exit;
}
list($width,$height) = getimagesize($image_name);  
$im = ImageCreateFrompng($image_name);
ImageSaveAlpha($im, true);
if (($image_name != $filepath) or ($_GET["force"]))
{
  //ImagePng($im, $filepath);
  //ImageSaveAlpha($im, true);
  //file_put_contents(str_replace("original", "cache", $filepath), "");
}
if ($_GET["nocrop"])
{
$dest = ImageCreateTruecolor($width, $height);
ImageSaveAlpha($dest, true);
ImageAlphaBlending($dest, false);//这里很重要,意思是不合并颜色,直接用$img图像颜色替换,包括透明色;
//$color = imagecolorallocatealpha($dest, 0, 0, 0, 127);
//ImageFill($dest, 0, 0, $color);
//ImageColorTransparent($dest, $color);
ImageCopyMerge($dest, $im, 0, 0, 0, 0, $width, $height, 100);
ImageDestroy($im);
$nwidth = $width;
$nheight = $height;
}
else
{
$mwidth = $width /2;
$mheight = $height /2;
$top = $mheight;
$bottom = $mheight;
$right = $mwidth;
$left = $mwidth;
//for ($x = $mwidth; $x < $width; $x++)
for ($x = 0; $x < $width; $x++)
{
  for ($y = 0; $y < $top; $y++)
  {
    $rgb = imagecolorat($im, $x, $y);
    $colors = imagecolorsforindex($im, $rgb);
    if (($colors['red'] < 224 || $colors['green'] < 224 || $colors['blue'] < 224) && ($colors['alpha'] < 64))
    {
    //有色点
      if ($y < $top)
      {
        $top = $y;
        break;
      }
    }
  }
}
//for ($x = 0; $x < $mwidth; $x++)
for ($x = 0; $x < $width; $x++)
{
  for ($y = $height-1; $y > $bottom; $y--)
  {
    $rgb = imagecolorat($im, $x, $y);
    $colors = imagecolorsforindex($im, $rgb);
    if (($colors['red'] < 224 || $colors['green'] < 224 || $colors['blue'] < 224) && ($colors['alpha'] < 64))
    {
      if ($y > $bottom)
      {
        $bottom = $y;
        break;
      }
    }
  }
}
for ($y = 0; $y < $height; $y++)
{
  for ($x = 0; $x < $left; $x++)
  {
    $rgb = imagecolorat($im, $x, $y);
    $colors = imagecolorsforindex($im, $rgb);
    if (($colors['red'] < 192 || $colors['green'] < 192 || $colors['blue'] < 192) && ($colors['alpha'] < 64))
    {
      if ($x < $left)
      {
        $left = $x;
        break;
      }
    }
  }
}
for ($y = 0; $y < $height; $y++)
{
  for ($x = $width-1; $x > $right; $x--)
  {
    $rgb = imagecolorat($im, $x, $y);
    $colors = imagecolorsforindex($im, $rgb);
    if (($colors['red'] < 224 || $colors['green'] < 224 || $colors['blue'] < 224) && ($colors['alpha'] < 64))
    {
      if ($x > $right)
      {
        $right = $x;
        break;
      }
    }
  }
}
$nheight = $bottom - $top + 2;
$nwidth = $right - $left + 2;
$dest = ImageCreateTruecolor($nwidth, $nheight);
ImageAlphaBlending($dest, false);//这里很重要,意思是不合并颜色,直接用$img图像颜色替换,包括透明色;
ImageSaveAlpha($dest, true);//这里很重要,意思是不要丢了$thumb图像的透明色;
//$white = ImageColorAllocate($dest, 255, 255, 255);
//ImageFill($dest, 0, 0, $white);
//ImageColorTransparent($dest, $white);
ImageCopy($dest, $im, 0, 0, $left - 1, $top - 1, $nwidth, $nheight);
ImageDestroy($im);
}
if ($_GET["margin"])
{
  $margin = $_GET["margin"];
}
elseif ($_GET["nocrop"])
{
  $margin = 0;
}
else
{
  $margin = 5;
}
if ($_GET["size"])
{
  $size = $_GET["size"]-2*$margin;
}
else
{
  $size = 790;
}
if ($_GET["tbm"])
{
  $size = 610;
}
if ($nheight > $nwidth)
{
  $theight = $size;
  $twidth = $size * $nwidth / $nheight;
  $left = ($size - $twidth)/2;
  $top = $margin;
}
else
{
  $twidth = $size;
  $theight = $size * $nheight / $nwidth;
  $top = ($size - $theight)/2;
  $left = $margin;
}
if ($_GET["nocrop"])
{
  $fwidth = $twidth;
  $fheight = $theight;
  $thumb = ImageCreateTrueColor($twidth, $theight);
  if ($_GET["output"])
  {
    $color = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
    ImageFill($thumb, 0, 0, $color);
    ImageColorTransparent($thumb, $color);
  }
  else
  {
	$white = ImageColorAllocate($thumb, 255, 255, 255);
	ImageFill($thumb, 0, 0, $white);
	ImageColorTransparent($thumb, $white);
  }
  
  ImageAlphaBlending($thumb, false);//这里很重要,意思是不合并颜色,直接用$img图像颜色替换,包括透明色;
  ImageSaveAlpha($thumb, true);//这里很重要,意思是不要丢了$thumb图像的透明色;
  ImageCopyReSampled($thumb, $dest, 0, 0, 0, 0, $twidth, $theight, $nwidth, $nheight);
}
else
{
  $fwidth = $twidth+2*$margin;
  $fheight = $theight+2*$margin;
  if(isset($_GET["square"]))
  {
  	//正方形补白边
  	$thumb = ImageCreateTrueColor($size+2*$margin, $size+2*$margin);
  }
  else
  {
    //长宽去白边
  	$thumb = ImageCreateTrueColor($fwidth, $fheight);
  }
  if ($_GET["output"])
  {
    ImageAlphaBlending($thumb, false);//这里很重要,意思是不合并颜色,直接用$img图像颜色替换,包括透明色;
    ImageSaveAlpha($thumb, true);//这里很重要,意思是不要丢了$thumb图像的透明色;
    $color = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
    ImageFill($thumb, 0, 0, $color);
    ImageColorTransparent($thumb, $color);
  }
  else
  {
	$white = ImageColorAllocate($thumb, 255, 255, 255);
	ImageFill($thumb, 0, 0, $white);
	ImageColorTransparent($thumb, $white);
  }
  if(isset($_GET["square"]))
  {
  	//正方形补白边
  	ImageCopyReSampled($thumb, $dest, $left, $top, 0, 0, $twidth, $theight, $nwidth, $nheight);
  }
  else
  {
  	//长宽去白边
  	ImageCopyReSampled($thumb, $dest, $margin, $margin, 0, 0, $twidth, $theight, $nwidth, $nheight);
  }
} 
ImageDestroy($dest);
if (!($_GET["nomark"]))
{
  if ($fwidth > 620 || $fheight > 620)
  {
    $logo = ImageCreateFrompng("./images/logo220.png");
    $logosize = 220;
  }
  else
  {
    $logo = ImageCreateFrompng("./images/logo130.png");
    $logosize = 130;
  }
  ImageSaveAlpha($logo, true);
  
  if(isset($_GET["square"]))
  {
    //正方形补白边
  	ImageCopy($thumb, $logo, $size-$logosize+$margin, $size-$logosize+$margin, 0, 0, $logosize, $logosize);
  }
  else
  {
  	//长宽去白边
  	ImageCopy($thumb, $logo, $fwidth-$logosize-$margin, $fheight-$logosize-$margin, 0, 0, $logosize, $logosize);
  }
  ImageDestroy($logo);
}
if ($_GET["output"])
{
  Header("Content-type: image/png");
  Imagepng($thumb);
}
else
{
  if ($_GET["quality"])
  {
    $quality = $_GET["quality"];
  }
  else
  {
    $quality = 100;
  }
  Header("Content-type: image/jpeg");
  Imagejpeg($thumb, NULL, $quality);
}
ImageDestroy($thumb);
?>
