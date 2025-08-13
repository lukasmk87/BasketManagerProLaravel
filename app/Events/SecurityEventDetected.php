<?php

namespace App\Events;

use App\Models\SecurityEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SecurityEventDetected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public SecurityEvent $securityEvent;

    /**
     * Create a new event instance.
     */
    public function __construct(SecurityEvent $securityEvent)
    {
        $this->securityEvent = $securityEvent;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('security-events'),
            new PrivateChannel('security-events.' . $this->securityEvent->severity),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'security.event.detected';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'event_id' => $this->securityEvent->event_id,
            'event_type' => $this->securityEvent->event_type,
            'severity' => $this->securityEvent->severity,
            'status' => $this->securityEvent->status,
            'description' => $this->securityEvent->description,
            'occurred_at' => $this->securityEvent->occurred_at->toISOString(),
            'source_ip' => $this->securityEvent->source_ip,
            'user_id' => $this->securityEvent->user_id,
            'requires_notification' => $this->securityEvent->requires_notification,
            'requires_investigation' => $this->securityEvent->requires_investigation,
            'severity_icon' => $this->securityEvent->getSeverityIcon(),
            'severity_color' => $this->securityEvent->getSeverityColor(),
            'is_critical' => $this->securityEvent->isCritical(),
            'is_emergency_related' => $this->securityEvent->isEmergencyRelated(),
            'is_gdpr_related' => $this->securityEvent->isGdprRelated(),
        ];
    }

    /**
     * Determine if this event should be queued.
     */
    public function shouldQueue(): bool
    {
        // Don't queue critical events - broadcast immediately
        return !$this->securityEvent->isCritical();
    }
}
