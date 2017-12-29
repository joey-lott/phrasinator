<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\ImagePaths;
use App\RPL\TextImageV3;
use App\RPL\CardImage;
use App\RPL\CardFront;
use App\RPL\CardMockup;
use App\RPL\Color;
use App\UserLatestJobTime;
use Carbon\Carbon;
use App\AppSecrets;

class GenerateCardImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $width;
    private $height;
    private $phrase;
    private $fontName;
    private $imageLocation;
    private $pixabayImage;
    private $lineSpacing;
    private $textJustification;
    private $userId;
    private $basePath;
    private $timeQueued;
    private $border;
    private $borderColor;
    private $borderWidth;

    public function __construct($userId, $width, $height, $phrase, $fontName,
                                $imageLocation, $pixabayImage, $lineSpacing,
                                $textJustification, $basePath, $border, $borderColor, $borderWidth)
    {
      $this->userId = $userId;
      $this->width = $width;
      $this->height = $height;
      $this->phrase = $phrase;
      $this->fontName = $fontName;
      $this->imageLocation = $imageLocation;
      $this->pixabayImage = $pixabayImage;
      $this->lineSpacing = $lineSpacing;
      $this->textJustification = $textJustification;
      $this->border = $border;
      $this->borderColor = $borderColor;
      $this->borderWidth = $borderWidth;

      // This wasn't working on fortrabbit. It worked locally, so there's
      // a difference between how jobs are run locally versus on forrabbit
      // (obviously). For now, set basePath in handle() instead.
      //$this->basePath = $basePath;


      $this->timeQueued = Carbon::now();

      // Remove records for the user's queued job times
      UserLatestJobTime::where("userId", $userId)->delete();
      // Insert a new record.
      $latest = new UserLatestJobTime();
      $latest->latestJobTime = $this->timeQueued;
      $latest->userId = $userId;
      $latest->save();

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

      $p = getcwd();
      // If the last character of the path is not a /, add one.
      if(substr($p, strlen($p)-1) != "/") {
        $p .= "/";
      }
      $this->basePath = $p;

      // Check to see if this is the latest job for this user. If not,
      // return early.
      $latest = UserLatestJobTime::where("userId", $this->userId)->get()->last();


      if($this->timeQueued == $latest->latestJobTime) {
        return $this->runImageCreation();
      }
    }

    private function getPathAndFile($path, $file) {
      $index = strpos($path, $file);
      $returnPath = substr($path, 0, $index);
      $returnFile = substr($path, $index);
      return ["file" => $returnFile, "path" => $returnPath];
    }

    private function runImageCreation() {

//      $width =

      $front = $this->makeFront(3000, 2100, $this->phrase, $this->fontName, $this->imageLocation, $this->pixabayImage, "", new Color("000000"), $this->lineSpacing, $this->textJustification, $this->basePath, $this->border, $this->borderColor, $this->borderWidth);
      $frontPath = $front["tempPath"];
      $frontUrl = $front["url"];
      $filename = $front["filename"];
      $frontParts = $this->getPathAndFile($frontUrl, $filename);
      $card5x7Path = $this->make5x7png($this->basePath, $frontPath, $filename);
      $card5x7parts = $this->getPathAndFile($card5x7Path, $filename);
      $mockupPath = $this->makeMockup($this->basePath, $frontPath, $filename."_mockup");
      $mockupParts = $this->getPathAndFile($mockupPath, $filename."_mockup");

      $ip = new ImagePaths();
      $ip->userId = $this->userId;
      $ip->imagePath = $frontParts["path"];
      $ip->imageName = $frontParts["file"];
      $ip->save();

      $ip = new ImagePaths();
      $ip->userId = $this->userId;
      $ip->imagePath = $card5x7parts["path"];
      $ip->imageName = $card5x7parts["file"];
      $ip->save();

      $ip = new ImagePaths();
      $ip->userId = $this->userId;
      $ip->imagePath = $mockupParts["path"];
      $ip->imageName = $mockupParts["file"];
      $ip->save();

      return $card5x7parts["path"];
    }

    private function makeFront($width, $height, $phrase, $fontName, $imageLocation, $pixabayImage, $filenameUniqueSuffix, $color, $lineSpacing, $textJustification, $basePath) {
      $composite = new CardFront($width/2, $height, $this->userId, $basePath, $this->border, $this->borderColor, $this->borderWidth);

      // If image location was set to above or below (not none), try to grab the image
      if($imageLocation == "above" || $imageLocation == "below") {
          // If the pixabay image was selected from the grid of search results
          if(isset($pixabayImage)) {
            // Get the image from pixabay
            $composite->fetchFromUrl($pixabayImage);
          }
      }
      // This is how much height remains to fill in the composite image.
      // Only need to do this for one of the composites since both will report the same
      $heightRemaining = $composite->fetchHeightRemaining();

      ini_set('max_execution_time', 120);

      // Generate the text image
      $image = new TextImageV3($phrase, $fontName, $composite->fetchDesignWidth(), $heightRemaining, $color, $lineSpacing, $textJustification, $basePath);
      $image->adjustFontToFillSpace();
      $image->generateImageResource();
      $imageData = $image->saveToDisk();
      $image->destroy();

      // Add the text images to the composite images. The language here is
      // confusing because the form asks for the *image* (i.e. pixabay image)
      // location relative to the text. But here, we are adding the text
      // to the composite. So if imageLocation is "above", call addBelow(),
      // and vice versa.
      if($imageLocation == "above") {
        $composite->addBelow($imageData);
      }
      else {
        $composite->addAbove($imageData);
      }
      $filename = $image->getfilename();
      $path = $composite->saveToDisk($filename."".$filenameUniqueSuffix, $this->userId);
      return ["tempPath" => $path["tempPath"], "url" => $path["url"], "filename" => $filename];
    }

    private function make5x7png($basePath, $frontPath, $filename) {
      $width = 3000;
      $height = 2100;
      $cardImage = new CardImage($width, $height, $this->userId, $basePath, $frontPath);
      $cardImagePath = $cardImage->saveToDisk($filename, $this->userId);

      return $cardImagePath;
    }

    private function makeMockup($basePath, $frontPath, $filename) {

      $mockup = new CardMockup(1000, 1000, $this->userId, $basePath, $frontPath);
      $mockupPath = $mockup->saveToDisk($filename, $this->userId);
      return $mockupPath;
    }

}
