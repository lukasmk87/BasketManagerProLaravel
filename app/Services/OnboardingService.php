<?php

namespace App\Services;

use App\Models\Club;
use App\Models\ClubSubscriptionPlan;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Club\ClubCrudService;

class OnboardingService
{
    public function __construct(
        private ClubCrudService $clubCrudService,
        private TeamService $teamService
    ) {}

    /**
     * Get the available age groups for team creation.
     */
    public function getAgeGroups(): array
    {
        return [
            'U8' => 'U8 (unter 8 Jahre)',
            'U10' => 'U10 (unter 10 Jahre)',
            'U12' => 'U12 (unter 12 Jahre)',
            'U14' => 'U14 (unter 14 Jahre)',
            'U16' => 'U16 (unter 16 Jahre)',
            'U18' => 'U18 (unter 18 Jahre)',
            'U20' => 'U20 (unter 20 Jahre)',
            'Herren' => 'Herren',
            'Damen' => 'Damen',
        ];
    }

    /**
     * Get available subscription plans for a tenant.
     */
    public function getAvailablePlans(?Tenant $tenant = null): array
    {
        $query = ClubSubscriptionPlan::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price');

        if ($tenant) {
            $query->where('tenant_id', $tenant->id);
        }

        return $query->get()->toArray();
    }

    /**
     * Get the default free plan for a tenant.
     */
    public function getFreePlan(?Tenant $tenant = null): ?ClubSubscriptionPlan
    {
        $query = ClubSubscriptionPlan::where('is_active', true)
            ->where(function ($q) {
                $q->where('price', 0)
                  ->orWhere('slug', 'free-club')
                  ->orWhere('is_default', true);
            })
            ->orderBy('price');

        if ($tenant) {
            $query->where('tenant_id', $tenant->id);
        }

        return $query->first();
    }

    /**
     * Create a club during onboarding.
     */
    public function createClubForOnboarding(array $data, User $user): Club
    {
        Log::info('OnboardingService: Creating club for onboarding', [
            'user_id' => $user->id,
            'club_name' => $data['name'] ?? 'unknown',
        ]);

        // Determine tenant
        $tenantId = $this->resolveTenantId($user);

        // Prepare club data with minimal required fields
        $clubData = [
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'address_city' => $data['city'],
            'logo_path' => $data['logo_path'] ?? null,
            'description' => $data['description'] ?? null,
            'add_current_user_as_admin' => true, // User becomes club admin
        ];

        $club = $this->clubCrudService->createClub($clubData);

        // Assign the club_admin role to the user
        if (!$user->hasRole('club_admin')) {
            $user->assignRole('club_admin');
        }

        Log::info('OnboardingService: Club created successfully', [
            'club_id' => $club->id,
            'user_id' => $user->id,
        ]);

        return $club;
    }

    /**
     * Create a team during onboarding.
     */
    public function createTeamForOnboarding(array $data, Club $club, User $user): Team
    {
        Log::info('OnboardingService: Creating team for onboarding', [
            'user_id' => $user->id,
            'club_id' => $club->id,
            'team_name' => $data['name'] ?? 'unknown',
        ]);

        // Determine current season
        $currentYear = date('Y');
        $currentMonth = (int) date('m');
        $season = $currentMonth >= 8
            ? "{$currentYear}/" . ($currentYear + 1)
            : ($currentYear - 1) . "/{$currentYear}";

        // Prepare team data
        $teamData = [
            'name' => $data['name'],
            'club_id' => $club->id,
            'age_group' => $data['age_group'],
            'gender' => $data['gender'] ?? 'mixed',
            'season' => $season,
            'is_active' => true,
        ];

        $team = $this->teamService->createTeam($teamData);

        // Assign trainer role if not already assigned
        if (!$user->hasRole('trainer')) {
            $user->assignRole('trainer');
        }

        Log::info('OnboardingService: Team created successfully', [
            'team_id' => $team->id,
            'club_id' => $club->id,
            'user_id' => $user->id,
        ]);

        return $team;
    }

