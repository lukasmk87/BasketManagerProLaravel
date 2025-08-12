<?php

namespace App\Services\OpenApi\SDK;

use Illuminate\Support\Str;

class phpSDKGenerator implements SDKGeneratorInterface
{
    public function generate(array $openApiSpec): string
    {
        $apiTitle = $openApiSpec['info']['title'];
        $apiVersion = $openApiSpec['info']['version'];
        $apiDescription = $openApiSpec['info']['description'] ?? '';
        
        $code = $this->generateHeader($apiTitle, $apiVersion, $apiDescription);
        $code .= $this->generateClientClass($openApiSpec);
        $code .= $this->generateResourceClasses($openApiSpec);
        $code .= $this->generateModelClasses($openApiSpec);
        $code .= $this->generateExceptionClasses();
        
        return $code;
    }

    public function getTargetLanguage(): string
    {
        return 'php';
    }

    public function getSdkVersion(): string
    {
        return '1.0.0';
    }

    private function generateHeader(string $title, string $version, string $description): string
    {
        return <<<PHP
<?php

/**
 * {$title} PHP SDK
 * 
 * {$description}
 * 
 * API Version: {$version}
 * SDK Version: {$this->getSdkVersion()}
 * Generated: {date('Y-m-d H:i:s')} UTC
 * 
 * @package BasketManagerPro
 * @author Auto-generated from OpenAPI specification
 * @link https://basketmanager-pro.com/api/documentation
 */

namespace BasketManagerPro;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

PHP;
    }

    private function generateClientClass(array $openApiSpec): string
    {
        $baseUrl = $openApiSpec['servers'][0]['url'] ?? 'https://api.basketmanager-pro.com/api';
        
        return <<<PHP

/**
 * BasketManager Pro API Client
 */
class BasketManagerProClient
{
    private HttpClient \$httpClient;
    private string \$apiKey;
    private string \$baseUrl;
    private array \$defaultHeaders;

    // Resource instances
    public Teams \$teams;
    public Players \$players;
    public Games \$games;
    public Clubs \$clubs;
    public Tournaments \$tournaments;
    public Training \$training;
    public Videos \$videos;
    public Analytics \$analytics;

    /**
     * Initialize the API client
     * 
     * @param string \$apiKey Your API key
     * @param string \$baseUrl Optional custom base URL
     * @param array \$options Additional GuzzleHttp client options
     */
    public function __construct(string \$apiKey, string \$baseUrl = '{$baseUrl}', array \$options = [])
    {
        \$this->apiKey = \$apiKey;
        \$this->baseUrl = rtrim(\$baseUrl, '/');
        
        \$this->defaultHeaders = [
            'Authorization' => 'Bearer ' . \$apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'BasketManagerPro-PHP-SDK/' . \$this->getSdkVersion()
        ];

        \$this->httpClient = new HttpClient(array_merge([
            'base_uri' => \$this->baseUrl,
            'headers' => \$this->defaultHeaders,
            'timeout' => 30,
            'connect_timeout' => 10,
            'http_errors' => false
        ], \$options));

        // Initialize resource classes
        \$this->teams = new Teams(\$this);
        \$this->players = new Players(\$this);
        \$this->games = new Games(\$this);
        \$this->clubs = new Clubs(\$this);
        \$this->tournaments = new Tournaments(\$this);
        \$this->training = new Training(\$this);
        \$this->videos = new Videos(\$this);
        \$this->analytics = new Analytics(\$this);
    }

    /**
     * Make HTTP request to the API
     * 
     * @param string \$method HTTP method
     * @param string \$endpoint API endpoint
     * @param array \$options Request options
     * @return array Response data
     * @throws ApiException
     */
    public function request(string \$method, string \$endpoint, array \$options = []): array
    {
        try {
            \$response = \$this->httpClient->request(\$method, \$endpoint, \$options);
            
            return \$this->handleResponse(\$response);
        } catch (GuzzleException \$e) {
            throw new ApiException('HTTP request failed: ' . \$e->getMessage(), 0, \$e);
        }
    }

    /**
     * Handle API response
     * 
     * @param ResponseInterface \$response
     * @return array
     * @throws ApiException
     */
    private function handleResponse(ResponseInterface \$response): array
    {
        \$statusCode = \$response->getStatusCode();
        \$body = \$response->getBody()->getContents();
        \$data = json_decode(\$body, true);

        if (\$statusCode >= 200 && \$statusCode < 300) {
            return \$data ?? [];
        }

        // Handle error responses
        \$errorMessage = \$data['message'] ?? 'Unknown API error';
        \$errors = \$data['errors'] ?? [];

        switch (\$statusCode) {
            case 400:
                throw new BadRequestException(\$errorMessage, \$errors);
            case 401:
                throw new UnauthorizedException(\$errorMessage);
            case 403:
                throw new ForbiddenException(\$errorMessage);
            case 404:
                throw new NotFoundException(\$errorMessage);
            case 422:
                throw new ValidationException(\$errorMessage, \$errors);
            case 429:
                throw new RateLimitException(\$errorMessage);
            default:
                throw new ApiException(\$errorMessage, \$statusCode);
        }
    }

    /**
     * Get SDK version
     */
    public function getSdkVersion(): string
    {
        return '{$this->getSdkVersion()}';
    }

    /**
     * Get API base URL
     */
    public function getBaseUrl(): string
    {
        return \$this->baseUrl;
    }

    /**
     * Update API key
     */
    public function setApiKey(string \$apiKey): void
    {
        \$this->apiKey = \$apiKey;
        \$this->defaultHeaders['Authorization'] = 'Bearer ' . \$apiKey;
    }

    /**
     * Test API connection
     * 
     * @return array API status information
     * @throws ApiException
     */
    public function ping(): array
    {
        return \$this->request('GET', '/v4/documentation/ping');
    }
}

PHP;
    }

