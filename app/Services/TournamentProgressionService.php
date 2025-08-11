<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\TournamentTeam;
use App\Models\TournamentBracket;
use App\Models\Game;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class TournamentProgressionService
{
    public function advanceTournament(Tournament $tournament): bool
    {
        return match($tournament->type) {
            'single_elimination' => $this->advanceSingleElimination($tournament),
            'double_elimination' => $this->advanceDoubleElimination($tournament),
            'round_robin' => $this->advanceRoundRobin($tournament),
            'swiss_system' => $this->advanceSwissSystem($tournament),
            'group_stage_knockout' => $this->advanceGroupStageKnockout($tournament),
            default => throw new InvalidArgumentException('Unbekannter Tournament-Typ: ' . $tournament->type),
        };
    }

    public function processGameResult(TournamentBracket $bracket, Game $game): bool
    {
        // Update bracket with game results
        $bracket->update([
            'team1_score' => $game->home_score,
            'team2_score' => $game->away_score,
            'score_by_period' => $game->score_by_period,
            'overtime' => $game->overtime_periods > 0,
            'overtime_periods' => $game->overtime_periods,
            'status' => 'completed',
            'actual_start_time' => $game->actual_start_time,
            'actual_end_time' => $game->actual_end_time,
            'actual_duration' => $game->duration,
        ]);

        // Determine winner and loser
        $winnerId = $game->home_score > $game->away_score ? $bracket->team1_id : $bracket->team2_id;
        $loserId = $game->home_score > $game->away_score ? $bracket->team2_id : $bracket->team1_id;

        $bracket->update([
            'winner_team_id' => $winnerId,
            'loser_team_id' => $loserId,
        ]);

        // Update team statistics
        $this->updateTeamStatistics($bracket, $game);

        // Advance teams to next round
        return $this->advanceTeams($bracket);
    }

    protected function advanceTeams(TournamentBracket $bracket): bool
    {
        $tournament = $bracket->tournament;

        return match($tournament->type) {
            'single_elimination' => $this->advanceTeamsSingleElimination($bracket),
            'double_elimination' => $this->advanceTeamsDoubleElimination($bracket),
            'round_robin' => $this->advanceTeamsRoundRobin($bracket),
            'swiss_system' => $this->advanceTeamsSwissSystem($bracket),
            'group_stage_knockout' => $this->advanceTeamsGroupStageKnockout($bracket),
            default => false,
        };
    }

    // Single Elimination Methods
    protected function advanceSingleElimination(Tournament $tournament): bool
    {
        $completedBrackets = $tournament->brackets()
                                      ->where('status', 'completed')
                                      ->get();

        foreach ($completedBrackets as $bracket) {
            $this->advanceTeamsSingleElimination($bracket);
        }

        return $this->checkTournamentCompletion($tournament);
    }

    protected function advanceTeamsSingleElimination(TournamentBracket $bracket): bool
    {
        if (!$bracket->winner_team_id) {
            return false;
        }

        // Advance winner to next round
        if ($bracket->winner_advances_to) {
            $nextBracket = TournamentBracket::find($bracket->winner_advances_to);
            
            if ($nextBracket) {
                if (!$nextBracket->team1_id) {
                    $nextBracket->update(['team1_id' => $bracket->winner_team_id]);
                } elseif (!$nextBracket->team2_id) {
                    $nextBracket->update(['team2_id' => $bracket->winner_team_id]);
                }

                // Check if next bracket is ready to be scheduled
                if ($nextBracket->team1_id && $nextBracket->team2_id) {
                    $nextBracket->update(['status' => 'pending']);
                    $this->scheduleNextBracket($nextBracket);
                }
            }
        } else {
            // This is the final - tournament completed
            $this->declareTournamentWinner($bracket->tournament, $bracket->winner_team_id);
        }

        // Eliminate the loser
        if ($bracket->loser_team_id) {
            $this->eliminateTeam($bracket->loser_team_id, $bracket->round_name);
        }

        return true;
    }

    // Double Elimination Methods
    protected function advanceDoubleElimination(Tournament $tournament): bool
    {
        $completedBrackets = $tournament->brackets()
                                      ->where('status', 'completed')
                                      ->get();

        foreach ($completedBrackets as $bracket) {
            $this->advanceTeamsDoubleElimination($bracket);
        }

        return $this->checkTournamentCompletion($tournament);
    }

    protected function advanceTeamsDoubleElimination(TournamentBracket $bracket): bool
    {
        if (!$bracket->winner_team_id) {
            return false;
        }

        $tournament = $bracket->tournament;

        if ($bracket->bracket_type === 'main') {
            // Winner's bracket
            $this->advanceWinnerInWinnersBracket($bracket);
            $this->dropLoserToLosersBracket($bracket);
        } elseif ($bracket->bracket_type === 'consolation') {
            // Loser's bracket
            $this->advanceWinnerInLosersBracket($bracket);
            $this->eliminateLoserFromLosersBracket($bracket);
        }

        return true;
    }

    protected function advanceWinnerInWinnersBracket(TournamentBracket $bracket): void
    {
        if ($bracket->winner_advances_to) {
            $nextBracket = TournamentBracket::find($bracket->winner_advances_to);
            
            if ($nextBracket) {
                if (!$nextBracket->team1_id) {
                    $nextBracket->update(['team1_id' => $bracket->winner_team_id]);
                } elseif (!$nextBracket->team2_id) {
                    $nextBracket->update(['team2_id' => $bracket->winner_team_id]);
                }

                if ($nextBracket->team1_id && $nextBracket->team2_id) {
                    $nextBracket->update(['status' => 'pending']);
                }
            }
        }
    }

    protected function dropLoserToLosersBracket(TournamentBracket $bracket): void
    {
        if ($bracket->loser_advances_to) {
            $losersBracket = TournamentBracket::find($bracket->loser_advances_to);
            
            if ($losersBracket) {
                if (!$losersBracket->team1_id) {
                    $losersBracket->update(['team1_id' => $bracket->loser_team_id]);
                } elseif (!$losersBracket->team2_id) {
                    $losersBracket->update(['team2_id' => $bracket->loser_team_id]);
                }

                if ($losersBracket->team1_id && $losersBracket->team2_id) {
                    $losersBracket->update(['status' => 'pending']);
                }
            }
        }
    }

    // Round Robin Methods
    protected function advanceRoundRobin(Tournament $tournament): bool
    {
        if ($tournament->groups_count > 1) {
            return $this->advanceRoundRobinGroups($tournament);
        }

        // Single round robin - check if all games completed
        $totalBrackets = $tournament->brackets()->count();
        $completedBrackets = $tournament->brackets()->where('status', 'completed')->count();

        if ($totalBrackets === $completedBrackets) {
            $this->calculateFinalStandings($tournament);
            return $this->completeTournament($tournament);
        }

        return false;
    }

    protected function advanceTeamsRoundRobin(TournamentBracket $bracket): bool
    {
        // Update team statistics (already done in processGameResult)
        // Check if group/tournament is complete
        return $this->checkRoundRobinCompletion($bracket->tournament, $bracket->group_name);
    }

    protected function advanceRoundRobinGroups(Tournament $tournament): bool
    {
        $groups = $tournament->brackets()
                            ->whereNotNull('group_name')
                            ->groupBy('group_name');

        $allGroupsComplete = true;

        foreach ($groups as $groupName => $groupBrackets) {
            if (!$this->checkRoundRobinCompletion($tournament, $groupName)) {
                $allGroupsComplete = false;
            }
        }

        if ($allGroupsComplete) {
            // All groups complete, advance to knockout stage if applicable
            $this->advanceGroupWinnersToKnockout($tournament);
            return true;
        }

        return false;
    }

    // Swiss System Methods
    protected function advanceSwissSystem(Tournament $tournament): bool
    {
        $currentRound = $tournament->brackets()
                                 ->whereNotNull('swiss_round')
                                 ->max('swiss_round');

        $currentRoundBrackets = $tournament->brackets()
                                         ->where('swiss_round', $currentRound)
                                         ->get();

        $allCurrentRoundComplete = $currentRoundBrackets->every(fn($b) => $b->status === 'completed');

        if ($allCurrentRoundComplete) {
            $maxRounds = $this->calculateSwissRounds($tournament->registered_teams);
            
            if ($currentRound < $maxRounds) {
                // Generate next round
                app(BracketGeneratorService::class)->generateNextSwissRound($tournament, $currentRound + 1);
            } else {
                // Tournament complete
                $this->calculateFinalStandings($tournament);
                return $this->completeTournament($tournament);
            }
        }

        return false;
    }

    protected function advanceTeamsSwissSystem(TournamentBracket $bracket): bool
    {
        // Swiss system doesn't eliminate teams, just tracks points
        return $this->advanceSwissSystem($bracket->tournament);
    }

    // Group Stage + Knockout Methods
    protected function advanceGroupStageKnockout(Tournament $tournament): bool
    {
        // First check if group stage is complete
        if (!$this->isGroupStageComplete($tournament)) {
            return $this->advanceRoundRobinGroups($tournament);
        }

        // Group stage complete, advance knockout stage
        return $this->advanceSingleElimination($tournament);
    }

    protected function advanceTeamsGroupStageKnockout(TournamentBracket $bracket): bool
    {
        if ($bracket->group_name) {
            // Group stage game
            return $this->advanceTeamsRoundRobin($bracket);
        } else {
            // Knockout stage game
            return $this->advanceTeamsSingleElimination($bracket);
        }
    }

    // Utility Methods
    protected function updateTeamStatistics(TournamentBracket $bracket, Game $game): void
    {
        $team1 = $bracket->team1;
        $team2 = $bracket->team2;

        if ($team1 && $team2) {
            if ($bracket->winner_team_id === $team1->id) {
                $team1->recordWin($game->home_score, $game->away_score);
                $team2->recordLoss($game->away_score, $game->home_score);
            } else {
                $team2->recordWin($game->away_score, $game->home_score);
                $team1->recordLoss($game->home_score, $game->away_score);
            }
        }
    }

    protected function eliminateTeam(int $teamId, string $eliminationRound): void
    {
        $team = TournamentTeam::find($teamId);
        if ($team) {
            $team->eliminate($eliminationRound);
        }
    }

    protected function declareTournamentWinner(Tournament $tournament, int $winnerTeamId): void
    {
        $winnerTeam = TournamentTeam::find($winnerTeamId);
        if ($winnerTeam) {
            $winnerTeam->update([
                'final_position' => 1,
                'elimination_round' => 'winner',
            ]);
        }

        $this->completeTournament($tournament);
    }

    protected function completeTournament(Tournament $tournament): bool
    {
        $tournament->update([
            'status' => 'completed',
            'completed_games' => $tournament->brackets()->where('status', 'completed')->count(),
        ]);

        // Calculate final standings
        $this->calculateFinalStandings($tournament);

        // Generate awards
        $this->generateTournamentAwards($tournament);

        return true;
    }

    protected function calculateFinalStandings(Tournament $tournament): void
    {
        $teams = $tournament->tournamentTeams()
                           ->where('status', 'approved')
                           ->get();

        // Sort teams based on tournament type
        $sortedTeams = match($tournament->type) {
            'round_robin', 'swiss_system' => $this->sortTeamsByPointsAndStats($teams),
            'single_elimination', 'double_elimination' => $this->sortTeamsByEliminationRound($teams),
            'group_stage_knockout' => $this->sortTeamsGroupStageKnockout($teams),
            default => $teams,
        };

        $position = 1;
        foreach ($sortedTeams as $team) {
            if (!$team->final_position) {
                $team->update(['final_position' => $position++]);
            }
        }
    }

    protected function sortTeamsByPointsAndStats(Collection $teams): Collection
    {
        return $teams->sortByDesc([
            ['tournament_points', 'desc'],
            ['point_differential', 'desc'],
            ['points_for', 'desc'],
        ]);
    }

    protected function sortTeamsByEliminationRound(Collection $teams): Collection
    {
        $roundOrder = [
            'winner' => 1,
            'final' => 2,
            'semifinal' => 3,
            'quarterfinal' => 4,
            'round_of_16' => 5,
            'round_of_32' => 6,
            'group_stage' => 7,
        ];

        return $teams->sortBy(function ($team) use ($roundOrder) {
            return $roundOrder[$team->elimination_round] ?? 999;
        });
    }

    protected function sortTeamsGroupStageKnockout(Collection $teams): Collection
    {
        // More complex sorting for group + knockout tournaments
        return $teams->sortByDesc([
            ['tournament_points', 'desc'],
            ['wins', 'desc'],
            ['point_differential', 'desc'],
        ]);
    }

    protected function checkTournamentCompletion(Tournament $tournament): bool
    {
        $totalBrackets = $tournament->brackets()->count();
        $completedBrackets = $tournament->brackets()->where('status', 'completed')->count();

        if ($totalBrackets === $completedBrackets) {
            return $this->completeTournament($tournament);
        }

        return false;
    }

    protected function checkRoundRobinCompletion(Tournament $tournament, string $groupName = null): bool
    {
        $query = $tournament->brackets();
        
        if ($groupName) {
            $query->where('group_name', $groupName);
        }

        $totalBrackets = $query->count();
        $completedBrackets = $query->where('status', 'completed')->count();

        return $totalBrackets === $completedBrackets;
    }

    protected function isGroupStageComplete(Tournament $tournament): bool
    {
        $groupStageBrackets = $tournament->brackets()
                                       ->whereNotNull('group_name')
                                       ->count();

        $completedGroupStageBrackets = $tournament->brackets()
                                                ->whereNotNull('group_name')
                                                ->where('status', 'completed')
                                                ->count();

        return $groupStageBrackets === $completedGroupStageBrackets;
    }

    protected function advanceGroupWinnersToKnockout(Tournament $tournament): void
    {
        $groups = $tournament->brackets()
                            ->whereNotNull('group_name')
                            ->with(['team1', 'team2'])
                            ->get()
                            ->groupBy('group_name');

        $groupWinners = collect();

        foreach ($groups as $groupName => $groupBrackets) {
            $groupTeams = $this->getGroupStandings($groupBrackets);
            $winner = $groupTeams->first(); // Top team in group
            $groupWinners->push($winner);
        }

        // Generate knockout brackets with group winners
        app(BracketGeneratorService::class)->generateKnockoutFromGroupWinners($tournament, $groupWinners);
    }

    protected function getGroupStandings(Collection $groupBrackets): Collection
    {
        // Calculate group standings based on completed games
        $teamStats = collect();
        
        foreach ($groupBrackets as $bracket) {
            if ($bracket->status === 'completed') {
                // Update team statistics based on this game
                // This is a simplified version
            }
        }

        return $teamStats->sortByDesc('points');
    }

    protected function scheduleNextBracket(TournamentBracket $bracket): void
    {
        // Auto-schedule the next bracket if possible
        $tournament = $bracket->tournament;
        
        // Simple scheduling: next available time slot
        $lastScheduled = $tournament->brackets()
                                  ->whereNotNull('scheduled_at')
                                  ->orderBy('scheduled_at', 'desc')
                                  ->first();

        if ($lastScheduled) {
            $nextTime = $lastScheduled->scheduled_at->addHours(2); // 2 hour gap
        } else {
            $nextTime = $tournament->start_date->setTime(
                $tournament->daily_start_time->hour,
                $tournament->daily_start_time->minute
            );
        }

        $bracket->update([
            'scheduled_at' => $nextTime,
            'venue' => $tournament->primary_venue,
            'status' => 'scheduled',
        ]);
    }

    protected function generateTournamentAwards(Tournament $tournament): void
    {
        // Generate basic awards (champion, runner-up, etc.)
        $champion = $tournament->tournamentTeams()
                             ->where('final_position', 1)
                             ->first();

        if ($champion) {
            $tournament->awards()->create([
                'award_name' => 'Champion',
                'award_type' => 'team_award',
                'award_category' => 'champion',
                'recipient_team_id' => $champion->id,
                'selected_at' => now(),
                'selection_method' => 'automatic',
            ]);
        }

        $runnerUp = $tournament->tournamentTeams()
                             ->where('final_position', 2)
                             ->first();

        if ($runnerUp) {
            $tournament->awards()->create([
                'award_name' => 'Runner-Up',
                'award_type' => 'team_award',
                'award_category' => 'runner_up',
                'recipient_team_id' => $runnerUp->id,
                'selected_at' => now(),
                'selection_method' => 'automatic',
            ]);
        }

        // Generate statistical awards (MVP, top scorer, etc.)
        $this->generateStatisticalAwards($tournament);
    }

    protected function generateStatisticalAwards(Tournament $tournament): void
    {
        // This would analyze game statistics to award MVP, top scorer, etc.
        // Implementation would depend on available statistics
    }

    protected function calculateSwissRounds(int $teamCount): int
    {
        return (int) ceil(log($teamCount, 2));
    }
}