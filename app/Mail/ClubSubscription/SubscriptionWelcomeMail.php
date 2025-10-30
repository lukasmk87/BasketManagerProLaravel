<?php

namespace App\Mail\ClubSubscription;

use App\Models\Club;
use App\Models\ClubSubscriptionPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionWelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Club $club,
        public ClubSubscriptionPlan $plan,
        public bool $isTrialActive = false,
        public ?int $trialDaysRemaining = null
    ) {
        $this->afterCommit();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(
                '🎉 Willkommen bei %s - %s',
                config('app.name'),
                $this->club->name
            ),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.club-subscription.subscription-welcome',
            with: [
                'club' => $this->club,
                'plan' => $this->plan,
                'planName' => $this->plan->name,
                'planPrice' => $this->plan->price,
                'planCurrency' => $this->plan->currency,
                'planFeatures' => $this->plan->features ?? [],
                'planLimits' => [
                    'max_teams' => $this->plan->max_teams,
                    'max_players' => $this->plan->max_players,
                    'max_games' => $this->plan->max_games,
                    'max_training_sessions' => $this->plan->max_training_sessions,
                ],
                'isTrialActive' => $this->isTrialActive,
                'trialDaysRemaining' => $this->trialDaysRemaining,
                'trialEndsAt' => $this->isTrialActive ? $this->club->subscription_trial_ends_at : null,
                'nextBillingDate' => $this->club->subscription_current_period_end,
                'billingInterval' => $this->plan->billing_interval ?? 'monthly',
                'dashboardUrl' => route('club.dashboard', $this->club->id),
                'billingPortalUrl' => route('club.billing-portal', $this->club->id),
                'supportUrl' => route('support.contact'),
                'gettingStartedSteps' => $this->getGettingStartedSteps(),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'club-subscription',
            'welcome',
            'club:' . $this->club->id,
            'tenant:' . $this->club->tenant_id,
            'plan:' . $this->plan->id,
        ];
    }

    /**
     * Get getting started steps.
     */
    private function getGettingStartedSteps(): array
    {
        return [
            [
                'title' => 'Teams erstellen',
                'description' => 'Legen Sie Ihre Basketball-Teams an',
                'url' => route('teams.create', ['club' => $this->club->id]),
                'icon' => '👥',
            ],
            [
                'title' => 'Spieler hinzufügen',
                'description' => 'Fügen Sie Ihre Spieler zu den Teams hinzu',
                'url' => route('players.index', ['club' => $this->club->id]),
                'icon' => '🏀',
            ],
            [
                'title' => 'Spiele planen',
                'description' => 'Erstellen Sie Ihren Spielplan',
                'url' => route('games.create', ['club' => $this->club->id]),
                'icon' => '📅',
            ],
            [
                'title' => 'Trainings organisieren',
                'description' => 'Planen Sie Ihre Trainingseinheiten',
                'url' => route('trainings.index', ['club' => $this->club->id]),
                'icon' => '💪',
            ],
        ];
    }
}
