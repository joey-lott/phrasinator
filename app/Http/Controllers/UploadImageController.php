<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\UploadedImage;

class UploadImageController extends Controller
{
    public function form() {
      return view("uploadedImagesForm");
    }

    public function upload(Request $request) {
//      $name = uniqid().".png";
      $id = auth()->user()->id;
      Storage::makeDirectory("uploads/".$id);
      $s3File = Storage::putFile("uploads/".$id, $request->file("image"), "public");
      $url = env("AWS_BASE_URL").$s3File;
      $ui = new UploadedImage();
      $ui->userId = $id;
      $ui->url = $url;
      $ui->save();
      return redirect("/home");
    }

    // This is called by API route. Needs not to have middleware applied.
    public function getImages($userId) {
      $ui = UploadedImage::where("userId", $userId)->get()->all();
      return $ui;
    }
}
