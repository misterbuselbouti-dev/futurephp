<!-- Time Tracking Interface -->
<div class="card mb-4" id="timeTrackingCard">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
            <i class="fas fa-clock me-2"></i>
            Suivi du Temps
        </h6>
        <div id="sessionStatus" class="badge bg-secondary">Non démarré</div>
    </div>
    <div class="card-body">
        <!-- Time Display -->
        <div class="row text-center mb-3">
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-value" id="totalHours">0h 0m</div>
                    <div class="stat-label">Temps total</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-value" id="sessionCount">0</div>
                    <div class="stat-label">Sessions</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-value" id="currentSessionTime">0h 0m</div>
                    <div class="stat-label">Session actuelle</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-value" id="workStatus">En attente</div>
                    <div class="stat-label">Statut</div>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="d-flex justify-content-center gap-2 mb-3">
            <button type="button" class="btn btn-success" id="startBtn" onclick="startWorkSession()">
                <i class="fas fa-play me-2"></i>Démarrer
            </button>
            <button type="button" class="btn btn-warning" id="pauseBtn" onclick="pauseWorkSession()" disabled>
                <i class="fas fa-pause me-2"></i>Pause
            </button>
            <button type="button" class="btn btn-info" id="resumeBtn" onclick="resumeWorkSession()" disabled>
                <i class="fas fa-play me-2"></i>Reprendre
            </button>
            <button type="button" class="btn btn-danger" id="endBtn" onclick="endWorkSession()" disabled>
                <i class="fas fa-stop me-2"></i>Terminer
            </button>
        </div>
        
        <!-- Session Notes -->
        <div class="mb-3">
            <label class="form-label">Notes de session</label>
            <textarea class="form-control" id="sessionNotes" rows="2" 
                      placeholder="Notes sur la session de travail actuelle..."></textarea>
        </div>
        
        <!-- Time Logs -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-history me-2"></i>
                    Historique des sessions
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Action</th>
                                <th>Utilisateur</th>
                                <th>Heure</th>
                                <th>Durée</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody id="timeLogsBody">
                            <tr>
                                <td colspan="5" class="text-center text-muted">Aucune session enregistrée</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Time Tracking Modal -->
<div class="modal fade" id="timeTrackingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-clock me-2"></i>
                    <span id="timeModalTitle">Gestion du Temps</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="timeTrackingForm">
                    <input type="hidden" id="timeAction">
                    <input type="hidden" id="timeBreakdownId">
                    <input type="hidden" id="timeAssignmentId">
                    
                    <div class="mb-3">
                        <label class="form-label">Action</label>
                        <div class="alert alert-info" id="actionDescription">
                            <!-- Action description will be populated here -->
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" id="timeActionNotes" rows="3" 
                                  placeholder="Notes sur cette action..."></textarea>
                    </div>
                    
                    <!-- Time Summary -->
                    <div id="timeSummary" class="card mb-3">
                        <div class="card-body">
                            <h6 class="card-title">Résumé du temps</h6>
                            <div class="row">
                                <div class="col-6">
                                    <p><strong>Temps total:</strong> <span id="summaryTotalTime">0h 0m</span></p>
                                    <p><strong>Nombre de sessions:</strong> <span id="summarySessionCount">0</span></p>
                                </div>
                                <div class="col-6">
                                    <p><strong>Premier début:</strong> <span id="summaryFirstStart">-</span></p>
                                    <p><strong>Dernière action:</strong> <span id="summaryLastAction">-</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="confirmTimeAction()">
                    <i class="fas fa-check me-2"></i>Confirmer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentBreakdownId = null;
let currentAssignmentId = null;
let sessionTimer = null;
let sessionStartTime = null;
let isPaused = false;

// Initialize time tracking
function initTimeTracking(breakdownId, assignmentId) {
    currentBreakdownId = breakdownId;
    currentAssignmentId = assignmentId;
    
    // Load current status
    loadSessionStatus();
    
    // Load time logs
    loadTimeLogs();
    
    // Start timer for current session
    startSessionTimer();
}

