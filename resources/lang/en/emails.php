<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Email Language Lines - English
    |--------------------------------------------------------------------------
    |
    | The following language lines are used in email templates sent by the
    | BasketManager Pro application.
    |
    */

    'common' => [
        'greeting' => 'Hello :name,',
        'greeting_default' => 'Hello,',
        'regards' => 'Regards',
        'signature' => 'The BasketManager Pro Team',
        'footer_text' => 'If you\'re having trouble clicking the ":actionText" button, copy and paste the URL below into your web browser:',
        'copyright' => '© :year BasketManager Pro. All rights reserved.',
        'unsubscribe' => 'If you no longer wish to receive these emails, you can unsubscribe here: :url',
        'app_name' => 'BasketManager Pro',
        'privacy_notice' => 'This email may contain confidential information. If you received this email in error, please delete it.',
    ],

    'auth' => [
        'welcome_subject' => 'Welcome to BasketManager Pro!',
        'welcome_line1' => 'Welcome to BasketManager Pro! We\'re excited to have you join our basketball community.',
        'welcome_line2' => 'Your registration was successful. You can now sign in with your credentials.',
        'welcome_action' => 'Sign In Now',

        'verify_email_subject' => 'Verify Email Address',
        'verify_email_line1' => 'Please click the button below to verify your email address.',
        'verify_email_line2' => 'If you did not create an account, no further action is required.',
        'verify_email_action' => 'Verify Email Address',

        'reset_password_subject' => 'Reset Password',
        'reset_password_line1' => 'You are receiving this email because we received a password reset request for your account.',
        'reset_password_line2' => 'This password reset link will expire in :count minutes.',
        'reset_password_line3' => 'If you did not request a password reset, no further action is required.',
        'reset_password_action' => 'Reset Password',

        'two_factor_enabled_subject' => 'Two-Factor Authentication Enabled',
        'two_factor_enabled_line1' => 'Two-factor authentication has been successfully enabled for your account.',
        'two_factor_enabled_line2' => 'Your account is now additionally protected. You will need an authentication code for your next login.',
        'two_factor_enabled_line3' => 'Please store your recovery codes safely.',

        'login_alert_subject' => 'New Login Detected',
        'login_alert_line1' => 'A new login to your account has been detected.',
        'login_alert_line2' => 'IP Address: :ip',
        'login_alert_line3' => 'Time: :time',
        'login_alert_line4' => 'If this wasn\'t you, please change your password immediately.',
        'login_alert_action' => 'Change Password',
    ],

    'team_invitations' => [
        'subject' => 'Team Invitation: :team',
        'greeting' => 'You have been invited to join team ":team"!',
        'line1' => ':inviter has invited you to become a member of team ":team".',
        'line2' => 'If you would like to accept this invitation, click the button below:',
        'line3' => 'If you did not expect to receive an invitation to this team, you may disregard this email.',
        'action' => 'Accept Invitation',
        'team_details' => 'Team Details:',
        'club' => 'Club: :club',
        'category' => 'Category: :category',
        'season' => 'Season: :season',
        'coach' => 'Coach: :coach',
    ],

    'player_notifications' => [
        'registration_approved_subject' => 'Player Registration Approved',
        'registration_approved_line1' => 'Your registration as a player for team ":team" has been approved.',
        'registration_approved_line2' => 'You can now sign in and access your player profile.',
        'registration_approved_action' => 'View Profile',

        'jersey_assigned_subject' => 'Jersey Number Assigned',
        'jersey_assigned_line1' => 'You have been assigned jersey number :number for team ":team".',
        'jersey_assigned_line2' => 'This number is reserved for you for the entire :season season.',

        'captain_appointed_subject' => 'Appointed as Captain',
        'captain_appointed_line1' => 'Congratulations! You have been appointed as captain of team ":team".',
        'captain_appointed_line2' => 'With this role comes additional responsibility for your team.',

        'injury_report_subject' => 'Injury Report Required',
        'injury_report_line1' => 'Please report your current injury status for upcoming games.',
        'injury_report_line2' => 'This information helps the coach with game planning.',
        'injury_report_action' => 'Report Status',
    ],

    'game_notifications' => [
        'game_scheduled_subject' => 'New Game Scheduled: :home vs :away',
        'game_scheduled_line1' => 'A new game has been scheduled for your team.',
        'game_details' => 'Game Details:',
        'opponent' => 'Opponent: :opponent',
        'date_time' => 'Date & Time: :datetime',
        'venue' => 'Venue: :venue',
        'type' => 'Type: :type',
        'game_scheduled_action' => 'View Game',

        'game_reminder_subject' => 'Game Reminder: :home vs :away',
        'game_reminder_line1' => 'Reminder: Your game starts in :hours hours.',
        'game_reminder_line2' => 'Don\'t forget to arrive at the venue on time.',
        'game_reminder_action' => 'View Game Details',

        'game_cancelled_subject' => 'Game Cancelled: :home vs :away',
        'game_cancelled_line1' => 'The game scheduled for :datetime has been cancelled.',
        'game_cancelled_line2' => 'Reason: :reason',
        'game_cancelled_line3' => 'A new date will be announced as soon as possible.',

        'game_rescheduled_subject' => 'Game Rescheduled: :home vs :away',
        'game_rescheduled_line1' => 'The game has been rescheduled to a new date.',
        'old_date' => 'Old Date: :old_date',
        'new_date' => 'New Date: :new_date',
        'game_rescheduled_action' => 'View Updated Details',

        'game_result_subject' => 'Game Result: :home :home_score - :away_score :away',
        'game_result_line1' => 'The game has ended. Here is the final result:',
        'final_score' => 'Final Score: :home :home_score - :away_score :away',
        'your_stats' => 'Your Statistics:',
        'points' => 'Points: :points',
        'rebounds' => 'Rebounds: :rebounds',
        'assists' => 'Assists: :assists',
        'minutes' => 'Playing Time: :minutes minutes',
        'game_result_action' => 'View Full Statistics',
    ],

    'training_notifications' => [
        'training_scheduled_subject' => 'Training Scheduled: :date',
        'training_scheduled_line1' => 'A new training session has been scheduled for your team.',
        'training_details' => 'Training Details:',
        'date_time' => 'Date & Time: :datetime',
        'venue' => 'Venue: :venue',
        'focus' => 'Focus: :focus',
        'equipment' => 'Required Equipment: :equipment',
        'training_scheduled_action' => 'View Training',

        'training_cancelled_subject' => 'Training Cancelled: :date',
        'training_cancelled_line1' => 'The training scheduled for :datetime has been cancelled.',
        'training_cancelled_line2' => 'Reason: :reason',

        'training_reminder_subject' => 'Training Reminder: Today at :time',
        'training_reminder_line1' => 'Reminder: Training today at :time.',
        'training_reminder_line2' => 'Venue: :venue',
        'training_reminder_line3' => 'Please arrive 15 minutes early.',
    ],

    'emergency_notifications' => [
        'emergency_contact_added_subject' => 'Emergency Contact Added',
        'emergency_contact_added_line1' => 'A new emergency contact has been added for :player.',
        'emergency_contact_added_line2' => 'Contact: :contact_name (:relationship)',
        'emergency_contact_added_line3' => 'Phone: :phone',

        'qr_code_generated_subject' => 'Emergency QR Code Generated',
        'qr_code_generated_line1' => 'A new emergency QR code has been generated for team ":team".',
        'qr_code_generated_line2' => 'This code allows access to emergency contacts in case of emergency.',
        'qr_code_generated_line3' => 'Valid until: :expires_at',
        'qr_code_generated_action' => 'View QR Code',

        'emergency_access_used_subject' => 'Emergency Access Used',
        'emergency_access_used_line1' => 'Emergency access for team ":team" has been used.',
        'emergency_access_used_line2' => 'Time: :time',
        'emergency_access_used_line3' => 'IP Address: :ip',
        'emergency_access_used_line4' => 'If this was not authorized, please contact system administration immediately.',
    ],

    'admin_notifications' => [
        'new_registration_subject' => 'New User Registration',
        'new_registration_line1' => 'A new user has registered.',
        'user_details' => 'User Details:',
        'name' => 'Name: :name',
        'email' => 'Email: :email',
        'registration_date' => 'Registered: :date',
        'new_registration_action' => 'Manage User',

        'system_backup_subject' => 'System Backup :status',
        'system_backup_success_line1' => 'System backup completed successfully.',
        'system_backup_failed_line1' => 'System backup failed.',
        'backup_details' => 'Backup Details:',
        'backup_date' => 'Date: :date',
        'backup_size' => 'Size: :size',
        'backup_location' => 'Location: :location',
        'error_message' => 'Error Message: :error',

        'suspicious_activity_subject' => 'Suspicious Activity Detected',
        'suspicious_activity_line1' => 'Suspicious activity has been detected in the system.',
        'activity_details' => 'Activity Details:',
        'user' => 'User: :user',
        'activity' => 'Activity: :activity',
        'ip_address' => 'IP Address: :ip',
        'timestamp' => 'Time: :time',
        'suspicious_activity_action' => 'Review Logs',
    ],

    'statistics_reports' => [
        'weekly_report_subject' => 'Weekly Statistics Report',
        'weekly_report_line1' => 'Here is your weekly statistics report for :week.',
        'monthly_report_subject' => 'Monthly Statistics Report',
        'monthly_report_line1' => 'Here is your monthly statistics report for :month.',
        'season_report_subject' => 'Season Report :season',
        'season_report_line1' => 'The complete season report for :season is available.',
        'report_highlights' => 'Highlights:',
        'games_played' => 'Games Played: :count',
        'wins_losses' => 'Wins/Losses: :wins/:losses',
        'top_scorer' => 'Top Scorer: :player (:points points)',
        'report_action' => 'View Full Report',
    ],

    'system_notifications' => [
        'maintenance_scheduled_subject' => 'Scheduled Maintenance',
        'maintenance_scheduled_line1' => 'Scheduled maintenance for the BasketManager Pro system.',
        'maintenance_start' => 'Start: :start_time',
        'maintenance_end' => 'End: :end_time',
        'maintenance_scheduled_line2' => 'The system will be unavailable during this time.',
        'maintenance_scheduled_line3' => 'We apologize for any inconvenience.',

        'system_update_subject' => 'System Update Available',
        'system_update_line1' => 'A new version of BasketManager Pro is available.',
        'current_version' => 'Current Version: :current',
        'new_version' => 'New Version: :new',
        'update_features' => 'New Features: :features',
        'system_update_action' => 'Perform Update',

        'password_expiry_subject' => 'Password Expiring',
        'password_expiry_line1' => 'Your password will expire in :days days.',
        'password_expiry_line2' => 'Please change your password to continue having access.',
        'password_expiry_action' => 'Change Password',
    ],

    'gdpr_notifications' => [
        'data_export_subject' => 'Your Data is Ready for Download',
        'data_export_line1' => 'Your data export request has been processed.',
        'data_export_line2' => 'The file will be available for 30 days.',
        'data_export_action' => 'Download Data',

        'data_deletion_subject' => 'Data Deletion Confirmed',
        'data_deletion_line1' => 'Your data has been deleted as per your request.',
        'data_deletion_line2' => 'If you have any questions, please contact our data protection officer.',

        'consent_reminder_subject' => 'Consent Required',
        'consent_reminder_line1' => 'We need your updated consent for data processing.',
        'consent_reminder_line2' => 'Without your consent, we cannot provide you with all services.',
        'consent_reminder_action' => 'Give Consent',
    ],

];