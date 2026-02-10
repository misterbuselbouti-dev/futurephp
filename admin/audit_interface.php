<!-- Audit and Logging Interface -->
<div class="iso-card mb-4" id="auditInterface">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h6 class="mb-0">
            <i class="fas fa-history me-2"></i>
            Journal des modifications
        </h6>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary btn-sm" onclick="refreshAuditLogs()">
                <i class="fas fa-sync me-1"></i>Actualiser
            </button>
            <button type="button" class="btn btn-success btn-sm" onclick="exportAuditLogs()">
                <i class="fas fa-download me-1"></i>Exporter
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearAuditFilters()">
                <i class="fas fa-times me-1"></i>Effacer
            </button>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-md-3">
            <label class="form-label">Action</label>
            <select class="form-select" id="auditActionFilter" onchange="filterAuditLogs()">
                <option value="">Toutes les actions</option>
                <option value="assignment">Assignation</option>
                <option value="work_started">Début travail</option>
                <option value="work_ended">Fin travail</option>
                <option value="session_started">Session démarrée</option>
                <option value="session_paused">Session mise en pause</option>
                <option value="session_resumed">Session reprise</option>
                <option value="session_ended">Session terminée</option>
                <option value="item_added">Pièce ajoutée</option>
                <option value="item_removed">Pièce retirée</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Date début</label>
            <input type="date" class="form-control" id="auditDateFrom" onchange="filterAuditLogs()">
        </div>
        <div class="col-md-3">
            <label class="form-label">Date fin</label>
            <input type="date" class="form-control" id="auditDateTo" onchange="filterAuditLogs()">
        </div>
        <div class="col-md-3">
            <label class="form-label">&nbsp;</label>
            <button type="button" class="btn btn-outline-warning btn-sm w-100" onclick="clearAuditFilters()">
                <i class="fas fa-times me-1"></i>Effacer filtres
            </button>
        </div>
    </div>
    
    <!-- Statistics Summary -->
    <div class="iso-stats-grid">
        <div class="stat-card text-center">
            <div class="stat-value" id="totalActions">0</div>
            <div class="stat-label">Total actions</div>
        </div>
        <div class="stat-card text-center">
            <div class="stat-value" id="todayActions">0</div>
            <div class="stat-label">Aujourd'hui</div>
        </div>
        <div class="stat-card text-center">
            <div class="stat-value" id="uniqueUsers">0</div>
            <div class="stat-label">Utilisateurs</div>
        </div>
        <div class="stat-card text-center">
            <div class="stat-value" id="avgActionsPerDay">0</div>
            <div class="stat-label">Actions/jour</div>
        </div>
    </div>
    
    <!-- Audit Logs Table -->
    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
        <table class="table table-hover">
            <thead class="table-light sticky-top">
                <tr>
                    <th>Date</th>
                    <th>Action</th>
                    <th>Champ</th>
                    <th>Ancienne valeur</th>
                    <th>Nouvelle valeur</th>
                    <th>Utilisateur</th>
                    <th>Adresse IP</th>
                </tr>
            </thead>
            <tbody id="auditLogsBody">
                <tr>
                    <td colspan="8" class="text-center text-muted">
                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                        Chargement...
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="text-muted">
                <small>Affichage: <span id="auditLogsCount">0</span> - Total: <span id="auditLogsTotal">0</span></small>
            </div>
            <nav>
                <ul class="pagination pagination-sm mb-0" id="auditPagination">
                    <!-- Pagination will be populated here -->
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Field History Modal -->
<div class="modal fade" id="fieldHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-history me-2"></i>
                    Historique du champ: <span id="fieldHistoryName">-</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Action</th>
                                <th>Ancienne valeur</th>
                                <th>Nouvelle valeur</th>
                                <th>Utilisateur</th>
                            </tr>
                        </thead>
                        <tbody id="fieldHistoryBody">
                            <!-- History will be populated here -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- System Statistics Modal -->
