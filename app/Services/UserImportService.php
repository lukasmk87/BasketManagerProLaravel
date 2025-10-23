<?php

namespace App\Services;

use App\Models\User;
use App\Models\Club;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;

class UserImportService
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Generate CSV template for user import.
     */
    public function generateTemplate(): string
    {
        $headers = [
            'name',
            'email',
            'password',
            'roles',
            'clubs',
            'phone',
            'gender',
            'date_of_birth',
            'is_active',
            'send_email'
        ];

        $exampleRow = [
            'Max Mustermann',
            'max@example.com',
            'password123',
            'player',
            '1',
            '+49 123 456789',
            'male',
            '1990-01-01',
            '1',
            '0'
        ];

        $csvContent = implode(',', $headers) . "\n";
        $csvContent .= implode(',', $exampleRow) . "\n";

        return $csvContent;
    }

    /**
     * Parse uploaded CSV/Excel file.
     */
    public function parseFile($file): Collection
    {
        $extension = $file->getClientOriginalExtension();

        if (in_array($extension, ['csv', 'txt'])) {
            return $this->parseCsv($file);
        } elseif (in_array($extension, ['xlsx', 'xls'])) {
            return $this->parseExcel($file);
        }

        throw new \Exception('Unsupported file format. Please upload CSV or Excel file.');
    }

    /**
     * Parse CSV file.
     */
    protected function parseCsv($file): Collection
    {
        $rows = [];
        $handle = fopen($file->getRealPath(), 'r');

        // Read header row
        $headers = fgetcsv($handle);

        if (!$headers) {
            fclose($handle);
            throw new \Exception('CSV file is empty or invalid.');
        }

        // Read data rows
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($headers)) {
                $rows[] = array_combine($headers, $row);
            }
        }

        fclose($handle);

        return collect($rows);
    }

    /**
     * Parse Excel file.
     */
    protected function parseExcel($file): Collection
    {
        $data = Excel::toArray([], $file);

        if (empty($data) || empty($data[0])) {
            throw new \Exception('Excel file is empty.');
        }

        $headers = $data[0][0];
        $rows = [];

        for ($i = 1; $i < count($data[0]); $i++) {
            if (count($data[0][$i]) === count($headers)) {
                $rows[] = array_combine($headers, $data[0][$i]);
            }
        }

        return collect($rows);
    }

    /**
     * Validate import data and return preview with errors.
     */
    public function validateAndPreview(Collection $data): array
    {
        $validRows = [];
        $invalidRows = [];
        $existingEmails = User::pluck('email')->toArray();
        $availableRoles = Role::pluck('name')->toArray();
        $availableClubs = Club::where('is_active', true)->pluck('id')->toArray();

        foreach ($data as $index => $row) {
            $rowNumber = $index + 2; // +2 because of header row and 0-based index
            $errors = [];

            // Validate required fields
            if (empty($row['name'])) {
                $errors[] = 'Name ist erforderlich';
            }

            if (empty($row['email'])) {
                $errors[] = 'E-Mail ist erforderlich';
            } elseif (!filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Ungültige E-Mail-Adresse';
            } elseif (in_array($row['email'], $existingEmails)) {
                $errors[] = 'E-Mail bereits vergeben';
            }

            if (empty($row['password'])) {
                $errors[] = 'Passwort ist erforderlich';
            } elseif (strlen($row['password']) < 8) {
                $errors[] = 'Passwort muss mindestens 8 Zeichen haben';
            }

            if (empty($row['roles'])) {
                $errors[] = 'Mindestens eine Rolle ist erforderlich';
            } else {
                $roles = array_map('trim', explode(',', $row['roles']));
                foreach ($roles as $role) {
                    if (!in_array($role, $availableRoles)) {
                        $errors[] = "Rolle '{$role}' existiert nicht";
                    }
                }
            }

            // Validate optional fields
            if (!empty($row['gender']) && !in_array($row['gender'], ['male', 'female', 'other'])) {
                $errors[] = 'Ungültiges Geschlecht (erlaubt: male, female, other)';
            }

            if (!empty($row['date_of_birth'])) {
                $date = \DateTime::createFromFormat('Y-m-d', $row['date_of_birth']);
                if (!$date || $date->format('Y-m-d') !== $row['date_of_birth']) {
                    $errors[] = 'Ungültiges Geburtsdatum (Format: YYYY-MM-DD)';
                }
            }

            if (!empty($row['clubs'])) {
                $clubIds = array_map('trim', explode(',', $row['clubs']));
                foreach ($clubIds as $clubId) {
                    if (!is_numeric($clubId) || !in_array((int)$clubId, $availableClubs)) {
                        $errors[] = "Club-ID '{$clubId}' ist ungültig";
                    }
                }
            }

            // Add to appropriate list
            if (empty($errors)) {
                $validRows[] = [
                    'row_number' => $rowNumber,
                    'data' => $row,
                ];
            } else {
                $invalidRows[] = [
                    'row_number' => $rowNumber,
                    'data' => $row,
                    'errors' => $errors,
                ];
            }
        }

        return [
            'total' => count($data),
            'valid' => count($validRows),
            'invalid' => count($invalidRows),
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows,
        ];
    }

    /**
     * Import users from validated data.
     */
    public function importUsers(array $validRows, string $createdBy): array
    {
        $imported = [];
        $failed = [];

        DB::beginTransaction();

        try {
            foreach ($validRows as $item) {
                $row = $item['data'];
                $rowNumber = $item['row_number'];

                try {
                    // Prepare user data
                    $userData = [
                        'name' => $row['name'],
                        'email' => $row['email'],
                        'password' => $row['password'],
                        'phone' => $row['phone'] ?? null,
                        'gender' => $row['gender'] ?? null,
                        'date_of_birth' => !empty($row['date_of_birth']) ? $row['date_of_birth'] : null,
                        'is_active' => !empty($row['is_active']) && $row['is_active'] == '1',
                        'roles' => array_map('trim', explode(',', $row['roles'])),
                    ];

                    // Create user
                    $user = $this->userService->createUser($userData);

                    // Attach clubs if provided
                    if (!empty($row['clubs'])) {
                        $clubIds = array_map('trim', explode(',', $row['clubs']));
                        foreach ($clubIds as $clubId) {
                            $user->clubs()->attach($clubId, [
                                'role' => 'member',
                                'joined_at' => now(),
                                'is_active' => true,
                            ]);
                        }
                    }

                    // Send welcome email if requested
                    if (!empty($row['send_email']) && $row['send_email'] == '1') {
                        $user->notify(new \App\Notifications\NewUserCreatedNotification(
                            $row['password'],
                            $createdBy
                        ));
                    }

                    $imported[] = [
                        'row_number' => $rowNumber,
                        'name' => $user->name,
                        'email' => $user->email,
                    ];

                } catch (\Exception $e) {
                    $failed[] = [
                        'row_number' => $rowNumber,
                        'data' => $row,
                        'error' => $e->getMessage(),
                    ];

                    Log::error("User import failed for row {$rowNumber}", [
                        'data' => $row,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            return [
                'success' => true,
                'imported' => count($imported),
                'failed' => count($failed),
                'imported_users' => $imported,
                'failed_users' => $failed,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('User import transaction failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Import failed: ' . $e->getMessage(),
                'imported' => 0,
                'failed' => count($validRows),
            ];
        }
    }
}