    private function generateResourceClasses(array $openApiSpec): string
    {
        $resourceClasses = '';
        $tags = $openApiSpec['tags'] ?? [];
        
        foreach ($tags as $tag) {
            $resourceName = $tag['name'];
            $className = Str::studly($resourceName);
            
            $resourceClasses .= $this->generateResourceClass($className, $resourceName, $openApiSpec);
        }
        
        return $resourceClasses;
    }

    private function generateResourceClass(string $className, string $resourceName, array $openApiSpec): string
    {
        $methods = $this->extractMethodsForTag($resourceName, $openApiSpec);
        $methodCode = '';
        
        foreach ($methods as $method) {
            $methodCode .= $this->generateResourceMethod($method) . "\n";
        }

        return <<<PHP

/**
 * {$resourceName} resource
 */
class {$className}
{
    private BasketManagerProClient \$client;

    public function __construct(BasketManagerProClient \$client)
    {
        \$this->client = \$client;
    }

{$methodCode}}

PHP;
    }

    private function extractMethodsForTag(string $tagName, array $openApiSpec): array
    {
        $methods = [];
        
        foreach ($openApiSpec['paths'] as $path => $operations) {
            foreach ($operations as $httpMethod => $operation) {
                if (isset($operation['tags']) && in_array($tagName, $operation['tags'])) {
                    $methods[] = [
                        'path' => $path,
                        'http_method' => $httpMethod,
                        'operation_id' => $operation['operationId'] ?? null,
                        'summary' => $operation['summary'] ?? '',
                        'parameters' => $operation['parameters'] ?? [],
                        'request_body' => $operation['requestBody'] ?? null,
                        'responses' => $operation['responses'] ?? []
                    ];
                }
            }
        }
        
        return $methods;
    }

