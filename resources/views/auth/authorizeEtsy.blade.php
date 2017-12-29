@extends('layouts.app')

@section('content')
<div class="panel-heading">Etsy Authorization</div>

<div class="panel-body">
  You must authorize GB Lightning Lister to make changes to your Etsy seller account on your behalf.<br>
  <a href="{{$etsyLink}}">CLICK HERE TO GO TO ETSY AUTHORIZATION</a>
</div>
@stop
