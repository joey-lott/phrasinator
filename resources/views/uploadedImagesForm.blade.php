@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">Upload Image</div>

                <div class="panel-body">
                  <form method="post" enctype="multipart/form-data" action="/image/upload">
                    {{csrf_field()}}
                    <div class="col-sm-6">
                      <input type="file" class="form-control" name="image">
                    </div>
                    <div class="col-sm-6">
                      <button class="btn btn-primary form-control">UPLOAD</button>
                    </div>
                  </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
