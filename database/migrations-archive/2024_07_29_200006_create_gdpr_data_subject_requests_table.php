<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gdpr_data_subject_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_id')->unique(); // Human-readable ID like "DSR-2024-001"
            
            // Request Subject
            $table->morphs('subject'); // Player, User, etc.
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users'); // May be guardian
            
            // Request Details
            $table->enum('request_type', [
                'access',           // Article 15 - Right of access
                'rectification',    // Article 16 - Right to rectification
                'erasure',          // Article 17 - Right to erasure (Right to be forgotten)
                'restrict',         // Article 18 - Right to restriction of processing
                'portability',      // Article 20 - Right to data portability
                'object',           // Article 21 - Right to object
                'stop_automated',   // Article 22 - Rights related to automated decision making
            ]);
            $table->text('request_description');
            $table->json('specific_data_requested')->nullable(); // Specific fields/categories requested
            
            // Timing
            $table->timestamp('received_at');
            $table->timestamp('acknowledgment_sent_at')->nullable();
            $table->date('deadline_date'); // 30 days from receipt (1 month)
            $table->timestamp('completed_at')->nullable();
            
            // Identity Verification
            $table->boolean('identity_verified')->default(false);
            $table->timestamp('identity_verified_at')->nullable();
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users');
            $table->json('verification_documents')->nullable();
            $table->text('verification_notes')->nullable();
            
            // Processing Status
            $table->enum('status', [
                'received',      // Request received but not yet processed
                'verifying',     // Verifying identity
                'processing',    // Processing the request
                'on_hold',       // Waiting for clarification or additional info
                'completed',     // Request completed successfully
                'rejected',      // Request rejected (with reason)
                'partially_completed' // Some parts completed, others rejected
            ])->default('received');
            
            // Response Information
            $table->text('response_summary')->nullable();
            $table->json('data_provided')->nullable(); // What data was provided
            $table->json('actions_taken')->nullable(); // What actions were performed
            $table->text('rejection_reason')->nullable(); // If rejected, why
            
            // Files and Documentation
            $table->json('request_attachments')->nullable(); // Files provided with request
            $table->json('response_files')->nullable(); // Files provided in response
            $table->string('export_file_path')->nullable(); // Path to data export file
            
            // Communication Log
            $table->json('communication_log')->nullable(); // All communications about this request
            $table->timestamp('last_contact_at')->nullable();
            
            // Complexity and Special Handling
            $table->boolean('requires_legal_review')->default(false);
            $table->boolean('involves_third_parties')->default(false);
            $table->json('third_party_details')->nullable();
            $table->integer('complexity_score')->default(1); // 1-5 scale
            
            // Follow-up and Appeals
            $table->boolean('appeal_filed')->default(false);
            $table->timestamp('appeal_filed_at')->nullable();
            $table->text('appeal_details')->nullable();
            
            // Processing Notes
            $table->text('internal_notes')->nullable();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users');
            $table->json('processing_log')->nullable(); // Detailed processing steps
            
            $table->timestamps();
            
            // Indexes
            // morphs('subject') already creates subject_type, subject_id index
            $table->index(['request_type', 'status']);
            $table->index('received_at');
            $table->index('deadline_date');
            $table->index(['status', 'deadline_date']);
            $table->index('assigned_to_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gdpr_data_subject_requests');
    }
};