<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /** @test */
    public function the_application_returns_a_successful_response(): void
    {
        $response = $this->followingRedirects()->get('/');

        $response->assertStatus(200);
    }
}
