<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Player Management - English
    |--------------------------------------------------------------------------
    |
    | Player-related translations for management and display.
    |
    */

    'title' => 'Players',
    'player' => 'Player',
    'players' => 'Players',
    'my_profile' => 'My Profile',
    'player_profile' => 'Player Profile',
    'all_players' => 'All Players',
    'team_players' => 'Team Players',

    'actions' => [
        'create_player' => 'Create Player',
        'edit_player' => 'Edit Player',
        'delete_player' => 'Delete Player',
        'view_player' => 'View Player',
        'activate_player' => 'Activate Player',
        'deactivate_player' => 'Deactivate Player',
        'transfer_player' => 'Transfer Player',
        'update_statistics' => 'Update Statistics',
        'upload_photo' => 'Upload Photo',
        'add_emergency_contact' => 'Add Emergency Contact',
        'generate_qr_code' => 'Generate QR Code',
    ],

    'personal_info' => [
        'title' => 'Personal Information',
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'nickname' => 'Nickname',
        'full_name' => 'Full Name',
        'birth_date' => 'Birth Date',
        'age' => 'Age',
        'gender' => 'Gender',
        'nationality' => 'Nationality',
        'birth_place' => 'Birth Place',
        'profile_photo' => 'Profile Photo',
    ],

    'contact_info' => [
        'title' => 'Contact Information',
        'email' => 'Email',
        'phone' => 'Phone',
        'parent_phone' => 'Parent Phone',
        'address' => 'Address',
        'street' => 'Street',
        'street_number' => 'Street Number',
        'postal_code' => 'Postal Code',
        'city' => 'City',
    ],

    'basketball_info' => [
        'title' => 'Basketball Information',
        'jersey_number' => 'Jersey Number',
        'position' => 'Position',
        'preferred_position' => 'Preferred Position',
        'height' => 'Height (cm)',
        'weight' => 'Weight (kg)',
        'dominant_hand' => 'Dominant Hand',
        'basketball_since' => 'Playing Basketball Since',
        'previous_clubs' => 'Previous Clubs',
        'achievements' => 'Achievements',
        'experience_years' => 'Years of Experience',
        'bmi' => 'BMI',
    ],

    'team_info' => [
        'title' => 'Team Information',
        'current_team' => 'Current Team',
        'joined_team_at' => 'Joined Team',
        'contract_until' => 'Contract Until',
        'is_captain' => 'Captain',
        'is_vice_captain' => 'Vice Captain',
        'player_status' => 'Player Status',
        'is_active' => 'Active',
        'notes' => 'Notes',
    ],

    'medical_info' => [
        'title' => 'Medical Information',
        'medical_conditions' => 'Medical Conditions',
        'medications' => 'Medications',
        'allergies' => 'Allergies',
        'doctor_contact' => 'Doctor Contact',
        'insurance_info' => 'Insurance Information',
        'emergency_info' => 'Emergency Information',
    ],

    'consent_info' => [
        'title' => 'Consent Information',
        'medical_consent' => 'Medical Treatment',
        'photo_consent' => 'Photo Rights',
        'data_processing_consent' => 'Data Processing',
        'consent_given' => 'Consent Given',
        'consent_date' => 'Consent Date',
        'consent_required' => 'Consent Required',
        'all_consents_given' => 'All Consents Given',
        'missing_consents' => 'Missing Consents',
    ],

    'parent_info' => [
        'title' => 'Parent/Guardian Information',
        'parent_name' => 'Parent/Guardian Name',
        'parent_email' => 'Parent Email',
        'parent_phone_primary' => 'Parent Phone (Primary)',
        'parent_phone_secondary' => 'Parent Phone (Secondary)',
        'guardian_name' => 'Guardian Name',
        'guardian_contact' => 'Guardian Contact',
        'is_minor' => 'Minor',
        'parental_consent_required' => 'Parental Consent Required',
    ],

    'emergency_contacts' => [
        'title' => 'Emergency Contacts',
        'contact_name' => 'Name',
        'phone_number' => 'Phone Number',
        'relationship' => 'Relationship',
        'is_primary' => 'Primary Contact',
        'notes' => 'Notes',
        'add_contact' => 'Add Contact',
        'edit_contact' => 'Edit Contact',
        'delete_contact' => 'Delete Contact',
        'primary_contact' => 'Primary Emergency Contact',
        'secondary_contacts' => 'Additional Emergency Contacts',
        'no_contacts' => 'No emergency contacts recorded',
    ],

    'statistics' => [
        'title' => 'Statistics',
        'season_stats' => 'Season Statistics',
        'career_stats' => 'Career Statistics',
        'last_games' => 'Recent Games',
        'averages' => 'Averages',
        'totals' => 'Totals',
        'per_game' => 'Per Game',
        'per_minute' => 'Per Minute',
        'no_statistics' => 'No statistics available',
    ],

    'status' => [
        'active' => 'Active',
        'injured' => 'Injured',
        'suspended' => 'Suspended',
        'inactive' => 'Inactive',
        'transferred' => 'Transferred',
    ],

    'messages' => [
        'created' => 'Player created successfully.',
        'updated' => 'Player updated successfully.',
        'deleted' => 'Player deleted successfully.',
        'activated' => 'Player activated successfully.',
        'deactivated' => 'Player deactivated successfully.',
        'transferred' => 'Player transferred successfully.',
        'photo_uploaded' => 'Profile photo uploaded successfully.',
        'emergency_contact_added' => 'Emergency contact added successfully.',
        'emergency_contact_updated' => 'Emergency contact updated successfully.',
        'emergency_contact_deleted' => 'Emergency contact deleted successfully.',
        'player_not_found' => 'Player not found.',
        'jersey_number_taken' => 'This jersey number is already taken.',
        'cannot_delete_with_games' => 'Cannot delete player with game history.',
        'consent_updated' => 'Consent information updated.',
        'qr_code_generated' => 'Emergency access QR code generated.',
    ],

    'validation' => [
        'first_name_required' => 'First name is required.',
        'last_name_required' => 'Last name is required.',
        'birth_date_required' => 'Birth date is required.',
        'birth_date_valid' => 'Please provide a valid birth date.',
        'jersey_number_unique' => 'This jersey number is already taken in the team.',
        'jersey_number_range' => 'Jersey number must be between 0 and 99.',
        'position_valid' => 'Please select a valid position.',
        'height_numeric' => 'Height must be a valid number.',
        'weight_numeric' => 'Weight must be a valid number.',
        'email_valid' => 'Please provide a valid email address.',
        'phone_valid' => 'Please provide a valid phone number.',
        'parent_consent_required' => 'Parental consent is required for minors.',
        'medical_consent_required' => 'Medical consent is required.',
    ],

    'filters' => [
        'all_positions' => 'All Positions',
        'all_statuses' => 'All Statuses',
        'active_only' => 'Active Only',
        'captains_only' => 'Captains Only',
        'minors_only' => 'Minors Only',
        'missing_consents' => 'Missing Consents',
        'by_age_group' => 'By Age Group',
        'by_team' => 'By Team',
    ],

    'tabs' => [
        'overview' => 'Overview',
        'personal' => 'Personal',
        'basketball' => 'Basketball',
        'medical' => 'Medical',
        'emergency' => 'Emergency',
        'statistics' => 'Statistics',
        'games' => 'Games',
        'documents' => 'Documents',
    ],

    'permissions' => [
        'view_players' => 'View Players',
        'create_players' => 'Create Players',
        'edit_players' => 'Edit Players',
        'delete_players' => 'Delete Players',
        'view_player_statistics' => 'View Player Statistics',
        'edit_player_statistics' => 'Edit Player Statistics',
        'view_emergency_contacts' => 'View Emergency Contacts',
        'edit_emergency_contacts' => 'Edit Emergency Contacts',
        'generate_emergency_qr_codes' => 'Generate Emergency QR Codes',
    ],

    'export' => [
        'player_list' => 'Export Player List',
        'player_statistics' => 'Export Player Statistics',
        'emergency_contacts' => 'Export Emergency Contacts',
        'team_roster' => 'Export Team Roster',
    ],

];