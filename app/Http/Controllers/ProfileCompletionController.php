<?php

namespace App\Http\Controllers;

use App\Services\ProfileCompletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileCompletionController extends Controller
{
    public function __construct(
        private ProfileCompletionService $profileCompletionService
    ) {}

    /**
     * Show the profile completion form.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // If profile is already complete, redirect to dashboard
        if (! $this->profileCompletionService->needsCompletion($user)) {
            return redirect()->route('dashboard');
        }

        $requiredFields = $this->profileCompletionService->getRequiredFields($user);
        $club = $this->profileCompletionService->getInvitedClub($user);
        $clubRole = $this->profileCompletionService->getClubRole($user);

        return Inertia::render('ProfileCompletion/Index', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'date_of_birth' => $user->date_of_birth?->format('Y-m-d'),
                'gender' => $user->gender,
                'emergency_contact_name' => $user->emergency_contact_name,
                'emergency_contact_phone' => $user->emergency_contact_phone,
            ],
            'requiredFields' => $requiredFields,
            'club' => $club ? [
                'name' => $club->name,
                'logo_url' => $club->logo_url,
            ] : null,
            'clubRole' => $clubRole,
        ]);
    }

    /**
     * Store the profile data.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $requiredFields = $this->profileCompletionService->getRequiredFields($user);

        // Build validation rules dynamically based on required fields
        $rules = $this->buildValidationRules($requiredFields);
        $validated = $request->validate($rules, $this->validationMessages());

        // Update user data
        $user->update($this->prepareUserData($validated));

        // Check if all required fields are now filled
        $user->refresh();
        $missingFields = $this->profileCompletionService->validateCompletion($user);

        if (empty($missingFields)) {
            $this->profileCompletionService->markComplete($user);

            return redirect()->route('profile-completion.complete');
        }

        return back()->withErrors([
            'incomplete' => __('Bitte füllen Sie alle Pflichtfelder aus.'),
        ]);
    }

    /**
     * Show the completion page.
     */
    public function complete(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // If user still needs profile completion, redirect back
        if ($this->profileCompletionService->needsCompletion($user)) {
            return redirect()->route('profile-completion.index');
        }

        $club = $user->clubs()->first();

        return Inertia::render('ProfileCompletion/Complete', [
            'club' => $club ? [
                'name' => $club->name,
                'logo_url' => $club->logo_url,
            ] : null,
        ]);
    }

    /**
     * Build validation rules based on required fields.
     *
     * @param  array<string>  $requiredFields
     * @return array<string, array<string>>
     */
    private function buildValidationRules(array $requiredFields): array
    {
        $allRules = [
            'phone' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female,other,prefer_not_to_say'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
        ];

        // Mark required fields as required
        foreach ($requiredFields as $field) {
            if (isset($allRules[$field])) {
                $allRules[$field][0] = 'required';
            }
        }

        return $allRules;
    }

    /**
     * Get validation messages.
     *
     * @return array<string, string>
     */
    private function validationMessages(): array
    {
        return [
            'phone.required' => __('Bitte geben Sie Ihre Telefonnummer ein.'),
            'date_of_birth.required' => __('Bitte geben Sie Ihr Geburtsdatum ein.'),
            'date_of_birth.before' => __('Das Geburtsdatum muss in der Vergangenheit liegen.'),
            'emergency_contact_name.required' => __('Bitte geben Sie den Namen eines Notfallkontakts ein.'),
            'emergency_contact_phone.required' => __('Bitte geben Sie die Telefonnummer des Notfallkontakts ein.'),
        ];
    }

    /**
     * Prepare user data for update.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function prepareUserData(array $validated): array
    {
        return array_filter($validated, fn ($value) => $value !== null);
    }
}
