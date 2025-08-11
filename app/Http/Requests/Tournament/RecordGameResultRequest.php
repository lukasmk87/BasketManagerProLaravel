<?php

namespace App\Http\Requests\Tournament;

use Illuminate\Foundation\Http\FormRequest;

class RecordGameResultRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Basic Game Result
            'team1_score' => 'required|integer|min:0|max:200',
            'team2_score' => 'required|integer|min:0|max:200',
            
            // Score by Period
            'score_by_period' => 'nullable|array',
            'score_by_period.*.team1' => 'required_with:score_by_period|integer|min:0|max:50',
            'score_by_period.*.team2' => 'required_with:score_by_period|integer|min:0|max:50',
            'score_by_period.*.period' => 'required_with:score_by_period|integer|min:1|max:10',
            'score_by_period.*.is_overtime' => 'sometimes|boolean',
            
            // Overtime Information
            'overtime' => 'boolean',
            'overtime_periods' => 'nullable|integer|min:0|max:5',
            
            // Game Timing
            'actual_start_time' => 'nullable|date',
            'actual_end_time' => 'nullable|date|after:actual_start_time',
            'actual_duration' => 'nullable|integer|min:30|max:300', // minutes
            
            // Game Notes and Commentary
            'game_notes' => 'nullable|string|max:2000',
            'referee_notes' => 'nullable|string|max:1000',
            'technical_fouls' => 'nullable|array',
            'technical_fouls.*.player_id' => 'sometimes|exists:users,id',
            'technical_fouls.*.team_id' => 'sometimes|exists:tournament_teams,id',
            'technical_fouls.*.reason' => 'sometimes|string|max:255',
            'technical_fouls.*.period' => 'sometimes|integer|min:1|max:10',
            'technical_fouls.*.time_remaining' => 'sometimes|string|max:10',
            
            // Player Statistics (Optional)
            'player_stats' => 'nullable|array',
            'player_stats.*.player_id' => 'required_with:player_stats|exists:users,id',
            'player_stats.*.team_id' => 'required_with:player_stats|exists:tournament_teams,id',
            'player_stats.*.minutes_played' => 'sometimes|integer|min:0|max:60',
            'player_stats.*.points' => 'sometimes|integer|min:0|max:100',
            'player_stats.*.rebounds' => 'sometimes|integer|min:0|max:30',
            'player_stats.*.assists' => 'sometimes|integer|min:0|max:20',
            'player_stats.*.steals' => 'sometimes|integer|min:0|max:15',
            'player_stats.*.blocks' => 'sometimes|integer|min:0|max:15',
            'player_stats.*.fouls' => 'sometimes|integer|min:0|max:6',
            'player_stats.*.field_goals_made' => 'sometimes|integer|min:0|max:50',
            'player_stats.*.field_goals_attempted' => 'sometimes|integer|min:0|max:100',
            'player_stats.*.three_points_made' => 'sometimes|integer|min:0|max:20',
            'player_stats.*.three_points_attempted' => 'sometimes|integer|min:0|max:40',
            'player_stats.*.free_throws_made' => 'sometimes|integer|min:0|max:30',
            'player_stats.*.free_throws_attempted' => 'sometimes|integer|min:0|max:40',
            
            // Team Statistics (Optional)
            'team_stats' => 'nullable|array',
            'team_stats.team1' => 'sometimes|array',
            'team_stats.team2' => 'sometimes|array',
            'team_stats.team1.timeouts_used' => 'sometimes|integer|min:0|max:10',
            'team_stats.team1.team_fouls' => 'sometimes|integer|min:0|max:50',
            'team_stats.team1.total_rebounds' => 'sometimes|integer|min:0|max:100',
            'team_stats.team1.turnovers' => 'sometimes|integer|min:0|max:50',
            'team_stats.team2.timeouts_used' => 'sometimes|integer|min:0|max:10',
            'team_stats.team2.team_fouls' => 'sometimes|integer|min:0|max:50',
            'team_stats.team2.total_rebounds' => 'sometimes|integer|min:0|max:100',
            'team_stats.team2.turnovers' => 'sometimes|integer|min:0|max:50',
            
            // Game Quality Metrics
            'game_quality' => 'nullable|array',
            'game_quality.attendance' => 'sometimes|integer|min:0|max:50000',
            'game_quality.atmosphere_rating' => 'sometimes|integer|min:1|max:10',
            'game_quality.competitiveness_rating' => 'sometimes|integer|min:1|max:10',
            'game_quality.officiating_rating' => 'sometimes|integer|min:1|max:10',
            'game_quality.entertainment_value' => 'sometimes|integer|min:1|max:10',
            
            // Key Moments and Highlights
            'key_moments' => 'nullable|array',
            'key_moments.*.type' => 'required_with:key_moments|string|in:timeout,substitution,foul,technical,ejection,injury,milestone,highlight',
            'key_moments.*.description' => 'required_with:key_moments|string|max:255',
            'key_moments.*.period' => 'required_with:key_moments|integer|min:1|max:10',
            'key_moments.*.time_remaining' => 'required_with:key_moments|string|max:10',
            'key_moments.*.player_id' => 'sometimes|exists:users,id',
            'key_moments.*.team_id' => 'sometimes|exists:tournament_teams,id',
            
            // Post-Game Information
            'post_game' => 'nullable|array',
            'post_game.winning_margin' => 'sometimes|integer|min:0|max:150',
            'post_game.lead_changes' => 'sometimes|integer|min:0|max:50',
            'post_game.largest_lead' => 'sometimes|integer|min:0|max:100',
            'post_game.comeback' => 'sometimes|boolean',
            'post_game.comeback_deficit' => 'sometimes|integer|min:0|max:50',
            
            // Confirmation and Verification
            'result_verified_by' => 'nullable|string|max:255',
            'both_teams_confirmed' => 'sometimes|boolean',
            'official_signature' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'team1_score.required' => 'Der Punktestand für Team 1 ist erforderlich.',
            'team2_score.required' => 'Der Punktestand für Team 2 ist erforderlich.',
            'team1_score.min' => 'Der Punktestand darf nicht negativ sein.',
            'team2_score.min' => 'Der Punktestand darf nicht negativ sein.',
            'team1_score.max' => 'Unplausibler Punktestand (Maximum: 200 Punkte).',
            'team2_score.max' => 'Unplausibler Punktestand (Maximum: 200 Punkte).',
            'score_by_period.*.team1.required_with' => 'Team 1 Punktestand pro Periode ist erforderlich.',
            'score_by_period.*.team2.required_with' => 'Team 2 Punktestand pro Periode ist erforderlich.',
            'score_by_period.*.period.required_with' => 'Periodennummer ist erforderlich.',
            'overtime_periods.max' => 'Maximal 5 Verlängerungen sind möglich.',
            'actual_end_time.after' => 'Die Endzeit muss nach der Startzeit liegen.',
            'actual_duration.min' => 'Spieldauer muss mindestens 30 Minuten betragen.',
            'actual_duration.max' => 'Spieldauer darf maximal 5 Stunden betragen.',
            'game_notes.max' => 'Spielnotizen dürfen maximal 2000 Zeichen haben.',
            'referee_notes.max' => 'Schiedsrichternotizen dürfen maximal 1000 Zeichen haben.',
            'technical_fouls.*.player_id.exists' => 'Der angegebene Spieler existiert nicht.',
            'technical_fouls.*.team_id.exists' => 'Das angegebene Team existiert nicht.',
            'player_stats.*.player_id.required_with' => 'Spieler-ID ist für Statistiken erforderlich.',
            'player_stats.*.player_id.exists' => 'Der angegebene Spieler existiert nicht.',
            'player_stats.*.team_id.required_with' => 'Team-ID ist für Spielerstatistiken erforderlich.',
            'player_stats.*.minutes_played.max' => 'Spielzeit darf maximal 60 Minuten betragen.',
            'player_stats.*.points.max' => 'Unplausible Punktzahl für einen Spieler (Maximum: 100).',
            'player_stats.*.fouls.max' => 'Ein Spieler kann maximal 6 Fouls haben.',
            'game_quality.attendance.min' => 'Zuschauerzahl darf nicht negativ sein.',
            'game_quality.atmosphere_rating.min' => 'Bewertung muss zwischen 1 und 10 liegen.',
            'game_quality.atmosphere_rating.max' => 'Bewertung muss zwischen 1 und 10 liegen.',
            'key_moments.*.type.required_with' => 'Typ des Schlüsselmoments ist erforderlich.',
            'key_moments.*.type.in' => 'Ungültiger Typ für Schlüsselmoment.',
            'key_moments.*.description.required_with' => 'Beschreibung des Schlüsselmoments ist erforderlich.',
            'key_moments.*.period.required_with' => 'Periode für Schlüsselmoment ist erforderlich.',
            'key_moments.*.time_remaining.required_with' => 'Verbleibende Zeit für Schlüsselmoment ist erforderlich.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'team1_score' => 'Punktestand Team 1',
            'team2_score' => 'Punktestand Team 2',
            'score_by_period' => 'Punktestand pro Periode',
            'overtime' => 'Verlängerung',
            'overtime_periods' => 'Anzahl Verlängerungen',
            'actual_start_time' => 'Tatsächliche Startzeit',
            'actual_end_time' => 'Tatsächliche Endzeit',
            'actual_duration' => 'Tatsächliche Spieldauer',
            'game_notes' => 'Spielnotizen',
            'referee_notes' => 'Schiedsrichternotizen',
            'technical_fouls' => 'Technische Fouls',
            'player_stats' => 'Spielerstatistiken',
            'team_stats' => 'Teamstatistiken',
            'game_quality' => 'Spielqualität',
            'key_moments' => 'Schlüsselmomente',
            'post_game' => 'Nach dem Spiel',
            'result_verified_by' => 'Ergebnis verifiziert von',
            'both_teams_confirmed' => 'Beide Teams bestätigt',
            'official_signature' => 'Offizielle Unterschrift',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert boolean strings to actual booleans
        $booleanFields = [
            'overtime', 'both_teams_confirmed', 'official_signature'
        ];

        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false
                ]);
            }
        }

        // Set overtime_periods based on overtime flag
        if (!$this->overtime) {
            $this->merge(['overtime_periods' => 0]);
        }

        // Set actual times if not provided
        if (!$this->has('actual_end_time') && $this->has('actual_start_time') && $this->has('actual_duration')) {
            $startTime = new \DateTime($this->actual_start_time);
            $endTime = $startTime->add(new \DateInterval('PT' . $this->actual_duration . 'M'));
            $this->merge(['actual_end_time' => $endTime->format('Y-m-d H:i:s')]);
        }

        // Calculate duration if not provided
        if (!$this->has('actual_duration') && $this->has('actual_start_time') && $this->has('actual_end_time')) {
            $start = new \DateTime($this->actual_start_time);
            $end = new \DateTime($this->actual_end_time);
            $duration = $start->diff($end)->i + ($start->diff($end)->h * 60);
            $this->merge(['actual_duration' => $duration]);
        }

        // Calculate post-game winning margin
        if (!$this->has('post_game.winning_margin') && $this->has('team1_score') && $this->has('team2_score')) {
            $margin = abs($this->team1_score - $this->team2_score);
            $this->merge([
                'post_game' => array_merge($this->input('post_game', []), ['winning_margin' => $margin])
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $bracket = $this->route('bracket');
            
            // Validate game can have result recorded
            $this->validateGameStatus($validator, $bracket);
            
            // Validate score consistency
            $this->validateScoreConsistency($validator);
            
            // Validate overtime logic
            $this->validateOvertimeLogic($validator);
            
            // Validate period scores
            $this->validatePeriodScores($validator, $bracket);
            
            // Validate player statistics consistency
            $this->validatePlayerStatistics($validator, $bracket);
            
            // Validate team statistics
            $this->validateTeamStatistics($validator);
        });
    }

    /**
     * Validate game status allows result recording.
     */
    protected function validateGameStatus($validator, $bracket): void
    {
        if ($bracket->status !== 'in_progress') {
            $validator->errors()->add('team1_score', 'Das Spiel muss aktiv sein, um Ergebnisse einzutragen.');
        }

        if ($bracket->is_completed) {
            $validator->errors()->add('team1_score', 'Für dieses Spiel wurde bereits ein Ergebnis eingetragen.');
        }

        if (!$bracket->has_both_teams) {
            $validator->errors()->add('team1_score', 'Das Spiel benötigt zwei Teams, um ein Ergebnis einzutragen.');
        }
    }

    /**
     * Validate score consistency.
     */
    protected function validateScoreConsistency($validator): void
    {
        // Can't have a tie in elimination tournaments
        $bracket = $this->route('bracket');
        $tournament = $bracket->tournament;
        
        if (in_array($tournament->type, ['single_elimination', 'double_elimination'])) {
            if ($this->team1_score === $this->team2_score) {
                $validator->errors()->add('team2_score', 'K.O.-Spiele können nicht unentschieden enden.');
            }
        }

        // Validate that scores are reasonable
        if ($this->team1_score + $this->team2_score > 300) {
            $validator->errors()->add('team1_score', 'Die Gesamtpunktzahl erscheint unrealistisch hoch.');
        }

        if ($this->team1_score > 150 || $this->team2_score > 150) {
            $validator->errors()->add('team1_score', 'Einzelteam-Punktzahl erscheint unrealistisch hoch.');
        }
    }

    /**
     * Validate overtime logic.
     */
    protected function validateOvertimeLogic($validator): void
    {
        if ($this->overtime && (!$this->has('overtime_periods') || $this->overtime_periods < 1)) {
            $validator->errors()->add('overtime_periods', 'Bei Verlängerung muss die Anzahl der Verlängerungsperioden angegeben werden.');
        }

        if (!$this->overtime && $this->has('overtime_periods') && $this->overtime_periods > 0) {
            $validator->errors()->add('overtime', 'Verlängerungsperioden können nur bei Verlängerung angegeben werden.');
        }

        // Overtime is only possible with tied regulation score
        if ($this->overtime && $this->has('score_by_period')) {
            $regulationPeriods = array_filter($this->score_by_period, fn($period) => !($period['is_overtime'] ?? false));
            if (!empty($regulationPeriods)) {
                $team1Regulation = array_sum(array_column($regulationPeriods, 'team1'));
                $team2Regulation = array_sum(array_column($regulationPeriods, 'team2'));
                
                if ($team1Regulation !== $team2Regulation) {
                    $validator->errors()->add('overtime', 'Verlängerung ist nur bei Gleichstand nach regulärer Spielzeit möglich.');
                }
            }
        }
    }

    /**
     * Validate period scores add up to final score.
     */
    protected function validatePeriodScores($validator, $bracket): void
    {
        if (!$this->has('score_by_period') || !is_array($this->score_by_period)) {
            return;
        }

        $team1Total = array_sum(array_column($this->score_by_period, 'team1'));
        $team2Total = array_sum(array_column($this->score_by_period, 'team2'));

        if ($team1Total !== $this->team1_score) {
            $validator->errors()->add('score_by_period', 
                "Team 1: Summe der Periodenscores ({$team1Total}) entspricht nicht dem Endergebnis ({$this->team1_score}).");
        }

        if ($team2Total !== $this->team2_score) {
            $validator->errors()->add('score_by_period', 
                "Team 2: Summe der Periodenscores ({$team2Total}) entspricht nicht dem Endergebnis ({$this->team2_score}).");
        }

        // Validate period sequence
        $periods = array_column($this->score_by_period, 'period');
        $expectedPeriods = range(1, count($periods));
        
        if (array_diff($expectedPeriods, $periods)) {
            $validator->errors()->add('score_by_period', 'Perioden müssen lückenlos nummeriert sein (1, 2, 3, ...).');
        }
    }

    /**
     * Validate player statistics consistency.
     */
    protected function validatePlayerStatistics($validator, $bracket): void
    {
        if (!$this->has('player_stats') || !is_array($this->player_stats)) {
            return;
        }

        foreach ($this->player_stats as $index => $stats) {
            // Validate team membership
            if (isset($stats['team_id']) && 
                !in_array($stats['team_id'], [$bracket->team1_id, $bracket->team2_id])) {
                $validator->errors()->add("player_stats.{$index}.team_id", 
                    'Spieler muss einem der beiden Teams angehören.');
            }

            // Validate shot statistics consistency
            if (isset($stats['field_goals_made']) && isset($stats['field_goals_attempted'])) {
                if ($stats['field_goals_made'] > $stats['field_goals_attempted']) {
                    $validator->errors()->add("player_stats.{$index}.field_goals_made", 
                        'Getroffene Würfe können nicht höher sein als Versuche.');
                }
            }

            if (isset($stats['three_points_made']) && isset($stats['three_points_attempted'])) {
                if ($stats['three_points_made'] > $stats['three_points_attempted']) {
                    $validator->errors()->add("player_stats.{$index}.three_points_made", 
                        'Getroffene Dreier können nicht höher sein als Versuche.');
                }
            }

            if (isset($stats['free_throws_made']) && isset($stats['free_throws_attempted'])) {
                if ($stats['free_throws_made'] > $stats['free_throws_attempted']) {
                    $validator->errors()->add("player_stats.{$index}.free_throws_made", 
                        'Getroffene Freiwürfe können nicht höher sein als Versuche.');
                }
            }
        }
    }

    /**
     * Validate team statistics.
     */
    protected function validateTeamStatistics($validator): void
    {
        if (!$this->has('team_stats') || !is_array($this->team_stats)) {
            return;
        }

        foreach (['team1', 'team2'] as $team) {
            if (!isset($this->team_stats[$team])) continue;
            
            $stats = $this->team_stats[$team];
            
            // Validate timeout usage
            if (isset($stats['timeouts_used'])) {
                $tournament = $this->route('bracket')->tournament;
                $maxTimeouts = $tournament->game_rules['timeouts_per_half'] ?? 3;
                $totalTimeouts = $maxTimeouts * 2; // Assuming two halves
                
                if ($this->overtime) {
                    $totalTimeouts += $this->overtime_periods; // One timeout per overtime
                }
                
                if ($stats['timeouts_used'] > $totalTimeouts) {
                    $validator->errors()->add("team_stats.{$team}.timeouts_used", 
                        "Zu viele Timeouts verwendet (Maximum: {$totalTimeouts}).");
                }
            }
        }
    }
}