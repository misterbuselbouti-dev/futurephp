-- Add taux_horaire column to drivers table if it doesn't exist
-- FUTURE AUTOMOTIVE - Add taux_horaire column

ALTER TABLE drivers 
ADD COLUMN taux_horaire DECIMAL(10,2) DEFAULT 15.48 
COMMENT 'Taux horaire en MAD';

-- Update existing drivers with default taux_horaire
UPDATE drivers SET taux_horaire = 15.48 WHERE taux_horaire IS NULL;

-- Show result
SELECT 
    'taux_horaire column added successfully' as status,
    COUNT(*) as total_drivers,
    COUNT(CASE WHEN taux_horaire IS NOT NULL THEN 1 END) as drivers_with_taux,
    AVG(taux_horaire) as avg_taux_horaire
FROM drivers;
