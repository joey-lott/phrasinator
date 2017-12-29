<?php

namespace App\RPL;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use App\RPL\Color;

class CardImage {

  private $imageWidth = 3000;
  private $imageHeight = 3000;
  private $verticalSpacing = 50;
  private $images = [];
  private $compositeImage;
  private $uniqueId;
  private $basePath;
  private $frontImagePath;

  public function __construct($width = 3000, $height = 2100, $uniqueId = "", $basePath = "", $frontImagePath) {
    $this->imageWidth = $width;
    $this->imageHeight = $height;

    $this->basePath = $basePath;

    $this->uniqueId = $uniqueId;
    $this->frontImagePath = $frontImagePath;
  }

  public function saveToDisk($fileName, $path = null) {

    // If the path doesn't exist, create it first.
    Storage::makeDirectory($path);

    $padding = 150;

    $totalWidth = $this->imageWidth + $padding * 2;
    $totalHeight = $this->imageHeight + $padding * 2;

    $compositeImage = new \Imagick();
    $compositeImage->newImage($totalWidth, $totalHeight, new \ImagickPixel("#FFFFFF"));

    $draw = new \ImagickDraw();
    $draw->setStrokeColor("#000000");
    $draw->setStrokeWidth(3);

    // right, top vertical
    $draw->line($totalWidth - $padding, 0, $totalWidth - $padding, $padding/2);
    // right, top horizontal
    $draw->line($totalWidth - $padding/2, $padding, $totalWidth, $padding);

    // right, bottom vertical
    $draw->line($totalWidth - $padding, $totalHeight - $padding/2, $totalWidth - $padding, $totalHeight);
    // right, bottom horizontal
    $draw->line($totalWidth - $padding/2, $totalHeight - $padding, $totalWidth, $totalHeight -$padding);

    // left, top vertical
    $draw->line($padding, 0, $padding, $padding/2);
    // left, top horizontal
    $draw->line($padding/2, $padding, 0, $padding);

    // left, bottom vertical
    $draw->line($padding, $totalHeight - $padding/2, $padding, $totalHeight);
    // left, bottom horizontal
    $draw->line($padding/2, $totalHeight - $padding, 0, $totalHeight -$padding);

    $compositeImage->drawImage($draw);

    $handle = fopen($this->frontImagePath, 'rb');
    $image = new \Imagick();
    $image->readImageFile($handle);
    fclose($handle);

    $frontX = $this->imageWidth/2 + $padding;
    $frontY = $padding;

    // Add the image to the composite.
    $compositeImage->compositeImage($image, \imagick::COMPOSITE_DEFAULT, $frontX, $frontY);
    // destroy it from memory
    $image->clear();

    $name = $fileName.".pdf";

    $tmpPath = $this->basePath;
    $compositeImage->setImageUnits(\imagick::RESOLUTION_PIXELSPERINCH);
    $compositeImage->setResolution(300, 300);
    $compositeImage->setImageFormat("pdf");

    // Put directly on S3
    $storedFile = Storage::put($this->uniqueId."/".$name, $compositeImage->getImageBlob(), "public");

    $compositeImage->clear();

    $url = env("AWS_BASE_URL").$this->uniqueId."/".$name;

    return $url;
  }


}
