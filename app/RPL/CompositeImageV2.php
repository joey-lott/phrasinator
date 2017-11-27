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
  private $compositeImage;
  private $uniqueId;

  public function __construct($width = 3000, $height = 3000, $uniqueId = "") {
    $this->imageWidth = $width;
    $this->imageHeight = $height;

    // Adjust the vertical spacing relative to the height
    $this->verticalSpacing *= ($height / 3000);

    $this->uniqueId = $uniqueId;
  }

  public function fetchFromUrl($url) {
    $handle = fopen($url, 'rb');
    $image = new \Imagick();
    $image->readImageFile($handle);
    fclose($handle);
    $height = $image->getImageHeight();
    $width = $image->getImageWidth();
    $name = $this->uniqueId."_tmp_pixabay.png";
    $this->saveImageToDisk($image, $name);
    $image->destroy();
    $this->images[] = ["name" => $name, "height" => $height, "width" => $width];
  }

  public function saveImageToDisk($image, $name) {
    $tmpPath = base_path();
    $image->writeImage($tmpPath.$name);
  }

  public function fetchHeightRemaining() {
    $heightRemaining = $this->imageHeight;
    foreach($this->images as $image) {
        // Subtract the image resource height;
        $heightRemaining -= $image["height"] + $this->verticalSpacing;// + ($this->imageHeight * $this->verticalSpaceMultiplier));
    }
    return $heightRemaining;
  }

  public function addAbove($imageData) {
    array_unshift($this->images, $imageData);
  }

  public function addBelow($imageData) {
    array_push($this->images, $imageData);
  }

  public function saveToDisk($fileName, $path = null) {

    // If the path doesn't exist, create it first.
    Storage::makeDirectory($path);

    $compositeImage = new \Imagick();
    $compositeImage->newImage($this->imageWidth, $this->imageHeight, "none");

    $totalHeight = 0;

    foreach($this->images as $image) {
      $h = $image["height"];
      $totalHeight += $h + $this->verticalSpacing;// + ($this->imageHeight * $this->verticalSpaceMultiplier);
    }
    $totalHeight -= $this->verticalSpacing;

    $y = ($this->imageHeight - $totalHeight) / 2;

    foreach($this->images as $image) {
      $w = $image["width"];
      $h = $image["height"];
      $x = ($this->imageWidth - $w) / 2;

      // Get the image from disk
      $path = base_path().$image["name"];
      $handle = fopen($path, 'rb');
      $image = new \Imagick();
      $image->readImageFile($handle);
      fclose($handle);

      // Add the image to the composite.
      $compositeImage->compositeImage($image, \imagick::COMPOSITE_DEFAULT, $x, $y);
      // destroy it from memory
      $image->destroy();
      $y += $h + $this->verticalSpacing;// + ($this->imageHeight * $this->verticalSpaceMultiplier);
    }

    $name = $fileName.".png";

    $tmpPath = base_path();

// need to make this file name unique...add the user ID
    // Save a temp file locally.
    $compositeImage->writeImage($tmpPath."/temp.png");

    // Upload the temp file to s3
    $storedFile = Storage::putFileAs($path, new File($tmpPath."/temp.png"), $name, "public");

    $url = Storage::url($storedFile);

    return $url;
  }


}
