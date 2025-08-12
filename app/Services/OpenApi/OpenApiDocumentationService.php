<?php

namespace App\Services\OpenApi;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use App\Models\Team;
use App\Models\Player;
use App\Models\Game;
use App\Models\Club;
use App\Models\User;

class OpenApiDocumentationService
{
    private array $openApiSpec;
    private array $processedModels = [];

    public function __construct()
    {
        $this->openApiSpec = $this->initializeBaseSpec();
    }

    /**
     * Generate complete OpenAPI 3.0 documentation
     */
    public function generateDocumentation(string $version = '4.0'): array
    {
        $this->openApiSpec['info']['version'] = $version;
        
        // Process all routes and generate paths
        $this->processRoutes();
        
        // Generate schemas from models
        $this->generateSchemas();
        
        // Add security schemes
        $this->addSecuritySchemes();
        
        // Add response templates
        $this->addResponseTemplates();
        
        return $this->openApiSpec;
    }

    /**
     * Initialize base OpenAPI specification
     */
    private function initializeBaseSpec(): array
    {
        return [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'BasketManager Pro API',
                'description' => 'Enterprise Basketball Club Management API with multi-tenant architecture, real-time scoring, and comprehensive statistics.',
                'version' => '4.0.0',
                'termsOfService' => config('app.url') . '/terms',
                'contact' => [
                    'name' => 'BasketManager Pro Support',
                    'url' => config('app.url') . '/support',
                    'email' => 'support@basketmanager-pro.com'
                ],
                'license' => [
                    'name' => 'MIT',
                    'url' => 'https://opensource.org/licenses/MIT'
                ]
            ],
            'servers' => [
                [
                    'url' => config('app.url') . '/api',
                    'description' => 'Production API'
                ],
                [
                    'url' => 'https://staging-api.basketmanager-pro.com/api',
                    'description' => 'Staging API'
                ],
                [
                    'url' => 'http://localhost:8000/api',
                    'description' => 'Development API'
                ]
            ],
            'paths' => [],
            'components' => [
                'schemas' => [],
                'securitySchemes' => [],
                'responses' => [],
                'parameters' => [],
                'examples' => [],
                'requestBodies' => [],
                'headers' => [],
                'callbacks' => [],
                'links' => []
            ],
            'tags' => [
                ['name' => 'Authentication', 'description' => 'User authentication and token management'],
                ['name' => 'Teams', 'description' => 'Team management operations'],
                ['name' => 'Players', 'description' => 'Player management and statistics'],
                ['name' => 'Games', 'description' => 'Game management and live scoring'],
                ['name' => 'Tournaments', 'description' => 'Tournament and bracket management'],
                ['name' => 'Training', 'description' => 'Training sessions and drill management'],
                ['name' => 'Analytics', 'description' => 'ML-powered analytics and predictions'],
                ['name' => 'Videos', 'description' => 'Video analysis and annotations'],
                ['name' => 'Multi-tenant', 'description' => 'Tenant management and subscription'],
                ['name' => 'Webhooks', 'description' => 'Real-time event notifications'],
                ['name' => 'External APIs', 'description' => 'Federation and third-party integrations']
            ]
        ];
    }

    /**
     * Process all application routes and generate OpenAPI paths
     */
    private function processRoutes(): void
    {
        $routes = Route::getRoutes();
        
        foreach ($routes as $route) {
            // Skip non-API routes
            if (!Str::startsWith($route->uri(), 'api/')) {
                continue;
            }

            // Skip routes without names or controllers
            if (!$route->getAction('controller')) {
                continue;
            }

            $this->processRoute($route);
        }
    }

    /**
     * Process individual route and add to OpenAPI spec
     */
    private function processRoute($route): void
    {
        $uri = '/' . ltrim($route->uri(), '/');
        $methods = $route->methods();
        
        // Convert Laravel route parameters to OpenAPI format
        $openApiPath = $this->convertRouteParameters($uri);
        
        if (!isset($this->openApiSpec['paths'][$openApiPath])) {
            $this->openApiSpec['paths'][$openApiPath] = [];
        }

        foreach ($methods as $method) {
            if (in_array(strtolower($method), ['get', 'post', 'put', 'patch', 'delete', 'options', 'head'])) {
                $this->openApiSpec['paths'][$openApiPath][strtolower($method)] = 
                    $this->generateOperationSpec($route, $method);
            }
        }
    }

    /**
     * Convert Laravel route parameters to OpenAPI format
     */
    private function convertRouteParameters(string $uri): string
    {
        return preg_replace('/\{([^}]+)\}/', '{$1}', $uri);
    }

    /**
     * Generate operation specification for a route
     */
    private function generateOperationSpec($route, string $method): array
    {
        $controllerAction = $route->getAction('controller');
        [$controllerClass, $methodName] = explode('@', $controllerAction);
        
        $operation = [
            'summary' => $this->generateSummary($controllerClass, $methodName),
            'description' => $this->generateDescription($controllerClass, $methodName),
            'operationId' => $this->generateOperationId($controllerClass, $methodName, $method),
            'tags' => [$this->getTagFromController($controllerClass)],
            'parameters' => $this->extractParameters($route, $controllerClass, $methodName),
            'responses' => $this->generateResponses($controllerClass, $methodName)
        ];

        // Add request body for POST/PUT/PATCH methods
        if (in_array(strtolower($method), ['post', 'put', 'patch'])) {
            $requestBody = $this->generateRequestBody($controllerClass, $methodName);
            if ($requestBody) {
                $operation['requestBody'] = $requestBody;
            }
        }

        // Add security requirement
        $operation['security'] = $this->getSecurityRequirement($route);

        return $operation;
    }

    /**
     * Generate operation summary from controller and method
     */
    private function generateSummary(string $controllerClass, string $methodName): string
    {
        $resource = $this->getResourceFromController($controllerClass);
        
        $summaries = [
            'index' => "List all {$resource}",
            'show' => "Get specific {$resource}",
            'store' => "Create new {$resource}",
            'update' => "Update {$resource}",
            'destroy' => "Delete {$resource}",
        ];

        return $summaries[$methodName] ?? ucfirst($methodName) . ' ' . $resource;
    }

    /**
     * Generate detailed description for operation
     */
    private function generateDescription(string $controllerClass, string $methodName): string
    {
        $resource = $this->getResourceFromController($controllerClass);
        
        $descriptions = [
            'index' => "Retrieve a paginated list of {$resource} with filtering and sorting options.",
            'show' => "Get detailed information about a specific {$resource} including related data.",
            'store' => "Create a new {$resource} with validation and automatic relationship setup.",
            'update' => "Update an existing {$resource} with partial or complete data replacement.",
            'destroy' => "Permanently delete a {$resource} and handle cascading relationships.",
        ];

        return $descriptions[$methodName] ?? "Perform {$methodName} operation on {$resource}";
    }

    /**
     * Generate unique operation ID
     */
    private function generateOperationId(string $controllerClass, string $methodName, string $httpMethod): string
    {
        $controller = class_basename($controllerClass);
        $version = $this->getVersionFromController($controllerClass);
        
        return strtolower($httpMethod) . 
               ($version ? $version . '_' : '') . 
               Str::snake(str_replace('Controller', '', $controller)) . '_' . 
               $methodName;
    }

    /**
     * Get tag name from controller
     */
    private function getTagFromController(string $controllerClass): string
    {
        if (Str::contains($controllerClass, 'Auth')) return 'Authentication';
        if (Str::contains($controllerClass, 'Team')) return 'Teams';
        if (Str::contains($controllerClass, 'Player')) return 'Players';
        if (Str::contains($controllerClass, 'Game')) return 'Games';
        if (Str::contains($controllerClass, 'Tournament')) return 'Tournaments';
        if (Str::contains($controllerClass, 'Training') || Str::contains($controllerClass, 'Drill')) return 'Training';
        if (Str::contains($controllerClass, 'Video')) return 'Videos';
        if (Str::contains($controllerClass, 'ML')) return 'Analytics';
        if (Str::contains($controllerClass, 'Webhook')) return 'Webhooks';
        if (Str::contains($controllerClass, 'Tenant')) return 'Multi-tenant';
        
        return 'General';
    }

    /**
     * Get resource name from controller
     */
    private function getResourceFromController(string $controllerClass): string
    {
        $controller = class_basename($controllerClass);
        return Str::lower(str_replace('Controller', '', $controller));
    }

    /**
     * Get API version from controller namespace
     */
    private function getVersionFromController(string $controllerClass): ?string
    {
        if (Str::contains($controllerClass, 'V2')) return 'v2';
        if (Str::contains($controllerClass, 'V3')) return 'v3';
        if (Str::contains($controllerClass, 'V4')) return 'v4';
        
        return null;
    }

    /**
     * Extract parameters from route
     */
    private function extractParameters($route, string $controllerClass, string $methodName): array
    {
        $parameters = [];
        
        // Add path parameters
        foreach ($route->parameterNames() as $paramName) {
            $parameters[] = [
                'name' => $paramName,
                'in' => 'path',
                'required' => true,
                'description' => "ID of the {$paramName}",
                'schema' => [
                    'type' => 'integer',
                    'format' => 'int64',
                    'minimum' => 1
                ],
                'example' => 1
            ];
        }

        // Add query parameters for index methods
        if ($methodName === 'index') {
            $parameters = array_merge($parameters, [
                [
                    'name' => 'page',
                    'in' => 'query',
                    'required' => false,
                    'description' => 'Page number for pagination',
                    'schema' => ['type' => 'integer', 'minimum' => 1],
                    'example' => 1
                ],
                [
                    'name' => 'per_page',
                    'in' => 'query',
                    'required' => false,
                    'description' => 'Number of items per page',
                    'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
                    'example' => 15
                ],
                [
                    'name' => 'search',
                    'in' => 'query',
                    'required' => false,
                    'description' => 'Search term for filtering results',
                    'schema' => ['type' => 'string'],
                    'example' => 'Lakers'
                ]
            ]);
        }

        return $parameters;
    }

    /**
     * Generate responses specification
     */
    private function generateResponses(string $controllerClass, string $methodName): array
    {
        $resource = $this->getResourceFromController($controllerClass);
        
        $responses = [
            '200' => [
                'description' => 'Successful operation',
                'content' => [
                    'application/json' => [
                        'schema' => $this->getResponseSchema($methodName, $resource)
                    ]
                ]
            ],
            '400' => ['$ref' => '#/components/responses/BadRequest'],
            '401' => ['$ref' => '#/components/responses/Unauthorized'],
            '403' => ['$ref' => '#/components/responses/Forbidden'],
            '404' => ['$ref' => '#/components/responses/NotFound'],
            '422' => ['$ref' => '#/components/responses/ValidationError'],
            '500' => ['$ref' => '#/components/responses/InternalServerError']
        ];

        // Customize based on method
        switch ($methodName) {
            case 'store':
                $responses['201'] = $responses['200'];
                $responses['201']['description'] = 'Resource created successfully';
                unset($responses['200']);
                break;
            case 'destroy':
                $responses['204'] = [
                    'description' => 'Resource deleted successfully'
                ];
                unset($responses['200']);
                break;
        }

        return $responses;
    }

    /**
     * Get response schema based on method and resource
     */
    private function getResponseSchema(string $methodName, string $resource): array
    {
        $resourceSchema = ['$ref' => "#/components/schemas/" . Str::studly($resource)];
        
        switch ($methodName) {
            case 'index':
                return [
                    'type' => 'object',
                    'properties' => [
                        'data' => [
                            'type' => 'array',
                            'items' => $resourceSchema
                        ],
                        'links' => ['$ref' => '#/components/schemas/PaginationLinks'],
                        'meta' => ['$ref' => '#/components/schemas/PaginationMeta']
                    ]
                ];
            case 'show':
            case 'store':
            case 'update':
                return [
                    'type' => 'object',
                    'properties' => [
                        'data' => $resourceSchema
                    ]
                ];
            default:
                return $resourceSchema;
        }
    }

    /**
     * Generate request body specification
     */
    private function generateRequestBody(string $controllerClass, string $methodName): ?array
    {
        if (!in_array($methodName, ['store', 'update'])) {
            return null;
        }

        $resource = $this->getResourceFromController($controllerClass);
        
        return [
            'required' => true,
            'content' => [
                'application/json' => [
                    'schema' => [
                        '$ref' => "#/components/schemas/" . Str::studly($resource) . "Request"
                    ]
                ]
            ]
        ];
    }

    /**
     * Get security requirements for route
     */
    private function getSecurityRequirement($route): array
    {
        $middlewares = $route->gatherMiddleware();
        
        if (in_array('auth:sanctum', $middlewares)) {
            return [['BearerAuth' => []]];
        }

        return [];
    }

    /**
     * Generate schemas from Eloquent models
     */
    private function generateSchemas(): void
    {
        $models = [
            Team::class,
            Player::class,
            Game::class,
            Club::class,
            User::class,
        ];

        foreach ($models as $modelClass) {
            $this->generateModelSchema($modelClass);
        }

        // Add common schemas
        $this->addCommonSchemas();
    }

    /**
     * Generate schema for Eloquent model
     */
    private function generateModelSchema(string $modelClass): void
    {
        if (in_array($modelClass, $this->processedModels)) {
            return;
        }

        $this->processedModels[] = $modelClass;
        
        $model = new $modelClass;
        $schemaName = class_basename($modelClass);
        
        $schema = [
            'type' => 'object',
            'properties' => [],
            'required' => []
        ];

        // Get fillable attributes
        $fillable = $model->getFillable();
        $casts = $model->getCasts();
        
        foreach ($fillable as $attribute) {
            $schema['properties'][$attribute] = $this->getPropertySchema($attribute, $casts[$attribute] ?? 'string');
        }

        // Add timestamps if model uses them
        if (method_exists($model, 'getCreatedAtColumn')) {
            $schema['properties']['created_at'] = [
                'type' => 'string',
                'format' => 'date-time',
                'description' => 'Creation timestamp',
                'example' => '2024-08-12T10:30:00Z'
            ];
            
            $schema['properties']['updated_at'] = [
                'type' => 'string',
                'format' => 'date-time',
                'description' => 'Last update timestamp',
                'example' => '2024-08-12T15:45:00Z'
            ];
        }

        // Add ID property
        $schema['properties']['id'] = [
            'type' => 'integer',
            'format' => 'int64',
            'description' => 'Unique identifier',
            'example' => 1
        ];

        $this->openApiSpec['components']['schemas'][$schemaName] = $schema;
        
        // Generate request schema
        $this->generateRequestSchema($schemaName, $schema);
    }

    /**
     * Get property schema based on cast type
     */
    private function getPropertySchema(string $attribute, string $castType): array
    {
        switch ($castType) {
            case 'integer':
            case 'int':
                return ['type' => 'integer', 'example' => 1];
            case 'float':
            case 'double':
            case 'real':
                return ['type' => 'number', 'format' => 'float', 'example' => 1.0];
            case 'boolean':
            case 'bool':
                return ['type' => 'boolean', 'example' => true];
            case 'array':
            case 'json':
                return ['type' => 'object', 'example' => []];
            case 'date':
                return ['type' => 'string', 'format' => 'date', 'example' => '2024-08-12'];
            case 'datetime':
            case 'timestamp':
                return ['type' => 'string', 'format' => 'date-time', 'example' => '2024-08-12T10:30:00Z'];
            default:
                return ['type' => 'string', 'example' => $this->getExampleValue($attribute)];
        }
    }

    /**
     * Get example value for attribute
     */
    private function getExampleValue(string $attribute): string
    {
        $examples = [
            'name' => 'Los Angeles Lakers',
            'email' => 'user@example.com',
            'phone' => '+1-555-123-4567',
            'address' => '123 Basketball Ave',
            'city' => 'Los Angeles',
            'description' => 'Professional basketball team',
            'title' => 'Season Championship',
        ];

        return $examples[$attribute] ?? 'Example value';
    }

    /**
     * Generate request schema for create/update operations
     */
    private function generateRequestSchema(string $schemaName, array $baseSchema): void
    {
        $requestSchema = $baseSchema;
        
        // Remove read-only fields
        unset($requestSchema['properties']['id']);
        unset($requestSchema['properties']['created_at']);
        unset($requestSchema['properties']['updated_at']);
        
        $this->openApiSpec['components']['schemas'][$schemaName . 'Request'] = $requestSchema;
    }

    /**
     * Add common schemas
     */
    private function addCommonSchemas(): void
    {
        $this->openApiSpec['components']['schemas']['PaginationLinks'] = [
            'type' => 'object',
            'properties' => [
                'first' => ['type' => 'string', 'nullable' => true],
                'last' => ['type' => 'string', 'nullable' => true],
                'prev' => ['type' => 'string', 'nullable' => true],
                'next' => ['type' => 'string', 'nullable' => true]
            ]
        ];

        $this->openApiSpec['components']['schemas']['PaginationMeta'] = [
            'type' => 'object',
            'properties' => [
                'current_page' => ['type' => 'integer'],
                'from' => ['type' => 'integer', 'nullable' => true],
                'last_page' => ['type' => 'integer'],
                'per_page' => ['type' => 'integer'],
                'to' => ['type' => 'integer', 'nullable' => true],
                'total' => ['type' => 'integer']
            ]
        ];

        $this->openApiSpec['components']['schemas']['Error'] = [
            'type' => 'object',
            'properties' => [
                'message' => ['type' => 'string'],
                'errors' => ['type' => 'object']
            ]
        ];
    }

    /**
     * Add security schemes
     */
    private function addSecuritySchemes(): void
    {
        $this->openApiSpec['components']['securitySchemes'] = [
            'BearerAuth' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'JWT',
                'description' => 'Laravel Sanctum Bearer Token'
            ],
            'ApiKeyAuth' => [
                'type' => 'apiKey',
                'in' => 'header',
                'name' => 'X-API-Key',
                'description' => 'API Key for external integrations'
            ]
        ];
    }

    /**
     * Add response templates
     */
    private function addResponseTemplates(): void
    {
        $this->openApiSpec['components']['responses'] = [
            'BadRequest' => [
                'description' => 'Bad Request',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error']
                    ]
                ]
            ],
            'Unauthorized' => [
                'description' => 'Unauthorized',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'message' => ['type' => 'string', 'example' => 'Unauthenticated']
                            ]
                        ]
                    ]
                ]
            ],
            'Forbidden' => [
                'description' => 'Forbidden',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'message' => ['type' => 'string', 'example' => 'This action is unauthorized']
                            ]
                        ]
                    ]
                ]
            ],
            'NotFound' => [
                'description' => 'Resource not found',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'message' => ['type' => 'string', 'example' => 'Resource not found']
                            ]
                        ]
                    ]
                ]
            ],
            'ValidationError' => [
                'description' => 'Validation Error',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'message' => ['type' => 'string'],
                                'errors' => ['type' => 'object']
                            ]
                        ]
                    ]
                ]
            ],
            'InternalServerError' => [
                'description' => 'Internal Server Error',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'message' => ['type' => 'string', 'example' => 'Server Error']
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}