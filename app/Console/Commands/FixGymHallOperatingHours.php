<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GymHall;

class FixGymHallOperatingHours extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gym:fix-operating-hours {hall_name?} {--normalize : Remove all day-specific parallel booking settings}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix operating hours configuration for gym halls, especially parallel booking settings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hallName = $this->argument('hall_name');
        $normalize = $this->option('normalize');

        if ($hallName) {
            $hall = GymHall::where('name', $hallName)->first();
            
            if (!$hall) {
                $this->error("Hall '{$hallName}' not found.");
                return 1;
            }
            
            $this->fixHall($hall, $normalize);
        } else {
            $halls = GymHall::whereNotNull('operating_hours')->get();
            
            if ($halls->isEmpty()) {
                $this->info('No halls found with operating_hours configuration.');
                return 0;
            }

            foreach ($halls as $hall) {
                $this->fixHall($hall, $normalize);
            }
        }

        return 0;
    }

    private function fixHall(GymHall $hall, bool $normalize)
    {
        $this->info("Processing hall: {$hall->name}");
        
        $this->line("  Global parallel bookings: " . ($hall->supports_parallel_bookings ? 'Enabled' : 'Disabled'));
        
        // Check each day for specific settings
        $daysWithSpecificSettings = [];
        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
            if ($hall->operating_hours && isset($hall->operating_hours[$day]['supports_parallel_bookings'])) {
                $dayEnabled = $hall->operating_hours[$day]['supports_parallel_bookings'];
                $this->line("  {$day}: " . ($dayEnabled ? 'Enabled' : 'Disabled') . ' (day-specific)');
                $daysWithSpecificSettings[] = $day;
            }
        }

        if (empty($daysWithSpecificSettings)) {
            $this->line("  No day-specific parallel booking settings found.");
            return;
        }

        if ($normalize) {
            $this->info("  Normalizing operating hours (removing day-specific settings)...");
            if ($hall->normalizeOperatingHoursParallelBookings()) {
                $this->info("  ✓ Successfully normalized operating hours for {$hall->name}");
            } else {
                $this->error("  ✗ Failed to normalize operating hours for {$hall->name}");
            }
        } else {
            $this->warn("  Hall has day-specific parallel booking settings. Use --normalize to remove them.");
            $this->line("  Days with specific settings: " . implode(', ', $daysWithSpecificSettings));
        }
        
        $this->line('');
    }
}