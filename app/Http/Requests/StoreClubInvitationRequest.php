<?php

namespace App\Http\Requests;

use App\Models\ClubInvitation;
use Illuminate\Foundation\Http\FormRequest;

class StoreClubInvitationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // User must be able to create invitations for clubs they administer
        return $this->user()->can('create', ClubInvitation::class);
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
                'integer',
                'exists:clubs,id',
                function ($attribute, $value, $fail) {
                    // Verify user has access to this club
                    $adminClubIds = $this->user()->getAdministeredClubIds();
                    if (!in_array($value, $adminClubIds)) {
                        $fail('Sie haben keine Berechtigung, Einladungen für diesen Club zu erstellen.');
                    }
                },
            ],
            'default_role' => [
                'required',
                'string',
                'in:member,player,parent,volunteer,sponsor',
            ],
            'expires_at' => [
                'required',
                'date',
                'after:now',
            ],
            'max_uses' => [
                'nullable',
                'integer',
                'min:1',
                'max:1000',
            ],
            'qr_size' => [
                'nullable',
                'integer',
                'in:200,300,400,600,800',
            ],
            'qr_format' => [
                'nullable',
                'string',
                'in:png,svg',
            ],
            'settings' => [
                'nullable',
                'array',
            ],
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
            'default_role' => 'Standardrolle',
            'expires_at' => 'Ablaufdatum',
            'max_uses' => 'Maximale Nutzungen',
            'qr_size' => 'QR-Code Größe',
            'qr_format' => 'QR-Code Format',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'expires_at.after' => 'Das Ablaufdatum muss in der Zukunft liegen.',
            'default_role.in' => 'Die ausgewählte Rolle ist ungültig.',
        ];
    }
}
