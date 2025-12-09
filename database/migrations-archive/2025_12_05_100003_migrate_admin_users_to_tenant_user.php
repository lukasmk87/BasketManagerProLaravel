<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migriert bestehende Admin-User in die tenant_user Pivot-Tabelle.
     * Für jeden User mit der tenant_admin Rolle wird ein Eintrag erstellt,
     * der ihn mit seinem aktuellen Tenant (via user.tenant_id) verknüpft.
     */
    public function up(): void
    {
        // Finde die tenant_admin Rolle (wurde in vorheriger Migration umbenannt)
        $tenantAdminRole = DB::table('roles')
            ->where('name', 'tenant_admin')
            ->where('guard_name', 'web')
            ->first();

        if (!$tenantAdminRole) {
            // Falls die Rolle noch nicht existiert, versuche mit dem alten Namen
            $tenantAdminRole = DB::table('roles')
                ->where('name', 'admin')
                ->where('guard_name', 'web')
                ->first();
        }

        if (!$tenantAdminRole) {
            return; // Keine Rolle gefunden, nichts zu migrieren
        }

        // Finde alle User mit der tenant_admin/admin Rolle
        $adminUserIds = DB::table('model_has_roles')
            ->where('role_id', $tenantAdminRole->id)
            ->where('model_type', 'App\\Models\\User')
            ->pluck('model_id');

        // Hole User-Daten mit tenant_id
        $adminUsers = DB::table('users')
            ->whereIn('id', $adminUserIds)
            ->whereNotNull('tenant_id')
            ->get(['id', 'tenant_id', 'created_at']);

        // Erstelle tenant_user Einträge
        foreach ($adminUsers as $user) {
            // Prüfe ob Eintrag bereits existiert
            $exists = DB::table('tenant_user')
                ->where('tenant_id', $user->tenant_id)
                ->where('user_id', $user->id)
                ->exists();

            if (!$exists) {
                DB::table('tenant_user')->insert([
                    'tenant_id' => $user->tenant_id,
                    'user_id' => $user->id,
                    'role' => 'tenant_admin',
                    'joined_at' => $user->created_at ?? now(),
                    'is_active' => true,
                    'is_primary' => true, // Da es der einzige zugewiesene Tenant ist
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optional: Lösche alle tenant_user Einträge die automatisch erstellt wurden
        // Wir löschen nur Einträge mit is_primary = true, da diese automatisch erstellt wurden
        DB::table('tenant_user')
            ->where('is_primary', true)
            ->where('role', 'tenant_admin')
            ->delete();
    }
};
