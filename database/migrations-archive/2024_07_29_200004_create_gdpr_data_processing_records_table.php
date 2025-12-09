<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gdpr_data_processing_records', function (Blueprint $table) {
            $table->id();
            
            // Processing Activity Information
            $table->string('activity_name');
            $table->text('activity_description');
            $table->enum('processing_purpose', [
                'club_management', 'emergency_contacts', 'game_statistics',
                'training_records', 'communication', 'legal_compliance',
                'performance_analysis', 'medical_information', 'other'
            ]);
            $table->json('legal_basis')->nullable(); // Art. 6 GDPR legal bases
            $table->json('special_category_basis')->nullable(); // Art. 9 GDPR if applicable
            
            // Data Categories
            $table->json('data_categories'); // Personal data categories processed
            $table->json('data_subjects'); // Categories of data subjects
            $table->json('recipients'); // Categories of recipients
            
            // International Transfers
            $table->boolean('international_transfers')->default(false);
            $table->json('transfer_destinations')->nullable();
            $table->json('transfer_safeguards')->nullable();
            
            // Retention
            $table->string('retention_period');
            $table->text('retention_criteria');
            $table->date('next_review_date');
            
            // Security Measures
            $table->json('technical_measures');
            $table->json('organizational_measures');
            
            // Responsible Parties
            $table->foreignId('controller_user_id')->nullable()->constrained('users');
            $table->string('processor_details')->nullable();
            $table->string('dpo_contact')->nullable(); // Data Protection Officer
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_reviewed_at')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['processing_purpose', 'is_active']);
            $table->index('next_review_date');
            $table->index(['controller_user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gdpr_data_processing_records');
    }
};