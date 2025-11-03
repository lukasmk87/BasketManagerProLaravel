<?php

namespace App\Services\Install;

class PermissionChecker
{
    /**
     * Folders that require write permissions
     */
    protected array $folders = [
        'storage/framework/' => 'Framework Storage',
        'storage/logs/' => 'Log Storage',
        'storage/app/' => 'Application Storage',
        'bootstrap/cache/' => 'Bootstrap Cache',
        'public/uploads/' => 'Public Uploads',
    ];

    /**
     * Check folder permissions
     *
     * @return array{satisfied: bool, permissions: array}
     */
    public function check(): array
    {
        $results = [];
        $allSatisfied = true;

        foreach ($this->folders as $folder => $name) {
            $path = base_path($folder);
            $result = $this->checkFolderPermission($path, $name);
            $results[$folder] = $result;

            if ($result['status'] !== 'success') {
                $allSatisfied = false;
            }
        }

        return [
            'satisfied' => $allSatisfied,
            'permissions' => $results,
        ];
    }

    /**
     * Check individual folder permission
     */
    protected function checkFolderPermission(string $path, string $name): array
    {
        if (! file_exists($path)) {
            // Try to create the directory
            try {
                mkdir($path, 0755, true);
                $exists = true;
                $writable = is_writable($path);
            } catch (\Exception $e) {
                return [
                    'name' => $name,
                    'path' => $path,
                    'exists' => false,
                    'writable' => false,
                    'status' => 'error',
                    'message' => "Folder does not exist and could not be created: {$e->getMessage()}",
                    'permission' => null,
                ];
            }
        } else {
            $exists = true;
            $writable = is_writable($path);
        }

        $permission = $exists ? substr(sprintf('%o', fileperms($path)), -4) : null;

        $status = 'error';
        $message = '';

        if (! $exists) {
            $message = 'Folder does not exist';
        } elseif (! $writable) {
            $message = "Folder exists but is not writable (permission: {$permission})";
        } else {
            $status = 'success';
            $message = "Folder is writable (permission: {$permission})";
        }

        return [
            'name' => $name,
            'path' => $path,
            'exists' => $exists,
            'writable' => $writable,
            'permission' => $permission,
            'status' => $status,
            'message' => $message,
        ];
    }

    /**
     * Get fix commands for permission issues
     */
    public function getFixCommands(): array
    {
        $commands = [];

        foreach ($this->folders as $folder => $name) {
            $path = base_path($folder);

            if (! file_exists($path) || ! is_writable($path)) {
                $commands[] = [
                    'folder' => $name,
                    'commands' => [
                        "mkdir -p {$path}",
                        "chmod -R 755 {$path}",
                        "chown -R www-data:www-data {$path}",
                    ],
                ];
            }
        }

        return $commands;
    }
}
