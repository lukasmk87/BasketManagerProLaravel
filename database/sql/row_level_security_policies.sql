-- Row Level Security Policies for Multi-Tenant Architecture
-- These policies ensure complete data isolation between tenants

-- Enable Row Level Security for all tenant-aware tables
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE teams ENABLE ROW LEVEL SECURITY;
ALTER TABLE players ENABLE ROW LEVEL SECURITY;
ALTER TABLE games ENABLE ROW LEVEL SECURITY;
ALTER TABLE tournaments ENABLE ROW LEVEL SECURITY;
ALTER TABLE training_sessions ENABLE ROW LEVEL SECURITY;
ALTER TABLE clubs ENABLE ROW LEVEL SECURITY;
ALTER TABLE seasons ENABLE ROW LEVEL SECURITY;
ALTER TABLE game_actions ENABLE ROW LEVEL SECURITY;
ALTER TABLE game_statistics ENABLE ROW LEVEL SECURITY;
ALTER TABLE training_drills ENABLE ROW LEVEL SECURITY;
ALTER TABLE media ENABLE ROW LEVEL SECURITY;
ALTER TABLE emergency_contacts ENABLE ROW LEVEL SECURITY;

-- Create function to get current tenant ID
CREATE OR REPLACE FUNCTION current_tenant_id() RETURNS UUID AS $$
BEGIN
    RETURN COALESCE(
        NULLIF(current_setting('basketmanager.current_tenant_id', true), ''),
        '00000000-0000-0000-0000-000000000000'::UUID
    );
END;
$$ LANGUAGE plpgsql STABLE;

-- Users table policies
CREATE POLICY tenant_users_policy ON users
    USING (tenant_id = current_tenant_id());

CREATE POLICY tenant_users_insert_policy ON users
    FOR INSERT WITH CHECK (tenant_id = current_tenant_id());

-- Teams table policies
CREATE POLICY tenant_teams_policy ON teams
    USING (tenant_id = current_tenant_id());

CREATE POLICY tenant_teams_insert_policy ON teams
    FOR INSERT WITH CHECK (tenant_id = current_tenant_id());

-- Players table policies
CREATE POLICY tenant_players_policy ON players
    USING (tenant_id = current_tenant_id());

CREATE POLICY tenant_players_insert_policy ON players
    FOR INSERT WITH CHECK (tenant_id = current_tenant_id());

-- Games table policies
CREATE POLICY tenant_games_policy ON games
    USING (tenant_id = current_tenant_id());

CREATE POLICY tenant_games_insert_policy ON games
    FOR INSERT WITH CHECK (tenant_id = current_tenant_id());

-- Tournaments table policies
CREATE POLICY tenant_tournaments_policy ON tournaments
    USING (tenant_id = current_tenant_id());

CREATE POLICY tenant_tournaments_insert_policy ON tournaments
    FOR INSERT WITH CHECK (tenant_id = current_tenant_id());

-- Training sessions table policies
CREATE POLICY tenant_training_sessions_policy ON training_sessions
    USING (tenant_id = current_tenant_id());

CREATE POLICY tenant_training_sessions_insert_policy ON training_sessions
    FOR INSERT WITH CHECK (tenant_id = current_tenant_id());

-- Clubs table policies
CREATE POLICY tenant_clubs_policy ON clubs
    USING (tenant_id = current_tenant_id());

CREATE POLICY tenant_clubs_insert_policy ON clubs
    FOR INSERT WITH CHECK (tenant_id = current_tenant_id());

-- Seasons table policies
CREATE POLICY tenant_seasons_policy ON seasons
    USING (tenant_id = current_tenant_id());

CREATE POLICY tenant_seasons_insert_policy ON seasons
    FOR INSERT WITH CHECK (tenant_id = current_tenant_id());

-- Game actions table policies
CREATE POLICY tenant_game_actions_policy ON game_actions
    USING (tenant_id = current_tenant_id());

CREATE POLICY tenant_game_actions_insert_policy ON game_actions
    FOR INSERT WITH CHECK (tenant_id = current_tenant_id());

-- Game statistics table policies
CREATE POLICY tenant_game_statistics_policy ON game_statistics
    USING (tenant_id = current_tenant_id());

