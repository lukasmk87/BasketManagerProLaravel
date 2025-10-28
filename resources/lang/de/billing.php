<?php

return [
    'title' => 'Abrechnung',

    'invoices' => [
        'title' => 'Rechnungen',
        'history' => 'Rechnungshistorie',
        'all_for_club' => 'Alle Rechnungen für :club',
        'upcoming' => 'Nächste Rechnung',
        'upcoming_preview' => 'Vorschau der kommenden Abrechnung',
        'invoice_number' => 'Rechnung #:number',
        'no_invoices' => 'Keine Rechnungen vorhanden',
        'no_invoices_created' => 'Es wurden noch keine Rechnungen erstellt.',
        'no_invoices_status' => 'Keine Rechnungen mit Status ":status" gefunden.',
        'first_invoice_info' => 'Sobald Ihr Abonnement abgerechnet wird, erscheinen hier Ihre Rechnungen.',
        'check_subscription' => 'Überprüfen Sie Ihren Abonnement-Status für weitere Informationen.',
        'load_more' => 'Weitere Rechnungen laden',
        'days_until' => 'Tage bis zur Abrechnung',
        'positions' => 'Positionen',
        'more_positions' => '+ :count weitere Position(en)',
    ],

    'status' => [
        'label' => 'Status',
        'all' => 'Alle',
        'draft' => 'Entwurf',
        'open' => 'Offen',
        'paid' => 'Bezahlt',
        'uncollectible' => 'Uneinbringlich',
        'void' => 'Storniert',
        'overdue' => '(überfällig)',
    ],

    'labels' => [
        'due_on' => 'Fällig am',
        'subtotal' => 'Zwischensumme',
        'tax' => 'MwSt (:percent%)',
        'discount' => 'Rabatt',
        'total' => 'Gesamt',
        'description' => 'Beschreibung',
        'period' => 'Zeitraum',
        'amount' => 'Betrag',
    ],

    'actions' => [
        'details' => 'Details',
        'download_pdf' => 'PDF',
        'view_details' => 'Details anzeigen',
    ],

    'payment_methods' => [
        'title' => 'Zahlungsmethoden',
        'manage' => 'Verwalten Sie Ihre Zahlungsmethoden für :club',
        'add' => 'Zahlungsmethode hinzufügen',
        'default' => 'Standard-Zahlungsmethode',
        'set_default' => 'Als Standard festlegen',
        'remove' => 'Entfernen',
        'edit' => 'Bearbeiten',
        'active_subscription' => 'Aktives Abonnement',
        'next_payment' => 'Nächste Zahlung am :date',
    ],

    'cards' => [
        'invoices_title' => 'Rechnungen',
        'invoices_desc' => 'Sehen Sie alle Ihre Rechnungen und laden Sie PDF-Versionen herunter',
        'subscription_title' => 'Abonnement',
        'subscription_desc' => 'Verwalten Sie Ihren Abonnement-Plan und sehen Sie Details zu Ihren Features',
        'subscription_desc_alt' => 'Verwalten Sie Ihren Abonnement-Plan und Features',
    ],

    'info' => [
        'important' => 'Wichtige Informationen',
        'auto_email' => 'Rechnungen werden automatisch per E-Mail versandt',
        'pdf_download' => 'PDF-Rechnungen können jederzeit heruntergeladen werden',
        'support_contact' => 'Bei Fragen zu einer Rechnung kontaktieren Sie unseren Support',
        'auto_payment' => 'Zahlungen werden automatisch von Ihrer hinterlegten Zahlungsmethode abgebucht',
        'auto_charge' => 'Diese Rechnung wird automatisch an Ihre hinterlegte Zahlungsmethode gesendet. Nach erfolgreicher Zahlung erhalten Sie eine Bestätigung per E-Mail.',
    ],

    'messages' => [
        'loading_error' => 'Fehler beim Laden der Rechnungen',
        'loading_error_retry' => 'Fehler beim Laden',
        'pdf_error' => 'Fehler beim Herunterladen der PDF',
        'payment_methods_error' => 'Fehler beim Laden der Zahlungsmethoden',
        'added' => 'Zahlungsmethode erfolgreich hinzugefügt!',
        'updated' => 'Rechnungsinformationen erfolgreich aktualisiert!',
        'default_set' => 'Standard-Zahlungsmethode erfolgreich geändert!',
        'default_error' => 'Fehler beim Setzen der Standard-Zahlungsmethode',
        'removed' => 'Zahlungsmethode erfolgreich entfernt!',
        'remove_error' => 'Fehler beim Entfernen der Zahlungsmethode',
    ],

    'breadcrumbs' => [
        'dashboard' => 'Dashboard',
        'subscription' => 'Abonnement',
        'invoices' => 'Rechnungen',
        'payment_methods' => 'Zahlungsmethoden',
    ],

    'navigation' => [
        'view_invoices' => 'Rechnungen ansehen →',
        'manage_subscription' => 'Abonnement verwalten →',
    ],
];
