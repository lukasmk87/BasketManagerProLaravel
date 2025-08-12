<?php

namespace App\Services\OpenApi\SDK;

use Illuminate\Support\Str;

class pythonSDKGenerator implements SDKGeneratorInterface
{
    public function generate(array $openApiSpec): string
    {
        $apiTitle = $openApiSpec['info']['title'];
        $apiVersion = $openApiSpec['info']['version'];
        $apiDescription = $openApiSpec['info']['description'] ?? '';
        
        $code = $this->generateHeader($apiTitle, $apiVersion, $apiDescription);
        $code .= $this->generateImports();
        $code .= $this->generateExceptionClasses();
        $code .= $this->generateClientClass($openApiSpec);
        $code .= $this->generateResourceClasses($openApiSpec);
        
        return $code;
    }

    public function getTargetLanguage(): string
    {
        return 'python';
    }

    public function getSdkVersion(): string
    {
        return '1.0.0';
    }

    private function generateHeader(string $title, string $version, string $description): string
    {
        return <<<PYTHON
"""
{$title} Python SDK

{$description}

API Version: {$version}
SDK Version: {$this->getSdkVersion()}
Generated: {date('Y-m-d H:i:s')} UTC

@package BasketManagerPro
@author Auto-generated from OpenAPI specification
@link https://basketmanager-pro.com/api/documentation
"""

PYTHON;
    }

    private function generateImports(): string
    {
        return <<<PYTHON
import requests
import json
from typing import Dict, List, Optional, Union, Any
from urllib.parse import urljoin, urlencode
from datetime import datetime


PYTHON;
    }

    private function generateExceptionClasses(): string
    {
        return <<<PYTHON
class ApiException(Exception):
    """Base API Exception"""
    
    def __init__(self, message: str, status_code: int = 0, errors: Optional[Dict] = None):
        super().__init__(message)
        self.status_code = status_code
        self.errors = errors or {}


class BadRequestException(ApiException):
    """Bad Request Exception (400)"""
    
    def __init__(self, message: str, errors: Optional[Dict] = None):
        super().__init__(message, 400, errors)


class UnauthorizedException(ApiException):
    """Unauthorized Exception (401)"""
    
    def __init__(self, message: str):
        super().__init__(message, 401)


class ForbiddenException(ApiException):
    """Forbidden Exception (403)"""
    
    def __init__(self, message: str):
        super().__init__(message, 403)


class NotFoundException(ApiException):
    """Not Found Exception (404)"""
    
    def __init__(self, message: str):
        super().__init__(message, 404)


class ValidationException(ApiException):
    """Validation Exception (422)"""
    
    def __init__(self, message: str, errors: Optional[Dict] = None):
        super().__init__(message, 422, errors)


class RateLimitException(ApiException):
    """Rate Limit Exception (429)"""
    
    def __init__(self, message: str):
        super().__init__(message, 429)


PYTHON;
    }

    private function generateClientClass(array $openApiSpec): string
    {
        $baseUrl = $openApiSpec['servers'][0]['url'] ?? 'https://api.basketmanager-pro.com/api';
        
        return <<<PYTHON
class BasketManagerProClient:
    """BasketManager Pro API Client"""
    
    def __init__(self, api_key: str, base_url: str = "{$baseUrl}", timeout: int = 30):
        """
        Initialize the API client
        
        Args:
            api_key: Your API key
            base_url: Optional custom base URL
            timeout: Request timeout in seconds
        """
        self.api_key = api_key
        self.base_url = base_url.rstrip('/')
        self.timeout = timeout
        self.sdk_version = "{$this->getSdkVersion()}"
        
        # Default headers
        self.default_headers = {
            'Authorization': f'Bearer {api_key}',
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'User-Agent': f'BasketManagerPro-Python-SDK/{self.sdk_version}'
        }
        
        # Initialize session
        self.session = requests.Session()
        self.session.headers.update(self.default_headers)
        
        # Initialize resource classes
        self.teams = Teams(self)
        self.players = Players(self)
        self.games = Games(self)
        self.clubs = Clubs(self)
        self.tournaments = Tournaments(self)
        self.training = Training(self)
        self.videos = Videos(self)
        self.analytics = Analytics(self)
    
    def request(self, method: str, endpoint: str, **kwargs) -> Dict[str, Any]:
        """
        Make HTTP request to the API
        
        Args:
            method: HTTP method
            endpoint: API endpoint
            **kwargs: Additional request arguments
            
        Returns:
            Response data as dictionary
            
        Raises:
            ApiException: For API errors
        """
        url = urljoin(self.base_url, endpoint.lstrip('/'))
        
        try:
            response = self.session.request(
                method.upper(),
                url,
                timeout=self.timeout,
                **kwargs
            )
            
            return self._handle_response(response)
            
        except requests.exceptions.RequestException as e:
            raise ApiException(f"HTTP request failed: {str(e)}")
    
    def _handle_response(self, response: requests.Response) -> Dict[str, Any]:
        """Handle API response"""
        try:
            data = response.json()
        except json.JSONDecodeError:
            data = {}
        
        if 200 <= response.status_code < 300:
            return data
        
        # Handle error responses
        message = data.get('message', 'Unknown API error')
        errors = data.get('errors', {})
        
        if response.status_code == 400:
            raise BadRequestException(message, errors)
        elif response.status_code == 401:
            raise UnauthorizedException(message)
        elif response.status_code == 403:
            raise ForbiddenException(message)
        elif response.status_code == 404:
            raise NotFoundException(message)
        elif response.status_code == 422:
            raise ValidationException(message, errors)
        elif response.status_code == 429:
            raise RateLimitException(message)
        else:
            raise ApiException(message, response.status_code, errors)
    
    def set_api_key(self, api_key: str) -> None:
        """Update API key"""
        self.api_key = api_key
        self.session.headers['Authorization'] = f'Bearer {api_key}'
    
    def get_sdk_version(self) -> str:
        """Get SDK version"""
        return self.sdk_version
    
    def get_base_url(self) -> str:
        """Get API base URL"""
        return self.base_url
    
    def ping(self) -> Dict[str, Any]:
        """
        Test API connection
        
        Returns:
            API status information
        """
        return self.request('GET', '/v4/documentation/ping')


PYTHON;
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

        return <<<PYTHON

class {$className}:
    """{$resourceName} resource"""
    
    def __init__(self, client: BasketManagerProClient):
        self.client = client

{$methodCode}
PYTHON;
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
            $pythonParam = Str::snake($param);
            $params[] = "{$pythonParam}: Union[int, str]";
        }
        
        if (!empty($queryParams) || $method['request_body']) {
            $params[] = "**kwargs";
        }
        
        $signature = implode(', ', $params);
        
        // Build endpoint
        $endpoint = $path;
        foreach ($pathParams as $param) {
            $pythonParam = Str::snake($param);
            $endpoint = str_replace("{{$param}}", "{" . $pythonParam . "}", $endpoint);
        }
        
        // Build docstring
        $docstring = "        \"\"\"\n        {$summary}\n        \n        Returns:\n            Response data as dictionary\n        \"\"\"";

        return <<<PYTHON
    def {$methodName}(self, {$signature}) -> Dict[str, Any]:
{$docstring}
        endpoint = f"{$endpoint}"
        return self.client.request('{$httpMethod}', endpoint, **kwargs)

PYTHON;
    }

    private function operationIdToMethodName(?string $operationId): string
    {
        if (!$operationId) {
            return 'unknown_operation';
        }
        
        // Convert operation ID to snake_case method name
        return Str::snake($operationId);
    }
}