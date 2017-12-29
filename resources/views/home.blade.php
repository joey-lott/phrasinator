@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">Dashboard</div>

                @if(session()->has("message"))
                <div class="alert alert-success">
                  {{session()->get("message")}}
                </div>
                @endif

                <div class="panel-body">
                  <a href="/generate">IMAGE GENERATOR</a><br><br>
                  <a href="/generate/print">PRINT IMAGE GENERATOR</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
