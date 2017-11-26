<?php

namespace App\Http\Middleware;

use Closure;
use App\SubscriptionOptions;

class TestIfSubscribed
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
      if($request->user()->subscribed("basic-access")) {
        return $next($request);
      }

      // If the user is not subscribed to any of them, redirect
      return redirect("/subscribe");
    }
}
