<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Basketball Terminology - German
    |--------------------------------------------------------------------------
    |
    | Basketball-specific terms, positions, categories, and actions in German.
    |
    */

    'positions' => [
        'PG' => 'Aufbauspieler',
        'SG' => 'Shooting Guard',
        'SF' => 'Small Forward', 
        'PF' => 'Power Forward',
        'C' => 'Center',
    ],

    'position_abbreviations' => [
        'PG' => 'AS',
        'SG' => 'SG',
        'SF' => 'SF',
        'PF' => 'PF',
        'C' => 'C',
    ],

    'categories' => [
        'U8' => 'U8 (unter 8 Jahre)',
        'U10' => 'U10 (unter 10 Jahre)',
        'U12' => 'U12 (unter 12 Jahre)',
        'U14' => 'U14 (unter 14 Jahre)',
        'U16' => 'U16 (unter 16 Jahre)',
        'U18' => 'U18 (unter 18 Jahre)',
        'U20' => 'U20 (unter 20 Jahre)',
        'Herren' => 'Herren',
        'Damen' => 'Damen',
        'Senioren' => 'Senioren',
        'Mixed' => 'Mixed',
    ],

    'game_actions' => [
        'field_goal_made' => 'Feldwurf getroffen',
        'field_goal_missed' => 'Feldwurf verfehlt',
        'three_point_made' => 'Dreipunktwurf getroffen',
        'three_point_missed' => 'Dreipunktwurf verfehlt',
        'free_throw_made' => 'Freiwurf getroffen',
        'free_throw_missed' => 'Freiwurf verfehlt',
        'rebound_offensive' => 'Offensiv-Rebound',
        'rebound_defensive' => 'Defensiv-Rebound',
        'assist' => 'Assist',
        'steal' => 'Steal',
        'block' => 'Block',
        'turnover' => 'Ballverlust',
        'foul_personal' => 'Persönliches Foul',
        'foul_technical' => 'Technisches Foul',
        'foul_flagrant' => 'Flagrant Foul',
    ],

    'game_statuses' => [
        'scheduled' => 'Geplant',
        'live' => 'Live',
        'finished' => 'Beendet',
        'cancelled' => 'Abgesagt',
        'postponed' => 'Verschoben',
    ],

    'game_types' => [
        'regular' => 'Ligaspiel',
        'playoff' => 'Playoff',
        'friendly' => 'Freundschaftsspiel',
        'tournament' => 'Turnier',
        'cup' => 'Pokal',
    ],

    'player_statuses' => [
        'active' => 'Aktiv',
        'injured' => 'Verletzt',
        'suspended' => 'Gesperrt',
        'inactive' => 'Inaktiv',
        'transferred' => 'Transferiert',
    ],

    'team_statuses' => [
        'active' => 'Aktiv',
        'inactive' => 'Inaktiv',
        'disbanded' => 'Aufgelöst',
        'archived' => 'Archiviert',
    ],

    'statistics' => [
        'points' => 'Punkte',
        'field_goals' => 'Feldwürfe',
        'field_goals_made' => 'Feldwürfe getroffen',
        'field_goals_attempted' => 'Feldwürfe versucht',
        'field_goal_percentage' => 'Feldwurf-Quote',
        'three_points' => 'Dreipunktwürfe',
        'three_points_made' => 'Dreipunktwürfe getroffen',
        'three_points_attempted' => 'Dreipunktwürfe versucht',
        'three_point_percentage' => 'Dreipunkt-Quote',
        'free_throws' => 'Freiwürfe',
        'free_throws_made' => 'Freiwürfe getroffen',
        'free_throws_attempted' => 'Freiwürfe versucht',
        'free_throw_percentage' => 'Freiwurf-Quote',
        'rebounds' => 'Rebounds',
        'offensive_rebounds' => 'Offensiv-Rebounds',
        'defensive_rebounds' => 'Defensiv-Rebounds',
        'assists' => 'Assists',
        'steals' => 'Steals',
        'blocks' => 'Blocks',
        'turnovers' => 'Ballverluste',
        'fouls' => 'Fouls',
        'games_played' => 'Spiele',
        'minutes_played' => 'Spielminuten',
        'plus_minus' => 'Plus/Minus',
        'efficiency' => 'Effizienz',
    ],

    'quarters' => [
        '1' => '1. Viertel',
        '2' => '2. Viertel',
        '3' => '3. Viertel',
        '4' => '4. Viertel',
        'overtime' => 'Verlängerung',
    ],

    'gender' => [
        'male' => 'Männlich',
        'female' => 'Weiblich',
        'mixed' => 'Gemischt',
    ],

    'dominant_hand' => [
        'left' => 'Links',
        'right' => 'Rechts',
        'both' => 'Beidseitig',
    ],

    'emergency_relationships' => [
        'parent' => 'Elternteil',
        'mother' => 'Mutter',
        'father' => 'Vater',
        'guardian' => 'Vormund',
        'sibling' => 'Geschwister',
        'grandparent' => 'Großelternteil',
        'partner' => 'Partner',
        'friend' => 'Freund',
        'other' => 'Sonstige',
    ],

    'consent_types' => [
        'medical_consent' => 'Medizinische Einverständniserklärung',
        'photo_consent' => 'Bildrechte-Einverständnis',
        'data_processing_consent' => 'Datenschutz-Einverständnis',
    ],

    'common' => [
        'home' => 'Heim',
        'away' => 'Gast',
        'vs' => 'vs.',
        'halftime' => 'Halbzeit',
        'fulltime' => 'Endstand',
        'timeout' => 'Auszeit',
        'substitution' => 'Einwechslung',
        'captain' => 'Kapitän',
        'vice_captain' => 'Vizekapitän',
        'coach' => 'Trainer',
        'assistant_coach' => 'Co-Trainer',
        'referee' => 'Schiedsrichter',
        'scorer' => 'Statistiker',
        'venue' => 'Spielort',
        'season' => 'Saison',
        'league' => 'Liga',
        'division' => 'Staffel',
        'ranking' => 'Tabelle',
        'wins' => 'Siege',
        'losses' => 'Niederlagen',
        'ties' => 'Unentschieden',
    ],

];