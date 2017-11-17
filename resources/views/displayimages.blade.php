@extends("layouts.app")

@section("content")
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">Image Results</div>

                <div class="panel-body">

                  <div class="row">
                    <div class="col-md-6">
                      <img src='{{$path1}}' width='200' height='200' border='1' style="border: 1px solid #000">
                    </div>

                    <div class="col-md-6">
                      <img src='{{$path2}}' width='200' height='200' border='1' style='background-color:black'>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-4">
                      <a href="/generate-with-image">MAKE CHANGES</a>
                    </div>
                    <div class="col-md-8">
                      <a href="/generate-with-image?clear=now">START A NEW IMAGE</a>
                    </div>
                  </div>




    </div>
</div>
</div>
</div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script>
</script>
@stop
