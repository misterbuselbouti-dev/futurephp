-- FUTURE AUTOMOTIVE - Enhanced Breakdown Management System
-- SQL Schema for Advanced Work Tracking, Inventory Integration, and Time Management

-- جدول ربط العطل بالمنتجات المستخدمة من المخزون
CREATE TABLE IF NOT EXISTS breakdown_work_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    breakdown_id INT NOT NULL,
    assignment_id INT NOT NULL,
    article_id INT NOT NULL,
    quantity_used DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit_cost DECIMAL(10,2) NULL,
    total_cost DECIMAL(10,2) NULL,
    notes TEXT,
    added_by_user_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (breakdown_id) REFERENCES breakdown_reports(id) ON DELETE CASCADE,
    FOREIGN KEY (assignment_id) REFERENCES breakdown_assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (added_by_user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_breakdown_id (breakdown_id),
    INDEX idx_assignment_id (assignment_id),
    INDEX idx_article_id (article_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول تتبع الوقت التفصيلي للعمل
CREATE TABLE IF NOT EXISTS breakdown_time_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    breakdown_id INT NOT NULL,
    assignment_id INT NOT NULL,
    user_id INT NOT NULL,
    action_type ENUM('start', 'pause', 'resume', 'end') NOT NULL,
    action_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    created_by_user_id INT NOT NULL,
    
    FOREIGN KEY (breakdown_id) REFERENCES breakdown_reports(id) ON DELETE CASCADE,
    FOREIGN KEY (assignment_id) REFERENCES breakdown_assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_breakdown_id (breakdown_id),
    INDEX idx_assignment_id (assignment_id),
    INDEX idx_user_id (user_id),
    INDEX idx_action_time (action_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول سجل التدقيق والتغييرات
CREATE TABLE IF NOT EXISTS breakdown_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    breakdown_id INT NOT NULL,
    assignment_id INT NULL,
    action_type VARCHAR(50) NOT NULL,
    field_name VARCHAR(100) NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    performed_by_user_id INT NOT NULL,
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    
    FOREIGN KEY (breakdown_id) REFERENCES breakdown_reports(id) ON DELETE CASCADE,
    FOREIGN KEY (assignment_id) REFERENCES breakdown_assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by_user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_breakdown_id (breakdown_id),
    INDEX idx_action_type (action_type),
    INDEX idx_performed_at (performed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- تحديث جدول breakdown_assignments لإضافة حقول جديدة
ALTER TABLE breakdown_assignments 
ADD COLUMN IF NOT EXISTS total_cost DECIMAL(10,2) NULL AFTER notes,
ADD COLUMN IF NOT EXISTS estimated_hours DECIMAL(5,2) NULL AFTER total_cost,
ADD COLUMN IF NOT EXISTS actual_hours DECIMAL(5,2) NULL AFTER estimated_hours,
ADD COLUMN IF NOT EXISTS work_status ENUM('pending', 'in_progress', 'paused', 'completed', 'cancelled') DEFAULT 'pending' AFTER actual_hours;

-- إضافة فهارس للبحث السريع
CREATE INDEX IF NOT EXISTS idx_breakdown_reports_status ON breakdown_reports(status);
CREATE INDEX IF NOT EXISTS idx_breakdown_reports_created_at ON breakdown_reports(created_at);
CREATE INDEX IF NOT EXISTS idx_breakdown_reports_bus_id ON breakdown_reports(bus_id);
CREATE INDEX IF NOT EXISTS idx_breakdown_assignments_user ON breakdown_assignments(assigned_to_user_id);

-- جدول حالة مخزون مؤقت للعرض السريع
CREATE TABLE IF NOT EXISTS breakdown_inventory_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    breakdown_id INT NOT NULL,
    article_id INT NOT NULL,
    stock_available INT NOT NULL DEFAULT 0,
    last_checked TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (breakdown_id) REFERENCES breakdown_reports(id) ON DELETE CASCADE,
    UNIQUE KEY unique_breakdown_article (breakdown_id, article_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إضافة تعليقات توضيحية للجداول
ALTER TABLE breakdown_work_items COMMENT = 'جدول ربط العطل بالمنتجات المستخدمة من المخزون';
ALTER TABLE breakdown_time_logs COMMENT = 'جدول تتبع الوقت التفصيلي للعمل';
ALTER TABLE breakdown_audit_log COMMENT = 'جدول سجل التدقيق والتغييرات';

-- إنشاء إجراء مخزن لحساب المدة التلقائي
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS CalculateWorkDuration(IN p_breakdown_id INT, IN p_assignment_id INT)
BEGIN
    DECLARE v_start_time TIMESTAMP;
    DECLARE v_end_time TIMESTAMP;
    DECLARE v_total_hours DECIMAL(10,2);
    
    -- Get first start time
    SELECT action_time INTO v_start_time 
    FROM breakdown_time_logs 
    WHERE breakdown_id = p_breakdown_id 
    AND assignment_id = p_assignment_id 
    AND action_type = 'start' 
    ORDER BY action_time ASC 
    LIMIT 1;
    
    -- Get last end time
    SELECT action_time INTO v_end_time 
    FROM breakdown_time_logs 
    WHERE breakdown_id = p_breakdown_id 
    AND assignment_id = p_assignment_id 
    AND action_type = 'end' 
    ORDER BY action_time DESC 
    LIMIT 1;
    
    -- Calculate duration
    IF v_start_time IS NOT NULL AND v_end_time IS NOT NULL THEN
        SET v_total_hours = TIMESTAMPDIFF(MINUTE, v_start_time, v_end_time) / 60;
        
        -- Update assignment
        UPDATE breakdown_assignments 
        SET actual_hours = v_total_hours 
        WHERE id = p_assignment_id;
    END IF;
END //
DELIMITER ;

-- بيانات تجريبية للاختبار (اختياري - يمكن إزالتها في الإنتاج)
-- INSERT INTO breakdown_work_items (breakdown_id, assignment_id, article_id, quantity_used, added_by_user_id) 
-- VALUES (1, 1, 1, 2, 1);

SELECT '✅ تم إنشاء جميع الجداول والهياكل الجديدة بنجاح' AS result;
