<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Team Management - German
    |--------------------------------------------------------------------------
    |
    | Team-related translations for management and display.
    |
    */

    'title' => 'Teams',
    'team' => 'Team',
    'teams' => 'Teams',
    'my_teams' => 'Meine Teams',
    'all_teams' => 'Alle Teams',

    'actions' => [
        'create_team' => 'Team erstellen',
        'edit_team' => 'Team bearbeiten',
        'delete_team' => 'Team löschen',
        'view_team' => 'Team anzeigen',
        'join_team' => 'Team beitreten',
        'leave_team' => 'Team verlassen',
        'manage_roster' => 'Kader verwalten',
        'add_player' => 'Spieler hinzufügen',
        'remove_player' => 'Spieler entfernen',
    ],

    'fields' => [
        'name' => 'Teamname',
        'short_name' => 'Kurzname',
        'category' => 'Kategorie',
        'age_group' => 'Altersgruppe',
        'gender' => 'Geschlecht',
        'season' => 'Saison',
        'league' => 'Liga',
        'division' => 'Staffel',
        'description' => 'Beschreibung',
        'home_venue' => 'Heimspielort',
        'training_days' => 'Trainingstage',
        'training_time' => 'Trainingszeit',
        'head_coach' => 'Cheftrainer',
        'assistant_coach' => 'Co-Trainer',
        'max_players' => 'Maximale Spieleranzahl',
        'current_players' => 'Aktuelle Spieleranzahl',
        'budget' => 'Budget',
        'status' => 'Status',
        'is_competitive' => 'Wettkampfmannschaft',
        'accepts_new_players' => 'Nimmt neue Spieler auf',
        'team_colors' => 'Teamfarben',
        'logo' => 'Logo',
        'contact_persons' => 'Ansprechpartner',
        'founded' => 'Gegründet',
        'club' => 'Verein',
    ],

    'labels' => [
        'players_count' => 'Spieler',
        'games_count' => 'Spiele',
        'wins' => 'Siege',
        'losses' => 'Niederlagen',
        'win_percentage' => 'Siegquote',
        'points_scored' => 'Erzielte Punkte',
        'points_allowed' => 'Zugelassene Punkte',
        'average_age' => 'Durchschnittsalter',
        'roster_full' => 'Kader voll',
        'slots_available' => 'Freie Plätze',
        'next_game' => 'Nächstes Spiel',
        'last_game' => 'Letztes Spiel',
    ],

    'status' => [
        'active' => 'Aktiv',
        'inactive' => 'Inaktiv',
        'disbanded' => 'Aufgelöst',
        'archived' => 'Archiviert',
    ],

    'messages' => [
        'created' => 'Team erfolgreich erstellt.',
        'updated' => 'Team erfolgreich aktualisiert.',
        'deleted' => 'Team erfolgreich gelöscht.',
        'player_added' => 'Spieler erfolgreich hinzugefügt.',
        'player_removed' => 'Spieler erfolgreich entfernt.',
        'roster_full' => 'Der Kader ist bereits voll.',
        'player_already_member' => 'Spieler ist bereits Mitglied dieses Teams.',
        'cannot_delete_with_active_games' => 'Team kann nicht gelöscht werden, da noch aktive Spiele vorhanden sind.',
        'coach_assigned' => 'Trainer erfolgreich zugewiesen.',
        'coach_removed' => 'Trainer erfolgreich entfernt.',
        'team_not_found' => 'Team nicht gefunden.',
        'no_permission' => 'Keine Berechtigung für diese Aktion.',
    ],

    'validation' => [
        'name_required' => 'Teamname ist erforderlich.',
        'name_max' => 'Teamname darf maximal 255 Zeichen lang sein.',
        'category_required' => 'Kategorie ist erforderlich.',
        'season_required' => 'Saison ist erforderlich.',
        'season_format' => 'Saison muss im Format YYYY-YY angegeben werden (z.B. 2024-25).',
        'max_players_min' => 'Maximale Spieleranzahl muss mindestens 5 betragen.',
        'max_players_max' => 'Maximale Spieleranzahl darf höchstens 20 betragen.',
        'unique_name_per_season' => 'Ein Team mit diesem Namen existiert bereits in dieser Saison.',
        'coach_must_be_trainer' => 'Der ausgewählte Benutzer muss die Rolle "Trainer" haben.',
        'budget_numeric' => 'Budget muss eine gültige Zahl sein.',
    ],

    'filters' => [
        'all_categories' => 'Alle Kategorien',
        'all_seasons' => 'Alle Saisons',
        'all_statuses' => 'Alle Status',
        'competitive_only' => 'Nur Wettkampfmannschaften',
        'accepting_players' => 'Nimmt neue Spieler auf',
        'my_coached_teams' => 'Meine betreuten Teams',
        'club_teams' => 'Vereinsteams',
    ],

    'tabs' => [
        'overview' => 'Übersicht',
        'roster' => 'Kader',
        'games' => 'Spiele',
        'statistics' => 'Statistiken',
        'training' => 'Training',
        'settings' => 'Einstellungen',
    ],

    'empty_states' => [
        'no_teams' => 'Keine Teams vorhanden.',
        'no_players' => 'Keine Spieler im Kader.',
        'no_games' => 'Keine Spiele geplant.',
        'no_statistics' => 'Keine Statistiken verfügbar.',
        'create_first_team' => 'Erstellen Sie Ihr erstes Team.',
        'add_first_player' => 'Fügen Sie den ersten Spieler hinzu.',
    ],

    'permissions' => [
        'view_teams' => 'Teams anzeigen',
        'create_teams' => 'Teams erstellen',
        'edit_teams' => 'Teams bearbeiten',
        'delete_teams' => 'Teams löschen',
        'manage_team_rosters' => 'Teamkader verwalten',
        'assign_coaches' => 'Trainer zuweisen',
        'view_team_statistics' => 'Teamstatistiken anzeigen',
    ],

];