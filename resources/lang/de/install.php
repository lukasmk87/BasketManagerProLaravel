<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Installation Wizard Translations (German)
    |--------------------------------------------------------------------------
    */

    // General
    'app_name' => 'BasketManager Pro',
    'installation_wizard' => 'Installations-Assistent',
    'step' => 'Schritt :current von :total',
    'continue' => 'Weiter',
    'back' => 'Zurück',
    'finish' => 'Installation abschließen',
    'retry' => 'Erneut versuchen',
    'test_connection' => 'Verbindung testen',
    'optional' => 'Optional',
    'required' => 'Erforderlich',
    'success' => 'Erfolgreich',
    'error' => 'Fehler',
    'warning' => 'Warnung',

    // Step 0: Language Selection
    'select_language' => 'Sprache wählen',
    'select_language_subtitle' => 'Wählen Sie Ihre bevorzugte Sprache für die Installation',
    'german' => 'Deutsch',
    'english' => 'Englisch',

    // Step 1: Welcome
    'welcome_title' => 'Willkommen zu BasketManager Pro',
    'welcome_subtitle' => 'Professionelles Basketball Club Management System',
    'welcome_description' => 'Dieser Installations-Assistent führt Sie durch die Einrichtung von BasketManager Pro. Der Prozess dauert etwa 5-10 Minuten.',
    'welcome_features' => 'Features:',
    'feature_live_scoring' => 'Live-Spielverfolgung und Statistiken',
    'feature_team_management' => 'Team- und Spielerverwaltung',
    'feature_training' => 'Trainingsverwaltung und Drill-Bibliothek',
    'feature_tournaments' => 'Turnierverwaltung mit Brackets',
    'feature_subscriptions' => 'Multi-Tenant Subscription-System',
    'feature_gdpr' => 'GDPR-konforme Datenverwaltung',
    'start_installation' => 'Installation starten',

    // Step 2: Server Requirements
    'requirements_title' => 'Server-Anforderungen prüfen',
    'requirements_subtitle' => 'Überprüfung der Systemanforderungen',
    'requirements_description' => 'Ihr Server muss die folgenden Anforderungen erfüllen, um BasketManager Pro auszuführen.',
    'requirement' => 'Anforderung',
    'current_value' => 'Aktueller Wert',
    'status' => 'Status',
    'all_requirements_met' => 'Alle Anforderungen erfüllt!',
    'requirements_not_met' => 'Einige Anforderungen sind nicht erfüllt. Bitte beheben Sie diese Probleme, bevor Sie fortfahren.',
    'php_version' => 'PHP Version',
    'php_extensions' => 'PHP Erweiterungen',
    'php_configuration' => 'PHP Konfiguration',
    'php_functions' => 'PHP Funktionen',

    // Step 3: Permissions
    'permissions_title' => 'Ordner-Berechtigungen prüfen',
    'permissions_subtitle' => 'Überprüfung der Schreibrechte',
    'permissions_description' => 'Die folgenden Verzeichnisse müssen beschreibbar sein.',
    'folder' => 'Ordner',
    'path' => 'Pfad',
    'permission' => 'Berechtigung',
    'writable' => 'Beschreibbar',
    'not_writable' => 'Nicht beschreibbar',
    'all_permissions_ok' => 'Alle Berechtigungen korrekt!',
    'permissions_issues' => 'Einige Ordner sind nicht beschreibbar. Führen Sie die folgenden Befehle aus:',
    'fix_permissions_title' => 'Berechtigungen korrigieren',

    // Step 4: Environment Configuration
    'environment_title' => 'Umgebungskonfiguration',
    'environment_subtitle' => 'Konfiguration der Anwendungseinstellungen',
    'environment_description' => 'Konfigurieren Sie Ihre Anwendung, Datenbank und externe Services.',
    'tab_application' => 'Anwendung',
    'tab_database' => 'Datenbank',
    'tab_mail' => 'E-Mail',
    'tab_stripe' => 'Stripe',

    // Application Settings
    'app_name_label' => 'Anwendungsname',
    'app_name_placeholder' => 'BasketManager Pro',
    'app_url_label' => 'Anwendungs-URL',
    'app_url_placeholder' => 'https://your-domain.com',
    'app_env_label' => 'Umgebung',
    'app_env_local' => 'Lokal (Entwicklung)',
    'app_env_staging' => 'Staging (Test)',
    'app_env_production' => 'Produktion (Live)',
    'app_debug_label' => 'Debug-Modus',
    'app_debug_help' => 'Debug-Modus nur in Entwicklungsumgebungen aktivieren',

    // Database Settings
    'db_connection_label' => 'Datenbank-Typ',
    'db_host_label' => 'Host',
    'db_port_label' => 'Port',
    'db_database_label' => 'Datenbankname',
    'db_username_label' => 'Benutzername',
    'db_password_label' => 'Passwort',
    'test_database_connection' => 'Datenbankverbindung testen',
    'database_connection_success' => 'Datenbankverbindung erfolgreich!',
    'database_connection_failed' => 'Datenbankverbindung fehlgeschlagen',

    // Mail Settings
    'mail_mailer_label' => 'Mail-Treiber',
    'mail_host_label' => 'SMTP Host',
    'mail_port_label' => 'SMTP Port',
    'mail_username_label' => 'SMTP Benutzername',
    'mail_password_label' => 'SMTP Passwort',
    'mail_encryption_label' => 'Verschlüsselung',
    'mail_from_address_label' => 'Absender-E-Mail',
    'mail_from_name_label' => 'Absender-Name',
    'mail_configuration_optional' => 'E-Mail-Konfiguration ist optional und kann später eingerichtet werden.',

    // Stripe Settings
    'stripe_key_label' => 'Stripe Publishable Key',
    'stripe_key_placeholder' => 'pk_test_...',
    'stripe_secret_label' => 'Stripe Secret Key',
    'stripe_secret_placeholder' => 'sk_test_...',
    'stripe_webhook_label' => 'Stripe Webhook Secret',
    'stripe_webhook_placeholder' => 'whsec_...',
    'test_stripe_connection' => 'Stripe-Verbindung testen',
    'stripe_connection_success' => 'Stripe-Verbindung erfolgreich!',
    'stripe_connection_failed' => 'Stripe-Verbindung fehlgeschlagen',
    'stripe_configuration_optional' => 'Stripe-Konfiguration ist optional und kann später eingerichtet werden.',

    'save_configuration' => 'Konfiguration speichern',
    'configuration_saved' => 'Konfiguration erfolgreich gespeichert',
    'environment_saved' => 'Umgebungsvariablen erfolgreich gespeichert',
    'environment_save_failed' => 'Fehler beim Speichern der Umgebungsvariablen',
    'configure_environment_first' => 'Bitte konfigurieren Sie zuerst die Umgebung',

    // Step 5: Database Setup
    'database_title' => 'Datenbank einrichten',
    'database_subtitle' => 'Migrationen und Seeders ausführen',
    'database_description' => 'Dieser Schritt erstellt alle notwendigen Datenbanktabellen und Grunddaten.',
    'run_migrations' => 'Migrationen ausführen',
    'migrations_running' => 'Migrationen werden ausgeführt...',
    'migrations_completed' => 'Migrationen erfolgreich abgeschlossen',
    'migration_failed' => 'Migration fehlgeschlagen',
    'migration_output' => 'Migrations-Ausgabe',
    'complete_migrations_first' => 'Bitte schließen Sie zuerst die Datenbankmigrationen ab',

    // Step 6: Super Admin Creation
    'admin_title' => 'Super Admin erstellen',
    'admin_subtitle' => 'Erstellen Sie Ihren ersten Administrator-Account',
    'admin_description' => 'Dieser Account wird die vollständige Kontrolle über Ihre BasketManager Pro Installation haben.',
    'tenant_name_label' => 'Organisationsname',
    'tenant_name_placeholder' => 'Mein Basketball Club',
    'admin_name_label' => 'Admin-Name',
    'admin_name_placeholder' => 'Max Mustermann',
    'admin_email_label' => 'Admin-E-Mail',
    'admin_email_placeholder' => 'admin@example.com',
    'admin_password_label' => 'Passwort',
    'admin_password_confirm_label' => 'Passwort bestätigen',
    'password_strength' => 'Passwortstärke',
    'password_weak' => 'Schwach',
    'password_medium' => 'Mittel',
    'password_strong' => 'Stark',
    'subscription_tier_label' => 'Subscription-Tier',
    'subscription_tier_description' => 'Wählen Sie Ihren Subscription-Plan (kann später geändert werden)',

    // Subscription Tiers
    'subscription_free' => 'Kostenlos',
    'subscription_basic' => 'Basic',
    'subscription_professional' => 'Professional',
    'subscription_enterprise' => 'Enterprise',
    'subscription_custom' => 'Individuell',
    'unlimited' => 'Unbegrenzt',

    'create_admin' => 'Administrator erstellen',
    'admin_creation_success' => 'Administrator erfolgreich erstellt',
    'admin_creation_failed' => 'Fehler beim Erstellen des Administrators',

    // Step 7: Complete
    'complete_title' => 'Installation abgeschlossen!',
    'complete_subtitle' => 'BasketManager Pro ist einsatzbereit',
    'complete_description' => 'Ihre Installation war erfolgreich. Sie können sich jetzt mit Ihren Administrator-Zugangsdaten anmelden.',
    'your_credentials' => 'Ihre Zugangsdaten',
    'email' => 'E-Mail',
    'important_note' => 'Wichtiger Hinweis',
    'change_password_note' => 'Bitte ändern Sie nach dem ersten Login Ihr Passwort.',
    'save_credentials_note' => 'Speichern Sie Ihre Zugangsdaten an einem sicheren Ort.',
    'go_to_login' => 'Zum Login',
    'explore_dashboard' => 'Dashboard erkunden',

    // Errors
    'stripe_valid' => 'Stripe-Schlüssel sind gültig',
    'installation_locked' => 'Die Installation ist bereits abgeschlossen. Verwenden Sie `php artisan install:unlock --force` um neu zu installieren.',

    // Help Text
    'help_php_version' => 'BasketManager Pro erfordert mindestens PHP 8.2',
    'help_database' => 'MySQL 8.0+ oder PostgreSQL 14+ wird empfohlen',
    'help_stripe' => 'Stripe-Schlüssel finden Sie in Ihrem Stripe-Dashboard unter Entwickler > API-Schlüssel',
];
