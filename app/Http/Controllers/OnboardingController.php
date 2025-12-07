<?php

namespace App\Http\Controllers;

use App\Http\Requests\Onboarding\StoreClubRequest;
use App\Http\Requests\Onboarding\StorePlanRequest;
use App\Http\Requests\Onboarding\StoreTeamRequest;
use App\Models\Club;
use App\Models\ClubSubscriptionPlan;
use App\Models\InvoiceRequest;
use App\Services\OnboardingService;
use App\Services\Stripe\ClubSubscriptionCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function __construct(
        private OnboardingService $onboardingService,
        private ClubSubscriptionCheckoutService $checkoutService
    ) {}

    /**
     * Display the onboarding wizard.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // If already completed, redirect to dashboard
        if ($user->hasCompletedOnboarding()) {
            return redirect()->route('dashboard');
        }

        // Get onboarding progress
        $progress = $this->onboardingService->getOnboardingProgress($user);

        // Get available plans and age groups
        $tenant = $user->tenant;
        $availablePlans = $this->onboardingService->getAvailablePlans($tenant);
        $ageGroups = $this->onboardingService->getAgeGroups();

        // Find free plan (preselected)
        $freePlan = $this->onboardingService->getFreePlan($tenant);

        return Inertia::render('Onboarding/Index', [
            'progress' => $progress,
            'availablePlans' => $availablePlans,
            'ageGroups' => $ageGroups,
            'freePlanId' => $freePlan?->id,
            'club' => $progress['club'],
        ]);
    }

    /**
     * Store the club (Step 1).
     */
    public function storeClub(StoreClubRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        try {
            // Handle logo upload
            if ($request->hasFile('logo')) {
                $path = $request->file('logo')->store('clubs/logos', 'public');
                $validated['logo_path'] = $path;
            }

            $club = $this->onboardingService->createClubForOnboarding($validated, $user);

            // Store club ID in session for next step
            session(['onboarding_club_id' => $club->id]);

            return redirect()->route('onboarding.index')
                ->with('success', 'Club erfolgreich erstellt! Jetzt wähle deinen Plan.');

        } catch (\Exception $e) {
            Log::error('Onboarding: Failed to create club', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'error' => 'Fehler beim Erstellen des Clubs: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Store the team (Step 3 - final step).
     */
    public function storeTeam(StoreTeamRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Get club from session or user's clubs
        $clubId = session('onboarding_club_id');
        $club = $clubId
            ? Club::find($clubId)
            : $user->clubs()->first();

        if (! $club) {
            return redirect()->route('onboarding.index')
                ->withErrors(['error' => 'Bitte erstelle zuerst einen Club.']);
        }

        try {
            $team = $this->onboardingService->createTeamForOnboarding($validated, $club, $user);

            // Team is the final step - mark onboarding as complete and redirect
            $user->markOnboardingComplete();

            // Clear session
            session()->forget('onboarding_club_id');

            return redirect()->route('onboarding.complete')
                ->with('success', 'Team erfolgreich erstellt!');

        } catch (\Exception $e) {
            Log::error('Onboarding: Failed to create team', [
                'user_id' => $user->id,
                'club_id' => $club->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'error' => 'Fehler beim Erstellen des Teams: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Store the plan selection (Step 2).
     */
    public function storePlan(StorePlanRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Get club from session or user's clubs
        $clubId = session('onboarding_club_id');
        $club = $clubId
            ? Club::find($clubId)
            : $user->clubs()->first();

        if (! $club) {
            return redirect()->route('onboarding.index')
                ->withErrors(['error' => 'Bitte erstelle zuerst einen Club.']);
        }

        try {
            $plan = ClubSubscriptionPlan::findOrFail($validated['plan_id']);

            $paidPlan = $this->onboardingService->selectPlanForOnboarding($plan, $club, $user);

            // Free plan - redirect back to onboarding for team step
            if (! $paidPlan) {
                return redirect()->route('onboarding.index')
                    ->with('success', 'Plan erfolgreich gewählt! Jetzt erstelle dein erstes Team.');
            }

            // Invoice payment - assign plan immediately and create invoice request
            if (($validated['payment_method'] ?? 'stripe') === 'invoice') {
                // Check for existing pending invoice request
                $existingRequest = InvoiceRequest::where('requestable_type', Club::class)
                    ->where('requestable_id', $club->id)
                    ->pending()
                    ->exists();

                if ($existingRequest) {
                    return back()->withErrors([
                        'error' => 'Es existiert bereits eine ausstehende Rechnungsanfrage für diesen Club.',
                    ]);
                }

                // Assign plan immediately
                $club->update(['club_subscription_plan_id' => $plan->id]);

                // Create invoice request
                InvoiceRequest::create([
                    'tenant_id' => $club->tenant_id,
                    'requestable_type' => Club::class,
                    'requestable_id' => $club->id,
                    'subscription_plan_type' => ClubSubscriptionPlan::class,
                    'subscription_plan_id' => $plan->id,
                    'billing_name' => $validated['billing_name'],
                    'billing_email' => $validated['billing_email'],
                    'billing_address' => $validated['billing_address'] ?? null,
                    'vat_number' => $validated['vat_number'] ?? null,
                    'billing_interval' => $validated['billing_interval'] ?? 'monthly',
                    'status' => InvoiceRequest::STATUS_PENDING,
                    'requested_by' => auth()->id(),
                ]);

                Log::info('Onboarding: Invoice payment requested', [
                    'user_id' => $user->id,
                    'club_id' => $club->id,
                    'plan_id' => $plan->id,
                ]);

                return redirect()->route('onboarding.index')
                    ->with('success', 'Plan gewählt! Deine Rechnung wird erstellt und per E-Mail versendet.');
            }

            // Stripe payment - create checkout session and redirect
            $session = $this->checkoutService->createCheckoutSession(
                $club,
                $paidPlan,
                [
                    'billing_interval' => $validated['billing_interval'] ?? 'monthly',
                    'success_url' => route('onboarding.index'),
                    'cancel_url' => route('onboarding.index'),
                ]
            );

            return redirect()->away($session->url);

        } catch (\Exception $e) {
            Log::error('Onboarding: Failed to select plan', [
                'user_id' => $user->id,
                'club_id' => $club->id,
                'plan_id' => $validated['plan_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'error' => 'Fehler bei der Plan-Auswahl: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Display the completion page.
     */
    public function complete(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // Ensure onboarding is marked complete
        if (! $user->hasCompletedOnboarding()) {
            $user->markOnboardingComplete();
        }

        // Clear session
        session()->forget('onboarding_club_id');

        // Get user's club for display
        $club = $user->clubs()->with('subscriptionPlan')->first();

        return Inertia::render('Onboarding/Complete', [
            'club' => $club,
            'plan' => $club?->subscriptionPlan,
        ]);
    }
}
