<?php

namespace App\RPL;

class CompositeImage {

  private $imageWidth = 3000;
  private $imageHeight = 3000;
  private $verticalSpacing = 50;
  private $transparencyColor;
  private $images = [];

  public function __construct($width = 3000, $height = 3000) {
    $this->imageWidth = $width;
    $this->imageHeight = $height;

    // Adjust the vertical spacing relative to the height
    $this->verticalSpacing *= ($height / 3000);
  }

  public function fetchFromUrl($url) {
    if(explode(".", $url)[2] == "png") {
      $image = imagecreatefrompng($url);
    }
    else {
      $image = imagecreatefromjpeg($url);
    }
    $this->images[] = $image;
  }

  public function fetchHeightRemaining() {
    $heightRemaining = $this->imageHeight;
    foreach($this->images as $image) {
        // Subtract the image resource height;
        $heightRemaining -= imagesy($image) + $this->verticalSpacing;// + ($this->imageHeight * $this->verticalSpaceMultiplier));
    }
    return $heightRemaining;
  }

  public function addAbove($imageResource) {
    array_unshift($this->images, $imageResource);
  }

  public function addBelow($imageResource) {
    array_push($this->images, $imageResource);
  }

  public function setTransparent($transparent) {
    $this->transparencyColor = $transparent;
  }

  public function saveToDisk($name) {
    $fileName = preg_replace('/[^a-zA-Z0-9\s]/', '', $name);

    $compositeImage = imagecreatetruecolor($this->imageWidth, $this->imageHeight);


    $transparent = imagecolorallocate($compositeImage, $this->transparencyColor->red, $this->transparencyColor->green, $this->transparencyColor->blue);
    imagefilledrectangle($compositeImage, 0, 0, $this->imageWidth, $this->imageHeight, $transparent);

    imagecolortransparent($compositeImage, $transparent);

    $totalHeight = 0;

    foreach($this->images as $image) {
      $h = imagesy($image);
      $totalHeight += $h + $this->verticalSpacing;// + ($this->imageHeight * $this->verticalSpaceMultiplier);
    }
    $totalHeight -= $this->verticalSpacing;

    $y = ($this->imageHeight - $totalHeight) / 2;

    foreach($this->images as $image) {
      $w = imagesx($image);
      $h = imagesy($image);
      $x = ($this->imageWidth - $w) / 2;
      imagecopy($compositeImage, $image, $x, $y, 0, 0, $w, $h);
      $y += $h + $this->verticalSpacing;// + ($this->imageHeight * $this->verticalSpaceMultiplier);
    }

    $name = $fileName.".png";
    imagepng($compositeImage, base_path()."/public/images/".$name);

    $this->destroyResources();
    //dump("destroying composite image resource");
    //dump($compositeImage);
    imagedestroy($compositeImage);
    //dump("destroyed");
    //dump($compositeImage);
    return $name;
  }

  public function destroyResources() {
    foreach($this->images as $image) {
      //dump("destroying inner image resource");
      //dump($image);
      imagedestroy($image);
      //dump("destroyed");
      //dump($image);
    }
  }

}
