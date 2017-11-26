@extends("layouts.app")

@section("content")
<script src="js/jscolor.js"></script>
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">My Account</div>

                <div class="panel-body">

                  @if(session()->has("message"))
                    <div class="alert-info">
                      {{session()->get("message")}}
                    </div>
                    <div>&nbsp;</div>
                  @endif
                  @if($onGracePeriod)
                    You have cancelled your subscription.<br><br>
                    We have scheduled to close your account at {{$endsAt->toDayDateTimeString()}} UTC.<br><br>
                    Would you like to resume your subscription so that you can continue using the app without interruption? Doing so will resume your billing agreement, and you will be charged again at the end of the billing cycle.
                    <form method="post" action="/account/resume">
                      {{csrf_field()}}
                      <button class="btn btn-default">Resume My Subscription</button>
                    </form>
                  @else
                    <div>You are on the {{$currentPlanName}} plan.</div>
                    <div>Your next bill will be {{$invoice->date()->toDayDateTimeString()}} UTC for {{$invoice->total()}}.</div>

                    <form method="get" action="/account/change">
                      <button class="btn btn-default">Change My Subscription</button>
                    </form>

                    <form method="get" action="/account/cancel">
                      <button class="btn btn-default">Cancel My Subscription</button>
                    </form>
                  @endif

    </div>
</div>
</div>
</div>
</div>
@stop
