<form action="/charge" method="post" id="payment-form">
  <div class="row" id="card-errors-container" style="height: 0">
    <div id="card-errors" class="alert alert-danger" style="visibility: hidden">
    </div>
  </div>
  <div class="form-group">
      <label for="card-exp">Name on Card</label>
      <div>
          <input type="text" id="name-on-card" class="form-control">
      </div>
  </div>
  <div class="form-group">
      <label for="card-number">Credit Card Number</label>
      <span id="card-number" class="form-control">
          <!-- Stripe Card Element -->
      </span>
  </div>
  <div class="row">
    <div class="form-group col-lg-3">
        <label for="card-cvc">CVC Number</label>
        <span id="card-cvc" class="form-control">
            <!-- Stripe CVC Element -->
        </span>
    </div>
    <div class="form-group col-lg-3">
        <label for="card-exp">Expiration</label>
        <span id="card-exp" class="form-control">
            <!-- Stripe Card Expiry Element -->
        </span>
    </div>
    <div class="form-group col-lg-6">
        <label for="card-exp">Billing Postal Code</label>
        <div>
            <input type="text" id="billing-zip" class="form-control">
        </div>
    </div>
  </div>
  <div class="row">
    <div class="col-lg-12">
      <button class="btn btn-primary form-control" id="submitPaymentButton">Submit Payment and Begin Your 7-Day Trial</button>
    </div>
  </div>
</form>
