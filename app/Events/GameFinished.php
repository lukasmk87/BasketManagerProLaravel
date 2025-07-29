<?php

namespace App\Events;

use App\Models\Game;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameFinished implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Game $game
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
        return 'game.finished';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'game_id' => $this->game->id,
            'status' => $this->game->status,
            'final_scores' => [
                'home' => $this->game->home_team_score,
                'away' => $this->game->away_team_score,
                'margin' => abs($this->game->home_team_score - $this->game->away_team_score),
            ],
            'teams' => [
                'home' => [
                    'id' => $this->game->home_team_id,
                    'name' => $this->game->homeTeam->name,
                    'score' => $this->game->home_team_score,
                    'is_winner' => $this->game->home_team_score > $this->game->away_team_score,
                ],
                'away' => [
                    'id' => $this->game->away_team_id,
                    'name' => $this->game->awayTeam->name,
                    'score' => $this->game->away_team_score,
                    'is_winner' => $this->game->away_team_score > $this->game->home_team_score,
                ],
            ],
            'game_info' => [
                'duration_minutes' => $this->game->duration,
                'went_to_overtime' => $this->game->went_to_overtime,
                'period_scores' => $this->game->period_scores,
                'venue' => $this->game->venue,
                'attendance' => $this->game->attendance,
                'scheduled_at' => $this->game->scheduled_at->toISOString(),
                'actual_start_time' => $this->game->actual_start_time?->toISOString(),
                'actual_end_time' => $this->game->actual_end_time?->toISOString(),
            ],
            'result' => $this->game->result,
            'winner' => $this->game->winner?->name,
            'point_differential' => $this->game->point_differential,
            'timestamp' => now()->toISOString(),
        ];
    }
}