<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Installation Wizard Translations (English)
    |--------------------------------------------------------------------------
    */

    // General
    'app_name' => 'BasketManager Pro',
    'installation_wizard' => 'Installation Wizard',
    'step' => 'Step :current of :total',
    'continue' => 'Continue',
    'back' => 'Back',
    'finish' => 'Finish Installation',
    'retry' => 'Retry',
    'test_connection' => 'Test Connection',
    'optional' => 'Optional',
    'required' => 'Required',
    'success' => 'Success',
    'error' => 'Error',
    'warning' => 'Warning',

    // Step 0: Language Selection
    'select_language' => 'Select Language',
    'select_language_subtitle' => 'Choose your preferred language for the installation',
    'german' => 'German',
    'english' => 'English',

    // Step 1: Welcome
    'welcome_title' => 'Welcome to BasketManager Pro',
    'welcome_subtitle' => 'Professional Basketball Club Management System',
    'welcome_description' => 'This installation wizard will guide you through setting up BasketManager Pro. The process takes about 5-10 minutes.',
    'welcome_features' => 'Features:',
    'feature_live_scoring' => 'Live game tracking and statistics',
    'feature_team_management' => 'Team and player management',
    'feature_training' => 'Training management and drill library',
    'feature_tournaments' => 'Tournament management with brackets',
    'feature_subscriptions' => 'Multi-tenant subscription system',
    'feature_gdpr' => 'GDPR-compliant data management',
    'start_installation' => 'Start Installation',

    // Step 2: Server Requirements
    'requirements_title' => 'Check Server Requirements',
    'requirements_subtitle' => 'Checking system requirements',
    'requirements_description' => 'Your server must meet the following requirements to run BasketManager Pro.',
    'requirement' => 'Requirement',
    'current_value' => 'Current Value',
    'status' => 'Status',
    'all_requirements_met' => 'All requirements met!',
    'requirements_not_met' => 'Some requirements are not met. Please fix these issues before proceeding.',
    'php_version' => 'PHP Version',
    'php_extensions' => 'PHP Extensions',
    'php_configuration' => 'PHP Configuration',
    'php_functions' => 'PHP Functions',

    // Step 3: Permissions
    'permissions_title' => 'Check Folder Permissions',
    'permissions_subtitle' => 'Checking write permissions',
    'permissions_description' => 'The following directories must be writable.',
    'folder' => 'Folder',
    'path' => 'Path',
    'permission' => 'Permission',
    'writable' => 'Writable',
    'not_writable' => 'Not Writable',
    'all_permissions_ok' => 'All permissions correct!',
    'permissions_issues' => 'Some folders are not writable. Run the following commands:',
    'fix_permissions_title' => 'Fix Permissions',

    // Step 4: Environment Configuration
    'environment_title' => 'Environment Configuration',
    'environment_subtitle' => 'Configure application settings',
    'environment_description' => 'Configure your application, database, and external services.',
    'tab_application' => 'Application',
    'tab_database' => 'Database',
    'tab_mail' => 'Email',
    'tab_stripe' => 'Stripe',

    // Application Settings
    'app_name_label' => 'Application Name',
    'app_name_placeholder' => 'BasketManager Pro',
    'app_url_label' => 'Application URL',
    'app_url_placeholder' => 'https://your-domain.com',
    'app_env_label' => 'Environment',
    'app_env_local' => 'Local (Development)',
    'app_env_staging' => 'Staging (Testing)',
    'app_env_production' => 'Production (Live)',
    'app_debug_label' => 'Debug Mode',
    'app_debug_help' => 'Enable debug mode only in development environments',

    // Database Settings
    'db_connection_label' => 'Database Type',
    'db_host_label' => 'Host',
    'db_port_label' => 'Port',
    'db_database_label' => 'Database Name',
    'db_username_label' => 'Username',
    'db_password_label' => 'Password',
    'test_database_connection' => 'Test Database Connection',
    'database_connection_success' => 'Database connection successful!',
    'database_connection_failed' => 'Database connection failed',

    // Mail Settings
    'mail_mailer_label' => 'Mail Driver',
    'mail_host_label' => 'SMTP Host',
    'mail_port_label' => 'SMTP Port',
    'mail_username_label' => 'SMTP Username',
    'mail_password_label' => 'SMTP Password',
    'mail_encryption_label' => 'Encryption',
    'mail_from_address_label' => 'From Email',
    'mail_from_name_label' => 'From Name',
    'mail_configuration_optional' => 'Email configuration is optional and can be set up later.',

    // Stripe Settings
    'stripe_key_label' => 'Stripe Publishable Key',
    'stripe_key_placeholder' => 'pk_test_...',
    'stripe_secret_label' => 'Stripe Secret Key',
    'stripe_secret_placeholder' => 'sk_test_...',
    'stripe_webhook_label' => 'Stripe Webhook Secret',
    'stripe_webhook_placeholder' => 'whsec_...',
    'test_stripe_connection' => 'Test Stripe Connection',
    'stripe_connection_success' => 'Stripe connection successful!',
    'stripe_connection_failed' => 'Stripe connection failed',
    'stripe_configuration_optional' => 'Stripe configuration is optional and can be set up later.',

    'save_configuration' => 'Save Configuration',
    'configuration_saved' => 'Configuration saved successfully',
    'environment_saved' => 'Environment variables saved successfully',
    'environment_save_failed' => 'Failed to save environment variables',
    'configure_environment_first' => 'Please configure the environment first',

    // Step 5: Database Setup
    'database_title' => 'Set Up Database',
    'database_subtitle' => 'Run migrations and seeders',
    'database_description' => 'This step will create all necessary database tables and seed initial data.',
    'run_migrations' => 'Run Migrations',
    'migrations_running' => 'Running migrations...',
    'migrations_completed' => 'Migrations completed successfully',
    'migration_failed' => 'Migration failed',
    'migration_output' => 'Migration Output',
    'complete_migrations_first' => 'Please complete database migrations first',

    // Step 6: Super Admin Creation
    'admin_title' => 'Create Super Admin',
    'admin_subtitle' => 'Create your first administrator account',
    'admin_description' => 'This account will have full control over your BasketManager Pro installation.',
    'tenant_name_label' => 'Organization Name',
    'tenant_name_placeholder' => 'My Basketball Club',
    'admin_name_label' => 'Admin Name',
    'admin_name_placeholder' => 'John Doe',
    'admin_email_label' => 'Admin Email',
    'admin_email_placeholder' => 'admin@example.com',
    'admin_password_label' => 'Password',
    'admin_password_confirm_label' => 'Confirm Password',
    'password_strength' => 'Password Strength',
    'password_weak' => 'Weak',
    'password_medium' => 'Medium',
    'password_strong' => 'Strong',
    'subscription_tier_label' => 'Subscription Tier',
    'subscription_tier_description' => 'Choose your subscription plan (can be changed later)',

    // Subscription Tiers
    'subscription_free' => 'Free',
    'subscription_basic' => 'Basic',
    'subscription_professional' => 'Professional',
    'subscription_enterprise' => 'Enterprise',
    'subscription_custom' => 'Custom',
    'unlimited' => 'Unlimited',

    'create_admin' => 'Create Administrator',
    'admin_creation_success' => 'Administrator created successfully',
    'admin_creation_failed' => 'Failed to create administrator',

    // Step 7: Complete
    'complete_title' => 'Installation Complete!',
    'complete_subtitle' => 'BasketManager Pro is ready to use',
    'complete_description' => 'Your installation was successful. You can now log in with your administrator credentials.',
    'your_credentials' => 'Your Credentials',
    'email' => 'Email',
    'important_note' => 'Important Note',
    'change_password_note' => 'Please change your password after first login.',
    'save_credentials_note' => 'Save your credentials in a secure location.',
    'go_to_login' => 'Go to Login',
    'explore_dashboard' => 'Explore Dashboard',

    // Errors
    'stripe_valid' => 'Stripe keys are valid',
    'installation_locked' => 'Installation is already complete. Use `php artisan install:unlock --force` to reinstall.',

    // Help Text
    'help_php_version' => 'BasketManager Pro requires at least PHP 8.2',
    'help_database' => 'MySQL 8.0+ or PostgreSQL 14+ is recommended',
    'help_stripe' => 'Find your Stripe keys in your Stripe Dashboard under Developers > API keys',
];
