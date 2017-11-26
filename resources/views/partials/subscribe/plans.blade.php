<form action="/subscribe" method="post" id="choose-subscription-form">
  <input type="hidden" id="stripeToken" name="stripeToken">
  {{csrf_field()}}

  <div class="row">
    <h3 class="col-lg-12">1. Choose a Plan</h3>
  </div>

  <div class="form-group" style="text-align:center">
    <div class="col-lg-6">
      <input type="radio" id="basic" style="visibility:hidden;position:absolute" name="subscriptionType" value="phrasinator-basic-monthly" onchange="choseSubscription(this)" checked>
      <label for="basic" style="cursor:pointer;width:100%">
        <div id="basic-panel" class="panel panel-primary">
          <div class="panel-heading panel-primary">
            <div>Basic Subscription</div>
          </div>
          <div class="panel-body">
            <h3>$9.99/month</h3>
            <div style="margin:auto;height:100px;width:100px">
              <img src="/images/checkmark.png" style="visibility:show;height:100px;width:100px" id="basic-check">
            </div>
          </div>
        </div>
      </label>
    </div>
    <div class="col-lg-6">
      <input type="radio" id="plus" style="visibility:hidden;position:absolute" name="subscriptionType" value="phrasinator-plus-monthly" onchange="choseSubscription(this)">
      <label for="plus" style="cursor:pointer;width:100%">
        <div id="plus-panel" class="panel panel-default">
          <div class="panel-heading panel-primary">Plus Subscription</div>
          <div class="panel-body">
            <h3>$19.99/month</h3>
            <div style="margin:auto;height:100px;width:100px">
              <img src="/images/checkmark.png" style="visibility:hidden;height:100px;width:100px" id="plus-check">
            </div>
          </div>
        </div>
      </label>
    </div>
  </div>

  <div class="row">
    <h3 class="col-lg-12">2. Enter a Coupon</h3>
  </div>

  <div class="row">
    <label class="form-group col-md-3">Have a coupon code?</label>
    <div class="col-md-2">
      <input type="text" class="form-control" name="coupon">
    </div>
    <div class="col-md-7">
      @if($couponError)
      <div class="alert alert-warning">
        Your Coupon Code Was Invalid
      </div>
      @endif
    </div>
  </div>
</form>
