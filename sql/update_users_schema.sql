-- FUTURE AUTOMOTIVE - Update Users Table Schema
-- تحديث هيكل جدول users لإزالة الأعمدة غير المطلوبة وإضافة الأعمدة المطلوبة

-- Check if last_login column exists, remove it if it does
SET @dbname = DATABASE();
SET @tablename = 'users';
SET @columnname = 'last_login';

-- Drop the column if it exists
SET @sql = CONCAT('ALTER TABLE ', @tablename, ' DROP COLUMN IF EXISTS ', @columnname);
SET @sql = REPLACE(@sql, 'DROP COLUMN IF EXISTS ', 'DROP COLUMN ');
SET @sql = REPLACE(@sql, 'DROP COLUMN IF EXISTS ', 'DROP COLUMN ');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if status column exists, remove it if it does (we use is_active instead)
SET @columnname = 'status';
SET @sql = CONCAT('ALTER TABLE ', @tablename, ' DROP COLUMN IF EXISTS ', @columnname);
SET @sql = REPLACE(@sql, 'DROP COLUMN IF EXISTS ', 'DROP COLUMN ');
SET @sql = REPLACE(@sql, 'DROP COLUMN IF EXISTS ', 'DROP COLUMN ');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if is_active column exists, add it if it doesn't
SET @columnname = 'is_active';
SET @sql = CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN IF NOT EXISTS ', @columnname, ' TINYINT(1) DEFAULT 1');
SET @sql = REPLACE(@sql, 'ADD COLUMN IF NOT EXISTS ', 'ADD COLUMN ');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for is_active if it doesn't exist
SET @indexname = 'idx_is_active';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (table_schema = @dbname)
      AND (table_name = @tablename)
      AND (index_name = @indexname)
  ) > 0,
  'SELECT 1',
  CONCAT('CREATE INDEX ', @indexname, ' ON ', @tablename, ' (', @columnname, ')')
));

PREPARSE addIndexIfNotExists FROM @preparedStatement;
EXECUTE addIndexIfNotExists;
DEALLOCATE PREPARE addIndexIfNotExists;

-- Update existing records to have is_active = 1 (active)
UPDATE users SET is_active = 1 WHERE is_active IS NULL OR is_active = 0;

-- Show results
SELECT 
    'Users table schema updated successfully' as status,
    (SELECT COUNT(*) as count FROM users) as total_users,
    (SELECT COUNT(*) as count FROM users WHERE is_active = 1) as active_users,
    (SELECT COUNT(*) as count FROM users WHERE role = 'admin') as admin_users,
    'Removed last_login and status columns, added is_active column' as message;
