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
        Schema::table('invoice_requests', function (Blueprint $table) {
            $table->enum('billing_interval', ['monthly', 'yearly'])->nullable()->after('vat_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_requests', function (Blueprint $table) {
            $table->dropColumn('billing_interval');
        });
    }
};
