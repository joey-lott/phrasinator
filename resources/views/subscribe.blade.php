@extends("layouts.app")

@section("content")
<style>

.StripeElement {
  padding: 10px 12px;
  border-radius: 4px;
  border: 1px solid transparent;
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
                <div class="panel-heading">Subscribe</div>

                <div class="panel-body">

                  @if($stripeError != null)
                    <div class="form-group alert alert-danger">
                      {{$stripeError}}
                    </div>
                  @endif

                  @include("partials.subscribe.plans")
                  <div>&nbsp;</div>

                  <div class="row">
                    <h3 class="col-lg-12">3. Enter Your Payment Information</h3>
                  </div>
                  @include("partials.subscribe.paymentform")

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
    lineHeight: '1.35',
    fontSize: '1.11rem',
    color: '#495057',
    fontFamily: 'apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif'
  },
  invalid: {
    color: '#fa755a',
    iconColor: '#fa755a'
  }
};

var card;
var stripe;
var elements;
var errors;
var paymentButton = document.getElementById('submitPaymentButton');

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
  // card = elements.create('card', {style: style});
  // card.mount('#card-element');
  //
  // // Card number
   card = elements.create('cardNumber', {
       'placeholder': '',
       'style': style
   });
   card.mount('#card-number');

   // CVC
   var cvc = elements.create('cardCvc', {
       'placeholder': '',
       'style': style
   });
   cvc.mount('#card-cvc');

   // Card expiry
   var exp = elements.create('cardExpiry', {
       'placeholder': '',
       'style': style
   });
   exp.mount('#card-exp');


});

function handlePaymentSubmit() {

  paymentButton.disabled = true;

  var name = $("#name-on-card").val();
  if(name == "") {
    displayCardError("You must provide the name on the card");
    return;
  }

  var zip = $("#billing-zip").val();
  if(zip == "") {
    displayCardError("You must provide the billing postal code for the card");
    return;
  }

  paymentButton.disabled = true;

  // Get the token from Stripe
  var p = stripe.createToken(card);
  p.then(handleStripeResponse);
}

function handleStripeResponse(result) {
    if (result.error) {
      // Inform the customer that there was an error

      displayCardError(result.error.message);
      paymentButton.disabled = false;
    } else {
      // Send the token to the server
      stripeTokenHandler(result.token);
    }
}

function displayCardError(message) {
  var errors = $('#card-errors');
  errors.html(message);
  errors.css("visibility", "visible");
  errorsContainer = $("#card-errors-container");
  errorsContainer.css("height", "70");
}

function stripeTokenHandler(token) {

  var subscribeForm = document.getElementById('choose-subscription-form');
  var stripeToken = document.getElementById('stripeToken');

  // Insert the value of the token in a hidden form field
  stripeToken.value = token.id;
  // Submit the form
  subscribeForm.submit();
}

// Allow the user to submit payment only after choosing a subscription type
function choseSubscription(input) {
  if(input.id == "basic") {
    $("#basic-panel").addClass("panel-primary");
    $("#plus-panel").removeClass("panel-primary");
    $("#plus-panel").addClass("panel-default");
    $("#basic-check").css("visibility", "visible");
    $("#plus-check").css("visibility", "hidden");
  }
  else {
    $("#plus-panel").addClass("panel-primary");
    $("#basic-panel").removeClass("panel-primary");
    $("#basic-panel").addClass("panel-default");
    $("#plus-check").css("visibility", "visible");
    $("#basic-check").css("visibility", "hidden");

  }

  paymentButton.disabled = false;
}

</script>

@stop
