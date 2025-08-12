<?php

namespace App\Services\OpenApi\SDK;

use Illuminate\Support\Str;

class javascriptSDKGenerator implements SDKGeneratorInterface
{
    public function generate(array $openApiSpec): string
    {
        $apiTitle = $openApiSpec['info']['title'];
        $apiVersion = $openApiSpec['info']['version'];
        $apiDescription = $openApiSpec['info']['description'] ?? '';
        
        $code = $this->generateHeader($apiTitle, $apiVersion, $apiDescription);
        $code .= $this->generateClientClass($openApiSpec);
        $code .= $this->generateResourceClasses($openApiSpec);
        $code .= $this->generateExceptionClasses();
        $code .= $this->generateExports();
        
        return $code;
    }

    public function getTargetLanguage(): string
    {
        return 'javascript';
    }

    public function getSdkVersion(): string
    {
        return '1.0.0';
    }

    private function generateHeader(string $title, string $version, string $description): string
    {
        return <<<JS
/**
 * {$title} JavaScript SDK
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

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD
        define(['axios'], factory);
    } else if (typeof module === 'object' && module.exports) {
        // Node.js/CommonJS
        module.exports = factory(require('axios'));
    } else {
        // Browser global
        root.BasketManagerPro = factory(root.axios);
    }
}(typeof self !== 'undefined' ? self : this, function (axios) {
    'use strict';

JS;
    }

    private function generateClientClass(array $openApiSpec): string
    {
        $baseUrl = $openApiSpec['servers'][0]['url'] ?? 'https://api.basketmanager-pro.com/api';
        
        return <<<JS
    /**
     * BasketManager Pro API Client
     */
    class BasketManagerProClient {
        /**
         * Initialize the API client
         * 
         * @param {string} apiKey - Your API key
         * @param {string} baseUrl - Optional custom base URL
         * @param {Object} options - Additional axios options
         */
        constructor(apiKey, baseUrl = '{$baseUrl}', options = {}) {
            this.apiKey = apiKey;
            this.baseUrl = baseUrl.replace(/\/$/, '');
            this.sdkVersion = '{$this->getSdkVersion()}';

            // Create axios instance
            this.http = axios.create({
                baseURL: this.baseUrl,
                timeout: 30000,
                headers: {
                    'Authorization': `Bearer \${apiKey}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'User-Agent': `BasketManagerPro-JS-SDK/\${this.sdkVersion}`
                },
                ...options
            });

            // Add response interceptor for error handling
            this.http.interceptors.response.use(
                response => response,
                error => this.handleError(error)
            );

            // Initialize resource classes
            this.teams = new Teams(this);
            this.players = new Players(this);
            this.games = new Games(this);
            this.clubs = new Clubs(this);
            this.tournaments = new Tournaments(this);
            this.training = new Training(this);
            this.videos = new Videos(this);
            this.analytics = new Analytics(this);
        }

        /**
         * Make HTTP request to the API
         * 
         * @param {string} method - HTTP method
         * @param {string} endpoint - API endpoint
         * @param {Object} options - Request options
         * @returns {Promise<Object>} Response data
         */
        async request(method, endpoint, options = {}) {
            try {
                const response = await this.http.request({
                    method: method.toUpperCase(),
                    url: endpoint,
                    ...options
                });
                
                return response.data;
            } catch (error) {
                throw this.handleError(error);
            }
        }

        /**
         * Handle API errors
         * 
         * @param {Error} error - The error object
         * @returns {ApiException} Formatted error
         */
        handleError(error) {
            if (!error.response) {
                return new ApiException('Network error: ' + error.message);
            }

            const status = error.response.status;
            const data = error.response.data;
            const message = data?.message || 'Unknown API error';
            const errors = data?.errors || {};

            switch (status) {
                case 400:
                    return new BadRequestException(message, errors);
                case 401:
                    return new UnauthorizedException(message);
                case 403:
                    return new ForbiddenException(message);
                case 404:
                    return new NotFoundException(message);
                case 422:
                    return new ValidationException(message, errors);
                case 429:
                    return new RateLimitException(message);
                default:
                    return new ApiException(message, status, errors);
            }
        }

        /**
         * Update API key
         * 
         * @param {string} apiKey - New API key
         */
        setApiKey(apiKey) {
            this.apiKey = apiKey;
            this.http.defaults.headers['Authorization'] = `Bearer \${apiKey}`;
        }

        /**
         * Get SDK version
         * 
         * @returns {string} SDK version
         */
        getSdkVersion() {
            return this.sdkVersion;
        }

        /**
         * Get API base URL
         * 
         * @returns {string} Base URL
         */
        getBaseUrl() {
            return this.baseUrl;
        }

        /**
         * Test API connection
         * 
         * @returns {Promise<Object>} API status information
         */
        async ping() {
            return await this.request('GET', '/v4/documentation/ping');
        }
    }

JS;
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

        return <<<JS

    /**
     * {$resourceName} resource
     */
    class {$className} {
        constructor(client) {
            this.client = client;
        }

{$methodCode}    }

JS;
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
        $params = [];
        foreach ($pathParams as $param) {
            $params[] = Str::camel($param);
        }
        
        if (!empty($queryParams) || $method['request_body']) {
            $params[] = "options = {}";
        }
        
        $signature = implode(', ', $params);
        
        // Build endpoint
        $endpoint = $path;
        foreach ($pathParams as $param) {
            $camelParam = Str::camel($param);
            $endpoint = str_replace("{{$param}}", "\${" . $camelParam . "}", $endpoint);
        }
        
        // Build request options
        $requestOptions = "{}";
        if (!empty($queryParams)) {
            $queryParamsJson = json_encode($queryParams);
            $requestOptions = "this.buildQueryOptions(options, {$queryParamsJson})";
        }
        
        if ($method['request_body']) {
            $requestOptions = "{ data: options.data || options }";
        }

        return <<<JS
        /**
         * {$summary}
         * 
         * @param {Object} options - Request options
         * @returns {Promise<Object>} Response data
         */
        async {$methodName}({$signature}) {
            return await this.client.request('{$httpMethod}', `{$endpoint}`, {$requestOptions});
        }

JS;
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

    private function generateExceptionClasses(): string
    {
        return <<<JS

    /**
     * Base API Exception
     */
    class ApiException extends Error {
        constructor(message, status = 0, errors = {}) {
            super(message);
            this.name = 'ApiException';
            this.status = status;
            this.errors = errors;
        }
    }

    /**
     * Bad Request Exception (400)
     */
    class BadRequestException extends ApiException {
        constructor(message, errors = {}) {
            super(message, 400, errors);
            this.name = 'BadRequestException';
        }
    }

    /**
     * Unauthorized Exception (401)
     */
    class UnauthorizedException extends ApiException {
        constructor(message) {
            super(message, 401);
            this.name = 'UnauthorizedException';
        }
    }

    /**
     * Forbidden Exception (403)
     */
    class ForbiddenException extends ApiException {
        constructor(message) {
            super(message, 403);
            this.name = 'ForbiddenException';
        }
    }

    /**
     * Not Found Exception (404)
     */
    class NotFoundException extends ApiException {
        constructor(message) {
            super(message, 404);
            this.name = 'NotFoundException';
        }
    }

    /**
     * Validation Exception (422)
     */
    class ValidationException extends ApiException {
        constructor(message, errors = {}) {
            super(message, 422, errors);
            this.name = 'ValidationException';
        }
    }

    /**
     * Rate Limit Exception (429)
     */
    class RateLimitException extends ApiException {
        constructor(message) {
            super(message, 429);
            this.name = 'RateLimitException';
        }
    }

JS;
    }

    private function generateExports(): string
    {
        return <<<JS

    // Export the client and exceptions
    return {
        BasketManagerProClient,
        ApiException,
        BadRequestException,
        UnauthorizedException,
        ForbiddenException,
        NotFoundException,
        ValidationException,
        RateLimitException
    };

}));

/**
 * Usage Example:
 * 
 * // Node.js/CommonJS
 * const { BasketManagerProClient } = require('./basketmanager-pro-sdk');
 * const client = new BasketManagerProClient('your-api-key');
 * 
 * // ES6 Modules
 * import { BasketManagerProClient } from './basketmanager-pro-sdk.js';
 * const client = new BasketManagerProClient('your-api-key');
 * 
 * // Browser
 * <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
 * <script src="basketmanager-pro-sdk.js"></script>
 * <script>
 *   const client = new BasketManagerPro.BasketManagerProClient('your-api-key');
 * </script>
 * 
 * // Examples
 * try {
 *   // List teams
 *   const teams = await client.teams.index({ page: 1, per_page: 20 });
 *   console.log('Teams:', teams);
 * 
 *   // Get specific team
 *   const team = await client.teams.show(1);
 *   console.log('Team:', team);
 * 
 *   // Create new player
 *   const newPlayer = await client.players.store({
 *     first_name: 'John',
 *     last_name: 'Doe',
 *     jersey_number: 23,
 *     position: 'SF',
 *     team_id: 1
 *   });
 *   console.log('New player:', newPlayer);
 * 
 *   // Get live game data
 *   const liveGame = await client.games.liveScore(123);
 *   console.log('Live score:', liveGame);
 * 
 * } catch (error) {
 *   if (error instanceof ValidationException) {
 *     console.error('Validation errors:', error.errors);
 *   } else {
 *     console.error('API error:', error.message);
 *   }
 * }
 */

JS;
    }
}