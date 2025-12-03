<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'pricing.display_mode',
                'value' => 'gross',
                'type' => 'string',
                'group' => 'pricing',
                'description' => 'Preisanzeige-Modus: gross = inkl. MwSt., net = exkl. MwSt.',
            ],
            [
                'key' => 'pricing.is_small_business',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'pricing',
                'description' => 'Kleinunternehmer-Regelung nach §19 UStG - keine MwSt.-Ausweisung',
            ],
            [
                'key' => 'pricing.default_tax_rate',
                'value' => '19.00',
                'type' => 'decimal',
                'group' => 'pricing',
                'description' => 'Standard-MwSt.-Satz in Prozent',
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
