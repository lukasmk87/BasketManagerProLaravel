<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Services\OpenApi\OpenApiDocumentationService;
// use Symfony\Component\Yaml\Yaml;

class GenerateOpenApiDocsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'api:generate-docs 
                          {--api-version=4.0 : API version to generate documentation for}
                          {--format=json : Output format (json, yaml, both)}
                          {--output=storage/api-docs/ : Output directory}';

    /**
     * The console command description.
     */
    protected $description = 'Generate OpenAPI 3.0 documentation from Laravel routes and models';

    private OpenApiDocumentationService $documentationService;

    /**
     * Create a new command instance.
     */
    public function __construct(OpenApiDocumentationService $documentationService)
    {
        parent::__construct();
        $this->documentationService = $documentationService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $version = $this->option('api-version');
        $format = $this->option('format');
        $outputPath = $this->option('output');

        $this->info("🚀 Generating OpenAPI {$version} documentation...");

        try {
            // Generate documentation
            $documentation = $this->documentationService->generateDocumentation($version);
            
            // Ensure output directory exists
            if (!File::exists($outputPath)) {
                File::makeDirectory($outputPath, 0755, true);
                $this->info("✅ Created output directory: {$outputPath}");
            }

            // Save in requested format(s)
            $this->saveDocumentation($documentation, $outputPath, $format);

            // Generate SDK stubs
            $this->generateSDKStubs($documentation, $outputPath);

            // Update configuration
            $this->updateApiConfig($version, $outputPath);

            $this->newLine();
            $this->info("🎉 OpenAPI documentation generated successfully!");
            $this->info("📁 Output directory: {$outputPath}");
            $this->info("🌐 View documentation at: " . config('app.url') . "/api/documentation");
            
            // Display statistics
            $this->displayStatistics($documentation);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Failed to generate documentation: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            
            return self::FAILURE;
        }
    }

    /**
     * Save documentation in specified format(s)
     */
    private function saveDocumentation(array $documentation, string $outputPath, string $format): void
    {
        switch ($format) {
            case 'json':
                $this->saveJson($documentation, $outputPath);
                break;
            case 'yaml':
                $this->saveYaml($documentation, $outputPath);
                break;
            case 'both':
                $this->saveJson($documentation, $outputPath);
                $this->saveYaml($documentation, $outputPath);
                break;
            default:
                throw new \InvalidArgumentException("Invalid format: {$format}. Use: json, yaml, or both");
        }
    }

    /**
     * Save documentation as JSON
     */
    private function saveJson(array $documentation, string $outputPath): void
    {
        $jsonPath = $outputPath . 'openapi.json';
        $prettyJson = json_encode($documentation, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        File::put($jsonPath, $prettyJson);
        $this->info("💾 JSON documentation saved: {$jsonPath}");
    }

    /**
     * Save documentation as YAML
     */
    private function saveYaml(array $documentation, string $outputPath): void
    {
        $yamlPath = $outputPath . 'openapi.yaml';
        $yamlContent = $this->arrayToYaml($documentation);
        
        File::put($yamlPath, $yamlContent);
        $this->info("💾 YAML documentation saved: {$yamlPath}");
    }

    /**
     * Convert array to YAML format
     */
    private function arrayToYaml(array $array, int $indent = 0): string
    {
        $yaml = '';
        $indentStr = str_repeat('  ', $indent);
        
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (empty($value)) {
                    $yaml .= $indentStr . $key . ": []\n";
                } elseif (array_keys($value) === range(0, count($value) - 1)) {
                    // Indexed array
                    $yaml .= $indentStr . $key . ":\n";
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            $yaml .= $indentStr . "  -\n";
                            $yaml .= $this->arrayToYaml($item, $indent + 2);
                        } else {
                            $yaml .= $indentStr . '  - ' . $this->formatYamlValue($item) . "\n";
                        }
                    }
                } else {
                    // Associative array
                    $yaml .= $indentStr . $key . ":\n";
                    $yaml .= $this->arrayToYaml($value, $indent + 1);
                }
            } else {
                $yaml .= $indentStr . $key . ': ' . $this->formatYamlValue($value) . "\n";
            }
        }
        
        return $yaml;
    }

    /**
     * Format YAML value
     */
    private function formatYamlValue($value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_null($value)) {
            return 'null';
        }
        if (is_numeric($value)) {
            return (string) $value;
        }
        if (is_string($value) && (strpos($value, "\n") !== false || strpos($value, '"') !== false)) {
            return "|\n    " . str_replace("\n", "\n    ", $value);
        }
        
        return '"' . str_replace('"', '\"', $value) . '"';
    }

    /**
     * Generate SDK stubs for different languages
     */
    private function generateSDKStubs(array $documentation, string $outputPath): void
    {
        $this->info("🔧 Generating SDK stubs...");
        
        $languages = ['php', 'javascript', 'python'];
        
        foreach ($languages as $language) {
            try {
                $generator = app("App\\Services\\OpenApi\\SDK\\{$language}SDKGenerator");
                $stub = $generator->generate($documentation);
                
                $stubPath = $outputPath . "sdk-{$language}.stub";
                File::put($stubPath, $stub);
                
                $this->info("📦 {$language} SDK stub generated: {$stubPath}");
            } catch (\Exception $e) {
                $this->warn("⚠️  Failed to generate {$language} SDK stub: " . $e->getMessage());
            }
        }
    }

    /**
     * Update API configuration
     */
    private function updateApiConfig(string $version, string $outputPath): void
    {
        $configPath = config_path('api.php');
        
        if (!File::exists($configPath)) {
            $config = [
                'default_version' => $version,
                'versions' => [
                    $version => [
                        'openapi_spec' => $outputPath . 'openapi.json',
                        'documentation_url' => '/api/documentation',
                        'enabled' => true
                    ]
                ],
                'rate_limiting' => [
                    'default' => 60,
                    'authenticated' => 1000
                ],
                'cors' => [
                    'enabled' => true,
                    'origins' => ['*']
                ]
            ];
            
            File::put($configPath, "<?php\n\nreturn " . var_export($config, true) . ";\n");
            $this->info("📝 Created API configuration: {$configPath}");
        } else {
            $this->info("📋 API configuration exists: {$configPath}");
        }
    }

    /**
     * Display generation statistics
     */
    private function displayStatistics(array $documentation): void
    {
        $pathCount = count($documentation['paths']);
        $schemaCount = count($documentation['components']['schemas']);
        $tagCount = count($documentation['tags']);
        
        $operationCount = 0;
        foreach ($documentation['paths'] as $path => $operations) {
            $operationCount += count(array_filter($operations, function($key) {
                return in_array($key, ['get', 'post', 'put', 'patch', 'delete', 'options']);
            }, ARRAY_FILTER_USE_KEY));
        }

        $this->newLine();
        $this->info("📊 Documentation Statistics:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['API Paths', $pathCount],
                ['Operations', $operationCount],
                ['Schemas', $schemaCount],
                ['Tags', $tagCount],
                ['API Version', $documentation['info']['version']]
            ]
        );

        // Show endpoint breakdown by tag
        $this->showEndpointBreakdown($documentation);
    }

    /**
     * Show endpoint breakdown by tag
     */
    private function showEndpointBreakdown(array $documentation): void
    {
        $tagStats = [];
        
        foreach ($documentation['paths'] as $path => $operations) {
            foreach ($operations as $method => $operation) {
                if (in_array($method, ['get', 'post', 'put', 'patch', 'delete', 'options']) && isset($operation['tags'][0])) {
                    $tag = $operation['tags'][0];
                    $tagStats[$tag] = ($tagStats[$tag] ?? 0) + 1;
                }
            }
        }

        if (!empty($tagStats)) {
            $this->newLine();
            $this->info("🏷️  Endpoints by Category:");
            
            $tableData = [];
            foreach ($tagStats as $tag => $count) {
                $tableData[] = [$tag, $count];
            }
            
            $this->table(['Category', 'Endpoints'], $tableData);
        }
    }
}