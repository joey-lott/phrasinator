@extends("layouts.app")

@section("content")
<script src="js/jscolor.js"></script>
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">Image Settings</div>

                <div class="panel-body">


                  <!-- Commenting this out for now because
                       the preview generation won't work until
                       I fix how the composite image gets created.
                       right now the size of the composite image
                       doesn't shrink or grow the entire contents.

                  <div class="row">
                    <div class="col-lg-12">
                      <img id="preview" width='200' height='200' border='1' style="border: 1px solid #000">
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-lg-12">
                      <form action="/" id="preview-form">
                        <button>preview</button>
                      </form>
                    </div>
                  </div>
                -->


      @foreach($fonts as $font)
      <?php
        if(isset($font->googleName)) {
          $googleName = implode("+", explode(" ", $font->googleName));
          echo '<link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family='.$googleName.'" />';

        }
      ?>
      @endforeach
      <form method="post" action="/generate">
        {{csrf_field()}}
        <div class="row form-group">
          <label class="col-md-2 form-group">font style:</label>
          <div class="col-md-10">
            <select name="fontName" id="fontName" class="form-control">
              @foreach($fonts as $font)
                <?php
                  if(isset($font->googleName)) {
                    $googleName = implode("+", explode(" ", $font->googleName));
                    echo '<option value="'.$font->file.'" style="font-family:\''.$font->googleName.'\'; font-size: 20px"';
                    if($fontName == $font->file) echo " selected";
                    echo '>'.$font->label.'</option>';
                  }
                  else {
                    echo '<option value="'.$font->file.'"';
                    if($fontName == $font->file) echo " selected";
                    echo '>'.$font->label.'</option>';
                  }
                ?>
              @endforeach
            </select>
          </div>
        </div>
        <div class="row form-group">
          <label class="form-group col-md-2">Phrase:</label>
          <div class="col-md-10">
            <input type="text" name="phrase" id="phrase" class="form-control" value="{{$phrase}}">
          </div>
        </div>

        <div class="row form-group">
          <label class="form-group col-md-2">Color Markup Generator:</label>
          <div class="col-md-3">
            <input class="jscolor" onchange="updateColorMarkup(this.jscolor)">
          </div>
          <div class="col-md-7">
            <input type="text" id="colorMarkup" class="form-control">
          </div>
        </div>

        <div>
          <button class="btn btn-primary">Make Image</button>
        </div>
        <div></div>

        @if($showExtras)
        <hr>

        <div class="row form-group">
          <label class="form-group col-md-2">Line Spacing:</label>
          <div class="col-md-10">
            <input type="range" name="lineSpacing" id="lineSpacing" class="form-control" min="-0.5" max=".5" step="0.01" value="{{$lineSpacing}}">
          </div>
        </div>

        <div class="row form-group">
          <label class="form-group col-md-2">Text Justification:</label>
          <div class="col-md-10">
              <input type="radio" name="textJustification" id="align-left" value="left"<?php if($textJustification == "left") echo " checked";?>> Left
              <input type="radio" name="textJustification" id="align-center" value="center"<?php if($textJustification == "center") echo " checked";?>> Center
              <input type="radio" name="textJustification" id="align-right" value="right"<?php if($textJustification == "right") echo " checked";?>> Right
          </div>
        </div>

        <div class="row form-group">
          <label class="form-group col-md-2">Pixabay Image Location:</label>
          <div class="col-md-10">
            <select name="imageLocation" id="imageLocation" class="form-control">
              <option>none</option>
              <option<?php if($imageLocation == "above") echo " selected"; ?>>above</option>
              <option<?php if($imageLocation == "below") echo " selected"; ?>>below</option>
            </select>
          </div>
        </div>

        <div class="row form-group">
          <label class="form-group col-md-2">Search Pixabay:</label>
          <div class="col-md-4">
            <input type="text" id="pixabayKeyword" class="form-control">
          </div>
          <div class="col-md-3">
            <button class="btn btn-default" onclick="runPixabaySearch(event);">Search Pixabay</button>
          </div>
          <label class="form-group col-md-2">Only Vectors?:</label>
          <div class="col-md-1">
            <input type="checkbox" id="vectorOnly" class="form-control" checked>
          </div>
        </div>
        <div class="row form-group" id="images">
          @if($imageUrl != "")
            <img src="{{$imageUrl}}" width="100">
            <input type="hidden" name="pixabayImage" id="pixabayImage" value="{{$imageUrl}}">
          @endif
        </div>
        <div class="row form-group">
          <label class="form-group col-md-2">Total Image Size:</label>
          <div class="col-md-10">
            <div class="row">
              <input type="radio" name="size" value="large"<?php if($size == "large") echo " checked";?>> Large
              <input type="radio" name="size" value="medium"<?php if($size == "medium") echo " checked";?>> Medium
              <input type="radio" name="size" value="small"<?php if($size == "small") echo " checked";?>> Small
            </div>
            <div class="row">
              (smaller total image size means pixabay images appear larger relative to text)
            </div>
          </div>
        </div>
        <div>
          <button class="btn btn-primary">Make Image</button>
        </div>
      </form>
      @endif

    </div>
