<?php

namespace App\Http\Middleware;

use Closure;


class TestIfNotSubscribed
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
        // If the user is subscribed to any of them, redirect

        return redirect("/home");
      }

      return $next($request);
    }
}
