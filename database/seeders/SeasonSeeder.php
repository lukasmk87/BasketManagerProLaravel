<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\Season;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SeasonSeeder extends Seeder
{
    /**
     * Erstellt eine Default-Season für jeden Club ohne existierende Season.
     */
    public function run(): void
    {
        $currentYear = (int) now()->format('Y');
        $seasonName = $currentYear.'/'.$currentYear + 1;

        $clubsWithoutSeason = Club::whereDoesntHave('seasons')->get();

        foreach ($clubsWithoutSeason as $club) {
            Season::create([
                'club_id' => $club->id,
                'name' => $seasonName,
                'start_date' => Carbon::create($currentYear, 9, 1),
                'end_date' => Carbon::create($currentYear + 1, 6, 30),
                'status' => 'active',
                'is_current' => true,
                'description' => 'Automatisch erstellte Saison',
            ]);

            $this->command->info("Season '{$seasonName}' für Club '{$club->name}' erstellt.");
        }

        if ($clubsWithoutSeason->isEmpty()) {
            $this->command->info('Alle Clubs haben bereits eine Season.');
        }
    }
}
