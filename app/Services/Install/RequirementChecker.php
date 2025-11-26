<?php

namespace App\Services\Install;

class RequirementChecker
{
    /**
     * Minimum requirements for BasketManager Pro
     */
    protected array $requirements = [
        'php' => [
            'version' => '8.2.0',
            'name' => 'PHP Version',
            'type' => 'php',
        ],
        'extensions' => [
            'bcmath' => 'BCMath Extension',
            'ctype' => 'Ctype Extension',
            'fileinfo' => 'Fileinfo Extension',
            'json' => 'JSON Extension',
            'mbstring' => 'Mbstring Extension',
            'openssl' => 'OpenSSL Extension',
            'pdo' => 'PDO Extension',
            'tokenizer' => 'Tokenizer Extension',
            'xml' => 'XML Extension',
            'gd' => 'GD Extension',
            'curl' => 'cURL Extension',
            'zip' => 'Zip Extension',
        ],
        'functions' => [
            'proc_open' => 'proc_open()',
            'symlink' => 'symlink()',
        ],
    ];

    /**
     * Required Composer packages with their main classes
     */
    protected array $composerPackages = [
        'stevebauman/purify' => [
            'name' => 'HTML Purify (XSS Protection)',
            'class' => \Stevebauman\Purify\Facades\Purify::class,
            'required' => true,
        ],
        'spatie/laravel-permission' => [
            'name' => 'Laravel Permission (RBAC)',
            'class' => \Spatie\Permission\PermissionRegistrar::class,
            'required' => true,
        ],
        'spatie/laravel-activitylog' => [
            'name' => 'Activity Log (Audit Trail)',
            'class' => \Spatie\Activitylog\ActivitylogServiceProvider::class,
            'required' => true,
        ],
    ];

    /**
     * Check all server requirements
     *
     * @return array{satisfied: bool, requirements: array}
     */
    public function check(): array
    {
        $results = [
            'php_version' => $this->checkPhpVersion(),
            'extensions' => $this->checkExtensions(),
            'functions' => $this->checkFunctions(),
            'memory_limit' => $this->checkMemoryLimit(),
            'upload_max_filesize' => $this->checkUploadMaxFilesize(),
            'composer_packages' => $this->checkComposerPackages(),
        ];

        $satisfied = collect($results)->every(function ($category) {
            if (isset($category['status'])) {
                return $category['status'] === 'success';
            }

            return collect($category)->every(fn ($item) => $item['status'] === 'success');
        });

        return [
            'satisfied' => $satisfied,
            'requirements' => $results,
        ];
    }

    /**
     * Check PHP version
     */
    protected function checkPhpVersion(): array
    {
        $currentVersion = PHP_VERSION;
        $requiredVersion = $this->requirements['php']['version'];
        $satisfied = version_compare($currentVersion, $requiredVersion, '>=');

        return [
            'name' => $this->requirements['php']['name'],
            'required' => '>= '.$requiredVersion,
            'current' => $currentVersion,
            'status' => $satisfied ? 'success' : 'error',
            'message' => $satisfied
                ? "PHP {$currentVersion} is installed"
                : "PHP {$requiredVersion} or higher is required, you have {$currentVersion}",
        ];
    }

    /**
     * Check required PHP extensions
     */
    protected function checkExtensions(): array
    {
        $results = [];

        foreach ($this->requirements['extensions'] as $extension => $name) {
            $loaded = extension_loaded($extension);

            $results[$extension] = [
                'name' => $name,
                'status' => $loaded ? 'success' : 'error',
                'message' => $loaded
                    ? "{$name} is installed"
                    : "{$name} is not installed",
            ];
        }

        return $results;
    }

    /**
     * Check required PHP functions
     */
    protected function checkFunctions(): array
    {
        $results = [];

        foreach ($this->requirements['functions'] as $function => $name) {
            $enabled = function_exists($function);

            $results[$function] = [
                'name' => $name,
                'status' => $enabled ? 'success' : 'warning',
                'message' => $enabled
                    ? "{$name} is available"
                    : "{$name} is disabled (optional, but recommended)",
            ];
        }

        return $results;
    }

    /**
     * Check memory limit
     */
    protected function checkMemoryLimit(): array
    {
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = $this->convertToBytes($memoryLimit);
        $requiredBytes = 256 * 1024 * 1024; // 256MB
        $satisfied = $memoryLimitBytes === -1 || $memoryLimitBytes >= $requiredBytes;

        return [
            'name' => 'Memory Limit',
            'required' => '>= 256M',
            'current' => $memoryLimit,
            'status' => $satisfied ? 'success' : 'warning',
            'message' => $satisfied
                ? "Memory limit is {$memoryLimit}"
                : "Memory limit is {$memoryLimit}, recommended is 256M or higher",
        ];
    }

    /**
     * Check upload max filesize
     */
    protected function checkUploadMaxFilesize(): array
    {
        $uploadMax = ini_get('upload_max_filesize');
        $uploadMaxBytes = $this->convertToBytes($uploadMax);
        $requiredBytes = 20 * 1024 * 1024; // 20MB
        $satisfied = $uploadMaxBytes >= $requiredBytes;

        return [
            'name' => 'Upload Max Filesize',
            'required' => '>= 20M',
            'current' => $uploadMax,
            'status' => $satisfied ? 'success' : 'warning',
            'message' => $satisfied
                ? "Upload max filesize is {$uploadMax}"
                : "Upload max filesize is {$uploadMax}, recommended is 20M or higher",
        ];
    }

    /**
     * Convert PHP ini notation to bytes
     */
    protected function convertToBytes(string $value): int
    {
        if ($value === '-1') {
            return -1;
        }

        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;

        return match ($last) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    /**
     * Check required Composer packages
     */
    protected function checkComposerPackages(): array
    {
        $results = [];

        foreach ($this->composerPackages as $package => $config) {
            $installed = class_exists($config['class']);
            $status = $installed ? 'success' : ($config['required'] ? 'error' : 'warning');

            $results[$package] = [
                'name' => $config['name'],
                'status' => $status,
                'message' => $installed
                    ? "{$config['name']} is installed"
                    : "{$config['name']} is not installed. Run: composer install",
            ];
        }

        return $results;
    }
}