// Load session status
function loadSessionStatus() {
    fetch('ajax_time_tracking.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=get_session_status&breakdown_id=${currentBreakdownId}&assignment_id=${currentAssignmentId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateSessionStatus(data.status, data.last_action);
            updateTimeDisplay(data.actual_hours);
        }
    })
    .catch(error => console.error('Error:', error));
}

// Update session status
function updateSessionStatus(status, lastAction) {
    const statusElement = document.getElementById('sessionStatus');
    const startBtn = document.getElementById('startBtn');
    const pauseBtn = document.getElementById('pauseBtn');
    const resumeBtn = document.getElementById('resumeBtn');
    const endBtn = document.getElementById('endBtn');
    const workStatus = document.getElementById('workStatus');
    
    // Reset all buttons
    startBtn.disabled = false;
    pauseBtn.disabled = true;
    resumeBtn.disabled = true;
    endBtn.disabled = true;
    
    // Update status badge and buttons based on status
    switch (status) {
        case 'pending':
            statusElement.textContent = 'Non démarré';
            statusElement.className = 'badge bg-secondary';
            workStatus.textContent = 'En attente';
            break;
            
        case 'in_progress':
            statusElement.textContent = 'En cours';
            statusElement.className = 'badge bg-primary';
            workStatus.textContent = 'En cours';
            startBtn.disabled = true;
            pauseBtn.disabled = false;
            endBtn.disabled = false;
            break;
            
        case 'paused':
            statusElement.textContent = 'En pause';
            statusElement.className = 'badge bg-warning';
            workStatus.textContent = 'En pause';
            startBtn.disabled = true;
            resumeBtn.disabled = false;
            endBtn.disabled = false;
            break;
            
        case 'completed':
            statusElement.textContent = 'Terminé';
            statusElement.className = 'badge bg-success';
            workStatus.textContent = 'Terminé';
            startBtn.disabled = true;
            pauseBtn.disabled = true;
            resumeBtn.disabled = true;
            endBtn.disabled = true;
            break;
    }
}

// Start work session
function startWorkSession() {
    showTimeActionModal('start', 'Démarrer une nouvelle session de travail');
}

// Pause work session
function pauseWorkSession() {
    showTimeActionModal('pause', 'Mettre en pause la session actuelle');
}

// Resume work session
function resumeWorkSession() {
    showTimeActionModal('resume', 'Reprendre la session de travail');
}

// End work session
function endWorkSession() {
    showTimeActionModal('end', 'Terminer la session de travail');
}

// Show time action modal
function showTimeActionModal(action, description) {
    document.getElementById('timeAction').value = action;
    document.getElementById('timeBreakdownId').value = currentBreakdownId;
    document.getElementById('timeAssignmentId').value = currentAssignmentId;
    document.getElementById('timeModalTitle').textContent = description;
    document.getElementById('actionDescription').textContent = description;
    
    // Load time summary
    loadTimeSummary();
    
    // Show modal
    new bootstrap.Modal(document.getElementById('timeTrackingModal')).show();
}

// Load time summary
function loadTimeSummary() {
    fetch('ajax_time_tracking.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=get_time_summary&breakdown_id=${currentBreakdownId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const summary = data.summary;
            document.getElementById('summaryTotalTime').textContent = formatDuration(summary.actual_hours || 0);
            document.getElementById('summarySessionCount').textContent = summary.total_sessions || 0;
            document.getElementById('summaryFirstStart').textContent = summary.first_start ? new Date(summary.first_start).toLocaleString() : '-';
            document.getElementById('summaryLastAction').textContent = summary.last_action ? new Date(summary.last_action).toLocaleString() : '-';
        }
    })
    .catch(error => console.error('Error:', error));
}

