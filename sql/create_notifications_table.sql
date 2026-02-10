-- Create notifications table if it doesn't exist
CREATE TABLE IF NOT EXISTS `notifications` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    action_url VARCHAR(255),
    action_text VARCHAR(255),
    icon VARCHAR(50)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_notifications_user_unread ON notifications(user_id, is_read);
CREATE INDEX IF NOT EXISTS idx_notifications_entity ON notifications(entity_type, entity_id);
CREATE INDEX IF NOT EXISTS idx_notifications_created_at ON notifications(created_at);
CREATE INDEX IF NOT EXISTS idx_notifications_priority ON notifications(priority);

-- Insert sample notification data if table is empty
INSERT IGNORE INTO notifications (user_id, entity_type, entity_id, title, message, priority, icon) VALUES
(1, 'work_order', 1, 'أمر عمل جديد', 'تم إنشاء أمر عمل جديد', 'medium', 'fas fa-wrench'),
(1, 'work_order', 2, 'أمر عمل منتهي', 'تم إنهاء أمر العمل بنجاح', 'high', 'fas fa-check-circle'),
(1, 'system', 1, 'صيانة النظام', 'تم تحديث النظام بنجاح', 'low', 'fas fa-cog'),
(1, 'audit', 1, 'تدقيق النظام', 'تم إجراء تدقيق على النظام', 'medium', 'fas fa-shield-alt'),
(1, 'backup', 1, 'نسخة احتياطي', 'تم إنشاء نسخة احتياطي بنجاح', 'high', 'fas fa-save');
