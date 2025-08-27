<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Team Management - English
    |--------------------------------------------------------------------------
    |
    | Team-related translations for management and display.
    |
    */

    'title' => 'Teams',
    'team' => 'Team',
    'teams' => 'Teams',
    'my_teams' => 'My Teams',
    'all_teams' => 'All Teams',

    'actions' => [
        'create_team' => 'Create Team',
        'edit_team' => 'Edit Team',
        'delete_team' => 'Delete Team',
        'view_team' => 'View Team',
        'join_team' => 'Join Team',
        'leave_team' => 'Leave Team',
        'manage_roster' => 'Manage Roster',
        'add_player' => 'Add Player',
        'remove_player' => 'Remove Player',
    ],

    'fields' => [
        'name' => 'Team Name',
        'short_name' => 'Short Name',
        'category' => 'Category',
        'age_group' => 'Age Group',
        'gender' => 'Gender',
        'season' => 'Season',
        'league' => 'League',
        'division' => 'Division',
        'description' => 'Description',
        'home_venue' => 'Home Venue',
        'training_days' => 'Training Days',
        'training_time' => 'Training Time',
        'head_coach' => 'Head Coach',
        'assistant_coach' => 'Assistant Coach',
        'max_players' => 'Maximum Players',
        'current_players' => 'Current Players',
        'budget' => 'Budget',
        'status' => 'Status',
        'is_competitive' => 'Competitive Team',
        'accepts_new_players' => 'Accepts New Players',
        'team_colors' => 'Team Colors',
        'logo' => 'Logo',
        'contact_persons' => 'Contact Persons',
        'founded' => 'Founded',
        'club' => 'Club',
    ],

    'labels' => [
        'players_count' => 'Players',
        'games_count' => 'Games',
        'wins' => 'Wins',
        'losses' => 'Losses',
        'win_percentage' => 'Win Percentage',
        'points_scored' => 'Points Scored',
        'points_allowed' => 'Points Allowed',
        'average_age' => 'Average Age',
        'roster_full' => 'Roster Full',
        'slots_available' => 'Slots Available',
        'next_game' => 'Next Game',
        'last_game' => 'Last Game',
    ],

    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'disbanded' => 'Disbanded',
        'archived' => 'Archived',
    ],

    'messages' => [
        'created' => 'Team created successfully.',
        'updated' => 'Team updated successfully.',
        'deleted' => 'Team deleted successfully.',
        'player_added' => 'Player added successfully.',
        'player_removed' => 'Player removed successfully.',
        'roster_full' => 'The roster is already full.',
        'player_already_member' => 'Player is already a member of this team.',
        'cannot_delete_with_active_games' => 'Cannot delete team with active games.',
        'coach_assigned' => 'Coach assigned successfully.',
        'coach_removed' => 'Coach removed successfully.',
        'team_not_found' => 'Team not found.',
        'no_permission' => 'No permission for this action.',
    ],

    'validation' => [
        'name_required' => 'Team name is required.',
        'name_max' => 'Team name may not be greater than 255 characters.',
        'category_required' => 'Category is required.',
        'season_required' => 'Season is required.',
        'season_format' => 'Season must be in format YYYY-YY (e.g., 2024-25).',
        'max_players_min' => 'Maximum players must be at least 5.',
        'max_players_max' => 'Maximum players may not be greater than 20.',
        'unique_name_per_season' => 'A team with this name already exists in this season.',
        'coach_must_be_trainer' => 'Selected user must have the "trainer" role.',
        'budget_numeric' => 'Budget must be a valid number.',
    ],

    'filters' => [
        'all_categories' => 'All Categories',
        'all_seasons' => 'All Seasons',
        'all_statuses' => 'All Statuses',
        'competitive_only' => 'Competitive Only',
        'accepting_players' => 'Accepting New Players',
        'my_coached_teams' => 'My Coached Teams',
        'club_teams' => 'Club Teams',
    ],

    'tabs' => [
        'overview' => 'Overview',
        'roster' => 'Roster',
        'games' => 'Games',
        'statistics' => 'Statistics',
        'training' => 'Training',
        'settings' => 'Settings',
    ],

    'empty_states' => [
        'no_teams' => 'No teams available.',
        'no_players' => 'No players in roster.',
        'no_games' => 'No games scheduled.',
        'no_statistics' => 'No statistics available.',
        'create_first_team' => 'Create your first team.',
        'add_first_player' => 'Add the first player.',
    ],

    'permissions' => [
        'view_teams' => 'View Teams',
        'create_teams' => 'Create Teams',
        'edit_teams' => 'Edit Teams',
        'delete_teams' => 'Delete Teams',
        'manage_team_rosters' => 'Manage Team Rosters',
        'assign_coaches' => 'Assign Coaches',
        'view_team_statistics' => 'View Team Statistics',
    ],

];