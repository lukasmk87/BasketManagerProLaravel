<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'csrf_token' => fn () => csrf_token(),
            'auth' => [
                'user' => function () use ($request) {
                    if (! $user = $request->user()) {
                        return;
                    }

                    // Check if user has team features enabled (from Jetstream)
                    $userHasTeamFeatures = class_exists('\Laravel\Jetstream\Jetstream')
                        ? \Laravel\Jetstream\Jetstream::userHasTeamFeatures($user)
                        : false;

                    // Load current team if user has team features
                    if ($user && $userHasTeamFeatures) {
                        $user->currentTeam;
                    }

                    return array_merge($user->toArray(), array_filter([
                        'all_teams' => $userHasTeamFeatures ? $user->allTeams()->values() : null,
                        'current_team' => $userHasTeamFeatures ? $user->currentTeam : null,
                    ]), [
                        'two_factor_enabled' => class_exists('\Laravel\Fortify\Features')
                            && \Laravel\Fortify\Features::enabled(\Laravel\Fortify\Features::twoFactorAuthentication())
                            && ! is_null($user->two_factor_secret),
                        'roles' => $user->getRoleNames()->toArray(),
                    ]);
                },
            ],
            'currentClub' => function () use ($request) {
                if (! $user = $request->user()) {
                    return null;
                }

                // Get the first club the user belongs to (primary club)
                // You can modify this logic based on your multi-tenant needs
                $club = $user->clubs()
                    ->wherePivot('is_active', true)
                    ->orderBy('club_user.created_at', 'asc')
                    ->first();

                if (!$club) {
                    return null;
                }

                return [
                    'id' => $club->id,
                    'name' => $club->name,
                    'short_name' => $club->short_name,
                    'logo_url' => $club->logo_url,
                    'primary_color' => $club->primary_color,
                    'secondary_color' => $club->secondary_color,
                    'accent_color' => $club->accent_color,
                ];
            },
            'jetstream' => function () use ($request) {
                if (!class_exists('\Laravel\Jetstream\Jetstream')) {
                    return null;
                }

                $user = $request->user();

                return [
                    'canCreateTeams' => $user &&
                                        \Laravel\Jetstream\Jetstream::userHasTeamFeatures($user) &&
                                        \Illuminate\Support\Facades\Gate::forUser($user)->check('create', \Laravel\Jetstream\Jetstream::newTeamModel()),
                    'canManageTwoFactorAuthentication' => class_exists('\Laravel\Fortify\Features') 
                        ? \Laravel\Fortify\Features::canManageTwoFactorAuthentication() 
                        : false,
                    'canUpdatePassword' => class_exists('\Laravel\Fortify\Features') 
                        ? \Laravel\Fortify\Features::enabled(\Laravel\Fortify\Features::updatePasswords()) 
                        : false,
                    'canUpdateProfileInformation' => class_exists('\Laravel\Fortify\Features') 
                        ? \Laravel\Fortify\Features::canUpdateProfileInformation() 
                        : false,
                    'hasEmailVerification' => class_exists('\Laravel\Fortify\Features') 
                        ? \Laravel\Fortify\Features::enabled(\Laravel\Fortify\Features::emailVerification()) 
                        : false,
                    'flash' => $request->session()->get('flash', []),
                    'hasAccountDeletionFeatures' => \Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures(),
                    'hasApiFeatures' => \Laravel\Jetstream\Jetstream::hasApiFeatures(),
                    'hasTeamFeatures' => \Laravel\Jetstream\Jetstream::hasTeamFeatures(),
                    'hasTermsAndPrivacyPolicyFeature' => \Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature(),
                    'managesProfilePhotos' => \Laravel\Jetstream\Jetstream::managesProfilePhotos(),
                ];
            },
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'message' => fn () => $request->session()->get('message'),
            ],
            'locale' => function () use ($request) {
                // Use authenticated user's locale preference, fallback to app locale
                if ($user = $request->user()) {
                    return $user->locale ?? app()->getLocale();
                }
                return app()->getLocale();
            },
            'translations' => fn () => [
                'subscription' => __('subscription'),
                'billing' => __('billing'),
                'checkout' => __('checkout'),
            ],
        ];
    }
}
