@extends("layouts.app")

@section("content")
<script src="js/jscolor.js"></script>
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">My Account</div>

                <div class="panel-body">

                  You have successfully cancelled your account.<br><br>
                  You will not be billed for any more use.<br>
                  However, you will be able to continue using the app until {{$endsAt->toDayDateTimeString()}} UTC, which is the end of your trial or billing cycle.<br><br>
                  <a href="/account">return to "my account" (where you can resume your subscription if you made a mistake)</a><br>
                  <a href="/home">return to dashboard</a>

    </div>
</div>
</div>
</div>
</div>
@stop
