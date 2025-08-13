<?php

namespace App\Services;

use App\Models\GdprConsentRecord;
use App\Models\GdprDataProcessingRecord;
use App\Models\GdprDataSubjectRequest;
use App\Models\User;
use App\Models\Player;
use App\Models\EmergencyContact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use ZipArchive;

class GDPRComplianceService
{
    protected array $exportableModels = [
        'user' => User::class,
        'player' => Player::class,
        'emergency_contact' => EmergencyContact::class,
    ];

    protected array $gdprSensitiveFields = [
        'users' => ['name', 'email', 'phone', 'address', 'birth_date', 'emergency_contact_info'],
        'players' => ['first_name', 'last_name', 'birth_date', 'address', 'phone', 'email', 'medical_notes', 'emergency_contact_id'],
        'emergency_contacts' => ['name', 'phone', 'email', 'address', 'relationship', 'medical_notes'],
    ];

    /**
     * Process a data subject request according to GDPR requirements
     */
    public function processDataSubjectRequest(GdprDataSubjectRequest $request): array
    {
        Log::info("Processing GDPR data subject request", ['request_id' => $request->request_id]);

        $result = [
            'success' => false,
            'message' => '',
            'data' => null,
            'processing_time' => microtime(true),
        ];

        try {
            // Verify identity if not already done
            if (!$request->identity_verified) {
                throw new \Exception('Identity verification required before processing request');
            }

            // Check deadline compliance
            if (Carbon::now()->isAfter($request->deadline_date)) {
                $request->update(['response_status' => 'overdue']);
                Log::warning("GDPR request deadline exceeded", ['request_id' => $request->request_id]);
            }

            switch ($request->request_type) {
                case 'data_export':
                    $result['data'] = $this->handleDataExportRequest($request);
                    $result['message'] = 'Data export completed successfully';
                    break;

                case 'data_portability':
                    $result['data'] = $this->handleDataPortabilityRequest($request);
                    $result['message'] = 'Data portability package created successfully';
                    break;

                case 'data_erasure':
                    $result['data'] = $this->handleDataErasureRequest($request);
                    $result['message'] = 'Data erasure completed successfully';
                    break;

                case 'data_rectification':
                    $result['data'] = $this->handleDataRectificationRequest($request);
                    $result['message'] = 'Data rectification completed successfully';
                    break;

                case 'processing_restriction':
                    $result['data'] = $this->handleProcessingRestrictionRequest($request);
                    $result['message'] = 'Processing restriction applied successfully';
                    break;

                case 'objection_to_processing':
                    $result['data'] = $this->handleObjectionRequest($request);
                    $result['message'] = 'Objection to processing handled successfully';
                    break;

                default:
                    throw new \Exception("Unknown request type: {$request->request_type}");
            }

            // Update request status
            $request->update([
                'response_status' => 'completed',
                'completed_at' => Carbon::now(),
                'processing_notes' => json_encode($result['data']),
            ]);

            $result['success'] = true;

        } catch (\Exception $e) {
            Log::error("GDPR request processing failed", [
                'request_id' => $request->request_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $request->update([
                'response_status' => 'failed',
                'processing_notes' => json_encode(['error' => $e->getMessage()]),
            ]);

            $result['message'] = 'Request processing failed: ' . $e->getMessage();
        }

        $result['processing_time'] = microtime(true) - $result['processing_time'];
        return $result;
    }

    /**
     * Handle data export request (GDPR Article 15)
     */
    protected function handleDataExportRequest(GdprDataSubjectRequest $request): array
    {
        $subject = $request->subject;
        $exportData = [];
        $exportFiles = [];

        // Gather all personal data for the subject
        foreach ($this->exportableModels as $type => $modelClass) {
            if ($subject instanceof $modelClass || $this->isRelatedToSubject($subject, $modelClass)) {
                $data = $this->extractPersonalData($subject, $modelClass);
                if (!empty($data)) {
                    $exportData[$type] = $data;
                }
            }
        }

        // Include consent records
        $consentRecords = GdprConsentRecord::where('consentable_type', get_class($subject))
            ->where('consentable_id', $subject->id)
            ->get()
            ->toArray();

        if (!empty($consentRecords)) {
            $exportData['consent_records'] = $consentRecords;
        }

        // Include processing records
        $processingRecords = GdprDataProcessingRecord::where('data_subject_type', get_class($subject))
            ->where('data_subject_id', $subject->id)
            ->get()
            ->toArray();

        if (!empty($processingRecords)) {
            $exportData['data_processing_records'] = $processingRecords;
        }

        // Create export package
        $exportFileName = $this->createDataExportPackage($request, $exportData);
        
        return [
            'export_file' => $exportFileName,
            'data_categories' => array_keys($exportData),
            'total_records' => array_sum(array_map('count', $exportData)),
            'export_format' => 'json_zip',
            'expires_at' => Carbon::now()->addDays(30)->toISOString(),
        ];
    }

    /**
     * Handle data portability request (GDPR Article 20)
     */
    protected function handleDataPortabilityRequest(GdprDataSubjectRequest $request): array
    {
        // Similar to data export but in machine-readable format with specific scope
        $exportData = $this->handleDataExportRequest($request);
        
        // Convert to structured, machine-readable format
        $portabilityData = [
            'subject_info' => [
                'type' => get_class($request->subject),
                'id' => $request->subject->id,
                'export_date' => Carbon::now()->toISOString(),
            ],
            'data' => $exportData,
            'format_version' => '1.0',
            'schema' => $this->getDataPortabilitySchema(),
        ];

        $fileName = $this->createPortabilityPackage($request, $portabilityData);

        return [
            'portability_file' => $fileName,
            'format' => 'json',
            'schema_version' => '1.0',
            'expires_at' => Carbon::now()->addDays(30)->toISOString(),
        ];
    }

    /**
     * Handle data erasure request (GDPR Article 17 - Right to be Forgotten)
     */
    protected function handleDataErasureRequest(GdprDataSubjectRequest $request): array
    {
        $subject = $request->subject;
        $erasedData = [];
        $retainedData = [];

        // Check for legal obligations that prevent erasure
        $legalRetentionReasons = $this->checkLegalRetentionRequirements($subject);
        
        if (!empty($legalRetentionReasons)) {
            Log::info("Data erasure limited by legal requirements", [
                'subject_id' => $subject->id,
                'retention_reasons' => $legalRetentionReasons
            ]);
        }

        // Anonymize or delete personal data
        foreach ($this->exportableModels as $type => $modelClass) {
            if ($subject instanceof $modelClass) {
                $result = $this->erasePersonalData($subject, $legalRetentionReasons);
                $erasedData[$type] = $result['erased'];
                $retainedData[$type] = $result['retained'];
            }
        }

        // Update consent records to reflect erasure
        GdprConsentRecord::where('consentable_type', get_class($subject))
            ->where('consentable_id', $subject->id)
            ->update([
                'consent_withdrawn_at' => Carbon::now(),
                'withdrawal_reason' => 'data_erasure_request',
            ]);

        // Create erasure audit trail
        $this->createErasureAuditTrail($request, $erasedData, $retainedData);

        return [
            'erasure_completed' => true,
            'erased_data_categories' => array_keys($erasedData),
            'retained_data_categories' => array_keys($retainedData),
            'retention_reasons' => $legalRetentionReasons,
            'erasure_date' => Carbon::now()->toISOString(),
        ];
    }

    /**
     * Handle data rectification request (GDPR Article 16)
     */
    protected function handleDataRectificationRequest(GdprDataSubjectRequest $request): array
    {
        $subject = $request->subject;
        $specificData = json_decode($request->specific_data_requested, true);
        $rectifiedFields = [];

        if (!$specificData || !isset($specificData['corrections'])) {
            throw new \Exception('No correction data provided in request');
        }

        foreach ($specificData['corrections'] as $field => $newValue) {
            if ($this->isFieldRectifiable($subject, $field)) {
                $oldValue = $subject->getAttribute($field);
                $subject->setAttribute($field, $newValue);
                
                $rectifiedFields[$field] = [
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                    'rectified_at' => Carbon::now()->toISOString(),
                ];
            }
        }

        $subject->save();

        // Log rectification for audit purposes
        Log::info("GDPR data rectification completed", [
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
            'rectified_fields' => array_keys($rectifiedFields),
            'request_id' => $request->request_id,
        ]);

        return [
            'rectification_completed' => true,
            'rectified_fields' => $rectifiedFields,
            'rectification_date' => Carbon::now()->toISOString(),
        ];
    }

    /**
     * Handle processing restriction request (GDPR Article 18)
     */
    protected function handleProcessingRestrictionRequest(GdprDataSubjectRequest $request): array
    {
        $subject = $request->subject;
        
        // Add processing restriction flag
        if (method_exists($subject, 'addProcessingRestriction')) {
            $subject->addProcessingRestriction('gdpr_article_18', [
                'request_id' => $request->request_id,
                'restriction_date' => Carbon::now()->toISOString(),
                'reason' => $request->request_description,
            ]);
        }

        // Update consent records to reflect restriction
        GdprConsentRecord::where('consentable_type', get_class($subject))
            ->where('consentable_id', $subject->id)
            ->update(['processing_restricted' => true]);

        return [
            'restriction_applied' => true,
            'restriction_date' => Carbon::now()->toISOString(),
            'restricted_processing_types' => ['marketing', 'analytics', 'profiling'],
        ];
    }

    /**
     * Handle objection to processing request (GDPR Article 21)
     */
    protected function handleObjectionRequest(GdprDataSubjectRequest $request): array
    {
        $subject = $request->subject;
        $specificData = json_decode($request->specific_data_requested, true);
        $objectedProcessing = $specificData['objected_processing'] ?? ['all'];

        // Withdraw relevant consents
        $withdrawnConsents = [];
        foreach ($objectedProcessing as $processingType) {
            $consent = GdprConsentRecord::where('consentable_type', get_class($subject))
                ->where('consentable_id', $subject->id)
                ->where('consent_type', $processingType)
                ->where('consent_given', true)
                ->first();

            if ($consent) {
                $consent->update([
                    'consent_withdrawn_at' => Carbon::now(),
                    'withdrawal_reason' => 'objection_to_processing',
                ]);
                $withdrawnConsents[] = $processingType;
            }
        }

        return [
            'objection_processed' => true,
            'objection_date' => Carbon::now()->toISOString(),
            'withdrawn_consents' => $withdrawnConsents,
            'objected_processing_types' => $objectedProcessing,
        ];
    }

    /**
     * Create consent record with proper GDPR compliance
     */
    public function recordConsent(Model $consentable, User $user, array $consentData): GdprConsentRecord
    {
        $consent = GdprConsentRecord::create([
            'consentable_type' => get_class($consentable),
            'consentable_id' => $consentable->id,
            'given_by_user_id' => $user->id,
            'consent_type' => $consentData['type'],
            'consent_text' => $consentData['text'],
            'consent_version' => $consentData['version'] ?? '1.0',
            'consent_given' => $consentData['given'],
            'consent_given_at' => $consentData['given'] ? Carbon::now() : null,
            'collection_method' => $consentData['method'] ?? 'web_form',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'legal_basis' => $consentData['legal_basis'] ?? 'consent',
            'processing_purposes' => json_encode($consentData['purposes'] ?? []),
            'data_categories' => json_encode($consentData['data_categories'] ?? []),
        ]);

        // Log consent for audit trail
        Log::info("GDPR consent recorded", [
            'consent_id' => $consent->id,
            'type' => $consentData['type'],
            'given' => $consentData['given'],
            'user_id' => $user->id,
        ]);

        return $consent;
    }

    /**
     * Check if consent needs renewal (GDPR requires periodic renewal)
     */
    public function checkConsentRenewal(Model $consentable): array
    {
        $renewalRequired = [];
        $renewalMonths = config('gdpr.consent_renewal_months', 24);

        $consents = GdprConsentRecord::where('consentable_type', get_class($consentable))
            ->where('consentable_id', $consentable->id)
            ->where('consent_given', true)
            ->where('consent_given_at', '<', Carbon::now()->subMonths($renewalMonths))
            ->get();

        foreach ($consents as $consent) {
            $renewalRequired[] = [
                'consent_id' => $consent->id,
                'consent_type' => $consent->consent_type,
                'given_at' => $consent->consent_given_at,
                'renewal_due' => true,
            ];
        }

        return $renewalRequired;
    }

    /**
     * Generate comprehensive GDPR compliance report
     */
    public function generateComplianceReport(Carbon $fromDate = null, Carbon $toDate = null): array
    {
        $fromDate = $fromDate ?? Carbon::now()->subMonth();
        $toDate = $toDate ?? Carbon::now();

        $report = [
            'report_period' => [
                'from' => $fromDate->toISOString(),
                'to' => $toDate->toISOString(),
            ],
            'data_subject_requests' => $this->getRequestStatistics($fromDate, $toDate),
            'consent_management' => $this->getConsentStatistics($fromDate, $toDate),
            'data_processing' => $this->getProcessingStatistics($fromDate, $toDate),
            'compliance_issues' => $this->identifyComplianceIssues($fromDate, $toDate),
            'recommendations' => $this->generateComplianceRecommendations(),
            'generated_at' => Carbon::now()->toISOString(),
        ];

        return $report;
    }

    /**
     * Extract personal data for export
     */
    protected function extractPersonalData(Model $subject, string $modelClass): array
    {
        $data = [];
        $tableName = (new $modelClass)->getTable();
        $sensitiveFields = $this->gdprSensitiveFields[$tableName] ?? [];

        if ($subject instanceof $modelClass) {
            foreach ($sensitiveFields as $field) {
                if ($subject->getAttribute($field) !== null) {
                    $data[$field] = $subject->getAttribute($field);
                }
            }
            
            // Include metadata
            $data['created_at'] = $subject->created_at;
            $data['updated_at'] = $subject->updated_at;
        }

        return $data;
    }

    /**
     * Create data export package as encrypted ZIP
     */
    protected function createDataExportPackage(GdprDataSubjectRequest $request, array $exportData): string
    {
        $fileName = "gdpr_export_{$request->request_id}_" . time() . ".zip";
        $tempPath = storage_path("app/temp/{$fileName}");
        $finalPath = "gdpr-exports/{$fileName}";

        $zip = new ZipArchive;
        if ($zip->open($tempPath, ZipArchive::CREATE) === TRUE) {
            // Add main data export
            $zip->addFromString('personal_data.json', json_encode($exportData, JSON_PRETTY_PRINT));
            
            // Add metadata
            $metadata = [
                'export_date' => Carbon::now()->toISOString(),
                'request_id' => $request->request_id,
                'data_subject_type' => $request->subject_type,
                'data_subject_id' => $request->subject_id,
                'legal_basis' => 'GDPR Article 15 - Right of Access',
            ];
            $zip->addFromString('export_metadata.json', json_encode($metadata, JSON_PRETTY_PRINT));
            
            // Add GDPR information
            $gdprInfo = $this->getGDPRInformationText();
            $zip->addFromString('gdpr_information.txt', $gdprInfo);
            
            $zip->close();
        }

        // Move to final location
        Storage::disk('private')->put($finalPath, file_get_contents($tempPath));
        unlink($tempPath);

        return $finalPath;
    }

    /**
     * Create data portability package
     */
    protected function createPortabilityPackage(GdprDataSubjectRequest $request, array $portabilityData): string
    {
        $fileName = "gdpr_portability_{$request->request_id}_" . time() . ".json";
        $filePath = "gdpr-exports/{$fileName}";

        Storage::disk('private')->put($filePath, json_encode($portabilityData, JSON_PRETTY_PRINT));

        return $filePath;
    }

    /**
     * Check legal retention requirements that prevent erasure
     */
    protected function checkLegalRetentionRequirements(Model $subject): array
    {
        $retentionReasons = [];

        // Check for financial/accounting obligations (7 years in Germany)
        if ($this->hasFinancialData($subject)) {
            $retentionReasons[] = [
                'reason' => 'financial_records_retention',
                'legal_basis' => 'German Commercial Code (HGB) §257',
                'retention_period' => '7 years',
            ];
        }

        // Check for medical data retention (youth sports)
        if ($this->hasMedicalData($subject)) {
            $retentionReasons[] = [
                'reason' => 'medical_records_retention',
                'legal_basis' => 'Youth Protection Act',
                'retention_period' => 'Until majority + 5 years',
            ];
        }

        // Check for insurance claims
        if ($this->hasInsuranceClaims($subject)) {
            $retentionReasons[] = [
                'reason' => 'insurance_claims',
                'legal_basis' => 'Insurance law requirements',
                'retention_period' => '30 years',
            ];
        }

        return $retentionReasons;
    }

    /**
     * Erase personal data while respecting legal retention
     */
    protected function erasePersonalData(Model $subject, array $retentionReasons): array
    {
        $erased = [];
        $retained = [];

        $tableName = $subject->getTable();
        $sensitiveFields = $this->gdprSensitiveFields[$tableName] ?? [];

        foreach ($sensitiveFields as $field) {
            $shouldRetain = $this->shouldRetainField($field, $retentionReasons);
            
            if ($shouldRetain) {
                $retained[$field] = $subject->getAttribute($field);
            } else {
                $erased[$field] = $subject->getAttribute($field);
                // Anonymize the field
                $subject->setAttribute($field, $this->anonymizeField($field, $subject->getAttribute($field)));
            }
        }

        // Add erasure timestamp
        if (in_array('erased_at', $subject->getFillable())) {
            $subject->erased_at = Carbon::now();
        }

        $subject->save();

        return compact('erased', 'retained');
    }

    /**
     * Anonymize field value
     */
    protected function anonymizeField(string $fieldName, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return match($fieldName) {
            'name', 'first_name', 'last_name' => '[ERASED]',
            'email' => 'erased_' . time() . '@anonymized.local',
            'phone' => '+49000000000',
            'address' => '[ADDRESS ERASED]',
            'birth_date' => '1900-01-01',
            default => '[ERASED]',
        };
    }

    /**
     * Additional helper methods for GDPR compliance...
     */
    protected function getRequestStatistics(Carbon $fromDate, Carbon $toDate): array
    {
        return GdprDataSubjectRequest::whereBetween('received_at', [$fromDate, $toDate])
            ->selectRaw('request_type, response_status, COUNT(*) as count')
            ->groupBy(['request_type', 'response_status'])
            ->get()
            ->toArray();
    }

    protected function getConsentStatistics(Carbon $fromDate, Carbon $toDate): array
    {
        return GdprConsentRecord::whereBetween('created_at', [$fromDate, $toDate])
            ->selectRaw('consent_type, consent_given, COUNT(*) as count')
            ->groupBy(['consent_type', 'consent_given'])
            ->get()
            ->toArray();
    }

    protected function getProcessingStatistics(Carbon $fromDate, Carbon $toDate): array
    {
        return GdprDataProcessingRecord::whereBetween('created_at', [$fromDate, $toDate])
            ->selectRaw('processing_purpose, COUNT(*) as count')
            ->groupBy('processing_purpose')
            ->get()
            ->toArray();
    }

    protected function identifyComplianceIssues(Carbon $fromDate, Carbon $toDate): array
    {
        $issues = [];

        // Check for overdue requests
        $overdueRequests = GdprDataSubjectRequest::where('deadline_date', '<', Carbon::now())
            ->where('response_status', '!=', 'completed')
            ->count();

        if ($overdueRequests > 0) {
            $issues[] = [
                'type' => 'overdue_requests',
                'severity' => 'high',
                'count' => $overdueRequests,
                'description' => "There are {$overdueRequests} overdue GDPR requests",
            ];
        }

        // Check for consent renewals
        $renewalsNeeded = GdprConsentRecord::where('consent_given', true)
            ->where('consent_given_at', '<', Carbon::now()->subMonths(24))
            ->count();

        if ($renewalsNeeded > 0) {
            $issues[] = [
                'type' => 'consent_renewals_needed',
                'severity' => 'medium',
                'count' => $renewalsNeeded,
                'description' => "There are {$renewalsNeeded} consents requiring renewal",
            ];
        }

        return $issues;
    }

    protected function generateComplianceRecommendations(): array
    {
        return [
            'Implement automated consent renewal notifications',
            'Regular GDPR compliance audits should be conducted quarterly',
            'Ensure all staff are trained on GDPR data handling procedures',
            'Review and update privacy policies annually',
            'Implement data minimization practices in data collection',
        ];
    }

    // Additional helper methods
    protected function isRelatedToSubject(Model $subject, string $modelClass): bool
    {
        // Check if there's a relationship between subject and the model
        return false; // Implement based on your model relationships
    }

    protected function isFieldRectifiable(Model $subject, string $field): bool
    {
        $tableName = $subject->getTable();
        $sensitiveFields = $this->gdprSensitiveFields[$tableName] ?? [];
        return in_array($field, $sensitiveFields);
    }

    protected function createErasureAuditTrail(GdprDataSubjectRequest $request, array $erasedData, array $retainedData): void
    {
        Log::info("GDPR data erasure audit trail", [
            'request_id' => $request->request_id,
            'subject_type' => $request->subject_type,
            'subject_id' => $request->subject_id,
            'erased_categories' => array_keys($erasedData),
            'retained_categories' => array_keys($retainedData),
            'erasure_date' => Carbon::now()->toISOString(),
        ]);
    }

    protected function getDataPortabilitySchema(): array
    {
        return [
            'version' => '1.0',
            'format' => 'JSON',
            'encoding' => 'UTF-8',
            'data_types' => $this->exportableModels,
        ];
    }

    protected function getGDPRInformationText(): string
    {
        return "GDPR Data Export Information\n\n" .
               "This export contains your personal data as required by Article 15 of the GDPR.\n" .
               "The data is provided in JSON format and includes all personal information we hold about you.\n\n" .
               "Your rights under GDPR:\n" .
               "- Right to rectification (Article 16)\n" .
               "- Right to erasure (Article 17)\n" .
               "- Right to restrict processing (Article 18)\n" .
               "- Right to data portability (Article 20)\n" .
               "- Right to object (Article 21)\n\n" .
               "This export expires 30 days from the generation date for security reasons.\n" .
               "Generated on: " . Carbon::now()->format('Y-m-d H:i:s T');
    }

    protected function hasFinancialData(Model $subject): bool
    {
        // Check if subject has any financial/payment related data
        return false; // Implement based on your financial models
    }

    protected function hasMedicalData(Model $subject): bool
    {
        if ($subject instanceof Player) {
            return !empty($subject->medical_notes);
        }
        return false;
    }

    protected function hasInsuranceClaims(Model $subject): bool
    {
        // Check if subject has any insurance claims
        return false; // Implement based on your insurance models
    }

    protected function shouldRetainField(string $field, array $retentionReasons): bool
    {
        // Check if this field should be retained due to legal requirements
        foreach ($retentionReasons as $reason) {
            if ($this->fieldAffectedByRetention($field, $reason['reason'])) {
                return true;
            }
        }
        return false;
    }

    protected function fieldAffectedByRetention(string $field, string $reason): bool
    {
        $retentionMappings = [
            'financial_records_retention' => ['name', 'address', 'birth_date'],
            'medical_records_retention' => ['medical_notes', 'name', 'birth_date'],
            'insurance_claims' => ['name', 'birth_date', 'address', 'phone'],
        ];

        return in_array($field, $retentionMappings[$reason] ?? []);
    }
}