<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration creates the enterprise_leads table for managing
     * enterprise/federation leads in the admin panel.
     */
    public function up(): void
    {
        Schema::create('enterprise_leads', function (Blueprint $table) {
            $table->id();
            $table->string('organization_name');
            $table->enum('organization_type', ['verband', 'grossverein', 'akademie', 'sonstige']);
            $table->string('contact_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('club_count')->nullable();
            $table->string('team_count')->nullable();
            $table->text('message')->nullable();
            $table->boolean('gdpr_accepted')->default(false);
            $table->boolean('newsletter_optin')->default(false);
            $table->enum('status', ['new', 'contacted', 'qualified', 'proposal', 'won', 'lost'])->default('new');
            $table->text('notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('organization_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enterprise_leads');
    }
};
