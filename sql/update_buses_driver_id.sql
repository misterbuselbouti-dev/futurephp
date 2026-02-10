-- FUTURE AUTOMOTIVE - Update Buses Table with driver_id Column
-- إضافة عمود driver_id لجدول buses مع العلاقات المناسبة

-- Check if driver_id column exists, add it if it doesn't
SET @dbname = DATABASE();
SET @tablename = 'buses';
SET @columnname = 'driver_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_schema = @dbname)
      AND (table_name = @tablename)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' INT AFTER status')
));

PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add foreign key constraint if it doesn't exist
SET @dbname = DATABASE();
SET @tablename = 'buses';
SET @constraintname = 'buses_ibfk_1';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE
      (constraint_schema = @dbname)
      AND (table_name = @tablename)
      AND (constraint_name = @constraintname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD CONSTRAINT ', @constraintname, ' FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL')
));

PREPARE addForeignKeyIfNotExists FROM @preparedStatement;
EXECUTE addForeignKeyIfNotExists;
DEALLOCATE PREPARE addForeignKeyIfNotExists;

-- Add index for driver_id if it doesn't exist
SET @dbname = DATABASE();
SET @tablename = 'buses';
SET @indexname = 'idx_driver_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (table_schema = @dbname)
      AND (table_name = @tablename)
      AND (index_name = @indexname)
  ) > 0,
  'SELECT 1',
  CONCAT('CREATE INDEX ', @indexname, ' ON ', @tablename, ' (driver_id)')
));

PREPARE addIndexIfNotExists FROM @preparedStatement;
EXECUTE addIndexIfNotExists;
DEALLOCATE PREPARE addIndexIfNotExists;

-- Update existing buses records to have no driver assigned initially
UPDATE buses SET driver_id = NULL WHERE driver_id IS NULL;

-- Show results
SELECT 
    'Buses table updated successfully' as status,
    (SELECT COUNT(*) as count FROM buses) as total_buses,
    (SELECT COUNT(*) as count FROM buses WHERE driver_id IS NOT NULL) as buses_with_drivers,
    'driver_id column and foreign key added successfully' as message;
