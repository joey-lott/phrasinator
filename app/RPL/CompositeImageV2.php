<?php

namespace App\RPL;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;

class CompositeImageV2 {

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
    $handle = fopen($url, 'rb');
    $image = new \Imagick();
    $image->readImageFile($handle);
    $this->images[] = $image;
  }

  public function fetchHeightRemaining() {
    $heightRemaining = $this->imageHeight;
    foreach($this->images as $image) {
        // Subtract the image resource height;
        $heightRemaining -= $image->getImageHeight() + $this->verticalSpacing;// + ($this->imageHeight * $this->verticalSpaceMultiplier));
    }
    return $heightRemaining;
  }

  public function addAbove($imageResource) {
    array_unshift($this->images, $imageResource);
  }

  public function addBelow($imageResource) {
    array_push($this->images, $imageResource);
  }

  public function saveToDisk($fileName, $path = null) {

    // If the path doesn't exist, create it first.
    Storage::makeDirectory($path);

    $compositeImage = new \Imagick();
    $compositeImage->newImage($this->imageWidth, $this->imageHeight, "none");

    $totalHeight = 0;

    foreach($this->images as $image) {
      $h = $image->getImageHeight();
dump($h);
      $totalHeight += $h + $this->verticalSpacing;// + ($this->imageHeight * $this->verticalSpaceMultiplier);
    }
    $totalHeight -= $this->verticalSpacing;

    $y = ($this->imageHeight - $totalHeight) / 2;

    foreach($this->images as $image) {
      $w = $image->getImageWidth();
      $h = $image->getImageHeight();
      $x = ($this->imageWidth - $w) / 2;
      //$compositeImage->setGravity(\imagick::GRAVITY_CENTER);
      $compositeImage->compositeImage($image, \imagick::COMPOSITE_DEFAULT, $x, $y);
      $y += $h + $this->verticalSpacing;// + ($this->imageHeight * $this->verticalSpaceMultiplier);
    }

    $name = $fileName.".png";

    $tmpPath = base_path();

    // Save a temp file locally.
    $compositeImage->writeImage($tmpPath."/temp.png");

    // Upload the temp file to s3
    $storedFile = Storage::putFileAs($path, new File($tmpPath."/temp.png"), $name, "public");

    $url = Storage::url($storedFile);

    return $url;
  }


}
