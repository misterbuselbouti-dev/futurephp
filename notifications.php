<?php
// FUTURE AUTOMOTIVE - Notifications Management Page with Sound Alerts
require_once 'config.php';
require_once 'includes/functions.php';

// Check authentication
require_login();

$currentUserId = (int)($_SESSION['user_id'] ?? 0);

// Handle marking notifications as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'mark_read') {
        $database = new Database();
        $pdo = $database->connect();
        
        if (isset($_POST['notification_id'])) {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND user_id = ?");
            $stmt->execute([$_POST['notification_id'], $currentUserId]);
        } elseif (isset($_POST['mark_all'])) {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE is_read = 0 AND user_id = ?");
            $stmt->execute([$currentUserId]);
        }
        
        echo json_encode(['success' => true]);
        exit;
    }
}

// Get notifications from database
try {
    $database = new Database();
    $pdo = $database->connect();

    // Detect legacy vs new notifications schema
    $notifCols = [];
    try {
        $notifCols = $pdo->query("SHOW COLUMNS FROM notifications")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $notifCols = [];
    }

    $hasLegacyRelatedId = in_array('related_id', $notifCols, true);
    $hasTitle = in_array('title', $notifCols, true);
    $hasMessage = in_array('message', $notifCols, true);
    $hasUserId = in_array('user_id', $notifCols, true);
    $hasEntityType = in_array('entity_type', $notifCols, true);
    $hasEntityId = in_array('entity_id', $notifCols, true);
    
    if ($hasLegacyRelatedId) {
        // Legacy query (older maintenance module)
        $stmt = $pdo->query("
            SELECT n.*, 
                   bi.bus_id, bi.issue_type_id, bi.custom_description, bi.priority,
                   b.bus_number, b.make, b.model,
                   d.name as driver_name, d.phone as driver_phone,
                   it.name as issue_type_name, it.category, it.priority as default_priority,
                   u.full_name as mechanic_name
            FROM notifications n
            LEFT JOIN bus_issues bi ON n.related_id = bi.id AND n.type IN ('bus_issue', 'bus_issue_assigned', 'bus_issue_status')
            LEFT JOIN buses b ON bi.bus_id = b.id
            LEFT JOIN drivers d ON bi.driver_id = d.id
            LEFT JOIN issue_types it ON bi.issue_type_id = it.id
            LEFT JOIN users u ON bi.mechanic_id = u.id
            ORDER BY n.created_at DESC
        ");
        $notifications = $stmt->fetchAll();
    } elseif ($hasUserId && $hasEntityType && $hasEntityId) {
        // New breakdown notifications schema
        $stmt = $pdo->prepare("
            SELECT 
                n.*,
                br.report_ref,
                br.category AS breakdown_category,
                br.urgency AS breakdown_urgency,
                br.kilometrage,
                br.message_text,
                br.created_at AS breakdown_created_at,
                b.bus_number,
                b.license_plate,
                CONCAT(d.prenom, ' ', d.nom) AS driver_name,
                d.phone AS driver_phone,
                pi.pan_code,
                pi.label_fr
            FROM notifications n
            LEFT JOIN breakdown_reports br ON n.entity_type = 'breakdown_report' AND n.entity_id = br.id
            LEFT JOIN buses b ON br.bus_id = b.id
            LEFT JOIN drivers d ON br.driver_id = d.id
            LEFT JOIN pan_issues pi ON br.pan_issue_id = pi.id
            WHERE n.user_id = ?
            ORDER BY n.created_at DESC
        ");
        $stmt->execute([$currentUserId]);
        $notifications = $stmt->fetchAll();
    } else {
        // Fallback
        $stmt = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC");
        $notifications = $stmt->fetchAll();
    }
    
    // Count unread notifications
    if ($hasUserId) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE is_read = 0 AND user_id = ?");
        $stmt->execute([$currentUserId]);
        $unread_count = (int)($stmt->fetch()['count'] ?? 0);
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM notifications WHERE is_read = 0");
        $unread_count = (int)($stmt->fetch()['count'] ?? 0);
    }
    
} catch (Exception $e) {
    $notifications = [];
    $unread_count = 0;
    $error_message = "Error loading notifications: " . $e->getMessage();
}

// Page title
$page_title = 'الإشعارات';
?>

<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s;
        }
        .sidebar-collapsed .main-content {
            margin-left: 70px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border: none;
        }
        .notification-item {
            border-right: 4px solid #007bff;
            margin-bottom: 10px;
            transition: all 0.3s;
        }
        .notification-item.unread {
            border-right-color: #dc3545;
            background-color: #fff5f5;
        }
        .notification-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="mb-2">
                        <i class="fas fa-bell me-3"></i>
                        الإشعارات
                        <?php if ($unread_count > 0): ?>
                        <span class="badge bg-danger ms-2 pulse"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </h1>
                    <p class="text-muted">إدارة الإشعارات والتنبيهات</p>
                </div>
            </div>
            
            <!-- Alert Messages -->
            <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        قائمة الإشعارات
                        <?php if ($unread_count > 0): ?>
                        <span class="badge bg-danger ms-2"><?php echo $unread_count; ?> غير مقروء</span>
                        <?php endif; ?>
                    </h5>
                    <div>
                        <?php if ($unread_count > 0): ?>
                        <button class="btn btn-sm btn-outline-primary" onclick="markAllRead()">
                            <i class="fas fa-check-double me-1"></i>
                            تعيين الكل كمقروء
                        </button>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-outline-secondary" onclick="refreshNotifications()">
                            <i class="fas fa-sync-alt me-1"></i>
                            تحديث
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($notifications)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-bell-slash fa-3x text-secondary mb-3"></i>
                        <p class="text-secondary">لا توجد إشعارات حالياً</p>
                    </div>
                    <?php else: ?>
                    <div class="notification-list">
                        <?php foreach ($notifications as $notification): ?>
                        <div class="card notification-item <?php echo $notification['is_read'] == 0 ? 'unread' : ''; ?>" 
                             data-notification-id="<?php echo $notification['id']; ?>">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="card-title mb-1">
                                            <?php 
                                            $icon = 'fa-info-circle';
                                            $color = 'primary';
                                            
                                            if ($notification['type'] === 'bus_issue') {
                                                $icon = 'fa-exclamation-triangle';
                                                $color = 'danger';
                                            } elseif ($notification['type'] === 'bus_issue_assigned') {
                                                $icon = 'fa-user-wrench';
                                                $color = 'warning';
                                            } elseif ($notification['type'] === 'bus_issue_status') {
                                                $icon = 'fa-check-circle';
                                                $color = 'success';
                                            } elseif ($notification['type'] === 'breakdown_new') {
                                                $icon = 'fa-triangle-exclamation';
                                                $color = 'danger';
                                            }
                                            ?>
                                            <i class="fas <?php echo $icon; ?> me-2 text-<?php echo $color; ?>"></i>
                                            <?php 
                                            // Enhanced title for bus issues
                                            if ($notification['type'] === 'bus_issue' && $notification['driver_name'] && $notification['bus_number']) {
                                                echo htmlspecialchars("عطل في حافلة {$notification['bus_number']} - السائق: {$notification['driver_name']}");
                                            } elseif ($notification['type'] === 'bus_issue_assigned' && $notification['mechanic_name']) {
                                                echo htmlspecialchars("تم تعيين {$notification['mechanic_name']} للعطل");
                                            } elseif ($notification['type'] === 'breakdown_new' && !empty($notification['report_ref'])) {
                                                $busLabel = !empty($notification['bus_number']) ? (' - Bus ' . $notification['bus_number']) : '';
                                                echo htmlspecialchars('بلاغ عطب جديد ' . $notification['report_ref'] . $busLabel);
                                            } else {
                                                echo htmlspecialchars($notification['title'] ?? $notification['type'] ?? 'Notification');
                                            }
                                            ?>
                                        </h6>
                                        
                                        <?php if (!empty($notification['bus_number']) && !empty($notification['driver_name'])): ?>
                                        <div class="mb-2">
                                            <span class="badge bg-light text-dark me-2">
                                                <i class="fas fa-bus me-1"></i>
                                                <?php echo htmlspecialchars($notification['bus_number']); ?>
                                            </span>
                                            <span class="badge bg-info text-white me-2">
                                                <i class="fas fa-user me-1"></i>
                                                <?php echo htmlspecialchars($notification['driver_name']); ?>
                                            </span>
                                            <?php if (!empty($notification['issue_type_name'])): ?>
                                            <span class="badge bg-warning text-dark me-2">
                                                <i class="fas fa-wrench me-1"></i>
                                                <?php echo htmlspecialchars($notification['issue_type_name']); ?>
                                            </span>
                                            <?php endif; ?>
                                            <?php if (!empty($notification['priority'])): ?>
                                            <?php 
                                            $priority_colors = [
                                                'low' => 'success',
                                                'medium' => 'warning', 
                                                'high' => 'danger',
                                                'critical' => 'dark'
                                            ];
                                            $priority_labels = [
                                                'low' => 'منخفض',
                                                'medium' => 'متوسط',
                                                'high' => 'عالي',
                                                'critical' => 'خطير'
                                            ];
                                            $color = $priority_colors[$notification['priority']] ?? 'secondary';
                                            $label = $priority_labels[$notification['priority']] ?? $notification['priority'];
                                            ?>
                                            <span class="badge bg-<?php echo $color; ?> text-white">
                                                <i class="fas fa-exclamation me-1"></i>
                                                <?php echo $label; ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <p class="card-text text-muted mb-2">
                                            <?php 
                                            // Enhanced message
                                            if (!empty($notification['custom_description'])) {
                                                echo htmlspecialchars($notification['custom_description']);
                                            } elseif (!empty($notification['message'])) {
                                                echo htmlspecialchars($notification['message']);
                                            } elseif ($notification['type'] === 'breakdown_new') {
                                                $parts = [];
                                                if (!empty($notification['breakdown_category'])) $parts[] = 'Type: ' . $notification['breakdown_category'];
                                                if (!empty($notification['breakdown_urgency'])) $parts[] = 'Urgence: ' . $notification['breakdown_urgency'];
                                                if (!empty($notification['kilometrage'])) $parts[] = 'KM: ' . $notification['kilometrage'];
                                                if (!empty($notification['pan_code'])) $parts[] = 'PAN: ' . $notification['pan_code'];
                                                echo htmlspecialchars(!empty($parts) ? implode(' | ', $parts) : 'Incident déclaré');
                                            } else {
                                                echo 'لا يوجد وصف متاح';
                                            }
                                            ?>
                                        </p>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?>
                                        </small>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <?php if ($notification['is_read'] == 0): ?>
                                        <span class="badge bg-danger me-2">جديد</span>
                                        <?php endif; ?>
                                        <?php if ($notification['type'] === 'bus_issue' && $notification['related_id']): ?>
                                        <a href="bus_work_orders.php" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-external-link-alt me-1"></i>
                                            عرض
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($notification['type'] === 'breakdown_new' && ($notification['entity_id'] ?? 0)): ?>
                                        <a href="admin_breakdown_view.php?id=<?php echo (int)$notification['entity_id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-external-link-alt me-1"></i>
                                            عرض
                                        </a>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-success ms-2" onclick="markAsRead(<?php echo $notification['id']; ?>)">
                                            <i class="fas fa-check me-1"></i>
                                            مقروء
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger ms-2" onclick="deleteNotification(<?php echo $notification['id']; ?>)">
                                            <i class="fas fa-trash me-1"></i>
                                            حذف
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Audio for notification sound -->
    <audio id="notificationSound" preload="auto">
        <source src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIG2m98OScTgwOUarm7blmGgU7k9n1unEiBC13yO/eizEIHWq+8+OWT" type="audio/wav">
    </audio>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="notification_checker.js"></script>
    <script>
        let notificationSound = document.getElementById('notificationSound');
        let unreadCount = <?php echo $unread_count; ?>;
        let soundInterval;
        
        // Play notification sound if there are unread notifications
        function playNotificationSound() {
            if (unreadCount > 0) {
                notificationSound.play().catch(e => console.log('Audio play failed:', e));
            }
        }
        
        // Initialize notification checker
        document.addEventListener('DOMContentLoaded', function() {
            if (unreadCount > 0) {
                playNotificationSound(); // Play immediately
                startSoundInterval(); // Start interval
            }
            
            // Update notification count every 30 seconds
            setInterval(() => {
                fetch('api/notifications/count.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.count > unreadCount) {
                            playNotificationSound();
                            unreadCount = data.count;
                        }
                    });
            }, 30000);
        });
        
        function markAsRead(notificationId) {
            console.log('Marking notification as read:', notificationId);
            
            fetch('api/notifications/mark_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    notification_id: notificationId
                })
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    // Remove unread styling
                    const notificationElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
                    if (notificationElement) {
                        notificationElement.classList.remove('unread');
                        const newBadge = notificationElement.querySelector('.badge.bg-danger');
                        if (newBadge) {
                            newBadge.remove();
                        }
                    }
                    
                    // Update unread count
                    updateUnreadCount(-1);
                    
                    // Show success message
                    showToast('تم تعيين الإشعار كمقروء', 'success');
                } else {
                    showToast('فشل تعيين الإشعار كمقروء: ' + (data.error || 'خطأ غير معروف'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('حدث خطأ أثناء تعيين الإشعار كمقروء', 'error');
            });
        }
        
        function markAllRead() {
            fetch('api/notifications/mark_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    mark_all: true
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove all unread styling
                    document.querySelectorAll('.notification-item.unread').forEach(item => {
                        item.classList.remove('unread');
                        const newBadge = item.querySelector('.badge.bg-danger');
                        if (newBadge) {
                            newBadge.remove();
                        }
                    });
                    
                    // Update unread count to 0
                    updateUnreadCount(-unreadCount);
                    
                    // Hide "mark all as read" button
                    const markAllBtn = document.querySelector('button[onclick="markAllRead()"]');
                    if (markAllBtn) {
                        markAllBtn.style.display = 'none';
                    }
                    
                    // Show success message
                    showToast(data.message || 'تم تعيين جميع الإشعارات كمقروء', 'success');
                } else {
                    showToast('فشل تعيين جميع الإشعارات كمقروء: ' + (data.error || 'خطأ غير معروف'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('حدث خطأ أثناء تعيين جميع الإشعارات كمقروء', 'error');
            });
        }
        
        function deleteNotification(notificationId) {
            if (confirm('هل أنت متأكد من حذف هذا الإشعار؟')) {
                fetch('api/notifications/delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        notification_id: notificationId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove notification element
                        const notificationElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
                        if (notificationElement) {
                            const wasUnread = notificationElement.classList.contains('unread');
                            notificationElement.remove();
                            
                            if (wasUnread) {
                                updateUnreadCount(-1);
                            }
                        }
                        
                        // Show success message
                        showToast('تم حذف الإشعار بنجاح', 'success');
                        
                        // Reload page if no notifications left
                        if (document.querySelectorAll('.notification-item').length === 0) {
                            setTimeout(() => location.reload(), 1500);
                        }
                    } else {
                        showToast('فشل حذف الإشعار: ' + (data.error || 'خطأ غير معروف'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('حدث خطأ أثناء حذف الإشعار', 'error');
                });
            }
        }
        
        function updateUnreadCount(change) {
            unreadCount += change;
            const countElements = document.querySelectorAll('#notificationCount, .badge:contains("غير مقروء")');
            countElements.forEach(element => {
                if (element.id === 'notificationCount') {
                    element.textContent = unreadCount;
                    element.style.display = unreadCount > 0 ? 'inline-block' : 'none';
                }
            });
        }
        
        function showToast(message, type = 'info') {
            // Create toast element
            const toastHtml = `
                <div class="toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'primary'} border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            // Add toast to container
            let toastContainer = document.getElementById('toastContainer');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.id = 'toastContainer';
                toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                document.body.appendChild(toastContainer);
            }
            
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            
            // Initialize and show toast
            const toastElement = toastContainer.lastElementChild;
            const toast = new bootstrap.Toast(toastElement);
            toast.show();
            
            // Remove toast after hidden
            toastElement.addEventListener('hidden.bs.toast', () => {
                toastElement.remove();
            });
        }
    </script>
    
    <style>
        .notification-item {
            border-left: 4px solid #007bff;
            margin-bottom: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 0.375rem;
            transition: all 0.3s ease;
        }
        
        .notification-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        
        .notification-item.unread {
            border-left-color: #007bff;
            background: #e7f3ff;
        }
        
        .notification-item.warning {
            border-left-color: #ffc107;
            background: #fff8e1;
        }
        
        .notification-item.danger {
            border-left-color: #dc3545;
            background: #ffebee;
        }
        
        .notification-item.success {
            border-left-color: #28a745;
            background: #e8f5e8;
        }
        
        .notification-meta {
            font-size: 0.875rem;
            color: #6c757d;
        }
        
        .notification-actions {
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .notification-item:hover .notification-actions {
            opacity: 1;
        }
    </style>
</body>
</html>
