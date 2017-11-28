<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
// use App\ImagePaths;
// use App\RPL\TextImageV3;
// use App\RPL\CompositeImageV2;
// use App\RPL\Color;
// use App\UserLatestJobTime;
// use Carbon\Carbon;
// use App\AppSecrets;

class TestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $basePath;

    public function __construct()
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {


      $this->basePath = getcwd()."/";
      dump($this->basePath);

      dblog($this->basePath, "test job output");

    }


}
