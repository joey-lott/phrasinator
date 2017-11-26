@extends("layouts.app")

@section("content")
<style>

.StripeElement {
  background-color: white;
  padding: 10px 12px;
  border-radius: 4px;
  border: 1px solid transparent !important;
  box-shadow: 0 1px 3px 0 #e6ebf1;
  -webkit-transition: box-shadow 150ms ease;
  transition: box-shadow 150ms ease;
}

.StripeElement--focus {
  box-shadow: 0 1px 3px 0 #cfd7df;
}

.StripeElement--invalid {
  border-color: #fa755a;
}

.StripeElement--webkit-autofill {
  background-color: #fefde5 !important;
}
</style>

<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">Change Plan</div>

                <div class="panel-body">

                  <p>You are currently on the {{$planName}} plan.</p>
                  <p>Would you like to switch to the {{$otherPlanName}} plan for {{$otherPlanPrice}}?</p>

                  <form action="{{$planFormAction}}" method="post">
                    {{csrf_field()}}
                    <input type="hidden" name="planName" value="{{$otherPlanId}}">
                    <button class="btn btn-primary">YES, CHOOSE THIS PLAN</button>
                  </form>

                  <div>&nbsp;</div>

                  <div>Here are the details of what you'll get in this plan:</div>
                  <div class="row">
                    <div class="col-md-6">
                    @include("partials.plans.".strtolower($otherPlanName))
                    </div>
                    <div class="col-md-6">
                    </div>
                  </div>
                </div>
              </div>

            </div>
        </div>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script>

// Custom styling can be passed to options when creating an Element.
// (Note that this demo uses a wider set of styles than the guide below.)
var style = {
  base: {
    color: '#32325d',
    lineHeight: '18px',
    fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
    fontSmoothing: 'antialiased',
    fontSize: '16px',
    '::placeholder': {
      color: '#aab7c4'
    }
  },
  invalid: {
    color: '#fa755a',
    iconColor: '#fa755a'
  }
};

var card;
var stripe;
var elements;

// Wait until the document is ready...
$(document).ready(function() {
  var form = document.getElementById('payment-form');

  // Handle the form submit for the payment form...
  form.addEventListener('submit', function(event) {
    event.preventDefault();
    // Call this function to keep vars in correct scope
    // Otherwise "this" is the form instead of Window, as it should be
    handlePaymentSubmit();
  });

  stripe = Stripe("{{$stripeKey}}");

  // This code inserts the strip form elements into the form
  elements = stripe.elements();
  card = elements.create('card', {style: style});
  card.mount('#card-element');

});

function handlePaymentSubmit() {
  var paymentButton = document.getElementById('submitPaymentButton');
  //paymentButton.disabled = true;

  // Get the token from Stripe
  var p = stripe.createToken(card);
  p.then(handleStripeResponse);
}

function handleStripeResponse(result) {
    if (result.error) {
      // Inform the customer that there was an error
      var errorElement = document.getElementById('card-errors');
      errorElement.textContent = result.error.message;
      paymentButton.disabled = false;
    } else {
      // Send the token to the server
      stripeTokenHandler(result.token);
    }
}

function stripeTokenHandler(token) {
  var paymentButton = document.getElementById('submitPaymentButton');
  paymentButton.disabled = true;

  var subscribeForm = document.getElementById('choose-subscription-form');
  var stripeToken = document.getElementById('stripeToken');

  // Insert the value of the token in a hidden form field
  stripeToken.value = token.id;
  // Submit the form
  subscribeForm.submit();
}

// Allow the user to submit payment only after choosing a subscription type
function choseSubscription(input) {
  var paymentButton = document.getElementById('submitPaymenButton');
  paymentButton.disabled = false;
}

</script>

@stop
