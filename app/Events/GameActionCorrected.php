<?php

namespace App\Events;

use App\Models\Game;
use App\Models\GameAction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameActionCorrected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Game $game,
        public GameAction $action
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("game.{$this->game->id}"),
            new Channel("team.{$this->action->team_id}"),
            new Channel('live-games'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'game.action.corrected';
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
                'is_corrected' => $this->action->is_corrected,
                'correction_reason' => $this->action->correction_reason,
                'corrected_by' => $this->action->correctedBy?->name,
            ],
            'message' => 'Spielaktion wurde korrigiert',
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Determine if this event should broadcast.
     */
    public function broadcastWhen(): bool
    {
        return $this->game->liveGame?->is_being_broadcasted ?? false;
    }
}