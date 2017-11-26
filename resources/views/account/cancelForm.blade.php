@extends("layouts.app")

@section("content")
<script src="js/jscolor.js"></script>
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">My Account</div>

                <div class="panel-body">

                  If you cancel your subscription, you will be able to continue using the app until the end of your billing cycle. After that, you will no longer be able to access the app without purchasing a new subscription.<br><br>
                  Are you sure you wish to cancel?
                  <form method="post" action="/account/cancel">
                    {{csrf_field()}}
                    <button class="btn btn-default">Yes, Cancel My Subscription Now</button>
                  </form>

    </div>
</div>
</div>
</div>
</div>
@stop
