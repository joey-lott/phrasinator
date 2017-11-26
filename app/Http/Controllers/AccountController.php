<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class AccountController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function welcome() {
      $invoice = auth()->user()->upcomingInvoice();
      return view("account.welcome", ["total" => $invoice->total()]);
    }

    public function myAccount(Request $request) {
      $invoice = auth()->user()->upcomingInvoice();
      return view('account.index', ["onGracePeriod" => $request->user()->onGracePeriodDefaultSubscription(), "endsAt" => Carbon::parse($request->user()->getCurrentSubscription()->ends_at), "currentPlanName" => $request->user()->getCurrentPlanName(), "invoice" => $invoice]);
    }

    public function cancelForm() {
      return view('account.cancelForm');
    }
}
