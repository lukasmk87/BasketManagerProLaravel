<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\TournamentTeam;
use App\Models\TournamentBracket;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class BracketGeneratorService
{
    public function generateBrackets(Tournament $tournament): bool
    {
        // Clear any existing brackets
        $tournament->brackets()->delete();

        $teams = $tournament->tournamentTeams()
                           ->where('status', 'approved')
                           ->get();

        if ($teams->count() < $tournament->min_teams) {
            throw new InvalidArgumentException('Nicht genügend Teams für Bracket-Generierung');
        }

        return match($tournament->type) {
            'single_elimination' => $this->generateSingleElimination($tournament, $teams),
            'double_elimination' => $this->generateDoubleElimination($tournament, $teams),
            'round_robin' => $this->generateRoundRobin($tournament, $teams),
            'swiss_system' => $this->generateSwissSystem($tournament, $teams),
            'group_stage_knockout' => $this->generateGroupStageKnockout($tournament, $teams),
            default => throw new InvalidArgumentException('Unbekannter Tournament-Typ: ' . $tournament->type),
        };
    }

    protected function generateSingleElimination(Tournament $tournament, Collection $teams): bool
    {
        $teamCount = $teams->count();
        $rounds = $this->calculateRounds($teamCount);
        
        // Seed the teams
        $seededTeams = $this->seedTeams($teams);
        
        // Calculate bracket size (next power of 2)
        $bracketSize = 2 ** $rounds;
        
        // Generate first round matchups
        $firstRoundBrackets = $this->generateFirstRound($tournament, $seededTeams, $bracketSize);
        
        // Generate subsequent rounds
        $this->generateSubsequentRounds($tournament, $firstRoundBrackets, $rounds);
        
        return true;
    }

    protected function generateDoubleElimination(Tournament $tournament, Collection $teams): bool
    {
        $teamCount = $teams->count();
        $seededTeams = $this->seedTeams($teams);
        
        // Generate Winner's Bracket
        $this->generateWinnersBracket($tournament, $seededTeams);
        
        // Generate Loser's Bracket
        $this->generateLosersBracket($tournament, $seededTeams);
        
        // Generate Grand Final
        $this->generateGrandFinal($tournament);
        
        return true;
    }

    protected function generateRoundRobin(Tournament $tournament, Collection $teams): bool
    {
        $teamList = $teams->toArray();
        $teamCount = count($teamList);
        
        if ($tournament->groups_count > 1) {
            return $this->generateRoundRobinGroups($tournament, $teams);
        }
        
        $round = 1;
        $brackets = [];
        
        // Generate all possible matchups
        for ($i = 0; $i < $teamCount; $i++) {
            for ($j = $i + 1; $j < $teamCount; $j++) {
                $brackets[] = TournamentBracket::create([
                    'tournament_id' => $tournament->id,
                    'bracket_type' => 'main',
                    'round' => $round,
                    'round_name' => "Runde {$round}",
                    'position_in_round' => count($brackets) + 1,
                    'total_rounds' => $teamCount - 1,
                    'team1_id' => $teamList[$i]['id'],
                    'team2_id' => $teamList[$j]['id'],
                    'team1_seed' => $teamList[$i]['seed'],
                    'team2_seed' => $teamList[$j]['seed'],
                    'status' => 'pending',
                ]);
                
                // Distribute games across rounds
                if (count($brackets) % ($teamCount / 2) === 0) {
                    $round++;
                }
            }
        }
        
        return true;
    }

    protected function generateSwissSystem(Tournament $tournament, Collection $teams): bool
    {
        $teamCount = $teams->count();
        $rounds = $this->calculateSwissRounds($teamCount);
        
        // First round: pair teams by initial seeding
        $this->generateSwissFirstRound($tournament, $teams);
        
        // Mark that subsequent rounds will be generated dynamically
        $tournament->update([
            'total_games' => ($teamCount / 2) * $rounds,
        ]);
        
        return true;
    }

    protected function generateGroupStageKnockout(Tournament $tournament, Collection $teams): bool
    {
        // First generate group stage
        $this->generateRoundRobinGroups($tournament, $teams);
        
        // Then generate knockout stage for group winners
        $this->generateKnockoutFromGroups($tournament);
        
        return true;
    }

    // Helper Methods for Single Elimination
    protected function calculateRounds(int $teamCount): int
    {
        return (int) ceil(log($teamCount, 2));
    }

    protected function seedTeams(Collection $teams): Collection
    {
        return $teams->sortBy('seed')->values();
    }

    protected function generateFirstRound(Tournament $tournament, Collection $seededTeams, int $bracketSize): Collection
    {
        $brackets = collect();
        $teamCount = $seededTeams->count();
        
        // Create brackets with byes if necessary
        $matchups = $this->createFirstRoundMatchups($seededTeams, $bracketSize);
        
        $position = 1;
        foreach ($matchups as $matchup) {
            $bracket = TournamentBracket::create([
                'tournament_id' => $tournament->id,
                'bracket_type' => 'main',
                'round' => 1,
                'round_name' => $this->getRoundName(1, $this->calculateRounds($teamCount)),
                'position_in_round' => $position++,
                'total_rounds' => $this->calculateRounds($teamCount),
                'team1_id' => $matchup['team1']?->id,
                'team2_id' => $matchup['team2']?->id,
                'team1_seed' => $matchup['team1']?->seed,
                'team2_seed' => $matchup['team2']?->seed,
                'status' => $matchup['team2'] ? 'pending' : 'bye',
            ]);
            
            // If it's a bye, set the winner immediately
            if (!$matchup['team2']) {
                $bracket->update(['winner_team_id' => $matchup['team1']->id]);
            }
            
            $brackets->push($bracket);
        }
        
        return $brackets;
    }

    protected function createFirstRoundMatchups(Collection $seededTeams, int $bracketSize): array
    {
        $matchups = [];
        $teams = $seededTeams->toArray();
        
        // Fill with nulls to reach bracket size
        while (count($teams) < $bracketSize) {
            $teams[] = null;
        }
        
        // Create matchups using standard tournament seeding
        for ($i = 0; $i < $bracketSize / 2; $i++) {
            $team1Index = $i;
            $team2Index = $bracketSize - 1 - $i;
            
            $matchups[] = [
                'team1' => $teams[$team1Index],
                'team2' => $teams[$team2Index],
            ];
        }
        
        return $matchups;
    }

    protected function generateSubsequentRounds(Tournament $tournament, Collection $previousRound, int $totalRounds): void
    {
        $currentRound = 2;
        $previousBrackets = $previousRound;
        
        while ($currentRound <= $totalRounds) {
            $nextRoundBrackets = collect();
            $position = 1;
            
            // Pair up brackets from previous round
            for ($i = 0; $i < $previousBrackets->count(); $i += 2) {
                $bracket1 = $previousBrackets[$i];
                $bracket2 = $previousBrackets->get($i + 1);
                
                $nextBracket = TournamentBracket::create([
                    'tournament_id' => $tournament->id,
                    'bracket_type' => 'main',
                    'round' => $currentRound,
                    'round_name' => $this->getRoundName($currentRound, $totalRounds),
                    'position_in_round' => $position++,
                    'total_rounds' => $totalRounds,
                    'status' => 'pending',
                    'matchup_description' => $this->getMatchupDescription($bracket1, $bracket2),
                ]);
                
                // Set advancement links
                $bracket1->update(['winner_advances_to' => $nextBracket->id]);
                if ($bracket2) {
                    $bracket2->update(['winner_advances_to' => $nextBracket->id]);
                }
                
                $nextRoundBrackets->push($nextBracket);
            }
            
            $previousBrackets = $nextRoundBrackets;
            $currentRound++;
        }
        
        // Generate third place game if enabled
        if ($tournament->third_place_game) {
            $this->generateThirdPlaceGame($tournament, $previousBrackets->first());
        }
    }

    // Helper Methods for Double Elimination
    protected function generateWinnersBracket(Tournament $tournament, Collection $seededTeams): void
    {
        $teamCount = $seededTeams->count();
        $rounds = $this->calculateRounds($teamCount);
        $bracketSize = 2 ** $rounds;
        
        // Generate first round
        $firstRoundBrackets = $this->generateFirstRound($tournament, $seededTeams, $bracketSize);
        
        // Generate subsequent winner bracket rounds
        $this->generateWinnersBracketRounds($tournament, $firstRoundBrackets, $rounds);
    }

    protected function generateWinnersBracketRounds(Tournament $tournament, Collection $previousRound, int $totalRounds): void
    {
        $currentRound = 2;
        $previousBrackets = $previousRound;
        
        while ($currentRound <= $totalRounds) {
            $nextRoundBrackets = collect();
            $position = 1;
            
            for ($i = 0; $i < $previousBrackets->count(); $i += 2) {
                $bracket1 = $previousBrackets[$i];
                $bracket2 = $previousBrackets->get($i + 1);
                
                $nextBracket = TournamentBracket::create([
                    'tournament_id' => $tournament->id,
                    'bracket_type' => 'main',
                    'round' => $currentRound,
                    'round_name' => "Winners Round {$currentRound}",
                    'position_in_round' => $position++,
                    'total_rounds' => $totalRounds,
                    'status' => 'pending',
                ]);
                
                // Winner advances to next winners round
                $bracket1->update(['winner_advances_to' => $nextBracket->id]);
                if ($bracket2) {
                    $bracket2->update(['winner_advances_to' => $nextBracket->id]);
                }
                
                $nextRoundBrackets->push($nextBracket);
            }
            
            $previousBrackets = $nextRoundBrackets;
            $currentRound++;
        }
    }

    protected function generateLosersBracket(Tournament $tournament, Collection $seededTeams): void
    {
        // Complex loser's bracket logic
        // Implementation would depend on specific double elimination format
        
        // Create initial losers bracket matches
        $losersBracketRounds = $this->calculateLosersBracketRounds($seededTeams->count());
        
        for ($round = 1; $round <= $losersBracketRounds; $round++) {
            $this->generateLosersBracketRound($tournament, $round);
        }
    }

    protected function generateLosersBracketRound(Tournament $tournament, int $round): void
    {
        // Simplified loser's bracket round generation
        TournamentBracket::create([
            'tournament_id' => $tournament->id,
            'bracket_type' => 'consolation',
            'round' => $round,
            'round_name' => "Losers Round {$round}",
            'position_in_round' => 1,
            'total_rounds' => $this->calculateLosersBracketRounds($tournament->registered_teams),
            'status' => 'pending',
        ]);
    }

    protected function generateGrandFinal(Tournament $tournament): void
    {
        TournamentBracket::create([
            'tournament_id' => $tournament->id,
            'bracket_type' => 'main',
            'round' => 999, // Special round number for grand final
            'round_name' => 'Grand Final',
            'position_in_round' => 1,
            'total_rounds' => 999,
            'status' => 'pending',
        ]);
    }

    // Helper Methods for Round Robin
    protected function generateRoundRobinGroups(Tournament $tournament, Collection $teams): bool
    {
        $groupCount = $tournament->groups_count;
        $teamsPerGroup = (int) ceil($teams->count() / $groupCount);
        
        $groups = $teams->chunk($teamsPerGroup);
        $groupNames = $this->generateGroupNames($groupCount);
        
        foreach ($groups as $index => $groupTeams) {
            $groupName = $groupNames[$index];
            $this->generateSingleGroupRoundRobin($tournament, $groupTeams, $groupName);
        }
        
        return true;
    }

    protected function generateSingleGroupRoundRobin(Tournament $tournament, Collection $teams, string $groupName): void
    {
        $teamList = $teams->toArray();
        $teamCount = count($teamList);
        $round = 1;
        
        for ($i = 0; $i < $teamCount; $i++) {
            for ($j = $i + 1; $j < $teamCount; $j++) {
                TournamentBracket::create([
                    'tournament_id' => $tournament->id,
                    'bracket_type' => 'main',
                    'round' => $round,
                    'round_name' => "Group {$groupName} - Round {$round}",
                    'position_in_round' => ($i * $teamCount) + $j,
                    'total_rounds' => $teamCount - 1,
                    'team1_id' => $teamList[$i]['id'],
                    'team2_id' => $teamList[$j]['id'],
                    'team1_seed' => $teamList[$i]['seed'],
                    'team2_seed' => $teamList[$j]['seed'],
                    'group_name' => $groupName,
                    'status' => 'pending',
                ]);
            }
        }
    }

    // Helper Methods for Swiss System
    protected function generateSwissFirstRound(Tournament $tournament, Collection $teams): void
    {
        $seededTeams = $teams->sortBy('seed')->values();
        $teamCount = $seededTeams->count();
        
        // Pair top half against bottom half
        $topHalf = $seededTeams->take($teamCount / 2);
        $bottomHalf = $seededTeams->skip($teamCount / 2);
        
        $position = 1;
        foreach ($topHalf as $index => $topTeam) {
            $bottomTeam = $bottomHalf->get($index);
            
            if ($bottomTeam) {
                TournamentBracket::create([
                    'tournament_id' => $tournament->id,
                    'bracket_type' => 'main',
                    'round' => 1,
                    'round_name' => 'Swiss Round 1',
                    'position_in_round' => $position++,
                    'total_rounds' => $this->calculateSwissRounds($teamCount),
                    'team1_id' => $topTeam->id,
                    'team2_id' => $bottomTeam->id,
                    'team1_seed' => $topTeam->seed,
                    'team2_seed' => $bottomTeam->seed,
                    'swiss_round' => 1,
                    'status' => 'pending',
                ]);
            }
        }
    }

    protected function calculateSwissRounds(int $teamCount): int
    {
        return (int) ceil(log($teamCount, 2));
    }

    // Utility Methods
    protected function getRoundName(int $round, int $totalRounds): string
    {
        $remainingTeams = 2 ** ($totalRounds - $round + 1);
        
        return match($remainingTeams) {
            2 => 'Finale',
            4 => 'Halbfinale',
            8 => 'Viertelfinale',
            16 => 'Achtelfinale',
            32 => 'Runde der letzten 32',
            64 => 'Runde der letzten 64',
            default => "Runde {$round}",
        };
    }

    protected function getMatchupDescription(TournamentBracket $bracket1, TournamentBracket $bracket2 = null): string
    {
        if (!$bracket2) {
            return "Sieger von Spiel {$bracket1->position_in_round}";
        }
        
        return "Sieger von Spiel {$bracket1->position_in_round} vs Sieger von Spiel {$bracket2->position_in_round}";
    }

    protected function generateThirdPlaceGame(Tournament $tournament, TournamentBracket $finalBracket): void
    {
        TournamentBracket::create([
            'tournament_id' => $tournament->id,
            'bracket_type' => 'third_place',
            'round' => $finalBracket->round,
            'round_name' => 'Spiel um Platz 3',
            'position_in_round' => 1,
            'total_rounds' => $finalBracket->total_rounds,
            'status' => 'pending',
        ]);
    }

    protected function generateGroupNames(int $count): array
    {
        $names = [];
        for ($i = 0; $i < $count; $i++) {
            $names[] = chr(65 + $i); // A, B, C, D, etc.
        }
        return $names;
    }

    protected function calculateLosersBracketRounds(int $teamCount): int
    {
        return $this->calculateRounds($teamCount) * 2 - 2;
    }

    protected function generateKnockoutFromGroups(Tournament $tournament): void
    {
        // This would generate knockout brackets from group winners
        // Implementation depends on specific tournament format
    }

    // Dynamic Swiss Pairing (called between rounds)
    public function generateNextSwissRound(Tournament $tournament, int $round): bool
    {
        $teams = $tournament->tournamentTeams()
                           ->where('status', 'approved')
                           ->get()
                           ->sortByDesc('tournament_points')
                           ->values();
        
        $pairings = $this->calculateSwissPairings($teams, $round);
        
        $position = 1;
        foreach ($pairings as $pairing) {
            TournamentBracket::create([
                'tournament_id' => $tournament->id,
                'bracket_type' => 'main',
                'round' => $round,
                'round_name' => "Swiss Round {$round}",
                'position_in_round' => $position++,
                'total_rounds' => $this->calculateSwissRounds($teams->count()),
                'team1_id' => $pairing['team1']->id,
                'team2_id' => $pairing['team2']->id,
                'team1_seed' => $pairing['team1']->seed,
                'team2_seed' => $pairing['team2']->seed,
                'swiss_round' => $round,
                'status' => 'pending',
            ]);
        }
        
        return true;
    }

    protected function calculateSwissPairings(Collection $teams, int $round): array
    {
        // Simplified Swiss pairing algorithm
        // In practice, this would be more complex to avoid repeat matchups
        
        $pairings = [];
        $availableTeams = $teams->toArray();
        
        while (count($availableTeams) >= 2) {
            $team1 = array_shift($availableTeams);
            $team2 = array_shift($availableTeams);
            
            $pairings[] = [
                'team1' => $team1,
                'team2' => $team2,
            ];
        }
        
        return $pairings;
    }
}