<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('player_absences', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')
                ->nullable()
                ->constrained('tenants')
                ->onDelete('cascade');
            $table->foreignId('player_id')
                ->constrained()
                ->onDelete('cascade');

            $table->enum('type', [
                'vacation',   // Urlaub
                'illness',    // Krankheit
                'injury',     // Verletzung
                'personal',   // Persönlich
                'other'       // Sonstiges
            ])->default('other');

            $table->date('start_date');
            $table->date('end_date');
            $table->text('notes')->nullable();          // Interne Notizen des Spielers
            $table->string('reason', 255)->nullable();  // Kurze Begründung für Trainer

            $table->timestamps();
            $table->softDeletes();

            // Indexes für Performance
            $table->index(['tenant_id', 'player_id']);
            $table->index(['player_id', 'start_date', 'end_date']);
            $table->index(['start_date', 'end_date']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_absences');
    }
};
