<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        Schema::defaultStringLength(100);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
      // Register EtsyAPI as a singleton. Honestly, this may not
      // be necessary. It may not need to be instantiated at all.
      // Perhaps all methods should be made static in a future
      // refactor.
      \App::singleton("\App\Etsy\EtsyAPI", function() {

        $key = env("ETSY_API_KEY");
        $secret = env("ETSY_API_SECRET");
        return new \App\Etsy\EtsyAPI($key, $secret);
      });
  }
}
