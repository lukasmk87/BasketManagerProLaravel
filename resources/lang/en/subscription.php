<?php

return [
    'title' => 'Club Subscription',
    'manage' => 'Manage Subscription',
    'cancel' => 'Cancel Subscription',

    'plans' => [
        'available' => 'Available Plans',
        'current' => 'Current Plan',
        'recommended' => 'Recommended',
        'select' => 'Select Plan',
        'subscribe' => 'Subscribe Now',
        'switch' => 'Switch Plan',
        'upgrade' => '↑ Upgrade to :plan',
        'downgrade' => '↓ Switch to :plan',
        'switch_to_free' => 'Switch to Free Plan',
        'free' => 'Free',
        'features' => 'Features',
        'limits' => 'Limits',
        'description' => 'Choose the plan that best fits your requirements',
    ],

    'status' => [
        'active' => 'Active',
        'trial' => 'Trial',
        'past_due' => 'Payment Due',
        'canceled' => 'Canceled',
        'incomplete' => 'Incomplete',
        'no_subscription' => 'No Active Subscription',
    ],

    'trial' => [
        'running' => 'Trial Period Running',
        'ends' => 'Your trial ends on :date',
        'days_remaining' => ':days :unit remaining',
        'test_free' => 'Test free for :days days',
    ],

    'billing' => [
        'interval' => 'Billing Interval',
        'monthly' => 'Monthly',
        'yearly' => 'Yearly',
        'per_month' => '/ month',
        'per_year' => '/ year',
        'next_billing' => 'Next Billing',
        'start_date' => 'Start Date',
        'ends_at' => 'Ends At',
        'toggle_interval' => 'Toggle billing interval',
        'price' => 'Price',
    ],

    'usage' => [
        'title' => 'Usage Statistics',
        'description' => 'Your current usage compared to plan limits',
        'limit_reached' => '⚠️ Limit Almost Reached',
    ],

    'portal' => [
        'open' => 'Open Billing Portal',
    ],

    'swap' => [
        'title' => 'Plan Change',
        'preview' => 'Preview of Changes and Costs',
        'comparison' => 'Plan Comparison',
        'current' => 'Current',
        'new' => 'New',
        'upgrade' => '↑ Upgrade',
        'downgrade' => '↓ Downgrade',
        'switch' => 'Switch',
        'costs' => 'Cost Overview',
        'credit' => 'Credit (unused time)',
        'debit' => 'New Plan Charge (prorated)',
        'due_today' => 'Due Today',
        'what_happens' => 'What Happens?',
        'details' => 'Show Details',
        'details_count' => 'Show Details (:count items)',
        'confirm' => 'Switch Plan Now',
        'confirming' => 'Switching plan...',
        'calculating' => 'Calculating proration...',
        'next_payment' => 'From :date you will pay :amount / :interval',
        'proration' => 'Proration',
        'description' => 'Description',
        'period' => 'Period',
        'amount' => 'Amount',
        'total' => 'Total',

        'explanations' => [
            'upgrade' => 'You are upgrading to a higher tier plan. You will receive a prorated refund for the remaining time of your current plan and pay the prorated amount for the new plan until the next billing date.',
            'downgrade' => 'You are switching to a lower cost plan. You will receive a credit for the remaining time of your current plan, which will be applied to the first payment of the new plan.',
            'change' => 'You are switching to a different plan. The difference will be automatically calculated and settled.',
        ],

        'important_notes' => [
            'title' => 'Important Notes',
            'immediate' => 'The plan change will take effect <strong>immediately</strong>',
            'refund' => 'You will receive a prorated refund for the unused time',
            'payment' => 'Your default payment method will be charged for the amount due',
            'next_billing' => 'Next regular billing: :date',
            'can_change' => 'You can change or cancel at any time',
            'list' => [
                'immediate' => 'The plan change will take effect <strong>immediately</strong>',
                'refund' => 'You will receive a prorated refund for the unused time',
                'payment' => 'Your default payment method will be charged for the amount due',
                'next_billing' => 'Next regular billing: :date',
                'can_change' => 'You can change or cancel at any time',
            ],
        ],

        'next_billing' => [
            'title' => 'Next Billing',
            'text' => ':date - :amount :interval',
            'monthly' => 'monthly',
            'yearly' => 'yearly',
        ],
    ],

    'cancel_modal' => [
        'title' => 'Cancel Subscription',
        'question' => 'Do you really want to cancel your subscription?',
        'at_period_end' => 'Cancel at Period End',
        'at_period_end_desc' => 'Your access remains active until the end of the current billing period',
        'immediately' => 'Cancel Immediately',
        'immediately_desc' => 'Your access will be terminated immediately',
    ],

    'info' => [
        'important' => 'Important Notes',
        'yearly_discount' => 'Yearly subscriptions offer :percent% discount',
        'prices_include_tax' => 'All prices include VAT',
        'upgrade_anytime' => 'You can upgrade or downgrade at any time',
        'prorated_refund' => 'Prorated refund on plan change',
        'secure_payment' => 'Secure payment via Stripe',
        'change_immediate' => 'The change takes effect immediately',
        'auto_proration' => 'Prorated refund/charge occurs automatically',
        'payment_charged' => 'Your payment method will be charged',
        'next_billing_date' => 'Next regular billing: :date',
    ],

    'messages' => [
        'no_checkout_url' => 'Error: No checkout URL received',
        'checkout_failed' => 'Error starting checkout: :error',
        'plan_swapped' => 'Successfully switched to :plan!',
        'no_portal_url' => 'Error: No portal URL received',
        'portal_failed' => 'Error opening billing portal: :error',
        'use_portal' => 'Please use the billing portal to cancel your subscription.',
        'cancel_failed' => 'Error canceling: :error',
        'no_plans' => 'Select one of the plans below to access all features.',
        'swap_preview_error' => 'Error loading preview',
        'retry' => 'Try Again',
    ],

    'common' => [
        'loading' => 'Loading...',
        'cancel' => 'Cancel',
        'day' => 'day',
        'days' => 'days',
        'days_dative' => 'days',
        'not_available' => 'Not Available',
        'unlimited' => 'Unlimited',
    ],
];
