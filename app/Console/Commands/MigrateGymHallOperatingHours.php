<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GymHall;

class MigrateGymHallOperatingHours extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gym:migrate-operating-hours 
                            {--dry-run : Run without making changes}
                            {--enable-parallel-bookings : Enable parallel bookings by default instead of using global setting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate gym hall operating hours to include day-specific parallel booking settings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $enableParallelBookings = $this->option('enable-parallel-bookings');
        
        if ($dryRun) {
            $this->info('Running in DRY RUN mode - no changes will be made');
        }

        if ($enableParallelBookings) {
            $this->info('Using enabled parallel bookings as default for all days');
        }

        $this->info('Migrating gym hall operating hours...');

        $gymHalls = GymHall::all();
        
        if ($gymHalls->count() === 0) {
            $this->info('No gym halls found to migrate.');
            return 0;
        }

        $progressBar = $this->output->createProgressBar($gymHalls->count());
        $migratedCount = 0;
        $skippedCount = 0;

        foreach ($gymHalls as $gymHall) {
            $this->migrateGymHall($gymHall, $dryRun, $enableParallelBookings, $migratedCount, $skippedCount);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Migration completed:");
        $this->info("- Migrated: {$migratedCount} gym halls");
        $this->info("- Skipped: {$skippedCount} gym halls (already have day-specific settings)");
        
        if ($dryRun) {
            $this->warn('This was a DRY RUN - no actual changes were made.');
            $this->info('Run without --dry-run flag to perform the migration.');
        }

        return 0;
    }

    private function migrateGymHall(GymHall $gymHall, bool $dryRun, bool $enableParallelBookings, int &$migratedCount, int &$skippedCount)
    {
        // Check if this gym hall already has day-specific parallel booking settings
        if ($this->hasModernOperatingHours($gymHall)) {
            $skippedCount++;
            return;
        }

        $this->line("Migrating: {$gymHall->name}");

        // Get the parallel booking setting
        if ($enableParallelBookings) {
            $globalParallelBookings = true; // Force enable for all days
        } else {
            $globalParallelBookings = $gymHall->supports_parallel_bookings ?? true; // Default to enabled
        }

        // If no operating hours exist, create basic structure
        if (!$gymHall->operating_hours) {
            $operatingHours = $this->createBasicOperatingHours($gymHall, $globalParallelBookings);
        } else {
            // Extend existing operating hours with parallel booking settings
            $operatingHours = $this->extendOperatingHours($gymHall->operating_hours, $globalParallelBookings);
        }

        if (!$dryRun) {
            $gymHall->update(['operating_hours' => $operatingHours]);
        }

        $this->line("  → Added parallel booking settings for all days (default: " . 
                   ($globalParallelBookings ? 'enabled' : 'disabled') . ")");

        $migratedCount++;
    }

    private function hasModernOperatingHours(GymHall $gymHall): bool
    {
        if (!$gymHall->operating_hours) {
            return false;
        }

        // Check if at least one day has the supports_parallel_bookings key
        foreach ($gymHall->operating_hours as $daySettings) {
            if (is_array($daySettings) && array_key_exists('supports_parallel_bookings', $daySettings)) {
                return true;
            }
        }

        return false;
    }

    private function createBasicOperatingHours(GymHall $gymHall, bool $globalParallelBookings): array
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $operatingHours = [];

        $defaultOpenTime = $gymHall->opening_time?->format('H:i') ?? '08:00';
        $defaultCloseTime = $gymHall->closing_time?->format('H:i') ?? '22:00';

        foreach ($days as $day) {
            $operatingHours[$day] = [
                'is_open' => true,
                'open_time' => $defaultOpenTime,
                'close_time' => $defaultCloseTime,
                'supports_parallel_bookings' => $globalParallelBookings
            ];
        }

        return $operatingHours;
    }

    private function extendOperatingHours(array $existingHours, bool $globalParallelBookings): array
    {
        $operatingHours = $existingHours;

        foreach ($operatingHours as $day => $settings) {
            if (is_array($settings) && !array_key_exists('supports_parallel_bookings', $settings)) {
                $operatingHours[$day]['supports_parallel_bookings'] = $globalParallelBookings;
            }
        }

        return $operatingHours;
    }
}