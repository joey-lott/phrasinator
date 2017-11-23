<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Storage;
use League\Flysystem\Filesystem;
use Spatie\Dropbox\Client as DropboxClient;
use Spatie\FlysystemDropbox\DropboxAdapter;

class DBXServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
      Storage::extend('dropbox', function ($app, $config) {
                  $client = new DropboxClient(
                      env("DBX_API_TOKEN")
                  );

                  return new Filesystem(new DropboxAdapter($client));
              });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
