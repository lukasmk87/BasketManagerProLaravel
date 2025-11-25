<?php

namespace Tests\Feature;

use App\Models\BasketballTeam;
use App\Models\Club;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VideoFile;
use App\Observers\VideoFileObserver;
use App\Services\ClubUsageTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * SEC-008: Storage Usage Feature Tests.
 *
 * Tests for storage calculation, automatic tracking, and limit enforcement.
 *
 * @see SECURITY_AND_PERFORMANCE_FIXES.md SEC-008
 */
class SEC008StorageUsageTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Club $club;
    protected BasketballTeam $team;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure installation marker exists
        if (!file_exists(storage_path('installed'))) {
            file_put_contents(storage_path('installed'), date('Y-m-d H:i:s'));
        }

        $this->setupTestData();
    }

    protected function setupTestData(): void
    {
        // Create roles
        foreach (['super_admin', 'admin', 'club_admin', 'trainer', 'player'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // Create tenant and club
        $this->tenant = Tenant::factory()->create();
        $this->club = Club::factory()->create(['tenant_id' => $this->tenant->id]);

        // Create user
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user->assignRole('trainer');
        $this->club->users()->attach($this->user->id, [
            'role' => 'admin',
            'joined_at' => now(),
            'is_active' => true,
        ]);

        // Create team
        $this->team = BasketballTeam::factory()->create([
            'club_id' => $this->club->id,
            'tenant_id' => $this->tenant->id,
            'head_coach_id' => $this->user->id,
        ]);

        // Use fake storage for tests
        Storage::fake('public');
    }

    /** @test */
    public function calculate_storage_usage_includes_video_files(): void
    {
        // Create video files with known sizes
        VideoFile::factory()->create([
            'team_id' => $this->team->id,
            'file_size' => 1024 * 1024 * 1024, // 1 GB
        ]);

        VideoFile::factory()->create([
            'team_id' => $this->team->id,
            'file_size' => 512 * 1024 * 1024, // 0.5 GB
        ]);

        $storageGB = $this->club->calculateStorageUsage();

        $this->assertEquals(1.5, $storageGB);
    }

    /** @test */
    public function calculate_storage_usage_returns_zero_for_club_without_files(): void
    {
        $storageGB = $this->club->calculateStorageUsage();

        $this->assertEquals(0.0, $storageGB);
    }

    /** @test */
    public function calculate_storage_usage_only_includes_own_club_files(): void
    {
        // Create another club with a team and video
        $otherClub = Club::factory()->create(['tenant_id' => $this->tenant->id]);
        $otherTeam = BasketballTeam::factory()->create([
            'club_id' => $otherClub->id,
            'tenant_id' => $this->tenant->id,
        ]);

        // Video for other club
        VideoFile::factory()->create([
            'team_id' => $otherTeam->id,
            'file_size' => 2 * 1024 * 1024 * 1024, // 2 GB
        ]);

        // Video for our club
        VideoFile::factory()->create([
            'team_id' => $this->team->id,
            'file_size' => 1024 * 1024 * 1024, // 1 GB
        ]);

        // Our club should only see 1 GB
        $ourStorage = $this->club->calculateStorageUsage();
        $otherStorage = $otherClub->calculateStorageUsage();

        $this->assertEquals(1.0, $ourStorage);
        $this->assertEquals(2.0, $otherStorage);
    }

    /** @test */
    public function video_file_observer_tracks_storage_on_create(): void
    {
        $usageService = app(ClubUsageTrackingService::class);

        // Get initial usage
        $initialUsage = $usageService->getCurrentUsage($this->club, 'max_storage_gb');

        // Create a video file (observer should track it)
        $video = VideoFile::factory()->create([
            'team_id' => $this->team->id,
            'file_size' => 1024 * 1024 * 1024, // 1 GB
        ]);

        // Check usage increased
        $newUsage = $usageService->getCurrentUsage($this->club, 'max_storage_gb');

        $this->assertGreaterThan($initialUsage, $newUsage);
    }

    /** @test */
    public function video_file_observer_untracks_storage_on_delete(): void
    {
        $usageService = app(ClubUsageTrackingService::class);

        // Create a video file
        $video = VideoFile::factory()->create([
            'team_id' => $this->team->id,
            'file_size' => 1024 * 1024 * 1024, // 1 GB
        ]);

        // Get usage after creation
        $usageAfterCreate = $usageService->getCurrentUsage($this->club, 'max_storage_gb');

        // Delete the video
        $video->delete();

        // Get usage after deletion
        $usageAfterDelete = $usageService->getCurrentUsage($this->club, 'max_storage_gb');

        $this->assertLessThan($usageAfterCreate, $usageAfterDelete);
    }

    /** @test */
    public function sync_storage_command_updates_club_usage(): void
    {
        // Create video files directly without observer
        VideoFile::withoutEvents(function () {
            VideoFile::factory()->create([
                'team_id' => $this->team->id,
                'file_size' => 2 * 1024 * 1024 * 1024, // 2 GB
            ]);
        });

        // Run the sync command
        $this->artisan('club:sync-storage', ['--club' => $this->club->id])
            ->assertExitCode(0);

        // Check that usage is now synced
        $usageService = app(ClubUsageTrackingService::class);
        $currentUsage = $usageService->getCurrentUsage($this->club, 'max_storage_gb');

        $this->assertEquals(2.0, $currentUsage);
    }

    /** @test */
    public function sync_storage_command_supports_dry_run(): void
    {
        VideoFile::withoutEvents(function () {
            VideoFile::factory()->create([
                'team_id' => $this->team->id,
                'file_size' => 1024 * 1024 * 1024, // 1 GB
            ]);
        });

        // Run in dry-run mode
        $this->artisan('club:sync-storage', ['--dry-run' => true])
            ->assertExitCode(0)
            ->expectsOutput('[DRY RUN] No changes were made.');
    }
}
