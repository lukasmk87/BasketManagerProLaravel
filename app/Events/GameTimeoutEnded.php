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

class GameTimeoutEnded implements ShouldBroadcast
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
        return 'game.timeout.ended';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'game_id' => $this->game->id,
            'timeout_info' => [
                'is_in_timeout' => $this->liveGame->is_in_timeout,
                'timeout_team' => null,
            ],
            'clock_info' => [
                'period_is_running' => $this->liveGame->period_is_running,
                'game_phase' => $this->liveGame->game_phase,
                'period_time_remaining' => $this->liveGame->period_time_remaining,
                'current_period' => $this->liveGame->current_period,
            ],
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