<?php

namespace App\Mail\ClubSubscription;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionAnalyticsReportMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 2;
    public $backoff = 120;

    public function __construct(
        public Tenant $tenant,
        public array $reportData,
        public string $reportPeriod = 'monthly'
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(
                '📊 Subscription Analytics Report - %s (%s)',
                $this->tenant->name,
                $this->reportData['date'] ?? now()->format('Y-m')
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.club-subscription.analytics-report',
            with: [
                'tenant' => $this->tenant,
                'reportDate' => $this->reportData['date'],
                'reportPeriod' => $this->reportPeriod,

                // MRR Metrics
                'totalMRR' => $this->reportData['mrr']['total'] ?? 0,
                'mrrGrowthRate' => $this->reportData['mrr']['growth_rate_3m'] ?? 0,
                'mrrByPlan' => $this->reportData['mrr']['by_plan'] ?? [],

                // Churn Metrics
                'churnRate' => $this->reportData['churn']['monthly_rate'] ?? 0,
                'revenueChurn' => $this->reportData['churn']['revenue_churn'] ?? 0,
                'churnReasons' => $this->reportData['churn']['reasons'] ?? [],

                // LTV Metrics
                'averageLTV' => $this->reportData['ltv']['average'] ?? 0,
                'ltvByPlan' => $this->reportData['ltv']['by_plan'] ?? [],

                // Health Metrics
                'activeSubscriptions' => $this->reportData['health']['active_subscriptions'] ?? 0,
                'trialConversionRate' => $this->reportData['health']['trial_conversion'] ?? 0,
                'avgSubscriptionDuration' => $this->reportData['health']['avg_duration_days'] ?? 0,
                'upgradeDowngradeRates' => $this->reportData['health']['upgrade_downgrade'] ?? [],

                // Summary
                'keyInsights' => $this->generateKeyInsights(),
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
        return ['admin', 'analytics-report', 'tenant:' . $this->tenant->id, 'period:' . $this->reportPeriod];
    }

    private function generateKeyInsights(): array
    {
        $insights = [];

        $mrrGrowth = $this->reportData['mrr']['growth_rate_3m'] ?? 0;
        if ($mrrGrowth > 10) {
            $insights[] = ['type' => 'positive', 'text' => sprintf('Starkes MRR-Wachstum von %.1f%% in den letzten 3 Monaten', $mrrGrowth)];
        } elseif ($mrrGrowth < 0) {
            $insights[] = ['type' => 'negative', 'text' => sprintf('MRR-Rückgang von %.1f%% - Handlungsbedarf!', abs($mrrGrowth))];
        }

        $churnRate = $this->reportData['churn']['monthly_rate'] ?? 0;
        if ($churnRate > 5) {
            $insights[] = ['type' => 'warning', 'text' => sprintf('Churn-Rate bei %.1f%% - über dem Zielwert von 5%%', $churnRate)];
        } else {
            $insights[] = ['type' => 'positive', 'text' => sprintf('Gesunde Churn-Rate von %.1f%%', $churnRate)];
        }

        $trialConversion = $this->reportData['health']['trial_conversion'] ?? 0;
        if ($trialConversion < 20) {
            $insights[] = ['type' => 'warning', 'text' => sprintf('Niedrige Trial-Conversion von %.1f%% - Onboarding optimieren', $trialConversion)];
        }

        return $insights;
    }
}
