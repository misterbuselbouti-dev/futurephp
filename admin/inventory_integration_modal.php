<!-- Inventory Integration Modal -->
<div class="modal fade" id="inventoryModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-boxes me-2"></i>
                    Gestion des Pièces - <span id="breakdownRef">-</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Search Section -->
                <div class="row mb-3">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="articleSearch" 
                                   placeholder="Rechercher par référence, désignation, code barre...">
                            <button class="btn btn-primary" type="button" onclick="searchArticles()">
                                Rechercher
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-exclamation-triangle text-warning"></i></span>
                            <select class="form-select" id="stockFilter" onchange="filterArticles()">
                                <option value="">Tous les articles</option>
                                <option value="available">Disponibles</option>
                                <option value="low">Stock bas</option>
                                <option value="critical">Stock critique</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Search Results -->
                <div id="searchResults" class="mb-3" style="display: none;">
                    <h6><i class="fas fa-list me-2"></i>Résultats de recherche</h6>
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Référence</th>
                                    <th>Désignation</th>
                                    <th>Stock</th>
                                    <th>Prix</th>
                                    <th>Statut</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="searchResultsBody">
                                <!-- Results will be populated here -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Current Work Items -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-tools me-2"></i>
                            Pièces utilisées (<span id="itemsCount">0</span>)
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Référence</th>
                                        <th>Désignation</th>
                                        <th>Quantité</th>
                                        <th>Prix unitaire</th>
                                        <th>Total</th>
                                        <th>Notes</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="workItemsBody">
                                    <!-- Work items will be populated here -->
                                </tbody>
                                <tfoot>
                                    <tr class="table-primary">
                                        <th colspan="4">Total Coût:</th>
                                        <th id="totalCost">0.00 MAD</th>
                                        <th colspan="2"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <small>
                                        Les pièces utilisées seront automatiquement déduites du stock.
                                        Le stock sera restauré si une pièce est retirée.
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6 text-end">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="exportWorkItems()">
                                    <i class="fas fa-download me-1"></i>Exporter
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-success" onclick="saveWorkItems()">
                    <i class="fas fa-save me-2"></i>Enregistrer les modifications
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>
                    Ajouter une pièce
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addItemForm">
                    <input type="hidden" id="selectedArticleId">
                    <input type="hidden" id="selectedAssignmentId">
                    
                    <div class="mb-3">
                        <label class="form-label">Article sélectionné</label>
                        <div class="card">
                            <div class="card-body">
                                <h6 id="selectedArticleName" class="mb-1">-</h6>
                                <p class="mb-0">
                                    <small class="text-muted">
                                        Référence: <span id="selectedArticleRef">-</span> | 
                                        Stock disponible: <span id="selectedArticleStock" class="fw-bold">-</span>
                                    </small>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Quantité <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="itemQuantity" 
                                       min="0.01" step="0.01" required>
                                <div class="form-text">
                                    Unité: <span id="selectedArticleUnit">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Prix unitaire (MAD)</label>
                                <input type="number" class="form-control" id="itemUnitCost" 
                                       min="0" step="0.01">
                                <div class="form-text">
                                    Total: <span id="itemTotalCost" class="fw-bold">0.00 MAD</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" id="itemNotes" rows="2" 
                                  placeholder="Notes sur l'utilisation de cette pièce..."></textarea>
                    </div>
                    
                    <!-- Stock Warning -->
                    <div id="stockWarning" class="alert alert-warning" style="display: none;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <span id="stockWarningText"></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="confirmAddItem()">
                    <i class="fas fa-plus me-2"></i>Ajouter
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentBreakdownId = null;
let currentAssignmentId = null;
let workItems = [];
let searchResults = [];

// Open inventory modal
function openInventoryModal(breakdownId, assignmentId) {
    currentBreakdownId = breakdownId;
    currentAssignmentId = assignmentId;
    
    document.getElementById('breakdownRef').textContent = 'BRK-' + breakdownId;
    
    // Load existing work items
    loadWorkItems();
    
    // Show modal
    new bootstrap.Modal(document.getElementById('inventoryModal')).show();
}

// Search articles
function searchArticles() {
    const searchTerm = document.getElementById('articleSearch').value.trim();
    
    if (searchTerm.length < 2) {
        document.getElementById('searchResults').style.display = 'none';
        return;
    }
    
    fetch('ajax_inventory_integration.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=search_articles&search_term=${encodeURIComponent(searchTerm)}&breakdown_id=${currentBreakdownId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            searchResults = data.articles;
            displaySearchResults(data.articles);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Erreur lors de la recherche', 'danger');
    });
}

// Display search results
function displaySearchResults(articles) {
    const tbody = document.getElementById('searchResultsBody');
    tbody.innerHTML = '';
    
    articles.forEach(article => {
        const row = document.createElement('tr');
        
        // Stock status badge
        let stockBadge = '';
        if (article.stock_status === 'critical') {
            stockBadge = '<span class="badge bg-danger">Critique</span>';
        } else if (article.stock_status === 'low') {
            stockBadge = '<span class="badge bg-warning">Bas</span>';
        } else {
            stockBadge = '<span class="badge bg-success">Disponible</span>';
        }
        
        row.innerHTML = `
            <td>${article.reference}</td>
            <td>${article.designation}</td>
            <td>${article.stock_actuel} ${article.unite}</td>
            <td>${article.prix_achat} MAD</td>
            <td>${stockBadge}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="selectArticle(${article.id})" 
                        ${article.stock_actuel <= 0 ? 'disabled' : ''}>
                    <i class="fas fa-plus me-1"></i>Ajouter
                </button>
            </td>
        `;
        
        tbody.appendChild(row);
    });
    
    document.getElementById('searchResults').style.display = 'block';
}