</div>
</div>
</div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script>

// Wait until the document is ready...
$(document).ready(function() {

  var form = $('#preview-form');
  // Handle the form submit for the payment form...
  form.submit(function(event) {
    var userId = {{$userId}};
    var phrase = $("#phrase").val();
    var fontName = $("#fontName").val();
    var imageLocation = $("#imageLocation").val();
    var pixabayImage = $("#pixabayImage").val();
    if(typeof(pixabayImage) === "undefined") {
      // It might me undefined if the user ran a search instead of using a session-stored image URL
      // So look for radiobuttons...
      var radioVal = $("input[name=pixabayImage]:checked").val();
      if(typeof(radioVal) !== "undefined") {
        pixabayImage = radioVal;
      }
    }
    var lineSpacing = $("#lineSpacing").val();
    var textJustification;
    if($("#align-center").attr("checked") == "checked") textJustification = "center";
    else if($("#align-left").attr("checked") == "checked") textJustification = "left";
    if($("#align-center").attr("checked") == "checked") textJustification = "right";
    console.log(userId, phrase, fontName, imageLocation, pixabayImage, lineSpacing, textJustification);
    event.preventDefault();
    var url = "/api/generate-preview?userId="+userId+"&phrase="+phrase+"&fontName="+fontName+"&imageLocation="+imageLocation+"&lineSpacing="+lineSpacing+"&textJustification="+textJustification;
    if(typeof(pixabayImage) !== "undefined") url += "&pixabayImage="+pixabayImage;
    $.get(url, function(response) {
      // Start a timeout to check for the images
      setTimeout(testForImages, 1000);
    });
  });
});

function testForImages() {

  url = "/api/get-image-paths/{{$userId}}";
  $.get(url, handleImagePathResults);
}

function handleImagePathResults(response) {
  console.log(response);
  if(response.length < 2) {
    setTimeout(testForImages, 2000);
  }
  else {
    $("#preview").attr("src", response[0].imagePath);
  }
}

  // The page number for pixabay searches. Default to 1 for first page.
  let page = 1;

  // The total number of search results available to page through. Returned by pixabay search.
  let totalHits;
  let hits;

  function runPixabaySearch(event) {
    event.preventDefault();
    fetchImagesPage();
    return false;
  }

  function fetchImagesPage() {
    keyword = $("#pixabayKeyword").val();
    url = "/api/search-pixabay?keyword="+keyword+"&page="+page;
    if($("#vectorOnly").prop("checked")) {
      url += "&vectorOnly=yes"
    }
    $.get(url, handlePixabayResults);
  }

  function handlePixabayResults(response) {
    totalHits = response.totalHits;
    hits = response.hits;
    $("#images").empty();
    for(i = 0; i < hits.length; i++) {

      // Pixabay returns 640 images, but allows up to 960. So swap 640 out with 960 in the returned URLs
      let wfurl = hits[i].webformatURL;
      let wfurlParts = wfurl.split("_");
      let fileExtension = wfurlParts[1].split(".")[1];
      wfurl = wfurlParts[0]+"_960."+fileExtension;

      $("#images").append("<input type='radio' id='image"+i+"' name='pixabayImage' onchange='imageSelectionChange(this);' value='"+wfurl+"' style='visibility:hidden;position:absolute'><label for='image"+i+"' id='image"+i+"_label' style='border: 2px solid transparent'><img src='"+hits[i].previewURL+"'></label>");
    }
  }

  function imageSelectionChange(selectedRadio) {
    for(i = 0; i < hits.length; i++) {
      $("#image"+i+"_label").css("border", "2px solid transparent");
    }
    $("#"+selectedRadio.id+"_label").css("border", selectedRadio.checked ? "2px solid #f00" : "2px solid transparent");
  }

  function updateColorMarkup(jscolor) {
    $("#colorMarkup").val("[[[color="+jscolor.toString()+"]]]");
  }
</script>
@stop
