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
  private $basePath;

  public function __construct($width = 3000, $height = 3000, $uniqueId = "", $basePath = "") {
    $this->imageWidth = $width;
    $this->imageHeight = $height;

    $this->basePath = $basePath;

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
    $tmpPath = $this->basePath;
    dblog("{$tmpPath}{$name}", "write pixabay image to disk");
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
      $path = $this->basePath.$image["name"];
      $handle = fopen($path, 'rb');
      $image = new \Imagick();
      $image->readImageFile($handle);
      fclose($handle);
      // Delete the temp file
      unlink($path);

      dblog(($image->getResource(\imagick::RESOURCETYPE_MEMORY))/1000000, "retrieved temp image memory");

      // Add the image to the composite.
      $compositeImage->compositeImage($image, \imagick::COMPOSITE_DEFAULT, $x, $y);
      dblog(($compositeImage->getResource(\imagick::RESOURCETYPE_MEMORY))/1000000, "composite image memory");
      // destroy it from memory
      $image->clear();
      dblog(($image->getResource(\imagick::RESOURCETYPE_MEMORY))/1000000, "retrieved temp image memory after being destroyed");
      $y += $h + $this->verticalSpacing;// + ($this->imageHeight * $this->verticalSpaceMultiplier);
    }

    $name = $fileName.".png";

    $tmpPath = $this->basePath;

    // Save a temp file locally.
    $compositeImage->writeImage($tmpPath.$this->uniqueId."_temp.png");
    $compositeImage->clear();

    // Upload the temp file to s3
    $storedFile = Storage::putFileAs($path, new File($tmpPath.$this->uniqueId."_temp.png"), $name, "public");

    $url = Storage::url($storedFile);

    // Delete the temp file
    unlink($path);

    dblog(($compositeImage->getResource(\imagick::RESOURCETYPE_MEMORY))/1000000, "composite image memory after destroy");

    return $url;
  }


}
