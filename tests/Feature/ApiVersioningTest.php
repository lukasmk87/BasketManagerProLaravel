<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiVersioningTest extends TestCase
{
    /**
     * Test default API version resolution
     */
    public function test_default_version_resolution(): void
    {
        $response = $this->get('/api/');

        $response->assertStatus(200)
                ->assertJson([
                    'current_version' => '4.0',
                    'default_version' => '4.0',
                ])
                ->assertJsonStructure([
                    'api_name',
                    'current_version',
                    'default_version',
                    'supported_versions',
                    'documentation',
                    'features',
                    'rate_limits',
                    'migration_guides',
                    'status',
                    'timestamp'
                ]);
    }

    /**
     * Test Accept header version resolution
     */
    public function test_accept_header_version_resolution(): void
    {
        $response = $this->withHeaders([
            'Accept' => 'application/vnd.basketmanager.v1+json'
        ])->get('/api/');

        $response->assertStatus(200)
                ->assertJson([
                    'current_version' => '1',
                    'default_version' => '4.0',
                ]);
    }

    /**
     * Test API-Version header resolution
     */
    public function test_api_version_header_resolution(): void
    {
        $response = $this->withHeaders([
            'API-Version' => '3.0'
        ])->get('/api/');

        $response->assertStatus(200)
                ->assertJson([
                    'current_version' => '3.0',
                    'default_version' => '4.0',
                ]);
    }

    /**
     * Test X-API-Version header resolution
     */
    public function test_x_api_version_header_resolution(): void
    {
        $response = $this->withHeaders([
            'X-API-Version' => '1.0'
        ])->get('/api/');

        $response->assertStatus(200)
                ->assertJson([
                    'current_version' => '1.0',
                    'default_version' => '4.0',
                ]);
    }

    /**
     * Test query parameter version resolution
     */
    public function test_query_parameter_version_resolution(): void
    {
        $response = $this->get('/api/?api_version=3.0');

        $response->assertStatus(200)
                ->assertJson([
                    'current_version' => '3.0',
                    'default_version' => '4.0',
                ]);
    }

    /**
     * Test unsupported version returns error
     */
    public function test_unsupported_version_returns_error(): void
    {
        $response = $this->withHeaders([
            'API-Version' => '5.0'
        ])->get('/api/health');

        $response->assertStatus(400)
                ->assertJson([
                    'error' => 'Unsupported API version',
                    'message' => "API version '5.0' is not supported",
                ])
                ->assertJsonStructure([
                    'error',
                    'message',
                    'supported_versions',
                    'current_version'
                ]);
    }

    /**
     * Test version response headers
     */
    public function test_version_response_headers(): void
    {
        $response = $this->withHeaders([
            'API-Version' => '1.0'
        ])->get('/api/health');

        $response->assertStatus(200)
                ->assertHeader('X-API-Version', '1.0')
                ->assertHeader('X-Supported-Versions');

        // Check that X-Supported-Versions contains expected versions
        $supportedVersions = $response->headers->get('X-Supported-Versions');
        $this->assertStringContainsString('1.0', $supportedVersions);
        $this->assertStringContainsString('4.0', $supportedVersions);
    }

    /**
     * Test health endpoint works across versions
     */
    public function test_health_endpoint_works_across_versions(): void
    {
        // Test default version
        $response = $this->get('/api/health');
        $response->assertStatus(200)
                ->assertJson(['status' => 'healthy']);

        // Test v1.0
        $response = $this->withHeaders(['API-Version' => '1.0'])
                        ->get('/api/health');
        $response->assertStatus(200)
                ->assertJson(['status' => 'healthy'])
                ->assertHeader('X-API-Version', '1.0');

        // Test v4.0
        $response = $this->withHeaders(['API-Version' => '4.0'])
                        ->get('/api/health');
        $response->assertStatus(200)
                ->assertJson(['status' => 'healthy'])
                ->assertHeader('X-API-Version', '4.0');
    }

    /**
     * Test API status endpoint
     */
    public function test_api_status_endpoint(): void
    {
        $response = $this->get('/api/status');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'api',
                    'status',
                    'version',
                    'uptime',
                    'rate_limits',
                    'maintenance' => [
                        'scheduled',
                        'message'
                    ]
                ])
                ->assertJson([
                    'api' => 'BasketManager Pro API',
                    'status' => 'operational',
                    'version' => '4.0'
                ]);
    }

    /**
     * Test disabled version (v2.0) returns error
     */
    public function test_disabled_version_returns_error(): void
    {
        $response = $this->withHeaders([
            'API-Version' => '2.0'
        ])->get('/api/health');

        $response->assertStatus(400)
                ->assertJson([
                    'error' => 'Unsupported API version',
                    'message' => "API version '2.0' is not supported",
                ]);
    }

    /**
     * Test version precedence (Accept header over query param)
     */
    public function test_version_precedence(): void
    {
        $response = $this->withHeaders([
            'Accept' => 'application/vnd.basketmanager.v3+json'
        ])->get('/api/?api_version=1.0');

        // Accept header should take precedence
        $response->assertStatus(200)
                ->assertJson([
                    'current_version' => '3',
                ]);
    }
}
