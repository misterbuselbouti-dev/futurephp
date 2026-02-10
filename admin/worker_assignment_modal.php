<!-- Worker Assignment Modal -->
<div class="modal fade" id="assignmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i>
                    <span id="modalTitle">Assigner un Technicien</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="assignmentForm">
                    <input type="hidden" id="breakdownId" name="breakdown_id">
                    <input type="hidden" id="assignmentId" name="assignment_id">
                    <input type="hidden" id="actionType" name="action">
                    
                    <!-- Breakdown Info -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="card-title">Informations de l'Incident</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Référence:</strong> <span id="modalRef">-</span></p>
                                    <p><strong>Bus:</strong> <span id="modalBus">-</span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Type:</strong> <span id="modalType">-</span></p>
                                    <p><strong>Urgence:</strong> <span id="modalUrgency">-</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Technician Selection -->
                    <div class="mb-3">
                        <label class="form-label">Technicien <span class="text-danger">*</span></label>
                        <select class="form-select" id="technicianSelect" name="technician_id" required>
                            <option value="">Sélectionner un technicien...</option>
                        </select>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Charge de travail actuelle: <span id="workloadInfo">-</span>
                        </div>
                    </div>
                    
                    <!-- Notes -->
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" id="assignmentNotes" name="notes" rows="3" 
                                  placeholder="Instructions spéciales pour le technicien..."></textarea>
                    </div>
                    
                    <!-- Time Tracking Info -->
                    <div id="timeTrackingInfo" class="card mb-3" style="display: none;">
                        <div class="card-body">
                            <h6 class="card-title">Suivi du Temps</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <p><strong>Début:</strong> <span id="startTime">-</span></p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>Fin:</strong> <span id="endTime">-</span></p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>Durée:</strong> <span id="duration">-</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Work Items -->
                    <div id="workItemsSection" class="card mb-3" style="display: none;">
                        <div class="card-body">
                            <h6 class="card-title">Pièces Utilisées</h6>
                            <div id="workItemsList"></div>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addWorkItem()">
                                <i class="fas fa-plus me-1"></i>Ajouter une pièce
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="saveAssignment()">
                    <i class="fas fa-save me-2"></i>Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions Modal -->
<div class="modal fade" id="quickActionsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-bolt me-2"></i>
                    Actions Rapides
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary" onclick="quickAssign()">
                        <i class="fas fa-user-plus me-2"></i>Assigner un Technicien
                    </button>
                    <button type="button" class="btn btn-success" onclick="quickStartWork()">
                        <i class="fas fa-play me-2"></i>Démarrer le Travail
                    </button>
                    <button type="button" class="btn btn-warning" onclick="quickPauseWork()">
                        <i class="fas fa-pause me-2"></i>Mettre en Pause
                    </button>
                    <button type="button" class="btn btn-danger" onclick="quickEndWork()">
                        <i class="fas fa-stop me-2"></i>Terminer le Travail
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentBreakdownId = null;
let currentAssignmentId = null;

// Open assignment modal
function openAssignmentModal(breakdownId, action = 'assign') {
    currentBreakdownId = breakdownId;
    document.getElementById('breakdownId').value = breakdownId;
    document.getElementById('actionType').value = action;
    
    // Load breakdown info
    fetch(`ajax_worker_assignment.php?action=get_assignment_details&breakdown_id=${breakdownId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update modal with breakdown info
                // This would be populated from the breakdown data
                document.getElementById('modalRef').textContent = 'BRK-' + breakdownId;
                
                // Load available technicians
                loadAvailableTechnicians();
                
                // Show/hide sections based on action
                if (action === 'start' || action === 'end') {
                    document.getElementById('timeTrackingInfo').style.display = 'block';
                    document.getElementById('workItemsSection').style.display = 'block';
                }
                
                // Update modal title
                const titles = {
                    'assign': 'Assigner un Technicien',
                    'start': 'Démarrer le Travail',
                    'end': 'Terminer le Travail'
                };
                document.getElementById('modalTitle').textContent = titles[action] || 'Assigner un Technicien';
                
                // Show modal
                new bootstrap.Modal(document.getElementById('assignmentModal')).show();
            }
        })
        .catch(error => console.error('Error:', error));
}

// Load available technicians
function loadAvailableTechnicians() {
    fetch('ajax_worker_assignment.php?action=get_available_technicians')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('technicianSelect');
                select.innerHTML = '<option value="">Sélectionner un technicien...</option>';
                
                data.technicians.forEach(tech => {
                    const option = document.createElement('option');
                    option.value = tech.id;
                    option.textContent = `${tech.full_name} (${tech.role}) - ${tech.active_assignments} actifs`;
                    option.dataset.workload = tech.current_workload;
                    select.appendChild(option);
                });
                
                // Update workload info on selection
                select.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const workload = selectedOption.dataset.workload || '0';
                    document.getElementById('workloadInfo').textContent = `${workload} assignments actives`;
                });
            }
        })
        .catch(error => console.error('Error:', error));
}

// Save assignment
function saveAssignment() {
    const form = document.getElementById('assignmentForm');
    const formData = new FormData(form);
    
    fetch('ajax_worker_assignment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('assignmentModal')).hide();
            
            // Show success message
            showAlert(data.message, 'success');
            
            // Reload page or update table
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Erreur lors de l\'enregistrement', 'danger');
    });
}

// Quick assign function
function quickAssign(breakdownId) {
    openAssignmentModal(breakdownId, 'assign');
}

// Quick start work
function quickStartWork(breakdownId) {
    if (confirm('Démarrer le travail pour cet incident?')) {
        // Implementation would go here
        console.log('Starting work for breakdown:', breakdownId);
    }
}

// Show alert message
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Add work item function
function addWorkItem() {
    // Implementation for adding work items
    console.log('Adding work item...');
}
</script>
