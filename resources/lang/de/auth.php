<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines - German
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'Diese Kombination aus Zugangsdaten wurde nicht in unserer Datenbank gefunden.',
    'password' => 'Das eingegebene Passwort ist nicht korrekt.',
    'throttle' => 'Zu viele Loginversuche. Versuchen Sie es bitte in :seconds Sekunden nochmal.',

    'login' => [
        'title' => 'Anmelden',
        'welcome_back' => 'Willkommen zurück!',
        'sign_in_account' => 'Melden Sie sich in Ihrem Konto an',
        'email' => 'E-Mail-Adresse',
        'password' => 'Passwort',
        'remember_me' => 'Angemeldet bleiben',
        'forgot_password' => 'Passwort vergessen?',
        'sign_in' => 'Anmelden',
        'no_account' => 'Noch kein Konto?',
        'create_account' => 'Konto erstellen',
        'login_successful' => 'Erfolgreich angemeldet.',
        'invalid_login' => 'Ungültige Anmeldedaten.',
        'account_disabled' => 'Ihr Konto wurde deaktiviert.',
        'email_not_verified' => 'Bitte bestätigen Sie Ihre E-Mail-Adresse.',
    ],

    'register' => [
        'title' => 'Registrieren',
        'create_account' => 'Neues Konto erstellen',
        'join_basketball' => 'Treten Sie unserer Basketball-Community bei',
        'name' => 'Vollständiger Name',
        'email' => 'E-Mail-Adresse',
        'password' => 'Passwort',
        'password_confirmation' => 'Passwort bestätigen',
        'terms_agree' => 'Ich stimme den :terms_of_service und der :privacy_policy zu',
        'terms_of_service' => 'Nutzungsbedingungen',
        'privacy_policy' => 'Datenschutzerklärung',
        'register' => 'Registrieren',
        'already_registered' => 'Bereits registriert?',
        'sign_in' => 'Hier anmelden',
        'registration_successful' => 'Registrierung erfolgreich.',
        'verification_email_sent' => 'Bestätigungs-E-Mail wurde gesendet.',
    ],

    'forgot_password' => [
        'title' => 'Passwort vergessen',
        'forgot_password' => 'Passwort vergessen?',
        'no_problem' => 'Kein Problem. Geben Sie einfach Ihre E-Mail-Adresse an und wir senden Ihnen einen Link zum Zurücksetzen des Passworts.',
        'email' => 'E-Mail-Adresse',
        'send_reset_link' => 'Link zum Zurücksetzen senden',
        'back_to_login' => 'Zurück zur Anmeldung',
        'reset_link_sent' => 'Link zum Zurücksetzen wurde gesendet.',
        'reset_link_failed' => 'Fehler beim Senden des Reset-Links.',
    ],

    'reset_password' => [
        'title' => 'Passwort zurücksetzen',
        'reset_password' => 'Passwort zurücksetzen',
        'email' => 'E-Mail-Adresse',
        'password' => 'Neues Passwort',
        'password_confirmation' => 'Passwort bestätigen',
        'reset_password_button' => 'Passwort zurücksetzen',
        'password_reset_successful' => 'Passwort erfolgreich zurückgesetzt.',
        'invalid_token' => 'Ungültiger oder abgelaufener Reset-Token.',
    ],

    'two_factor' => [
        'title' => 'Zwei-Faktor-Authentifizierung',
        'confirm_access' => 'Zugriff bestätigen',
        'authentication_challenge' => 'Bitte bestätigen Sie den Zugriff auf Ihr Konto, indem Sie den Authentifizierungscode eingeben, der von Ihrer Authentifizierungs-App bereitgestellt wird.',
        'recovery_challenge' => 'Bitte bestätigen Sie den Zugriff auf Ihr Konto, indem Sie einen Ihrer Notfall-Wiederherstellungscodes eingeben.',
        'code' => 'Code',
        'recovery_code' => 'Wiederherstellungscode',
        'use_authentication_code' => 'Authentifizierungscode verwenden',
        'use_recovery_code' => 'Wiederherstellungscode verwenden',
        'sign_in' => 'Anmelden',
        'invalid_code' => 'Der eingegebene Code ist ungültig.',
        'invalid_recovery_code' => 'Der eingegebene Wiederherstellungscode ist ungültig.',
    ],

    'email_verification' => [
        'title' => 'E-Mail-Bestätigung',
        'verify_email' => 'E-Mail-Adresse bestätigen',
        'verification_required' => 'Danke für die Registrierung! Bevor Sie beginnen können, müssen Sie Ihre E-Mail-Adresse bestätigen, indem Sie auf den Link klicken, den wir Ihnen gerade per E-Mail gesendet haben. Falls Sie die E-Mail nicht erhalten haben, senden wir Ihnen gerne eine neue.',
        'verification_sent' => 'Ein neuer Bestätigungslink wurde an die E-Mail-Adresse gesendet, die Sie bei der Registrierung angegeben haben.',
        'resend_verification' => 'Bestätigungs-E-Mail erneut senden',
        'logout' => 'Abmelden',
        'email_verified' => 'E-Mail-Adresse erfolgreich bestätigt.',
    ],

    'confirm_password' => [
        'title' => 'Passwort bestätigen',
        'confirm_password' => 'Passwort bestätigen',
        'secure_area' => 'Dies ist ein sicherer Bereich der Anwendung. Bitte bestätigen Sie Ihr Passwort, bevor Sie fortfahren.',
        'password' => 'Passwort',
        'confirm' => 'Bestätigen',
        'password_confirmed' => 'Passwort bestätigt.',
        'incorrect_password' => 'Das eingegebene Passwort ist nicht korrekt.',
    ],

    'social_login' => [
        'title' => 'Social Login',
        'continue_with' => 'Fortfahren mit',
        'google' => 'Google',
        'facebook' => 'Facebook',
        'github' => 'GitHub',
        'or_continue_with_email' => 'Oder mit E-Mail-Adresse fortfahren',
        'login_successful' => 'Erfolgreich über :provider angemeldet.',
        'login_failed' => 'Social Login fehlgeschlagen. Bitte versuchen Sie es erneut.',
        'email_already_exists' => 'Ein Konto mit dieser E-Mail-Adresse existiert bereits.',
        'provider_not_supported' => 'Dieser Anbieter wird nicht unterstützt.',
        'account_linked' => 'Konto erfolgreich verknüpft.',
        'account_unlinked' => 'Konto-Verknüpfung erfolgreich entfernt.',
    ],

    'logout' => [
        'title' => 'Abmelden',
        'logout' => 'Abmelden',
        'confirm_logout' => 'Möchten Sie sich wirklich abmelden?',
        'logout_successful' => 'Erfolgreich abgemeldet.',
        'logout_other_devices' => 'Von anderen Geräten abmelden',
        'logout_other_devices_description' => 'Melden Sie sich von allen anderen Browser-Sitzungen auf allen Ihren Geräten ab.',
    ],

    'account' => [
        'deactivated' => 'Ihr Konto wurde deaktiviert. Kontaktieren Sie den Administrator.',
        'suspended' => 'Ihr Konto wurde gesperrt.',
        'email_change_verification' => 'E-Mail-Änderung bestätigen',
        'email_changed' => 'E-Mail-Adresse erfolgreich geändert.',
        'password_changed' => 'Passwort erfolgreich geändert.',
        'profile_updated' => 'Profil erfolgreich aktualisiert.',
        'settings_updated' => 'Einstellungen erfolgreich aktualisiert.',
    ],

    'emergency_access' => [
        'title' => 'Notfallzugriff',
        'emergency_login' => 'Notfall-Anmeldung',
        'access_description' => 'Sie greifen über einen Notfall-QR-Code auf diese Informationen zu.',
        'limited_access' => 'Ihr Zugriff ist auf Notfallinformationen beschränkt.',
        'access_logged' => 'Dieser Zugriff wird zu Sicherheitszwecken protokolliert.',
        'invalid_access_key' => 'Ungültiger oder abgelaufener Notfall-Zugriffsschlüssel.',
        'access_expired' => 'Der Notfallzugriff ist abgelaufen.',
        'access_revoked' => 'Der Notfallzugriff wurde widerrufen.',
        'team_access_only' => 'Zugriff nur auf Team-relevante Informationen.',
    ],

    'roles' => [
        'admin' => 'Administrator',
        'club_admin' => 'Vereinsadministrator',
        'trainer' => 'Trainer',
        'scorer' => 'Statistiker',
        'player' => 'Spieler',
        'parent' => 'Elternteil',
        'guest' => 'Gast',
    ],

    'permissions' => [
        'access_denied' => 'Zugriff verweigert.',
        'insufficient_permissions' => 'Unzureichende Berechtigungen.',
        'role_required' => 'Diese Aktion erfordert die Rolle: :role',
        'permission_required' => 'Diese Aktion erfordert die Berechtigung: :permission',
        'team_access_required' => 'Zugriff auf dieses Team erforderlich.',
        'club_access_required' => 'Zugriff auf diesen Verein erforderlich.',
    ],

    'session' => [
        'expired' => 'Ihre Sitzung ist abgelaufen. Bitte melden Sie sich erneut an.',
        'invalid' => 'Ungültige Sitzung.',
        'concurrent_login' => 'Sie wurden von einem anderen Gerät angemeldet.',
        'timeout_warning' => 'Ihre Sitzung läuft in :minutes Minuten ab.',
        'extend_session' => 'Sitzung verlängern',
    ],

    'security' => [
        'suspicious_activity' => 'Verdächtige Aktivität erkannt.',
        'login_from_new_device' => 'Anmeldung von einem neuen Gerät erkannt.',
        'unusual_login_location' => 'Anmeldung von einem ungewöhnlichen Standort.',
        'multiple_failed_attempts' => 'Mehrere fehlgeschlagene Anmeldeversuche.',
        'account_locked' => 'Konto aufgrund verdächtiger Aktivität gesperrt.',
        'security_notification_sent' => 'Sicherheitsbenachrichtigung gesendet.',
    ],

];