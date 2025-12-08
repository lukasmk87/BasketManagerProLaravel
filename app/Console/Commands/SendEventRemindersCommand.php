<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\TrainingSession;
use App\Notifications\EventResponseReminder;
use App\Services\PlayerAvailabilityService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SendEventRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:send-reminders
                            {--hours=48 : Hours before event to send reminder}
                            {--dry-run : Preview without sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders to players who have not responded to upcoming events';

    /**
     * Execute the console command.
     */
    public function handle(PlayerAvailabilityService $availabilityService): int
    {
        $hours = (int) $this->option('hours');
        $dryRun = $this->option('dry-run');

        $this->info("Checking for events within the next {$hours} hours...");

        if ($dryRun) {
            $this->warn('Dry run mode - no notifications will be sent.');
            $this->newLine();
        }

        try {
            $now = now();
            $cutoff = $now->copy()->addHours($hours);

            // Get upcoming games
            $games = Game::where('scheduled_at', '>', $now)
                ->where('scheduled_at', '<=', $cutoff)
                ->whereNotIn('status', ['cancelled', 'finished', 'forfeited'])
                ->with(['homeTeam.players.user', 'awayTeam.players.user'])
                ->get();

            // Get upcoming trainings
            $trainings = TrainingSession::where('scheduled_at', '>', $now)
                ->where('scheduled_at', '<=', $cutoff)
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->with(['team.players.user'])
                ->get();

            $stats = [
                'games_checked' => $games->count(),
                'trainings_checked' => $trainings->count(),
                'reminders_sent' => 0,
                'players_notified' => 0,
            ];

            // Process games
            foreach ($games as $game) {
                $this->processGame($game, $availabilityService, $stats, $dryRun);
            }

            // Process trainings
            foreach ($trainings as $training) {
                $this->processTraining($training, $availabilityService, $stats, $dryRun);
            }

            $this->newLine();
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Games checked', $stats['games_checked']],
                    ['Trainings checked', $stats['trainings_checked']],
                    ['Reminders sent', $stats['reminders_sent']],
                    ['Players notified', $stats['players_notified']],
                ]
            );

            Log::info('Event reminders processed', $stats);

            $this->info('Event reminder processing completed.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error processing event reminders: {$e->getMessage()}");

            Log::error('Failed to process event reminders', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Process a game and send reminders to players without response.
     */
    protected function processGame(
        Game $game,
        PlayerAvailabilityService $availabilityService,
        array &$stats,
        bool $dryRun
    ): void {
        $players = collect();

        // Collect players from both teams
        if ($game->homeTeam) {
            $players = $players->merge($game->homeTeam->players);
        }
        if ($game->awayTeam) {
            $players = $players->merge($game->awayTeam->players);
        }

        $this->sendRemindersToPlayers(
            $players,
            'game',
            $game->id,
            EventResponseReminder::fromGame($game),
            $availabilityService,
            $stats,
            $dryRun
        );
    }

    /**
     * Process a training session and send reminders to players without response.
     */
    protected function processTraining(
        TrainingSession $training,
        PlayerAvailabilityService $availabilityService,
        array &$stats,
        bool $dryRun
    ): void {
        $players = $training->team?->players ?? collect();

        $this->sendRemindersToPlayers(
            $players,
            'training',
            $training->id,
            EventResponseReminder::fromTraining($training),
            $availabilityService,
            $stats,
            $dryRun
        );
    }

    /**
     * Send reminders to players who haven't responded.
     */
    protected function sendRemindersToPlayers(
        Collection $players,
        string $eventType,
        int $eventId,
        EventResponseReminder $notification,
        PlayerAvailabilityService $availabilityService,
        array &$stats,
        bool $dryRun
    ): void {
        $playersWithoutResponse = $availabilityService->getPlayersWithoutResponse(
            $eventType,
            $eventId,
            $players->pluck('id')->toArray()
        );

        foreach ($playersWithoutResponse as $playerId) {
            $player = $players->firstWhere('id', $playerId);

            if (! $player || ! $player->user) {
                continue;
            }

            if ($dryRun) {
                $this->line("  Would notify: {$player->user->name} ({$player->user->email})");
            } else {
                $player->user->notify(clone $notification);
                $stats['reminders_sent']++;
            }

            $stats['players_notified']++;
        }
    }
}
