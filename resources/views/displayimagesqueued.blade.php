@extends("layouts.app")

@section("content")
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">Image Results</div>

                <div class="panel-body">

                  <div class="row">
                    <div class="col-md-12">
                      <div class="alert alert-info" id="message">
                        Please wait as images are generated. It may take a moment. Do not reload this page.
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-6">
                      <img id="image1" width='200' height='200' border='1' style="border: 1px solid #000">
                    </div>

                    <div class="col-md-6">
                      <img id="image2" width='200' height='200' border='1' style='background-color:black'>
                    </div>
                  </div>
                  <div class="row">&nbsp;</div>
                  <div class="row">
                    <div class="col-md-12">
                      <a href="/generate">MAKE CHANGES</a>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-md-12">
                      <a href="/generate?clear=now">START A NEW IMAGE</a>
                    </div>
                  </div>




    </div>
</div>
</div>
</div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script>
  setTimeout(testForImages, 1000);

  function testForImages() {

    url = "/api/get-image-paths/{{$userId}}";
    $.get(url, handleImagePathResults);
  }

  function handleImagePathResults(response) {
    if(response.length < 2) {
      setTimeout(testForImages, 2000);
    }
    else {
      $("#message").empty();
      $("#message").html("Images created and loading...");
      $("#image1").attr("src", response[0].imagePath);
      $("#image2").attr("src", response[1].imagePath);
    }
  }
</script>
@stop
