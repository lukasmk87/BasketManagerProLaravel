<?php

namespace App\Events;

use App\Models\Game;
use App\Models\GameAction;
use App\Models\LiveGame;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameActionAdded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Game $game,
        public GameAction $action,
        public LiveGame $liveGame
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("game.{$this->game->id}"),
            new Channel("team.{$this->action->team_id}"),
            new Channel("player.{$this->action->player_id}"),
            new Channel('live-games'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'game.action.added';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'game_id' => $this->game->id,
            'action' => [
                'id' => $this->action->id,
                'action_type' => $this->action->action_type,
                'player_id' => $this->action->player_id,
                'player_name' => $this->action->player->full_name,
                'team_id' => $this->action->team_id,
                'points' => $this->action->points,
                'period' => $this->action->period,
                'time_remaining' => $this->action->time_remaining,
                'is_successful' => $this->action->is_successful,
                'description' => $this->action->action_description,
                'display_time' => $this->action->display_time,
            ],
            'live_game' => [
                'current_score_home' => $this->liveGame->current_score_home,
                'current_score_away' => $this->liveGame->current_score_away,
                'fouls_home_total' => $this->liveGame->fouls_home_total,
                'fouls_away_total' => $this->liveGame->fouls_away_total,
                'shot_clock_remaining' => $this->liveGame->shot_clock_remaining,
                'last_update_at' => $this->liveGame->last_update_at,
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