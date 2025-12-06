<?php

namespace App\Http\Controllers;

use App\Mail\EnterpriseLeadConfirmation;
use App\Mail\EnterpriseLeadNotification;
use App\Models\EnterpriseLead;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnterpriseLeadController extends Controller
{
    /**
     * Store a new enterprise lead from the contact form.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization_name' => ['required', 'string', 'max:255'],
            'organization_type' => ['required', 'string', 'in:' . implode(',', array_keys(EnterpriseLead::ORGANIZATION_TYPES))],
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'club_count' => ['nullable', 'string', 'in:' . implode(',', array_keys(EnterpriseLead::CLUB_COUNT_OPTIONS))],
            'team_count' => ['nullable', 'string', 'in:' . implode(',', array_keys(EnterpriseLead::TEAM_COUNT_OPTIONS))],
            'message' => ['nullable', 'string', 'max:5000'],
            'gdpr_accepted' => ['required', 'accepted'],
            'newsletter_optin' => ['nullable', 'boolean'],
        ], [
            'organization_name.required' => 'Bitte geben Sie den Namen Ihrer Organisation an.',
            'organization_type.required' => 'Bitte wählen Sie einen Organisationstyp.',
            'organization_type.in' => 'Bitte wählen Sie einen gültigen Organisationstyp.',
            'contact_name.required' => 'Bitte geben Sie Ihren Namen an.',
            'email.required' => 'Bitte geben Sie Ihre E-Mail-Adresse an.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse an.',
            'gdpr_accepted.required' => 'Sie müssen der Datenschutzerklärung zustimmen.',
            'gdpr_accepted.accepted' => 'Sie müssen der Datenschutzerklärung zustimmen.',
        ]);

        try {
            // Create the lead
            $lead = EnterpriseLead::create([
                'organization_name' => $validated['organization_name'],
                'organization_type' => $validated['organization_type'],
                'contact_name' => $validated['contact_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'club_count' => $validated['club_count'] ?? null,
                'team_count' => $validated['team_count'] ?? null,
                'message' => $validated['message'] ?? null,
                'gdpr_accepted' => true,
                'newsletter_optin' => $request->boolean('newsletter_optin'),
                'status' => 'new',
            ]);

            Log::info('Enterprise lead created', [
                'lead_id' => $lead->id,
                'organization' => $lead->organization_name,
                'type' => $lead->organization_type,
            ]);

            // Send notification to admins (Super Admins)
            $this->notifyAdmins($lead);

            // Send confirmation to the lead
            $this->sendConfirmation($lead);

            return redirect()
                ->route('enterprise')
                ->with('success', 'Vielen Dank für Ihre Anfrage! Wir melden uns innerhalb von 24 Stunden bei Ihnen.')
                ->withFragment('contact');

        } catch (\Exception $e) {
            Log::error('Failed to create enterprise lead', [
                'error' => $e->getMessage(),
                'data' => $validated,
            ]);

            return redirect()
                ->route('enterprise')
                ->withInput()
                ->withErrors(['general' => 'Es ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.'])
                ->withFragment('contact');
        }
    }

    /**
     * Notify super admins about new lead.
     */
    private function notifyAdmins(EnterpriseLead $lead): void
    {
        try {
            // Get all super admins
            $superAdmins = User::role('super_admin')->get();

            foreach ($superAdmins as $admin) {
                Mail::to($admin->email)->queue(new EnterpriseLeadNotification($lead));
            }

            Log::info('Enterprise lead notifications sent', [
                'lead_id' => $lead->id,
                'admin_count' => $superAdmins->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send admin notifications for enterprise lead', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send confirmation email to the lead.
     */
    private function sendConfirmation(EnterpriseLead $lead): void
    {
        try {
            Mail::to($lead->email)->queue(new EnterpriseLeadConfirmation($lead));

            Log::info('Enterprise lead confirmation sent', [
                'lead_id' => $lead->id,
                'email' => $lead->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send confirmation to enterprise lead', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
