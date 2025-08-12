<?php

namespace App\Http\Controllers\Api\V4;

use App\Http\Controllers\Controller;
use App\Services\OpenApi\OpenApiDocumentationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

class DocumentationController extends Controller
{
    private OpenApiDocumentationService $documentationService;

    public function __construct(OpenApiDocumentationService $documentationService)
    {
        $this->documentationService = $documentationService;
    }

    /**
     * Display interactive API documentation (Swagger UI)
     */
    public function index(): Response
    {
        $swaggerUiHtml = $this->generateSwaggerUI();
        
        return response($swaggerUiHtml)
            ->header('Content-Type', 'text/html')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Get OpenAPI specification as JSON
     */
    public function spec(Request $request): JsonResponse
    {
        $version = $request->get('version', '4.0');
        
        // Try to load from cache first
        $cacheKey = "openapi_spec_{$version}";
        
        $spec = Cache::remember($cacheKey, 3600, function () use ($version) {
            return $this->documentationService->generateDocumentation($version);
        });

        return response()->json($spec)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }

    /**
     * Download OpenAPI specification in various formats
     */
    public function download(Request $request): Response
    {
        $format = $request->get('format', 'json');
        $version = $request->get('version', '4.0');
        
        $spec = Cache::remember("openapi_spec_{$version}", 3600, function () use ($version) {
            return $this->documentationService->generateDocumentation($version);
        });

        switch ($format) {
            case 'yaml':
                $content = \Symfony\Component\Yaml\Yaml::dump($spec, 4, 2);
                $filename = "basketmanager-pro-api-{$version}.yaml";
                $contentType = 'application/x-yaml';
                break;
            case 'json':
            default:
                $content = json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                $filename = "basketmanager-pro-api-{$version}.json";
                $contentType = 'application/json';
                break;
        }

        return response($content)
            ->header('Content-Type', $contentType)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    /**
     * Get API statistics and health information
     */
    public function stats(): JsonResponse
    {
        $spec = Cache::remember('openapi_spec_4.0', 3600, function () {
            return $this->documentationService->generateDocumentation('4.0');
        });

        $pathCount = count($spec['paths']);
        $schemaCount = count($spec['components']['schemas']);
        $tagCount = count($spec['tags']);
        
        $operationCount = 0;
        $methodBreakdown = [];
        
        foreach ($spec['paths'] as $path => $operations) {
            foreach ($operations as $method => $operation) {
                if (in_array($method, ['get', 'post', 'put', 'patch', 'delete', 'options'])) {
                    $operationCount++;
                    $methodBreakdown[$method] = ($methodBreakdown[$method] ?? 0) + 1;
                }
            }
        }

        // Tag statistics
        $tagStats = [];
        foreach ($spec['paths'] as $path => $operations) {
            foreach ($operations as $method => $operation) {
                if (in_array($method, ['get', 'post', 'put', 'patch', 'delete', 'options']) && isset($operation['tags'][0])) {
                    $tag = $operation['tags'][0];
                    $tagStats[$tag] = ($tagStats[$tag] ?? 0) + 1;
                }
            }
        }

        return response()->json([
            'api_version' => $spec['info']['version'],
            'generated_at' => now()->toISOString(),
            'statistics' => [
                'total_paths' => $pathCount,
                'total_operations' => $operationCount,
                'total_schemas' => $schemaCount,
                'total_tags' => $tagCount,
                'method_breakdown' => $methodBreakdown,
                'tag_breakdown' => $tagStats
            ],
            'servers' => $spec['servers'],
            'security_schemes' => array_keys($spec['components']['securitySchemes'] ?? [])
        ]);
    }

    /**
     * Generate SDK for specified language
     */
    public function generateSDK(Request $request): Response
    {
        $language = $request->get('language', 'php');
        $version = $request->get('version', '4.0');
        
        $validLanguages = ['php', 'javascript', 'python'];
        
        if (!in_array($language, $validLanguages)) {
            abort(400, "Invalid language. Supported: " . implode(', ', $validLanguages));
        }

        try {
            $spec = Cache::remember("openapi_spec_{$version}", 3600, function () use ($version) {
                return $this->documentationService->generateDocumentation($version);
            });

            $generatorClass = "App\\Services\\OpenApi\\SDK\\{$language}SDKGenerator";
            
            if (!class_exists($generatorClass)) {
                abort(501, "SDK generator for {$language} not implemented yet");
            }

            $generator = app($generatorClass);
            $sdkCode = $generator->generate($spec);

            $filename = "basketmanager-pro-{$language}-sdk-{$version}." . $this->getFileExtension($language);
            $contentType = $this->getContentType($language);

            return response($sdkCode)
                ->header('Content-Type', $contentType)
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate');

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'SDK generation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test API endpoint for validating connectivity
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'version' => config('app.version', '4.0.0'),
            'environment' => app()->environment()
        ]);
    }

    /**
     * Generate Swagger UI HTML
     */
    private function generateSwaggerUI(): string
    {
        $specUrl = route('api.v4.documentation.spec');
        $title = config('app.name') . ' API Documentation';
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{$title}</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.0.0/swagger-ui.css" />
    <link rel="icon" type="image/png" href="https://unpkg.com/swagger-ui-dist@5.0.0/favicon-32x32.png" sizes="32x32" />
    <link rel="icon" type="image/png" href="https://unpkg.com/swagger-ui-dist@5.0.0/favicon-16x16.png" sizes="16x16" />
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }
        
        *, *:before, *:after {
            box-sizing: inherit;
        }

        body {
            margin: 0;
            background: #fafafa;
        }

        .swagger-ui .topbar {
            background-color: #1f2937;
        }

        .swagger-ui .topbar .download-url-wrapper input[type=text] {
            border-color: #3b82f6;
        }

        .swagger-ui .btn.authorize {
            background-color: #059669;
            border-color: #059669;
        }

        .swagger-ui .btn.authorize:hover {
            background-color: #047857;
            border-color: #047857;
        }

        .custom-header {
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
        }

        .custom-header h1 {
            margin: 0 0 10px 0;
            font-size: 2.5em;
            font-weight: 300;
        }

        .custom-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 1.1em;
        }

