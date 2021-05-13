<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\RPL\TextImageV3;
use App\RPL\CompositeImageV2;
use App\RPL\Color;
use App\ImagePaths;
use App\Jobs\GenerateCompositeImage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Fonts;

class GeneratorController extends Controller
{

    //
    public function __construct()
    {
        $this->middleware('auth')->except("generatePreview");
        $this->middleware('subscribed')->except("generatePreview");
    }

    public function generateQueuedImageJob(Request $request) {
      

      $userId = auth()->user()->id;

      // Delete all the paths from previous jobs.
      ImagePaths::where("userId", $userId)->delete();

      // First, delete the user's files directory on s3 (default storage)
      if(Storage::exists($userId)) Storage::deleteDirectory($userId);
      // Then, create the directory for the user on s3
      Storage::makeDirectory($userId);

      // This is the path to the temp directory
      //$imagePath = base_path()."/storage/app/temp/";
      $imagePath = "temp";

      $size = $request->size;
      $width = 3000;
      $height = 3000;
      if($size == "medium") {
        $width = 2000;
        $height = 2000;
      }
      else if($size == "small") {
        $width = 1500;
        $height = 1500;
      }

      session(["phrase" => $request->phrase,
               "fontName" => $request->fontName,
               "size" => $request->size,
               "imageLocation" => $request->imageLocation,
               "imageUrl" => $request->pixabayImage,
               "lineSpacing" => $request->lineSpacing,
               "textJustification" => $request->textJustification]);


      ImagePaths::where("userId", $userId)->delete();
      GenerateCompositeImage::dispatch($userId, $width, $height, $request->phrase, $request->fontName, $request->imageLocation, $request->pixabayImage, $request->lineSpacing, $request->textJustification, $imagePath);
      // $job = new GenerateCompositeImage($userId, $width, $height, $request->phrase, $request->fontName, $request->imageLocation, $request->pixabayImage, $request->lineSpacing, $request->textJustification, $imagePath);
      // $job->handle();
      return view("displayimagesqueued", ["userId" => $userId]);

    }


    // Called as API endpoint
    public function generatePreview(Request $request) {

      $userId = $request->userId;

      // Delete all the paths from previous jobs.
      ImagePaths::where("userId", $userId)->delete();

      $imagePath = base_path()."/public/images/".$userId."/";

      // First, delete the user's files directory
      Storage::deleteDirectory($userId);
      // Then, create the directory for the user
      Storage::makeDirectory($userId);

      $width = 1000;
      $height = 1000;

      ImagePaths::where("userId", $userId)->delete();
      GenerateCompositeImage::dispatch($userId, $width, $height, $request->phrase, $request->fontName, $request->imageLocation, $request->pixabayImage, $request->lineSpacing, $request->textJustification, $imagePath);

      return ["status" => "ok", "userId" => $userId, "width" => $width, "height" => $height, "phrase" => $request->phrase, "fontName" => $request->fontName, "pixabayImage" => $request->pixabayImage, "lineSpacing" => $request->lineSpacing, "textJustification" => $request->textJustification, "imagePath" => $imagePath];

    }

/*    public function generate(Request $request) {

      // First, delete all images in the images folder
      $files = glob(base_path()."/public/images/*");
      foreach($files as $file) {
        if(is_file($file)) {
          unlink($file);
        }
      }

      $size = $request->size;
      $width = 3000;
      $height = 3000;
      if($size == "medium") {
        $width = 2000;
        $height = 2000;
      }
      else if($size == "small") {
        $width = 1500;
        $height = 1500;
      }

      session(["phrase" => $request->phrase,
               "fontName" => $request->fontName,
               "size" => $request->size,
               "imageLocation" => $request->imageLocation,
               "imageUrl" => $request->pixabayImage,
               "lineSpacing" => $request->lineSpacing,
               "textJustification" => $request->textJustification]);

         $path = $this->makeComposite($width, $height, $request->phrase, $request->fontName, $request->imageLocation, $request->pixabayImage, "_dark", new Color("000000"), $request->lineSpacing, $request->textJustification);
         $path1 = "/images/".($path);
         $path = $this->makeComposite($width, $height, $request->phrase, $request->fontName, $request->imageLocation, $request->pixabayImage, "_light", new Color("FFFFFF"), $request->lineSpacing, $request->textJustification);
         $path2 = "/images/".($path);

         return view("displayimages", ["path1" => $path1, "path2" => $path2]);

    }

    private function makeComposite($width, $height, $phrase, $fontName, $imageLocation, $pixabayImage, $fileNameUniqueSuffix, $color, $lineSpacing, $textJustification) {
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
      $image = new TextImageV3($phrase, $fontName, $width, $heightRemaining, $color, $lineSpacing, $textJustification);
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
      $path = $composite->saveToDisk($image->getFileName()."".$fileNameUniqueSuffix);
      return $path;
    }
*/
    public function form(Request $request) {

      // If "clear=now" is passed through the request, clear
      // session data for a new image.
      if($request->clear == "now") {
        session()->forget("phrase");
        session()->forget("fontName");
        session()->forget("size");
        session()->forget("imageLocation");
        session()->forget("imageUrl");
        session()->forget("lineSpacing");

      }

      // If there is session data, use it to populate the form.
      $phrase = session()->has("phrase") ? session()->get("phrase") : "";
      $fontName = session()->has("fontName") ? session()->get("fontName") : "";
      $size = session()->has("size") ? session()->get("size") : "large";
      $imageLocation = session()->has("imageLocation") ? session()->get("imageLocation") : "";
      $imageUrl = session()->has("imageUrl") ? session()->get("imageUrl") : "";
      $lineSpacing = session()->has("lineSpacing") ? session()->get("lineSpacing") : 0;
      $textJustification = session()->has("textJustification") ? session()->get("textJustification") : "center";
      $showExtras = !auth()->user()->onBasicPlan();

      return view("formWithImageSelector", ["phrase" => $phrase, "fontName" => $fontName, "size" => $size,
      "imageLocation" => $imageLocation,
      "imageUrl" => $imageUrl,
      "lineSpacing" => $lineSpacing,
      "textJustification" => $textJustification,
      "fonts" => Fonts::getFonts(auth()->user()->getCurrentStripePlanName()),
      "showExtras" => $showExtras,
      "userId" => auth()->user()->id]);
    }

}
