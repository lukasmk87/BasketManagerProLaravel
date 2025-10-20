<?php

namespace App\Http\Requests;

use App\Models\Player;
use App\Models\BasketballTeam;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AssignPlayerToTeamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Load the player
        $player = Player::find($this->player_id);

        if (!$player) {
            return false;
        }

        // Use the PlayerPolicy to check authorization
        return $this->user()->can('assignToTeam', $player);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'player_id' => [
                'required',
                'exists:players,id',
            ],
            'team_id' => [
                'required',
                'exists:teams,id',
            ],
            'jersey_number' => [
                'nullable',
                'integer',
                'min:0',
                'max:99',
            ],
            'position' => [
                'nullable',
                'in:PG,SG,SF,PF,C',
            ],
            'team_data' => [
                'nullable',
                'array',
            ],
            'team_data.jersey_number' => [
                'nullable',
                'integer',
                'min:0',
                'max:99',
            ],
            'team_data.position' => [
                'nullable',
                'in:PG,SG,SF,PF,C',
            ],
            'team_data.role' => [
                'nullable',
                'string',
                'max:255',
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
            // Load player and team
            $player = Player::with('registeredViaInvitation')->find($this->player_id);
            $team = BasketballTeam::find($this->team_id);

            if (!$player || !$team) {
                return; // Basic validation will catch this
            }

            // Check 1: Player must be pending assignment
            if (!$player->pending_team_assignment) {
                $validator->errors()->add(
                    'player_id',
                    'Dieser Spieler ist nicht für eine Team-Zuordnung vorgesehen.'
                );

                return;
            }

            // Check 2: Team must belong to a club that the user manages
            if (!$this->user()->hasRole(['super_admin', 'admin'])) {
                $userClubIds = $this->user()->clubs()->pluck('clubs.id')->toArray();

                if (!in_array($team->club_id, $userClubIds)) {
                    $validator->errors()->add(
                        'team_id',
                        'Sie haben keine Berechtigung, Spieler zu diesem Team hinzuzufügen.'
                    );

                    return;
                }
            }

            // Check 3: Team should belong to the same club as the invitation
            if ($player->registeredViaInvitation) {
                $invitationClubId = $player->registeredViaInvitation->club_id;

                if ($team->club_id != $invitationClubId) {
                    $validator->errors()->add(
                        'team_id',
                        'Das Team gehört nicht zum Club, für den sich der Spieler registriert hat.'
                    );

                    return;
                }
            }

            // Check 4: Jersey number must be unique in the team (if provided)
            $jerseyNumber = $this->input('jersey_number') ?? $this->input('team_data.jersey_number');

            if ($jerseyNumber !== null) {
                $jerseyExists = Player::where('team_id', $this->team_id)
                    ->where('jersey_number', $jerseyNumber)
                    ->where('id', '!=', $this->player_id)
                    ->exists();

                if ($jerseyExists) {
                    $validator->errors()->add(
                        'jersey_number',
                        "Die Trikotnummer {$jerseyNumber} wird bereits von einem anderen Spieler in diesem Team verwendet."
                    );
                }
            }

            // Check 5: Team is active
            if (!$team->is_active) {
                $validator->errors()->add(
                    'team_id',
                    'Dieses Team ist nicht aktiv und kann keine neuen Spieler aufnehmen.'
                );
            }

            // Check 6: Team has not reached maximum player limit (if set)
            if ($team->max_players && $team->players()->count() >= $team->max_players) {
                $validator->errors()->add(
                    'team_id',
                    'Dieses Team hat bereits die maximale Anzahl an Spielern erreicht.'
                );
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
            'player_id.required' => 'Bitte wählen Sie einen Spieler aus.',
            'player_id.exists' => 'Der ausgewählte Spieler existiert nicht.',

            'team_id.required' => 'Bitte wählen Sie ein Team aus.',
            'team_id.exists' => 'Das ausgewählte Team existiert nicht.',

            'jersey_number.integer' => 'Die Trikotnummer muss eine ganze Zahl sein.',
            'jersey_number.min' => 'Die Trikotnummer muss mindestens :min sein.',
            'jersey_number.max' => 'Die Trikotnummer darf maximal :max sein.',

            'position.in' => 'Bitte wählen Sie eine gültige Position (PG, SG, SF, PF, C).',

            'team_data.array' => 'Die Team-Daten müssen ein gültiges Format haben.',

            'team_data.jersey_number.integer' => 'Die Trikotnummer muss eine ganze Zahl sein.',
            'team_data.jersey_number.min' => 'Die Trikotnummer muss mindestens :min sein.',
            'team_data.jersey_number.max' => 'Die Trikotnummer darf maximal :max sein.',

            'team_data.position.in' => 'Bitte wählen Sie eine gültige Position (PG, SG, SF, PF, C).',

            'team_data.role.max' => 'Die Rollenbeschreibung darf maximal :max Zeichen lang sein.',
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
            'player_id' => 'Spieler',
            'team_id' => 'Team',
            'jersey_number' => 'Trikotnummer',
            'position' => 'Position',
            'team_data' => 'Team-Daten',
            'team_data.jersey_number' => 'Trikotnummer',
            'team_data.position' => 'Position',
            'team_data.role' => 'Rolle im Team',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // If jersey_number is provided at root level, also set it in team_data
        if ($this->has('jersey_number') && !$this->has('team_data.jersey_number')) {
            $this->merge([
                'team_data' => array_merge($this->input('team_data', []), [
                    'jersey_number' => $this->input('jersey_number'),
                ]),
            ]);
        }

        // If position is provided at root level, also set it in team_data
        if ($this->has('position') && !$this->has('team_data.position')) {
            $this->merge([
                'team_data' => array_merge($this->input('team_data', []), [
                    'position' => $this->input('position'),
                ]),
            ]);
        }
    }
}
