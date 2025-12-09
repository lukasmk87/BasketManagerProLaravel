<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Erstellt eine dedizierte team_coaches Tabelle für Trainer-Zuordnungen.
     * Dies ermöglicht, dass ein Benutzer gleichzeitig Spieler UND Trainer sein kann
     * (z.B. Spielertrainer), ohne Datenverluste in der team_user Pivot-Tabelle.
     */
    public function up(): void
    {
        Schema::create('team_coaches', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('team_id')
                ->constrained('teams')
                ->onDelete('cascade');

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Coach Role
            $table->enum('role', ['head_coach', 'assistant_coach'])
                ->comment('Trainer-Rolle: Haupttrainer oder Co-Trainer');

            // Coaching Credentials
            $table->string('coaching_license')->nullable()
                ->comment('Trainerlizenz (z.B. C-Lizenz, B-Lizenz, A-Lizenz, Pro-Lizenz)');

            $table->json('coaching_certifications')->nullable()
                ->comment('Zusätzliche Zertifikate (z.B. Athletiktrainer, Mentalcoach, Video-Analyst)');

            $table->text('coaching_specialties')->nullable()
                ->comment('Trainings-Schwerpunkte (z.B. Verteidigungstraining, Wurftraining, Taktik)');

            // Status & Metadata
            $table->timestamp('joined_at')->nullable()
                ->comment('Datum, an dem der Trainer diesem Team beigetreten ist');

            $table->boolean('is_active')->default(true)
                ->comment('Ist der Trainer aktuell aktiv für dieses Team?');

            $table->timestamps();

            // Indexes
            // Ein Benutzer kann Head Coach UND Assistant Coach im selben Team sein
            // (z.B. wenn vorübergehend beide Rollen übernommen werden)
            $table->unique(['team_id', 'user_id', 'role'], 'team_coach_unique');

            // Performance-Index für Abfragen nach Team
            $table->index('team_id');

            // Performance-Index für Abfragen nach User (welche Teams trainiert ein User?)
            $table->index('user_id');

            // Index für aktive Coaches
            $table->index(['team_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_coaches');
    }
};