    /**
     * Assign a subscription plan to a club.
     * Returns checkout URL for paid plans, null for free plans.
     */
    public function selectPlanForOnboarding(ClubSubscriptionPlan $plan, Club $club, User $user): ?string
    {
        Log::info('OnboardingService: Selecting plan for club', [
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'club_id' => $club->id,
            'price' => $plan->price,
        ]);

        // Free plan - directly assign
        if ((float) $plan->price === 0.0) {
            $club->update([
                'club_subscription_plan_id' => $plan->id,
            ]);

            // Mark onboarding as complete
            $user->markOnboardingComplete();

            Log::info('OnboardingService: Free plan assigned, onboarding complete', [
                'club_id' => $club->id,
                'plan_id' => $plan->id,
            ]);

            return null;
        }

        // Paid plan - return checkout URL (will be handled by ClubCheckoutController)
        // The onboarding_completed_at will be set via webhook after successful payment
        return route('club.checkout.create', [
            'club' => $club->id,
            'plan' => $plan->id,
            'onboarding' => true,
        ]);
    }

    /**
     * Assign free plan and mark onboarding as complete.
     */
    public function completeOnboardingWithFreePlan(Club $club, User $user): void
    {
        $freePlan = $this->getFreePlan($club->tenant);

        if ($freePlan) {
            $club->update([
                'club_subscription_plan_id' => $freePlan->id,
            ]);
        }

        $user->markOnboardingComplete();

        Log::info('OnboardingService: Onboarding completed with free plan', [
            'club_id' => $club->id,
            'user_id' => $user->id,
            'plan_id' => $freePlan?->id,
        ]);
    }

    /**
     * Mark onboarding as complete for a user.
     */
    public function markOnboardingComplete(User $user): void
    {
        $user->markOnboardingComplete();

        Log::info('OnboardingService: Onboarding marked as complete', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Resolve tenant ID for new club creation.
     * Uses domain-based resolution with fallback to first active tenant.
     */
    private function resolveTenantId(User $user): string
    {
        // 1. If user already has a tenant, use it
        if ($user->tenant_id) {
            return $user->tenant_id;
        }

        // 2. Try to get from app container (set by middleware)
        $tenant = app()->bound('tenant') ? app('tenant') : null;
        if ($tenant) {
            // Assign tenant to user
            $user->update(['tenant_id' => $tenant->id]);
            return $tenant->id;
        }

        // 3. Domain-based resolution with fallback
        $currentDomain = request()->getHost();
        $tenant = Tenant::resolveDefaultTenant($currentDomain);

        if (!$tenant) {
            Log::error('OnboardingService: No tenant found', [
                'user_id' => $user->id,
                'domain' => $currentDomain,
            ]);
            throw new \RuntimeException(
                'Kein aktiver Mandant gefunden. Bitte stellen Sie sicher, dass die Installation abgeschlossen ist ' .
                'oder konfigurieren Sie einen Standard-Mandanten in der .env Datei (DEFAULT_TENANT_DOMAIN).'
            );
        }

        // Assign tenant to user
        $user->update(['tenant_id' => $tenant->id]);

        Log::info('OnboardingService: User assigned to tenant via domain resolution', [
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'domain' => $currentDomain,
        ]);

        return $tenant->id;
    }

    /**
     * Get onboarding progress for a user.
     */
    public function getOnboardingProgress(User $user): array
    {
        $clubs = $user->clubs()->get();
        $hasClub = $clubs->isNotEmpty();
        $club = $clubs->first();

        $hasTeam = false;
        $hasPlan = false;

        if ($club) {
            $hasTeam = Team::where('club_id', $club->id)->exists();
            $hasPlan = $club->club_subscription_plan_id !== null;
        }

        return [
            'has_club' => $hasClub,
            'has_team' => $hasTeam,
            'has_plan' => $hasPlan,
            'club' => $club,
            'current_step' => $this->determineCurrentStep($hasClub, $hasTeam, $hasPlan),
            'is_complete' => $user->hasCompletedOnboarding(),
        ];
    }

    /**
     * Determine the current onboarding step.
     */
    private function determineCurrentStep(bool $hasClub, bool $hasTeam, bool $hasPlan): int
    {
        if (!$hasClub) {
            return 1;
        }

        if (!$hasTeam) {
            return 2;
        }

        if (!$hasPlan) {
            return 3;
        }

        return 3; // All complete
    }
}
