-- Debug script to inspect GADA hall configuration
-- Run this to understand the current state

SELECT 
    'GADA Hall Configuration' as info;

SELECT 
    id,
    name,
    supports_parallel_bookings as global_parallel_bookings,
    hall_type,
    court_count,
    JSON_PRETTY(operating_hours) as operating_hours_formatted
FROM gym_halls 
WHERE name = 'GADA';

-- Check if there are any day-specific parallel booking settings
SELECT 
    'Day-specific parallel booking settings:' as info;

SELECT 
    name,
    JSON_EXTRACT(operating_hours, '$.monday.supports_parallel_bookings') as monday,
    JSON_EXTRACT(operating_hours, '$.tuesday.supports_parallel_bookings') as tuesday,
    JSON_EXTRACT(operating_hours, '$.wednesday.supports_parallel_bookings') as wednesday,
    JSON_EXTRACT(operating_hours, '$.thursday.supports_parallel_bookings') as thursday,
    JSON_EXTRACT(operating_hours, '$.friday.supports_parallel_bookings') as friday,
    JSON_EXTRACT(operating_hours, '$.saturday.supports_parallel_bookings') as saturday,
    JSON_EXTRACT(operating_hours, '$.sunday.supports_parallel_bookings') as sunday
FROM gym_halls 
WHERE name = 'GADA';

-- Check existing team assignments for Tuesday
SELECT 
    'Current Tuesday assignments:' as info;

SELECT 
    tsa.id,
    tsa.day_of_week,
    tsa.start_time,
    tsa.end_time,
    t.name as team_name,
    ts.id as time_slot_id,
    gh.name as hall_name
FROM gym_time_slot_team_assignments tsa
JOIN teams t ON tsa.team_id = t.id
JOIN gym_time_slots ts ON tsa.gym_time_slot_id = ts.id
JOIN gym_halls gh ON ts.gym_hall_id = gh.id
WHERE gh.name = 'GADA' 
    AND tsa.day_of_week = 'tuesday'
    AND tsa.status = 'active';