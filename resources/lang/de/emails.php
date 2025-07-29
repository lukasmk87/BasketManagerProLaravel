<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Email Language Lines - German
    |--------------------------------------------------------------------------
    |
    | The following language lines are used in email templates sent by the
    | BasketManager Pro application.
    |
    */

    'common' => [
        'greeting' => 'Hallo :name,',
        'greeting_default' => 'Hallo,',
        'regards' => 'Mit freundlichen Grüßen',
        'signature' => 'Ihr BasketManager Pro Team',
        'footer_text' => 'Falls Sie Probleme beim Klicken auf den ":actionText" Button haben, kopieren Sie die unten stehende URL und fügen Sie sie in Ihren Webbrowser ein:',
        'copyright' => '© :year BasketManager Pro. Alle Rechte vorbehalten.',
        'unsubscribe' => 'Falls Sie diese E-Mails nicht mehr erhalten möchten, können Sie sich hier abmelden: :url',
        'app_name' => 'BasketManager Pro',
        'privacy_notice' => 'Diese E-Mail kann vertrauliche Informationen enthalten. Falls Sie diese E-Mail fälschlicherweise erhalten haben, löschen Sie sie bitte.',
    ],

    'auth' => [
        'welcome_subject' => 'Willkommen bei BasketManager Pro!',
        'welcome_line1' => 'Willkommen bei BasketManager Pro! Wir freuen uns, Sie in unserer Basketball-Community begrüßen zu dürfen.',
        'welcome_line2' => 'Ihre Registrierung war erfolgreich. Sie können sich jetzt mit Ihren Zugangsdaten anmelden.',
        'welcome_action' => 'Jetzt anmelden',

        'verify_email_subject' => 'E-Mail-Adresse bestätigen',
        'verify_email_line1' => 'Bitte klicken Sie auf den Button unten, um Ihre E-Mail-Adresse zu bestätigen.',
        'verify_email_line2' => 'Falls Sie kein Konto erstellt haben, ist keine weitere Aktion erforderlich.',
        'verify_email_action' => 'E-Mail-Adresse bestätigen',

        'reset_password_subject' => 'Passwort zurücksetzen',
        'reset_password_line1' => 'Sie erhalten diese E-Mail, weil wir eine Anfrage zum Zurücksetzen des Passworts für Ihr Konto erhalten haben.',
        'reset_password_line2' => 'Dieser Link zum Zurücksetzen des Passworts läuft in :count Minuten ab.',
        'reset_password_line3' => 'Falls Sie kein Zurücksetzen des Passworts angefordert haben, ist keine weitere Aktion erforderlich.',
        'reset_password_action' => 'Passwort zurücksetzen',

        'two_factor_enabled_subject' => 'Zwei-Faktor-Authentifizierung aktiviert',
        'two_factor_enabled_line1' => 'Die Zwei-Faktor-Authentifizierung wurde erfolgreich für Ihr Konto aktiviert.',
        'two_factor_enabled_line2' => 'Ihr Konto ist jetzt zusätzlich geschützt. Bei der nächsten Anmeldung benötigen Sie einen Authentifizierungscode.',
        'two_factor_enabled_line3' => 'Bewahren Sie Ihre Wiederherstellungscodes sicher auf.',

        'login_alert_subject' => 'Neue Anmeldung erkannt',
        'login_alert_line1' => 'Eine neue Anmeldung bei Ihrem Konto wurde erkannt.',
        'login_alert_line2' => 'IP-Adresse: :ip',
        'login_alert_line3' => 'Zeit: :time',
        'login_alert_line4' => 'Falls Sie das nicht waren, ändern Sie bitte sofort Ihr Passwort.',
        'login_alert_action' => 'Passwort ändern',
    ],

    'team_invitations' => [
        'subject' => 'Einladung zum Team: :team',
        'greeting' => 'Sie wurden eingeladen, dem Team ":team" beizutreten!',
        'line1' => ':inviter hat Sie eingeladen, Mitglied des Teams ":team" zu werden.',
        'line2' => 'Wenn Sie diese Einladung annehmen möchten, klicken Sie auf den Button unten:',
        'line3' => 'Falls Sie nicht erwartet haben, eine Einladung zu diesem Team zu erhalten, können Sie diese E-Mail ignorieren.',
        'action' => 'Einladung annehmen',
        'team_details' => 'Team-Details:',
        'club' => 'Verein: :club',
        'category' => 'Kategorie: :category',
        'season' => 'Saison: :season',
        'coach' => 'Trainer: :coach',
    ],

    'player_notifications' => [
        'registration_approved_subject' => 'Spielerregistrierung genehmigt',
        'registration_approved_line1' => 'Ihre Registrierung als Spieler für das Team ":team" wurde genehmigt.',
        'registration_approved_line2' => 'Sie können sich jetzt anmelden und auf Ihre Spielerprofile zugreifen.',
        'registration_approved_action' => 'Profil anzeigen',

        'jersey_assigned_subject' => 'Trikotnummer zugewiesen',
        'jersey_assigned_line1' => 'Ihnen wurde die Trikotnummer :number für das Team ":team" zugewiesen.',
        'jersey_assigned_line2' => 'Diese Nummer ist für die gesamte Saison :season reserviert.',

        'captain_appointed_subject' => 'Zum Kapitän ernannt',
        'captain_appointed_line1' => 'Herzlichen Glückwunsch! Sie wurden zum Kapitän des Teams ":team" ernannt.',
        'captain_appointed_line2' => 'Mit dieser Rolle übernehmen Sie zusätzliche Verantwortung für Ihr Team.',

        'injury_report_subject' => 'Verletzungsmeldung erforderlich',
        'injury_report_line1' => 'Bitte melden Sie Ihren aktuellen Verletzungsstatus für die kommenden Spiele.',
        'injury_report_line2' => 'Diese Information hilft dem Trainer bei der Spielplanung.',
        'injury_report_action' => 'Status melden',
    ],

    'game_notifications' => [
        'game_scheduled_subject' => 'Neues Spiel angesetzt: :home vs :away',
        'game_scheduled_line1' => 'Ein neues Spiel wurde für Ihr Team angesetzt.',
        'game_details' => 'Spiel-Details:',
        'opponent' => 'Gegner: :opponent',
        'date_time' => 'Datum & Zeit: :datetime',
        'venue' => 'Spielort: :venue',
        'type' => 'Typ: :type',
        'game_scheduled_action' => 'Spiel anzeigen',

        'game_reminder_subject' => 'Spielerinnerung: :home vs :away',
        'game_reminder_line1' => 'Erinnerung: Ihr Spiel beginnt in :hours Stunden.',
        'game_reminder_line2' => 'Vergessen Sie nicht, rechtzeitig am Spielort zu sein.',
        'game_reminder_action' => 'Spieldetails anzeigen',

        'game_cancelled_subject' => 'Spiel abgesagt: :home vs :away',
        'game_cancelled_line1' => 'Das für :datetime geplante Spiel wurde abgesagt.',
        'game_cancelled_line2' => 'Grund: :reason',
        'game_cancelled_line3' => 'Ein neuer Termin wird so bald wie möglich bekannt gegeben.',

        'game_rescheduled_subject' => 'Spiel verschoben: :home vs :away',
        'game_rescheduled_line1' => 'Das Spiel wurde auf einen neuen Termin verschoben.',
        'old_date' => 'Alter Termin: :old_date',
        'new_date' => 'Neuer Termin: :new_date',
        'game_rescheduled_action' => 'Aktualisierte Details anzeigen',

        'game_result_subject' => 'Spielergebnis: :home :home_score - :away_score :away',
        'game_result_line1' => 'Das Spiel ist beendet. Hier ist das Endergebnis:',
        'final_score' => 'Endstand: :home :home_score - :away_score :away',
        'your_stats' => 'Ihre Statistiken:',
        'points' => 'Punkte: :points',
        'rebounds' => 'Rebounds: :rebounds',
        'assists' => 'Assists: :assists',
        'minutes' => 'Spielzeit: :minutes Minuten',
        'game_result_action' => 'Vollständige Statistiken anzeigen',
    ],

    'training_notifications' => [
        'training_scheduled_subject' => 'Training angesetzt: :date',
        'training_scheduled_line1' => 'Ein neues Training wurde für Ihr Team angesetzt.',
        'training_details' => 'Training-Details:',
        'date_time' => 'Datum & Zeit: :datetime',
        'venue' => 'Ort: :venue',
        'focus' => 'Schwerpunkt: :focus',
        'equipment' => 'Benötigte Ausrüstung: :equipment',
        'training_scheduled_action' => 'Training anzeigen',

        'training_cancelled_subject' => 'Training abgesagt: :date',
        'training_cancelled_line1' => 'Das für :datetime geplante Training wurde abgesagt.',
        'training_cancelled_line2' => 'Grund: :reason',

        'training_reminder_subject' => 'Trainingserinnerung: Heute um :time',
        'training_reminder_line1' => 'Erinnerung: Training heute um :time.',
        'training_reminder_line2' => 'Ort: :venue',
        'training_reminder_line3' => 'Seien Sie bitte 15 Minuten vorher da.',
    ],

    'emergency_notifications' => [
        'emergency_contact_added_subject' => 'Notfallkontakt hinzugefügt',
        'emergency_contact_added_line1' => 'Ein neuer Notfallkontakt wurde für :player hinzugefügt.',
        'emergency_contact_added_line2' => 'Kontakt: :contact_name (:relationship)',
        'emergency_contact_added_line3' => 'Telefon: :phone',

        'qr_code_generated_subject' => 'Notfall-QR-Code generiert',
        'qr_code_generated_line1' => 'Ein neuer Notfall-QR-Code wurde für das Team ":team" generiert.',
        'qr_code_generated_line2' => 'Dieser Code ermöglicht den Zugriff auf Notfallkontakte im Notfall.',
        'qr_code_generated_line3' => 'Gültig bis: :expires_at',
        'qr_code_generated_action' => 'QR-Code anzeigen',

        'emergency_access_used_subject' => 'Notfallzugriff verwendet',
        'emergency_access_used_line1' => 'Der Notfallzugriff für Team ":team" wurde verwendet.',
        'emergency_access_used_line2' => 'Zeit: :time',
        'emergency_access_used_line3' => 'IP-Adresse: :ip',
        'emergency_access_used_line4' => 'Falls dies nicht autorisiert war, kontaktieren Sie sofort die Systemadministration.',
    ],

    'admin_notifications' => [
        'new_registration_subject' => 'Neue Benutzerregistrierung',
        'new_registration_line1' => 'Ein neuer Benutzer hat sich registriert.',
        'user_details' => 'Benutzer-Details:',
        'name' => 'Name: :name',
        'email' => 'E-Mail: :email',
        'registration_date' => 'Registriert am: :date',
        'new_registration_action' => 'Benutzer verwalten',

        'system_backup_subject' => 'System-Backup :status',
        'system_backup_success_line1' => 'Das System-Backup wurde erfolgreich erstellt.',
        'system_backup_failed_line1' => 'Das System-Backup ist fehlgeschlagen.',
        'backup_details' => 'Backup-Details:',
        'backup_date' => 'Datum: :date',
        'backup_size' => 'Größe: :size',
        'backup_location' => 'Speicherort: :location',
        'error_message' => 'Fehlermeldung: :error',

        'suspicious_activity_subject' => 'Verdächtige Aktivität erkannt',
        'suspicious_activity_line1' => 'Verdächtige Aktivität wurde im System erkannt.',
        'activity_details' => 'Aktivitäts-Details:',
        'user' => 'Benutzer: :user',
        'activity' => 'Aktivität: :activity',
        'ip_address' => 'IP-Adresse: :ip',
        'timestamp' => 'Zeit: :time',
        'suspicious_activity_action' => 'Logs überprüfen',
    ],

    'statistics_reports' => [
        'weekly_report_subject' => 'Wöchentlicher Statistikbericht',
        'weekly_report_line1' => 'Hier ist Ihr wöchentlicher Statistikbericht für :week.',
        'monthly_report_subject' => 'Monatlicher Statistikbericht',
        'monthly_report_line1' => 'Hier ist Ihr monatlicher Statistikbericht für :month.',
        'season_report_subject' => 'Saisonbericht :season',
        'season_report_line1' => 'Der vollständige Saisonbericht für :season ist verfügbar.',
        'report_highlights' => 'Highlights:',
        'games_played' => 'Gespielte Spiele: :count',
        'wins_losses' => 'Siege/Niederlagen: :wins/:losses',
        'top_scorer' => 'Topscorer: :player (:points Punkte)',
        'report_action' => 'Vollständigen Bericht anzeigen',
    ],

    'system_notifications' => [
        'maintenance_scheduled_subject' => 'Geplante Wartungsarbeiten',
        'maintenance_scheduled_line1' => 'Geplante Wartungsarbeiten für das BasketManager Pro System.',
        'maintenance_start' => 'Start: :start_time',
        'maintenance_end' => 'Ende: :end_time',
        'maintenance_scheduled_line2' => 'Während dieser Zeit ist das System nicht verfügbar.',
        'maintenance_scheduled_line3' => 'Wir entschuldigen uns für die Unannehmlichkeiten.',

        'system_update_subject' => 'System-Update verfügbar',
        'system_update_line1' => 'Eine neue Version von BasketManager Pro ist verfügbar.',
        'current_version' => 'Aktuelle Version: :current',
        'new_version' => 'Neue Version: :new',
        'update_features' => 'Neue Funktionen: :features',
        'system_update_action' => 'Update durchführen',

        'password_expiry_subject' => 'Passwort läuft ab',
        'password_expiry_line1' => 'Ihr Passwort läuft in :days Tagen ab.',
        'password_expiry_line2' => 'Bitte ändern Sie Ihr Passwort, um weiterhin Zugriff zu haben.',
        'password_expiry_action' => 'Passwort ändern',
    ],

    'gdpr_notifications' => [
        'data_export_subject' => 'Ihre Daten sind bereit zum Download',
        'data_export_line1' => 'Ihr Antrag auf Datenexport wurde bearbeitet.',
        'data_export_line2' => 'Die Datei ist 30 Tage lang verfügbar.',
        'data_export_action' => 'Daten herunterladen',

        'data_deletion_subject' => 'Löschung Ihrer Daten bestätigt',
        'data_deletion_line1' => 'Ihre Daten wurden gemäß Ihrer Anfrage gelöscht.',
        'data_deletion_line2' => 'Falls Sie Fragen haben, kontaktieren Sie unseren Datenschutzbeauftragten.',

        'consent_reminder_subject' => 'Einverständniserklärung erforderlich',
        'consent_reminder_line1' => 'Wir benötigen Ihr aktualisiertes Einverständnis für die Datenverarbeitung.',
        'consent_reminder_line2' => 'Ohne Ihr Einverständnis können wir Ihnen nicht alle Dienste anbieten.',
        'consent_reminder_action' => 'Einverständnis erteilen',
    ],

];