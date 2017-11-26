@extends("layouts.app")

@section("content")
<script src="js/jscolor.js"></script>
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">Welcome</div>

                <div class="panel-body">

                    <h2>Welcome to phrasinator</h2>
                    <p>You are currently on a 7 day trial.</p>
                    <p>You will be billed for the first time 7 days from now unless you choose to cancel before then.</p>
                    <p>Your bill will be monthly for {{$total}}.</p>
                    <p><a href="/home">continue to the dashboard</a></p>

    </div>
</div>
</div>
</div>
</div>
@stop
