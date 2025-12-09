<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Benennt die 'admin' Rolle in 'tenant_admin' um.
     * Dies ist Teil des Berechtigungs-Refactorings für die neue Hierarchie:
     * Super Admin -> Tenant Admin -> Club Admin
     */
    public function up(): void
    {
        // Rolle umbenennen in der roles Tabelle (Spatie Permission)
        DB::table('roles')
            ->where('name', 'admin')
            ->where('guard_name', 'web')
            ->update(['name' => 'tenant_admin']);

        // Falls es auch für andere guards existiert
        DB::table('roles')
            ->where('name', 'admin')
            ->where('guard_name', 'sanctum')
            ->update(['name' => 'tenant_admin']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('roles')
            ->where('name', 'tenant_admin')
            ->where('guard_name', 'web')
            ->update(['name' => 'admin']);

        DB::table('roles')
            ->where('name', 'tenant_admin')
            ->where('guard_name', 'sanctum')
            ->update(['name' => 'admin']);
    }
};
