<?php

namespace App\Mail\ClubSubscription;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HighChurnAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 2;
    public $backoff = 120;

    public function __construct(
        public Tenant $tenant,
        public array $churnData
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('notifications.subjects.high_churn_alert', [
                'tenant_name' => $this->tenant->name,
                'churn_rate' => number_format($this->churnData['churn_rate'], 1)
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.club-subscription.high-churn-alert',
            with: [
                'tenant' => $this->tenant,
                'period' => $this->churnData['period'],
                'churnRate' => $this->churnData['churn_rate'],
                'customersStart' => $this->churnData['customers_start'],
                'customersEnd' => $this->churnData['customers_end'],
                'churnedCustomers' => $this->churnData['churned_customers'],
                'voluntaryChurn' => $this->churnData['voluntary_churn'],
                'involuntaryChurn' => $this->churnData['involuntary_churn'],
                'atRiskClubs' => $this->churnData['at_risk_clubs'] ?? [],
                'churnReasons' => $this->churnData['churn_reasons'] ?? [],
                'revenueImpact' => $this->churnData['revenue_impact'] ?? 0,
                'recommendedActions' => $this->getRecommendedActions(),
                'analyticsUrl' => route('tenant.analytics.dashboard', $this->tenant->id),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    public function tags(): array
    {
        return ['admin', 'churn-alert', 'priority:high', 'tenant:' . $this->tenant->id];
    }

    private function getRecommendedActions(): array
    {
        $actions = [];

        if ($this->churnData['involuntary_churn'] > $this->churnData['voluntary_churn']) {
            $actions[] = __('notifications.churn_alert.recommended_actions.payment_updates');
            $actions[] = __('notifications.churn_alert.recommended_actions.dunning_process');
        }

        if ($this->churnData['churn_rate'] > 10) {
            $actions[] = __('notifications.churn_alert.recommended_actions.churn_survey');
            $actions[] = __('notifications.churn_alert.recommended_actions.winback_campaign');
        }

        $actions[] = __('notifications.churn_alert.recommended_actions.contact_at_risk');
        $actions[] = __('notifications.churn_alert.recommended_actions.improve_features');

        return $actions;
    }
}