CREATE POLICY tenant_game_statistics_insert_policy ON game_statistics
    FOR INSERT WITH CHECK (tenant_id = current_tenant_id());

-- Training drills table policies
CREATE POLICY tenant_training_drills_policy ON training_drills
    USING (tenant_id = current_tenant_id());

CREATE POLICY tenant_training_drills_insert_policy ON training_drills
    FOR INSERT WITH CHECK (tenant_id = current_tenant_id());

-- Media table policies
CREATE POLICY tenant_media_policy ON media
    USING (tenant_id = current_tenant_id());

CREATE POLICY tenant_media_insert_policy ON media
    FOR INSERT WITH CHECK (tenant_id = current_tenant_id());

-- Emergency contacts table policies
CREATE POLICY tenant_emergency_contacts_policy ON emergency_contacts
    USING (tenant_id = current_tenant_id());

CREATE POLICY tenant_emergency_contacts_insert_policy ON emergency_contacts
    FOR INSERT WITH CHECK (tenant_id = current_tenant_id());

-- Create admin bypass policies for superusers
-- This allows system administrators to access all tenant data
CREATE POLICY admin_bypass_policy ON users
    FOR ALL TO basketmanager_admin
    USING (true);

CREATE POLICY admin_bypass_policy ON teams
    FOR ALL TO basketmanager_admin
    USING (true);

CREATE POLICY admin_bypass_policy ON players
    FOR ALL TO basketmanager_admin
    USING (true);

CREATE POLICY admin_bypass_policy ON games
    FOR ALL TO basketmanager_admin
    USING (true);

CREATE POLICY admin_bypass_policy ON tournaments
    FOR ALL TO basketmanager_admin
    USING (true);

CREATE POLICY admin_bypass_policy ON training_sessions
    FOR ALL TO basketmanager_admin
    USING (true);

CREATE POLICY admin_bypass_policy ON clubs
    FOR ALL TO basketmanager_admin
    USING (true);

CREATE POLICY admin_bypass_policy ON seasons
    FOR ALL TO basketmanager_admin
    USING (true);

CREATE POLICY admin_bypass_policy ON game_actions
    FOR ALL TO basketmanager_admin
    USING (true);

CREATE POLICY admin_bypass_policy ON game_statistics
    FOR ALL TO basketmanager_admin
    USING (true);

CREATE POLICY admin_bypass_policy ON training_drills
    FOR ALL TO basketmanager_admin
    USING (true);

CREATE POLICY admin_bypass_policy ON media
    FOR ALL TO basketmanager_admin
    USING (true);

CREATE POLICY admin_bypass_policy ON emergency_contacts
    FOR ALL TO basketmanager_admin
    USING (true);

-- Create database role for admin access
CREATE ROLE basketmanager_admin;

-- Grant necessary permissions to admin role
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO basketmanager_admin;
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO basketmanager_admin;

-- Create function to disable RLS for admin operations
CREATE OR REPLACE FUNCTION disable_rls_for_admin() RETURNS void AS $$
BEGIN
    IF current_user = 'basketmanager_admin' THEN
        SET row_security = off;
    END IF;
END;
$$ LANGUAGE plpgsql;

-- Create function to enable RLS
CREATE OR REPLACE FUNCTION enable_rls() RETURNS void AS $$
BEGIN
    SET row_security = on;
END;
$$ LANGUAGE plpgsql;

-- Create indexes on tenant_id for performance
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_users_tenant_id ON users(tenant_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_teams_tenant_id ON teams(tenant_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_players_tenant_id ON players(tenant_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_games_tenant_id ON games(tenant_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_tournaments_tenant_id ON tournaments(tenant_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_training_sessions_tenant_id ON training_sessions(tenant_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_clubs_tenant_id ON clubs(tenant_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_seasons_tenant_id ON seasons(tenant_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_game_actions_tenant_id ON game_actions(tenant_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_game_statistics_tenant_id ON game_statistics(tenant_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_training_drills_tenant_id ON training_drills(tenant_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_media_tenant_id ON media(tenant_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_emergency_contacts_tenant_id ON emergency_contacts(tenant_id);