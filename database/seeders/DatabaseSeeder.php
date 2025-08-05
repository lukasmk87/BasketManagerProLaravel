<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Temporarily set Carbon locale to English for database operations
        $originalLocale = Carbon::getLocale();
        Carbon::setLocale('en');
        
        try {
            // User::factory(10)->withPersonalTeam()->create();

            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        } finally {
            // Restore original Carbon locale
            Carbon::setLocale($originalLocale);
        }
    }
}
