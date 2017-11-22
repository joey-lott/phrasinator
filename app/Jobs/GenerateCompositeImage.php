<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\ImagePaths;
use App\RPL\TextImageV2;
use App\RPL\CompositeImage;
use App\RPL\Color;
use App\UserLatestJobTime;
use Carbon\Carbon;

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
      $this->basePath = $basePath;


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
      // Check to see if this is the latest job for this user. If not,
      // return early.
      $latest = UserLatestJobTime::where("userId", $this->userId)->get()->last();

      dump($this->timeQueued);
      dump($latest->latestJobTime);

      if($this->timeQueued == $latest->latestJobTime) {
        dump("close enough");
        $this->runImageCreation();
      }

    }

    private function runImageCreation() {
      // First, delete all images in the images folder
      $files = glob(base_path()."/public/images/{$this->userId}/*");
      foreach($files as $file) {
        if(is_file($file)) {
//          unlink($file);
        }
      }

      $path = $this->makeComposite($this->width, $this->height, $this->phrase, $this->fontName, $this->imageLocation, $this->pixabayImage, "_dark", new Color("000000"), $this->lineSpacing, $this->textJustification, $this->basePath);
      $path1 = "/images/".$this->userId."/".($path);
      $path = $this->makeComposite($this->width, $this->height, $this->phrase, $this->fontName, $this->imageLocation, $this->pixabayImage, "_light", new Color("FFFFFF"), $this->lineSpacing, $this->textJustification, $this->basePath);
      $path2 = "/images/".$this->userId."/".($path);

      $ip = new ImagePaths();
      $ip->userId = $this->userId;
      $ip->imagePath = $path1;
      $ip->save();

      $ip = new ImagePaths();
      $ip->userId = $this->userId;
      $ip->imagePath = $path2;
      $ip->save();
    }

    private function makeComposite($width, $height, $phrase, $fontName, $imageLocation, $pixabayImage, $fileNameUniqueSuffix, $color, $lineSpacing, $textJustification, $basePath) {
      $composite = new CompositeImage($width, $height);

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
      // Set a default font size to -1 (which means ignore font size setting)
      // This is no longer used. Can probably delete next line. Comment for now.
      //$fontSize = isset($request->fontSize) ? $request->fontSize : -1;



      // Generate the text image
      $image = new TextImageV2($phrase, $fontName, $width, $heightRemaining, $color, $lineSpacing, $textJustification);
      $image->adjustFontToFillSpace();
      $resource = $image->generateImageResource();

      // Add the text images to the composite images. The language here is
      // confusing because the form asks for the *image* (i.e. pixabay image)
      // location relative to the text. But here, we are adding the text
      // to the composite. So if imageLocation is "above", call addBelow(),
      // and vice versa.
      if($imageLocation == "above") {
        $composite->addBelow($resource);
      }
      else {
        $composite->addAbove($resource);
      }
      // Set the transparency of the composite to the transparency generated by the text image
      $composite->setTransparent($image->transparent);
      $path = $composite->saveToDisk($image->getFileName()."".$fileNameUniqueSuffix, $basePath);
      return $path;
    }

}