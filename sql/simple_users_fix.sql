-- FUTURE AUTOMOTIVE - Simple Users Table Fix
-- إصلاح بسيط وهيكل لجدول users

-- Method 1: Try to drop columns safely (will fail if columns don't exist, but that's OK)
ALTER TABLE users DROP COLUMN IF EXISTS last_login;
ALTER TABLE users DROP COLUMN IF EXISTS status;

-- Method 2: Add is_active column if it doesn't exist
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1;

-- Update existing records to be active
UPDATE users SET is_active = 1 WHERE is_active IS NULL;

-- Add index for performance
CREATE INDEX IF NOT EXISTS idx_is_active ON users (is_active);

-- Show current table structure
SHOW COLUMNS FROM users;

-- Show results
SELECT 
    'Users table updated' as status,
    (SELECT COUNT(*) as count FROM users) as total_users,
    (SELECT COUNT(*) as count FROM users WHERE is_active = 1) as active_users,
    (SELECT COUNT(*) as count FROM users WHERE role = 'admin') as admin_users;
