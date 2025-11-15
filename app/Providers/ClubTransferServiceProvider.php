<?php

namespace App\Providers;

use App\Events\ClubTransferCompleted;
use App\Events\ClubTransferFailed;
use App\Events\ClubTransferInitiated;
use App\Events\ClubTransferRolledBack;
use App\Listeners\CleanupExpiredRollbackData;
use App\Listeners\NotifySuperAdminsOfTransferCompletion;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class ClubTransferServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        ClubTransferCompleted::class => [
            NotifySuperAdminsOfTransferCompletion::class,
            CleanupExpiredRollbackData::class,
        ],

        ClubTransferFailed::class => [
            CleanupExpiredRollbackData::class,
        ],

        ClubTransferRolledBack::class => [
            // Add listeners if needed
        ],

        ClubTransferInitiated::class => [
            // Add listeners if needed
        ],
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        parent::boot();
    }
}
