<?php

namespace App\Services\OpenApi\SDK;

interface SDKGeneratorInterface
{
    /**
     * Generate SDK code from OpenAPI specification
     */
    public function generate(array $openApiSpec): string;

    /**
     * Get the language/platform this generator targets
     */
    public function getTargetLanguage(): string;

    /**
     * Get the SDK version
     */
    public function getSdkVersion(): string;
}