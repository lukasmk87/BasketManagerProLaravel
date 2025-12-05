<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Erstellt die tenant_user Pivot-Tabelle für Multi-Tenant Admin-Zuordnung.
     * Ein User kann Tenant-Admin in mehreren Tenants sein.
     */
    public function up(): void
    {
        Schema::create('tenant_user', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Rollen-Typ im Tenant
            $table->enum('role', ['tenant_admin', 'billing_admin'])->default('tenant_admin');

            // Mitgliedschaftsinformationen
            $table->date('joined_at');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_primary')->default(false); // Primärer Tenant des Users

            // Permissions innerhalb des Tenants (JSON für Erweiterbarkeit)
            $table->json('permissions')->nullable();

            // Notizen und Metadata
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Unique constraint - User kann nur eine Rolle pro Tenant haben
            $table->unique(['tenant_id', 'user_id']);

            // Indexes für häufige Abfragen
            $table->index(['tenant_id', 'role']);
            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_user');
    }
};
