-- FUTURE AUTOMOTIVE - Create breakdown_assignments table
-- إنشاء جدول breakdown_assignments

-- Create breakdown_assignments table
CREATE TABLE IF NOT EXISTS breakdown_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    breakdown_id INT NOT NULL,
    assigned_to INT NOT NULL,
    assigned_by INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'assigned',
    notes TEXT DEFAULT NULL,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    completion_notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (breakdown_id) REFERENCES breakdown_reports(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_breakdown_id (breakdown_id),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_assigned_by (assigned_by),
    INDEX idx_status (status),
    INDEX idx_assigned_at (assigned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Show table structure
DESCRIBE breakdown_assignments;

-- Add sample data
INSERT INTO breakdown_assignments (
    breakdown_id, assigned_to, assigned_by, status, notes
) VALUES 
(1, 1, 1, 'assigned', 'Initial assignment'),
(2, 1, 1, 'in_progress', 'Work started'),
(3, 2, 1, 'completed', 'Issue resolved');

-- Show sample data
SELECT * FROM breakdown_assignments;

-- Test the problematic query
SELECT 'Testing breakdown_assignments query:' as status;

SELECT ba.*, br.report_ref, br.description AS breakdown_description,
       u1.username AS assigned_to_name, u2.username AS assigned_by_name
FROM breakdown_assignments ba
LEFT JOIN breakdown_reports br ON ba.breakdown_id = br.id
LEFT JOIN users u1 ON ba.assigned_to = u1.id
LEFT JOIN users u2 ON ba.assigned_by = u2.id
ORDER BY ba.assigned_at DESC;