// Select article
function selectArticle(articleId) {
    const article = searchResults.find(a => a.id === articleId);
    if (!article) return;
    
    // Populate add item modal
    document.getElementById('selectedArticleId').value = article.id;
    document.getElementById('selectedAssignmentId').value = currentAssignmentId;
    document.getElementById('selectedArticleName').textContent = article.designation;
    document.getElementById('selectedArticleRef').textContent = article.reference;
    document.getElementById('selectedArticleStock').textContent = `${article.stock_actuel} ${article.unite}`;
    document.getElementById('selectedArticleUnit').textContent = article.unite;
    document.getElementById('itemUnitCost').value = article.prix_achat;
    
    // Show stock warning if needed
    const warning = document.getElementById('stockWarning');
    if (article.stock_status === 'critical') {
        warning.style.display = 'block';
        document.getElementById('stockWarningText').textContent = 
            `Attention: Stock critique (${article.stock_actuel} ${article.unite} disponible)`;
    } else if (article.stock_status === 'low') {
        warning.style.display = 'block';
        document.getElementById('stockWarningText').textContent = 
            `Stock bas (${article.stock_actuel} ${article.unite} disponible)`;
    } else {
        warning.style.display = 'none';
    }
    
    // Calculate total on quantity change
    document.getElementById('itemQuantity').addEventListener('input', calculateItemTotal);
    document.getElementById('itemUnitCost').addEventListener('input', calculateItemTotal);
    
    // Show modal
    new bootstrap.Modal(document.getElementById('addItemModal')).show();
}

// Calculate item total
function calculateItemTotal() {
    const quantity = parseFloat(document.getElementById('itemQuantity').value) || 0;
    const unitCost = parseFloat(document.getElementById('itemUnitCost').value) || 0;
    const total = quantity * unitCost;
    
    document.getElementById('itemTotalCost').textContent = `${total.toFixed(2)} MAD`;
}

// Confirm add item
function confirmAddItem() {
    const articleId = document.getElementById('selectedArticleId').value;
    const assignmentId = document.getElementById('selectedAssignmentId').value;
    const quantity = parseFloat(document.getElementById('itemQuantity').value);
    const unitCost = parseFloat(document.getElementById('itemUnitCost').value);
    const notes = document.getElementById('itemNotes').value;
    
    if (!quantity || quantity <= 0) {
        showAlert('Veuillez entrer une quantité valide', 'warning');
        return;
    }
    
    fetch('ajax_inventory_integration.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add_work_item&breakdown_id=${currentBreakdownId}&assignment_id=${assignmentId}&article_id=${articleId}&quantity=${quantity}&unit_cost=${unitCost}&notes=${encodeURIComponent(notes)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('addItemModal')).hide();
            
            // Show success message
            showAlert(data.message, 'success');
            
            // Reload work items
            loadWorkItems();
            
            // Clear search
            document.getElementById('articleSearch').value = '';
            document.getElementById('searchResults').style.display = 'none';
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Erreur lors de l\'ajout', 'danger');
    });
}

// Load work items
function loadWorkItems() {
    fetch('ajax_inventory_integration.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=get_breakdown_items&breakdown_id=${currentBreakdownId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            workItems = data.items;
            displayWorkItems();
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Display work items
function displayWorkItems() {
    const tbody = document.getElementById('workItemsBody');
    tbody.innerHTML = '';
    
    let totalCost = 0;
    
    workItems.forEach((item, index) => {
        const total = item.quantity_used * item.unit_cost;
        totalCost += total;
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.reference}</td>
            <td>${item.designation}</td>
            <td>${item.quantity_used} ${item.unite}</td>
            <td>${item.unit_cost} MAD</td>
            <td>${total.toFixed(2)} MAD</td>
            <td>${item.notes || '-'}</td>
            <td>
                <button class="btn btn-sm btn-danger" onclick="removeWorkItem(${item.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        
        tbody.appendChild(row);
    });
    
    document.getElementById('itemsCount').textContent = workItems.length;
    document.getElementById('totalCost').textContent = `${totalCost.toFixed(2)} MAD`;
}

// Remove work item
function removeWorkItem(itemId) {
    if (!confirm('Êtes-vous sûr de vouloir retirer cette pièce? Le stock sera restauré.')) {
        return;
    }
    
    fetch('ajax_inventory_integration.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=remove_work_item&work_item_id=${itemId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            loadWorkItems();
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Erreur lors du retrait', 'danger');
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
    
    const modalBody = document.querySelector('#inventoryModal .modal-body');
    modalBody.insertBefore(alertDiv, modalBody.firstChild);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Export work items
function exportWorkItems() {
    // Implementation for exporting work items to CSV
    console.log('Exporting work items...');
}

// Filter articles
function filterArticles() {
    const filter = document.getElementById('stockFilter').value;
    // Implementation for filtering articles
    console.log('Filtering by:', filter);
}

// Search on Enter key
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('articleSearch');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchArticles();
            }
        });
    }
});
</script>
