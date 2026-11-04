<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_root_url_shows_the_public_home(): void
    {
        $response = $this->get('/');

        $response->assertOk()->assertViewIs('public.home');
    }
}
