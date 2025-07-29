<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines - English
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    'login' => [
        'title' => 'Sign In',
        'welcome_back' => 'Welcome back!',
        'sign_in_account' => 'Sign in to your account',
        'email' => 'Email Address',
        'password' => 'Password',
        'remember_me' => 'Remember me',
        'forgot_password' => 'Forgot your password?',
        'sign_in' => 'Sign In',
        'no_account' => 'Don\'t have an account?',
        'create_account' => 'Create Account',
        'login_successful' => 'Successfully signed in.',
        'invalid_login' => 'Invalid login credentials.',
        'account_disabled' => 'Your account has been disabled.',
        'email_not_verified' => 'Please verify your email address.',
    ],

    'register' => [
        'title' => 'Register',
        'create_account' => 'Create New Account',
        'join_basketball' => 'Join our basketball community',
        'name' => 'Full Name',
        'email' => 'Email Address',
        'password' => 'Password',
        'password_confirmation' => 'Confirm Password',
        'terms_agree' => 'I agree to the :terms_of_service and :privacy_policy',
        'terms_of_service' => 'Terms of Service',
        'privacy_policy' => 'Privacy Policy',
        'register' => 'Register',
        'already_registered' => 'Already registered?',
        'sign_in' => 'Sign in here',
        'registration_successful' => 'Registration successful.',
        'verification_email_sent' => 'Verification email has been sent.',
    ],

    'forgot_password' => [
        'title' => 'Forgot Password',
        'forgot_password' => 'Forgot your password?',
        'no_problem' => 'No problem. Just let us know your email address and we will email you a password reset link.',
        'email' => 'Email Address',
        'send_reset_link' => 'Send Password Reset Link',
        'back_to_login' => 'Back to Login',
        'reset_link_sent' => 'Password reset link has been sent.',
        'reset_link_failed' => 'Failed to send reset link.',
    ],

    'reset_password' => [
        'title' => 'Reset Password',
        'reset_password' => 'Reset Password',
        'email' => 'Email Address',
        'password' => 'New Password',
        'password_confirmation' => 'Confirm Password',
        'reset_password_button' => 'Reset Password',
        'password_reset_successful' => 'Password reset successfully.',
        'invalid_token' => 'Invalid or expired reset token.',
    ],

    'two_factor' => [
        'title' => 'Two Factor Authentication',
        'confirm_access' => 'Confirm Access',
        'authentication_challenge' => 'Please confirm access to your account by entering the authentication code provided by your authenticator application.',
        'recovery_challenge' => 'Please confirm access to your account by entering one of your emergency recovery codes.',
        'code' => 'Code',
        'recovery_code' => 'Recovery Code',
        'use_authentication_code' => 'Use authentication code',
        'use_recovery_code' => 'Use recovery code',
        'sign_in' => 'Sign In',
        'invalid_code' => 'The provided code is invalid.',
        'invalid_recovery_code' => 'The provided recovery code is invalid.',
    ],

    'email_verification' => [
        'title' => 'Email Verification',
        'verify_email' => 'Verify Email Address',
        'verification_required' => 'Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.',
        'verification_sent' => 'A new verification link has been sent to the email address you provided during registration.',
        'resend_verification' => 'Resend Verification Email',
        'logout' => 'Log Out',
        'email_verified' => 'Email address verified successfully.',
    ],

    'confirm_password' => [
        'title' => 'Confirm Password',
        'confirm_password' => 'Confirm Password',
        'secure_area' => 'This is a secure area of the application. Please confirm your password before continuing.',
        'password' => 'Password',
        'confirm' => 'Confirm',
        'password_confirmed' => 'Password confirmed.',
        'incorrect_password' => 'The provided password is incorrect.',
    ],

    'social_login' => [
        'title' => 'Social Login',
        'continue_with' => 'Continue with',
        'google' => 'Google',
        'facebook' => 'Facebook',
        'github' => 'GitHub',
        'or_continue_with_email' => 'Or continue with email',
        'login_successful' => 'Successfully signed in with :provider.',
        'login_failed' => 'Social login failed. Please try again.',
        'email_already_exists' => 'An account with this email already exists.',
        'provider_not_supported' => 'This provider is not supported.',
        'account_linked' => 'Account linked successfully.',
        'account_unlinked' => 'Account unlinked successfully.',
    ],

    'logout' => [
        'title' => 'Sign Out',
        'logout' => 'Sign Out',
        'confirm_logout' => 'Are you sure you want to sign out?',
        'logout_successful' => 'Successfully signed out.',
        'logout_other_devices' => 'Sign out other devices',
        'logout_other_devices_description' => 'Sign out of all other browser sessions across all of your devices.',
    ],

    'account' => [
        'deactivated' => 'Your account has been deactivated. Contact administrator.',
        'suspended' => 'Your account has been suspended.',
        'email_change_verification' => 'Verify email change',
        'email_changed' => 'Email address changed successfully.',
        'password_changed' => 'Password changed successfully.',
        'profile_updated' => 'Profile updated successfully.',
        'settings_updated' => 'Settings updated successfully.',
    ],

    'emergency_access' => [
        'title' => 'Emergency Access',
        'emergency_login' => 'Emergency Login',
        'access_description' => 'You are accessing this information via emergency QR code.',
        'limited_access' => 'Your access is limited to emergency information.',
        'access_logged' => 'This access is logged for security purposes.',
        'invalid_access_key' => 'Invalid or expired emergency access key.',
        'access_expired' => 'Emergency access has expired.',
        'access_revoked' => 'Emergency access has been revoked.',
        'team_access_only' => 'Access limited to team-related information.',
    ],

    'roles' => [
        'admin' => 'Administrator',
        'club_admin' => 'Club Administrator',
        'trainer' => 'Trainer',
        'scorer' => 'Scorer',
        'player' => 'Player',
        'parent' => 'Parent',
        'guest' => 'Guest',
    ],

    'permissions' => [
        'access_denied' => 'Access denied.',
        'insufficient_permissions' => 'Insufficient permissions.',
        'role_required' => 'This action requires role: :role',
        'permission_required' => 'This action requires permission: :permission',
        'team_access_required' => 'Team access required.',
        'club_access_required' => 'Club access required.',
    ],

    'session' => [
        'expired' => 'Your session has expired. Please sign in again.',
        'invalid' => 'Invalid session.',
        'concurrent_login' => 'You have been signed in from another device.',
        'timeout_warning' => 'Your session will expire in :minutes minutes.',
        'extend_session' => 'Extend Session',
    ],

    'security' => [
        'suspicious_activity' => 'Suspicious activity detected.',
        'login_from_new_device' => 'Login from new device detected.',
        'unusual_login_location' => 'Login from unusual location.',
        'multiple_failed_attempts' => 'Multiple failed login attempts.',
        'account_locked' => 'Account locked due to suspicious activity.',
        'security_notification_sent' => 'Security notification sent.',
    ],

];