<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playbook_plays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('playbook_id')->constrained()->onDelete('cascade');
            $table->foreignId('play_id')->constrained()->onDelete('cascade');

            // Order within playbook
            $table->integer('order')->default(0);

            // Notes specific to this play in this playbook
            $table->text('notes')->nullable();

            $table->timestamps();

            // Unique constraint to prevent duplicates
            $table->unique(['playbook_id', 'play_id']);

            // Index for ordering
            $table->index(['playbook_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playbook_plays');
    }
};