    private function generateResourceMethod(array $method): string
    {
        $operationId = $method['operation_id'];
        $methodName = $this->operationIdToMethodName($operationId);
        $summary = $method['summary'];
        $httpMethod = strtoupper($method['http_method']);
        $path = $method['path'];
        
        // Extract parameters
        $pathParams = [];
        $queryParams = [];
        
        foreach ($method['parameters'] as $param) {
            if ($param['in'] === 'path') {
                $pathParams[] = $param['name'];
            } elseif ($param['in'] === 'query') {
                $queryParams[] = $param['name'];
            }
        }
        
        // Build method signature
        $signature = "public function {$methodName}(";
        $params = [];
        
        foreach ($pathParams as $param) {
            $params[] = "\$" . Str::camel($param);
        }
        
        if (!empty($queryParams) || $method['request_body']) {
            $params[] = "array \$options = []";
        }
        
        $signature .= implode(', ', $params) . ")";
        
        // Build method body
        $endpoint = $path;
        foreach ($pathParams as $param) {
            $camelParam = Str::camel($param);
            $endpoint = str_replace("{{$param}}", "\" . \${$camelParam} . \"", $endpoint);
        }
        
        $requestOptions = "[]";
        if (!empty($queryParams)) {
            $requestOptions = "\$this->buildQueryOptions(\$options, " . json_encode($queryParams) . ")";
        }
        
        if ($method['request_body']) {
            $requestOptions = "\$this->buildRequestOptions(\$options)";
        }

        return <<<PHP
    /**
     * {$summary}
     * 
     * @param array \$options Request options
     * @return array Response data
     * @throws ApiException
     */
    {$signature}: array
    {
        return \$this->client->request('{$httpMethod}', "{$endpoint}", {$requestOptions});
    }
PHP;
    }

    private function operationIdToMethodName(?string $operationId): string
    {
        if (!$operationId) {
            return 'unknownOperation';
        }
        
        // Convert operation ID to camelCase method name
        $parts = explode('_', $operationId);
        $method = array_shift($parts); // First part stays lowercase
        
        foreach ($parts as $part) {
            $method .= ucfirst($part);
        }
        
        return $method;
    }

    private function generateModelClasses(array $openApiSpec): string
    {
        $modelClasses = '';
        $schemas = $openApiSpec['components']['schemas'] ?? [];
        
        foreach ($schemas as $schemaName => $schema) {
            if (!Str::endsWith($schemaName, 'Request') && $schema['type'] === 'object') {
                $modelClasses .= $this->generateModelClass($schemaName, $schema);
            }
        }
        
        return $modelClasses;
    }

    private function generateModelClass(string $className, array $schema): string
    {
        $properties = $schema['properties'] ?? [];
        $propertiesCode = '';
        $constructorCode = '';
        
        foreach ($properties as $propertyName => $propertySchema) {
            $phpType = $this->openApiTypeToPhpType($propertySchema['type'] ?? 'string');
            $propertiesCode .= "    public {$phpType} \${$propertyName};\n";
        }

        return <<<PHP

/**
 * {$className} model
 */
class {$className}
{
{$propertiesCode}
    public function __construct(array \$data = [])
    {
        foreach (\$data as \$key => \$value) {
            if (property_exists(\$this, \$key)) {
                \$this->\$key = \$value;
            }
        }
    }

    /**
     * Convert model to array
     */
    public function toArray(): array
    {
        return get_object_vars(\$this);
    }

    /**
     * Convert model to JSON
     */
    public function toJson(int \$options = 0): string
    {
        return json_encode(\$this->toArray(), \$options);
    }
}

PHP;
    }

    private function openApiTypeToPhpType(string $openApiType): string
    {
        return match($openApiType) {
            'integer' => 'int',
            'number' => 'float', 
            'boolean' => 'bool',
            'array' => 'array',
            'object' => 'array',
            default => 'string'
        };
    }

    private function generateExceptionClasses(): string
    {
        return <<<PHP

/**
 * Base API Exception
 */
class ApiException extends \Exception
{
    protected array \$errors;

    public function __construct(string \$message = "", int \$code = 0, \Throwable \$previous = null, array \$errors = [])
    {
        parent::__construct(\$message, \$code, \$previous);
        \$this->errors = \$errors;
    }

    public function getErrors(): array
    {
        return \$this->errors;
    }
}

/**
 * Bad Request Exception (400)
 */
class BadRequestException extends ApiException {}

/**
 * Unauthorized Exception (401) 
 */
class UnauthorizedException extends ApiException {}

/**
 * Forbidden Exception (403)
 */
class ForbiddenException extends ApiException {}

/**
 * Not Found Exception (404)
 */
class NotFoundException extends ApiException {}

/**
 * Validation Exception (422)
 */
class ValidationException extends ApiException {}

/**
 * Rate Limit Exception (429)
 */
class RateLimitException extends ApiException {}

PHP;
    }
}