<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('playbooks', function (Blueprint $table) {
            $table->enum('status', ['draft', 'published', 'archived'])
                ->default('published')
                ->after('is_default');
        });
    }

    public function down(): void
    {
        Schema::table('playbooks', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
