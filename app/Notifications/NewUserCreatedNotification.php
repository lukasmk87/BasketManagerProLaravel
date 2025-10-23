<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class NewUserCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $password;
    protected string $createdBy;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $password, string $createdBy)
    {
        $this->password = $password;
        $this->createdBy = $createdBy;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $loginUrl = route('login');

        return (new MailMessage)
            ->subject('Willkommen bei ' . config('app.name'))
            ->greeting('Hallo ' . $notifiable->name . '!')
            ->line('Ihr Benutzerkonto wurde erfolgreich erstellt von ' . $this->createdBy . '.')
            ->line('')
            ->line('**Ihre Zugangsdaten:**')
            ->line('E-Mail: **' . $notifiable->email . '**')
            ->line('Passwort: **' . $this->password . '**')
            ->line('')
            ->line('**Wichtig:** Bitte ändern Sie Ihr Passwort nach dem ersten Login.')
            ->action('Jetzt einloggen', $loginUrl)
            ->line('')
            ->line('Die folgenden Rollen wurden Ihnen zugewiesen:')
            ->line('• ' . implode(', ', $notifiable->roles->pluck('name')->map(function($role) {
                return ucfirst(str_replace('_', ' ', $role));
            })->toArray()))
            ->line('')
            ->salutation('Viel Erfolg mit ' . config('app.name') . '!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'created_by' => $this->createdBy,
        ];
    }
}
