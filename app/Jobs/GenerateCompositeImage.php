<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\ImagePaths;
use App\RPL\TextImageV3;
use App\RPL\CompositeImageV2;
use App\RPL\Color;
use App\UserLatestJobTime;
use Carbon\Carbon;
use App\AppSecrets;

class GenerateCompositeImage implements ShouldQueue
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

    public function __construct($userId, $width, $height, $phrase, $fontName,
                                $imageLocation, $pixabayImage, $lineSpacing,
                                $textJustification, $basePath)
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
      dblog("GenerateCompositImage->handle()", "job queue");

      $this->basePath = getcwd()."/";

      // Check to see if this is the latest job for this user. If not,
      // return early.
      $latest = UserLatestJobTime::where("userId", $this->userId)->get()->last();


      if($this->timeQueued == $latest->latestJobTime) {
        $this->runImageCreation();
      }
    }

    private function getPathAndFile($path, $file) {
      $index = strpos($path, $file);
      $returnPath = substr($path, 0, $index);
      $returnFile = substr($path, $index);
      return ["file" => $returnFile, "path" => $returnPath];
    }

    private function runImageCreation() {

      $path1 = $this->makeComposite($this->width, $this->height, $this->phrase, $this->fontName, $this->imageLocation, $this->pixabayImage, "_dark", new Color("000000"), $this->lineSpacing, $this->textJustification, $this->basePath);
      $path2 = $this->makeComposite($this->width, $this->height, $this->phrase, $this->fontName, $this->imageLocation, $this->pixabayImage, "_light", new Color("FFFFFF"), $this->lineSpacing, $this->textJustification, $this->basePath);

      $pathAndFile1 = $this->getPathAndFile($path1["path"], $path1["fileName"]);
      $pathAndFile2 = $this->getPathAndFile($path2["path"], $path2["fileName"]);

      $ip = new ImagePaths();
      $ip->userId = $this->userId;
      $ip->imagePath = $pathAndFile1["path"];
      $ip->imageName = $pathAndFile1["file"];
      $ip->save();

      $ip = new ImagePaths();
      $ip->userId = $this->userId;
      $ip->imagePath = $pathAndFile2["path"];
      $ip->imageName = $pathAndFile2["file"];
      $ip->save();

      return $path1["path"];
    }

    private function makeComposite($width, $height, $phrase, $fontName, $imageLocation, $pixabayImage, $fileNameUniqueSuffix, $color, $lineSpacing, $textJustification, $basePath) {
      $composite = new CompositeImageV2($width, $height, $this->userId, $basePath);

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

      ini_set('max_execution_time', 60);

      // Generate the text image
      $image = new TextImageV3($phrase, $fontName, $width, $heightRemaining, $color, $lineSpacing, $textJustification, $basePath);
      $image->adjustFontToFillSpace();
      $image->generateImageResource();
      $imageData = $image->saveToDisk($this->userId."_tmp_text");
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
      $fileName = $image->getFileName();
      $path = $composite->saveToDisk($fileName."".$fileNameUniqueSuffix, $this->userId);
      return ["path" => $path, "fileName" => $fileName];
    }

}