        .custom-links {
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            text-align: center;
        }

        .custom-links a {
            margin: 0 10px;
            padding: 8px 16px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9em;
        }

        .custom-links a:hover {
            background: #0056b3;
        }
    </style>
</head>

<body>
    <div class="custom-header">
        <h1>🏀 BasketManager Pro API</h1>
        <p>Enterprise Basketball Club Management Platform</p>
    </div>
    
    <div class="custom-links">
        <a href="{$specUrl}?format=json" target="_blank">📄 Download JSON</a>
        <a href="{$specUrl}?format=yaml" target="_blank">📄 Download YAML</a>
        <a href="/api/v4/documentation/stats" target="_blank">📊 API Statistics</a>
        <a href="/api/v4/documentation/sdk?language=php" target="_blank">📦 PHP SDK</a>
        <a href="/api/v4/documentation/sdk?language=javascript" target="_blank">📦 JavaScript SDK</a>
        <a href="/api/v4/documentation/sdk?language=python" target="_blank">📦 Python SDK</a>
    </div>

    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist@5.0.0/swagger-ui-bundle.js" charset="UTF-8"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.0.0/swagger-ui-standalone-preset.js" charset="UTF-8"></script>
    <script>
    window.onload = function() {
        const ui = SwaggerUIBundle({
            url: '{$specUrl}',
            dom_id: '#swagger-ui',
            deepLinking: true,
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset
            ],
            plugins: [
                SwaggerUIBundle.plugins.DownloadUrl
            ],
            layout: "StandaloneLayout",
            requestInterceptor: function(request) {
                // Add custom headers or modify requests here
                console.log('API Request:', request);
                return request;
            },
            responseInterceptor: function(response) {
                // Handle responses here
                console.log('API Response:', response);
                return response;
            },
            onComplete: function() {
                console.log('SwaggerUI loaded successfully');
            },
            tryItOutEnabled: true,
            filter: true,
            supportedSubmitMethods: ['get', 'post', 'put', 'patch', 'delete'],
            docExpansion: 'list',
            defaultModelsExpandDepth: 2,
            defaultModelExpandDepth: 2,
            displayOperationId: true,
            displayRequestDuration: true
        });

        window.ui = ui;
    };
    </script>
</body>
</html>
HTML;
    }

    /**
     * Get file extension for language
     */
    private function getFileExtension(string $language): string
    {
        return match($language) {
            'php' => 'php',
            'javascript' => 'js',
            'python' => 'py',
            default => 'txt'
        };
    }

    /**
     * Get content type for language
     */
    private function getContentType(string $language): string
    {
        return match($language) {
            'php' => 'text/x-php',
            'javascript' => 'application/javascript',
            'python' => 'text/x-python',
            default => 'text/plain'
        };
    }
}