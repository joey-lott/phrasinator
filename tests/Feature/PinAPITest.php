<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Pinterest\PinAPI;

class PinAPITest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_can_get_authorization_url()
    {
        $api = new PinAPI();
        $url = $api->getAuthorizationUrl();
        $this->assertTrue(strlen($url) > 0);
    }
}
