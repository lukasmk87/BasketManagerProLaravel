<?php

namespace App\Events;

use App\Models\Game;
use App\Models\LiveGame;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameScoreUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Game $game,
        public LiveGame $liveGame
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("game.{$this->game->id}"),
            new Channel("team.{$this->game->home_team_id}"),
            new Channel("team.{$this->game->away_team_id}"),
            new Channel('live-games'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'game.score.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'game_id' => $this->game->id,
            'scores' => [
                'home' => $this->liveGame->current_score_home,
                'away' => $this->liveGame->current_score_away,
                'difference' => $this->liveGame->current_score_difference,
                'leading_team' => $this->liveGame->leading_team,
            ],
            'game_time' => [
                'current_period' => $this->liveGame->current_period,
                'time_remaining' => $this->liveGame->period_time_remaining,
                'period_is_running' => $this->liveGame->period_is_running,
                'game_phase' => $this->liveGame->game_phase,
            ],
            'team_stats' => [
                'home' => [
                    'fouls' => $this->liveGame->fouls_home_total,
                    'timeouts_remaining' => $this->liveGame->timeouts_home_remaining,
                ],
                'away' => [
                    'fouls' => $this->liveGame->fouls_away_total,
                    'timeouts_remaining' => $this->liveGame->timeouts_away_remaining,
                ],
            ],
            'last_update_at' => $this->liveGame->last_update_at->toISOString(),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Determine if this event should broadcast.
     */
    public function broadcastWhen(): bool
    {
        return $this->game->canBeScored() && $this->liveGame->is_being_broadcasted;
    }
}