<div class="modal fade" id="systemStatsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-chart-bar me-2"></i>
                    Statistiques du système
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Période</label>
                        <div class="input-group">
                            <input type="date" class="form-control" id="statsDateFrom">
                            <span class="input-group-text">à</span>
                            <input type="date" class="form-control" id="statsDateTo">
                            <button class="btn btn-primary" onclick="loadSystemStats()">Actualiser</button>
                        </div>
                    </div>
                </div>
                
                <!-- Statistics Content -->
                <div id="systemStatsContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status"></div>
                        <p class="mt-2">Chargement des statistiques...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentBreakdownId = null;
let auditLogsCurrentPage = 1;
let auditLogsPerPage = 20;
let auditLogsTotal = 0;

// Initialize audit interface
function initAuditInterface(breakdownId) {
    currentBreakdownId = breakdownId;
    
    // Set default date range (last 30 days)
    const today = new Date();
    const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
    
    document.getElementById('auditDateFrom').value = thirtyDaysAgo.toISOString().split('T')[0];
    document.getElementById('auditDateTo').value = today.toISOString().split('T')[0];
    
    // Load audit logs
    loadAuditLogs();
    
    // Load statistics
    loadAuditStatistics();
}

// Load audit logs
function loadAuditLogs(page = 1) {
    auditLogsCurrentPage = page;
    
    const actionFilter = document.getElementById('auditActionFilter').value;
    const dateFrom = document.getElementById('auditDateFrom').value;
    const dateTo = document.getElementById('auditDateTo').value;
    
    const offset = (page - 1) * auditLogsPerPage;
    
    fetch('ajax_audit_system.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=get_audit_logs&breakdown_id=${currentBreakdownId}&limit=${auditLogsPerPage}&offset=${offset}&action_filter=${actionFilter}&date_from=${dateFrom}&date_to=${dateTo}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayAuditLogs(data.logs);
            updateAuditPagination(data.total, page);
            auditLogsTotal = data.total;
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Erreur lors du chargement des logs', 'danger');
    });
}

// Display audit logs
function displayAuditLogs(logs) {
    const tbody = document.getElementById('auditLogsBody');
    
    if (logs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Aucune action trouvée</td></tr>';
        return;
    }
    
    tbody.innerHTML = '';
    
    logs.forEach(log => {
        const row = document.createElement('tr');
        
        // Format old/new values
        const formatValue = (value) => {
            if (!value) return '-';
            try {
                const parsed = JSON.parse(value);
                if (typeof parsed === 'object') {
                    return JSON.stringify(parsed, null, 2);
                }
                return parsed;
            } catch {
                return value;
            }
        };
        
        // Action badge
        let actionBadge = '';
        switch (log.action_type) {
            case 'assignment':
                actionBadge = '<span class="badge bg-primary">Assignation</span>';
                break;
            case 'work_started':
                actionBadge = '<span class="badge bg-success">Début travail</span>';
                break;
            case 'work_ended':
                actionBadge = '<span class="badge bg-danger">Fin travail</span>';
                break;
            case 'session_started':
                actionBadge = '<span class="badge bg-info">Session démarrée</span>';
                break;
            case 'session_paused':
                actionBadge = '<span class="badge bg-warning">Session mise en pause</span>';
                break;
            case 'session_resumed':
                actionBadge = '<span class="badge bg-info">Session reprise</span>';
                break;
            case 'session_ended':
                actionBadge = '<span class="badge bg-danger">Session terminée</span>';
                break;
            case 'item_added':
                actionBadge = '<span class="badge bg-success">Pièce ajoutée</span>';
                break;
            case 'item_removed':
                actionBadge = '<span class="badge bg-warning">Pièce retirée</span>';
                break;
            default:
                actionBadge = `<span class="badge bg-secondary">${log.action_type}</span>`;
        }
        
        row.innerHTML = `
            <td>${log.formatted_time}</td>
            <td>${actionBadge}</td>
            <td>${log.field_name || '-'}</td>
            <td><code class="text-muted">${formatValue(log.old_value)}</code></td>
            <td><code class="text-muted">${formatValue(log.new_value)}</code></td>
            <td>
                <div>${log.performed_by_name}</div>
                <small class="text-muted">${log.performed_by_role}</small>
            </td>
            <td><small class="text-muted">${log.ip_address || '-'}</small></td>
        `;
        
        tbody.appendChild(row);
    });
    
    // Update count
    document.getElementById('auditLogsCount').textContent = logs.length;
}

