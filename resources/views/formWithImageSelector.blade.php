@extends("layouts.app")

@section("content")
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">Image Settings</div>

                <div class="panel-body">


      <form method="post" action="/generate">
        {{csrf_field()}}
        <div class="row form-group">
          <label class="col-md-2 form-group">font style:</label>
          <div class="col-md-10">
            <?php
              $fonts = [["file" => "knockout.ttf", "label" => "knockout"],
                        ["file" => "GILLUBCD.ttf", "label" => "gill"],
                        ["file" => "KGSecondChancesSketch.ttf", "label" => "sketch"],
                        ["file" => "STENCIL.ttf", "label" => "stencil"],
                        ["file" => "jackport.ttf", "label" => "varsity"]
                       ];
            ?>
            <select name="fontName" class="form-control">
              @foreach($fonts as $font)
                <option value="{{$font["file"]}}"<?php if($fontName == $font["file"]) echo " selected"; ?>>{{$font["label"]}}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="row form-group">
          <label class="form-group col-md-2">Phrase:</label>
          <div class="col-md-10">
            <input type="text" name="phrase" class="form-control" value="{{$phrase}}">
          </div>
        </div>

        <div>
          <button class="btn btn-primary">Make Image</button>
        </div>
        <div></div>

        <hr>

        <div class="row form-group">
          <label class="form-group col-md-2">Line Spacing:</label>
          <div class="col-md-10">
            <input type="range" name="lineSpacing" class="form-control" min="0" max=".5" step="0.05" value="{{$lineSpacing}}">
          </div>
        </div>

        <div class="row form-group">
          <label class="form-group col-md-2">Text Justification:</label>
          <div class="col-md-10">
              <input type="radio" name="textJustification" value="left"<?php if($textJustification == "left") echo " checked";?>> Left
              <input type="radio" name="textJustification" value="center"<?php if($textJustification == "center") echo " checked";?>> Center
              <input type="radio" name="textJustification" value="right"<?php if($textJustification == "right") echo " checked";?>> Right
          </div>
        </div>

        <div class="row form-group">
          <label class="form-group col-md-2">Pixabay Image Location:</label>
          <div class="col-md-10">
            <select name="imageLocation" class="form-control">
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
            <input type="hidden" name="pixabayImage" value="{{$imageUrl}}">
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


    </div>
</div>
</div>
</div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script>

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
</script>
@stop
