<?php

namespace App\Http\Requests;

use App\Models\PlayerRegistrationInvitation;
use App\Models\Club;
use App\Models\BasketballTeam;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePlayerRegistrationInvitationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user can create player invitations
        if (!$this->user()->can('create', PlayerRegistrationInvitation::class)) {
            return false;
        }

        // Additional check: User must belong to the club they're creating invitation for
        $clubId = $this->input('club_id');

        if ($clubId) {
            $userClubIds = $this->user()->clubs()->pluck('clubs.id')->toArray();
            return in_array($clubId, $userClubIds);
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'club_id' => [
                'required',
                'exists:clubs,id',
            ],
            'target_team_id' => [
                'nullable',
                'exists:teams,id',
            ],
            'expires_at' => [
                'required',
                'date',
                'after:now',
                'before:' . now()->addYear()->toDateTimeString(),
            ],
            'max_registrations' => [
                'nullable',
                'integer',
                'min:1',
                'max:500',
            ],
            'qr_size' => [
                'nullable',
                'integer',
                'min:100',
                'max:1000',
            ],
            'settings' => [
                'nullable',
                'array',
            ],
            'settings.require_email_verification' => [
                'sometimes',
                'boolean',
            ],
            'settings.collect_address' => [
                'sometimes',
                'boolean',
            ],
            'settings.collect_position' => [
                'sometimes',
                'boolean',
            ],
            'settings.collect_experience' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Check if target_team belongs to the specified club
            if ($this->filled(['club_id', 'target_team_id'])) {
                $team = BasketballTeam::find($this->target_team_id);

                if ($team && $team->club_id != $this->club_id) {
                    $validator->errors()->add(
                        'target_team_id',
                        'Das ausgewählte Team gehört nicht zum angegebenen Club.'
                    );
                }
            }

            // Check if user is authorized for the specific club
            $clubId = $this->input('club_id');

            if ($clubId) {
                // Super admin and admin can create for any club
                if (!$this->user()->hasRole(['super_admin', 'admin'])) {
                    $userClubIds = $this->user()->clubs()->pluck('clubs.id')->toArray();

                    if (!in_array($clubId, $userClubIds)) {
                        $validator->errors()->add(
                            'club_id',
                            'Sie haben keine Berechtigung, Einladungen für diesen Club zu erstellen.'
                        );
                    }
                }
            }

            // Validate expires_at is reasonable (not too far in future, not too soon)
            if ($this->filled('expires_at')) {
                $expiresAt = \Carbon\Carbon::parse($this->expires_at);
                $minDate = now()->addHours(1);
                $maxDate = now()->addYear();

                if ($expiresAt->lt($minDate)) {
                    $validator->errors()->add(
                        'expires_at',
                        'Das Ablaufdatum muss mindestens 1 Stunde in der Zukunft liegen.'
                    );
                }

                if ($expiresAt->gt($maxDate)) {
                    $validator->errors()->add(
                        'expires_at',
                        'Das Ablaufdatum darf maximal 1 Jahr in der Zukunft liegen.'
                    );
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'club_id.required' => 'Bitte wählen Sie einen Club aus.',
            'club_id.exists' => 'Der ausgewählte Club existiert nicht.',

            'target_team_id.exists' => 'Das ausgewählte Team existiert nicht.',

            'expires_at.required' => 'Bitte geben Sie ein Ablaufdatum an.',
            'expires_at.date' => 'Das Ablaufdatum muss ein gültiges Datum sein.',
            'expires_at.after' => 'Das Ablaufdatum muss in der Zukunft liegen.',
            'expires_at.before' => 'Das Ablaufdatum darf maximal 1 Jahr in der Zukunft liegen.',

            'max_registrations.integer' => 'Die maximale Anzahl an Registrierungen muss eine ganze Zahl sein.',
            'max_registrations.min' => 'Die maximale Anzahl an Registrierungen muss mindestens :min sein.',
            'max_registrations.max' => 'Die maximale Anzahl an Registrierungen darf maximal :max sein.',

            'qr_size.integer' => 'Die QR-Code-Größe muss eine ganze Zahl sein.',
            'qr_size.min' => 'Die QR-Code-Größe muss mindestens :min Pixel betragen.',
            'qr_size.max' => 'Die QR-Code-Größe darf maximal :max Pixel betragen.',

            'settings.array' => 'Die Einstellungen müssen ein gültiges Format haben.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'club_id' => 'Club',
            'target_team_id' => 'Ziel-Team',
            'expires_at' => 'Ablaufdatum',
            'max_registrations' => 'Maximale Anzahl Registrierungen',
            'qr_size' => 'QR-Code-Größe',
            'settings' => 'Einstellungen',
        ];
    }
}