// Update pagination
function updateAuditPagination(total, currentPage) {
    const totalPages = Math.ceil(total / auditLogsPerPage);
    const pagination = document.getElementById('auditPagination');
    
    pagination.innerHTML = '';
    
    // Previous button
    const prevDisabled = currentPage <= 1 ? 'disabled' : '';
    pagination.innerHTML += `
        <li class="page-item ${prevDisabled}">
            <a class="page-link" href="#" onclick="loadAuditLogs(${currentPage - 1})" ${prevDisabled}>Précédent</a>
        </li>
    `;
    
    // Page numbers
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        const active = i === currentPage ? 'active' : '';
        pagination.innerHTML += `
            <li class="page-item ${active}">
                <a class="page-link" href="#" onclick="loadAuditLogs(${i})">${i}</a>
            </li>
        `;
    }
    
    // Next button
    const nextDisabled = currentPage >= totalPages ? 'disabled' : '';
    pagination.innerHTML += `
        <li class="page-item ${nextDisabled}">
            <a class="page-link" href="#" onclick="loadAuditLogs(${currentPage + 1})" ${nextDisabled}>Suivant</a>
        </li>
    `;
    
    // Update total
    document.getElementById('auditLogsTotal').textContent = total;
}

// Filter audit logs
function filterAuditLogs() {
    loadAuditLogs(1);
}

// Clear audit filters
function clearAuditFilters() {
    document.getElementById('auditActionFilter').value = '';
    document.getElementById('auditDateFrom').value = '';
    document.getElementById('auditDateTo').value = '';
    loadAuditLogs(1);
}

// Refresh audit logs
function refreshAuditLogs() {
    loadAuditLogs(auditLogsCurrentPage);
    loadAuditStatistics();
}

// Export audit logs
function exportAuditLogs() {
    fetch('ajax_audit_system.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=export_audit_logs&breakdown_id=${currentBreakdownId}&format=csv`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Create download link
            const blob = new Blob([data.csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = data.filename;
            a.click();
            window.URL.revokeObjectURL(url);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Erreur lors de l\'export', 'danger');
    });
}

// Load audit statistics
function loadAuditStatistics() {
    // This would load statistics for the current breakdown
    // Implementation would go here
    console.log('Loading audit statistics...');
}

// Show field history
function showFieldHistory(fieldName) {
    fetch('ajax_audit_system.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=get_field_history&breakdown_id=${currentBreakdownId}&field_name=${fieldName}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('fieldHistoryName').textContent = fieldName;
            displayFieldHistory(data.history);
            new bootstrap.Modal(document.getElementById('fieldHistoryModal')).show();
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Erreur lors du chargement de l\'historique', 'danger');
    });
}

// Display field history
function displayFieldHistory(history) {
    const tbody = document.getElementById('fieldHistoryBody');
    
    if (history.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Aucun historique trouvé</td></tr>';
        return;
    }
    
    tbody.innerHTML = '';
    
    history.forEach(log => {
        const row = document.createElement('tr');
        
        const formatValue = (value) => {
            if (!value) return '-';
            try {
                return JSON.parse(value);
            } catch {
                return value;
            }
        };
        
        row.innerHTML = `
            <td>${log.performed_at}</td>
            <td><span class="badge bg-info">${log.action_display}</span></td>
            <td><code>${formatValue(log.old_value)}</code></td>
            <td><code>${formatValue(log.new_value)}</code></td>
            <td>${log.performed_by_name}</td>
        `;
        
        tbody.appendChild(row);
    });
}

// Show alert
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const card = document.getElementById('auditInterface');
    card.insertBefore(alertDiv, card.firstChild);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}
</script>
