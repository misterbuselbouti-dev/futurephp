-- FUTURE AUTOMOTIVE - Fix bus_id column in drivers table
-- إصلاح عمود bus_id في جدول drivers

-- Check if bus_id column exists, add it if it doesn't
SET @dbname = DATABASE();
SET @tablename = 'drivers';
SET @columnname = 'bus_id';

-- Check if column exists
SELECT COUNT(*) INTO @column_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @dbname 
  AND TABLE_NAME = @tablename 
  AND COLUMN_NAME = @columnname;

-- Add column if it doesn't exist
SET @sql = IF(@column_exists > 0, 
    'SELECT ''bus_id column already exists'' as message',
    'ALTER TABLE drivers ADD COLUMN bus_id INT AFTER pin_code'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key constraint if buses table exists
SET @buses_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'buses');

SET @sql = IF(@buses_exists > 0,
    'ALTER TABLE drivers ADD CONSTRAINT fk_drivers_buses FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE SET NULL',
    'SELECT ''buses table does not exist, skipping foreign key'' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Show result
SELECT 'Column check completed' as status;

-- Show table structure
DESCRIBE drivers;

-- Test the query that was failing
SELECT 'Testing the problematic query:' as status;

SELECT d.*, b.bus_number, b.make, b.model
FROM drivers d 
LEFT JOIN buses b ON d.bus_id = b.id 
ORDER BY d.id
LIMIT 3;
