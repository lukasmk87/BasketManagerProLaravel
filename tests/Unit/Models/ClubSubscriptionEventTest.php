<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\ClubSubscriptionEvent;
use App\Models\Club;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ClubSubscriptionEventTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Club $club;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->club = Club::factory()->for($this->tenant)->create();
    }

    /** @test */
    /** @test */
    public function lifecycle_events_scope()
    {
        ClubSubscriptionEvent::factory()
            ->for($this->tenant)
            ->for($this->club)
            ->subscriptionCreated()
            ->create();

        ClubSubscriptionEvent::factory()
            ->for($this->tenant)
            ->for($this->club)
            ->subscriptionCanceled()
            ->create();

        ClubSubscriptionEvent::factory()
            ->for($this->tenant)
            ->for($this->club)
            ->planUpgraded()
            ->create();

        $lifecycleEvents = ClubSubscriptionEvent::lifecycleEvents()->get();

        $this->assertCount(2, $lifecycleEvents);
        $this->assertTrue($lifecycleEvents->pluck('event_type')->contains(ClubSubscriptionEvent::TYPE_SUBSCRIPTION_CREATED));
        $this->assertTrue($lifecycleEvents->pluck('event_type')->contains(ClubSubscriptionEvent::TYPE_SUBSCRIPTION_CANCELED));
        $this->assertFalse($lifecycleEvents->pluck('event_type')->contains(ClubSubscriptionEvent::TYPE_PLAN_UPGRADED));
    }

    /** @test */
    /** @test */
    public function plan_changes_scope()
    {
        ClubSubscriptionEvent::factory()
            ->for($this->tenant)
            ->for($this->club)
            ->planUpgraded()
            ->create();

        ClubSubscriptionEvent::factory()
            ->for($this->tenant)
            ->for($this->club)
            ->planDowngraded()
            ->create();

        ClubSubscriptionEvent::factory()
            ->for($this->tenant)
            ->for($this->club)
            ->subscriptionCreated()
            ->create();

        $planChanges = ClubSubscriptionEvent::planChanges()->get();

        $this->assertCount(2, $planChanges);
        $this->assertTrue($planChanges->pluck('event_type')->contains(ClubSubscriptionEvent::TYPE_PLAN_UPGRADED));
        $this->assertTrue($planChanges->pluck('event_type')->contains(ClubSubscriptionEvent::TYPE_PLAN_DOWNGRADED));
    }

    /** @test */
    /** @test */
    public function trial_events_scope()
    {
        ClubSubscriptionEvent::factory()
            ->for($this->tenant)
            ->for($this->club)
            ->trialStarted()
            ->create();

        ClubSubscriptionEvent::factory()
            ->for($this->tenant)
            ->for($this->club)
            ->trialConverted()
            ->create();

        ClubSubscriptionEvent::factory()
            ->for($this->tenant)
            ->for($this->club)
            ->trialExpired()
            ->create();

        ClubSubscriptionEvent::factory()
            ->for($this->tenant)
            ->for($this->club)
            ->subscriptionCreated()
            ->create();

        $trialEvents = ClubSubscriptionEvent::trialEvents()->get();

        $this->assertCount(3, $trialEvents);
        $this->assertTrue($trialEvents->pluck('event_type')->contains(ClubSubscriptionEvent::TYPE_TRIAL_STARTED));
        $this->assertTrue($trialEvents->pluck('event_type')->contains(ClubSubscriptionEvent::TYPE_TRIAL_CONVERTED));
        $this->assertTrue($trialEvents->pluck('event_type')->contains(ClubSubscriptionEvent::TYPE_TRIAL_EXPIRED));
    }

    /** @test */
    /** @test */
    public function payment_events_scope()
    {
        ClubSubscriptionEvent::factory()
            ->for($this->tenant)
            ->for($this->club)
            ->paymentSucceeded()
            ->create();

        ClubSubscriptionEvent::factory()
            ->for($this->tenant)
            ->for($this->club)
            ->paymentFailed()
            ->create();

        ClubSubscriptionEvent::factory()
            ->for($this->tenant)
            ->for($this->club)
            ->subscriptionCreated()
            ->create();

        $paymentEvents = ClubSubscriptionEvent::paymentEvents()->get();

        $this->assertCount(2, $paymentEvents);
        $this->assertTrue($paymentEvents->pluck('event_type')->contains(ClubSubscriptionEvent::TYPE_PAYMENT_SUCCEEDED));
        $this->assertTrue($paymentEvents->pluck('event_type')->contains(ClubSubscriptionEvent::TYPE_PAYMENT_FAILED));
    }

    /** @test */
    /** @test */
    public function churn_events_scope()
    {
        ClubSubscriptionEvent::factory()
            ->for($this->tenant)
            ->for($this->club)
            ->subscriptionCanceled()
            ->create();

        ClubSubscriptionEvent::factory()
            ->for($this->tenant)
            ->for($this->club)
            ->trialExpired()
            ->create();

        ClubSubscriptionEvent::factory()
            ->for($this->tenant)
            ->for($this->club)
            ->subscriptionCreated()
            ->create();

        $churnEvents = ClubSubscriptionEvent::churnEvents()->get();

        $this->assertCount(2, $churnEvents);
        $this->assertTrue($churnEvents->pluck('event_type')->contains(ClubSubscriptionEvent::TYPE_SUBSCRIPTION_CANCELED));
        $this->assertTrue($churnEvents->pluck('event_type')->contains(ClubSubscriptionEvent::TYPE_TRIAL_EXPIRED));
    }

    /** @test */
    /** @test */
    public function in_month_scope()
    {
        ClubSubscriptionEvent::factory()
            ->for($this->tenant)
            ->for($this->club)
            ->inMonth(2025, 3)
            ->count(3)
            ->create();

        ClubSubscriptionEvent::factory()
            ->for($this->tenant)
            ->for($this->club)
            ->inMonth(2025, 4)
            ->count(2)
            ->create();

        $marchEvents = ClubSubscriptionEvent::inMonth(2025, 3)->get();

        $this->assertCount(3, $marchEvents);
        $this->assertTrue($marchEvents->every(function ($event) {
            return $event->event_date->year === 2025 && $event->event_date->month === 3;
        }));
    }

    /** @test */
    /** @test */
    public function is_churn_method()
    {
        $canceledEvent = ClubSubscriptionEvent::factory()
            ->for($this->tenant)
            ->for($this->club)
            ->subscriptionCanceled()
            ->create();

        $trialExpiredEvent = ClubSubscriptionEvent::factory()
            ->for($this->tenant)
            ->for($this->club)
            ->trialExpired()
            ->create();

        $createdEvent = ClubSubscriptionEvent::factory()
            ->for($this->tenant)
            ->for($this->club)
            ->subscriptionCreated()
            ->create();

        $this->assertTrue($canceledEvent->isChurn());
        $this->assertTrue($trialExpiredEvent->isChurn());
        $this->assertFalse($createdEvent->isChurn());
    }

    /** @test */
    /** @test */
    public function is_voluntary_churn_method()
    {
        $voluntaryEvent = ClubSubscriptionEvent::factory()
            ->for($this->tenant)
            ->for($this->club)
            ->voluntaryCancellation()
            ->create();

        $involuntaryEvent = ClubSubscriptionEvent::factory()
            ->for($this->tenant)
            ->for($this->club)
            ->involuntaryCancellation()
            ->create();

        $this->assertTrue($voluntaryEvent->isVoluntaryChurn());
        $this->assertFalse($involuntaryEvent->isVoluntaryChurn());
    }

    /** @test */
    /** @test */
    public function is_involuntary_churn_method()
    {
        $involuntaryEvent = ClubSubscriptionEvent::factory()
            ->for($this->tenant)
            ->for($this->club)
            ->involuntaryCancellation()
            ->create();

        $voluntaryEvent = ClubSubscriptionEvent::factory()
            ->for($this->tenant)
            ->for($this->club)
            ->voluntaryCancellation()
            ->create();

        $this->assertTrue($involuntaryEvent->isInvoluntaryChurn());
        $this->assertFalse($voluntaryEvent->isInvoluntaryChurn());
    }

    /** @test */
    /** @test */
    public function formatted_mrr_change()
    {
        $positiveEvent = ClubSubscriptionEvent::factory()
            ->for($this->tenant)
            ->for($this->club)
            ->withMRRChange(150.50)
            ->create();

        $negativeEvent = ClubSubscriptionEvent::factory()
            ->for($this->tenant)
            ->for($this->club)
            ->withMRRChange(-75.25)
            ->create();

        $this->assertEquals('+150.50 EUR', $positiveEvent->getFormattedMRRChange());
        $this->assertEquals('-75.25 EUR', $negativeEvent->getFormattedMRRChange());
        $this->assertEquals('+150.50 USD', $positiveEvent->getFormattedMRRChange('USD'));
    }
}
