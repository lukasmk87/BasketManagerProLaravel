<?php

namespace App\Services\Install;

use Illuminate\Support\Facades\File;

class EnvironmentManager
{
    protected string $envPath;

    protected string $envExamplePath;

    public function __construct()
    {
        $this->envPath = base_path('.env');
        $this->envExamplePath = base_path('.env.example');
    }

    /**
     * Get current environment configuration
     */
    public function getCurrentEnvironment(): array
    {
        if (! file_exists($this->envPath)) {
            // Copy from .env.example if .env doesn't exist
            if (file_exists($this->envExamplePath)) {
                copy($this->envExamplePath, $this->envPath);
            }
        }

        return [
            'app_name' => env('APP_NAME', 'BasketManager Pro'),
            'app_url' => env('APP_URL', 'http://localhost'),
            'app_env' => env('APP_ENV', 'production'),
            'app_debug' => env('APP_DEBUG', false),

            'db_connection' => env('DB_CONNECTION', 'mysql'),
            'db_host' => env('DB_HOST', '127.0.0.1'),
            'db_port' => env('DB_PORT', '3306'),
            'db_database' => env('DB_DATABASE', ''),
            'db_username' => env('DB_USERNAME', ''),
            'db_password' => env('DB_PASSWORD', ''),

            'mail_mailer' => env('MAIL_MAILER', 'smtp'),
            'mail_host' => env('MAIL_HOST', ''),
            'mail_port' => env('MAIL_PORT', '587'),
            'mail_username' => env('MAIL_USERNAME', ''),
            'mail_password' => env('MAIL_PASSWORD', ''),
            'mail_encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'mail_from_address' => env('MAIL_FROM_ADDRESS', ''),
            'mail_from_name' => env('MAIL_FROM_NAME', ''),

            'stripe_key' => env('STRIPE_KEY', ''),
            'stripe_secret' => env('STRIPE_SECRET', ''),
            'stripe_webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
        ];
    }

    /**
     * Save environment variables
     */
    public function saveEnvironment(array $data): bool
    {
        try {
            // Create backup before modification
            $this->createBackup();

            // Ensure .env file exists
            if (! file_exists($this->envPath)) {
                if (file_exists($this->envExamplePath)) {
                    copy($this->envExamplePath, $this->envPath);
                } else {
                    touch($this->envPath);
                }
            }

            $envContent = file_get_contents($this->envPath);

            // Map form fields to .env keys
            $envMapping = [
                'app_name' => 'APP_NAME',
                'app_url' => 'APP_URL',
                'app_env' => 'APP_ENV',
                'app_debug' => 'APP_DEBUG',

                'db_connection' => 'DB_CONNECTION',
                'db_host' => 'DB_HOST',
                'db_port' => 'DB_PORT',
                'db_database' => 'DB_DATABASE',
                'db_username' => 'DB_USERNAME',
                'db_password' => 'DB_PASSWORD',

                'mail_mailer' => 'MAIL_MAILER',
                'mail_host' => 'MAIL_HOST',
                'mail_port' => 'MAIL_PORT',
                'mail_username' => 'MAIL_USERNAME',
                'mail_password' => 'MAIL_PASSWORD',
                'mail_encryption' => 'MAIL_ENCRYPTION',
                'mail_from_address' => 'MAIL_FROM_ADDRESS',
                'mail_from_name' => 'MAIL_FROM_NAME',

                'stripe_key' => 'STRIPE_KEY',
                'stripe_secret' => 'STRIPE_SECRET',
                'stripe_webhook_secret' => 'STRIPE_WEBHOOK_SECRET',
            ];

            // Update each environment variable
            foreach ($envMapping as $formKey => $envKey) {
                if (array_key_exists($formKey, $data)) {
                    $value = $data[$formKey];

                    // Convert boolean to string for .env
                    if (is_bool($value)) {
                        $value = $value ? 'true' : 'false';
                    }

                    $envContent = $this->setEnvironmentValue($envContent, $envKey, $value);
                }
            }

            // Write updated content back to .env
            file_put_contents($this->envPath, $envContent);

            // Clear config cache to reload new values
            \Artisan::call('config:clear');

            return true;
        } catch (\Exception $e) {
            // Restore from backup if something went wrong
            $this->restoreBackup();
            throw $e;
        }
    }

    /**
     * Set environment variable value
     */
    protected function setEnvironmentValue(string $envContent, string $key, mixed $value): string
    {
        // Escape special characters and wrap in quotes if necessary
        $value = $this->formatValue($value);

        // Check if key exists
        $pattern = "/^{$key}=.*/m";

        if (preg_match($pattern, $envContent)) {
            // Update existing key
            return preg_replace($pattern, "{$key}={$value}", $envContent);
        }

        // Add new key at the end
        return $envContent."\n{$key}={$value}";
    }

    /**
     * Format value for .env file
     */
    protected function formatValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $value = (string) $value;

        // Quote if contains spaces or special characters
        if (preg_match('/\s|#|;/', $value)) {
            // Escape quotes and backslashes
            $value = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);

            return "\"{$value}\"";
        }

        return $value;
    }

    /**
     * Create backup of .env file
     */
    protected function createBackup(): void
    {
        if (file_exists($this->envPath)) {
            $backupPath = base_path('.env.backup.'.time());
            copy($this->envPath, $backupPath);
        }
    }

    /**
     * Restore .env from latest backup
     */
    protected function restoreBackup(): void
    {
        $backups = glob(base_path('.env.backup.*'));

        if (! empty($backups)) {
            // Get the latest backup
            usort($backups, fn ($a, $b) => filemtime($b) <=> filemtime($a));
            $latestBackup = $backups[0];

            copy($latestBackup, $this->envPath);
        }
    }

    /**
     * Clean old backup files (keep last 5)
     */
    public function cleanOldBackups(): void
    {
        $backups = glob(base_path('.env.backup.*'));

        if (count($backups) > 5) {
            // Sort by modification time (newest first)
            usort($backups, fn ($a, $b) => filemtime($b) <=> filemtime($a));

            // Keep only the 5 newest backups
            $toDelete = array_slice($backups, 5);

            foreach ($toDelete as $file) {
                unlink($file);
            }
        }
    }
}
