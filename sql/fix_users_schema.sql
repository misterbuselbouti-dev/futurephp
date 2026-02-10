-- FUTURE AUTOMOTIVE - Fix Users Table Schema
-- إصلاح هيكل جدول users بطريقة آمنة

-- Use stored procedure to safely drop columns if they exist
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS drop_column_if_exists(
    IN table_name VARCHAR(64),
    IN column_name VARCHAR(64)
)
BEGIN
    DECLARE column_exists INT;
    
    -- Check if column exists
    SELECT COUNT(*) INTO column_exists 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = table_name 
      AND COLUMN_NAME = column_name;
    
    -- Drop column if it exists
    IF column_exists > 0 THEN
        SET @sql = CONCAT('ALTER TABLE ', table_name, ' DROP COLUMN ', column_name);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

-- Call the procedure to drop last_login column if it exists
CALL drop_column_if_exists('users', 'last_login');

-- Call the procedure to drop status column if it exists
CALL drop_column_if_exists('users', 'status');

-- Drop the procedure
DROP PROCEDURE IF EXISTS drop_column_if_exists;

-- Add is_active column if it doesn't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1;

-- Update existing records to have is_active = 1 (active)
UPDATE users SET is_active = 1 WHERE is_active IS NULL;

-- Add index for is_active
CREATE INDEX IF NOT EXISTS idx_is_active ON users (is_active);

-- Show results
SELECT 
    'Users table schema fixed successfully' as status,
    (SELECT COUNT(*) as count FROM users) as total_users,
    (SELECT COUNT(*) as count FROM users WHERE is_active = 1) as active_users,
    (SELECT COUNT(*) as count FROM users WHERE role = 'admin') as admin_users,
    'Schema updated - removed last_login and status, added is_active' as message;
