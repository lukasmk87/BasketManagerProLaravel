-- Fix for operating_hours parallel booking configuration
-- This will identify and fix halls with incorrect per-day parallel booking settings

-- First, let's see the current state of operating_hours for all halls
SELECT 
    id, 
    name, 
    supports_parallel_bookings as global_parallel,
    JSON_EXTRACT(operating_hours, '$.tuesday.supports_parallel_bookings') as tuesday_parallel,
    operating_hours
FROM gym_halls 
WHERE operating_hours IS NOT NULL;

-- Update GADA hall to fix the Tuesday parallel bookings issue
-- This removes the day-specific parallel booking restriction
UPDATE gym_halls 
SET operating_hours = JSON_REMOVE(
    COALESCE(operating_hours, '{}'),
    '$.tuesday.supports_parallel_bookings'
)
WHERE name = 'GADA';

-- Also ensure the global setting is enabled
UPDATE gym_halls 
SET supports_parallel_bookings = 1 
WHERE name = 'GADA';

-- Verify the fix
SELECT 
    id, 
    name, 
    supports_parallel_bookings as global_parallel,
    JSON_EXTRACT(operating_hours, '$.tuesday.supports_parallel_bookings') as tuesday_parallel_after,
    operating_hours as operating_hours_after
FROM gym_halls 
WHERE name = 'GADA';