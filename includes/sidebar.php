<?php
// FUTURE AUTOMOTIVE - Sidebar Component
// Main navigation sidebar
?>

<!-- Sidebar -->
<aside class="sidebar <?php echo DIR === 'rtl' ? 'sidebar-rtl' : ''; ?>">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <i class="fas fa-car"></i>
            <span><?php echo APP_NAME; ?></span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav flex-column sidebar-menu">
            <?php
            $sbUser = function_exists('get_logged_in_user') ? get_logged_in_user() : null;
            $sbRole = $sbUser['role'] ?? '';
            ?>
            <!-- Bus Management -->
            <li class="nav-item">
                <a class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['buses_complete.php', 'buses_edit.php']) ? 'active' : ''; ?>" 
                   href="buses_complete.php">
                    <i class="fas fa-bus"></i>
                    <span>Bus Management</span>
                </a>
            </li>

            <?php if (in_array($sbRole, ['admin', 'maintenance_manager', 'technician'], true)): ?>
            <!-- Maintenance -->
            <li class="nav-item">
                <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#maintenanceCollapse">
                    <i class="fas fa-screwdriver-wrench"></i>
                    <span>Maintenance</span>
                    <i class="fas fa-chevron-down ms-auto"></i>
                </a>
                <ul class="nav collapse" id="maintenanceCollapse">
                    <li class="nav-item">
                        <a class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['admin_breakdowns.php','admin_breakdown_view.php'], true) ? 'active' : ''; ?>" 
                               href="admin_breakdowns.php">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>Incidents</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['admin_breakdowns_workshop.php', 'work_order_view.php', 'work_order_edit.php']) || 
                                   in_array(str_replace('admin/', '', basename($_SERVER['PHP_SELF'])), ['admin_breakdowns_workshop.php', 'work_order_view.php', 'work_order_edit.php']) ? 'active' : ''; ?>" 
                               href="admin/admin_breakdowns_workshop.php">
                            <i class="fas fa-wrench"></i>
                            <span>Gestion Atelier</span>
                        </a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>
            
            <!-- Drivers -->
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'drivers.php' ? 'active' : ''; ?>" 
                   href="drivers.php">
                    <i class="fas fa-id-card"></i>
                    <span>Drivers</span>
                </a>
            </li>

            <!-- Inventory -->
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'articles_stockables.php' ? 'active' : ''; ?>" 
                   href="articles_stockables.php">
                    <i class="fas fa-boxes"></i>
                    <span>Inventory</span>
                </a>
            </li>
            
            <!-- Stock Tétouan -->
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'stock_tetouan.php' ? 'active' : ''; ?>" 
                   href="stock_tetouan.php">
                    <i class="fas fa-warehouse"></i>
                    <span>Stock Tétouan</span>
                </a>
            </li>
            
            <!-- Stock Ksar-Larache -->
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'stock_ksar.php' ? 'active' : ''; ?>" 
                   href="stock_ksar.php">
                    <i class="fas fa-warehouse"></i>
                    <span>Stock Ksar-Larache</span>
                </a>
            </li>

            <!-- Export Data -->
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'export_data.php' ? 'active' : ''; ?>" 
                   href="export_data.php">
                    <i class="fas fa-download"></i>
                    <span>Export Data</span>
                </a>
            </li>

            <?php if ($sbRole === 'admin'): ?>
            <!-- Users -->
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'users_management.php' ? 'active' : ''; ?>" 
                   href="users_management.php">
                    <i class="fas fa-users-cog"></i>
                    <span>Users</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- Notifications -->
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'active' : ''; ?>" 
                   href="notifications.php">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                </a>
            </li>

            <!-- Divider -->
            <li class="nav-divider"></li>
            
            <!-- Gestion Achat -->
            <li class="nav-item">
                <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#achatCollapse">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Gestion Achat</span>
                    <i class="fas fa-chevron-down ms-auto"></i>
                </a>
                <ul class="nav collapse" id="achatCollapse">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'fournisseurs.php' ? 'active' : ''; ?>" 
                               href="fournisseurs.php">
                            <i class="fas fa-truck"></i>
                            <span>Fournisseurs</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'achat_da.php' ? 'active' : ''; ?>" 
                               href="achat_da.php">
                            <i class="fas fa-file-alt"></i>
                            <span>Demande d'Achat</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'archive_dashboard.php' ? 'active' : ''; ?>" 
                               href="archive_dashboard.php">
                            <i class="fas fa-archive"></i>
                            <span>Archive System</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'achat_dp.php' ? 'active' : ''; ?>" 
                               href="achat_dp.php">
                            <i class="fas fa-file-invoice"></i>
                            <span>Demande de Prix</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'achat_bc.php' ? 'active' : ''; ?>" 
                               href="achat_bc.php">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span>Bon de Commande</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'achat_be.php' ? 'active' : ''; ?>" 
                               href="achat_be.php">
                            <i class="fas fa-truck-loading"></i>
                            <span>Bon d'Entrée</span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Divider -->
            <li class="nav-divider"></li>
            
            <!-- Rapport Achats (Admin Only) -->
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <li class="nav-item">
                <a href="purchase_performance.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'purchase_performance.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Rapport Achats</span>
                </a>
            </li>
            <li class="nav-divider"></li>
            <?php endif; ?>

            <!-- System Audit (Admin Only) -->
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <li class="nav-item">
                <a href="audit_system.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'audit_system.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shield-alt"></i>
                    <span>System Audit</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="audit_report.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'audit_report.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span>Audit Report</span>
                </a>
            </li>
            <li class="nav-divider"></li>
            <?php endif; ?>
        </ul>
    </nav>
    
    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-details">
                <div class="user-name"><?php echo get_logged_in_user()['full_name']; ?></div>
                <div class="user-role">Administrateur</div>
            </div>
        </div>
        <div class="sidebar-actions">
            <a href="profile.php" class="btn btn-sm btn-outline-light">
                <i class="fas fa-user"></i>
            </a>
            <a href="logout.php" class="btn btn-sm btn-outline-danger">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</aside>

<script>
// Sidebar toggle
document.getElementById('sidebarToggle')?.addEventListener('click', function() {
    document.body.classList.toggle('sidebar-collapsed');
});

// Active menu highlighting and auto-expand
document.addEventListener('DOMContentLoaded', function() {
    const currentPath = window.location.pathname;
    const currentPage = currentPath.split('/').pop();
    
    // Remove all active classes first
    document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
        link.classList.remove('active');
    });
    
    // Add active class to current page links
    document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPage || (currentPage === '' && href === 'index.php')) {
            link.classList.add('active');
            
            // Auto-expand parent collapse if this is in a collapsed menu
            const parentCollapse = link.closest('.collapse');
            if (parentCollapse) {
                const collapseInstance = new bootstrap.Collapse(parentCollapse, {
                    show: true
                });
            }
        }
    });
    
    // Special handling for workshop pages
    const workshopPages = ['admin/admin_breakdowns_workshop.php', 'admin/work_order_view.php', 'admin/work_order_edit.php'];
    if (workshopPages.includes(currentPage)) {
        // Expand maintenance collapse
        const maintenanceCollapse = document.getElementById('maintenanceCollapse');
        if (maintenanceCollapse) {
            const collapseInstance = new bootstrap.Collapse(maintenanceCollapse, {
                show: true
            });
        }
        
        // Add active class to workshop link
        const workshopLink = document.querySelector('a[href="admin/admin_breakdowns_workshop.php"]');
        if (workshopLink) {
            workshopLink.classList.add('active');
        }
    }
});
</script>
