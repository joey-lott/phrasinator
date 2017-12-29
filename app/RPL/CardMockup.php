<?php

namespace App\RPL;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use App\RPL\Color;

class CardMockup {

  private $imageWidth = 3000;
  private $imageHeight = 3000;
  private $verticalSpacing = 50;
  private $images = [];
  private $compositeImage;
  private $uniqueId;
  private $basePath;
  private $frontImagePath;

  public function __construct($width = 1000, $height = 1000, $uniqueId = "", $basePath = "", $frontImagePath) {
    $this->imageWidth = $width;
    $this->imageHeight = $height;

    $this->basePath = $basePath;

    $this->uniqueId = $uniqueId;
    $this->frontImagePath = $frontImagePath;
  }

  public function saveToDisk($fileName, $path = null) {

    // If the path doesn't exist, create it first.
    Storage::makeDirectory($path);

    $mockupPath = storage_path("app/etsy-card-background.png");
    $compositeImage = new \Imagick();
    $compositeImage->newImage($this->imageWidth, $this->imageHeight, "none");

    $handle = fopen($mockupPath, 'rb');
    $image = new \Imagick();
    $image->readImageFile($handle);
    fclose($handle);

    // Add the image to the composite.
    $compositeImage->compositeImage($image, \imagick::COMPOSITE_DEFAULT, 0, 0);

    $image->clear();

    $handle = fopen($this->frontImagePath, 'rb');
    $image = new \Imagick();
    $image->readImageFile($handle);
    fclose($handle);

    $image->resizeImage(472, 655, \Imagick::FILTER_CATROM, 1);
    // 470, 652

    // Add the image to the composite.
    $compositeImage->compositeImage($image, \imagick::COMPOSITE_DEFAULT, 308, 120);
    // destroy it from memory
    $image->clear();

    $name = $fileName.".png";

    $tmpPath = $this->basePath;
    $compositeImage->setImageFormat("png");

    // Put directly on S3
    $storedFile = Storage::put($this->uniqueId."/".$name, $compositeImage->getImageBlob(), "public");

    $compositeImage->clear();

    $url = env("AWS_BASE_URL").$this->uniqueId."/".$name;

    return $url;
  }


}
