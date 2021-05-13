<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\AppSecrets;
class SubscribeController extends Controller
{

    //
    public function __construct()
    {
        /*$this->middleware('auth');
        $this->middleware('not.subscribed')->only(["subscribe", "showPaymentForm"]);
        $this->middleware('subscribed')->except(["subscribe", "showPaymentForm"]);
        */
    }

    public function showPaymentForm() {
      $stripeKey = AppSecrets::get("STRIPE_KEY");
      $couponError = false;
      $stripeErrorMessage = null;
      if(session()->has("stripeError")) {
        $message = session()->get("stripeError");
        if(strpos($message, "coupon") !== false) {
          $couponError = true;
        }
        else {
          $stripeErrorMessage = $message;
        }
      }
      return view("subscribe", ["couponError" => $couponError, "stripeError" => $stripeErrorMessage, "stripeKey" => $stripeKey]);
    }

    public function showChangePlanForm() {
      $stripeKey = AppSecrets::get("STRIPE_KEY");
      $planName = "Basic";
      $otherPlanName = "Plus";
      $otherPlanId = "phrasinator-plus-monthly";
      $otherPlanPrice = "$19.99/month";
      if(auth()->user()->onPlusPlan()) {
        $planName = "Plus";
        $otherPlanName = "Basic";
        $otherPlanId = "phrasinator-basic-monthly";
        $otherPlanPrice = "$9.99/month";
      }
      return view("changePlan", ["planName" => $planName, "otherPlanName" => $otherPlanName, "otherPlanId" => $otherPlanId, "otherPlanPrice" => $otherPlanPrice, "planFormAction" => "/account/change", "stripeKey" => $stripeKey]);
    }

    public function subscribe(Request $request) {
      $subscriptionType = $request->subscriptionType;
      $stripeToken = $request->stripeToken;
      $user = auth()->user();
      $subscriptionRequest = $user->newSubscription("basic-access", $subscriptionType)->trialDays(7);
      if($request->coupon != "" && $request->coupon != null) {
        $subscriptionRequest = $subscriptionRequest->withCoupon($request->coupon);
      }
      try {
        $response = $subscriptionRequest->create($stripeToken, ["email" => $user->email]);
      }
      catch(\Exception $ir) {
        return redirect()->back()->with("stripeError", $ir->getMessage());
      }
      return redirect("/account/welcome");
    }

    public function changePlan(Request $request) {
      $subscription = $request->user()->getCurrentSubscription();
      $subscription->swap($request->planName);
      return redirect("/account")->with("message", "You have successfully changed plans. Your next bill will reflect the change.");
    }

    public function cancel(Request $request) {
      $response = $request->user()->getCurrentSubscription()->cancel();
      return view("account.cancelled", ["endsAt" => Carbon::parse($response->ends_at)]);
    }

    public function resume(Request $request) {
      $subscription = $request->user()->getCurrentSubscription();
      if(isset($subscription)) {
        $response = $subscription->resume();
        return redirect("/account");
      }
    }
}
