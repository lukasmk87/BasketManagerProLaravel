<?php

return [
    'title' => 'Club Abonnement',
    'manage' => 'Abonnement verwalten',
    'cancel' => 'Abonnement kündigen',

    'plans' => [
        'available' => 'Verfügbare Pläne',
        'current' => 'Aktueller Plan',
        'recommended' => 'Empfohlen',
        'select' => 'Plan auswählen',
        'subscribe' => 'Jetzt abonnieren',
        'switch' => 'Plan wechseln',
        'upgrade' => '↑ Auf :plan upgraden',
        'downgrade' => '↓ Zu :plan wechseln',
        'switch_to_free' => 'Zu kostenlosem Plan wechseln',
        'free' => 'Kostenlos',
        'features' => 'Features',
        'limits' => 'Limits',
        'description' => 'Wählen Sie den Plan, der am besten zu Ihren Anforderungen passt',
    ],

    'status' => [
        'active' => 'Aktiv',
        'trial' => 'Testphase',
        'past_due' => 'Zahlung fällig',
        'canceled' => 'Gekündigt',
        'incomplete' => 'Unvollständig',
        'no_subscription' => 'Kein aktives Abonnement',
    ],

    'trial' => [
        'running' => 'Testphase läuft',
        'ends' => 'Ihre Testphase endet am :date',
        'days_remaining' => 'noch :days :unit',
        'test_free' => ':days Tage kostenlos testen',
    ],

    'billing' => [
        'interval' => 'Abrechnungsintervall',
        'monthly' => 'Monatlich',
        'yearly' => 'Jährlich',
        'per_month' => '/ Monat',
        'per_year' => '/ Jahr',
        'next_billing' => 'Nächste Abrechnung',
        'start_date' => 'Startdatum',
        'ends_at' => 'Endet am',
        'toggle_interval' => 'Abrechnungsintervall umschalten',
        'price' => 'Preis',
    ],

    'usage' => [
        'title' => 'Nutzungsstatistik',
        'description' => 'Ihre aktuelle Nutzung im Vergleich zu den Plan-Limits',
        'limit_reached' => '⚠️ Limit fast erreicht',
    ],

    'portal' => [
        'open' => 'Billing-Portal öffnen',
    ],

    'swap' => [
        'title' => 'Plan-Wechsel',
        'preview' => 'Vorschau der Änderungen und Kosten',
        'comparison' => 'Plan-Vergleich',
        'current' => 'Aktuell',
        'new' => 'Neu',
        'upgrade' => '↑ Upgrade',
        'downgrade' => '↓ Downgrade',
        'switch' => 'Wechsel',
        'costs' => 'Kostenübersicht',
        'credit' => 'Guthaben (ungenutzter Zeitraum)',
        'debit' => 'Neue Plan-Gebühr (anteilig)',
        'due_today' => 'Heute fällig',
        'what_happens' => 'Was passiert?',
        'details' => 'Details anzeigen',
        'details_count' => 'Details anzeigen (:count Positionen)',
        'confirm' => 'Plan jetzt wechseln',
        'confirming' => 'Plan wird gewechselt...',
        'calculating' => 'Proration wird berechnet...',
        'next_payment' => 'Ab :date zahlen Sie :amount / :interval',
        'proration' => 'Proration',
        'description' => 'Beschreibung',
        'period' => 'Zeitraum',
        'amount' => 'Betrag',
        'total' => 'Gesamt',

        'explanations' => [
            'upgrade' => 'Sie upgraden zu einem höherwertigen Plan. Sie erhalten eine anteilige Rückerstattung für die verbleibende Zeit Ihres aktuellen Plans und zahlen den anteiligen Betrag für den neuen Plan bis zum nächsten Abrechnungsdatum.',
            'downgrade' => 'Sie wechseln zu einem günstigeren Plan. Sie erhalten ein Guthaben für die verbleibende Zeit Ihres aktuellen Plans, das mit der ersten Zahlung des neuen Plans verrechnet wird.',
            'change' => 'Sie wechseln zu einem anderen Plan. Die Differenz wird automatisch berechnet und verrechnet.',
        ],

        'important_notes' => [
            'title' => 'Wichtige Hinweise',
            'immediate' => 'Der Plan-Wechsel wird <strong>sofort wirksam</strong>',
            'refund' => 'Sie erhalten eine anteilige Rückerstattung für den ungenutzten Zeitraum',
            'payment' => 'Ihre Standard-Zahlungsmethode wird für den fälligen Betrag belastet',
            'next_billing' => 'Nächste reguläre Abrechnung: :date',
            'can_change' => 'Sie können jederzeit wieder wechseln oder kündigen',
            'list' => [
                'immediate' => 'Der Plan-Wechsel wird <strong>sofort wirksam</strong>',
                'refund' => 'Sie erhalten eine anteilige Rückerstattung für den ungenutzten Zeitraum',
                'payment' => 'Ihre Standard-Zahlungsmethode wird für den fälligen Betrag belastet',
                'next_billing' => 'Nächste reguläre Abrechnung: :date',
                'can_change' => 'Sie können jederzeit wieder wechseln oder kündigen',
            ],
        ],

        'next_billing' => [
            'title' => 'Nächste Abrechnung',
            'text' => ':date - :amount :interval',
            'monthly' => 'monatlich',
            'yearly' => 'jährlich',
        ],
    ],

    'cancel_modal' => [
        'title' => 'Abonnement kündigen',
        'question' => 'Möchten Sie Ihr Abonnement wirklich kündigen?',
        'at_period_end' => 'Am Perioden-Ende kündigen',
        'at_period_end_desc' => 'Ihr Zugang bleibt bis zum Ende der aktuellen Abrechnungsperiode aktiv',
        'immediately' => 'Sofort kündigen',
        'immediately_desc' => 'Ihr Zugang wird sofort beendet',
    ],

    'info' => [
        'important' => 'Wichtige Hinweise',
        'yearly_discount' => 'Jährliche Abonnements bieten :percent% Rabatt',
        'prices_include_tax' => 'Alle Preise verstehen sich inklusive MwSt.',
        'upgrade_anytime' => 'Sie können jederzeit upgraden oder downgraden',
        'prorated_refund' => 'Anteilige Rückerstattung bei Plan-Wechsel',
        'secure_payment' => 'Sichere Zahlung über Stripe',
        'change_immediate' => 'Die Änderung wird sofort wirksam',
        'auto_proration' => 'Anteilige Rückerstattung/Belastung erfolgt automatisch',
        'payment_charged' => 'Ihre Zahlungsmethode wird belastet',
        'next_billing_date' => 'Nächste reguläre Abrechnung: :date',
    ],

    'messages' => [
        'no_checkout_url' => 'Fehler: Keine Checkout-URL erhalten',
        'checkout_failed' => 'Fehler beim Starten des Checkouts: :error',
        'plan_swapped' => 'Plan erfolgreich gewechselt zu :plan!',
        'no_portal_url' => 'Fehler: Keine Portal-URL erhalten',
        'portal_failed' => 'Fehler beim Öffnen des Billing-Portals: :error',
        'use_portal' => 'Bitte verwenden Sie das Billing-Portal, um Ihr Abonnement zu kündigen.',
        'cancel_failed' => 'Fehler beim Kündigen: :error',
        'no_plans' => 'Wählen Sie einen der untenstehenden Pläne aus, um die vollen Features zu nutzen.',
        'swap_preview_error' => 'Fehler beim Laden der Vorschau',
        'retry' => 'Erneut versuchen',
    ],

    'common' => [
        'loading' => 'Wird geladen...',
        'cancel' => 'Abbrechen',
        'day' => 'Tag',
        'days' => 'Tage',
        'days_dative' => 'Tagen',
        'not_available' => 'Nicht verfügbar',
        'unlimited' => 'Unbegrenzt',
    ],
];
