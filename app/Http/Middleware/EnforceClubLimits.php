<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\ClubUsageTrackingService;
use App\Models\Club;
use App\Exceptions\UsageQuotaExceededException;
use Illuminate\Support\Facades\Log;

class EnforceClubLimits
{
    private ClubUsageTrackingService $usageTracker;

    public function __construct(ClubUsageTrackingService $usageTracker)
    {
        $this->usageTracker = $usageTracker;
    }

    /**
     * Handle an incoming request.
     *
     * This middleware automatically enforces club usage limits based on route patterns.
     * It extracts the club from route parameters and checks if the club can perform
     * the requested action.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $limitType = null): Response
    {
        // Only enforce on POST requests (resource creation)
        if (!$request->isMethod('POST')) {
            return $next($request);
        }

        // Get club from route parameters
        $club = $this->getClubFromRequest($request);

        if (!$club) {
            // No club found, proceed (might be system-level route)
            return $next($request);
        }

        // Determine limit type from middleware parameter or route action
        $metric = $limitType ?? $this->detectMetricFromRoute($request);

        if (!$metric) {
            // No metric detected, proceed without limit check
            return $next($request);
        }

        // Check if club can perform this action
        try {
            $this->usageTracker->requireLimit($club, $metric, 1);
        } catch (UsageQuotaExceededException $e) {
            Log::warning('Club limit exceeded', [
                'club_id' => $club->id,
                'club_name' => $club->name,
                'metric' => $metric,
                'route' => $request->route()->getName(),
                'user_id' => auth()->id(),
            ]);

            // Return JSON response for API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Usage limit exceeded',
                    'message' => $e->getMessage(),
                    'metric' => $metric,
                    'limit' => $club->getLimit($metric),
                    'current_usage' => $this->usageTracker->getCurrentUsage($club, $metric),
                    'upgrade_required' => true,
                    'upgrade_recommendation' => $this->usageTracker->getUpgradeRecommendation($club),
                ], 429); // 429 Too Many Requests
            }

            // Redirect back with error for web requests
            return redirect()->back()
                ->withErrors(['limit' => $e->getMessage()])
                ->withInput();
        }

        return $next($request);
    }

    /**
     * Get club from request (route parameter or request data).
     *
     * @param Request $request
     * @return Club|null
     */
    private function getClubFromRequest(Request $request): ?Club
    {
        // Try route parameter first (e.g., /clubs/{club}/teams)
        $clubId = $request->route('club');

        if ($clubId) {
            if ($clubId instanceof Club) {
                return $clubId; // Already resolved by route model binding
            }

            return Club::find($clubId);
        }

        // Try request data (e.g., club_id in POST data)
        $clubId = $request->input('club_id');

        if ($clubId) {
            return Club::find($clubId);
        }

        // Try to get club from team
        $teamId = $request->route('team') ?? $request->input('team_id');

        if ($teamId) {
            $team = \App\Models\Team::find($teamId);
            return $team?->club;
        }

        return null;
    }

    /**
     * Detect metric type from route name or action.
     *
     * @param Request $request
     * @return string|null
     */
    private function detectMetricFromRoute(Request $request): ?string
    {
        $routeName = $request->route()->getName() ?? '';
        $uri = $request->path();

        // Detect from route name patterns
        if (str_contains($routeName, 'teams.store') || str_contains($uri, '/teams')) {
            return 'max_teams';
        }

        if (str_contains($routeName, 'players.store') || str_contains($uri, '/players')) {
            return 'max_players';
        }

        if (str_contains($routeName, 'games.store') || str_contains($uri, '/games')) {
            return 'max_games_per_month';
        }

        if (str_contains($routeName, 'training') || str_contains($uri, '/training-sessions')) {
            return 'max_training_sessions_per_month';
        }

        return null;
    }

    /**
     * Get user-friendly limit name for error messages.
     *
     * @param string $metric
     * @return string
     */
    private function getLimitName(string $metric): string
    {
        return match($metric) {
            'max_teams' => 'teams',
            'max_players' => 'players',
            'max_games_per_month' => 'games per month',
            'max_training_sessions_per_month' => 'training sessions per month',
            'max_storage_gb' => 'storage',
            default => $metric,
        };
    }
}
