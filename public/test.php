<?php
$font = "GILLUBCD.ttf";
$fontSize = 150;
$image = imagecreatetruecolor(500, 500);
$black = imagecolorallocate($image, 0, 0, 0);
$white = imagecolorallocate($image, 255, 255, 255);
$red = imagecolorallocate($image, 255, 0, 0);
$green = imagecolorallocate($image, 0, 255, 0);
imagefilledrectangle($image, 0, 0, 500, 500, $white);


$phrase = "PSYCHOTIC TEACHER";
$chars = preg_split("//u", $phrase, null, PREG_SPLIT_NO_EMPTY);

$x = 0;
$y = 200;
$kerning = 0;
foreach($chars as $char) {
  $charResponse = imagettftext($image, $fontSize, 0, $x, $y, $black, "../fonts/".$font, $char);
  $characterBox = imagettfbbox($fontSize, 0, "../fonts/".$font, $char);
  imagedashedline($image, $characterBox[0] + $x, $characterBox[1] + $y, $characterBox[2] + $x, $characterBox[3] + $y, $red);
  imagedashedline($image, $characterBox[2] + $x, $characterBox[3] + $y, $characterBox[4] + $x, $characterBox[5] + $y, $red);
  imagedashedline($image, $characterBox[4] + $x, $characterBox[5] + $y, $characterBox[6] + $x, $characterBox[7] + $y, $red);
  imagedashedline($image, $characterBox[6] + $x, $characterBox[7] + $y, $characterBox[0] + $x, $characterBox[1] + $y, $red);

  imagedashedline($image, $charResponse[0], $charResponse[1], $charResponse[2], $charResponse[3], $green);
  imagedashedline($image, $charResponse[2], $charResponse[3], $charResponse[4], $charResponse[5], $green);
  imagedashedline($image, $charResponse[4], $charResponse[5], $charResponse[6], $charResponse[7], $green);
  imagedashedline($image, $charResponse[6], $charResponse[7], $charResponse[0], $charResponse[1], $green);
  // var_dump($characterBox);
  // var_dump($charResponse);
  //$characterWidth = $characterBox[2] - $characterBox[0];
  $x = $charResponse[2] + $kerning;
  //var_dump($charResponse[4]);
}


header('Content-Type: image/png');
imagepng($image);
imagedestroy($image);

?>
