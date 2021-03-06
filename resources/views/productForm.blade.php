@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
          <div class="panel-heading">Verify the Listing Information</div>
          <div class="panel-body">
            <div>

              <form action="/list" method="post" id="listingForm">
              {{csrf_field()}}

              <input type="hidden" name="taxonomyId" value="{{$taxonomyId}}" />

                <div class="well">
                  <div class="form-group row">
                    <label class="col-sm-2">Title:</label>
                    <div class="col-sm-8">
                      <input type="text" class="form-control" onkeyup="testTitle()" id="title" name="title" value="" width="200">
                    </div>
                    <div class="col-sm-2">
                      <input type="text" readonly class="form-control" id="titleChars">
                    </div>
                  </div>
                  <div class="form-group row">
                    <label class="col-sm-2">Tags:</label>
                    <div class="col-sm-10">
                      <input type="text" class="form-control" name="tags" id="tags" value="">
                    </div>
                  </div>

                  <div class="row">
                    <label class="col-sm-2">Mockup Image:</label>
                    <div class="col-sm-10">
                        <img src="{{$image}}" width="200">
                    </div>
                  </div>

                  <div class="row">
                    <label class="col-sm-2">Description:</label>
                    <div class="col-sm-10">
                      <textarea name="description" class="form-control" rows="20" id="description">{{$description}}</textarea>
                    </div>
                  </div>
                    <div class="row">
                      <label class="col-sm-2">Price</label>
                      <div class="col-sm-10">
                        <input type="Text" class="form-control" name="price" value="{{$price}}">
                      </div>
                    </div>
                </div>

                <div class="row">
                  <label class="col-sm-2"></label>
                  <div class="col-sm-10"><button id="btn" class="btn btn-primary">Submit Listing to Etsy as Draft</button>
                </div>
              </form>
            </div>
          </div>
        </div>
    </div>
</div>
<script>
  testTitle();
  function testTitle() {
    titleInput = document.getElementById("title");
    titleChars = document.getElementById("titleChars");
    btn = document.getElementById("btn");
    title = titleInput.value;
    if(title.length > 140) {
      btn.disabled = true;
    }
    else {
      btn.disabled = false;
    }
    titleChars.value = title.length;
    var tags = [];
    tagsInput = document.getElementById("tags");
    phrases = title.split(" - ");
    for(var i = 0; i < phrases.length; i++) {
      tags.push(validateTag(phrases[i]));
    }
    words = title.split(" ");
    for(var i = 0; i < words.length; i++) {
      word = words[i];
      if(word == "-") continue;
      tags.push(validateTag(word));
    }
    tags = filterUnique(tags);
    tagsInput.value = tags.join(",");
  }

  function filterUnique(t) {
    var keys = {};
    var unique = [];
    for(var i = 0; i < t.length; i++) {
      if(keys.hasOwnProperty(t[i]) || t[i] == "") continue;
      keys[t[i]] = true;
      unique.push(t[i]);
      if(unique.length == 13) return unique;
    }
    return unique;
  }

  function validateTag(tag) {
    if(tag.length <= 20) {
      return tag;
    }
    parts = tag.split(" ");
    parts.pop();
    return validateTag(parts.join(" "));
  }
</script>
@endsection
