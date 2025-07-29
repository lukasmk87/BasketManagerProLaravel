<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SocialAccount;
use App\Services\LocalizationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Spatie\Permission\Models\Role;

class SocialAuthController extends Controller
{
    /**
     * The localization service instance.
     */
    protected LocalizationService $localizationService;

    /**
     * Create a new controller instance.
     */
    public function __construct(LocalizationService $localizationService)
    {
        $this->localizationService = $localizationService;
        $this->middleware('guest');
    }

    /**
     * Redirect to the social provider.
     */
    public function redirect(string $provider): RedirectResponse
    {
        if (!$this->isProviderSupported($provider)) {
            return redirect()->route('login')->withErrors([
                'social' => __('auth.social_login.provider_not_supported')
            ]);
        }

        try {
            // Store the intended URL and current locale in session
            session(['intended_url' => url()->previous()]);
            session(['social_login_locale' => $this->localizationService->getCurrentLocale()]);

            return Socialite::driver($provider)
                ->scopes($this->getProviderScopes($provider))
                ->redirect();
        } catch (\Exception $e) {
            Log::error('Social login redirect failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('login')->withErrors([
                'social' => __('auth.social_login.login_failed')
            ]);
        }
    }

    /**
     * Handle the social provider callback.
     */
    public function callback(string $provider): RedirectResponse
    {
        if (!$this->isProviderSupported($provider)) {
            return redirect()->route('login')->withErrors([
                'social' => __('auth.social_login.provider_not_supported')
            ]);
        }

        try {
            $socialiteUser = Socialite::driver($provider)->user();
            
            // Handle the social login
            $result = $this->handleSocialLogin($provider, $socialiteUser);
            
            if ($result['success']) {
                // Restore locale from session if available
                if (session('social_login_locale')) {
                    $this->localizationService->setLocale(session('social_login_locale'));
                    session()->forget('social_login_locale');
                }

                // Update last login info
                $result['user']->updateLastLogin(request()->ip());

                // Log successful social login
                Log::info('Social login successful', [
                    'user_id' => $result['user']->id,
                    'provider' => $provider,
                    'email' => $result['user']->email
                ]);

                return redirect()->intended('/dashboard')->with(
                    'success', 
                    __('auth.social_login.login_successful', ['provider' => ucfirst($provider)])
                );
            }

            return redirect()->route('login')->withErrors([
                'social' => $result['message']
            ]);

        } catch (InvalidStateException $e) {
            Log::warning('Invalid state exception in social login', [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('login')->withErrors([
                'social' => __('auth.social_login.login_failed')
            ]);
        } catch (\Exception $e) {
            Log::error('Social login callback failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('login')->withErrors([
                'social' => __('auth.social_login.login_failed')
            ]);
        }
    }

    /**
     * Link a social account to the current user.
     */
    public function link(string $provider): RedirectResponse
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (!$this->isProviderSupported($provider)) {
            return redirect()->back()->withErrors([
                'social' => __('auth.social_login.provider_not_supported')
            ]);
        }

        // Check if user already has this provider linked
        $existingAccount = auth()->user()->socialAccounts()->byProvider($provider)->first();
        if ($existingAccount) {
            return redirect()->back()->withErrors([
                'social' => __('This :provider account is already linked.', ['provider' => ucfirst($provider)])
            ]);
        }

        try {
            // Store linking intent in session
            session(['linking_provider' => $provider]);

            return Socialite::driver($provider)
                ->scopes($this->getProviderScopes($provider))
                ->redirect();
        } catch (\Exception $e) {
            Log::error('Social account linking redirect failed', [
                'provider' => $provider,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->withErrors([
                'social' => __('auth.social_login.login_failed')
            ]);
        }
    }

    /**
     * Handle the callback for linking a social account.
     */
    public function linkCallback(string $provider): RedirectResponse
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (session('linking_provider') !== $provider) {
            return redirect()->back()->withErrors([
                'social' => __('Invalid linking request.')
            ]);
        }

        try {
            $socialiteUser = Socialite::driver($provider)->user();
            
            // Check if this social account is already linked to another user
            $existingSocialAccount = SocialAccount::byProviderAndId($provider, $socialiteUser->getId())->first();
            if ($existingSocialAccount && $existingSocialAccount->user_id !== auth()->id()) {
                return redirect()->back()->withErrors([
                    'social' => __('This :provider account is already linked to another user.', ['provider' => ucfirst($provider)])
                ]);
            }

            // Link the account
            SocialAccount::findOrCreateFromSocialite(auth()->user(), $provider, $socialiteUser);

            session()->forget('linking_provider');

            Log::info('Social account linked successfully', [
                'user_id' => auth()->id(),
                'provider' => $provider
            ]);

            return redirect()->back()->with(
                'success',
                __('auth.social_login.account_linked')
            );

        } catch (\Exception $e) {
            Log::error('Social account linking failed', [
                'provider' => $provider,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->withErrors([
                'social' => __('auth.social_login.login_failed')
            ]);
        }
    }

    /**
     * Unlink a social account from the current user.
     */
    public function unlink(string $provider): RedirectResponse
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $socialAccount = auth()->user()->socialAccounts()->byProvider($provider)->first();
        
        if (!$socialAccount) {
            return redirect()->back()->withErrors([
                'social' => __('No :provider account found to unlink.', ['provider' => ucfirst($provider)])
            ]);
        }

        // Prevent unlinking if it's the only authentication method and user has no password
        if (auth()->user()->socialAccounts()->count() === 1 && !auth()->user()->password) {
            return redirect()->back()->withErrors([
                'social' => __('Cannot unlink your only authentication method. Please set a password first.')
            ]);
        }

        $socialAccount->delete();

        Log::info('Social account unlinked', [
            'user_id' => auth()->id(),
            'provider' => $provider
        ]);

        return redirect()->back()->with(
            'success',
            __('auth.social_login.account_unlinked')
        );
    }

    /**
     * Handle the social login process.
     */
    protected function handleSocialLogin(string $provider, $socialiteUser): array
    {
        // Check if social account already exists
        $socialAccount = SocialAccount::byProviderAndId($provider, $socialiteUser->getId())->first();

        if ($socialAccount) {
            // User exists - log them in
            if (!$socialAccount->user->is_active) {
                return [
                    'success' => false,
                    'message' => __('auth.account.deactivated')
                ];
            }

            // Update social account data
            SocialAccount::findOrCreateFromSocialite($socialAccount->user, $provider, $socialiteUser);

            Auth::login($socialAccount->user);

            return [
                'success' => true,
                'user' => $socialAccount->user,
                'message' => __('auth.social_login.login_successful', ['provider' => ucfirst($provider)])
            ];
        }

        // Check if user exists with same email
        $user = User::where('email', $socialiteUser->getEmail())->first();

        if ($user) {
            // User exists but no social account - link them
            if (!$user->is_active) {
                return [
                    'success' => false,
                    'message' => __('auth.account.deactivated')
                ];
            }

            SocialAccount::createFromSocialite($user, $provider, $socialiteUser);
            Auth::login($user);

            return [
                'success' => true,
                'user' => $user,
                'message' => __('auth.social_login.account_linked')
            ];
        }

        // Create new user
        $user = $this->createUserFromSocialite($provider, $socialiteUser);
        SocialAccount::createFromSocialite($user, $provider, $socialiteUser);

        Auth::login($user);

        return [
            'success' => true,
            'user' => $user,
            'message' => __('auth.register.registration_successful')
        ];
    }

    /**
     * Create a new user from socialite data.
     */
    protected function createUserFromSocialite(string $provider, $socialiteUser): User
    {
        $user = User::create([
            'name' => $socialiteUser->getName() ?: 'User',
            'email' => $socialiteUser->getEmail(),
            'password' => Hash::make(Str::random(32)),
            'email_verified_at' => now(),
            'language' => $this->localizationService->getCurrentLocale(),
            'timezone' => config('app.timezone'),
            'is_active' => true,
        ]);

        // Assign default role
        $defaultRole = Role::where('name', 'player')->first();
        if ($defaultRole) {
            $user->assignRole($defaultRole);
        }

        // Set avatar from social provider if available
        if ($socialiteUser->getAvatar()) {
            // This would typically involve downloading and storing the avatar
            // For now, we'll just store the URL in preferences
            $preferences = $user->preferences ?? [];
            $preferences['social_avatar'] = $socialiteUser->getAvatar();
            $user->update(['preferences' => $preferences]);
        }

        Log::info('New user created via social login', [
            'user_id' => $user->id,
            'provider' => $provider,
            'email' => $user->email
        ]);

        return $user;
    }

    /**
     * Check if the provider is supported.
     */
    protected function isProviderSupported(string $provider): bool
    {
        return SocialAccount::isProviderSupported($provider) && 
               config("services.{$provider}.client_id") !== null;
    }

    /**
     * Get the scopes for the provider.
     */
    protected function getProviderScopes(string $provider): array
    {
        $scopes = [
            'google' => ['email', 'profile'],
            'facebook' => ['email'],
            'github' => ['user:email'],
            'twitter' => [],
            'linkedin' => ['r_liteprofile', 'r_emailaddress'],
            'apple' => ['email', 'name'],
        ];

        return $scopes[$provider] ?? [];
    }

    /**
     * Get available social providers for the frontend.
     */
    public function getProviders(): array
    {
        $providers = [];

        foreach (SocialAccount::$supportedProviders as $provider) {
            if (config("services.{$provider}.client_id")) {
                $providers[] = [
                    'name' => $provider,
                    'display_name' => ucfirst($provider),
                    'enabled' => true,
                ];
            }
        }

        return $providers;
    }
}