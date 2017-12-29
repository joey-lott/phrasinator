<?php

namespace App\RPL;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use App\RPL\Color;

class CardFront {

  private $imageWidth = 3000;
  private $imageHeight = 3000;
  private $verticalSpacing = 50;
  private $images = [];
  private $compositeImage;
  private $uniqueId;
  private $basePath;
  private $border;
  private $borderColor;
  private $backgroundColor;
  private $designWidth;
  private $designHeight;

  public function __construct($width = 1500, $height = 2100, $uniqueId = "", $basePath = "", $border = true, $borderColor = "FF0000", $borderWidth = 20, $backgroundColor = "FFFFFF") {
    $this->imageWidth = $width;
    $this->imageHeight = $height;

    $this->basePath = $basePath;

    $this->border = $border;
    $this->borderColor = "#".$borderColor;
    $this->backgroundColor = "#".$backgroundColor;
    $this->borderWidth = $borderWidth;

    // Adjust the vertical spacing relative to the height
    $this->verticalSpacing *= ($height / 2100);

    $this->uniqueId = $uniqueId;

    $this->designWidth = ($width) - ($borderWidth * 2);
    $this->designHeight = $height - ($borderWidth * 2);
  }

  public function fetchFromUrl($url) {
    $handle = fopen($url, 'rb');
    $image = new \Imagick();
    $image->readImageFile($handle);
    fclose($handle);
    $height = $image->getImageHeight();
    $width = $image->getImageWidth();
    $name = $this->saveImageToDisk($image);
    $image->destroy();
    $this->images[] = ["name" => $name, "height" => $height, "width" => $width];
  }

  public function saveImageToDisk($image) {
    $tmpPath = storage_path("temp.png");
    $image->writeImage($tmpPath);
    return $tmpPath;
  }

  public function fetchHeightRemaining() {
    $heightRemaining = $this->designHeight;
    foreach($this->images as $image) {
        // Subtract the image resource height;
        $heightRemaining -= $image["height"] + $this->verticalSpacing;// + ($this->imageHeight * $this->verticalSpaceMultiplier));
    }
    return $heightRemaining;
  }

  public function fetchDesignWidth() {
    return $this->designWidth;
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
    $compositeImage->newImage($this->imageWidth, $this->imageHeight, new \ImagickPixel($this->backgroundColor));

    if($this->border) {
      $draw = new \ImagickDraw();
      $draw->setStrokeColor($this->borderColor);
      $draw->setStrokeWidth($this->borderWidth);
      $widthOffset = $this->borderWidth / 2;
      // left vertical
      $draw->line($widthOffset, 0, $widthOffset, $this->imageHeight);
      // right vertical
      $draw->line($this->imageWidth - $widthOffset, 0, $this->imageWidth - $widthOffset, $this->imageHeight);
      // top horizontal
      $draw->line($widthOffset, $widthOffset, $this->imageWidth - $widthOffset, $widthOffset);
      // bottom horizontal
      $draw->line($widthOffset, $this->imageHeight - $widthOffset, $this->imageWidth - $widthOffset, $this->imageHeight - $widthOffset);

      $compositeImage->drawImage($draw);
    }

    $totalHeight = 0;

    foreach($this->images as $image) {
      $h = $image["height"];
      $totalHeight += $h + $this->verticalSpacing;// + ($this->imageHeight * $this->verticalSpaceMultiplier);
    }
    $totalHeight -= $this->verticalSpacing;

    // Vertically center the image and add half the vertical spacing
    $y = ($this->imageHeight - $totalHeight) / 2 + $this->verticalSpacing/2;

    foreach($this->images as $image) {

      $w = $image["width"];
      $h = $image["height"];

      // Center on the right half of the composite image
      $x = ($this->imageWidth - $w) / 2;
      // Get the image from disk
      $path = $image["name"];
      $handle = fopen($path, 'rb');
      $image = new \Imagick();
      $image->readImageFile($handle);
      fclose($handle);

      // Add the image to the composite.
      $compositeImage->compositeImage($image, \imagick::COMPOSITE_DEFAULT, $x, $y);
      // destroy it from memory
      $image->clear();
      // Add the height plus half the vertical spacing. The vertical spacing is split between
      // the top of the image and the bottom to prevent the image being butted right up against the top
      $y += $h + $this->verticalSpacing/2;// + ($this->imageHeight * $this->verticalSpaceMultiplier);
    }

    $name = $fileName.".png";

    $tmpPath = $this->basePath;
    $compositeImage->setImageFormat("png");

    // Save a temp file to use in composites.
    $path = $this->saveImageToDisk($compositeImage);

    // Put directly on S3
    $storedFile = Storage::put($this->uniqueId."/".$name, $compositeImage->getImageBlob(), "public");

    $compositeImage->clear();


    $url = env("AWS_BASE_URL").$this->uniqueId."/".$name;

    return ["tempPath" => $path, "url" => $url];
  }


}
