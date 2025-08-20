<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gym_halls', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('club_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('address_street')->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_zip')->nullable();
            $table->string('address_country')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('capacity')->nullable();
            $table->json('facilities')->nullable(); // Umkleide, Duschen, etc.
            $table->json('equipment')->nullable(); // Körbe, Bälle, etc.
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();
            $table->json('operating_hours')->nullable(); // Wochentage mit Öffnungszeiten
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_key')->default(false);
            $table->text('access_instructions')->nullable();
            $table->text('special_rules')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['club_id', 'is_active']);
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gym_halls');
    }
};