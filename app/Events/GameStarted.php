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

class GameStarted implements ShouldBroadcast
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
        return 'game.started';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'game_id' => $this->game->id,
            'status' => $this->game->status,
            'home_team' => [
                'id' => $this->game->home_team_id,
                'name' => $this->game->homeTeam->name,
            ],
            'away_team' => [
                'id' => $this->game->away_team_id,
                'name' => $this->game->awayTeam->name,
            ],
            'live_game' => [
                'id' => $this->liveGame->id,
                'current_period' => $this->liveGame->current_period,
                'period_time_remaining' => $this->liveGame->period_time_remaining,
                'current_score_home' => $this->liveGame->current_score_home,
                'current_score_away' => $this->liveGame->current_score_away,
                'game_phase' => $this->liveGame->game_phase,
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}