<?php

namespace App\Console\Commands;

use App\Services\Install\InstallationService;
use Illuminate\Console\Command;

class UnlockInstallationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install:unlock {--force : Force unlock without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unlock the installation to allow reinstallation';

    /**
     * Execute the console command.
     */
    public function handle(InstallationService $installationService): int
    {
        $this->info('BasketManager Pro - Installation Unlock');
        $this->newLine();

        // Check if installation is locked
        if (! $installationService->isInstalled()) {
            $this->warn('Installation is not locked. No action needed.');

            return self::SUCCESS;
        }

        // Display warning
        $this->warn('⚠️  WARNING: This will unlock the installation and allow you to reinstall.');
        $this->warn('⚠️  All existing data will remain in the database unless you manually drop tables.');
        $this->warn('⚠️  This action is intended for development purposes only.');
        $this->newLine();

        // Request confirmation unless --force is used
        if (! $this->option('force')) {
            if (! $this->confirm('Are you sure you want to unlock the installation?', false)) {
                $this->info('Operation cancelled.');

                return self::FAILURE;
            }
        }

        try {
            // Perform unlock
            $installationService->unlockInstallation();

            $this->info('✅ Installation unlocked successfully!');
            $this->newLine();
            $this->info('You can now access the installation wizard at: '.route('install.index'));
            $this->newLine();
            $this->comment('Note: If you want a completely fresh installation, run the following commands:');
            $this->line('  php artisan migrate:fresh');
            $this->line('  php artisan db:seed');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to unlock installation: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
