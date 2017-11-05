<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\GearBubble\Browser;
use Illuminate\Support\Facades\Storage;

class GearBubbleTest extends TestCase
{

    public function test_can_see_new_campaign_page()
    {
      $browser = new Browser();
      $status = $browser->getPage("https://www.gearbubble.com/campaigns/new");
      $this->assertEquals(200, $status);
    }


}