// Confirm time action
function confirmTimeAction() {
    const action = document.getElementById('timeAction').value;
    const notes = document.getElementById('timeActionNotes').value;
    
    fetch('ajax_time_tracking.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=${action}_session&breakdown_id=${currentBreakdownId}&assignment_id=${currentAssignmentId}&notes=${encodeURIComponent(notes)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('timeTrackingModal')).hide();
            
            // Show success message
            showAlert(data.message, 'success');
            
            // Update status
            loadSessionStatus();
            
            // Load time logs
            loadTimeLogs();
            
            // Handle specific actions
            if (action === 'start') {
                sessionStartTime = new Date();
                isPaused = false;
            } else if (action === 'pause') {
                isPaused = true;
            } else if (action === 'resume') {
                isPaused = false;
            } else if (action === 'end') {
                sessionStartTime = null;
                isPaused = false;
            }
            
            // Clear notes
            document.getElementById('sessionNotes').value = '';
            document.getElementById('timeActionNotes').value = '';
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Erreur lors de l\'action', 'danger');
    });
}

// Load time logs
function loadTimeLogs() {
    fetch('ajax_time_tracking.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=get_time_logs&breakdown_id=${currentBreakdownId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayTimeLogs(data.logs);
        }
    })
    .catch(error => console.error('Error:', error));
}

// Display time logs
function displayTimeLogs(logs) {
    const tbody = document.getElementById('timeLogsBody');
    
    if (logs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Aucune session enregistrée</td></tr>';
        return;
    }
    
    tbody.innerHTML = '';
    
    logs.forEach((log, index) => {
        const row = document.createElement('tr');
        
        // Action badge
        let actionBadge = '';
        switch (log.action_type) {
            case 'start':
                actionBadge = '<span class="badge bg-success">Démarrage</span>';
                break;
            case 'pause':
                actionBadge = '<span class="badge bg-warning">Pause</span>';
                break;
            case 'resume':
                actionBadge = '<span class="badge bg-info">Reprise</span>';
                break;
            case 'end':
                actionBadge = '<span class="badge bg-danger">Fin</span>';
                break;
        }
        
        // Calculate duration (simplified)
        let duration = '-';
        if (index < logs.length - 1) {
            const nextLog = logs[index + 1];
            const durationMs = new Date(nextLog.action_time) - new Date(log.action_time);
            duration = formatDuration(durationMs / 3600000);
        }
        
        row.innerHTML = `
            <td>${actionBadge}</td>
            <td>${log.full_name}</td>
            <td>${log.formatted_time}</td>
            <td>${duration}</td>
            <td>${log.notes || '-'}</td>
        `;
        
        tbody.appendChild(row);
    });
}

// Start session timer
function startSessionTimer() {
    if (sessionTimer) {
        clearInterval(sessionTimer);
    }
    
    sessionTimer = setInterval(() => {
        if (sessionStartTime && !isPaused) {
            updateCurrentSessionTime();
        }
    }, 1000);
}

// Update current session time
function updateCurrentSessionTime() {
    if (!sessionStartTime) return;
    
    const now = new Date();
    const duration = (now - sessionStartTime) / 1000; // in seconds
    const hours = Math.floor(duration / 3600);
    const minutes = Math.floor((duration % 3600) / 60);
    
    document.getElementById('currentSessionTime').textContent = `${hours}h ${minutes}m`;
}

// Update time display
function updateTimeDisplay(hours) {
    document.getElementById('totalHours').textContent = formatDuration(hours || 0);
}

// Format duration
function formatDuration(hours) {
    if (!hours || hours <= 0) return '0h 0m';
    const h = Math.floor(hours);
    const m = Math.round((hours - h) * 60);
    return `${h}h ${m}m`;
}

// Show alert
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const card = document.getElementById('timeTrackingCard');
    card.insertBefore(alertDiv, card.firstChild);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}
</script>
