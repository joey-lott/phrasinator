<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Shopify\Utils\ProductUtils;
use App\Description;
use App\Etsy\Models\ShippingTemplate;
use App\Etsy\Models\Listing;
use App\Etsy\Models\ListingOffering;
use App\Etsy\Models\ListingProduct;
use App\Etsy\Models\PropertyValue;
use App\Etsy\Models\ListingStagingData;
use App\Listed;
use App\ImagePaths;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{

  public function __construct()
  {
      $this->middleware('auth');
  }


    public function showForm() {
      $paths = ImagePaths::where("userId", auth()->user()->id)->get()->all();
      $description = Storage::disk("local")->get("description.txt");
      $image = $paths[2]->imagePath.$paths[2]->imageName;
      $taxonomyId = 1265; // paper_and_party_supplies.paper.greeting_cards.blank_cards
      $price = 3;
      return view("productForm", ["image" => $image, "taxonomyId" => $taxonomyId, "price" => $price, "description" => $description]);
    }

    public function submit(Request $request) {
      $templates = json_decode(resolve("\App\Etsy\EtsyAPI")->fetchShippingTemplates(env("ETSY_USER_ID")));

      $tpc = \App\Etsy\Models\TaxonomyPropertyCollection::createFromTaxonomyId($request->taxonomyId);

      $staging = new ListingStagingData();

      $paths = ImagePaths::where("userId", auth()->user()->id)->get()->all();
      $image = $paths[2]->imagePath.$paths[2]->imageName;
      $templateId = $templates[0]->shipping_template_id;
      $listing = new Listing($request->title, $request->description, $request->price, $request->taxonomyId, $request->tags, $templateId, [$image], true);
      // Add the pdf
      $listing->addDigitalFileUrl($paths[1]->imagePath.$paths[1]->imageName);

      $response = $listing->saveToEtsy();
      if(isset($response["error"])) dd($response["error"]);


      return redirect("/home")->withMessage("Successfully Listed");
    }
}
