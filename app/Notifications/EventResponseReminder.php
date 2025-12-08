<?php

namespace App\Notifications;

use App\Models\Game;
use App\Models\TrainingSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventResponseReminder extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $eventType;

    protected int $eventId;

    protected string $eventTitle;

    protected string $eventDate;

    protected string $teamName;

    public function __construct(string $eventType, int $eventId, string $eventTitle, string $eventDate, string $teamName)
    {
        $this->eventType = $eventType;
        $this->eventId = $eventId;
        $this->eventTitle = $eventTitle;
        $this->eventDate = $eventDate;
        $this->teamName = $teamName;
    }

    /**
     * Create notification from a Game model.
     */
    public static function fromGame(Game $game): self
    {
        $title = ($game->home_team_name ?? 'Heim').' vs '.($game->away_team_name ?? 'Auswärts');

        return new self(
            'game',
            $game->id,
            $title,
            $game->scheduled_at->format('d.m.Y H:i'),
            $game->homeTeam?->name ?? 'Team'
        );
    }

    /**
     * Create notification from a TrainingSession model.
     */
    public static function fromTraining(TrainingSession $training): self
    {
        return new self(
            'training',
            $training->id,
            $training->title ?: 'Training',
            $training->scheduled_at->format('d.m.Y H:i'),
            $training->team?->name ?? 'Team'
        );
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $eventTypeLabel = $this->eventType === 'game' ? 'Spiel' : 'Training';
        $url = $this->getActionUrl();

        return (new MailMessage)
            ->subject("Erinnerung: Bitte um Rückmeldung für {$eventTypeLabel}")
            ->greeting("Hallo {$notifiable->name}!")
            ->line('Du hast noch nicht auf den folgenden Termin geantwortet:')
            ->line("**{$eventTypeLabel}:** {$this->eventTitle}")
            ->line("**Datum:** {$this->eventDate}")
            ->line("**Team:** {$this->teamName}")
            ->action('Jetzt antworten', $url)
            ->line('Bitte gib deinem Trainer Bescheid, ob du dabei sein kannst.')
            ->salutation('Sportliche Grüße');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'event_response_reminder',
            'event_type' => $this->eventType,
            'event_id' => $this->eventId,
            'event_title' => $this->eventTitle,
            'event_date' => $this->eventDate,
            'team_name' => $this->teamName,
            'message' => $this->getMessage(),
        ];
    }

    /**
     * Get the notification message.
     */
    protected function getMessage(): string
    {
        $eventTypeLabel = $this->eventType === 'game' ? 'Spiel' : 'Training';

        return "Bitte antworte auf: {$eventTypeLabel} am {$this->eventDate}";
    }

    /**
     * Get the action URL for the notification.
     */
    protected function getActionUrl(): string
    {
        return url('/de/dashboard');
    }
}
